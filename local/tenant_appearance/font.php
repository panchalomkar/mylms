<?php
/**
 * Saving font.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
require_once("{$CFG->dirroot}/local/tenant_appearance/classes/font_form.php");
 
$mformf = new font_form();
//check current editing company
$id= '';
if(!empty($SESSION->currenteditingcompany)){
	$id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
	$id = \iomad::is_company_user();
} 

if ($mformf->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($data = $mformf->get_data()) {
	
	$arryadata= 'fontnametheme_'.$id;
	$font=$data->$arryadata;
	// Font Style Setting
	$fontdata = $DB->get_record('config_plugins',array('name' =>'fontnametheme_'.$id));
	if(!empty($fontdata)){
	$update = new stdClass();
	$update->plugin='theme_remui';
	$update->name='fontnametheme_'.$id;
	$update->value=$font;
	$update->id=$fontdata->id;
   	$DB->update_record('config_plugins',$update);
	}else{
	$newaddz = new stdClass();
	$newaddz->plugin='theme_remui';
	$newaddz->name='fontnametheme_'.$id;
	$newaddz->value=$font;
	$DB->insert_record('config_plugins', $newaddz);
	}
	
} else {
	$fontname = $DB->get_record('config_plugins',array('name' =>'fontnametheme_'.$id));
	$fontnametheme='fontnametheme_'.$id;
	$updaterecord = new stdClass();
	$updaterecord->$fontnametheme = $fontname->value;
	$mformf->set_data($updaterecord);
}
theme_reset_all_caches();








