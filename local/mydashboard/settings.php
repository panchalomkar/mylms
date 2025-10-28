<?php

// This file is part of the Local notifications plugin
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
 * This plugin sends users a notifications message after logging in
 * and notify a moderator a new user has been added
 * it has a settings page that allow you to configure the messages
 * send.
 *
 * @package    local
 * @subpackage ranking
 * @copyright  2022 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
       global $USER, $CFG, $DB, $OUTPUT, $SESSION;
       if (!empty($SESSION->currenteditingcompany)) {
           $selectedcompany = $SESSION->currenteditingcompany;
       } else if (!empty($USER->profile->company)) {
           $usercompany = company::by_userid($USER->id);
           $selectedcompany = $usercompany->id;
       } else {
           $selectedcompany = "";
       }
    $settings = new admin_settingpage('local_mydashboard', get_string('pluginname', 'local_mydashboard'));
    $ADMIN->add('localplugins', $settings);

    
    //WELCOME POINTS 
    $wpoints = array(100=>100,500=>500,1000=>1000,1500=>1500,2000=>2000,3000=>3000,5000=>5000);
      $settings->add(new admin_setting_heading('welcome_heading', get_string('welcomepoint', 'local_mydashboard'), ''));
    
    $settings->add(new admin_setting_configselect('local_mydashboard/welcome_point'.'_'.$selectedcompany, 
           get_string('welcomepoint', 'local_mydashboard'), 
            get_string('welcomepoint_desc', 'local_mydashboard'), 
            0, 
            $wpoints));
    
    //Scratch card settings
     $settings->add(new admin_setting_heading('scratchcard_heading', get_string('scratchcard', 'local_mydashboard'), ''));
    $choices = array(1=>1, 2=>2, 3=>3);

    $settings->add(new admin_setting_configselect('local_mydashboard/quiz_scratch_card'.'_'.$selectedcompany, 
           get_string('quiz_scratch_card', 'local_mydashboard'), 
            get_string('quiz_scratch_card_desc', 'local_mydashboard'), 
            0, 
            $choices));
    
    $settings->add(new admin_setting_configselect('local_mydashboard/rank_promote'.'_'.$selectedcompany, 
            get_string('rank_promote', 'local_mydashboard'), 
            get_string('rank_promote_desc', 'local_mydashboard'), 
            0, 
            $choices));
    
    //DAILY QUIZ POINTS
     $points = array(1=>1,2=>2,3=>3,5=>5,10=>10,15=>15,20=>20,25=>25,30=>30,40=>40,50=>50);
      $settings->add(new admin_setting_heading('dailyquiz_heading', get_string('dailyquiz', 'local_mydashboard'), ''));
    
    $settings->add(new admin_setting_configselect('local_mydashboard/dailyquiz_point'.'_'.$selectedcompany, 
           get_string('dailyquiz_point', 'local_mydashboard'), 
            get_string('dailyquiz_point_desc', 'local_mydashboard'), 
            0, 
            $points));
    //DAILY LOGIN POINTS
    $settings->add(new admin_setting_heading('loginpoint_heading', get_string('loginpoints', 'local_mydashboard'), 
            get_string('loginpoints_desc', 'local_mydashboard') ));
   
     $settings->add(new admin_setting_configselect('local_mydashboard/loginpoint_day1'.'_'.$selectedcompany, 
            get_string('login_day1', 'local_mydashboard'),'', 
            0, 
            $points));
     $settings->add(new admin_setting_configselect('local_mydashboard/loginpoint_day2'.'_'.$selectedcompany, 
            get_string('login_day2', 'local_mydashboard'),'', 
            0, 
            $points));
     $settings->add(new admin_setting_configselect('local_mydashboard/loginpoint_day3'.'_'.$selectedcompany, 
            get_string('login_day3', 'local_mydashboard'),'', 
            0, 
            $points));
     $settings->add(new admin_setting_configselect('local_mydashboard/loginpoint_day4'.'_'.$selectedcompany, 
            get_string('login_day4', 'local_mydashboard'),'', 
            0, 
            $points));
     $settings->add(new admin_setting_configselect('local_mydashboard/loginpoint_day5'.'_'.$selectedcompany, 
            get_string('login_day5', 'local_mydashboard'),'', 
            0, 
            $points));

            $settings->add(new admin_setting_configcheckbox('local_mydashboard/showpointonheader',
            get_string('showpointdescription', 'local_mydashboard'),
            get_string('showpointdescription_desc', 'local_mydashboard'), 0));

}

