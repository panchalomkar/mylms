<?php
require_once '../../../config.php';
require_once '../locallib.php';
global $DB;
require_login();

$heading = get_string('pluginname', LANGFILE);
$context = context_system::instance();
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . BASEURL . '/gallery/index.php');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$contentid = optional_param('cid', 0, PARAM_INT);

echo $OUTPUT->header();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Create treeview with jsTree plugin and PHP</title>

        <link rel="stylesheet" type="text/css" href="jstree/dist/themes/default/style.min.css">
        <script src="jquery-3.4.1.min.js"></script>

        <script type="text/javascript" src="jstree/dist/jstree.min.js"></script>
    </head>


    <body>
        <?php
        $folderData = $DB->get_records('local_content_structure');

        $folders_arr = array();
        foreach ($folderData as $row) {
            $parentid = $row->parent;
            if ($parentid == '0')
                $parentid = "#";

            $selected = false;
            $opened = false;
            if ($row->id == 2) {
                $selected = true;
                $opened = true;
            }
            $folders_arr[] = array(
                "id" => $row->id,
                "parent" => $parentid,
                "text" => $row->name . ' <input type="checkbox" id="' . $row->id . '" name="ids[]" value="' . $row->id . '">',
                "state" => array(
                    "selected" => $selected,
                    "opened" => $opened
                )
            );

            $files = $DB->get_records('content_structure_files', array('contentid' => $row->id));
            foreach ($files as $file) {


                $folders_arr[] = array(
                    "id" => $row->id,
                    "parent" => $parentid,
                    "text" => $file->filename,
                    "state" => array(
                        "selected" => $selected,
                        "opened" => $opened
                    )
                );
            }
        }
        ?>

        <!-- Initialize jsTree -->
        <div id="folder_jstree"></div>

        <!-- Store folder list in JSON format -->
        <textarea id='txt_folderjsondata'><?= json_encode($folders_arr) ?></textarea>

        <!-- Script -->
        <script type="text/javascript">
            $(document).ready(function () {
                var folder_jsondata = JSON.parse($('#txt_folderjsondata').val());

                $('#folder_jstree').jstree({'core': {
                        'data': folder_jsondata,
                        'multiple': false
                    }, });

            });
        </script>
    </body>
</html>