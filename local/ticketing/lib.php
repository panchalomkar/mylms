<?php

function save_data($post, $files, $userid) {
    global $DB, $CFG;

    $user = $DB->get_record('user', ['id' => $userid]);
    $post = (object) $post;
    $post->userid = $user->id;
    $post->timecreated = time();
    $post->status = 'Open';
    $post->department = get_tenant_detail($user);
    if ($tid = $DB->insert_record('ticketing', $post)) {


        //check if any image attached
        if (count($files['attachments']['name']) > 0) {
            $target = $CFG->dataroot . '/ticketing/';

            $allowed_image_extension = array(
                "png", "jpg", "jpeg", "svg"
            );
           // foreach ($files['attachments']['name'] as $key => $value) {

                // Get image file extension
                if ($files['attachments']['error'] == 0) {

                    $file_extension = pathinfo($files["attachments"]["name"], PATHINFO_EXTENSION);
                    if (in_array($file_extension, $allowed_image_extension)) {

                        $filename = time() . str_replace(' ', '', basename($files['attachments']['name']));
                        if (move_uploaded_file($files["attachments"]["tmp_name"], $target . $filename)) {

                            $type = mime_content_type($target . $filename);
                            $file = new stdClass();
                            $file->tid = $tid;
                            $file->file = $filename;
                            $file->type = $type;
                            $file->timecreated = time();
                            $DB->insert_record('ticketing_files', $file);
                        }
                    }
                }
           // }

            //send email
            send_email($post, $tid, $user);

            return true;
        }
    }
    return false;
}

function get_tenant_detail($user) {
    global $DB;

    $company_user = $DB->get_record('company_users', ['userid' => $user->id]);


    $company = $DB->get_record('company', ['id' => $company_user->companyid]);
    //get class name
    $sql = "SELECT * FROM mdl_role_assignments a INNER JOIN mdl_role r ON r.id = a.roleid WHERE a.userid = $user->id AND r.shortname = 'companyteacher'";
    $teacher = $DB->get_records_sql($sql);
    if ($teacher) {
        $department = $DB->get_record('department', array('id' => get_user_preferences('department')));
    } else {

        $department = $DB->get_record('department', array('id' => $company_user->departmentid));
    }
    $classname = $department->name;
    if ($department->parent > 0) {
        $parent = $DB->get_record('department', array('id' => $department->parent));
        $classname = $parent->name . '-' . $department->name;
    }

    return $company->name . ' ' . $classname;
}

function send_email($post, $tid, $user) {
    global $DB;

    $touser = new stdClass();
    $touser->id = 2;
    $touser->firstname = 'Team';
    $touser->lastname = 'Support';
    $touser->email = $post->assignto;

    $subject = 'Ticket Raised #' . $tid;
    $messagehtml = 'Hi Support Team,<br><br>
                        ' . $user->firstname . ' ' . $user->lastname . ' (' . $post->department . ') has raised a ticket with below details:<br><br>
                            
Title : ' . $post->title . '<br>
Description : ' . $post->description . '<br>
Issue Type : ' . $post->type . '<br>
Issue Type : ' . $post->priority . '<br>
Time : ' . date('d-m-Y', $post->timecreated) . '<br>
              ';
    list($filepath, $filename) = get_attachments_email($tid);

    email_to_user($touser, $user, $subject, $messagehtml, $messagehtml, $filepath, $filename, $usetrueaddress, $user->email);
}

function get_attachments($tid) {
    global $DB, $CFG;

    $files = $DB->get_records('ticketing_files', ['tid' => $tid]);
    $html = '<ul class="popup">';
    foreach ($files as $value) {
        //   $html .= '<li><img src="' . $CFG->dataroot . '/ticketing/' . $value->file . '"></li>';
        $html .= '<li><img src="img.php?name=' . $value->file . '" width="100"></li>';
    }
    $html .= '<ul>';
    return $html;
}

function get_attachments_email($tid) {
    global $DB, $CFG;
    $files = $DB->get_record('ticketing_files', ['tid' => $tid]);
 
//    $attachments = array();
//    foreach ($files as $value) {
//        $attachments[$value->file] = $CFG->dataroot . '/ticketing/' . $value->file;
//    }
    return array($CFG->dataroot . '/ticketing/' . $files->file, $files->file);
}

function sendcustommail($post, $tid) {

    global $DB, $USER;

    $touser = new stdClass();
    $touser->id = 2;
    $touser->firstname = 'Team';
    $touser->lastname = 'Support';
    $touser->email = $post->assignto;

    $subject = 'Ticket Raised #' . $tid;
    $messagehtml = 'Hi Support Team,<br><br>
                        ' . $USER->firstname . ' ' . $USER->lastname . ' (' . $post->department . ') has raised a ticket with below details<br><br>
                            
Title : ' . $post->title . '<br>
Description : ' . $post->description . '<br>
Issue Type : ' . $post->type . '<br>
Issue Type : ' . $post->priority . '<br>
Time : ' . date('d-m-Y', $post->timecreated) . '<br>
              ';
    $attachments = get_attachments_email($tid);

    // Load Moodle libraries and set up email data

    global $CFG, $PAGE;
    $PAGE->set_context(context_system::instance());
    $email = new \core\email\message();
    $email->from($USER->email);
    $email->to($post->assignto);
    $email->subject($subject);
    $email->body($messagehtml);

// Add attachments

    foreach ($attachments as $key => $attachment) {
        $file = new \stdClass();
        $file->filepath = $attachment;
        $file->filename = $key;
        $file->mimetype = mime_content_type($attachment);
        $email->attach($file);
    }

// Send the email
    $email->send();
}
