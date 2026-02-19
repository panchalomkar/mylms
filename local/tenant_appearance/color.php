<?php
/**
 * Saving color.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
require_once("{$CFG->dirroot}/local/tenant_appearance/classes/color_form.php");
$mformc = new color_form();

if ($mformc->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($data = $mformc->get_data()) {
	
	//Get form data
	$arrayc=array_keys(get_object_vars($data));

	$brandprimary=$arrayc['0'];
	$bodybackground=$arrayc['1'];
	$brandprimaryv = $DB->get_record('config_plugins',array('name' =>$brandprimary));
	
	if(!empty($brandprimaryv)){
		$update = new stdClass();
		$update->plugin='theme_remui';
		$update->name=$brandprimary;
		$update->value=$data->$brandprimary;
		$update->id=$brandprimaryv->id;
		$DB->update_record('config_plugins',$update);
	}else{
		$newaddz = new stdClass();
		$newaddz->plugin='theme_remui';
		$newaddz->name=$brandprimary;
		$newaddz->value=$data->$brandprimary;
		$DB->insert_record('config_plugins', $newaddz);
	}
	
    
	$bodyback = $DB->get_record('config_plugins',array('name' =>$bodybackground));
	if(!empty($bodyback)){
		$update = new stdClass();
		$update->plugin='theme_remui';
		$update->name=$bodybackground;
		$update->value=$data->$bodybackground;
		$update->id=$bodyback->id;
		$DB->update_record('config_plugins',$update);
	}else{
		$newaddz = new stdClass();
		$newaddz->plugin='theme_remui';
		$newaddz->name=$bodybackground;
		$newaddz->value=$data->$bodybackground;
		$DB->insert_record('config_plugins', $newaddz);
	}

} else {
	//Check current editing Comapny
	 $id= '';
        if(!empty($SESSION->currenteditingcompany)){
            $id = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_user()){
            $id = \iomad::is_company_user();
        }  
	
	$brandprimaryv = $DB->get_record('config_plugins',array('name' =>'brandprimary_'.$id));
	$brand='brandprimary_'.$id;
	
	$bodybackground = $DB->get_record('config_plugins',array('name' =>'bodybackground_'.$id));
	$bodyback='bodybackground_'.$id;
	
	$updaterecord = new stdClass();
	$updaterecord->$brand = $brandprimaryv->value;
	$updaterecord->$bodyback = $bodybackground->value;
    $mformc->set_data($updaterecord);
}









