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
 * @since 3.4.2
 * @package format_classroom
 * @copyright eNyota Learning Pvt Ltd.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/filelib.php');
require_login();

/**
 * Adding location form.
 *
 * @package   format_classroom
 * @copyright 2018 eNyota Learning Pvt Ltd.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplehtml_form_apporval extends moodleform {
    /**
     * Add location form with definition.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE, $DB;
        $mform = $this->_form;  // Don't forget the underscore!

        $roleSql = "Select * from {role}";
        $roleResult = $DB->get_records_sql($roleSql, array());
        foreach($roleResult as $key => $role){
            $rolename[$key] = $role->shortname;
        }

        // Roles
        $rolename = array_merge(array(null => 'Select Role'), $rolename);
        $mform->addElement('select', 'roles', get_string('roles', 'local_competency') , $rolename);
        $mform->addHelpButton('roles', 'roles', 'local_competency');
        $mform->addRule('roles', get_string('required'), 'required', null, 'client');
        
        // Competency title
        $competency_titleSql = "Select * from {competency_title} WHERE isdeleted=?";
        $competency_titleResult = $DB->get_records_sql($competency_titleSql, array(0));
        foreach($competency_titleResult as $key => $competency_title){
            $competency_titleArr[$key] = $competency_title->title;
        }
        $competency_titleArr = array_merge(array(null => 'Select Competency Title'), $competency_titleArr);
        $select = $mform->addElement('select', 'competency_title', get_string('competency_title', 'local_competency') , $competency_titleArr);
        $mform->addHelpButton('competency_title', 'competency_title', 'local_competency');
        $mform->addRule('competency_title', get_string('required'), 'required', null, 'client');
        //$select->setMultiple(true);


        // Competency category name
        $competency_categorySql = "Select * from {competency_category} WHERE isdeleted=?";
        $competency_categoryResult = $DB->get_records_sql($competency_categorySql, array(0));
        foreach($competency_categoryResult as $key => $competency_category){
            $competency_categoryArr[$key] = $competency_category->name;
        }
        $competency_categoryArr = array_merge(array(null => 'Select Sub-Competency'), $competency_categoryArr);
        $select = $mform->addElement('select', 'competency_category', get_string('competency_category', 'local_competency') , $competency_categoryArr);
        $mform->addHelpButton('competency_category', 'competency_category', 'local_competency');
        $mform->addRule('competency_category', get_string('required'), 'required', null, 'client');
        //$select->setMultiple(true);


         // Competency name
        $competenciesSql = "Select * from {competencies} WHERE isdeleted=?";
        $competenciesResult = $DB->get_records_sql($competenciesSql, array(0));
        foreach($competenciesResult as $key => $competencies){
            $competenciesArr[$key] = $competencies->comptencyname;
        }
        $competenciesArr = array_merge(array(null => 'Select Competency'), $competenciesArr);
        $select = $mform->addElement('select', 'competencies', get_string('competencies', 'local_competency') , $competenciesArr);
        $mform->addHelpButton('competencies', 'competencies', 'local_competency');
        $mform->addRule('competencies', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);


         // Competency name
        $userSql = "Select * from {user} WHERE deleted=0 and suspended=0 and confirmed=1 AND id != 1";
        $userResult = $DB->get_records_sql($userSql, array());
        foreach($userResult as $key => $user){
            $userArr[$key] = fullname($user);
        }
        $userArr = array_merge(array(null => 'Select Competency'), $userArr);
        $select = $mform->addElement('select', 'user', get_string('user', 'local_competency') , $userArr);
        $mform->addHelpButton('user', 'user', 'local_competency');
        $mform->addRule('user', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        $this->add_action_buttons(true, 'Submit');
    }

    /**
     * Custom validation should be added here.
     *
     * @return void
     */
    public function validation($data, $files) {
        global $DB;
        $err = array();
        
        return $err;
        
    }
}