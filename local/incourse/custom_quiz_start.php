<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$cmid = required_param('cmid', PARAM_INT);

// Load course + quiz.
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// PAGE OUTPUT
echo $OUTPUT->header();
echo "<h2>$quiz->name</h2>";
echo "<p>Showing all quiz questions and options directly:</p><hr>";


// -------------------------------------------------------------------
// 1️⃣ FETCH QUIZ SLOTS (each slot links to a question_reference)
// -------------------------------------------------------------------
$slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');

foreach ($slots as $slot) {

    // -------------------------------------------------------------------
    // 2️⃣ GET QUESTION REFERENCE
    // -------------------------------------------------------------------
    $qref = $DB->get_record('question_references', [
        'component' => 'mod_quiz',
        'questionarea' => 'slot',
        'itemid' => $slot->id
    ]);

    if (!$qref) {
        echo "<p style='color:red;'>⚠ Question reference missing for slot {$slot->slot}</p>";
        continue;
    }

    // -------------------------------------------------------------------
    // 3️⃣ GET QUESTION VERSION
    // -------------------------------------------------------------------
    $qversion = $DB->get_record('question_versions', ['id' => $qref->questionid]);

    if (!$qversion) {
        echo "<p style='color:red;'>⚠ Question version missing for slot {$slot->slot}</p>";
        continue;
    }

    // This is the real question id
    $questionid = $qversion->questionid;

    // -------------------------------------------------------------------
    // 4️⃣ LOAD REAL QUESTION FROM `question` TABLE
    // -------------------------------------------------------------------
    $question = $DB->get_record('question', ['id' => $questionid]);

    if (!$question) {
        echo "<p style='color:red;'>⚠ Question not found! ID = $questionid</p>";
        continue;
    }

    // -------------------------------------------------------------------
    // 5️⃣ DISPLAY QUESTION
    // -------------------------------------------------------------------
    echo "<div style='margin-bottom:25px;padding:15px;border:1px solid #ccc;border-radius:10px;'>";

    echo "<h3>Q{$slot->slot}: " .
         format_text($question->questiontext, $question->questiontextformat) .
         "</h3>";

    // -------------------------------------------------------------------
    // 6️⃣ DISPLAY OPTIONS BY QUESTION TYPE
    // -------------------------------------------------------------------

    if ($question->qtype === 'multichoice') {

        $answers = $DB->get_records('question_answers', ['question' => $questionid]);

        echo "<ul>";
        foreach ($answers as $ans) {
            echo "<li>" . format_text($ans->answer, $ans->answerformat) . "</li>";
        }
        echo "</ul>";
    }

    if ($question->qtype === 'truefalse') {
        echo "<ul><li>True</li><li>False</li></ul>";
    }

    if ($question->qtype === 'shortanswer') {
        echo "<input type='text' disabled placeholder='Short answer here...' />";
    }

    echo "</div>";
}

echo $OUTPUT->footer();
