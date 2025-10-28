<?php
/**
 * Saving Slideshow images.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */

define('CLI_SCRIPT', true);

require_once($CFG->libdir.'/clilib.php');
require_once("{$CFG->dirroot}/local/tenant_appearance/classes/slideshow_form.php");

$mforms = new slideshow_form();
$filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => array('.png', '.jpg'));

//Check current editing comapny
$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
	$id = \iomad::is_company_user();
} 

if ($mforms->is_cancelled()) {

} else if ($data = $mforms->get_data()) {
	
	$showslideshow='showslideshow_'.$id;

	//Title
	$slide1title = 'slide1title_'.$id;
	$slide2title = 'slide2title_'.$id;
	$slide3title = 'slide3title_'.$id;

	//Content
	$slide1content = 'slide1content_'.$id;
	$slide2content = 'slide2content_'.$id;
	$slide3content = 'slide3content_'.$id;
	
	//Image
	$slide1image = 'slide1image_'.$id;
	$slide2image = 'slide2image_'.$id;
	$slide3image = 'slide3image_'.$id;

	if (empty($data->$showslideshow)) {
		$data->$showslideshow= 0;
	}
	
	//title
	$onetitle = $slide1title;
	$twotitle = $slide2title;
	$threestitle = $slide3title;
   
	//content
	$onecontent = $slide1content;
	$twocontent = $slide2content;
	$threecontent = $slide3content;

	//slide
	$oneslide = $slide1image;
	$twoslide = $slide2image;
	$threeslide = $slide3image;
	
	
    //Prepare draft area for images
	file_save_draft_area_files($data->$oneslide, 1, 'theme_remui', $oneslide, 0, $filemanageroptions);
	
	file_save_draft_area_files($data->$twoslide, 1, 'theme_remui', $twoslide, 0, $filemanageroptions);
	
	file_save_draft_area_files($data->$threeslide, 1, 'theme_remui', $threeslide, 0, $filemanageroptions);


	//save slide activation
	$slideact = $DB->get_record('config_plugins',array('name' =>$showslideshow));
	$slideactins = new stdClass();
	$slideactins->plugin='theme_remui';
	$slideactins->name=$showslideshow;
	$slideactins->value=$data->$showslideshow;
	if(!empty($slideact)){
		$slideactins->id=$slideact->id;
   		$DB->update_record('config_plugins',$slideactins);
	}else{
        $DB->insert_record('config_plugins', $slideactins);
	}
	
	//save slide title
	$slide1title = $DB->get_record('config_plugins',array('name' =>$onetitle));
	$stone = new stdClass();
	$stone->plugin='theme_remui';
	$stone->name=$onetitle;
	$stone->value=$data->$onetitle;

	if(!empty($slide1title)){
		$stone->id=$slide1title->id;
	    $DB->update_record('config_plugins',$stone);
	}else{
		$DB->insert_record('config_plugins', $stone);
	}
	
   	//slide2tilte
	$slide2title = $DB->get_record('config_plugins',array('name' =>$twotitle));
	$sttwo = new stdClass();
	$sttwo->plugin='theme_remui';
	$sttwo->name=$twotitle;
	$sttwo->value=$data->$twotitle;
	if(!empty($slide2title)){
		$sttwo->id=$slide2title->id;
	    $DB->update_record('config_plugins',$sttwo);
	}else{
		$DB->insert_record('config_plugins', $sttwo);
	}

	//slide3title
	$slide3title = $DB->get_record('config_plugins',array('name' =>$threestitle));
	$stthree = new stdClass();
	$stthree->plugin='theme_remui';
	$stthree->name=$threestitle;
	$stthree->value=$data->$threestitle;
	if(!empty($slide3title)){	
		$stthree->id=$slide3title->id;
        $DB->update_record('config_plugins',$stthree);
	}else{
		$DB->insert_record('config_plugins', $stthree);
	}

	//slide content one
	$slide1content = $DB->get_record('config_plugins',array('name' =>$onecontent));
	$scone = new stdClass();
	$scone->plugin='theme_remui';
	$scone->name=$onecontent;
	$scone->value=$data->$onecontent['text'];

	if(!empty($slide1content)){
		$scone->id=$slide1content->id;
		$DB->update_record('config_plugins',$scone);
	}else{
		$DB->insert_record('config_plugins', $scone);
	}

  	//Slide content two
    $slide2content = $DB->get_record('config_plugins',array('name' =>$twocontent));
	$sctwo = new stdClass();
	$sctwo->plugin='theme_remui';
	$sctwo->name=$twocontent;
	$sctwo->value=$data->$twocontent['text'];

	if(!empty($slide2content)){
		$sctwo->id=$slide2content->id;
	    $DB->update_record('config_plugins',$sctwo);
	}else{
		$DB->insert_record('config_plugins', $sctwo);
	}

	//Slide content three
	$slide3content = $DB->get_record('config_plugins',array('name' =>$threecontent));
	$scthree = new stdClass();
	$scthree->plugin='theme_remui';
	$scthree->name=$threecontent;
	$scthree->value=$data->$threecontent['text'];

	if(!empty($slide3content)){
		$scthree->id=$slide3content->id;
		$DB->update_record('config_plugins',$scthree);
	}else{
		$DB->insert_record('config_plugins', $scthree);
	}

	if(!empty($data)){
		//Get filename for slide1 image
		$fs = get_file_storage();
		$files = $fs->get_area_files(1, 'theme_remui', $oneslide, 0, '', false);
		$file = reset($files);
		if(!empty($file)){
			$a=$file->get_filename();
		}else{
			$a='';
		}

		//Save slide one image								
		$slideone = $DB->get_record('config_plugins',array('name' =>$oneslide));
		$ione = new stdClass();
		$ione->plugin='theme_remui';
		$ione->name=$oneslide;
		if(!empty($slideone)){
			if($a) {
				$ione->value='/'.$a;	
			} else {
				$ione->value='';
			}
			$ione->id=$slideone->id;
			$DB->update_record('config_plugins',$ione);
			purge_all_caches();
		}else{
			$ione->value='/'.$a;
			$DB->insert_record('config_plugins', $ione);
			purge_all_caches();
		}

		//slide 2 image
		//Get filename for slide2 image
		$fs1 = get_file_storage();
		$files1 = $fs->get_area_files(1, 'theme_remui', $twoslide, 0, '', false);
		$file1 = reset($files1);
		if(!empty($file1)){
			$b=$file1->get_filename();
		}else{
			$b='';
		}

		//Save slide one image								
		$slidetwo = $DB->get_record('config_plugins',array('name' =>$twoslide));
		$itwo = new stdClass();
		$itwo->plugin='theme_remui';
		$itwo->name=$twoslide;
		if(!empty($slidetwo)){
			if($b){
				$itwo->value='/'.$b;	
			}else{
				$itwo->value='';
			}
			$itwo->id=$slidetwo->id;
			$DB->update_record('config_plugins',$itwo);
			purge_all_caches();
		}else{
			$itwo->value='/'.$b;
			$DB->insert_record('config_plugins', $itwo);
			purge_all_caches();
		}

		//slide 3 image
		//Get filename for slide3 image
		$fs2 = get_file_storage();
		$files2 = $fs->get_area_files(1, 'theme_remui', $threeslide, 0, '', false);
		$file2 = reset($files2);
		if(!empty($file2)){
			$c=$file2->get_filename();
		}else{
			$c='';
		}

		//Save slide three image								
		$slidethree = $DB->get_record('config_plugins',array('name' =>$threeslide));
		$ithree = new stdClass();
		$ithree->plugin='theme_remui';
		$ithree->name=$threeslide;
		if(!empty($slidethree)){
			if($c){
				$ithree->value='/'.$c;	
			}else{
	            $ithree->value='';
			}
			$ithree->id=$slidethree->id;
		    $DB->update_record('config_plugins',$ithree);
			purge_all_caches();
		}else{
			$ithree->value='/'.$c;
			$DB->insert_record('config_plugins', $ithree);
			purge_all_caches();
		}

	}

	$url = $CFG->wwwroot . "/local/tenant_appearance/tenant_appearance.php#slideshow";
	redirect($url);
}else{

	//Get record
	$slideshowact = $DB->get_record('config_plugins',array('name' =>'showslideshow_'.$id));
	$slide='showslideshow_'.$id;
	
	$slide1title = $DB->get_record('config_plugins',array('name' =>'slide1title_'.$id));
	
	$slide1t='slide1title_'.$id;
	
	$slide2title = $DB->get_record('config_plugins',array('name' =>'slide2title_'.$id));
	$slide2t='slide2title_'.$id;
	
	$slide3title = $DB->get_record('config_plugins',array('name' =>'slide3title_'.$id));
	$slide3t='slide3title_'.$id;

	//content1
	$slide1content = $DB->get_record('config_plugins',array('name' =>'slide1content_'.$id));
	$slide1c='slide1content_'.$id;
	
	//content2
	$slide2content = $DB->get_record('config_plugins',array('name' =>'slide2content_'.$id));
	$slide2ct='slide2content_'.$id;
	
    //content3
	$slide3content = $DB->get_record('config_plugins',array('name' =>'slide3content_'.$id));
	$slide3ct='slide3content_'.$id;
	
	$updaterecordz = new stdClass();
	$updaterecordz->$slide = $slideshowact->value;
	$updaterecordz->$slide1t = $slide1title->value;
	$updaterecordz->$slide2t = $slide2title->value;
	$updaterecordz->$slide3t = $slide3title->value;
	$updaterecordz->$slide1c[text] = $slide1content->value;
	$updaterecordz->$slide2ct[text] = $slide2content->value;
	$updaterecordz->$slide3ct[text] = $slide3content->value;
	
    $mforms->set_data($updaterecordz);
	
	$fileentry = $DB->get_record('files', array('contextid'=>1, 'component'=>'theme_remui', 'filearea'=>'slide1image_'.$id));

	$fileentrytwo = $DB->get_record('files', array('contextid'=>1, 'component'=>'theme_remui', 'filearea'=>'slide2image_'.$id));

	$fileentrythree = $DB->get_record('files', array('contextid'=>1, 'component'=>'theme_remui', 'filearea'=>'slide3image_'.$id));


		if (empty($fileentry->id)) {
			$entry = new stdClass;
			$draftitemid = 0;
		} else {
			$draftitemid = $fileentry->itemid;
		}

		if (empty($fileentrytwo->id)) {
			$entry = new stdClass;
			$draftitemid = 0;
		} else {
			$draftitemidtwo = $fileentrytwo->itemid;
		}

		if (empty($fileentrythree->id)) {
			$entry = new stdClass;
			$draftitemid = 0;
		} else {
			$draftitemidthree = $fileentrythree->itemid;
		}

		// Prepare filemanager draft area.
		file_prepare_draft_area($draftitemid, 1, 'theme_remui','slide1image_'.$id, 0, $filemanageroptions);
		$slide1='slide1image_'.$id;
		$entry->$slide1 = $draftitemid;

		// Prepare filemanager draft area.
		file_prepare_draft_area($draftitemidtwo, 1, 'theme_remui', 'slide2image_'.$id, 0, $filemanageroptions);
		$slide2='slide2image_'.$id;
		$entry->$slide2 = $draftitemidtwo;

		// Prepare filemanager draft area.
		file_prepare_draft_area($draftitemidthree, 1, 'theme_remui', 'slide3image_'.$id, 0, $filemanageroptions);
		$slide3='slide3image_'.$id;
		
		$entry->$slide3 = $draftitemidthree;

		$mforms->set_data($entry);
	
  
	}

	





