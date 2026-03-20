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
 * External ilt API
 *
 * @package    block_lpd
 * @since      2021
 * @copyright  paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot."/blocks/lpd/lib/lib.php");

/**
 * Assign functions
 * @copyright paradiso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lpd_external extends external_api {

    public static function getlpdetail($action,$learningPath,$page,$lpid_selected) {
        global $CFG, $USER,$DB;

        if($action == 'getLpDetail') {
          if($action) {
              $ispagetypelocal = false;
              $view = displayLearningPathsViewDetail($learningPath,$USER->id,$page,$lpid_selected,$ispagetypelocal);
          }
        }
      $responsedata = array();
        if($jsonObj) {
            $responsedata['data'] = $view;
        } else {
            $responsedata['data'] = $view;
        }
        return $responsedata;
    }

    public static function getlpdetail_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_TEXT, 'action'),
                'learningPath' => new external_value(PARAM_TEXT, 'learningpath id'),
                'page' => new external_value(PARAM_TEXT, 'page id'),
                'lpid_selected' => new external_value(PARAM_TEXT, 'lp selected id')
            )
        );
    }

    public static function getlpdetail_returns() {
       return new external_single_structure(
            array(
                 'data' => new external_value(PARAM_RAW, 'return data'),
            )
            );
    }

    public static function lpviewdetails($page) {
      global $CFG, $USER,$DB;
      $userid = $USER->id;
      $errormsg = '';
      try {
          $config = get_config('block_lpd');
          if (isset($config->learningpath)) {
              $result = displayLearningPathsViewDetail($config->learningpath, $userid, $page );
          } else {
              $result = displayLearningPathsView($config->evaluate_progress, $userid, $page);
          }
      } catch (Exception $e) {
          $errormsg = html_writer::tag('div', get_string('noresultslp', 'block_lpd'), array('class' => ' text-bold lpd-lp-detail col-sm-12 col-md-12 col-lg-12 alert alert-mtlms text-left'));
      }
      $templatecontext['haslp'] = $errormsg;
      $templatecontext['table'] = $result;

      $responsedata = array();
        if($result) {
          $responsedata['haslp'] = $errormsg;
          $responsedata['table'] = $result;
        } else {
          $responsedata['haslp'] = $errormsg;
          $responsedata['table'] = '';
        }
        return $responsedata;
  }

  public static function lpviewdetails_parameters() {
      return new external_function_parameters(
          array(
              'page' => new external_value(PARAM_TEXT, 'page id'),
          )
      );
  }

  public static function lpviewdetails_returns() {
    return new external_single_structure(
      array(
           'haslp' => new external_value(PARAM_RAW, 'Has LP Data'),
           'table' => new external_value(PARAM_RAW, 'Return Data'),
      )
      );
  }
    
}
