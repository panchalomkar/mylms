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

namespace local_mt_dashboard\output;

use lang_string;

//Class to prepare a cohort idnumber for display.

class cohortidnumber extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param stdClass $cohort
     */
    public function __construct($cohort) {
        $cohortcontext = \context::instance_by_id($cohort->contextid);
        $editable = has_capability('local/companycohort:view', $cohortcontext);
        $displayvalue = s($cohort->idnumber); // All idnumbers are plain text.
        parent::__construct('core_cohort', 'cohortidnumber', $cohort->cohortid, $editable,
            $displayvalue,
            $cohort->idnumber,
            new lang_string('editcohortidnumber', 'cohort'),
            new lang_string('newidnumberfor', 'cohort', $displayvalue));
    }

    // Updates cohort name and returns instance of this object
    public static function update($cohortid, $newvalue) {
        global $DB;
        $cohort = $DB->get_record('cohort', array('id' => $cohortid), '*', MUST_EXIST);
        $cohortcontext = \context::instance_by_id($cohort->contextid);
        \external_api::validate_context($cohortcontext);
        require_capability('local/companycohort:view', $cohortcontext);
        $record = (object)array('id' => $cohort->cohortid, 'idnumber' => $newvalue, 'contextid' => $cohort->contextid);
        mt_dashboard_cohort_update_cohort($record);
        $cohort->idnumber = $newvalue;
        return new static($cohort);
    }
}
