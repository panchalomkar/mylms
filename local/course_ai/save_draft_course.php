<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/course_ai/lib.php');
require_login();
require_sesskey();

use PhpOffice\PhpWord\IOFactory;
session_start(); // ✅ Enable session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $USER, $DB;

    $coursename = required_param('coursename', PARAM_TEXT);
    $summary = optional_param('summary', '', PARAM_RAW); // allow raw for rich text

    // Check for uploaded .docx file
    $uploadPrompt = '';
    $uploadMessage = '';
    if (empty($summary) && isset($_FILES['course_doc']) && $_FILES['course_doc']['error'] === UPLOAD_ERR_OK) {
        require_once($CFG->dirroot . '/local/course_ai/vendor/autoload.php'); // PhpWord via Composer

        try {
            $filepath = $_FILES['course_doc']['tmp_name'];
            $phpWord = IOFactory::load($filepath);

            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }

            $uploadPrompt = trim($text);
            $uploadMessage = 'File uploaded and processed successfully.';
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to read Word document: ' . $e->getMessage()]);
            exit;
        }
    }

    // Final summary logic
    $finalSummary = $summary;

    if (empty($finalSummary) && empty($uploadPrompt)) {
        echo json_encode(['success' => false, 'error' => 'No summary or document content provided.']);
        exit;
    }

    try {
        // Create the course
        $course = local_course_ai_create_draft_course($coursename, $finalSummary, $USER->id);
        enrol_try_internal_enrol($course->id, $USER->id, 3);
        // --- After the course is created ---

// Ensure course was created
if (!empty($course->id)) {
    // Enable completion tracking for this course (per-course flag)
    $DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
//  $DB->set_field('course', 'visible', 1, ['id' => $course->id]);
    // Optional: set a default completion expected date for the course (not required)
    // $DB->set_field('course', 'completionexpected', time() + (7 * DAYSECS), ['id' => $course->id]);
}

        // Insert custom field
        $DB->execute("
    INSERT INTO {customfield_data} (
        fieldid, instanceid, intvalue, value, valueformat, timecreated, timemodified, contextid
    )
    SELECT
        f.id,
        :courseid1,
        1,
        '1',
        0,
        :time1,
        :time2,
        ctx.id
    FROM {customfield_field} f
    JOIN {context} ctx ON ctx.instanceid = :courseid2 AND ctx.contextlevel = 50
    WHERE f.shortname = 'is_ai_course'
      AND NOT EXISTS (
        SELECT 1 FROM {customfield_data} d
        WHERE d.instanceid = :courseid3 AND d.fieldid = f.id
    )
", [
            'courseid1' => $course->id,
            'courseid2' => $course->id,
            'courseid3' => $course->id,
            'time1' => time(),
            'time2' => time(),
        ]);

        if (!empty($uploadPrompt)) {
            $_SESSION['uploadprompt'] = $uploadPrompt;

            // Prepare the redirect URL for the response
            $redirecturl = new moodle_url('/local/course_ai/generate_course.php', [
                'courseid' => $course->id,
            ]);

            // Send the success response with the redirect URL
            echo json_encode([
                'success' => true,
                'courseid' => $course->id,
                'redirect' => $redirecturl->out(false)
            ]);
            exit; // Ensure exit after sending the response
        }

        // Default success response when no prompt
        echo json_encode([
            'success' => true,
            'courseid' => $course->id,
        ]);
    } catch (Exception $e) {
        // Error handling: always return a JSON error response
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false]);
exit;
