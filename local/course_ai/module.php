<?php
require_once('../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/course_ai/generate_course.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('AI-Generated Course Module');
$PAGE->set_pagelayout('standard');

// Include Bootstrap
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'));

echo $OUTPUT->header();
?>

<div class="container mt-4">

    <form method="get" class="mb-4">
        <input type="text" name="prompt" placeholder="Enter your module topic" class="form-control mb-2" required>
        <button type="submit" class="btn btn-primary">Generate Module</button>
    </form>

    <?php
    $prompt = optional_param('prompt', null, PARAM_TEXT);

    if ($prompt) {
        // 🔐 Store this securely in production
        $api_key = $CFG->openaiapikey; // 🔒 Regenerate it now!
    
        $data = [
            "model" => "gpt-3.5-turbo",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.7
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo '<div class="alert alert-danger">cURL Error: ' . curl_error($ch) . '</div>';
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result = json_decode($response, true);

            if ($http_code !== 200) {
                echo '<div class="alert alert-warning">API Error: ' . htmlspecialchars($response) . '</div>';
            } elseif (isset($result['choices'][0]['message']['content'])) {
                $generatedContent = $result['choices'][0]['message']['content'];
                echo "<div class='card shadow-sm p-4 bg-light'>";
                echo "<pre style='white-space: pre-wrap; word-wrap: break-word; font-family: inherit;'>" . htmlspecialchars($generatedContent) . "</pre>";
                echo "</div>";
            } else {
                echo '<div class="alert alert-info">No content was generated. Response: <pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre></div>';
            }
        }

        curl_close($ch);
    }
    ?>
</div>

<?php echo $OUTPUT->footer(); ?>