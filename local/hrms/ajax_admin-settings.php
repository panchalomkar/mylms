<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

$requesttype = optional_param('requesttype', '', PARAM_TEXT);

switch($requesttype) {
    case "add": 
        add();
    break;
} 


function add() {
    global $USER, $DB, $CFG;
    try {
        $admin1_id = optional_param('admin1_id', 0, PARAM_INT);  
        $admin2_id = optional_param('admin2_id', 0, PARAM_INT);  
        $admin3_id = optional_param('admin3_id', 0, PARAM_INT);  

        $admin_users = $DB->get_record_sql("SELECT id FROM `glms_hrms_admin_user`");
         
        if($admin_users){
            // update 
            $inslog = new stdClass();
            $inslog->id = $admin_users->id;
            $inslog->admin1_id = $admin1_id;
            $inslog->admin2_id = $admin2_id;
            $inslog->admin3_id = $admin3_id; 
            $inslog->updated_at = date("Y-m-d H:i:s");

            $dRES = $DB->update_record("hrms_admin_user", $inslog, false);

        } else {
            $inslog = new stdClass();
            $inslog->admin1_id = $admin1_id;
            $inslog->admin2_id = $admin2_id;
            $inslog->admin3_id = $admin3_id; 
            $inslog->created_at = date("Y-m-d H:i:s");

            $dRES = $DB->insert_record("hrms_admin_user", $inslog, true);

        }
        
        
        if($dRES) {
 
            echo json_encode([
                "success" => 1,
                "message" => "Successfully saved"
            ]); 
        } else {
            throw new \Exception("Sorry cannot process your request");
        }

        
    } catch(\Exception $e) {

        echo json_encode([
            "success" => 0,
            "message" => $e->getMessage()
        ]); 
    }
}