<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$comid = $_POST['maincom_id'];

$getusername = $DB->get_records_sql("SELECT cu.* FROM {competency_category} cc INNER JOIN {competency_users} cu ON cu.subcompetencyid = cc.id WHERE cc.id = $comid");

 echo COUNT($getusername);

?>