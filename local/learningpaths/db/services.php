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
 * Web service for mod ilt
 * @package    mod_ilt
 * @subpackage db
 * @since      2021
 * @copyright  Paradiso
 */

$services = array(
  'Local learningpaths Services' => array(
    'functions' => array('local_learningpath_publishlp', 'local_learningpath_actions','local_learningpath_ajax'),
    'shortname' => 'local_lp_services',
    'restrictedusers' => 0,
    'enabled' => 1,
    'ajax' => true,
  )
);

$functions = array(
  'local_learningpath_publishlp' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'publishlp',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => '',
    'type'          => 'write',
    'capabilities'  => '',
    'ajax' => true,
  ),
  'local_learningpath_actions' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'actions',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => '',
    'type'          => 'write',
    'capabilities'  => '',
    'ajax' => true,
  ),
  'local_learningpath_ajax' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'ajaxpage',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => '',
    'type'          => 'write',
    'capabilities'  => '',
    'ajax' => true,
  ),
  'local_learningpath_ajaxnew' => array(
    'classname' => 'local_learningpath_external',
    'methodname' => 'ajaxnew',
    'classpath' => 'local/learningpaths/externallib.php',
    'description' => '',
    'type' => 'write',
    'ajax' => true,
  ),
  'local_learningpath_mobile_api' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'lp_mobile_api',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => 'Create Learning Path API For Mobile',
    'type'          => 'write',
    'capabilities'  => '',
    'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
  'local_learningpath_mobile_course_api' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'lp_mobile_course_api',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => 'Create Learning Path API Courses For Mobile',
    'type'          => 'write',
    'capabilities'  => '',
    'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
  'local_learningpath_duplicatecheck' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'duplicatecheck',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => '',
    'type'          => 'write',
    'capabilities'  => '',
    'ajax' => true,
    'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  ),
  'local_lp_mobile_course_finish' => array(
    'classname'     => 'local_learningpath_external',
    'methodname'    => 'lp_mobile_course_finish',
    'classpath'     => 'local/learningpaths/externallib.php',
    'description'   => '',
    'type'          => 'write',
    'capabilities'  => '',
    'ajax' => true,
    'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
  )
);
