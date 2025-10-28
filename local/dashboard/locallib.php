<?php
//define('CONTEXT_USER', 50);
function rolewise_dashboard($event){
    global $CFG,$DB, $SITE,$USER;

        $eventdata = $event->get_data();
       
        $user = \core_user::get_user($eventdata['objectid']);
        if (user_has_role_assignment($USER->id,4)){
            redirect($CFG->wwwroot ."/local/dashboard/teacher_dashboard.php");
        }elseif (user_has_role_assignment($USER->id,16)){
            redirect($CFG->wwwroot ."/local/dashboard/parent_dashboard.php");
        }elseif (user_has_role_assignment($USER->id,1)){
            redirect($CFG->wwwroot ."/local/dashboard/supervisor_dashboard.php");
        }elseif (user_has_role_assignment($USER->id,6)){
        $allusernames = get_all_user_name_fields(true, 'u');

        $usercontexts = $DB->get_records_sql("SELECT * 
        FROM mdl_context
        WHERE instanceid = ? AND contextlevel = ".CONTEXT_USER, array($USER->id));
        foreach($usercontexts as $usercontextsval){
             $usercontexts_arr = $usercontextsval->id;
        }
        
        $userroleassigncntx = $DB->get_records_sql("SELECT userid
            FROM mdl_role_assignments
            WHERE contextid = ?", array($usercontexts_arr));
        foreach($userroleassigncntx as $userroleassigncntxval){
             $userroleassigncntx_arr = $userroleassigncntxval->userid;
        }
        //echo "***".$userroleassigncntx_arr;die;
         $parentData = $DB->get_record("user", array('id' => $userroleassigncntx_arr), '*', MUST_EXIST);
         $userData = $DB->get_record("user", array('id' => $USER->id ), '*', MUST_EXIST);
         send_login_notif_mail($parentData,$userData);
            
            redirect($CFG->wwwroot ."/local/dashboard/child_dashboard.php");
        }
}

function send_login_notif_mail($parentData,$userData) {
    global $DB;
    if ($parentData->email) {
        $email_subject = get_string('login_subject', 'local_dashboard');

        // Render template
        //$body = get_string('login_body', 'local_dashboard');
        $body = 'Your child "'.$userData->firstname.' '.$userData->lastname.'" has logged in at'.userdate($userData->currentlogin);

        try {
            $site = get_site();
            $messagehtml = text_to_html($body, false, false, true);
            $mail = email_to_user($parentData, $site->shortname, $email_subject, $body, $messagehtml);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
