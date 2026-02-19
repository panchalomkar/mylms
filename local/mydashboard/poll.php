<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


require_once('../../config.php');

require_once 'lib.php';
global $DB, $CFG, $USER;

require_login();

// redirect to report page if siteadmin

$PAGE->set_url('/local/mydashboard/poll.php');
$PAGE->set_title('Poll');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('poll', 'local_mydashboard'));

echo $OUTPUT->header();

$userid = $USER->id;

echo $OUTPUT->footer();