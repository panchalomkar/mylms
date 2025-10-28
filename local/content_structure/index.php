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

$heading = get_string('pluginname', LANGFILE);
$context = context_system::instance();
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$reporturl = 'Home';
$PAGE->navbar->add($reporturl, new moodle_url('/my'));
$PAGE->navbar->add(get_string('pluginname', 'local_content_structure'), '/local/content_structure/index.php');
//$download = optional_param('download', '', PARAM_ALPHA);
$id = $_GET['id'];
$delete = optional_param('delete', '', PARAM_INT);

$record = '';
if ($id) {
    $record = $DB->get_record('local_content_structure', array('id' => $id));
}
print_object($record);
//local lib instance
$localObj = new local_content_structure();
if ($delete) {
    delete_program($delete);
}

//$PAGE->navbar->add($heading, new moodle_url('index.php'));
$PAGE->set_url($CFG->wwwroot . BASEURL . '/index.php');

require_capability('local/content_structure:view', $context);
$table = new tableview('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    //    $PAGE->navbar->add('Testing table class', new moodle_url('/test.php'));
    echo $OUTPUT->header();
}
require_once 'form.php';
?>
<style>
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

    #file-info p {
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }
</style>

<?php
echo '<div class="manage_btns">';
echo '<a style="background:#003152;color:#fff;" href="#" class="btn" data-toggle="modal" data-target="#programModal"><i class="fa fa-plus" aria-hidden="true"></i> Create Folder</a>';
echo '<a style="background:#003152;color:#fff;" href="' . $CFG->wwwroot . BASEURL . '/gallery/" class="btn "> <i class="fa fa-eye" aria-hidden="true"></i> View CMS</a>';
echo '<a style="background:#003152;color:#fff;" href="' . $CFG->wwwroot . BASEURL . '/assign.php" class="btn "><i class="fa fa-list" aria-hidden="true"></i> Assign CMS</a>';
echo '</div>';


if (isset($_POST['submitbutton']) && trim($_POST['submitbutton']) == 'Save') {

    create_program((object) $_POST, $_FILES);
}
//Display list of reports
//$table->display_report_list();
//$table->define_baseurl($CFG->wwwroot . BASEURL . '/index.php');
////
//$table->out(15, true);
//data tables
global $DB, $USER;
$SQL = "SELECT cr.id, cr.name, cr.image, cr.timecreated, '' As enter, '' AS edit
        FROM {local_content_structure} cr WHERE parent=0";
$table = '<table id="example" class="display table-hover table-striped" style="width:100%">';
$table .= '<thead>
            <tr style="background: #003152;color: #fff;">
                <th  >' . get_string('icon', LANGFILE) . '</th>
                <th>' . get_string('programname', LANGFILE) . '</th>
                <th>' . get_string('timecreated', LANGFILE) . '</th>
                <th>' . get_string('action', LANGFILE) . '</th>
            </tr>
        </thead><tbody>';
$records = $DB->get_records_sql($SQL);
foreach ($records as $record) {

    $name = '<a href="' . $CFG->wwwroot . BASEURL . '/container.php?pid=' . $record->id . '">' . $record->name . '</a>';
    if ($record->image) {
        $icon = '<img src="' . $CFG->wwwroot . LOCALFILEPATH . '/media/' . $record->image . '" width="60">';
    } else {
        $icon = '<img src="' . $CFG->wwwroot . LOCALFILEPATH . '/folderimg.png" width="60">';
    }

    // prevent editing of admins by non-admins
    $btn = '';
    if (is_siteadmin($USER)) {
        $buttons = [];
        $buttons[] = '<a href="' . $CFG->wwwroot . BASEURL . '/container.php?pid=' . $record->id . '" <i class="icon fa fa-eye" aria-hidden="true" title="View"></i>'
            . '<img src="' . $CFG->wwwroot . BASEURL . '/images/enter.png" width="20" title="View" class="hideimg"></a>';
        $buttons[] = '<a href="#" class="btn-edit" data-id="' . $record->id . '" data-toggle="modal" data-target="#programModal">' .
            $OUTPUT->pix_icon('t/edit', get_string('edit')) . '</a>';

        $buttons[] = '<a href="#"><img style="    position: relative;bottom: 4px;" src="' . $CFG->wwwroot . '/local/content_structure/images/delete.png" width="20" title="Delete" class="delete-content" id="' . $record->id . '" type="container"></a>';
    }

    $btn = implode(' ', $buttons);


    $table .= ' <tr>
                <td>' . $icon . '</td>
                <td>' . $name . '</td>
                <td>' . date('d-m-Y H:i', $record->timecreated) . '</td>
                <td>' . $btn . '</td>
                </tr>';
}
$table .= '</tbody></table>';
echo $table;
//data tables end



?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css" type="text/css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<?php
echo $OUTPUT->footer();
?>
<script>
    $(document).ready(function () {
        $('.btn-edit').on('click', function (e) {
            e.preventDefault();

            const id = $(this).data('id');

            $.ajax({
                url: 'ajax.php',
                method: 'GET',
                data: { id: id, action: 'getprogram' },
                dataType: 'json',
                success: function (data) {
                    $('#programModalLabel').text('Edit Folder');

                    // Set form values
                    $('#id_name').val(data.name);
                    $('input[name="id"]').val(data.id);

                    // Set preview image
                    const imgSrc = data.image
                        ? M.cfg.wwwroot + '/local/content_structure/images/media/' + data.image
                        : M.cfg.wwwroot + '/local/content_structure/images/uploadimg.png';
                    $('#preview-img').attr('src', imgSrc);

                    $('#programModal').modal('show');
                },
                error: function () {
                    alert('Could not load data. Please try again.');
                }
            });
        });

        // Optional: clear form on modal close
        $('#programModal').on('hidden.bs.modal', function () {
            $('#programModalLabel').text('Create Folder');
            $('#mform1')[0].reset();
            $('#preview-img').attr('src', M.cfg.wwwroot + '/local/content_structure/images/uploadimg.png');
            $('input[name="id"]').val('');
        });
    });

    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    $(document).ready(function () {
        $('#example').DataTable({
            "aoColumnDefs": [
                { 'bSortable': false, 'aTargets': [0, 3] }
            ]

        });
    });

    //    function myFunction() {
    //        var input, filter, table, tr, td, i, txtValue;
    //        input = document.getElementById("myInput");
    //        filter = input.value.toUpperCase();
    //        table = document.getElementById("myTable");
    //        tr = table.getElementsByTagName("tr");
    //        for (i = 0; i < tr.length; i++) {
    //            td = tr[i].getElementsByTagName("td")[1];
    //            if (td) {
    //                txtValue = td.textContent || td.innerText;
    //                if (txtValue.toUpperCase().indexOf(filter) > -1) {
    //                    tr[i].style.display = "";
    //                } else {
    //                    tr[i].style.display = "none";
    //                }
    //            }
    //        }
    //    }

    $('body').on('click', '.delete-content', function (e) {
        e.preventDefault();

        var id = $(this).attr('id');
        var type = $(this).attr('type');

        if (confirm('Are you sure you want to delete the content?')) {
            var url = 'ajax.php?action=deletecontent';
            $.ajax({
                url: url,
                dataType: 'html',
                type: "POST",
                data: { id: id, type: type },
                success: function (data) {
                    // Option 1: reload page (if delete successful)
                    window.location.reload();

                    // OR Option 2: remove the row without reload
                    // $(this).closest('tr').remove();
                },
                error: function () {
                    alert('Data saving error');
                },
            });
        }
    });


    setTimeout(function () {
        $('#example tr th:nth-child(1)').removeClass('sorting_asc')

    }, 100);
    //   sorting :nth-child(2)'
    // $('#example tr th.sorting_asc').removeClass('sorting_asc');
    //   $('.sorting_disabled').removeClass('sorting_asc');
    //   $('#example tr th:nth-child(2)').click();
    //   $('#example').addClass('test');
    // Clear form and close modal
    require(['jquery'], function ($) {
        $(document).ready(function () {
            const fileInput = $('#id_file');
            const previewImg = $('#preview-img');
            const fileInfoBox = $('#file-info');
            const fileName = $('#file-name');
            const fileSize = $('#file-size');

            function resetImageUpload() {
                fileInput.val(null); // Clear file input
                previewImg.attr('src', '<?php echo $CFG->wwwroot . BASEURL . "/images/uploadimg.png"; ?>');
                fileName.text('');
                fileSize.text('');
                fileInfoBox.hide();
            }

            $('#id_cancel').on('click', function (e) {
                e.preventDefault();
                $('#mform1')[0].reset();
                resetImageUpload();
                $('#programModal').modal('hide');
                setTimeout(function () {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }, 300);
            });

            $('#programModal').on('hidden.bs.modal', function () {
                $('#mform1')[0].reset();
                resetImageUpload();
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });
        });
    });

    require(['jquery'], function ($) {
        $(document).ready(function () {
            const dropArea = $('#drop-area');
            const fileInput = $('#id_file');
            const previewImg = $('#preview-img');
            const fileInfoBox = $('#file-info');
            const fileName = $('#file-name');
            const fileSize = $('#file-size');
            const uploadStatus = $('#upload-status');
            const browseBtn = $('#browseBtn');

            // Click on "Browse" button opens file input
            browseBtn.on('click', function () {
                fileInput.trigger('click');
            });


            // Handle file selection
            function handleFile(file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please upload an image file (JPG, PNG, GIF)');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);

                const sizeInKB = (file.size / 1024).toFixed(2);
                fileName.text(file.name);
                fileSize.text(sizeInKB + ' KB');
                fileInfoBox.show();
            }

            fileInput.on('change', function (e) {
                const file = e.target.files[0];
                if (file) handleFile(file);
            });

            // Drag-and-drop
            dropArea.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropArea.addClass('dragover');
            });

            dropArea.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropArea.removeClass('dragover');
            });

            dropArea.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropArea.removeClass('dragover');
                const file = e.originalEvent.dataTransfer.files[0];
                if (file) {
                    fileInput[0].files = e.originalEvent.dataTransfer.files;
                    handleFile(file);
                }
            });
        });
    });


</script>