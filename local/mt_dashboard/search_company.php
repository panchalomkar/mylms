<?php
require_once( '../../config.php');
require_once($CFG->dirroot.'/local/mt_dashboard/lib.php');
global $SESSION , $CFG , $DB;
require_login();
$searchdata = optional_param('searchdata', '', PARAM_ALPHANUM); 
$suspend = optional_param('suspend', '', PARAM_ALPHANUM);
$total_tenants = get_all_companies( $suspend , $startfrom=0, $RECORD_PER_PAGE=0 , $searchdata, $add_limit = 0 );
echo json_encode($total_tenants);

