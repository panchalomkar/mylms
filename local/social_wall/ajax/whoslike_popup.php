<?php
/**
 * This file returns the html of newly added message.
 * 
 * @package	local_social_wall
 * @version	9.3
 * @author	Abhishek Vaidya
 * @since 03-04-2020
 * @paradiso
*/

global $CFG;
require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/social_wall/lib.php");

define('AJAX_SCRIPT', true);

require_login();

$msgid = optional_param('msgid', 0, PARAM_INT);

    $userdata=$DB->get_fieldset_sql("SELECT userid FROM {social_wall_ratings} WHERE msg_id = $msgid");
    
    for ($i = 0; $i < count($userdata); $i++) {
    $us_picture[] = get_user_picture($userdata[$i]);
    $us_name[] = $DB->get_fieldset_sql("SELECT CONCAT(firstname, ' ', lastname) AS fullname FROM {user} WHERE id=$userdata[$i]");
}

    $output .= html_writer::start_tag('table', ['class' => '']);
    
    for ($i = 0; $i < count($us_picture); $i++) {
    $output .= html_writer::start_tag('tr');
    $output .= html_writer::start_tag('th');
    $output .= html_writer::tag('div', $us_picture[$i], array('style' => 'vertical-align: center;border-radius: 50%;color: black !important;font-size: 14px;padding: 10px;'));
    $output .= html_writer::end_div();
    $output .= html_writer::end_tag('th');
    $output .= html_writer::start_tag('th');
    $output .= html_writer::tag('h3', $us_name[$i][0]);
    $output .= html_writer::end_tag('th');
    $output .= html_writer::end_tag('tr');
}

$output .= html_writer::end_tag('table');
    
    echo $output;
    die();