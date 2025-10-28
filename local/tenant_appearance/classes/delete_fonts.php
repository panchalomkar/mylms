<?php
/**
 * Delete file operations.
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
global $CFG, $DB;
require_once('../../../config.php');

define('AJAX_SCRIPT', true);

require_login();

$fontfile  = optional_param('filename','', PARAM_TEXT);
$fontindex = optional_param('fileindex', 0, PARAM_INT);

$id= '';
if(!empty($SESSION->currenteditingcompany)){
    $id = $SESSION->currenteditingcompany;
}else if(\iomad::is_company_user()){
    $id = \iomad::is_company_user();
}

$target_dir = $CFG->dirroot ."/theme/paradiso/fonts/$fontfile";


    if($id) {
        if($fontfile) {
        unlink($target_dir);
        $DB->delete_records("font_upload_setting",array('id'=>$fontindex));
        $fontnameval = $DB->get_record('config_plugins', array('value' => 'id_'.$fontindex, 'name' => 'fontnametheme_'.$id));
        if(isset($fontnameval->id)) {
        $update = new stdClass();
        $update->plugin='theme_remui';
        $update->name='fontnametheme_'.$id;
        $update->value='Poppins';
        $update->id=$fontnameval->id;
        $DB->update_record('config_plugins',$update);
        }
    echo "Success";
    }
} else {
        if($fontfile) {
        unlink($target_dir);
        $DB->delete_records("font_upload_setting",array('id'=>$fontindex));
        $fontnameval = $DB->get_record('config_plugins', array('value' => 'id_'.$fontindex, 'name' => 'fontnametheme'));
        if(isset($fontnameval->id)) {
        $update = new stdClass();
        $update->plugin='theme_remui';
        $update->name='fontnametheme';
        $update->value='Poppins';
        $update->id=$fontnameval->id;
        $DB->update_record('config_plugins',$update);
        }
        echo "Success";
    }
}
//For catche purge
theme_reset_all_caches();

