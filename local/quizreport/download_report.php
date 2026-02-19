<?php
require_once(dirname(__FILE__) . "/../../config.php");
global $CFG, $DB;

$quizid = required_param('quizid', PARAM_INT);
$quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);

// Fetch questions for the quiz using question_references
$questions = $DB->get_records_sql(
    "SELECT q.id, q.questiontext
     FROM {quiz_slots} qs
     JOIN {question_references} qr ON qr.itemid = qs.id
         AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
     JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
     JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
         AND (qr.version IS NULL OR qv.version = qr.version)
     JOIN {question} q ON q.id = qv.questionid
     WHERE qs.quizid = ?",
    [$quizid]
);

if (empty($questions)) {
    die("No questions found for this quiz. Cannot generate report.");
}

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=quiz_report_" . $quizid . ".csv");

$output = fopen("php://output", "w");
fputcsv($output, ["Quiz Report for " . $quiz->name]);
fputcsv($output, ["Question", "Option", "Response Count"]);

$has_data = false;
foreach ($questions as $question) {
    $responses = $DB->get_records_sql(
        "SELECT qa.responsesummary as answer, COUNT(*) as count
         FROM {question_attempts} qa
         JOIN {question_usages} qu ON qu.id = qa.questionusageid
         JOIN {quiz_attempts} qa2 ON qa2.uniqueid = qu.id
         WHERE qa.questionid = ? AND qa2.quiz = ?
         GROUP BY qa.responsesummary",
        [$question->id, $quizid]
    );

    foreach ($responses as $response) {
        $has_data = true;
        $answer = $response->answer ? $response->answer : 'No response';
        fputcsv($output, [$question->questiontext, $answer, $response->count]);
    }
}

if (!$has_data) {
    fputcsv($output, ["No responses found for this quiz."]);
}

fclose($output);
exit;
?>