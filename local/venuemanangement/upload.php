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
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
//require_once('locallib.php');
require_once('venuemanangement_user_form.php');

$iid = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

core_php_time_limit::raise(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/venuemanangement/upload.php');
$PAGE->set_url('/local/venuemanangement/upload.php');
$PAGE->set_pagelayout('admin');
//admin_externalpage_setup('tooluploadvenuemanangementuser');
if (has_capability('local/venuemanangement:managevenue', $context) || (has_capability('local/venuemanangement:uploadvenue', $context))) {

    /**
     * Validation callback function - verified the column line of csv file.
     * Converts standard column names to lowercase.
     * @param csv_import_reader $cir
     * @param array $stdfields standard user fields
     * @param array $profilefields custom profile fields
     * @param moodle_url $returnurl return url in case of any error
     * @return array list of fields
     */
    function venuemanangement_uu_validate_user_upload_columns(csv_import_reader $cir, $stdfields, moodle_url $returnurl) {
        $columns = $cir->get_columns();

        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error', $returnurl);
        }
        if (count($columns) < 4) {
            $cir->close();
            $cir->cleanup();
            print_error('csvfewcolumns', 'error', $returnurl);
        }

        // test columns
        $processed = array();
        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $lcfield = core_text::strtolower($field);
            if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
                // standard fields are only lowercase
                $newfield = $lcfield;
            } else {
                $cir->close();
                $cir->cleanup();
                print_error('invalidfieldname', 'error', $returnurl, $field);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                print_error('duplicatefieldname', 'error', $returnurl, $newfield);
            }
            $processed[$key] = $newfield;
        }

        return $processed;
    }

    /**
     * Tracking of processed users.
     *
     * This class prints user information into a html table.
     *
     * @package    core
     * @subpackage admin
     * @copyright  2007 Petr Skoda  {@link http://skodak.org}
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class venuemanangement_uu_progress_tracker {

        private $_row;
        public $columns = array('line', 'venuemanangement', 'userid', 'issuedate', 'validupto', 'status');

        /**
         * Print table header.
         * @return void
         */
        public function start() {
            $ci = 0;
            echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="' . get_string('uploadusersresult', 'local_venuemanangement') . '">';
            echo '<tr class="heading r0">';
            echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('uucsvline', 'local_venuemanangement') . '</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">venuemanangement</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">userid</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">issuedate</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">validupto</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('status') . '</th>';
            echo '</tr>';
            $this->_row = null;
        }

        /**
         * Flush previous line and start a new one.
         * @return void
         */
        public function flush() {
            if (empty($this->_row) or empty($this->_row['line']['normal'])) {
                // Nothing to print - each line has to have at least number
                $this->_row = array();
                foreach ($this->columns as $col) {
                    $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
                }
                return;
            }
            $ci = 0;
            $ri = 1;
            echo '<tr class="r' . $ri . '">';
            foreach ($this->_row as $key => $field) {
                foreach ($field as $type => $content) {
                    if ($field[$type] !== '') {
                        $field[$type] = '<span class="uu' . $type . '">' . $field[$type] . '</span>';
                    } else {
                        unset($field[$type]);
                    }
                }
                echo '<td class="cell c' . $ci++ . '">';
                if (!empty($field)) {
                    echo implode('<br />', $field);
                } else {
                    echo '&nbsp;';
                }
                echo '</td>';
            }
            echo '</tr>';
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
        }

        /**
         * Add tracking info
         * @param string $col name of column
         * @param string $msg message
         * @param string $level 'normal', 'warning' or 'error'
         * @param bool $merge true means add as new line, false means override all previous text of the same type
         * @return void
         */
        public function track($col, $msg, $level = 'normal', $merge = true) {
            if (empty($this->_row)) {
                $this->flush(); //init arrays
            }
            if (!in_array($col, $this->columns)) {
                debugging('Incorrect column:' . $col);
                return;
            }
            if ($merge) {
                if ($this->_row[$col][$level] != '') {
                    $this->_row[$col][$level] .='<br />';
                }
                $this->_row[$col][$level] .= $msg;
            } else {
                $this->_row[$col][$level] = $msg;
            }
        }

        /**
         * Print the table end
         * @return void
         */
        public function close() {
            $this->flush();
            echo '</table>';
        }

    }

//require_capability('moodle/site:uploadusers', context_system::instance());
//
    $usersupdated = 0;
    $userserrors = 0;
    $errorstr = get_string('error');
    $stryes = get_string('yes');
    $strno = get_string('no');
    $stryesnooptions = array(0 => $strno, 1 => $stryes);

    $returnurl = new moodle_url('/local/venuemanangement/upload.php');
    $today = time();
    $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

// array of all valid fields for validation
    $STD_FIELDS = array('line', 'userid', 'venuemanangement', 'issuedate', 'validupto');

    if (empty($iid)) {
        $mform1 = new venuemanangement_admin_uploaduser_form1();

        if ($formdata = $mform1->get_data()) {
            $iid = csv_import_reader::get_new_iid('uploaduser');
            $cir = new csv_import_reader($iid, 'uploaduser');

            $content = $mform1->get_file_content('userfile');

            $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
            $csvloaderror = $cir->get_error();
            unset($content);

            if (!is_null($csvloaderror)) {
                print_error('csvloaderror', '', $returnurl, $csvloaderror);
            }
            // test if columns ok
            $filecolumns = venuemanangement_uu_validate_user_upload_columns($cir, $STD_FIELDS, $returnurl);
            // continue to form2
        } else {
            echo $OUTPUT->header();

            echo $OUTPUT->heading_with_help(get_string('uploadusers', 'local_venuemanangement'), 'uploadusers', 'local_venuemanangement');

            $mform1->display();
            echo $OUTPUT->footer();
            die;
        }
    } else {
        $cir = new csv_import_reader($iid, 'uploaduser');
        $filecolumns = venuemanangement_uu_validate_user_upload_columns($cir, $STD_FIELDS, $returnurl);
    }

    $mform2 = new venuemanangement_admin_uploaduser_form2(null, array('columns' => $filecolumns, 'data' => array('iid' => $iid, 'previewrows' => $previewrows)));

// If a file has been uploaded, then process it
    if ($formdata = $mform2->is_cancelled()) {
        $cir->cleanup(true);
        redirect($returnurl);
    } else if ($formdata = $mform2->get_data()) {
        // Print the header
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadusersresult', 'local_venuemanangement'));

        // init csv import helper
        $cir->init();
        $linenum = 1; //column header is first line
        // init upload progress tracker
        $upt = new venuemanangement_uu_progress_tracker();
        $upt->start(); // start table

        while ($line = $cir->next()) {
            $upt->flush();
            $linenum++;

            $upt->track('line', $linenum);

            $user = new stdClass();

            // add fields to user object
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $user->$key = trim($value);

                if (in_array($key, $upt->columns)) {
                    // default value in progress tracking table, can be changed later
                    $upt->track($key, s($value), 'normal');
                }
            }
            if ($existvenuemanangement = $DB->get_record('venuemanangements_mapping', array('venuemanangement' => $user->venuemanangement))) {
                //$upt->track('status', $existvenuemanangement->venuemanangement, 'normal', false); 
                $upt->track('status', 'Duplicate venuemanangement ' . $user->venuemanangement, 'error');
                $userserrors++;
                continue;
            }
            if ($existvenuemanangement) {
                $upt->track('status', 'Record does not updated successfully', 'error');
            }
            if ($existinguser = $DB->get_record('user', array('id' => $user->userid))) {
                $upt->track('status', $existinguser->id, 'normal', false);
                $usersupdated++;
            } else {
                $upt->track('status', 'Invalid userID ' . $user->userid, 'error');
                $userserrors++;
                continue;
            }

            // Write in db
            if ($existinguser) {
                $uploadvenuemanangement = new stdClass();
                //$uploadvenuemanangement->id = 0;
                $uploadvenuemanangement->venuemanangement = $user->venuemanangement;
                $uploadvenuemanangement->userid = $user->userid;
                $uploadvenuemanangement->issuedate = strtotime($user->issuedate);
                $uploadvenuemanangement->validupto = strtotime($user->validupto);
                $DB->insert_record('venuemanangements_mapping', $uploadvenuemanangement);
                // required script put here
                $upt->track('status', 'Record updated successfully', 'normal');
            } else {
                $upt->track('status', 'Record does not updated successfully', 'error');
            }
        }
        $upt->close(); // close table

        $cir->close();
        $cir->cleanup(true);

        echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
        echo '<p>';
        echo get_string('usersupdated', 'local_venuemanangement') . ': ' . $usersupdated . '<br />';
        echo get_string('errors', 'local_venuemanangement') . ': ' . $userserrors . '</p>';
        echo $OUTPUT->box_end();
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
        die;
    }

// Print the header
    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('uploaduserspreview', 'local_venuemanangement'));

// NOTE: this is JUST csv processing preview, we must not prevent import from here if there is something in the file!!
//       this was intended for tion of csv formatting and encoding, not filtering the data!!!!
//       we definitely must not process the whole file!
// preview table data
    $data = array();
    $cir->init();
    $linenum = 1; //column header is first line
    $noerror = true; // Keep status of any error.
    while ($linenum <= $previewrows and $fields = $cir->next()) {
        $linenum++;
        $rowcols = array();
        $rowcols['line'] = $linenum;
        foreach ($fields as $key => $field) {
            $rowcols[$filecolumns[$key]] = s(trim($field));
        }
        $rowcols['status'] = array();

        if (isset($rowcols['userid'])) {
            $stduserid = clean_param($rowcols['userid'], PARAM_INT);
            if ($rowcols['userid'] != $stduserid) {
                $rowcols['status'][] = 'invaliduseridupload';
            } else {
                $rowcols['status'][] = 'Valid ';
            }
        } else {
            $rowcols['status'][] = get_string('missingusername');
        }
        // Check if rowcols have custom profile field with correct data and update error state.
        $noerror = $noerror;
        $rowcols['status'] = implode('<br />', $rowcols['status']);
        $data[] = $rowcols;
    }
    if ($fields = $cir->next()) {
        $data[] = array_fill(0, count($fields) + 2, '...');
    }
    $cir->close();

    $table = new html_table();
    $table->id = "uupreview";
    $table->attributes['class'] = 'generaltable';
    $table->tablealign = 'center';
    $table->summary = get_string('uploaduserspreview', 'local_venuemanangement');
    $table->head = array();
    $table->data = $data;

    $table->head[] = get_string('uucsvline', 'local_venuemanangement');
    foreach ($filecolumns as $column) {
        $table->head[] = $column;
    }
    $table->head[] = get_string('status');

    echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));

// Print the form if valid values are available
    if ($noerror) {
        $mform2->display();
    }
} else {
    print_error('accessdenied', 'admin');
}
echo $OUTPUT->footer();
die;

