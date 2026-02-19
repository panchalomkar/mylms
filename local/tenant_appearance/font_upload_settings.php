<?php
/**
 * Font Upload Setting Shown.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */

require_once( '../../config.php');
require_login();
global $USER, $CFG, $DB, $OUTPUT, $SESSION;
// Set the url.
$linkurl = new moodle_url('/local/tenant_appearance/index.php');
$linktext = get_string('font_upload_setting', 'local_tenant_appearance');
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);
$PAGE->navbar->add(get_string('pluginname', 'local_tenant_appearance'), '/local/tenant_appearance/index.php');
$PAGE->requires->css('/local/tenant_appearance/css/styles.css');
// Set tye pagetype correctly.
$PAGE->set_pagetype('local-tenant_appearance-index');
$PAGE->requires->css(new moodle_url('/local/tenant_appearance/css/bootstrap-colorpicker.min.css'));

// Check current editing Company.
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}
if($id) {
    $fontnameval = $DB->get_records('font_upload_setting', ['companyid'=>$id]);
    $fontval = $DB->get_record('config_plugins', array('name' => 'fontnametheme_'.$id));
    $link = $CFG->wwwroot .'/local/tenant_appearance/tenant_appearance.php#font';
}else {
    $fontnameval = $DB->get_records('font_upload_setting', ['companyid'=>0]);
    $fontval = $DB->get_record('config_plugins', array('name' => 'fontnametheme'));
    $link = $CFG->wwwroot .'/admin/settings.php?section=themesettingparadiso#theme_remui_fonts';
}
//Get files in array  
$fontnamevalnew=array_values($fontnameval);
//Get action link
$action=$CFG->wwwroot .'/local/tenant_appearance/classes/upload_fonts.php';

//Return param array
$templatecontext=[
  'records' => $fontnamevalnew,
  'link'=>$link,
  'action'=>$action
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_tenant_appearance/font_setting',$templatecontext);
$PAGE->requires->js_call_amd('local_tenant_appearance/color', 'init');
echo  $OUTPUT->footer();




