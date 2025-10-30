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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage ilt
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Sends message to administrator listing all updated
 * duplicate custom fields
 * @param array $data
 */
function ilt_send_admin_upgrade_msg($data) {
    global $SITE;

    // No data - no need to send email.
    if (empty($data)) {
        return;
    }

    $table = new html_table();
    $table->head = array('Custom field ID',
                         'Custom field original shortname',
                         'Custom field new shortname');
    $table->data = $data;
    $table->align = array ('center', 'center', 'center');

    $title    = "$SITE->fullname: Face to Face upgrade info";
    $note = 'During the last site upgrade the face-to-face module has been modified. It now
requires session custom fields to have unique shortnames. Since some of your
custom fields had duplicate shortnames, they have been renamed to remove
duplicates (see table below). This could impact on your email messages if you
reference those custom fields in the message templates.';

    $message  = html_writer::start_tag('html');
    $message .= html_writer::start_tag('head') . html_writer::tag('title', $title) . html_writer::end_tag('head');
    $message .= html_writer::start_tag('body');
    $message .= html_writer::tag('p', $note) . html_writer::table($table, true);
    $message .= html_writer::end_tag('body');
    $message .= html_writer::end_tag('html');

    $admin = get_admin();

    email_to_user($admin,
                  $admin,
                  $title,
                  '',
                  $message);

}

function xmldb_ilt_upgrade($oldversion=0) {
    global $CFG, $USER, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    require_once($CFG->dirroot . '/mod/ilt/lib.php');

    $result = true;

    if ($result && $oldversion < 2008050500) {
        $table = new xmldb_table('ilt');
        $field = new xmldb_field('thirdpartywaitlist');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'thirdparty');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2008061000) {
        $table = new xmldb_table('ilt_submissions');
        $field = new xmldb_field('notificationtype');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timemodified');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2008080100) {
        echo $OUTPUT->notification(get_string('upgradeprocessinggrades', 'ilt'), 'notifysuccess');
        require_once($CFG->dirroot . '/mod/ilt/lib.php');

        $transaction = $DB->start_delegated_transaction();
        $DB->debug = false; // Too much debug output.

        // Migrate the grades to the gradebook.
        $sql = "SELECT f.id, f.name, f.course, s.grade, s.timegraded, s.userid,
            cm.idnumber as cmidnumber
            FROM {ilt_submissions} s
            JOIN {ilt} f ON s.ilt = f.id
            JOIN {course_modules} cm ON cm.instance = f.id
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name='ilt'";
        if ($rs = $DB->get_recordset_sql($sql)) {
            foreach ($rs as $ilt) {
                $grade = new stdclass();
                $grade->userid = $ilt->userid;
                $grade->rawgrade = $ilt->grade;
                $grade->rawgrademin = 0;
                $grade->rawgrademax = 100;
                $grade->timecreated = $ilt->timegraded;
                $grade->timemodified = $ilt->timegraded;

                $result = $result && (GRADE_UPDATE_OK == ilt_grade_item_update($ilt, $grade));
            }
            $rs->close();
        }
        $DB->debug = true;

        // Remove the grade and timegraded fields from ilt_submissions.
        if ($result) {
            $table = new xmldb_table('ilt_submissions');
            $field1 = new xmldb_field('grade');
            $field2 = new xmldb_field('timegraded');
            $result = $result && $dbman->drop_field($table, $field1, false, true);
            $result = $result && $dbman->drop_field($table, $field2, false, true);
        }

        $transaction->allow_commit();
    }

    if ($result && $oldversion < 2008090800) {

        // Define field timemodified to be added to ilt_submissions.
        $table = new xmldb_table('ilt_submissions');
        $field = new xmldb_field('timecancelled');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'timemodified');

        // Launch add field.
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2009111300) {

        // New fields necessary for the training calendar.
        $table = new xmldb_table('ilt');
        $field1 = new xmldb_field('shortname');
        $field1->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'timemodified');
        $result = $result && $dbman->add_field($table, $field1);

        $field2 = new xmldb_field('description');
        $field2->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'shortname');
        $result = $result && $dbman->add_field($table, $field2);

        $field3 = new xmldb_field('showoncalendar');
        $field3->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'description');
        $result = $result && $dbman->add_field($table, $field3);
    }

    if ($result && $oldversion < 2009111600) {

        $table1 = new xmldb_table('ilt_session_field');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('type', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table1->add_field('possiblevalues', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table1->add_field('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table1->add_field('defaultvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('isfilter', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table1->add_field('showinsummary', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table1);

        $table2 = new xmldb_table('ilt_session_data');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table2->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table2->add_field('data', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table2);
    }

    if ($result && $oldversion < 2009111900) {

        // Remove unused field.
        $table = new xmldb_table('ilt_sessions');
        $field = new xmldb_field('closed');
        $result = $result && $dbman->drop_field($table, $field);
    }    
    
    // Migration of old Location, Venue and Room fields.
    if ($result && $oldversion < 2009112300) {

        // Create three new custom fields.
        $newfield1 = new stdClass();
        $newfield1->name = 'Location';
        $newfield1->shortname = 'location';
        $newfield1->type = 0; // Free text.
        $newfield1->required = 1;
        if (!$locationfieldid = $DB->insert_record('ilt_session_field', $newfield1)) {
            $result = false;
        }

        $newfield2 = new stdClass();
        $newfield2->name = 'Venue';
        $newfield2->shortname = 'venue';
        $newfield2->type = 0; // Free text.
        $newfield2->required = 1;
        if (!$venuefieldid = $DB->insert_record('ilt_session_field', $newfield2)) {
            $result = false;
        }

        $newfield3 = new stdClass();
        $newfield3->name = 'Room';
        $newfield3->shortname = 'room';
        $newfield3->type = 0; // Free text.
        $newfield3->required = 1;
        $newfield3->showinsummary = 0;
        if (!$roomfieldid = $DB->insert_record('ilt_session_field', $newfield3)) {
            $result = false;
        }

        // Migrate data into the new fields.
        $olddebug = $DB->debug;
        $DB->debug = false; // Too much debug output.

        if ($rs = $DB->get_recordset('ilt_sessions', array(), '', 'id, location, venue, room')) {
            foreach ($rs as $session) {
                $locationdata = new stdClass();
                $locationdata->sessionid = $session->id;
                $locationdata->fieldid = $locationfieldid;
                $locationdata->data = $session->location;
                $result = $result && $DB->insert_record('ilt_session_data', $locationdata);

                $venuedata = new stdClass();
                $venuedata->sessionid = $session->id;
                $venuedata->fieldid = $venuefieldid;
                $venuedata->data = $session->venue;
                $result = $result && $DB->insert_record('ilt_session_data', $venuedata);

                $roomdata = new stdClass();
                $roomdata->sessionid = $session->id;
                $roomdata->fieldid = $roomfieldid;
                $roomdata->data = $session->room;
                $result = $result && $DB->insert_record('ilt_session_data', $roomdata);
            }
            $rs->close();
        }

        $DB->debug = $olddebug;

        // Drop the old fields.
        $table = new xmldb_table('ilt_sessions');
        $oldfield1 = new xmldb_field('location');
        $result = $result && $dbman->drop_field($table, $oldfield1);
        $oldfield2 = new xmldb_field('venue');
        $result = $result && $dbman->drop_field($table, $oldfield2);
        $oldfield3 = new xmldb_field('room');
        $result = $result && $dbman->drop_field($table, $oldfield3);
    }

    // Migration of old Location, Venue and Room placeholders in email templates.
    if ($result && $oldversion < 2009112400) {
        $transaction = $DB->start_delegated_transaction();

        $olddebug = $DB->debug;
        $DB->debug = false; // Too much debug output.

        $templatedfields = array('confirmationsubject', 'confirmationinstrmngr', 'confirmationmessage',
            'cancellationsubject', 'cancellationinstrmngr', 'cancellationmessage',
            'remindersubject', 'reminderinstrmngr', 'remindermessage',
            'waitlistedsubject', 'waitlistedmessage');

        if ($rs = $DB->get_recordset('ilt', array(), '', 'id, ' . implode(', ', $templatedfields))) {
            foreach ($rs as $activity) {
                $todb = new stdClass();
                $todb->id = $activity->id;

                foreach ($templatedfields as $fieldname) {
                    $s = $activity->$fieldname;
                    $s = str_replace('[location]', '[session:location]', $s);
                    $s = str_replace('[venue]', '[session:venue]', $s);
                    $s = str_replace('[room]', '[session:room]', $s);
                    $todb->$fieldname = $s;
                }

                $result = $result && $DB->update_record('ilt', $todb);
            }
            $rs->close();
        }
        $DB->debug = $olddebug;
        $transaction->allow_commit();
    }

    if ($result && $oldversion < 2009120900) {

        // Create Calendar events for all existing Face-to-face sessions.
        try {
            $transaction = $DB->start_delegated_transaction();
            if ($records = $DB->get_records('ilt_sessions', '', '', '', 'id, ilt')) {

                // Remove all exising site-wide events (there shouldn't be any).
                foreach ($records as $record) {
                    if (!ilt_remove_session_from_calendar($record, SITEID)) {
                        $result = false;
                        throw new Exception('Could not remove session from site calendar');
                        break;
                    }
                }

                // Add new site-wide events.
                foreach ($records as $record) {
                    $session = ilt_get_session($record->id);
                    $ilt = $DB->get_record('ilt', 'id', $record->ilt);

                    if (!ilt_add_session_to_calendar($session, $ilt, 'site')) {
                        $result = false;
                        throw new Exception('Could not add session to site calendar');
                        break;
                    }
                }
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    if ($result && $oldversion < 2009122901) {

        // Create table ilt_session_roles.
        $table = new xmldb_table('ilt_session_roles');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'ilt_sessions', array('id'));
        $result = $result && $dbman->create_table($table);

        // Create table ilt_signups.
        $table = new xmldb_table('ilt_signups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('mailedreminder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('discountcode', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('notificationtype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'ilt_sessions', array('id'));
        $result = $result && $dbman->create_table($table);

        // Create table ilt_signups_status.
        $table = new xmldb_table('ilt_signups_status');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('signupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('statuscode', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('superceded', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, '0');
        $table->add_field('note', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('signupid', XMLDB_KEY_FOREIGN, array('signupid'), 'ilt_signups', array('id'));
        $result = $result && $dbman->create_table($table);

        // Migrate submissions to signups.
        $table = new xmldb_table('ilt_submissions');
        if ($dbman->table_exists($table)) {
            require_once($CFG->dirroot . '/mod/ilt/lib.php');

            $transaction = $DB->start_delegated_transaction();

            // Get all submissions and loop through.
            $rs = $DB->get_recordset('ilt_submissions');

            foreach ($rs as $submission) {

                // Insert signup.
                $signup = new stdClass();
                $signup->sessionid = $submission->sessionid;
                $signup->userid = $submission->userid;
                $signup->mailedreminder = $submission->mailedreminder;
                $signup->discountcode = $submission->discountcode;
                $signup->notificationtype = $submission->notificationtype;

                $id = $DB->insert_record('ilt_signups', $signup);

                $signup->id = $id;

                // Check ilt still exists (some of them are missing).
                // Also, we need the course id so we can load the grade.
                $ilt = $DB->get_record('ilt', 'id', $submission->ilt);
                if (!$ilt) {

                    // If ilt delete, ignore as it's of no use to us now.
                    mtrace('Could not find ilt instance '.$submission->ilt);
                    continue;
                }

                // Get grade.
                $grade = ilt_get_grade($submission->userid, $ilt->course, $ilt->id);

                // Create initial "booked" signup status.
                $status = new stdClass();
                $status->signupid = $signup->id;
                $status->statuscode = MDL_ILT_STATUS_BOOKED;
                $status->superceded = ($grade->grade > 0 || $submission->timecancelled) ? 1 : 0;
                $status->createdby = $USER->id;
                $status->timecreated = $submission->timecreated;
                $status->mailed = 0;

                $DB->insert_record('ilt_signups_status', $status);

                // Create attended signup status.
                if ($grade->grade > 0) {
                    $status->statuscode = MDL_ILT_STATUS_FULLY_ATTENDED;
                    $status->grade = $grade->grade;
                    $status->timecreated = $grade->dategraded;
                    $status->superceded = $submission->timecancelled ? 1 : 0;

                    $DB->insert_record('ilt_signups_status', $status);
                }

                // If cancelled, create status.
                if ($submission->timecancelled) {
                    $status->statuscode = MDL_ILT_STATUS_USER_CANCELLED;
                    $status->timecreated = $submission->timecancelled;
                    $status->superceded = 0;

                    $DB->insert_record('ilt_signups_status', $status);
                }
            }

            $rs->close();
            $transaction->allow_commit();

            // Drop table ilt_submissions.
            $table = new xmldb_table('ilt_submissions');
            $result = $result && $dbman->drop_table($table);
        }

        // New field necessary for overbooking.
        $table = new xmldb_table('ilt_sessions');
        $field1 = new xmldb_field('allowoverbook');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'capacity');
        $result = $result && $dbman->add_field($table, $field1);
    }

    if ($result && $oldversion < 2010012000) {

        // New field for storing recommendations/advice.
        $table = new xmldb_table('ilt_signups_status');
        $field1 = new xmldb_field('advice');
        $field1->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null);
        $result = $result && $dbman->add_field($table, $field1);
    }

    if ($result && $oldversion < 2010012001) {

        // New field for storing manager approval requirement.
        $table = new xmldb_table('ilt');
        $field = new xmldb_field('approvalreqd');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'showoncalendar');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2010012700) {

        // New fields for storing request emails.
        $table = new xmldb_table('ilt');
        $field = new xmldb_field('requestsubject');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'reminderperiod');
        $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('requestinstrmngr');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'requestsubject');
        $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('requestmessage');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'requestinstrmngr');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2010051000) {

        // Create Calendar events for all existing Face-to-face sessions.
        $transaction = $DB->start_delegated_transaction();

        if ($records = $DB->get_records('ilt_sessions', '', '', '', 'id, ilt')) {

            // Remove all exising site-wide events (there shouldn't be any).
            foreach ($records as $record) {
                ilt_remove_session_from_calendar($record, SITEID);
            }

            // Add new site-wide events.
            foreach ($records as $record) {
                $session = ilt_get_session($record->id);
                $ilt = $DB->get_record('ilt', 'id', $record->ilt);

                ilt_add_session_to_calendar($session, $ilt, 'site');
            }
        }

        $transaction->allow_commit();

        // Add tables required for site notices.
        $table1 = new xmldb_table('ilt_notice');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('text', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table1);

        $table2 = new xmldb_table('ilt_notice_data');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table2->add_field('noticeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table2->add_field('data', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table2->add_index('ilt_notice_date_fieldid', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        $result = $result && $dbman->create_table($table2);
    }

    if ($result && $oldversion < 2010100400) {

        // Remove unused mailed field.
        $table = new xmldb_table('ilt_signups_status');
        $field = new xmldb_field('mailed');
        if ($dbman->field_exists($table, $field)) {
            $result = $result && $dbman->drop_field($table, $field, false, true);
        }
    }

    // 2.0 upgrade line.
    if ($oldversion < 2011120701) {

        // Update existing select fields to use new seperator.
        $badrows = $DB->get_records_sql(
            "
                SELECT
                    *
                FROM
                    {ilt_session_field}
                WHERE
                    possiblevalues LIKE '%;%'
                AND possiblevalues NOT LIKE '%" . ILT_CUSTOMFIELD_DELIMITER . "%'
                AND type IN (".ILT_CUSTOMFIELD_TYPE_SELECT.",".ILT_CUSTOMFIELD_TYPE_MULTISELECT.")
            "
        );

        if ($badrows) {
            $transaction = $DB->start_delegated_transaction();
            foreach ($badrows as $bad) {
                $fixedrow = new stdClass();
                $fixedrow->id = $bad->id;
                $fixedrow->possiblevalues = str_replace(';', ILT_CUSTOMFIELD_DELIMITER, $bad->possiblevalues);
                $DB->update_record('ilt_session_field', $fixedrow);
            }

            $transaction->allow_commit();
        }

        $baddatarows = $DB->get_records_sql(
            "
                SELECT
                    sd.id, sd.data
                FROM
                    {ilt_session_field} sf
                JOIN
                    {ilt_session_data} sd
                  ON
                    sd.fieldid=sf.id
                WHERE
                    sd.data LIKE '%;%'
                AND sd.data NOT LIKE '%". ILT_CUSTOMFIELD_DELIMITER ."%'
                AND sf.type = ".ILT_CUSTOMFIELD_TYPE_MULTISELECT
        );

        if ($baddatarows) {
            $transaction = $DB->start_delegated_transaction();

            foreach ($baddatarows as $bad) {
                $fixedrow = new stdClass();
                $fixedrow->id = $bad->id;
                $fixedrow->data = str_replace(';', ILT_CUSTOMFIELD_DELIMITER, $bad->data);
                $DB->update_record('ilt_session_data', $fixedrow);
            }

            $transaction->allow_commit();
        }

        upgrade_mod_savepoint(true, 2011120701, 'ilt');
    }

    if ($oldversion < 2011120702) {
        $table = new xmldb_table('ilt_session_field');
        $index = new xmldb_index('ind_session_field_unique');
        $index->set_attributes(XMLDB_INDEX_UNIQUE, array('shortname'));

        if ($dbman->table_exists($table)) {

            // Do we need to check for duplicates?
            if (!$dbman->index_exists($table, $index)) {

                // Check for duplicate records and make them unique.
                $replacements = array();

                $transaction = $DB->start_delegated_transaction();

                $sql = 'SELECT
                            l.id,
                            l.shortname
                        FROM
                            {ilt_session_field} l,
                            ( SELECT
                                    MIN(id) AS id,
                                    shortname
                              FROM
                                    {ilt_session_field}
                              GROUP BY
                                    shortname
                              HAVING COUNT(*)>1
                             ) a
                        WHERE
                            l.id<>a.id
                        AND l.shortname = a.shortname
                ';
                $rs = $DB->get_recordset_sql($sql, null);

                if ($rs !== false) {
                    foreach ($rs as $item) {
                        $data = (object)$item;

                        // Randomize the value.
                        $data->shortname = $DB->escape($data->shortname.'_'.$data->id);
                        $DB->update_record('ilt_session_field', $data);
                        $replacements[] = array($item['id'], $item['shortname'], $data->shortname);
                    }
                }

                $transaction->allow_commit();
                ilt_send_admin_upgrade_msg($replacements);

                // Apply the index.
                $dbman->add_index($table, $index);
            }
        }

        upgrade_mod_savepoint(true, 2011120702, 'ilt');
    }

    if ($oldversion < 2011120703) {

        $table = new xmldb_table('ilt');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the introformat field.
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'intro');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description');
        if ($dbman->field_exists($table, $field)) {

            // Move all data from description to intro.
            $ilts = $DB->get_records('ilt');
            foreach ($ilts as $ilt) {
                $ilt->intro = $ilt->description;
                $ilt->introformat = FORMAT_HTML;
                $DB->update_record('ilt', $ilt);
            }

            // Remove the old description field.
            $dbman->drop_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2011120703, 'ilt');
    }

    if ($oldversion < 2013010400) {

        // Add a field for the user calendar entry checkbox.
        $table = new xmldb_table('ilt');
        $field = new xmldb_field('usercalentry');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update the existing showoncalendar field, change true to ILT_CAL_SITE.
        $sql = 'UPDATE {ilt}
                SET showoncalendar = ?
                WHERE showoncalendar = ?';
        $DB->execute($sql, array(ILT_CAL_SITE, ILT_CAL_COURSE));

        $ILT = $DB->get_records('ilt');
        foreach ($ILT as $ilt) {
            ilt_update_instance($ilt, false);
        }

        upgrade_mod_savepoint(true, 2013010400, 'ilt');
    }

    if ($oldversion < 2017053000) {

        // Define field allowcancellationsdefault to be added to ilt.
        $table = new xmldb_table('ilt');
        $field = new xmldb_field('allowcancellationsdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'usercalentry');

        // Conditionally launch add field allowcancellationsdefault.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allowcancellations to be added to ilt_sessions.
        $table = new xmldb_table('ilt_sessions');
        $field = new xmldb_field('allowcancellations', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'discountcost');

        // Conditionally launch add field allowcancellations.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2017053000, 'ilt');
    }
    
    /*
    * @Author VaibhavG
    * @desc #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc upgrade the ilt_sessions table with added three more fields/columns.
    * @date 12Dec2018
    * @start code
    */
    if ($oldversion < 2018070504) {
        // Remove unused field.
        $table = new xmldb_table('ilt_sessions');
        //$table->add_field('discountcode', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $field1 = new xmldb_field('instructor');
        $field1->set_attributes(XMLDB_TYPE_TEXT ,'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field1)) {       
            $result = $result && $dbman->add_field($table, $field1);
        }
        
        $field2 = new xmldb_field('location');
        $field2->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field2)) { 
            $result = $result && $dbman->add_field($table, $field2);
        }
        
        $field3 = new xmldb_field('classroom');
        $field3->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field3)) { 
            $result = $result && $dbman->add_field($table, $field3);
        }
        
        $field4 = new xmldb_field('costcenter');
        $field4->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field4)) { 
            $result = $result && $dbman->add_field($table, $field4);
        }
        upgrade_mod_savepoint(true, 2018070504, 'ilt');
    }
    /*
    * @Author VaibhavG
    * @desc #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc upgrade the ilt_sessions table with added three more fields/columns.
    * @date 12Dec2018
    * @End code
    */
    
    /*
    * @Author VaibhavG
    * @desc #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @desc upgrade the ilt_sessions table with added session name field as per the suggession of Praveen Shukla
    * @date 14Dec2018
    * @start code
    */
    if ($oldversion < 2018070505) {
        // Remove unused field.
        $table = new xmldb_table('ilt_sessions');
        $field1 = new xmldb_field('sessionname');
        $field1->set_attributes(XMLDB_TYPE_TEXT ,'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field1)) {       
            $result = $result && $dbman->add_field($table, $field1);
        }
        
        
        upgrade_mod_savepoint(true, 2018070505, 'ilt');
    }
    
    
    /*
    * @Author VaibhavG
    * @desc #10: ILT Custom work - Assign multiple instructors in ILT Session
    * @deas cupgrade the ilt_sessions table with added three more fields/columns.
    * @date 12Dec2018
    * @End code
    */

    /*
    * @Author VaibhavG
    * @desc upgrade the ilt_sessions table with added resource field as per the suggession of Praveen Shukla & Yakul Bajaj
    * @date18Dec2018
    * @start code
    */
    if ($oldversion < 2018070506) {
        // Remove unused field.
        $table = new xmldb_table('ilt_sessions');
        $field1 = new xmldb_field('resource');
        $field1->set_attributes(XMLDB_TYPE_TEXT ,'medium', null, null, null, null);
        
        if (!$dbman->field_exists($table, $field1)) {       
            $result = $result && $dbman->add_field($table, $field1);
        }
        
        
        upgrade_mod_savepoint(true, 2018070506, 'ilt');
    }
    /*
    * @Author VaibhavG
    * @desc upgrade the ilt_sessions table with added resource field as per the suggession of Praveen Shukla & Yakul Bajaj
    * @date 18Dec2018
    * @End code
    */

    /**
     * Update the table colum to make it compatible with the Cost and Budget plugin
     * 
     * @author Sandeep B
     * @since 02-03-2019
     * @author Paradiso
     * 
     */
    if ($oldversion < 2018070507 ) {
        //add table 'costnbudget_layout'
        // Define field newfield to be added to course.
        $table = new xmldb_table('ilt_sessions');
        $field = new xmldb_field('costcenter', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'bu');
        }
        upgrade_mod_savepoint($result, 2018070507, 'ilt');
    }

    if($oldversion < 2018070508 ){
        // delete local_ilt entry in table, if exists
        if ($DB->record_exists('config_plugins', ['plugin' => "local_ilt"])) {
            $DB->delete_records('config_plugins', ['plugin' => "local_ilt"]);
        }
        upgrade_mod_savepoint(true, 2018070508, 'ilt');
    }

    /**
     * @ticket : #974 Create ILT Services for E-commerce
     * @author : Abhishek V
     * @since  : 24 August 2021
     */
    if ($oldversion < 2018070509) {
            $menu = "-
            Instructor Led Training
            E-Learning
            Blended Learning";
            $course_info_field                         =   new stdClass();
            $course_info_field->shortname              =  "coursetype";
            $course_info_field->name                   =   "Course Type";
            $course_info_field->datatype               =   "menu";
            $course_info_field->descriptionformat      =   "1";
            $course_info_field->categoryid             =   "2";
            $course_info_field->sortorder              =   "4";
            $course_info_field->required               =   "0";
            $course_info_field->locked                 =   "0";
            $course_info_field->visible                =   "2";
            $course_info_field->forceunique            =   "0";
            $course_info_field->defaultdata            =   "";
            $course_info_field->defaultdataformat      =   "0";
            $course_info_field->param1                 =   $menu;

            $course_info_field_id  = $DB->insert_record('course_info_field', $course_info_field);

        upgrade_mod_savepoint(true, 2018070509, 'ilt');    
    }
    // END #974
    return $result;
}
