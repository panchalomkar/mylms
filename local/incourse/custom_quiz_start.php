<?php
require_once('../../config.php');
require_login();

global $DB, $CFG, $USER;

// Correct quiz libraries
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

// ============= PARAMS =============
$cmid       = required_param('cmid', PARAM_INT);
$attemptid  = required_param('attempt', PARAM_INT);
// $start      = required_param('start', PARAM_RAW);

// $starturl = urldecode($start);

// ============= LOAD CM + QUIZ + COURSE =============
$cm     = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz   = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

// ============= LOAD ATTEMPT FROM DB =============
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$userid  = $attempt->userid;

// ============= CREATE MOODLE ATTEMPT OBJECT =============
$attemptobj = \mod_quiz\quiz_attempt::create($attemptid, $userid);

// ---------- AJAX handlers ----------
if (optional_param('ajax', 0, PARAM_INT) == 1) {
    $action = optional_param('action', '', PARAM_ALPHANUMEXT);

    // get a list of all slots in order and current slot index
    if ($action === 'init') {
        $allslots = $attemptobj->get_slots();
        $currentpage = $attemptobj->get_currentpage();
        $slots = array_values($allslots); // reindex
        $slot = reset($attemptobj->get_slots($currentpage)); // first slot on current page

        $data = [
            'slots' => $slots,
            'current_slot' => $slot,
            'total_questions' => count($slots),
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    if ($action === 'get_question') {
        $slot = required_param('slot', PARAM_INT);

        // Recreate attempt object to ensure up-to-date info.
        $attemptobj = \mod_quiz\quiz_attempt::create($attemptid, $userid);

        // All slot list
        $allslots = array_values($attemptobj->get_slots());
        $idx = array_search($slot, $allslots);

        if ($idx === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid slot']);
            exit;
        }

        // get question attempt and question
        $qa = $attemptobj->get_question_attempt($slot);
        $question = $qa->get_question();

        // question text and answers
        $questiontext = format_text($question->questiontext, FORMAT_HTML);
        $answers = [];
        foreach ($question->answers as $ans) {
            $answers[] = [
                'id' => $ans->id,
                'html' => format_text($ans->answer, FORMAT_HTML)
            ];
        }

        // numbers
        $questionnumber = $attemptobj->get_question_number($slot);
        $totalquestions = count($allslots);

        // next/prev slot calculation
        $prevSlot = ($idx > 0) ? $allslots[$idx - 1] : null;
        $nextSlot = ($idx < $totalquestions - 1) ? $allslots[$idx + 1] : null;

        // remaining seconds
        $remaining_seconds = $attemptobj->get_time_left_display(time()) ?? 0;

        $response = [
            'slot' => $slot,
            'questiontext' => $questiontext,
            'answers' => $answers,
            'questionnumber' => $questionnumber,
            'totalquestions' => $totalquestions,
            'prevslot' => $prevSlot,
            'nextslot' => $nextSlot,
            'remaining_seconds' => $remaining_seconds,
            // include whether answer exists for this slot (to mark checked)
            'last_answer' => ($qa->get_last_qt_data()['answer'] ?? null),
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // unknown action
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ============= PAGE SETUP (IMPORTANT PART) =============
$PAGE->set_url('/local/incourse/custom_quiz_start.php', [
    'cmid' => $cmid,
    'attempt' => $attemptid
]);
$PAGE->set_cm($cm, $course);          // REQUIRED FIX
$PAGE->set_context(context_module::instance($cm->id));
$PAGE->set_title("Quiz Attempt");
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// For initial render — render the current slot server-side for first paint:
$currentpage  = $attemptobj->get_currentpage();
$slotsinpage  = $attemptobj->get_slots($currentpage);
$slot         = reset($slotsinpage);
$qa           = $attemptobj->get_question_attempt($slot);
$question     = $qa->get_question();
$questiontext = format_text($question->questiontext, FORMAT_HTML);
$answers      = $question->answers;
$questionnumber = $attemptobj->get_question_number($slot);
$totalquestions = count($attemptobj->get_slots());
$remaining_seconds = $attemptobj->get_time_left_display(time()) ?? 0;

// processattempt URL (Moodle handles attempt processing)
$process_url = $attemptobj->processattempt_url()->out(false);

// Remove summary/review links by NOT rendering them.
?>

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>

<div id="quizApp" class="w-full px-4 md:px-10 py-6">

    <!-- TOP BAR -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <a href="<?php echo $starturl; ?>">
                <span class="material-symbols-outlined cursor-pointer">arrow_back</span>
            </a>
            <span class="text-xl font-semibold">Quiz</span>
            <span class="text-gray-500 ml-2">
                Question <span id="qNumber"><?php echo $questionnumber; ?></span> of <span id="qTotal"><?php echo $totalquestions; ?></span>
            </span>
        </div>

        <!-- removed Summary / Review links -->
        <div></div>
    </div>

    <!-- PROGRESS BAR -->
    <div class="h-2 w-full bg-gray-200 rounded-full mb-6">
        <div id="progressBar" class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($questionnumber / max(1,$totalquestions)) * 100; ?>%;"></div>
    </div>

    <!-- MAIN GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- QUESTION BOX -->
        <div class="md:col-span-2 bg-white shadow p-6 rounded-xl">

            <h2 class="text-2xl font-bold mb-4">Question <span id="questionIndex"><?php echo $questionnumber; ?></span></h2>
            <p class="text-gray-500 mb-6">Marked out of 1.00</p>

            <h3 id="questionText" class="text-lg font-semibold mb-4">
                <?php echo $questiontext; ?>
            </h3>

            <!-- OPTIONS -->
            <form id="answerForm">

                <input type="hidden" name="sesskey" id="sesskey" value="<?php echo sesskey(); ?>">
                <input type="hidden" name="slots" id="slotsField" value="<?php echo $slot; ?>">
                <!-- nextpage will be set by JS when submitting -->
                <input type="hidden" name="finishattempt" id="finishattempt" value="0">

                <div id="optionsContainer" class="space-y-4">
                    <?php 
                    $inputname = "q{$slot}";
                    foreach ($answers as $ans): ?>
                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 hover:bg-gray-50 cursor-pointer">
                            <input 
                                type="radio" 
                                name="<?php echo $inputname; ?>" 
                                value="<?php echo $ans->id; ?>" 
                                class="h-5 w-5"
                                <?php echo ($qa->get_last_qt_data()['answer'] ?? '') == $ans->id ? 'checked' : ''; ?>
                            />
                            <span class="text-lg"><?php echo format_text($ans->answer, FORMAT_HTML); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between mt-8">
                    <button type="button" id="prevBtn" class="px-4 py-2 border rounded-lg invisible">← Previous</button>
                    <button type="button" id="nextBtn" class="px-6 py-2 bg-blue-700 text-white rounded-lg">
                        Next →
                    </button>
                </div>
            </form>

        </div>

        <!-- RIGHT SIDEBAR -->
        <div class="space-y-6">

            <!-- TIMER -->
            <div class="bg-white shadow rounded-xl p-6 text-center">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2 justify-center">
                    <span class="material-symbols-outlined text-blue-600">schedule</span>
                    Time Remaining
                </h3>

                <div class="relative w-40 h-40 mx-auto">
                    <svg class="w-full h-full" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" stroke="#ffe4cd" stroke-width="10" fill="none"/>
                        <circle id="timerCircle" cx="50" cy="50" r="45"
                                stroke="#ec9707" stroke-width="10" fill="none"
                                stroke-dasharray="283" stroke-dashoffset="40"
                                stroke-linecap="round"/>
                    </svg>
                    <div id="timerDisplay" class="absolute inset-0 flex flex-col items-center justify-center text-3xl font-bold text-[#ec9707]">
                        <?php echo gmdate("i:s", $remaining_seconds); ?>
                        <div id="timerPct" class="text-sm text-gray-500 mt-1">
                            <?php
                            echo ($quiz->timelimit > 0)
                                ? round(($remaining_seconds / $quiz->timelimit) * 100)
                                : 100;
                            ?>% left
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUIZ NAVIGATION -->
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4">Quiz Navigation</h3>

                <div id="navGrid" class="grid grid-cols-4 gap-3 mb-4">
                    <?php foreach ($attemptobj->get_slots() as $s): ?>
                        <div data-slot="<?php echo $s; ?>" class="navCell py-2 rounded-lg border text-center cursor-pointer hover:bg-gray-100">
                            <?php echo $attemptobj->get_question_number($s); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-2 text-sm">
                    <p><span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span> Answered: <span id="answeredCount">0</span></p>
                    <p><span class="inline-block w-3 h-3 bg-gray-400 rounded-full"></span> Unanswered: <span id="unansweredCount">0</span></p>
                    <p><span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span> Flagged: <span id="flaggedCount">0</span></p>
                </div>

                <button id="finishBtn" class="mt-5 w-full bg-blue-700 hover:bg-blue-800 text-white py-3 rounded-lg">
                    Finish Attempt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const attemptId = <?php echo (int)$attemptid; ?>;
    const cmid = <?php echo (int)$cmid; ?>;
    const processUrl = "<?php echo $process_url; ?>";
    const sesskey = document.getElementById('sesskey').value;
    let currentSlot = <?php echo (int)$slot; ?>;
    let slotsList = []; // ordered list of slots
    let totalQuestions = <?php echo (int)$totalquestions; ?>;

    // DOM refs
    const qNumberEl = document.getElementById('qNumber');
    const qTotalEl = document.getElementById('qTotal');
    const qTextEl = document.getElementById('questionText');
    const optionsContainer = document.getElementById('optionsContainer');
    const progressBar = document.getElementById('progressBar');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const finishBtn = document.getElementById('finishBtn');
    const questionIndex = document.getElementById('questionIndex');
    const timerDisplay = document.getElementById('timerDisplay');
    const timerPct = document.getElementById('timerPct');
    const navGrid = document.getElementById('navGrid');

    // init: get slots list
    async function init() {
        const res = await fetch(window.location.pathname + '?ajax=1&action=init&attempt=' + attemptId + '&cmid=' + cmid, { credentials: 'same-origin' });
        const data = await res.json();
        slotsList = data.slots;
        totalQuestions = data.total_questions;
        qTotalEl.textContent = totalQuestions;
        // mark nav cells clickable
        document.querySelectorAll('.navCell').forEach(cell => {
            cell.addEventListener('click', () => {
                const slot = parseInt(cell.getAttribute('data-slot'));
                loadQuestion(slot);
            });
        });

        // load current slot (we already have initial content, but sync with server for last answer)
        loadQuestion(currentSlot, {replaceHistory:false});
        attachListeners();
    }

    function attachListeners(){
        prevBtn.addEventListener('click', () => {
            if (prevSlotGlobal !== null) {
                submitAndLoad(prevSlotGlobal, false);
            }
        });
        nextBtn.addEventListener('click', () => {
            if (nextSlotGlobal !== null) {
                submitAndLoad(nextSlotGlobal, false);
            }
        });
        finishBtn.addEventListener('click', () => {
            // submit current answers and finish attempt
            submitAndLoad(null, true);
        });
    }

    // globals to store prev/next for current question
    let prevSlotGlobal = null;
    let nextSlotGlobal = null;

    async function loadQuestion(slot, opts = {replaceHistory:true}) {
        // fetch question JSON
        const res = await fetch(window.location.pathname + '?ajax=1&action=get_question&attempt=' + attemptId + '&slot=' + slot, { credentials: 'same-origin' });
        if (!res.ok) {
            console.error('Failed to fetch question', await res.text());
            return;
        }
        const data = await res.json();

        currentSlot = data.slot;
        prevSlotGlobal = data.prevslot;
        nextSlotGlobal = data.nextslot;

        // update question text
        qTextEl.innerHTML = data.questiontext;
        // update numbers
        qNumberEl.textContent = data.questionnumber;
        questionIndex.textContent = data.questionnumber;
        qTotalEl.textContent = data.totalquestions;
        // progress
        const pct = (data.questionnumber / data.totalquestions) * 100;
        progressBar.style.width = pct + '%';

        // render options
        optionsContainer.innerHTML = '';
        const inputName = 'q' + data.slot;
        data.answers.forEach(ans => {
            const checked = (data.last_answer && parseInt(data.last_answer) === parseInt(ans.id)) ? 'checked' : '';
            const label = document.createElement('label');
            label.className = 'flex items-center gap-3 border rounded-lg px-4 py-3 hover:bg-gray-50 cursor-pointer';
            label.innerHTML = `
                <input type="radio" name="${inputName}" value="${ans.id}" class="h-5 w-5" ${checked} />
                <span class="text-lg">${ans.html}</span>
            `;
            optionsContainer.appendChild(label);
        });

        // set slots hidden field
        document.getElementById('slotsField').value = data.slot;

        // update timer
        updateTimer(data.remaining_seconds);

        // update Prev/Next button visibility
        if (prevSlotGlobal === null) {
            prevBtn.classList.add('invisible');
        } else {
            prevBtn.classList.remove('invisible');
        }
        if (nextSlotGlobal === null) {
            nextBtn.textContent = 'Finish →';
            nextBtn.onclick = () => submitAndLoad(null, true);
        } else {
            nextBtn.textContent = 'Next →';
            nextBtn.onclick = () => submitAndLoad(nextSlotGlobal, false);
        }

        // update nav cell styles
        document.querySelectorAll('.navCell').forEach(cell => {
            cell.classList.remove('bg-blue-700','text-white');
            if (parseInt(cell.getAttribute('data-slot')) === data.slot) {
                cell.classList.add('bg-blue-700','text-white');
            }
        });
    }

    // submit answer for current slot then load targetSlot (or finish attempt)
    async function submitAndLoad(targetSlot = null, finish = false) {
        // gather answer for current slot
        const slot = currentSlot;
        const inputName = 'q' + slot;
        const checked = document.querySelector('input[name="'+inputName+'"]:checked');
        // build form data as Moodle expects
        const form = new FormData();
        form.append('sesskey', sesskey);
        form.append('slots', slot);
        if (checked) {
            form.append(inputName, checked.value);
        }
        // compute nextpage value as an integer page index Moodle expects.
        // We don't know the internal page numbering, but Moodle uses pages in the attempt object.
        // Workaround: set nextpage to 0 (Moodle will still accept and save answers) — then we'll request desired slot content via AJAX.
        // But to be safer, if targetSlot is provided, set nextpage=targetIndexPage (not available here), so keep nextpage=0.
        form.append('nextpage', 0);
        form.append('finishattempt', finish ? 1 : 0);

        // POST to Moodle processattempt URL
        try {
            const resp = await fetch(processUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: form,
                redirect: 'follow'
            });

            // even if the server redirects to another page, we will ignore returned HTML and instead fetch the next question data.
            if (finish) {
                // If finish true, redirect user to attempt summary/finish page
                // We will attempt to redirect to the attempt summary that Moodle usually uses.
                // Construct summary URL from attemptobj (server-side generated) by reloading the page to attempt summary.
                window.location.href = "<?php echo $attemptobj->summary_url()->out(false); ?>";
                return;
            } else {
                // load the target slot (if provided) or nextSlotGlobal
                let nextLoadSlot = targetSlot !== null ? targetSlot : nextSlotGlobal;
                if (nextLoadSlot === null) {
                    // no next — show finish page
                    window.location.href = "<?php echo $attemptobj->summary_url()->out(false); ?>";
                    return;
                }
                await loadQuestion(nextLoadSlot);
            }
        } catch (err) {
            console.error('Error submitting answer', err);
        }
    }

    // update timer display
    function updateTimer(seconds) {
        // set initial
        let remaining = parseInt(seconds || 0);
        function tick() {
            if (remaining < 0) remaining = 0;
            const mmss = new Date(remaining * 1000).toISOString().substr(14,5);
            timerDisplay.firstChild && timerDisplay.firstChild.remove(); // clear existing text nodes (safe)
            // update inner text (we will replace innerHTML)
            timerDisplay.innerHTML = mmss + '<div id="timerPct" class="text-sm text-gray-500 mt-1">' +
                (<?php echo ($quiz->timelimit > 0) ? 1 : 0; ?> ? Math.round((remaining / <?php echo max(1,(int)$quiz->timelimit); ?>) * 100) + '% left' : '100% left') +
                '</div>';
            remaining--;
            if (remaining >= 0) {
                setTimeout(tick, 1000);
            } else {
                // time's up — auto submit finish
                submitAndLoad(null, true);
            }
        }
        // start ticking
        tick();
    }

    // start
    init();
})();
</script>

<?php
echo $OUTPUT->footer();
