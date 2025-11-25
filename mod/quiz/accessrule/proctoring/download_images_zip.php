<?php
require_once('../../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$reportid = required_param('reportid', PARAM_INT);

// Use module context instead of course context for better permission checking
$cm = get_coursemodule_from_id('quiz', $quizid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_capability('quizaccess/proctoring:viewreport', $context);

global $DB;
$fs = get_file_storage();

// Get ALL images for this student in this quiz, not just one reportid
$sql = "SELECT id, webcampicture, timemodified FROM {quizaccess_proctoring_logs}
        WHERE userid = :userid AND quizid = :quizid AND courseid = :courseid
        AND webcampicture IS NOT NULL AND webcampicture != ''
        ORDER BY timemodified ASC";
$params = ['userid' => $studentid, 'quizid' => $quizid, 'courseid' => $courseid];

$logs = $DB->get_records_sql($sql, $params);

if (empty($logs)) {
    throw new moodle_exception('noimagesfound', 'quizaccess_proctoring');
}

// Debug: Log what we found
error_log("ZIP Debug: Found " . count($logs) . " image records");

// Create temp ZIP file
$tempzip = tempnam(sys_get_temp_dir(), 'proctorzip_');
$zip = new ZipArchive();

if ($zip->open($tempzip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    throw new moodle_exception('cannotcreatezipfile', 'quizaccess_proctoring');
}

$imagecount = 0;
$user = $DB->get_record('user', ['id' => $studentid], 'firstname, lastname, email');

foreach ($logs as $log) {
    $url = $log->webcampicture;
    error_log("Processing URL: " . $url);

    // Method 1: Try pluginfile.php URL parsing
    if (preg_match('#/pluginfile\.php/(\d+)/([^/]+)/([^/]+)/(\d+)/(.*)$#', $url, $matches)) {
        list(, $contextid, $component, $filearea, $itemid, $filepathname) = $matches;

        $filename = basename($filepathname);
        $filepath = '/' . dirname($filepathname) . '/';
        if ($filepath === '//') $filepath = '/';

        error_log("Trying to get file: contextid=$contextid, component=$component, filearea=$filearea, itemid=$itemid, filepath=$filepath, filename=$filename");

        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        if ($file && !$file->is_directory()) {
            $zipfilename = 'image_' . $log->id . '_' . date('Y-m-d_H-i-s', $log->timemodified) . '_' . $filename;
            $zip->addFromString($zipfilename, $file->get_content());
            $imagecount++;
            error_log("Added file to ZIP: " . $zipfilename);
        } else {
            error_log("File not found in storage: " . $url);
        }
    }
    // Method 2: Try direct file path
    else if (file_exists($url)) {
        $content = file_get_contents($url);
        if ($content !== false) {
            $filename = 'image_' . $log->id . '_' . date('Y-m-d_H-i-s', $log->timemodified) . '.png';
            $zip->addFromString($filename, $content);
            $imagecount++;
            error_log("Added direct file to ZIP: " . $filename);
        }
    }
    // Method 3: Try to find files in proctoring component by searching
    else {
        error_log("Trying alternative file search for: " . $url);
        
        // Search for files in the proctoring component
        $files = $fs->get_area_files($context->id, 'quizaccess_proctoring', 'picture', $log->id, 'timemodified', false);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!$file->is_directory()) {
                    $zipfilename = 'image_' . $log->id . '_' . date('Y-m-d_H-i-s', $log->timemodified) . '_' . $file->get_filename();
                    $zip->addFromString($zipfilename, $file->get_content());
                    $imagecount++;
                    error_log("Added searched file to ZIP: " . $zipfilename);
                    break; // Only add one file per log entry
                }
            }
        }
    }
}

// Add a summary file
$summary = "Proctoring Images Summary\n";
$summary .= "========================\n\n";
$summary .= "Student: " . fullname($user) . "\n";
$summary .= "Email: " . $user->email . "\n";
$summary .= "Quiz ID: " . $quizid . "\n";
$summary .= "Course ID: " . $courseid . "\n";
$summary .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$summary .= "Total Images: " . $imagecount . "\n";
$summary .= "Total Log Records: " . count($logs) . "\n\n";

if ($imagecount == 0) {
    $summary .= "WARNING: No images were found or accessible.\n";
    $summary .= "This could be due to:\n";
    $summary .= "- Images not properly stored in Moodle file system\n";
    $summary .= "- Permission issues\n";
    $summary .= "- Incorrect file paths\n";
}

$zip->addFromString('summary.txt', $summary);

$zip->close();

// Check if ZIP has meaningful content (more than just the summary file)
if ($imagecount == 0) {
    unlink($tempzip);
    error_log("ZIP creation failed: No images added to ZIP");
    throw new moodle_exception('zipempty', 'quizaccess_proctoring');
}

error_log("ZIP created successfully with " . $imagecount . " images");

// Get student name for filename
$zipname = clean_filename('proctoring_images_' . fullname($user) . '_' . date('Y-m-d_H-i-s') . '.zip');

// Send ZIP to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Content-Length: ' . filesize($tempzip));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($tempzip);
unlink($tempzip);
exit;
?>
