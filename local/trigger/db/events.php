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
 * Definition of core event observers.
 *
 * The observers defined in this file are notified when respective events are triggered. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Events API: {@link http://docs.moodle.org/dev/Event_2}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package   local
 * @category  event
 * @copyright 2007 onwards Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of legacy event handlers.

$handlers = array(
    // No more old events!
);

// List of events_2 observers.

$observers = array(

    array(
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => 'local_trigger_observer::addloginpoints',
    ),
     array(
        'eventname'   => '\core\event\user_created',
        'callback'    => 'local_trigger_observer::newusercreated1',
    ),
    array(
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => 'local_trigger_observer::dailyquizsubmitted1',
    ),
    
     array(
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => 'local_trigger_observer::coursequizsubmitted1',
    ),
    
    array(
        'eventname'   => '\block_xp\event\user_leveledup',
        'callback'    => 'local_trigger_observer::checklevelup',
    ),
    array(
        'eventname'   => '\core\event\course_completed',
        'callback'    => 'local_trigger_observer::course_completion_points',
    ),
    
);

// List of all events triggered by Moodle can be found using Events list report.
