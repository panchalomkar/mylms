<?php
define('AJAX_SCRIPT', true); // Suppresses Moodle headers for clean JSON
require('../../config.php');
require_login();

header('Content-Type: application/json'); // Tell browser it's JSON

$action = required_param('action', PARAM_ALPHA);
$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

global $DB;
$response = ['success' => false, 'message' => 'Unexpected error'];

if ($action === 'update') {
    $videourl = required_param('video_url', PARAM_URL);
    $record = $DB->get_record('course_section_video', ['courseid' => $courseid, 'sectionid' => $sectionid]);

    if ($record) {
        $record->videourl = $videourl;
        $record->timemodified = time();
        $DB->update_record('course_section_video', $record);
    } else {
        $record = (object) [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'videourl' => $videourl,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $DB->insert_record('course_section_video', $record);
    }

    $response = ['success' => true, 'message' => '✅ Video URL updated'];
}

if ($action === 'delete') {
    $deleted = $DB->delete_records('course_section_video', ['courseid' => $courseid, 'sectionid' => $sectionid]);
    $response = $deleted ? ['success' => true, 'message' => '🗑️ Video deleted'] : ['success' => false, 'message' => '❌ Delete failed'];
}

echo json_encode($response);
exit;
// $currentUrlValue = $videoData['url'];
// // === Edit/Delete Icons + Input Form
// $videoHTML .= <<<HTML
// <div class="video-actions" data-courseid="$courseid" data-sectionid="$sectionid">
// <button class="edit-btn">✏️ Edit</button>
// <button class="delete-btn">🗑️ Delete</button>
// </div>

// <form method="post" class="video-form" id="video-form-$sectionid" style="display:none; max-width:500px; margin:20px auto;">
// <label for="video_url_$sectionid"><strong>Update Video URL:</strong></label>
// <input type="url" name="video_url" id="video_url_$sectionid" value="$currentUrlValue" placeholder="https://example.com/video" required style="width:100%; padding:8px;">
// <button type="submit" style="margin-top:10px; padding:6px 12px;">Save</button>
// </form>

// HTML;

// <script>
//     document.addEventListener('DOMContentLoaded', function () {
//         // Handle edit button
//         document.body.addEventListener('click', function (e) {
//             if (e.target.classList.contains('edit-btn')) {
//                 const container = e.target.closest('.video-actions');
//                 const sectionid = container.dataset.sectionid;
//                 const form = document.getElementById('video-form-' + sectionid);
//                 form.style.display = 'block';
//             }
//         });

//         // Handle delete button
//         document.body.addEventListener('click', function (e) {
//             if (e.target.classList.contains('delete-btn')) {
//                 const container = e.target.closest('.video-actions');
//                 const courseid = container.dataset.courseid;
//                 const sectionid = container.dataset.sectionid;

//                 if (confirm("Are you sure you want to delete this video?")) {
//                     fetch('add_video.php', {
//                         method: 'POST',
//                         headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//                         body: new URLSearchParams({
//                             action: 'delete',
//                             courseid,
//                             sectionid
//                         })
//                     })
//                         .then(res => res.json())
//                         .then(data => {
//                             alert(data.message);
//                             if (data.success) location.reload(); // Or remove video DOM
//                         });
//                 }
//             }
//         });

//         // Handle form submit (update video URL)
//         document.querySelectorAll('.video-form').forEach(form => {
//             form.addEventListener('submit', function (e) {
//                 e.preventDefault();
//                 const sectionid = this.id.split('-')[2];
//                 const courseid = this.closest('.video-actions')?.dataset.courseid || document.querySelector(`[data-sectionid="${sectionid}"]`)?.dataset.courseid;
//                 const video_url = this.querySelector('input[name="video_url"]').value;

//                 fetch('add_video.php', {
//                     method: 'POST',
//                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//                     body: new URLSearchParams({
//                         action: 'update',
//                         courseid,
//                         sectionid,
//                         video_url
//                     })
//                 })
//                     .then(res => res.json())
//                     .then(data => {
//                         alert(data.message);
//                         // if (data.success) location.reload(); // Or update iframe src dynamically
//                     });
//             });
//         });
//     });
// </script>