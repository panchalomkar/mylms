<?php
/**
 * Externallib 
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");

class local_tenant_appearance_external extends external_api {


    public static function delete_font($fileindex,$filename) {
        global $CFG, $USER,$DB;
        $params = self::validate_parameters(self::delete_font_parameters(),
                        array('fileindex' => $fileindex,'filename' => $filename));

        $fontindex  = $params['fileindex'];
        $fontfile   = $params['filename'];
       
        $responsedata = array();
        $responsedata['status'] = false;
        $id='';
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
                $fontnameval = $DB->get_record_sql('SELECT * FROM {config_plugins} WHERE value = ? AND name = ?', 
                ['id_'.$fontindex, 'fontnametheme_'.$id]);
                if(isset($fontnameval->id)) {
                $update = new stdClass();
                $update->plugin='theme_remui';
                $update->name='fontnametheme_'.$id;
                $update->value='Poppins';
                $update->id=$fontnameval->id;
                $DB->update_record('config_plugins',$update);
                }
                $responsedata['status'] = true;
            }
        } else {
            if($fontfile) {
                unlink($target_dir);
                $DB->delete_records("font_upload_setting",array('id'=>$fontindex));
                $fontnameval = $DB->get_record_sql('SELECT * FROM {config_plugins} WHERE value = ? AND name = ?', 
                ['id_'.$fontindex, 'fontnametheme_'.$id]);
                if(isset($fontnameval->id)) {
                $update = new stdClass();
                $update->plugin='theme_remui';
                $update->name='fontnametheme';
                $update->value='Poppins';
                $update->id=$fontnameval->id;
                $DB->update_record('config_plugins',$update);
                }
                $responsedata['status'] = true;
            }
        }
        //For catche purge
        theme_reset_all_caches();
        return $responsedata;
        
    }

    public static function delete_font_parameters() {
        return new external_function_parameters(
            array(
                'fileindex' => new external_value(PARAM_INT, 'file id'),
                'filename' => new external_value(PARAM_TEXT, 'filename')
            )
        );
    }

    public static function delete_font_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'True if the deleted was confirmed, false'),
            )
        );

    }
    
}
