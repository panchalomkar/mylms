<?php

class notification_task  extends \core\task\scheduled_task {
    public function get_name() {
        return 'Evaluation process';
    }
    public function execute() {
        notification_to_parent();
    }

    function notification_to_parent(){
    global $DB, $USER;
    $courseSql = $DB->get_records('course',array('visible' => '1'));
    foreach ($courseSql as $course) {
              
       $context = context_course::instance($course->id);
        $students = get_role_users(5 , $context);
        foreach ($students as $data) {
            $users[] = $data;
            $usersArr[] = $data->id;

        }
    }
                $sessionList = getchildliveClasses($usersArr,"today");
               $date = new DateTime("now");
                $currentTime = $date->getTimestamp();

                $now = date('H',$currentTime);
                                                   
                foreach ($sessionList as $session) { 
                    
                $from_time = $session->openingtime;
                $totalMin = round(abs($currentTime - $from_time) / 60,0);

                $start = date("H", $session->openingtime);
                $endtime = date("H", $session->closingtime);
                                              
                $participants = json_decode($session->participants);
                foreach($participants as $user){
                    if($user->selectiontype == 'user' || $user->selectiontype == 'all' && $now >= $start && $now <= $endtime && $totalMin > 15 ){
                        $sqlBBLog = $parentSql = "SELECT userid FROM {bigbluebuttonbn_logs} 
                                    WHERE bigbluebuttonbnid = :bigbluebuttonbnid AND userid = :userid AND log ='Join'";
                        if($user->selectiontype == 'all'){
                                $context1 = context_course::instance($session->course);
                                $studentsLog = get_role_users(5 , $context1);
                               
                                foreach ($studentsLog as $sdata) {
                                     $hasinLog = $DB->get_record_sql($sqlBBLog,['bigbluebuttonbnid'=> $session->id, 'userid' => $sdata->id]);
                                }
                        } else {
                            $hasinLog = $DB->get_record_sql($sqlBBLog,['bigbluebuttonbnid'=> $session->id, 'userid' => $user->selectionid]);
                        }
                       
                        if(empty($hasinLog)){
                            if($user->selectiontype == 'all'){
                                foreach ($users as $user) {
                                   $userArr = $DB->get_record('user',array('id' =>  $user->id), '*', MUST_EXIST);
                                }
                                
                            } else {
                                $userArr = $DB->get_record('user',array('id' =>  $hasinLog->userid), '*', MUST_EXIST);
                            }
                            
                            send_notif_mail($userArr,$session->name);
                        }

                    }
                }
            }
    }

function send_notif_mail($userData,$session) {
    global $DB;
    
    
        $email_subject = "Live Class Notification";

        // Render template
        //$body = get_string('login_body', 'local_dashboard');
        $body = "Your child $userData->firstname has not join the ".$session;

        try {
            $site = get_site();
            $mail = email_to_user($userData, $site->shortname, $email_subject, $body, $body);

        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    
}
}

