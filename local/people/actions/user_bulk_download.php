<?php
/**
* script for downloading of user lists
*/

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$format = optional_param('format', '', PARAM_ALPHA);


/* XLS as default format to download
* @author : Jorge Botero H.
* @since Jul 21 of 2016
* @paradiso
*/
    
$format = 'xls';

require_login();

/* Remove because that next line generate html code break the header for export
* @author : Andres Ag.
* @since August 11 of 2016
* @paradiso
*/
//admin_externalpage_setup('userbulk');
require_capability('moodle/user:update', context_system::instance());

$return = new moodle_url($SESSION->return);

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if ($format) {
    $fields = array('id'        => 'id',
                    'username'  => 'username',
                    'email'     => 'email',
                    'firstname' => 'firstname',
                    'lastname'  => 'lastname',
                    'idnumber'  => 'idnumber',
                    'institution' => 'institution',
                    'department' => 'department',
                    'phone1'    => 'phone1',
                    'phone2'    => 'phone2',
                    'city'      => 'city',
                    'url'       => 'url',
                    'icq'       => 'icq',
                    'skype'     => 'skype',
                    'aim'       => 'aim',
                    'yahoo'     => 'yahoo',
                    'msn'       => 'msn',
                    'country'   => 'country',
                    'suspended' => 'suspended'
                );

    if ($extrafields = $DB->get_records('user_info_field')) {
        foreach ($extrafields as $n=>$v){
            $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
        }
    }

    switch ($format) {
        case 'csv' : user_download_csv($fields);
        case 'ods' : user_download_ods($fields);
        case 'xls' : user_download_xls($fields);

    }
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('export_user_description', 'local_people'));

echo $OUTPUT->box_start();

echo $OUTPUT->render_from_template('local_people/user_bulk_download',[]);
echo $OUTPUT->box_end();

echo $OUTPUT->continue_button($return);

echo $OUTPUT->footer();

function user_download_ods($fields) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/odslib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.ods');

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

    $worksheet[0] = $workbook->add_worksheet('');
    $col = 0;
    foreach ($fields as $fieldname) {
        $worksheet[0]->write(0, $col, $fieldname);
        $col++;
    }

    $row = 1;
    foreach ($SESSION->bulk_users as $userid) {
        if (!$user = $DB->get_record('user', array('id'=>$userid))) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field=>$unused) {
            $worksheet[0]->write($row, $col, $user->$field);
            $col++;
        }
        $row++;
    }

    $workbook->close();
    die;
}

function user_download_xls($fields) {
    global $CFG, $SESSION, $DB;

    require_once("$CFG->libdir/excellib.class.php");
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $filename = clean_filename(get_string('users').'.xls');

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

    $worksheet[0] = $workbook->add_worksheet('');
    $col = 0;
    foreach ($fields as $fieldname) {
        $worksheet[0]->write(0, $col, $fieldname);
        $col++;
    }

    $row = 1;
    foreach ($SESSION->bulk_users as $userid) {
        if (!$user = $DB->get_record('user', array('id'=>$userid))) {
            continue;
        }
        $col = 0;
        profile_load_data($user);
        foreach ($fields as $field=>$unused) {
            $worksheet[0]->write($row, $col, $user->$field);
            $col++;
        }
        $row++;
    }

    $workbook->close();
    die;
}

function user_download_csv($fields) {
    global $CFG, $SESSION, $DB;

    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = clean_filename(get_string('users'));

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);

    foreach ($SESSION->bulk_users as $userid) {
        $row = array();
        if (!$user = $DB->get_record('user', array('id'=>$userid))) {
            continue;
        }
        profile_load_data($user);
        $userprofiledata = array();
        foreach ($fields as $field=>$unused) {
            // Custom user profile textarea fields come in an array
            // The first element is the text and the second is the format.
            // We only take the text.
            if (is_array($user->$field)) {
                $userprofiledata[] = reset($user->$field);
            } else {
                $userprofiledata[] = $user->$field;
            }
        }
        $csvexport->add_data($userprofiledata);
    }
    $csvexport->download_file();
    die;
}
