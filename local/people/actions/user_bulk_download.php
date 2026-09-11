<?php
/**
 * Script for downloading user lists (Production Ready)
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
require_sesskey(); // ✅ security

require_capability('moodle/user:update', context_system::instance());

global $SESSION, $DB;

// ✅ Get format safely
$format = optional_param('format', 'xls', PARAM_ALPHA);
$allowedformats = ['csv', 'xls', 'ods'];
if (!in_array($format, $allowedformats)) {
    $format = 'xls';
}

// ✅ 1. FIRST PRIORITY → GET userids (BEST METHOD)
$userids_param = optional_param('userids', '', PARAM_TEXT);

if (!empty($userids_param)) {
    $SESSION->bulk_users = array_filter(array_map('intval', explode(',', $userids_param)));
}

// ✅ 2. SECOND → Handle AJAX bulk_action JSON
$bulkjson = optional_param('bulk_action', '', PARAM_RAW);
if (!empty($bulkjson)) {
    $bulk = json_decode($bulkjson, true);

    if (!empty($bulk['data']) && is_array($bulk['data'])) {
        $SESSION->bulk_users = array_map('intval', $bulk['data']);
    }
}

// ✅ Validate session users
if (empty($SESSION->bulk_users) || !is_array($SESSION->bulk_users)) {
    $returnurl = !empty($SESSION->return) ? $SESSION->return : new moodle_url('/local/people/index.php');
    redirect($returnurl, 'No users selected for export', 2);
}

// ✅ Define fields
$fields = [
    'id'          => 'id',
    'username'    => 'username',
    'email'       => 'email',
    'firstname'   => 'firstname',
    'lastname'    => 'lastname',
    'idnumber'    => 'idnumber',
    'institution' => 'institution',
    'department'  => 'department',
    'phone1'      => 'phone1',
    'phone2'      => 'phone2',
    'city'        => 'city',
    'url'         => 'url',
    'icq'         => 'icq',
    'skype'       => 'skype',
    'aim'         => 'aim',
    'yahoo'       => 'yahoo',
    'msn'         => 'msn',
    'country'     => 'country',
    'suspended'   => 'suspended'
];

// ✅ Add custom profile fields
if ($extrafields = $DB->get_records('user_info_field')) {
    foreach ($extrafields as $v) {
        $fields['profile_field_' . $v->shortname] = 'profile_field_' . $v->shortname;
    }
}

// 🚀 NO OUTPUT BEFORE THIS POINT

switch ($format) {
    case 'csv':
        user_download_csv($fields);
        break;

    case 'ods':
        user_download_ods($fields);
        break;

    case 'xls':
    default:
        user_download_xls($fields);
        break;
}

exit;

/* ================= FUNCTIONS ================= */

function user_download_xls($fields) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/excellib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users') . '.xls');

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = $workbook->add_worksheet('');

    // Header
    $col = 0;
    foreach ($fields as $fieldname) {
        $worksheet->write(0, $col++, $fieldname);
    }

    // Data
    $row = 1;
    foreach ($SESSION->bulk_users as $userid) {
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }

        profile_load_data($user);

        $col = 0;
        foreach ($fields as $field => $unused) {
            $worksheet->write($row, $col++, $user->$field ?? '');
        }
        $row++;
    }

    $workbook->close();
    exit;
}

function user_download_ods($fields) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/odslib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users') . '.ods');

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = $workbook->add_worksheet('');

    // Header
    $col = 0;
    foreach ($fields as $fieldname) {
        $worksheet->write(0, $col++, $fieldname);
    }

    // Data
    $row = 1;
    foreach ($SESSION->bulk_users as $userid) {
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }

        profile_load_data($user);

        $col = 0;
        foreach ($fields as $field => $unused) {
            $worksheet->write($row, $col++, $user->$field ?? '');
        }
        $row++;
    }

    $workbook->close();
    exit;
}

function user_download_csv($fields) {
    global $CFG, $SESSION, $DB;

    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->libdir.'/csvlib.class.php');

    $filename = clean_filename(get_string('users'));

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    // Header
    $csvexport->add_data(array_values($fields));

    foreach ($SESSION->bulk_users as $userid) {
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            continue;
        }

        profile_load_data($user);

        $row = [];
        foreach ($fields as $field => $unused) {
            $value = $user->$field ?? '';
            if (is_array($value)) {
                $value = reset($value);
            }
            $row[] = $value;
        }

        $csvexport->add_data($row);
    }

    $csvexport->download_file();
    exit;
}