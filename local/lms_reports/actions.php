<?php 

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
global $CFG,$DB;  

$task = optional_param('task', null, PARAM_ALPHA);
$data = new report_overviewstats(); 

switch($task){
	case 'delete':
		$id = optional_param('id', null, PARAM_INT);
		$idcr = optional_param('idcr', null, PARAM_INT);
		
		$return=$data->delete_report($id); 
		if($return){
			echo json_encode( array('success'=>true, 'id' => $id ));
		}
	break;
	case 'dest':
		$id = optional_param('id', null, PARAM_INT);
		$fav = optional_param('fav', null, PARAM_INT);
		$mfd = optional_param('mfd', null, PARAM_INT);
		
		$return = $data->update_fav((empty($fav)?1:0) ,$id);
		$menuhtml = '';
		if( $mfd === 0 ){
			$menu = $data->get_menu_reports();
			$menuhtml .= $data->get_accordion_html($menu);
		}
		
		if($return){
			echo json_encode( array('success'=>true, 'id' => $id, 'fav' => (empty($fav)?1:0), 'mfd' => $menuhtml,  ));
		}
	break;
	case 'searchreport':
		//$DB->set_debug(true);
		/* Modfied By Sunita 
                  Date:9 August 2017
                  To resolve search issue on multiple key values ticket #233
                */ 
                
                $txt = optional_param('txt', null, PARAM_TEXT);
                $txt = strtolower($txt);
                $txt = str_replace(' ', '_', $txt);
		$menu=$data->get_menu_reports($txt);
                
		if(!empty($menu)){
			$html=$data->get_accordion_html($menu,$txt);  
		}
		echo json_encode( array('success'=>(empty($html)?false:true), 'menu' => $html ));
	break;
        case 'checkreportname':
            $text = optional_param('txt', null, PARAM_TEXT);
            if ($DB->record_exists_sql("SELECT * FROM {block_configurable_reports} where name = '$text'  AND visible = 1")) {
                $status = array("success"=>true,"message"=>get_string('reportexists', 'block_configurable_reports'));
            } else {
                $status = array("success" => false);
            }
            echo json_encode($status);
        break;
        
        default:
        break;
}


?>