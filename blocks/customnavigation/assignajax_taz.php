<script>
</script>
<?php
define('AJAX_SCRIPT', true);
require_once ("../../config.php");
global $DB, $USER, $OUTPUT, $CFG;

/**
* Add $roles var, that makes a temporaly save of the roles selected for the user
* @author Hugo S.
* @since june 05 of 2018
* @rlms
* @ticket 11
*/

$rep = $_GET['id'];
$roles = $_GET['roles'];
$output = new stdClass;
$role = $DB->get_records('role');
$count = 0;
$cheked = $DB->get_records('customnavigation', array('id' => $rep), 'sort ASC');

$chekedcheck = explode(',',$cheked[$rep]->roleid);
$roles = explode(',',$roles);

$output->result = '<div class="role_form">';
$output->result .= '<form id="roleform" name="assignform">';
$output->result .= '<table id="roletable" class="table"><tr><th><h3>Rolename</h3></td><td><h3>Assignrole</h3><td><h3><a class="closeButonPopup" id="add-content-close" onclick="hidetazft()"><span aria-hidden="true">×</span></a></h3></td></th></tr>';

/**
* Show site admin role
* @author Andres Ag.
* @since 08/10/2015
* @rlms
*/
$output->result .= '<tr><td>';
$output->result .= get_string('site_admin', 'block_customnavigation');
$output->result .= '</td><td style="text-align: center;">';

if(in_array("-1", $chekedcheck) || in_array("-1", $roles)){
    $output->result .= '<input type="checkbox" value ="-1"name="type" checked="checked"/>';
} else {
    $output->result .= '<input type="checkbox" value ="-1"name="type"/>';
}
$output->result .= '</td></tr>';

foreach($role as $rolename)
{
    $count += 1;
/*if($rolename->id ==3 || $rolename->id ==5 ||$rolename->id ==11 ||$rolename->id ==12){*/
    $output->result .= '<tr><td>';
    
    $name = $rolename->name;
    if(empty($name))
    {
        $name = $rolename->shortname;
    }

    $output->result .= $name;
    $output->result .= '</td><td style="text-align:center;">';

    if(in_array($rolename->id,$chekedcheck) || in_array($rolename->id, $roles)){
        $output->result .= '<input type="checkbox" value ="'.$rolename->id .'"name="type" checked="checked"/>';
    } else {
        $output->result .= '<input type="checkbox" value ="'.$rolename->id .'"name="type"/>';
    }
    $output->result .= '</td></tr>';
/*}*/
}

$output->result .= '<tr><td>';
$output->result .= get_string('no_roles', 'block_customnavigation');
$output->result .= '</td><td style="text-align: center;">';
if(in_array("0", $chekedcheck)){
    $output->result .= '<input type="checkbox" value ="0"name="type" checked="checked"/>';
} else {
    $output->result .= '<input type="checkbox" value ="0"name="type"/>';
}
$output->result .= '</td></tr>';

$output->result .='<tr><td>';
$output->result .= '<input class="btn btn-primary" type="button" onclick=submitrecrod(this.form,"'.$rep.'") name="submit" value="assign"/>';
$output->result .= '</td><td></td></tr></table></form>';
$output->result .= '</div>';

//$rolerecord = new stdClass();
//$rolerecord->userid = $USER->id ;
//$roleinssert = $DB->insert_record('customnavigation',$rolerecord);
print_r($output->result);
echo $output->result;
die;
