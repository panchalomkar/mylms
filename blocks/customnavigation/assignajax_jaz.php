<?php 
define('AJAX_SCRIPT', true);
require_once ("../../config.php");
global $DB, $USER, $OUTPUT, $CFG;
$rep = $_GET['allid'];
$totalrole = $_GET['roleid'];
// $graph = $DB->get_record('customnavigation',array('id' => $rep));
$DB->set_field('customnavigation','roleid',$totalrole, array('id' => $rep));
?>