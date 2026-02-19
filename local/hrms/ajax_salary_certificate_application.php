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
        $purpose_of_application = required_param('purpose_of_application', PARAM_TEXT);  
        $other_purpose_of_application = optional_param('other_purpose_of_application', '', PARAM_TEXT);  

        if(strtolower($purpose_of_application) == "others") {
            $purpose_of_application = $other_purpose_of_application;
        }

        $company_address = required_param('company_address', PARAM_TEXT);  
        $address = required_param('address', PARAM_TEXT); 
        $city = required_param('city', PARAM_TEXT); 
        $country = required_param('country', PARAM_TEXT); 
        
        $inslog = new stdClass();
        $inslog->user_id = $USER->id; 
        $inslog->purpose_of_application = $purpose_of_application;
        $inslog->company_address = $company_address;
        $inslog->address = $address;
        $inslog->city = $city;
        $inslog->country = $country; 
        $inslog->created_at = date("Y-m-d H:i:s");

        $dRES = $DB->insert_record("user_salary_certificate_application", $inslog, true);

        if($dRES) {
            addAdminDataHrms(1, 'salary-certificate-application', $dRES);

            echo json_encode([
                "success" => 1,
                "message" => "Successfully applied"
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

    // print_r($_REQUEST);

    /**
     * id
     * user_id
     * purpose_of_application
     * company_address
     * address
     * city
     * country 
     * 
     * approval_status - pending/approved/rejected
     * remarks 
     * created_at
     * updated_at
     */
    

}