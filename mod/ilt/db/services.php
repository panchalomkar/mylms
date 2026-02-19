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

$functions = array(

        'mod_ilt_get_ilt_session_ecommerce' => array(
            'classname'     => 'mod_ilt_external',
            'methodname'    => 'get_ilt_session_ecommerce',
            'classpath'     => 'mod/ilt/externallib.php',
            'description'   => 'ILT Services for E-commerce',
            'type'          => 'write',
            'capabilities'  => ''
        ),
        'mod_ilt_enroll_ilt_session_ecommerce' => array(
            'classname'     => 'mod_ilt_external',
            'methodname'    => 'enroll_ilt_session_ecommerce',
            'classpath'     => 'mod/ilt/externallib.php',
            'description'   => 'Enroll ILT Services for E-commerce',
            'type'          => 'write',
            'capabilities'  => ''
        ),
);
