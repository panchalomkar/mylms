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
 * Web service for block lpd
 * @package    block_lpd
 * @subpackage db
 * @since      2021
 * @copyright  Paradiso
 */

$services = array(
  'Block LPD Services' => array(
      'functions' => array('block_lpd_getlpdetail', 'block_lpd_lpviewdetails'),
      'shortname' => 'block_lpd_services',
      'restrictedusers' => 0,
      'enabled' => 1,
      'ajax' => true,
  )
);

$functions = array(

        'block_lpd_getlpdetail' => array(
            'classname'     => 'block_lpd_external',
            'methodname'    => 'getlpdetail',
            'classpath'     => 'blocks/lpd/externallib.php',
            'description'   => '',
            'type'          => 'write',
            'capabilities'  => '',
            'ajax' => true,
        ),
        'block_lpd_lpviewdetails' => array(
            'classname'     => 'block_lpd_external',
            'methodname'    => 'lpviewdetails',
            'classpath'     => 'blocks/lpd/externallib.php',
            'description'   => '',
            'type'          => 'write',
            'capabilities'  => '',
            'ajax' => true,
        ),
);


