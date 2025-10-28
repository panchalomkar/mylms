<!DOCTYPE html>
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
 * Page for creating or editing course category name/parent/description.
 *
 * When called with an id parameter, edits the category with that id.
 * Otherwise it creates a new category with default parent from the parent
 * parameter, which may be 0.
 *
 * @package    core_course
 * @copyright  2007 Nicolas Connault
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('locallib.php');

require_login();
global $DB;
$title = get_string('assign', 'local_content_structure');

$context = context_system::instance(1);
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/content_structure/assign.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'local_content_structure'));
$PAGE->navbar->add('Home', new moodle_url('/my'));
$PAGE->navbar->add('Back', new moodle_url('/local/content_structure')); // Or whatever "back" means here
$PAGE->navbar->add($title, new moodle_url('/local/content_structure/index.php'));

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$localobj = new local_content_structure();
$catData = $localobj->multilevel_categories();

$id = optional_param('cid', 0, PARAM_INT);

function printTree($content)
{
    global $DB;
    static $count;
    $childrens = $DB->get_records('local_content_structure', array('parent' => $content->id));

    echo $html .= '<li role="treeitem" aria-expanded="false">';
    echo $html .= '<span>' . $content->name . '</span>';
    if ($childrens) {
        echo $html .= '<ul role="group">';
        foreach ($childrens as $record) {

            $count++;
            //get files
            $files = $DB->get_records('content_structure_files', array('contentid' => $record->id));
            foreach ($files as $file) {
                $html .= '<li role="treeitem" class="doc">' . $file->filename . '</li>';
            }
            printTree($record);
        }
    }
    for ($i = 0; $i < $count; $i++) {
        echo $html .= '</ul';
    }
    echo $html .= '<li>';

    //    $html .= '</ul>';
//    return $html;
}

function multilevel_categories($parent_id = 0)
{
    global $DB;

    $records = $DB->get_records('local_content_structure', array('parent' => $parent_id));
    $catData = [];
    if ($records) {

        foreach ($records as $record) {
            $catData[] = [
                'id' => $record->id,
                'parent' => $record->parent,
                'name' => $record->name,
                'image' => $record->image,
                'nested_categories' => multilevel_categories($record->id)
            ];
        }

        return $catData;
    } else {
        return $catData = [];
    }
}

function display_option($nested_categories, $temp = array(), $cids)
{
    global $DB, $CFG;

    $option = '';

    foreach ($nested_categories as $nested) {
        $checked = in_array($nested['id'], $cids) ? 'checked' : '';

        // Get file extension
        $filename = $nested['image']; // Example: "sample.pdf"
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Map extension to icon filename (must exist in /icons/ folder)
        switch ($ext) {
            case 'pdf':
                $icon = 'pdf';
                break;
            case 'mp4':
            case 'mov':
            case 'avi':
                $icon = 'video';
                break;
            case 'doc':
            case 'docx':
                $icon = 'word';
                break;
            case 'ppt':
            case 'pptx':
                $icon = 'ppt';
                break;
            case 'xls':
            case 'xlsx':
                $icon = 'excel';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                $icon = 'image';
                break;
            default:
                $icon = 'url';
                break;
        }

        // Use icon instead of original image
        $iconpath = $CFG->wwwroot . '/local/content_structure/images/media/icons/' . $icon . '.png';
        $image = '<img src="' . $iconpath . '" width="30" alt="' . $ext . '">';

        $option .= '<li class="list-group-item pl-1">';
        $option .= '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" class="check-input ml-1" id="' . $nested['id'] . '" name="ids[]" value="' . $nested['id'] . '" ' . $checked . '>';
        $option .= '<span>' . $image . ' ' . $nested['name'] . '</span>';

        if (!empty($nested['nested_categories'])) {
            $option .= '<ul class="nested list-group">';
            $option .= display_option($nested['nested_categories'], $temp, $cids);
            $option .= '</ul>';
        }

        $option .= '</li>';
    }

    return $option;
}

?>
<html>

<head>
    <link href="css/local.css" rel="stylesheet" type="text/css" />
    <!--<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.full.min.js"></script>

    <script>
        $(document).ready(function () {
            $.noConflict();
            $(function () {
                $("select").select2();
            });
            var toggler = document.getElementsByClassName("caret");
            var i;

            for (i = 0; i < toggler.length; i++) {

                toggler[i].addEventListener("click", function () {
                    this.parentElement.querySelector(".nested").classList.toggle("active");
                    this.classList.toggle("caret-down");
                });
            }

            //                $('body').on('keyup', '.search-query', function (e) {
            //                    var value = $.trim($('.search-query').val()).toLowerCase();
            ////                        var value = $(this).val().toLowerCase();
            //                    $(".list-group-item").filter(function () {
            //                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            //                    });
            //                });


            $('body').on('keyup', '.search-query', function (e) {
                var filter = $.trim($('.search-query').val()).toLowerCase();
                $("#myUL > li").each(function () {
                    if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                        $(this).hide();
                    } else {
                        $(this).show()
                        //                            $('.list-group-item').css('display', 'block');
                    }
                });
            });


            //                $('body').on('keyup', '.search-query', function (e) {
            //
            //                    // Search text
            //                    var text = $.trim($('.search-query').val());
            //
            //                    // Hide all content class element
            //                    $('.caret').hide();
            //                    $('.check-input').hide();
            //                    $('.mform ul li').hide();
            //
            //                    // Search 
            //                    $('.caret:contains("' + text + '")').closest('.caret').show();
            //                    $('.caret:contains("' + text + '")').closest('.check-input').show();
            //                    $('.caret:contains("' + text + '")').closest('.mform ul li').show();
            //
            //                });


            //                $.expr[":"].contains = $.expr.createPseudo(function (arg) {
            //                    return function (elem) {
            //                        return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
            //                    };
            //                });

            $('body').on('change', '#company', function () {
                var id = $('#company').val();
                window.location.href = 'assign.php?cid=' + id;
            })

        });
    </script>
    <style>
        ul,
        #myUL {
            list-style-type: none;
        }

        #myUL {
            margin: 0;
            padding: 0;
        }

        .caret {
            cursor: pointer;
            -webkit-user-select: none;
            /* Safari 3.1+ */
            -moz-user-select: none;
            /* Firefox 2+ */
            -ms-user-select: none;
            /* IE 10+ */
            user-select: none;
        }

        .caret::before {
            content: "\25B6";
            color: black;
            display: inline-block;
            margin-right: 6px;
        }

        .caret-down::before {
            -ms-transform: rotate(90deg);
            /* IE 9 */
            -webkit-transform: rotate(90deg);
            /* Safari */
            transform: rotate(90deg);
        }

        .nested {
            display: none;
        }

        .active {
            display: block;
        }

        .custom-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #003152;
            padding: 20px 30px;
            border-radius: 10px;
            color: #ec9707;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }

        .custom-popup i {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .custom-popup p {
            margin: 0;
            font-size: 18px;
        }
    </style>
</head>

<body>


    <?php
    if (isset($_POST['formsubmit']) && $_POST['formsubmit'] == 'Save') {
        if ($_POST['company'] > 0) {
            $ids = $_POST['ids'];
            $DB->delete_records('content_assign_company', array('companyid' => $_POST['company']));
            foreach ($ids as $contentid) {
                $obj = new stdClass();
                $obj->companyid = $_POST['company'];
                $obj->contentid = $contentid;
                $obj->timecreated = time();
                $DB->insert_record('content_assign_company', $obj);
            }

            // ✅ Wait until the DOM is ready
            echo "<script>
            window.addEventListener('load', function() {
                showSuccessPopup();
            });
        </script>";
        }
    }


    $cids = array();
    if ($id) {
        $records = $DB->get_records('content_assign_company', array('companyid' => $id));
        foreach ($records as $r) {
            $cids[] = $r->contentid;
        }
    }
    $catData = multilevel_categories();
    //        print_object($catData);
    $companies = $DB->get_records('company');

    echo '<form action="" method="POST" class="mform">';
    echo ' <div id="fitem_id_parent" class="form-group row  fitem   ">';
    echo ' <div class="col-md-6 form-inline align-items-start felement pln" data-fieldtype="select">
            <select class="custom-select" name="company" id="company" style="width: 100%;" required >
                <option value="0">' . get_string('selectcompany', 'local_content_structure') . '</option>';
    foreach ($companies as $company) {
        if ($id == $company->id) {
            echo ' <option value="' . $company->id . '" selected>' . $company->name . '</option>';
        } else {
            echo ' <option value="' . $company->id . '">' . $company->name . '</option>';
        }
    }
    echo '</select></div>';
    echo '<div class="input-search d-flex  ">
            <i class="input-search-icon fa fa-search" aria-hidden="true"></i>

            <input id="coursesearchbox" name="search" type="text" placeholder="' . get_string('searchprogram', 'local_content_structure') . '" value="" class="form-control h-40 search-query" style="width:100% !important;">

        </div></div>';
    //        echo  '<input type="text" class="input-medium search-query" placeholder="Search program" style="width: 35%;float: right;"></div>';
//        echo '<input type="text" class="input-medium search-query" placeholder="Search program" style="width: 35%;float: right;">';
    echo '<ul id="myUL" class="list-group">';
    foreach ($catData as $cat) {
        $checked = '';
        if (in_array($cat['id'], $cids)) {
            $checked = "checked";
        }
        $defaultimage = $CFG->wwwroot . '/local/content_structure/images/folderimg.png';

        // Full path to check existence
        $imagefile = $cat['image'];
        $imagepath = $CFG->dirroot . '/local/content_structure/images/media/' . $imagefile;

        // URL for image if it exists
        if (!empty($imagefile) && file_exists($imagepath)) {
            $imageurl = $CFG->wwwroot . '/local/content_structure/images/media/' . $imagefile;
        } else {
            $imageurl = $defaultimage;
        }


        $image = '<img src="' . $imageurl . '" style="width: 50px;height: 50px;">';
        echo '<li class="list-group-item"><input type="checkbox" class="check-input ml-0" id="' . $cat['id'] . '" name="ids[]" value="' . $cat['id'] . '" ' . $checked . '><span class="caret">' . $image . ' ' . $cat['name'] . '</span>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;';
        if (!empty($cat['nested_categories'])) {
            echo '<ul class="nested list-group">';
            echo display_option($cat['nested_categories'], array(), $cids);
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul>';

    echo '<input type="submit" class="btn btn-primary" name="formsubmit" value="' . get_string('save') . '">';
    echo '</form>';
    ?>
    <div id="successPopup" class="custom-popup" style="display: none;">
        <div class="popup-content">
            <i class="fas fa-check-circle"></i>
            <p class="text-light">Assign successfully</p>
        </div>
    </div>


</body>

</html>
<script src='js/jquery-3.0.0.js' type='text/javascript'></script>
<script>
    function showSuccessPopup() {
        const popup = document.getElementById('successPopup');
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 3000); // Hide after 3 seconds
    }
</script>

<?php
echo $OUTPUT->footer();
