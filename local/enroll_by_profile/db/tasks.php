
<?php
/**
 * enroll_by_profile
 *
 *
 * @author      VaibhavGhadage
 * @package     enroll_by_profile
 * @since       17 Feb 2021
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname'     => 'local_enroll_by_profile\task\enroll_by_profile',
        'blocking'      => 0,
        'minute'        => '*/10',
        'hour'          => '*',
        'day'           => '*',
        'dayofweek'     => '*',
        'month'         => '*'
    ),

);