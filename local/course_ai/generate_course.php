<?php
require_once('../../config.php');

use core\context\course as course_context;

header('Content-Type: application/json');
ob_start();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');
require_once($CFG->dirroot . '/mod/videofile/lib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/local/course_ai/lib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/questionlib.php');

global $DB, $USER, $CFG;

/* ============================================================
   VALIDATION
   ============================================================ */

$courseid = required_param('courseid', PARAM_INT);
$prompt   = optional_param('prompt', '', PARAM_TEXT);

$course = get_course($courseid);
$context = course_context::instance($courseid, MUST_EXIST);

require_login($course);
require_capability('moodle/question:managecategory', $context);

/* ============================================================
   ENSURE DEFAULT QUESTION CATEGORY (NO NULL PARENT)
   ============================================================ */

$questioncategory = $DB->get_record('question_categories', [
    'contextid' => $context->id,
    'parent'    => 0
]);

if (!$questioncategory) {
    $category = new stdClass();
    $category->name      = 'Default for ' . $context->get_context_name();
    $category->info      = 'The default category for questions shared in context \'' .
                           $context->get_context_name() . '\'.';
    $category->contextid = $context->id;
    $category->parent    = 0;
    $category->sortorder = 999;
    $category->stamp     = make_unique_id_code();

    $category->id = $DB->insert_record('question_categories', $category);
    $questioncategory = $category;
}

$categoryid = $questioncategory->id;

/* ============================================================
   SESSION PROMPT
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prompt1 = $_SESSION['uploadprompt'] ?? '';
unset($_SESSION['uploadprompt']);

 $api_key = $CFG->openaiapikeyimage;
 
$coursename = $course->fullname;

/* ============================================================
   COURSE GENERATION
   ============================================================ */

if (empty($prompt) && !empty($prompt1)) {
    $content = generate_course_modules_from_prompt($prompt1, $api_key);
    generate_course_summary($coursename, $courseid, $api_key);
} else {
    $content = generate_course_modules_from_prompt($prompt, $api_key);
}

generate_course_image($coursename, $courseid, $api_key);

if (empty($content)) {
    echo json_encode(['success' => false]);
    exit;
}

$lines = explode("\n", trim($content));
$youtube_api_key = 'AIzaSyDbNZjjZHw285wITRZr1sEfQTOCtEY-zis';

/* ============================================================
   LOOP THROUGH GENERATED MODULES
   ============================================================ */

foreach ($lines as $line) {

    if (strpos($line, '|') === false) {
        continue;
    }

    $parts = array_map('trim', explode('|', $line));
    list($title, $summary) = array_slice($parts, 0, 2);

    /* ================= YOUTUBE ================= */

    $real_videourl = search_youtube_video($title, $youtube_api_key);
    $videourl = $real_videourl ?: 'https://www.youtube.com/watch/SPotKm1epaA?si=u2SdDy5z5k9AcYQB';

    /* ================= OPENAI SUMMARY ================= */

    $content_prompt = "'{$title}'";

    $content_data = [
        "model" => "gpt-4",
        "messages" => [
            [
                "role" => "system",
                "content" => "You will receive a text input from the user. Your task is to generate text based on their request. Return plain text only."
            ],
            ["role" => "user", "content" => $content_prompt]
        ],
        "temperature" => 0.7
    ];

    $ch2 = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ],
        CURLOPT_POSTFIELDS => json_encode($content_data)
    ]);

    $content_response = curl_exec($ch2);
    curl_close($ch2);

    $sectioncontent = '';
    if ($content_response) {
        $content_result = json_decode($content_response, true);
        $sectioncontent = $content_result['choices'][0]['message']['content'] ?? '';
    }

    /* ================= CREATE SECTION ================= */

    $section = course_create_section($courseid, true);
    if (!$section) {
        continue;
    }

    $sectionupdate = new stdClass();
    $sectionupdate->id = $section->id;
    $sectionupdate->course = $courseid;
    $sectionupdate->name = $title;
    $sectionupdate->summary = "<p>{$sectioncontent}</p>";
    $sectionupdate->summaryformat = FORMAT_HTML;

    $DB->update_record('course_sections', $sectionupdate);

    /* ================= QUIZ MODULE ================= */
/* ================= FILE MODULE ================= */

create_file_module(
    $courseid,
    $section->section,
    $title,
    $sectioncontent
);

 require_once($CFG->dirroot . '/course/modlib.php');

$quizmodule = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);

$quiz = new stdClass();
$quiz->modulename = 'quiz';
$quiz->module = $quizmodule->id;
$quiz->course = $courseid;
$quiz->section = $section->section;
$quiz->name = $title;
$quiz->intro = $title;
$quiz->introformat = FORMAT_HTML;

$quiz->timeopen = time();
$quiz->timeclose = time() + (7 * DAYSECS);
$quiz->timelimit = 0;
$quiz->grade = 12;
$quiz->gradepass = 6;
$quiz->attempts = 1;
$quiz->questiondecimalpoints = 2;
$quiz->quizpassword = '12345';
$quiz->password = '12345';
$quiz->preferredbehaviour = 'deferredfeedback';
$quiz->visible = 1;

$quiz->reviewattempt = 65536;
$quiz->reviewcorrectness = 1;
$quiz->reviewmarks = 1;

$quiz = add_moduleinfo($quiz, $course);

$cmid = $quiz->coursemodule;
$quizid = $quiz->instance;


    if (!$quizid) {
        continue;
    }

    $DB->set_field('course_modules', 'instance', $quizid, ['id' => $cmid]);
    course_add_cm_to_section($courseid, $cmid, $section->section);
    set_coursemodule_visible($cmid, 1);

    /* ================= COMPLETION ================= */

    $DB->set_field('course_modules', 'completion', 2, ['id' => $cmid]);
    $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $cmid]);
    $DB->set_field('course_modules', 'completionpassgrade', 1, ['id' => $cmid]);
    $DB->set_field('course_modules', 'completionexpected', time(), ['id' => $cmid]);

    /* ================= MCQ GENERATION ================= */

    $questiongeneratorprompt = "
Generate 4 multiple choice questions for the module: '$title'.
Each question must be 2 lines exactly as described previously.
";

    $mcq_data = [
        "model" => "gpt-4-turbo",
        "messages" => [["role" => "user", "content" => $questiongeneratorprompt]],
        "temperature" => 0.7
    ];

    $ch3 = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ],
        CURLOPT_POSTFIELDS => json_encode($mcq_data)
    ]);

    $mcq_response = curl_exec($ch3);
    curl_close($ch3);

    if ($mcq_response) {
        $mcq_result = json_decode($mcq_response, true);
        $mcq_text = $mcq_result['choices'][0]['message']['content'] ?? '';

        if ($mcq_text) {

            $rawLines = array_values(array_filter(explode("\n", trim($mcq_text))));
            $structuredQuestions = [];
            $index = 1;

            for ($i = 0; $i < count($rawLines) - 1; $i += 2) {

                preg_match('/^\d+\.\s*(.+)/', trim($rawLines[$i]), $questionMatch);
                preg_match('/A[).]\s*(.*?)\s*\|\s*B[).]\s*(.*?)\s*\|\s*C[).]\s*(.*?)\s*\|\s*D[).]\s*(.*?)\s*\|\s*Correct\s*Option:\s*([ABCD])/i',
                    trim($rawLines[$i+1]), $optionMatch);

                if (!empty($questionMatch[1]) && count($optionMatch) === 6) {

                    $options = [
                        trim($optionMatch[1]),
                        trim($optionMatch[2]),
                        trim($optionMatch[3]),
                        trim($optionMatch[4])
                    ];

                    $correctIndex = ord(strtoupper($optionMatch[5])) - ord('A');

                    $choices = [];
                    foreach ($options as $j => $optText) {
                        $choices[] = [
                            'text' => $optText,
                            'correct' => ($j === $correctIndex),
                            'feedback' => ''
                        ];
                    }

                    $structuredQuestions[] = [
                        'name' => 'Q' . $index,
                        'text' => $questionMatch[1],
                        'type' => 'multichoice',
                        'mark' => 3,
                        'feedback' => '',
                        'choices' => $choices
                    ];

                    $index++;
                }
            }

            if (!empty($structuredQuestions)) {
                 add_questions_to_quiz($courseid, $cmid, $structuredQuestions);
            }
        }
    }

    /* ================= VIDEO + ASSIGNMENT ================= */

    create_video_module($courseid, $section->section, $videourl, $title);
    create_assignment_module($courseid, $section->section, $title);
}

/* ============================================================
   RESPONSE
   ============================================================ */

echo json_encode([
    'success' => true,
    'courseid' => $courseid,
    'redirecturl' => "course_template.php?courseid={$courseid}"
]);

exit;
