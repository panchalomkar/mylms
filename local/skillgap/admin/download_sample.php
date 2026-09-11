<?php
/**
 * Sample CSV download handler.
 * Usage: /local/skillgap/admin/download_sample.php?type=designations|questions|subcomps
 */
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/skillgap/lib.php');
require_login();
require_capability('local/skillgap:admin', context_system::instance());

$type = required_param('type', PARAM_ALPHANUMEXT);

$files = [
    'designations' => [
        'filename' => 'sample_designations.csv',
        'content'  => "Designation,Parent Designation\n" .
                      "CTO,\n" .
                      "Engineering Director,CTO\n" .
                      "Lead Developer,Engineering Director\n" .
                      "Senior Developer,Lead Developer\n" .
                      "Developer,Senior Developer\n" .
                      "Junior Developer,Developer\n",
    ],
    'questions' => [
        'filename' => 'sample_questions.csv',
        'content'  => "Question,Option A (Correct),Option B,Option C,Option D\n" .
                      "What is the primary goal of Agile methodology?,Deliver working software iteratively,Write comprehensive documentation,Follow a strict plan,Avoid all changes\n" .
                      "Which practice improves code quality?,Code review,Skipping tests,Working alone,Ignoring feedback\n",
    ],
    'subcomps' => [
        'filename' => 'sample_sub_competencies.csv',
        'content'  => "Sub-Competency,Sub-Sub-Competency\n" .
                      "Programming Languages,Java\n" .
                      "Programming Languages,Python\n" .
                      "System Design,\n" .
                      "Code Review,\n",
    ],
];

if (!isset($files[$type])) {
    throw new moodle_exception('invalidparameter', 'error');
}

$file = $files[$type];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo $file['content'];
exit;
