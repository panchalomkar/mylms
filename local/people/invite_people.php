<?php
require( '../../config.php' );
require_once($CFG->dirroot . "/user/lib.php");
require_once($CFG->dirroot . "/user/profile/lib.php");
global $DB, $CFG, $USER;


define('AJAX_SCRIPT', true);

class invite_people {

    var $email;
    var $log;

    function is_valid() {
        return ( preg_match("/^[0-9a-z._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i", $this->get_email()) ) ? true : false;
    }

    function set_email($email = "") {
        $this->email = $email;
    }

    function get_email() {
        return $this->email;
    }

    function set_log($log = false) {
        $this->log = $log;
    }

    function store_log($msg) {
        if ($this->log === true) {
            error_log($msg . PHP_EOL, 3, "./log_" . date('Y-m-d') . ".txt");
        }
    }

    function add_object_vars($po_target, $po_data) {
        if (is_object($po_data) && is_object($po_target)) {
            foreach (array_keys(get_object_vars($po_data)) as $lc_attribute) {
                $po_target->{$lc_attribute} = $po_data->{$lc_attribute};
            }
        }
        return $po_target;
    }

    function render_mail($data, $template) {
        if (preg_match_all("/\{([a-z\_]{1,})\}/i", $template, $la_result)) {
            if (count($la_result) > 0) {
                foreach (array_values($la_result[1]) as $lc_Attribute) {
                    if (property_exists($data, $lc_Attribute)) {
                        $template = preg_replace("/\{" . $lc_Attribute . "\}/", $data->{$lc_Attribute}, $template);
                    }
                }
            }
        }
        return $template;
    }

    function notifications_log($message) {
        GLOBAL $DB, $USER;
        $data = new stdClass();
        $data->time = time();
        $data->userid = $USER->id;
        $data->ip = $_SERVER['REMOTE_ADDR'];
        $data->course = 1;
        $data->module = 'people';
        $data->cmid = 0;
        $data->action = 'invite';
        $data->url = 'invite';
        $data->info = $message;
        try {
            $DB->insert_record('log', $data);
            $this->store_log("invite sent to " . $this->get_email() . " - " . $message);
        } catch (Exception $e) {
            $this->store_log("error saving log " . $e->getMessage());
        }
    }

    function send_mail($user) {
        global $CFG, $USER, $SITE;
        if ($this->is_valid() === true) {
            // assign values to create a new user
            $site = get_site();
            $row = $user;
            $row->platform_name = $site->fullname;
            $row->register_url = $CFG->wwwroot;
            $subject = $this->render_mail($row, get_string('mail_subject_invite', 'local_people'));
            // render template
            $body = $this->render_mail($row, get_string('mail_template_invite', 'local_people'));
            
            try {
                $mail = email_to_user($user, $site->shortname, $subject, $body, $body);
            } catch (Exception $e) {
                $this->store_log("error mail student " . $e->getMessage());
            }
            
            if (!$mail) {
                $this->notifications_log($mail->ErrorInfo);
                return false;
            } else {
                $this->notifications_log('Invitation sent');
                return true;
            }
        } else {
            $this->notifications_log('not valid email ' . $this->get_email());
            return false;
        }
    }
}

// define status string
$la_status = array('status' => false);

if (isset($_POST['email']) && $_POST['email'] != "") {
    $invite_mail = new invite_people();
    $invite_mail->set_log();
    $invite_mail->set_email($_POST['email']);
    $invite_mail->store_log($invite_mail->get_email());
    if ($invite_mail->is_valid() === true) {
        if ($DB->record_exists('user', array('username' => $invite_mail->get_email(), 'mnethostid' => $CFG->mnet_localhost_id))) {
            $la_status['status'] = 'exist';
        } else {
            /**
             * the global variable makes a bug that change the value of the object $user
             * and the function send changes the name, the new user have to set 
             * empty name values.
             * @author Hugo E.
             * @since  May 21/2018
             * @ticket 1716
             * @paradiso
             */
            // create user
            $user = new stdClass();
            $password = generate_password();
            
            $user->email = $invite_mail->get_email();
            $user->firstname = '';
            $user->lastname = '';
            $user->username = $invite_mail->get_email();
            $user->password = $password;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->confirmed = 1;
            $user->id = user_create_user($user, true, false);
            
            // validate send process    
            if($user->id > 0){
                $user->password = $password;
                $result = $invite_mail->send_mail($user);
            }

            // reasign variables
            $la_status['status'] = $result;
        }
    }
}
echo json_encode($la_status);