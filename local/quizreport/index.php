<?php
// Moodle Local Plugin for Quiz Report
require_once(dirname(__FILE__) . "/../../config.php"); // Include Moodle's config.php

// Ensure Moodle's internal environment is defined
defined('MOODLE_INTERNAL') || die();

// Include required Moodle libraries
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

// Declare global variables
global $CFG, $DB, $OUTPUT, $PAGE;
?>
<link href="style.css" rel="stylesheet">
<style>
    .popuser.active button {
        background: #003152 !important;
        color: #fff;
    }
</style>
<?php
// Enable debugging to display errors (remove in production)
// $CFG->debug = (E_ALL | E_STRICT);
// $CFG->debugdisplay = 1;

// Display form to select course and quiz
function local_quizreport_extend_navigation()
{
    global $PAGE;
    $PAGE->navigation->add(
        get_string('quizreport', 'local_quizreport'),
        new moodle_url('/local/quizreport/index.php'),
        navigation_node::TYPE_SETTING
    );
}

// Set up the page context
$PAGE = new moodle_page();
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/quizreport/index.php');
$PAGE->set_title(get_string('quizreport', 'local_quizreport'));

// Main page to display the report
if (!isset($_GET['courseid']) || !isset($_GET['quizid'])) {
    echo $OUTPUT->header();

    echo '<h2 class="mb-4" style="color:#003152;">
        <i class="fa fa-pie-chart mt-2" style="color:#003152;"></i> ' . get_string('quizreport', 'local_quizreport') . '
      </h2>';

    echo '<div class="card mb-4 shadow-sm" style="border-left: 5px solid #ec9707;">';
    echo '<div class="card-body d-flex justify-content-center">';
    echo '<form method="GET" action="">';

    $courses = get_courses();
    if (empty($courses)) {
        echo '<p>No courses available.</p>';
        echo $OUTPUT->footer();
        exit;
    }

    echo '<div class="form-row align-items-end">';

    // Course dropdown
    echo '<div class="form-group col-md-4">';
    echo '<label style="color:#003152;" style="color:#003152;"><i class="fa fa-graduation-cap mr-1"></i>' . get_string('selectcourse', 'local_quizreport') . '</label>';
    echo '<select class="form-control" name="courseid" required>';
    foreach ($courses as $course) {
        echo '<option value="' . $course->id . '">' . htmlspecialchars($course->fullname) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Quiz dropdown
    echo '<div class="form-group col-md-4">';
    echo '<label style="color:#003152;" ><i class="fa fa-list-alt mr-1" style="color:#003152;"></i>' . get_string('selectquiz', 'local_quizreport') . '</label>';
    echo '<select class="form-control" name="quizid" id="quizid" required>';
    echo '<option value="">Select a quiz</option>';
    echo '</select>';
    echo '</div>';

    // Submit button
    echo '<div class="form-group col-md-4">';
    echo '<label style="visibility:hidden;">Generate</label>'; // Placeholder
    echo '<button type="submit" class="btn" style="background-color:#003152; color:white; position: relative;
    top: 8px;">
            <i class="fa fa-bar-chart mr-1"></i> Generate Report
          </button>';
    echo '</div>';

    echo '</div>'; // form-row
    echo '</form>';
    echo '</div>'; // card-body
    echo '</div>'; // card
    ?>

    <script>
        document.querySelector('select[name="courseid"]').addEventListener('change', function () {
            let courseid = this.value;
            if (courseid) {
                fetch('get_quizzes.php?courseid=' + courseid)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        let quizSelect = document.getElementById('quizid');
                        quizSelect.innerHTML = '<option value="">Select a quiz</option>';
                        if (data.length === 0) {
                            quizSelect.innerHTML += '<option value="">No quizzes available</option>';
                        } else {
                            data.forEach(quiz => {
                                let option = document.createElement('option');
                                option.value = quiz.id;
                                option.text = quiz.name;
                                quizSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching quizzes:', error);
                        let quizSelect = document.getElementById('quizid');
                        quizSelect.innerHTML = '<option value="">Error loading quizzes</option>';
                    });
            }
        });
    </script>
    <?php
    echo $OUTPUT->footer();
    exit;
}

// Fetch quiz data
$courseid = required_param('courseid', PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);

try {
    $course = get_course($courseid);
    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_login($course);

    // Fetch latest finished attempt per user for this quiz
    $latest_attempts = $DB->get_records_sql("SELECT qa.*
    FROM {quiz_attempts} qa
    JOIN (
        SELECT userid, MAX(id) AS latestid
        FROM {quiz_attempts}
        WHERE quiz = ? AND state = 'finished'
        GROUP BY userid
    ) latest ON qa.id = latest.latestid", [$quizid]);

    $total_attempts = count($latest_attempts);
    $total_responses = 0;
    $total_time = 0;

    foreach ($latest_attempts as $attempt) {
        $total_responses++;
        if ($attempt->timefinish && $attempt->timestart) {
            $total_time += $attempt->timefinish - $attempt->timestart;
        }
    }

    $average_time = $total_responses ? gmdate('i:s', $total_time / $total_responses) : '00:00';

    $questions = $DB->get_records_sql("SELECT q.id, q.questiontext
        FROM {quiz_slots} qs
        JOIN {question_references} qr ON qr.itemid = qs.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
        JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
        JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id AND (qr.version IS NULL OR qv.version = qr.version)
        JOIN {question} q ON q.id = qv.questionid
        WHERE qs.quizid = ?", [$quizid]);

    $latest_attempt_ids = $DB->get_records_sql_menu("SELECT userid, MAX(id) AS latestid
        FROM {quiz_attempts}
        WHERE quiz = ? AND state = 'finished'
        GROUP BY userid", [$quizid]);

    $latest_attempt_ids_list = implode(',', array_map('intval', array_values($latest_attempt_ids)));

    $questionwise_data = [];

    foreach ($questions as $question) {
        $raw_responses = $DB->get_records_sql("SELECT qa.id AS attemptid, qa.responsesummary AS answer, qa2.userid
            FROM {question_attempts} qa
            JOIN {question_usages} qu ON qu.id = qa.questionusageid
            JOIN {quiz_attempts} qa2 ON qa2.uniqueid = qu.id
            WHERE qa.questionid = ? 
              AND qa2.quiz = ? 
              AND qa2.state = 'finished'
              AND qa2.id IN ($latest_attempt_ids_list)", [$question->id, $quizid]);

        $responses = [];
        $response_users = [];
        $user_latest_answer = [];

        // Normalize response and store
        foreach ($raw_responses as $r) {
            $normalized = strtolower(trim(html_entity_decode($r->answer, ENT_QUOTES | ENT_HTML5)));
            $user_latest_answer[$r->userid] = $normalized;
        }

        foreach ($user_latest_answer as $userid => $normalized) {
            if (!isset($responses[$normalized])) {
                $responses[$normalized] = (object) [
                    'answer' => $normalized,
                    'count' => 0
                ];
            }
            $responses[$normalized]->count++;
        }

        if (!empty($user_latest_answer)) {
            list($in_sql, $params) = $DB->get_in_or_equal(array_keys($user_latest_answer));
            $user_data = $DB->get_records_sql("SELECT id, firstname, lastname, email, picture, imagealt
                FROM {user}
                WHERE id $in_sql", $params);

            foreach ($user_latest_answer as $userid => $normalized) {
                if (!isset($user_data[$userid]))
                    continue;
                $u = $user_data[$userid];
                $response_users[$normalized][] = (object) [
                    'userid' => $u->id,
                    'firstname' => $u->firstname,
                    'lastname' => $u->lastname,
                    'email' => $u->email,
                    'picture' => $u->picture,
                    'imagealt' => $u->imagealt
                ];
            }
        }

        $answers = $DB->get_records('question_answers', ['question' => $question->id]);

        $all_answers = [];
        $correct_answers = [];

        foreach ($answers as $ans) {
            $text = trim(strip_tags($ans->answer));
            $normalized = strtolower(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
            $all_answers[$normalized] = $text;

            if ((int) $ans->fraction == 1) {
                $correct_answers[] = $normalized;
            }

            if (!isset($responses[$normalized])) {
                $responses[$normalized] = (object) [
                    'answer' => $text,
                    'count' => 0
                ];
            }

            if (!isset($response_users[$normalized])) {
                $response_users[$normalized] = [];
            }
        }

        $questionwise_data[$question->id] = [
            'text' => $question->questiontext,
            'responses' => $responses,
            'users' => $response_users,
            'correct_answers' => $correct_answers
        ];
    }
    $PAGE = new moodle_page();
    $reporturl = 'Back';
    $PAGE->navbar->add($reporturl, new moodle_url('/local/quizreport/'));
    $PAGE->navbar->add((get_string('quizreport', 'local_quizreport')), '/local/quizreport/');
    // Display the report
    echo $OUTPUT->header();
    echo '<h2>Quiz Report: ' . htmlspecialchars($quiz->name) . '</h2>';
    $average_time = '00:00';
    $max_time_seconds = 0;

    if ($total_responses) {
        $average_seconds = $total_time / $total_responses;
        $average_time = gmdate('i:s', $average_seconds);
    }

    // Find maximum time spent in a single attempt for dynamic scaling
    foreach ($latest_attempts as $attempt) {
        if ($attempt->timefinish && $attempt->timestart) {
            $duration = $attempt->timefinish - $attempt->timestart;
            if ($duration > $max_time_seconds) {
                $max_time_seconds = $duration;
            }
        }
    }

    // Use total as max to show full circle if only one attempt/response
    $percent_responses = 100;
    $percent_attempts = 100;
    $percent_time = ($max_time_seconds > 0 && $average_seconds > 0)
        ? round(($average_seconds / $max_time_seconds) * 100)
        : 0;

    ?>
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <div class="circle-report-card">
        <div class="circle-boxes-horizontal">
            <!-- Total Responses -->
            <div class="circle-box">
                <div class="circle-progress" data-percent="<?php echo $percent_responses; ?>">
                    <svg width="140" height="140">
                        <circle class="circle-bg" cx="70" cy="70" r="60" />
                        <circle class="circle-fg" data-color="blue" cx="70" cy="70" r="60" />
                    </svg>
                    <div class="circle-text mt-3"><?php echo $total_responses; ?></div>
                </div>
                <div class="circle-label"><i class="fa fa-bar-chart"></i>
                    <?php echo get_string('totalresponses', 'local_quizreport'); ?></div>
            </div>

            <!-- Average Time -->
            <div class="circle-box">
                <div class="circle-progress" data-percent="<?php echo $percent_time; ?>">
                    <svg width="140" height="140">
                        <circle class="circle-bg" cx="70" cy="70" r="60" />
                        <circle class="circle-fg" data-color="orange" cx="70" cy="70" r="60" />
                    </svg>
                    <div class="circle-text" style="font-size: 14px;">Avg <?php echo $average_time; ?></div>
                    <div class="circle-subtext"><?php echo $percent_time ?>%</div>
                </div>
                <div class="circle-label"><i class="bi bi-clock-fill"></i>
                    <?php echo get_string('averagetime', 'local_quizreport'); ?></div>
            </div>

            <!-- Total Attempts -->
            <div class="circle-box">
                <div class="circle-progress" data-percent="<?php echo $percent_attempts; ?>">
                    <svg width="140" height="140">
                        <circle class="circle-bg" cx="70" cy="70" r="60" />
                        <circle class="circle-fg" data-color="blue" cx="70" cy="70" r="60" />
                    </svg>
                    <div class="circle-text mt-3"> <?php echo $total_attempts; ?></div>
                </div>
                <div class="circle-label"><i class="bi bi-clipboard2-check-fill"></i>
                    <?php echo get_string('totalattempts', 'local_quizreport'); ?></div>
            </div>
        </div>

        <!-- Download Buttons Inside Card (Bottom Right) -->
        <div class="report-download-bar-card">
            <a href="download_report.php?quizid=<?php echo $quizid; ?>" class="btn btn-download csv">
                <i class="fas fa-file-csv"></i> <?php echo get_string('downloadreport', 'local_quizreport'); ?> (CSV)
            </a>
            <a href="download_report_pdf.php?quizid=<?php echo $quizid; ?>" class="btn btn-download pdf">
                <i class="fas fa-file-pdf"></i> PDF Report
            </a>
        </div>
    </div>

    <script>
        document.querySelectorAll('.circle-progress').forEach(el => {
            const circle = el.querySelector('.circle-fg');
            const percent = parseFloat(el.getAttribute('data-percent'));
            const radius = 60;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percent / 100) * circumference;
            circle.style.strokeDasharray = `${circumference}`;
            circle.style.strokeDashoffset = offset;
        });
    </script>


    <?php
    echo '<h3 style="color:#003152;"><i class="fas fa-question-circle"></i> ' . get_string('questionwise', 'local_quizreport') . '</h3>';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
    // echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';
    echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>';



    echo '<div id="question-container" class="question-container">';

    $question_blocks = [];
    $index = 0;

    foreach ($questionwise_data as $qid => $qdata) {
        ob_start();
        echo '<div class="question-block card" data-index="' . $index . '">';

        echo '<div class="card-body">';

        // Left: Question + responses
        echo '<div class="card-left">';
        echo '<p class="question-text"><i class="fas fa-circle-question"></i> ' . htmlspecialchars(strip_tags($qdata['text'])) . '</p>';
        echo '<ul class="response-list">';

        $chart_labels = [];
        $chart_data = [];
        $chart_colors = [];
        $colors = ['#ec9707', '#003152', '#6c757d', '#17a2b8', '#ffc107'];
        $color_index = 0;

        $generatedModals = [];

        foreach ($qdata['responses'] as $response) {
            $answer = $response->answer ?: 'No response';
            if ($answer === 'No response') {
                continue;
            }
            $count = $response->count;
            $normalized = strtolower(trim(html_entity_decode($answer, ENT_QUOTES | ENT_HTML5)));

            // ✅ Add tick if this normalized answer is marked as correct
            $tick = in_array($normalized, $qdata['correct_answers'])
                ? ' <i class="bi bi-check-circle-fill text-success" title="Correct answer"></i>'
                : '';

            $modalKey = $qid . '|' . $answer;
            $modalId = 'modal-' . $qid . '-' . md5($answer);

            echo '<li>' . htmlspecialchars($answer) . $tick . ': 
                <a href="#" data-toggle="modal" class="btn-primary-t btn btn-sm p-1" data-target="#' . $modalId . '">
                    <u>' . $count . '</u>
                </a>
            </li>';

            $normalized = strtolower(trim($answer));
            if (!isset($generatedModals[$modalKey]) && !empty($qdata['users'][$normalized])) {

                $generatedModals[$modalKey] = true;
                $users = $qdata['users'][$normalized];
                $totalpages = ceil(count($users) / 5);

                echo '
                <div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header p-3" style="background:#003152;color:white;">
                                <h5 class="modal-title-md text-light" id="' . $modalId . 'Label">Users who answered: ' . htmlspecialchars($answer) . '</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true" class="mr-2 text-bold">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body"> ';
                echo '
<div class="input-group mb-3">
    <div class="input-group-prepend">
        <span class="input-group-text p-1 bg-white border-right-0">
            <i class="fa fa-search text-muted"></i>
        </span>
    </div>
    <input type="text" class="form-control p-0 col-6 border-left-0" placeholder="Search users..." onkeyup="searchUsers(this, \'' . $modalId . '\')">
</div>';


                foreach ($users as $index => $user) {
                    $userpic = $OUTPUT->user_picture((object) [
                        'id' => $user->userid,
                        'picture' => $user->picture,
                        'imagealt' => $user->imagealt,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                    ]);

                    echo '
                    <div class="user-item mb-3" data-page="' . floor($index / 5) . '" style="' . ($index >= 5 ? 'display:none;' : '') . '">
                        <div class="d-flex align-items-center">
                            ' . $userpic . '
                            <div class="ml-3">
                                <strong>' . fullname($user) . '</strong><br>
                                <small>' . s($user->email) . '</small>
                            </div>
                        </div>
                    </div>';
                }

                echo '
                            </div>';

                // Pagination controls
                if ($totalpages > 1) {
                    echo '<div class="modal-footer p-0 justify-content-center">';
                    echo '<nav><ul class="pagination mb-0">';
                    for ($p = 0; $p < $totalpages; $p++) {
                        echo '<li class="page-item popuser' . ($p === 0 ? ' active' : '') . '">
                                <button type="button" class="page-link p-2 m-1" data-page="' . $p . '" onclick="showUserPage(this, \'' . $modalId . '\')">' . ($p + 1) . '</button>
                              </li>';
                    }
                    echo '</ul></nav></div>';
                }

                echo '</div></div></div>'; // modal-content, modal-dialog, modal
            }

            $chart_labels[] = $answer;
            $chart_data[] = $response->count;
            $chart_colors[] = $colors[$color_index % count($colors)];
            $color_index++;
        }

        echo '</ul>';
        echo '</div>'; // end .card-left
        // Right: Chart
        echo '<div class="card-right"><canvas id="chart-' . $qid . '"></canvas></div>';

        echo '</div>'; // end .card-body
        echo '</div>'; // end .question-block

        $chart_js = '<script>
        new Chart(document.getElementById("chart-' . $qid . '"), {
            type: "pie",
            data: {
                labels: ' . json_encode($chart_labels) . ',
                datasets: [{
                    data: ' . json_encode($chart_data) . ',
                    backgroundColor: ' . json_encode($chart_colors) . ',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 10,
                        bottom: 30
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: "bottom",
                        labels: {
                            boxWidth: 20,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>';


        echo $chart_js;

        $question_blocks[] = ob_get_clean();
        $index++;
    }

    echo implode('', $question_blocks);
    echo '</div>';


    // Pagination buttons
    echo '<div id="pagination" style="text-align:center; margin-top: 20px;">';
    echo '<button onclick="prevPage()" class="pag-btn"><i class="fas fa-arrow-left"></i> Prev</button>';
    echo '<span id="page-info" style="margin: 0 10px; font-weight: bold; color:#003152;"></span>';
    echo '<button onclick="nextPage()" class="pag-btn">Next <i class="fas fa-arrow-right"></i></button>';
    echo '</div>';

    // Download Buttons
    echo '<div style="text-align: center; margin-top: 30px;">';
    echo '<a href="download_report.php?quizid=' . $quizid . '" class="btn btn-download csv"><i class="fas fa-file-csv"></i> ' . get_string('downloadreport', 'local_quizreport') . ' (CSV)</a>';
    echo '<a href="download_report_pdf.php?quizid=' . $quizid . '" class="btn btn-download pdf"><i class="fas fa-file-pdf"></i> PDF Report</a>';
    echo '</div>';
    ?>
    <script>
        function showUserPage(button, modalId) {
            const page = parseInt(button.getAttribute('data-page'));
            const modal = document.getElementById(modalId);
            const items = modal.querySelectorAll('.user-item');

            // Hide all
            items.forEach(item => item.style.display = 'none');

            // Show selected page
            items.forEach(item => {
                if (parseInt(item.getAttribute('data-page')) === page) {
                    item.style.display = 'block';
                }
            });

            // Update active button
            const paginationButtons = modal.querySelectorAll('.pagination .page-item');
            paginationButtons.forEach(li => li.classList.remove('active'));
            button.closest('.page-item').classList.add('active');
        }

        function searchUsers(input, modalId) {
            const filter = input.value.toLowerCase();
            const modal = document.getElementById(modalId);
            const items = modal.querySelectorAll('.user-item');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? 'block' : 'none';
            });

            // Optional: hide pagination while searching
            const pagination = modal.querySelector('.pagination');
            if (pagination) pagination.style.display = filter ? 'none' : 'flex';
        }


        const itemsPerPage = 5;
        const blocks = document.querySelectorAll('.question-block');
        let currentPage = 1;
        const totalPages = Math.ceil(blocks.length / itemsPerPage);

        function showPage(page) {
            blocks.forEach((block, index) => {
                block.style.display = (index >= (page - 1) * itemsPerPage && index < page * itemsPerPage) ? 'block' : 'none';
            });
            document.getElementById('page-info').textContent = `Page ${page} of ${totalPages}`;
        } function nextPage() {
            if
                (currentPage < totalPages) { currentPage++; showPage(currentPage); }
        } function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        }

        showPage(currentPage);
    </script>

    <?php

} catch (Exception $e) {
    echo $OUTPUT->header();
    echo '<h2>Error</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo $OUTPUT->footer();
    exit;
}
?>