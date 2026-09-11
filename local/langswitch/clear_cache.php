<?php
/**
 * Utility script to clear server-side translation cache.
 * Access: /local/langswitch/clear_cache.php
 * Only accessible by site admins.
 */
require_once('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$cachedir = __DIR__ . '/translations/';
$cleared  = 0;

if (is_dir($cachedir)) {
    foreach (glob($cachedir . '*_cache.json') as $file) {
        unlink($file);
        $cleared++;
    }
}

// Also clear all users' localStorage via JS
?>
<!DOCTYPE html>
<html>
<head>
    <title>LangSwitch Cache Cleared</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f5f6f8; }
        .box { background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #003152; }
        .success { color: green; font-weight: bold; }
        a { color: #003152; }
    </style>
</head>
<body>
<div class="box">
    <h2>🗑️ Cache Cleared</h2>
    <p class="success">✅ <?php echo $cleared; ?> server cache file(s) deleted.</p>
    <p>✅ Browser localStorage cache will be cleared on next page load.</p>
    <p><a href="<?php echo $CFG->wwwroot; ?>">← Back to LMS</a></p>
</div>
<script>
    // Clear all langswitch localStorage entries
    ['en','hi','mr','gu'].forEach(function(lang) {
        localStorage.removeItem('langswitch_' + lang);
    });
    console.log('LangSwitch: localStorage cache cleared');
</script>
</body>
</html>
<?php
exit;
