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

$title = get_string('display', 'local_content_structure');

$context = context_system::instance(1);
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/content_structure/display.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'local_content_structure'));

$PAGE->navbar->add(get_string('title', 'local_content_structure'), new moodle_url('index.php'));

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$localobj = new local_content_structure();
?>
<html>
    <head>
        <link rel="stylesheet" href="css/tree.css">
        <script src="js/tree.js"></script>
        <script src="js/treeitem.js"></script>
    </head>
    <body>

        <h3 id="tree_label">
            File Viewer
        </h3>
        <ul role="tree" aria-labelledby="tree_label">
            <li role="treeitem" aria-expanded="false">
                <span>
                    Projects
                </span>
                <ul role="group">
                    <li role="treeitem" class="doc">
                        project-1.docx
                    </li>
                    <li role="treeitem" class="doc">
                        project-2.docx
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            Project 3
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                project-3A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-3B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-3C.docx
                            </li>
                        </ul>
                    </li>
                    <li role="treeitem" class="doc">
                        project-4.docx
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            Project 5
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                project-5A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-5B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-5C.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-5D.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-5E.docx
                            </li>
                            <li role="treeitem" class="doc">
                                project-5F.docx
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li role="treeitem" aria-expanded="false">
                <span>
                    Reports
                </span>
                <ul role="group">
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            report-1
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                report-1A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-1B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-1C.docx
                            </li>
                        </ul>
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            report-2
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                report-2A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-2B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-2C.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-2D.docx
                            </li>
                        </ul>
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            report-3
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                report-3A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-3B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-3C.docx
                            </li>
                            <li role="treeitem" class="doc">
                                report-3D.docx
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li role="treeitem" aria-expanded="false">
                <span>
                    Letters
                </span>
                <ul role="group">
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            letter-1
                        </span>
                        <ul>
                            <li role="treeitem" class="doc">
                                letter-1A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-1B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-1C.docx
                            </li>
                        </ul>
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            letter-2
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                letter-2A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-2B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-2C.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-2D.docx
                            </li>
                        </ul>
                    </li>
                    <li role="treeitem" aria-expanded="false">
                        <span>
                            letter-3
                        </span>
                        <ul role="group">
                            <li role="treeitem" class="doc">
                                letter-3A.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-3B.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-3C.docx
                            </li>
                            <li role="treeitem" class="doc">
                                letter-3D.docx
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </body>
</html>

<?php
echo $OUTPUT->footer();
