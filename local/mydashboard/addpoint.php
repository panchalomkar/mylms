<?php
require_once('../../config.php');
require_once('lib.php');
global $USER, $DB, $SITE, $PAGE;
$pointvalue=$_POST['pointv'];
$daturl=$_POST['daturl'];


// Parse the URL to get the query string
$queryString = parse_url($daturl, PHP_URL_QUERY);

// Parse the query string to get the parameters
parse_str($queryString, $params);

// Access the 'id' parameter
$id = isset($params['id']) ? $params['id'] : null;

$userid= $id;
$pointtype= "admin";
$action= "added";
$points= $pointvalue;
add_point_log($userid, $pointtype, $action, $points);
// Output the result
echo "Leadership point succussfully added";
?>