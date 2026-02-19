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
        $remarks = optional_param('remarks', '', PARAM_TEXT);
        $terms_and_conditions = required_param('terms_and_conditions', PARAM_TEXT);

        $itinerary_array = [];

        $name_outbound = optional_param('name_outbound', '', PARAM_TEXT); 
        $is_travelling_self_outbound = optional_param('is_travelling_self_outbound', '', PARAM_TEXT);
        $from_city_outbound = optional_param('from_city_outbound', '', PARAM_TEXT);
        $to_city_outbound = optional_param('to_city_outbound', '', PARAM_TEXT);
        $day_outbound = optional_param('day_outbound', '', PARAM_TEXT);
        $date_outbound = optional_param('date_outbound', '', PARAM_TEXT);
        $time_outbound = optional_param('time_outbound', '', PARAM_TEXT);

        if($is_travelling_self_outbound == "on") {
            if(empty($from_city_outbound)) {
                throw new \Exception("From City Required for Self Outbound");
            }

            if(empty($to_city_outbound)) {
                throw new \Exception("To City Required for Self Outbound");
            }

            if(empty($day_outbound)) {
                throw new \Exception("Day Required for Self Outbound");
            }

            if(empty($date_outbound)) {
                throw new \Exception("Date Required for Self Outbound");
            }

            if(empty($time_outbound)) {
                throw new \Exception("Time Required for Self Outbound");
            }

            $itinerary_array[] = [
                "itinerary_for" => "SELF",
                "itinerary_type" => "OUTBOUND",
                "name" => $name_outbound,
                "from_city" => $from_city_outbound,
                "to_city" => $to_city_outbound,
                "travel_day" => $day_outbound,
                "travel_date" => $date_outbound,
                "travel_time" => $time_outbound,
            ];
        }


        $name_inbound = optional_param('name_inbound', '', PARAM_TEXT); 
        $is_travelling_self_inbound = optional_param('is_travelling_self_inbound', '', PARAM_TEXT);
        $from_city_inbound = optional_param('from_city_inbound', '', PARAM_TEXT);
        $to_city_inbound = optional_param('to_city_inbound', '', PARAM_TEXT);
        $day_inbound = optional_param('day_inbound', '', PARAM_TEXT);
        $date_inbound = optional_param('date_inbound', '', PARAM_TEXT);
        $time_inbound = optional_param('time_inbound', '', PARAM_TEXT);

        if($is_travelling_self_inbound == "on") {
            if(empty($from_city_inbound)) {
                throw new \Exception("From City Required for Self Inbound");
            }

            if(empty($to_city_inbound)) {
                throw new \Exception("To City Required for Self Inbound");
            }

            if(empty($day_inbound)) {
                throw new \Exception("Day Required for Self Inbound");
            }

            if(empty($date_inbound)) {
                throw new \Exception("Date Required for Self Inbound");
            }

            if(empty($time_inbound)) {
                throw new \Exception("Time Required for Self Inbound");
            }

            $itinerary_array[] = [
                "itinerary_for" => "SELF",
                "itinerary_type" => "INBOUND",
                "name" => $name_inbound,
                "from_city" => $from_city_inbound,
                "to_city" => $to_city_inbound,
                "travel_day" => $day_inbound,
                "travel_date" => $date_inbound,
                "travel_time" => $time_inbound,
            ];
        }


        $name_family_outbound_array = optional_param('name_family_outbound', '', PARAM_TEXT);
        $is_travelling_family_outbound_array = optional_param('is_travelling_family_outbound', '', PARAM_TEXT); //on
        $from_city_family_outbound_array = optional_param('from_city_family_outbound', '', PARAM_TEXT);
        $to_city_family_outbound_array = optional_param('to_city_family_outbound', '', PARAM_TEXT);
        $day_family_outbound_array = optional_param('day_family_outbound', '', PARAM_TEXT);
        $date_family_outbound_array = optional_param('date_family_outbound', '', PARAM_TEXT);
        $time_family_outbound_array = optional_param('time_family_outbound', '', PARAM_TEXT);

        foreach($name_family_outbound_array as $oidx => $name_family_outbound) { 
            $is_travelling_family_outbound = $is_travelling_family_outbound_array[$oidx];
            $from_city_family_outbound = $from_city_family_outbound_array[$oidx];
            $to_city_family_outbound = $to_city_family_outbound_array[$oidx];
            $day_family_outbound = $day_family_outbound_array[$oidx];
            $date_family_outbound = $date_family_outbound_array[$oidx];
            $time_family_outbound = $time_family_outbound_array[$oidx];


            if($is_travelling_family_outbound == "on") {

                if(empty($name_family_outbound)) {
                    throw new \Exception("Name Required for Family Outbound");
                }

                if(empty($from_city_family_outbound)) {
                    throw new \Exception("From City Required for Family Outbound");
                }
    
                if(empty($to_city_family_outbound)) {
                    throw new \Exception("To City Required for Family Outbound");
                }
    
                if(empty($day_family_outbound)) {
                    throw new \Exception("Day Required for Family Outbound");
                }
    
                if(empty($date_family_outbound)) {
                    throw new \Exception("Date Required for Family Outbound");
                }
    
                if(empty($time_family_outbound)) {
                    throw new \Exception("Time Required for Family Outbound");
                }
            }

            $itinerary_array[] = [
                "itinerary_for" => "FAMILY",
                "itinerary_type" => "OUTBOUND",
                "name" => $name_family_outbound,
                "from_city" => $from_city_family_outbound,
                "to_city" => $to_city_family_outbound,
                "travel_day" => $day_family_outbound,
                "travel_date" => $date_family_outbound,
                "travel_time" => $time_family_outbound,
            ];

        }

        $name_family_inbound_array = optional_param('name_family_inbound', '', PARAM_TEXT);
        $is_travelling_family_inbound_array = optional_param('is_travelling_family_inbound', '', PARAM_TEXT); //on
        $from_city_family_inbound_array = optional_param('from_city_family_inbound', '', PARAM_TEXT);
        $to_city_family_inbound_array = optional_param('to_city_family_inbound', '', PARAM_TEXT);
        $day_family_inbound_array = optional_param('day_family_inbound', '', PARAM_TEXT);
        $date_family_inbound_array = optional_param('date_family_inbound', '', PARAM_TEXT);
        $time_family_inbound_array = optional_param('time_family_inbound', '', PARAM_TEXT);

        foreach($name_family_inbound_array as $oidx => $name_family_inbound) { 
            $is_travelling_family_inbound = $is_travelling_family_inbound_array[$oidx];
            $from_city_family_inbound = $from_city_family_inbound_array[$oidx];
            $to_city_family_inbound = $to_city_family_inbound_array[$oidx];
            $day_family_inbound = $day_family_inbound_array[$oidx];
            $date_family_inbound = $date_family_inbound_array[$oidx];
            $time_family_inbound = $time_family_inbound_array[$oidx];


            if($is_travelling_family_inbound == "on") {

                if(empty($name_family_inbound)) {
                    throw new \Exception("Name Required for Family Inbound");
                }

                if(empty($from_city_family_inbound)) {
                    throw new \Exception("From City Required for Family Inbound");
                }
    
                if(empty($to_city_family_inbound)) {
                    throw new \Exception("To City Required for Family Inbound");
                }
    
                if(empty($day_family_inbound)) {
                    throw new \Exception("Day Required for Family Inbound");
                }
    
                if(empty($date_family_inbound)) {
                    throw new \Exception("Date Required for Family Inbound");
                }
    
                if(empty($time_family_inbound)) {
                    throw new \Exception("Time Required for Family Inbound");
                }

                $itinerary_array[] = [
                    "itinerary_for" => "FAMILY",
                    "itinerary_type" => "INBOUND",
                    "name" => $name_family_inbound,
                    "from_city" => $from_city_family_inbound,
                    "to_city" => $to_city_family_inbound,
                    "travel_day" => $day_family_inbound,
                    "travel_date" => $date_family_inbound,
                    "travel_time" => $time_family_inbound,
                ];
            }

        }

        if(count($itinerary_array) == 0) {
            throw new \Exception("Details are empty. Please fill the fields.");
        }

        $inslog = new stdClass();
        $inslog->user_id = $USER->id;
        $inslog->remarks = $remarks;
        $inslog->created_at = date("Y-m-d H:i:s");

        $dRES = $DB->insert_record("user_airticket_request", $inslog, true);

        if($dRES) {

            addAdminDataHrms(1, 'airticket-request', $dRES);

            foreach($itinerary_array as $itinerary) {
                $inslogsub = new stdClass();
                $inslogsub->ref_id = $dRES;
                $inslogsub->itinerary_for = $itinerary['itinerary_for'];
                $inslogsub->itinerary_type = $itinerary['itinerary_type'];
                $inslogsub->name = $itinerary['name'];
                $inslogsub->from_city = $itinerary['from_city'];
                $inslogsub->to_city = $itinerary['to_city'];
                $inslogsub->travel_day = $itinerary['travel_day'];
                $inslogsub->travel_date = $itinerary['travel_date'];
                $inslogsub->travel_time = $itinerary['travel_time'];

                $DB->insert_record("user_airticket_detail", $inslogsub);
            }



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
}