<?php
/**
 * Saving logo.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */

define('CLI_SCRIPT', true);

require_once($CFG->libdir.'/clilib.php');
require_once("{$CFG->dirroot}/local/tenant_appearance/classes/logo_form.php");
$mforma = new logo_form();
$filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => array('.png', '.jpg'));


if ($mforma->is_cancelled()) {

} else if ($data = $mforma->get_data()) {
	//Get form data 
	$arrayc=array_keys(get_object_vars($data));
	$name=$arrayc['0'];

	//Prepare draft for logo
	file_save_draft_area_files($data->$name, 1, 'theme_remui', $name, 0, $filemanageroptions);
} else {
	//check current editing company
	$id= '';
        if(!empty($SESSION->currenteditingcompany)){
            $id = $SESSION->currenteditingcompany;
        }else if(\iomad::is_company_user()){
            $id = \iomad::is_company_user();
        }
		
	//Get record of logo
	$fileentry = $DB->get_record('files', array('contextid'=>1, 'component'=>'theme_remui', 'filearea'=>'tenant_logo_'.$id));

	if (empty($fileentry->id)) {
		$entry = new stdClass;
		$draftitemid = 0;
	} else {
		$draftitemid = $fileentry->itemid;
	}
	

	// Prepare filemanager draft area for logo.
	file_prepare_draft_area($draftitemid, 1, 'theme_remui', 'tenant_logo_'.$id, 0, $filemanageroptions);
	$tlogo='tenant_logo_'.$id;
	$entry->$tlogo = $draftitemid;

	$mforma->set_data($entry);
  
}


if(!empty($data)){
	//Get filename for logo
	$fs = get_file_storage();
	$files = $fs->get_area_files(1, 'theme_remui', $name, 0, '', false);
	$file = reset($files);
	if(!empty($file)){
		$a=$file->get_filename();
	}else{
		$a='';
	}

	//Save company logo									
	$tenantlogo = $DB->get_record('config_plugins',array('name' =>$name));
	
	if(!empty($tenantlogo)){
			
		$update = new stdClass();
		$update->plugin='theme_remui';
		
		$update->name=$name;
		$update->value='/'.$a;
		$update->id=$tenantlogo->id;
		
		$DB->update_record('config_plugins',$update);
		purge_all_caches();
		$url = $CFG->wwwroot . "/local/tenant_appearance/tenant_appearance.php#logo";
	    redirect($url);
  
	}else{
		$newaddz = new stdClass();
		$newaddz->plugin='theme_remui';
		
		$newaddz->name=$name;
		$newaddz->value='/'.$a;

		$DB->insert_record('config_plugins', $newaddz);
		purge_all_caches();
		$url = $CFG->wwwroot . "/local/tenant_appearance/tenant_appearance.php#logo";
	    redirect($url);
	}
	
}	







