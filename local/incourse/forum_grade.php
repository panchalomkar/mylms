<?php 
require_once("../../config.php");
require_login();
global $DB, $USER, $CFG, $OUTPUT;

// --------------------
// COURSE SELECTION HANDLING
// --------------------
$courseid = optional_param('courseid', 0, PARAM_INT);

// Always set page URL
$PAGE->set_url(new moodle_url('/local/incourse/forum_grade.php', ['courseid'=>$courseid]));
$PAGE->set_pagelayout('standard');

?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<?php
echo $OUTPUT->header();

// Fetch all courses for dropdown
$courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');


// Dropdown with icon and label (proper alignment)
echo '<div class="max-w-5xl mx-auto p-6">
        <label class="flex items-center gap-2 font-medium text-gray-700 text-lg">
            <span class="material-icons text-blue-500">school</span>
            Select Course:
        </label>
        <form method="GET" class="mt-2">
            <select name="courseid" id="courseid" class="border p-2 rounded w-80" onchange="this.form.submit()">
                <option value="">-- Select Course --</option>';
foreach ($courses as $id => $name) {
    $selected = ($id == $courseid) ? "selected" : "";
    echo "<option value='$id' $selected>$name</option>";
}
echo '      </select>
        </form>
      </div>';

// Stop if no course selected
if ($courseid == 0) {
    echo '<p class="max-w-5xl mx-auto text-gray-600">Please select a course to load the report.</p>';
    echo $OUTPUT->footer();
    exit;
}

// --------------------
// COURSE SELECTED → LOAD REPORT LOGIC
// --------------------
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);

$PAGE->set_title("Forum Grading Report - " . $course->fullname);
$PAGE->set_heading($course->fullname);
?>



<h1 class="text-2xl font-bold mb-6 max-w-5xl mx-auto flex items-center gap-2">
    <span class="material-icons text-blue-500">forum</span>
    Forum Responses Report - <?php echo $course->fullname; ?>
</h1>

<?php
// Fetch forums (excluding announcements)
$forums = $DB->get_records_sql("
    SELECT f.id as forumid, f.name as forumname, cm.section
    FROM {forum} f
    JOIN {course_modules} cm ON cm.instance = f.id
    WHERE cm.course = :courseid
      AND f.type <> 'news'
    ORDER BY cm.section, f.name
", ['courseid' => $courseid]);

if (empty($forums)) {
    // No forums message
    echo '<div class="max-w-5xl mx-auto p-6 my-6 text-center text-gray-600 border border-dashed border-gray-300 rounded-lg bg-gray-50 flex flex-col items-center gap-3">
            <span class="material-icons text-5xl text-gray-400">forum</span>
            <h2 class="text-xl font-semibold">No forums available</h2>
            <p class="text-gray-500">There are no forums (other than announcements) in this course to display.</p>
          </div>';
} else {
    // Table header
    echo '<div class="max-w-5xl mx-auto overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 border border-gray-300 mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Section / Forum</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Question</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Responses</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($forums as $forum) {
        $question = $DB->get_field_sql("
            SELECT name FROM {forum_discussions}
            WHERE forum = ?
            ORDER BY id ASC LIMIT 1
        ", [$forum->forumid]) ?? "N/A";

        $responsecount = $DB->count_records_sql("
            SELECT COUNT(fp.id)
            FROM {forum_posts} fp
            JOIN {forum_discussions} fd ON fd.id = fp.discussion
            WHERE fd.forum = :forumid AND fp.deleted = 0
        ", ['forumid' => $forum->forumid]);

        echo "
            <tr class='hover:bg-gray-50 cursor-pointer' onclick='openModal({$forum->forumid})'>
                <td class='px-4 py-2'><span class='material-icons align-middle text-blue-500'>forum</span> {$forum->forumname}</td>
                <td class='px-4 py-2'>{$question}</td>
                <td class='px-4 py-2 text-center font-semibold'>{$responsecount}</td>
            </tr>";
    }

    echo '</tbody></table></div>';
}
?>

<!-- Modal -->
<div id="forumModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-40 items-center justify-center">
    <div class="bg-white rounded-lg w-11/12 max-w-5xl p-6 relative mx-auto my-20 shadow-lg">
        <button onclick="closeModal()" class="absolute top-2 right-2 material-icons text-gray-700 hover:text-black cursor-pointer text-2xl">
            close
        </button>

        <!-- Question + Download Buttons -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold flex items-center gap-2" id="modalTitle">
                <span class="material-icons text-blue-500">question_answer</span> Forum Responses
            </h2>
            <div class="flex gap-2 d-none">
                <button id="downloadPdf" class="bg-red-600 text-white px-3 py-1 rounded flex items-center gap-1 hover:bg-red-700">
                    <span class="material-icons">picture_as_pdf</span> PDF
                </button>
                <button id="downloadExcel" class="bg-green-600 text-white px-3 py-1 rounded flex items-center gap-1 hover:bg-green-700">
                    <span class="material-icons">grid_on</span> Excel
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 py-2 text-center text-sm font-medium text-gray-700">#</th>
                        <th class="px-2 py-2 text-center text-sm font-medium text-gray-700">Pic</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Student</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Response</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Grade</th>
                    </tr>
                </thead>
                <tbody id="modalBody" class="text-sm text-gray-700"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentForumId = 0;

function openModal(forumid) {
    currentForumId = forumid;
    $('#forumModal').removeClass('hidden').addClass('flex');
    $('#modalBody').html('<tr><td colspan="6" class="px-4 py-2 text-center">Loading...</td></tr>');

    $.ajax({
        url: 'forum_grade_ajax.php',
        method: 'GET',
        data: { forumid: forumid },
        dataType: 'json',
        success: function(res) {
            $('#modalBody').empty();
            if(res.length === 0) {
                $('#modalBody').html('<tr><td colspan="6" class="px-4 py-2 text-center text-gray-500">No responses found for this forum</td></tr>');
                $('#modalTitle').html('<span class="material-icons text-blue-500 align-middle">question_answer</span> No responses');
                return;
            }

            $('#modalTitle').html('<span class="material-icons text-blue-500 align-middle">question_answer</span> ' + res[0].question);

            res.forEach(function(r, index){
                $('#modalBody').append(
                    '<tr class="border-b">' +
                        '<td class="px-2 py-2 text-center">'+(index+1)+'</td>' +
                        '<td class="px-2 py-2 text-center">'+r.picturee+'</td>' +
                        '<td class="px-4 py-2">'+r.student+'</td>' +
                        '<td class="px-4 py-2">'+r.email+'</td>' +
                        '<td class="px-4 py-2">'+r.response+'</td>' +
                        '<td class="px-4 py-2 text-center font-semibold">'+r.grade+'</td>' +
                    '</tr>'
                );
            });
        },
        error: function() {
            $('#modalBody').html('<tr><td colspan="6" class="px-4 py-2 text-center text-red-600">Error loading responses</td></tr>');
        }
    });
}

function closeModal() {
    $('#forumModal').addClass('hidden').removeClass('flex');
}

// Close modal on clicking overlay
$('#forumModal').click(function(e){
    if(e.target.id === 'forumModal') {
        closeModal();
    }
});

// Download buttons
$('#downloadPdf').click(function() {
    window.location = 'forum_grade_ajax.php?forumid=' + currentForumId + '&download=pdf';
});
$('#downloadExcel').click(function() {
    window.location = 'forum_grade_ajax.php?forumid=' + currentForumId + '&download=xlsx';
});
</script>

<?php echo $OUTPUT->footer(); ?>
