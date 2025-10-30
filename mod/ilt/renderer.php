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

class mod_ilt_renderer extends plugin_renderer_base {

    /**
     * Builds session list table given an array of sessions
     */
    public function print_session_list_table($customfields, $sessions, $viewattendees, $editsessions) {
        $output = '';
    global $CFG, $DB,$PAGE,$USER,$SESSION,$OUTPUT;
        //print_object($sessions);
        $tableheader = array();
        foreach ($customfields as $field) {
            if (!empty($field->showinsummary)) {
                $tableheader[] = format_string($field->name);
            }
        }
        $tableheader[] = get_string('sessionname', 'ilt');
        $tableheader[] = get_string('sessioninstructors', 'ilt');
        $tableheader[] = get_string('sessioncostcenter', 'ilt');
        $tableheader[] = get_string('date', 'ilt');
        $tableheader[] = get_string('time', 'ilt');
        if ($viewattendees) {
            $tableheader[] = get_string('capacity', 'ilt');
        } else {
            $tableheader[] = get_string('seatsavailable', 'ilt');
        }
        $tableheader[] = get_string('status', 'ilt');
        
        $tableheader[] = ' ';

        $timenow = time();

        $table = new html_table();
        $table->summary = get_string('previoussessionslist', 'ilt');
        $table->head = $tableheader;
        $table->data = array();

        foreach ($sessions as $session) {
            $isbookedsession = false;
            $bookedsession = $session->bookedsession;
            $sessionstarted = false;
            $sessionfull = false;

            $sessionrow = array();

            // Custom fields.
            $customdata = $session->customfielddata;
            foreach ($customfields as $field) {
                if (empty($field->showinsummary)) {
                    continue;
                }

                if (empty($customdata[$field->id])) {
                    $sessionrow[] = '&nbsp;';
                } else {
                    if (ILT_CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                        $sessionrow[] = str_replace(ILT_CUSTOMFIELD_DELIMITER, html_writer::empty_tag('br'), format_string($customdata[$field->id]->data));
                    } else {
                        $sessionrow[] = format_string($customdata[$field->id]->data);
                    }

                }
            }

            // name.
            $name = $session->sessionname;
            $sessionrow[] = $name;
                    
            // instructor
            $instructor = $session->instructor;
            $instructors = ilt_get_instructor($instructor);
            $getinstructors = implode(',',$instructors);
            $sessionrow[] = $getinstructors;
            
            // cost center
            $costcenter = $session->bu;
            $sessionrow[] = $costcenter;
            
            // Dates/times.
            $allsessiondates = '';
            $allsessiontimes = '';
            if ($session->datetimeknown) {
                foreach ($session->sessiondates as $date) {
                    if (!empty($allsessiondates)) {
                        $allsessiondates .= html_writer::empty_tag('br');
                    }
                    $allsessiondates .= userdate($date->timestart, get_string('strftimedate'));
                    if (!empty($allsessiontimes)) {
                        $allsessiontimes .= html_writer::empty_tag('br');
                    }
                    $allsessiontimes .= userdate($date->timestart, get_string('strftimetime')).
                        ' - '.userdate($date->timefinish, get_string('strftimetime'));
                }
            } else {
                $allsessiondates = get_string('wait-listed', 'ilt');
                $allsessiontimes = get_string('wait-listed', 'ilt');
                $sessionwaitlisted = true;
            }
            $sessionrow[] = $allsessiondates;
            $sessionrow[] = $allsessiontimes;

            // Capacity.
            $signupcount = ilt_get_num_attendees($session->id, MDL_ILT_STATUS_APPROVED);
            $stats = $session->capacity - $signupcount;
            if ($viewattendees) {
                $stats = $signupcount . ' / ' . $session->capacity;
            } else {
                $stats = max(0, $stats);
            }
            $sessionrow[] = $stats;

            
            
            // Status.
            $status  = get_string('bookingopen', 'ilt');
            if ($session->datetimeknown && ilt_has_session_started($session, $timenow) && ilt_is_session_in_progress($session, $timenow)) {
                $status = get_string('sessioninprogress', 'ilt');
                $sessionstarted = true;
            } else if ($session->datetimeknown && ilt_has_session_started($session, $timenow)) {
                $status = get_string('sessionover', 'ilt');
                $sessionstarted = true;
            } else if ($bookedsession && $session->id == $bookedsession->sessionid) {
                $signupstatus = ilt_get_status($bookedsession->statuscode);
                $status = get_string('status_' . $signupstatus, 'ilt');
                $isbookedsession = true;
            } else if ($signupcount >= $session->capacity) {
                $status = get_string('bookingfull', 'ilt');
                $sessionfull = true;
            }

            $sessionrow[] = $status;

              // New desin of the Options.
            $options = '';
            $options .= html_writer::start_tag('a', array('class' => '', 'href' => '#', 'data-toggle' => 'dropdown')); 
            $options .=html_writer::tag('i','', array('class' => 'wid wid-dots'));  
            $options .= html_writer::end_tag('a'); 

            $options .= html_writer::start_tag('div', array('class' => 'dropdown-menu')); 
            if ($editsessions) {
                $options .=html_writer::link(new moodle_url('sessions.php', array('s'=>$session->id)), get_string('edit', 'ilt'), array('class' => 'dropdown-item'));
            //copy link
                $options .=html_writer::link(new moodle_url('sessions.php', array('s'=>$session->id,'c'=>1)), get_string('copy', 'ilt'), array('class' => 'dropdown-item'));
          //delete link
                $options .=html_writer::link(new moodle_url('sessions.php', array('s'=>$session->id,'d'=>1)), get_string('delete', 'ilt'), array('class' => 'dropdown-item'));
            }
            if ($viewattendees) {
                $options .=html_writer::link(new moodle_url('attendees.php', array('s'=>$session->id,'backtoallsessions'=>$session->ilt)), get_string('seeattendees', 'ilt'), array('class' => 'dropdown-item'));
            }
            if ($isbookedsession) {
                $options .=html_writer::link(new moodle_url('signup.php', array('s'=>$session->id,'backtoallsessions'=>$session->ilt)), get_string('moreinfo', 'ilt'), array('class' => 'dropdown-item'));
                if ($session->allowcancellations) {
                    $options .=html_writer::link(new moodle_url('cancelsignup.php', array('s'=>$session->id,'backtoallsessions'=>$session->ilt)), get_string('cancelbooking', 'ilt'), array('class' => 'dropdown-item'));
                }
            } 
            else if (!$sessionstarted and !$bookedsession) {
                $options .=html_writer::link(new moodle_url('signup.php', array('s'=>$session->id,'backtoallsessions'=>$session->ilt)), get_string('signup', 'ilt'), array('class' => 'dropdown-item'));
            }
            if (empty($options)) {
                $options = get_string('none', 'ilt');
            }
             
          $options .= html_writer::end_tag('div'); 
            
            $sessionrow[] = $options;
            $row = new html_table_row($sessionrow);
            // Set the CSS class for the row.
            if ($sessionstarted) {
                $row->attributes = array('class' => 'dimmed_text');
            } else if ($isbookedsession) {
                $row->attributes = array('class' => 'highlight');
            } else if ($sessionfull) {
                $row->attributes = array('class' => 'dimmed_text');
            }

            // Add row to table.
            $table->data[] = $row;
        }
        $output .= html_writer::table($table);
        return $output;
    }
}
