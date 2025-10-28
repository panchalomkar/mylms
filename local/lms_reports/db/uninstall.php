<?php
defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/local/datahub/lib.php');

function xmldb_local_lms_reports_uninstall() {
    global $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // Delete records
    $DB->delete_records('local_lms_reports');
    $DB->delete_records('local_lms_report_type'); 
}
