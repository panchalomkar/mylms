<?php
require_once('../../../config.php');
require_login();
require_sesskey();

header('Content-Type: application/json');

// ── Get OpenAI key from Moodle config.php ──────────────────────────
// Add this line to your config.php:
//   $CFG->openai_api_key = 'sk-xxxxxxxxxxxxxxxxxxxxxxxx';
if (empty($CFG->openai_api_key)) {
    echo json_encode(['error' => 'OpenAI API key not set in config.php. Add: $CFG->openai_api_key = "sk-xxx...";']);
    exit;
}

$openai_key = $CFG->openai_api_key;

// ── Params ───────────────────────────────────────────────────────────
$texts = required_param('texts', PARAM_RAW);
$lang  = required_param('lang',  PARAM_ALPHA);

$allowed = ['hi', 'mr', 'gu', 'en'];
if (!in_array($lang, $allowed, true)) {
    echo json_encode(['error' => 'Invalid language']);
    exit;
}

$texts_array = json_decode($texts, true);
if (!is_array($texts_array)) {
    echo json_encode(['error' => 'Invalid texts input']);
    exit;
}

// Dedupe + drop empty/too-short strings.
$texts_array = array_values(array_unique(array_filter($texts_array, function ($t) {
    return strlen(trim($t)) > 1;
})));

if (empty($texts_array)) {
    echo json_encode(['translations' => [], 'source' => 'empty']);
    exit;
}

// ── Layer 1: cache (APCu, falls back to file) ───────────────────────
$cache = local_langswitch_get_cache($lang);

$result = [];
$need   = [];

foreach ($texts_array as $text) {
    $key = md5(trim($text));
    if (array_key_exists($key, $cache)) {
        $result[$text] = $cache[$key];
    } else {
        $need[] = $text;
    }
}

// All in cache — return immediately, no OpenAI round trip.
if (empty($need)) {
    echo json_encode(['translations' => $result, 'source' => 'cache']);
    exit;
}

// ── Layer 2: OpenAI, only for genuinely uncached strings ────────────
$translated = local_langswitch_call_openai($need, $lang, $openai_key);

if ($translated === null) {
    // Fail soft: return whatever was already cached. The client keeps
    // showing the original text for the misses and will retry on the
    // next scan instead of the whole batch erroring out.
    echo json_encode(['translations' => $result, 'source' => 'partial_cache_only']);
    exit;
}

foreach ($translated as $original => $val) {
    $result[$original] = $val;
}

// Locked, incremental merge — never rewrites/loses concurrent entries.
local_langswitch_merge_cache($lang, $translated);

echo json_encode([
    'translations' => $result,
    'source'       => 'openai',
    'count'        => count($translated),
]);

// ─────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────

/**
 * Read the translation cache for a language. APCu-fronted so a warm
 * server serves repeat requests without re-reading/decoding the file.
 */
function local_langswitch_get_cache(string $lang): array {
    $apcukey = "langswitch_cache_{$lang}";

    if (function_exists('apcu_fetch')) {
        $hit = apcu_fetch($apcukey, $ok);
        if ($ok && is_array($hit)) {
            return $hit;
        }
    }

    $file  = __DIR__ . "/../translations/{$lang}_cache.json";
    $cache = [];

    if (is_file($file)) {
        $raw     = file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        $cache   = is_array($decoded) ? $decoded : [];
    }

    if (function_exists('apcu_store')) {
        apcu_store($apcukey, $cache, 300); // 5 min TTL; refreshed on every write.
    }

    return $cache;
}

/**
 * Merge new entries into the on-disk cache under an exclusive lock,
 * then refresh APCu. Read-modify-write happens inside the lock so
 * concurrent requests can never clobber each other's translations.
 */
function local_langswitch_merge_cache(string $lang, array $newentries): void {
    $dir = __DIR__ . '/../translations/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . "{$lang}_cache.json";

    $fh = fopen($file, 'c+');
    if (!$fh) {
        return;
    }

    if (flock($fh, LOCK_EX)) {
        $size     = filesize($file);
        $existing = $size > 0 ? json_decode(fread($fh, $size), true) : [];
        $existing = is_array($existing) ? $existing : [];

        foreach ($newentries as $original => $val) {
            $existing[md5(trim($original))] = $val;
        }

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($existing, JSON_UNESCAPED_UNICODE));
        fflush($fh);
        flock($fh, LOCK_UN);

        if (function_exists('apcu_store')) {
            apcu_store("langswitch_cache_{$lang}", $existing, 300);
        }
    }

    fclose($fh);
}

/**
 * Call OpenAI for a batch of strings. Returns [original => translated]
 * or null on hard failure (network error, bad HTTP status, unparsable
 * response) so the caller can fail soft instead of erroring the request.
 */
function local_langswitch_call_openai(array $need, string $lang, string $apikey): ?array {
    $langnames = [
        'en' => 'English',
        'hi' => 'Hindi',
        'mr' => 'Marathi',
        'gu' => 'Gujarati',
    ];

    $prompt = "You are a translation engine for a Learning Management System (LMS) UI.\n"
        . "Translate the following UI texts to {$langnames[$lang]}.\n\n"
        . "STRICT RULES:\n"
        . "1. Return ONLY a valid JSON object. No explanation, no markdown, no extra text.\n"
        . "2. Format: {\"original text\": \"translated text\", ...}\n"
        . "3. Keep translations short and natural for UI labels, buttons, headings.\n"
        . "4. Preserve numbers, emojis, special characters exactly as-is.\n"
        . "5. If a text is already in {$langnames[$lang]}, return it unchanged.\n"
        . "6. Do NOT translate proper nouns, brand names, email addresses, URLs.\n"
        . "7. Do NOT translate single letters or numbers.\n\n"
        . "Texts:\n" . json_encode($need, JSON_UNESCAPED_UNICODE);

    $payload = [
        'model'       => 'gpt-4o-mini',
        'messages'    => [
            ['role' => 'system', 'content' => 'You are a UI translation engine. Always respond with valid JSON only. No markdown, no explanation.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.1,
        'max_tokens'  => 2000,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        // Fail fast: a slow/hanging OpenAI call must not tie up a
        // PHP-FPM worker for 30s under load — that's a scalability risk
        // at high concurrency, not just a latency annoyance.
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 8,
    ]);

    $response  = curl_exec($ch);
    $curl_err  = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_err || $http_code !== 200) {
        return null;
    }

    $data    = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? null;

    if (!$content) {
        return null;
    }

    $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
    $content = preg_replace('/\s*```$/', '', $content);

    $translations = json_decode(trim($content), true);

    return is_array($translations) ? $translations : null;
}
