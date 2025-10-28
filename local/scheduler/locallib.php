<?php

class scheduler {

    public function save_slot_booking($post) {
        global $DB, $USER;

        $bookedcount = $DB->count_records('scheduler_slot_book', array('sch_slotid' => $slot->id));
        $availablecount = $slot->max_user - $bookedcount;

        $return = false;

        foreach ($post['booking'] as $row) {
            $slot = explode('@@', $row);
            $time = explode(' - ', $slot[1]);

            //check if slot already booked
            if (!$DB->record_exists('scheduler_slot_book', array('userid' => $USER->id, 'sch_slotid' => $slot[0]))) {

                $object = new stdClass();

                $object->userid = $USER->id;
                $object->sch_slotid = $slot[0];
                $object->u_timezone = usertimezone();
                $object->slot_start = $time[0];
                $object->slot_end = $time[1];
                $object->timecreated = time();

                if ($DB->insert_record('scheduler_slot_book', $object)) {
                    $return = true;
                }
            }
        }

        return $return;
    }

    public function converToTz($time = "", $toTz = '', $fromTz = '') {
        // timezone by php friendly values
        $date = new DateTime($time, new DateTimeZone($fromTz));
        $date->setTimezone(new DateTimeZone($toTz));
        $time = $date->format('H:i');
        return $time;
    }

    public function converToTzDate($time = "", $toTz = '', $fromTz = '') {
        // timezone by php friendly values
        $date = new DateTime($time, new DateTimeZone($fromTz));
        $date->setTimezone(new DateTimeZone($toTz));
        $time = $date->format('Y-m-d');
        return $time;
    }

    public function save_own_slots($post) {
        global $DB, $USER;
        $i = 0;
        $usertimezone = usertimezone();
        $failed = false;

        $transaction = $DB->start_delegated_transaction();
        foreach ($post['course'] as $data) {
            $st = $post['starttime'][$i];
            $et = $post['endtime'][$i];
            $date = $post['dates'][$i];
            $SQL = "SELECT * FROM mdl_scheduler_slot_book
                    WHERE slot_date = '$date' AND own_start < '$et' and own_end > '$st'";

            if (!$DB->record_exists_sql($SQL)) {
                $object->userid = $USER->id;
                $object->courseid = $post['course'][$i];
                $object->u_timezone = $usertimezone;
                $object->slot_date = $post['dates'][$i];
                $object->own_start = $post['starttime'][$i];
                $object->own_end = $post['endtime'][$i];
                $object->timecreated = time();
//                print_object($object);die;
                if ($object->courseid != '' && $object->slot_date != '' && $object->own_start != '' && $object->own_end != '') {
                    $DB->insert_record('scheduler_slot_book', $object);
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

}
