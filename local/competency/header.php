<?php
global $CFG, $DB, $OUTPUT, $PAGE, $USER;
$tabid=''; $limit =10;
$id =  optional_param('id', 0, PARAM_INT);
$selectPageNo = optional_param('selectPageNo', 1, PARAM_INT);
$selectPageNo1 = optional_param('selectPageNo1', 1, PARAM_INT);
$selectPageNo2 = optional_param('selectPageNo2', 1, PARAM_INT);
$selectPageNo3 = optional_param('selectPageNo3', 1, PARAM_INT);
$pagination=''; $pagination1='';
$pagination2=''; $pagination3='';
$tabid='';$errormessage1='';
$rows='';
?>
