<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains functionality.
 *
 * @package    report_custom_report
 * @author     Uvais
 * @copyright  2012 Isuru Madushanka Weerarathna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

require_once 'locallib.php';
require "$CFG->libdir/tablelib.php";
require_once 'tableview.php';

require_login();

$heading = get_string('addcourse', LANGFILE);
$context = context_system::instance();
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$reporturl = 'Back';
$PAGE->navbar->add($reporturl, new moodle_url('/local/content_structure/'));
$PAGE->navbar->add(get_string('addcourse', 'local_content_structure'), '/local/content_structure/index.php');
//$PAGE->set_heading($heading);
$PAGE->requires->js_call_amd('tool_datatables/init', 'init', array('.datatable', array()));
//$download = optional_param('download', '', PARAM_ALPHA);
$pid = required_param('pid', PARAM_INT);

$id = $_GET['id'];
$delete = optional_param('delete', '', PARAM_INT);

$record = '';
if ($id) {
    $record = $DB->get_record('local_content_structure', array('id' => $id));
}
//print_object($record); die;
//local lib instance
$localObj = new local_content_structure();
if ($delete) {
    delete_course_content($delete);
}

//$PAGE->navbar->add($heading, new moodle_url('index.php'));
$PAGE->set_url($CFG->wwwroot . BASEURL . '/index.php');
//$PAGE->navbar->add($record->name, new moodle_url($CFG->wwwroot . BASEURL . '/index.php'));
//$PAGE->navbar->add($heading, new moodle_url('container.php?id=' . $pid));
require_capability('local/content_structure:view', $context);
$table = new courseview('uniqueid');
//$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    //    $PAGE->navbar->add('Testing table class', new moodle_url('/test.php'));
    echo $OUTPUT->header();
}

require_once 'content_form.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitbutton']) && trim($_POST['submitbutton']) === 'Save') {
    create_course_content((object) $_POST, $_FILES, 'course');
    redirect(new moodle_url('container.php', ['pid' => $_POST['pid']])); // ✅ Prevent form resubmission
}


//Display list of reports
$table->display_report_list($pid);
$table->define_baseurl($CFG->wwwroot . BASEURL . '/container.php?pid=' . $pid);
//
$table->out(10, true);

?>

<div>
    <img src="images/loading.gif" width="100" id="loading" style="display: none;">
</div>
<script src="js/jquery-3.5.1.js"></script>
<script src="js/local.js"></script>
<!--MODAL-->
<div class="modal fade" id="moduleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 650px;">
        <div class="modal-content">
            <div class="modal-header btn-primary-t">
                <h2 class="modal-title text-light d-flex justify-content-start align-items-center"
                    id="exampleModalLabel" style="text-align:center !important; width: 100%;">
                    <?php echo get_string('addmodule', LANGFILE); ?>

                </h2>
                <button type="button" class="close closemodal text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-1">

            </div>
        </div>
    </div>
</div>
<!--MODAL END-->
<style>
    #loading {
        margin: 0px;
        padding: 0px;
        position: fixed;
        right: 0px;
        top: 0px;
        background-color: rgb(102, 102, 102);
        z-index: 30001;
        opacity: 0.5;
        color: White;
        top: 50%;
        left: 45%;
    }

    .upload-box {
        border: 2px dashed #ccc;
        padding: 30px 20px;
        border-radius: 10px;
        background-color: #fafafa;
        transition: 0.3s ease;
        width: 100%;
        cursor: pointer;
    }

    .upload-box:hover {
        background-color: #f0f0f0;
        border-color: #999;
    }

    .drag-text {
        font-size: 1.25rem;
        font-weight: 500;
        color: #333;
    }
</style>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<?php
echo $OUTPUT->footer();
?>
<script>
    require(['jquery'], function ($) {
        $(document).ready(function () {
            $("body").on("click", ".viewfile", function (e) {
                e.preventDefault();

                const archtype = $(this).attr("archtype");
                const fileUrl = $(this).data("url");
                const fileType = $(this).data("type");
                const ext = fileUrl.split(".").pop().toLowerCase();

                let iconClass = "fa-file";
                if (["pdf"].includes(ext)) iconClass = "fa-file-pdf-o";
                else if (["jpg", "jpeg", "png", "gif"].includes(ext)) iconClass = "fa-file-image-o";
                else if (["mp4", "mov", "avi"].includes(ext)) iconClass = "fa-file-video-o";
                else if (["doc", "docx"].includes(ext)) iconClass = "fa-file-word-o";
                else if (["ppt", "pptx"].includes(ext)) iconClass = "fa-file-powerpoint-o";
                else if (fileType === "link") iconClass = "fa-link";

                $("#exampleModalLabel").html(`<i class="fa ${iconClass} mr-2 border p-1 rounded"></i> ${archtype}`);
                $(".modal-body").html(`<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>`);
                $("#exampleModalLabel").show();

                if (!fileUrl) {
                    $(".modal-body").html("File not found or unsupported.");
                    return;
                }
  // ✅ YouTube embed handler
    // =====================
    if (/youtube\.com\/watch\?v=/.test(fileUrl) || /youtu\.be\//.test(fileUrl)) {
        let videoId = "";
        if (fileUrl.includes("watch?v=")) {
            videoId = new URL(fileUrl).searchParams.get("v");
        } else if (fileUrl.includes("youtu.be/")) {
            videoId = fileUrl.split("youtu.be/")[1].split("?")[0];
        }
        if (videoId) {
            const embedUrl = `https://www.youtube.com/embed/${videoId}`;
            $(".modal-body").html(`
                <iframe width="100%" height="500" src="${embedUrl}" 
                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; 
                    encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
                </iframe>
            `);
        } else {
            $(".modal-body").html(`Invalid YouTube URL.`);
        }
        return;
    } 
                // Google Drive embed handler
                if (/drive\.google\.com\/file\/d\//.test(fileUrl)) {
                    const match = fileUrl.match(/file\/d\/([^/]+)/);
                    if (match) {
                        const fileId = match[1];
                        const embedUrl = `https://drive.google.com/file/d/${fileId}/preview`;

                        $(".modal-body").html(`
            <p class="text-danger">Google Drive video previews may not work inside this page due to Google restrictions.</p>
            <a href="${fileUrl}" target="_blank" class="btn btn-primary">Open in Google Drive</a>
        `);
                    } else {
                        $(".modal-body").html(`Invalid Google Drive URL.`);
                    }
                    return;
                }


                // Native handlers
                if (["pdf"].includes(ext)) {
                    $(".modal-body").html(`<iframe src="${fileUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`);
                } else if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                    $(".modal-body").html(`<img src="${fileUrl}" style="max-width:100%; height:auto;" />`);
                } else if (["mp4", "mov", "avi"].includes(ext)) {
                    $(".modal-body").html(`
            <video controls style="width:100%; max-height: 500px;">
                <source src="${fileUrl}" type="video/${ext}">
                Your browser does not support the video tag.
            </video>
        `);
                } else if (["doc", "docx", "ppt", "pptx"].includes(ext)) {
                    if (window.location.hostname !== "localhost") {
                        const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
                        $(".modal-body").html(`<iframe src="${officeViewerUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`);
                    } else {
                        $(".modal-body").html(`<a href="${fileUrl}" target="_blank">Download and open with Office</a>`);
                    }
                } else if (fileType === "link") {
                    $(".modal-body").html(`<iframe src="${fileUrl}" width="100%" height="500px" frameborder="0"></iframe>`);
                } else {
                    $(".modal-body").html(`<a href="${fileUrl}" target="_blank">Download File</a>`);
                }
            });

            const dropCourseArea = $('#drop-area-course');
            const courseFileInput = $('#id_coursefile');
            const courseFileInfoBox = $('#course-file-info');
            const courseFileName = $('#course-file-name');
            const courseFileSize = $('#course-file-size');
            const courseUploadStatus = $('#course-upload-status');
            const browseCourseBtn = $('#browseCourseBtn');

            const allowedExtensions = ['pdf', 'ppt', 'pptx', 'docx', 'mp4', 'mov'];
            const maxFileSize = 50 * 1024 * 1024; // 50MB

            browseCourseBtn.on('click', function () {
                courseFileInput.trigger('click');
            });

            function handleCourseFile(file) {
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    alert('Invalid file type. Allowed: PDF, PPT, DOCX, MP4, MOV.');
                    return;
                }

                if (file.size > maxFileSize) {
                    alert('File exceeds 50MB limit.');
                    return;
                }

                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                courseFileName.text(file.name);
                courseFileSize.text(sizeInMB + ' MB');
                courseUploadStatus.text('File uploaded successfully.').addClass('text-success');
                courseFileInfoBox.show();
            }

            courseFileInput.on('change', function (e) {
                const file = e.target.files[0];
                if (file) handleCourseFile(file);
            });

            dropCourseArea.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropCourseArea.addClass('dragover');
            });

            dropCourseArea.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropCourseArea.removeClass('dragover');
            });

            dropCourseArea.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropCourseArea.removeClass('dragover');

                const file = e.originalEvent.dataTransfer.files[0];
                if (file) {
                    courseFileInput[0].files = e.originalEvent.dataTransfer.files;
                    handleCourseFile(file);
                }
            });
        });
        $('#moduleModal').on('hidden.bs.modal', function () {
    // Stop <video> (if any)
    const video = $(this).find('video').get(0);
    if (video) {
        video.pause();
        video.currentTime = 0;
    }

    // Stop <iframe> (e.g., YouTube embed)
    const iframe = $(this).find('iframe');
    if (iframe.length) {
        iframe.attr('src', iframe.attr('src')); // reloads & stops playback
    }
});

    });
</script>