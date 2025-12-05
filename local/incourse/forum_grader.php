<?php
require_once("../../config.php");
require_login();
global $DB, $PAGE, $OUTPUT;

// $courseid = required_param('courseid', PARAM_INT);
$courseid = 7; // example
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);

$PAGE->set_url(new moodle_url('/local/incourse/forum_grader.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Forum Grader - <?php echo $course->fullname; ?></h1>

    <!-- SECTION DROPDOWN -->
    <label class="font-semibold">Select Week (Section)</label>
    <select id="sectionSelect" class="border p-2 rounded w-full mb-4">
        <option value="">-- Select Week --</option>
        <?php
$modinfo = get_fast_modinfo($courseid);
$sections = $modinfo->get_section_info_all();

foreach ($sections as $s) {
    // Skip General section (section 0)
    if ($s->section == 0) continue;

    // Display the exact name as shown on the course page
    $name = trim($s->name);
    if ($name === '') {
        // fallback only if absolutely no name
        $name = $s->section >= 1 ? "Section {$s->section}" : "Section";
    }

    echo "<option value='{$s->section}'>{$name}</option>";
}

        ?>
    </select>

    <!-- FORUM DROPDOWN -->
    <div id="forumContainer" class="hidden">
        <label class="font-semibold">Select Forum</label>
        <select id="forumSelect" class="border p-2 rounded w-full mb-4"></select>
    </div>

    <!-- RESULT TABLE -->
    <div id="resultContainer" class="hidden">
        <h2 class="text-xl font-bold mb-4">Forum Responses</h2>

        <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Student Name</th>
                    <th class="px-4 py-2">Response</th>
                    <th class="px-4 py-2 text-center">Grade</th>
                </tr>
            </thead>
            <tbody id="resultBody"></tbody>
        </table>
    </div>
</div>

<script>
$("#sectionSelect").change(function() {
    let section = $(this).val();
    $("#resultContainer").addClass('hidden');

    if (section === "") {
        $("#forumContainer").addClass('hidden');
        return;
    }

    $.ajax({
        url: "forum_grader_ajax.php",  // ✅ correct
        method: "GET",
        data: { 
            section: section, 
            courseid: <?= $courseid ?>  // ✅ required parameter
        },
        dataType: "json",
        success: function(res) {
            $("#forumSelect").empty();
            $("#forumSelect").append(`<option value="">-- Select Forum --</option>`);

            res.forEach(f => {
                $("#forumSelect").append(`<option value="${f.id}">${f.name}</option>`);
            });

            $("#forumContainer").removeClass('hidden');
        }
    });
});

$("#forumSelect").change(function() {
    let forumid = $(this).val();
    if (forumid === "") return;

    $.ajax({
        url: "forum_grader_ajax.php",
        method: "GET",
        data: { 
            forumid: forumid, 
            courseid: <?= $courseid ?> 
        },
        dataType: "json",
        success: function(res) {
            $("#resultBody").empty();

            if (res.length === 0) {
                $("#resultBody").html("<tr><td colspan='3' class='text-center p-4'>No responses found</td></tr>");
            } else {
                res.forEach(r => {
                    $("#resultBody").append(`
                        <tr class="border-t">
                            <td class="px-4 py-2">${r.student}</td>
                            <td class="px-4 py-2">${r.response}</td>
                            <td class="px-4 py-2 text-center font-semibold">${r.grade}</td>
                        </tr>
                    `);
                });
            }

            $("#resultContainer").removeClass('hidden');
        }
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
