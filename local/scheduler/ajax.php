<?php

require_once('../../config.php');

global $DB, $USER;
require_login();
$action = optional_param('action', '', PARAM_TEXT);

switch ($action) {
    case 'get_courses_option':
        $id = optional_param('id', 0, PARAM_INT);
        $SQL = "SELECT c.*
                    FROM glms_user u
                    INNER JOIN glms_role_assignments ra ON ra.userid = u.id
                    INNER JOIN glms_context ct ON ct.id = ra.contextid
                    INNER JOIN glms_course c ON c.id = ct.instanceid
                    INNER JOIN glms_role r ON r.id = ra.roleid
                    WHERE r.shortname IN ('teacher', 'editingteacher') AND ra.userid = $USER->id AND c.visible = 1";

        $courses = $DB->get_records_sql($SQL);
        $option = '<option value="">Select Course</option>';
        foreach ($courses as $course) {
            $option .= '<option value="' . $course->id . '">' . $course->fullname . '</option>';
        }

        echo json_encode(array($id, $option), true);
        break;

    case 'save_data':
        echo ad_save($_POST);
        break;

    case 'getparticipant':
        $schid = optional_param('schid', 0, PARAM_INT);

        $SQL = "SELECT u.* FROM {user} u INNER JOIN {scheduler_slot_book} b ON b.userid = u.id WHERE sch_slotid = $schid";
        $users = $DB->get_records_sql($SQL);

        $res = '<ol>';
        foreach ($users as $user) {
            $res .= '<li>' . $user->firstname . ' ' . $user->lastname . '</li>';
        }
        echo $res .= '</ol>';
        break;


    default:
        break;
}

function ad_save($post) {
    global $DB, $USER;
    $usertimezone = usertimezone();
    $i = 0;
    $failed = false;

    $transaction = $DB->start_delegated_transaction();

    foreach ($post['course'] as $data) {
        $st = $post['starttime'][$i];
        $et = $post['endtime'][$i];
        $date = $post['dates'][$i];

        $SQL = "SELECT * FROM glms_scheduler_slot
                    WHERE slot_date = '$date' AND slot_start < '$et' and slot_end > '$st'";

        if (!$DB->record_exists_sql($SQL)) {
            $object = new stdClass();

            $object->userid = $USER->id;
            $object->courseid = $post['course'][$i];
            $object->u_timezone = $usertimezone;
            $object->slot_date = $post['dates'][$i];
            $object->slot_start = $post['starttime'][$i];
            $object->slot_end = $post['endtime'][$i];
            $object->max_user = $post['max_user'][$i];
            $object->timecreated = time();
            if ($object->courseid != '' && $object->slot_date != '' && $object->slot_start != '' && $object->slot_end != '' && $object->max_user != '') {
                $DB->insert_record('scheduler_slot', $object);
            }
            $i++;
        } else {
            $failed = true;
            break;
        }
    }
    if ($failed) {
        echo 'Make sure the slot should not be confict and blank';
        try {
            $a = 1 / 0;
        } catch (Exception $e) {

            $transaction->rollback($e);
        }
    } else {
        $transaction->allow_commit();
        return '1';
    }
}
