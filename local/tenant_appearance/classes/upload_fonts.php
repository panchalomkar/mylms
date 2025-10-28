<?php
/**
 * Upload fonts
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
global $CFG, $DB;
require_once('../../../config.php');

define('AJAX_SCRIPT', true);

require_login();
//Params
$fontfamily = optional_param('fontfamily', array(), PARAM_RAW);
$selectstyle = optional_param('selectstyle', array(), PARAM_RAW);
$fonttype = optional_param('fonttype', array(), PARAM_RAW);
$fontweight = optional_param('fontweight', array(), PARAM_RAW);

//check current editing company
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}

$target_dir = $CFG->dirroot ."/theme/paradiso/fonts/";
//Upload a font file
    if($id) {
        $outputfile = $id . '_' . $_FILES['file']['name'];
    } else {
        $outputfile = $_FILES['file']['name'];
    }
    $ok=move_uploaded_file($_FILES['file']['tmp_name'], $target_dir . $outputfile ); 
    $newaddz = new stdClass();
            $newaddz->font_file=$outputfile;
            $newaddz->font_family=$fontfamily;
            $newaddz->font_style=$selectstyle;
            $newaddz->font_type=$fonttype;
            $newaddz->font_weight=$fontweight;
            $newaddz->companyid=$id;
            $DB->insert_record('font_upload_setting', $newaddz);

$url = $CFG->wwwroot . "/local/tenant_appearance/font_upload_settings.php";
redirect($url);
