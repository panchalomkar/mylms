<?php

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $CFG, $USER, $DB, $OUTPUT, $PAGE;

$currentuser = $argv[1]; // Admin user id
$companyid = $argv[2]; // Company id default 0
$mid = $argv[3]; // Message id

// Send csv report to $USER email (0 to disable)
$sendreport = false;
if(get_config('local_social_wall', 'getemailreport')){
    $sendreport = true;
}

$email = $DB->get_record('user', ['id'=>$currentuser]);
if(empty($email)){
    return true;
}
$from_email = generate_email_user($CFG->noreplyaddress);

$iscompany = '';
$where = '';
if(!empty($companyid) && $companyid > 0){
    $iscompany = '';
    $where = 'WHERE companyid = ' . $companyid;
} else {
    $iscompany = 'NOT';
    $where = '';
}

$sql = "SELECT *
        FROM {user} u
        WHERE
            u.deleted = 0
            AND u.suspended = 0
            AND u.confirmed = 1
            AND u.username <> 'guest'
            AND u.id $iscompany IN (SELECT userid FROM {company_users} $where)";

$users = $DB->get_records_sql($sql);
$totalusers = count($users);

$csv_output = "";

$subject = get_string('socialwalladminsubject', 'local_social_wall');
$messagecontent = $DB->get_field('social_wall_messages', 'message', ['id'=>$mid]);
$messageArr =   json_decode($messagecontent);
$content    =   $messageArr->text;

if($companyid){
    $company = $DB->get_record('company', ['id'=>$companyid]);
    $companyname = $company->name;
    $getlogo = new \theme_remui\output\core_renderer($PAGE);
    $logourl = $getlogo->get_tenant_logo_url($companyid);
    $logourl = str_replace('//', 'https://', $logourl);
} else {
    $company = $DB->get_record('course', ['id'=>1]);
    $companyname = $company->fullname;
    $getlogo = new \theme_remui\output\core_renderer($PAGE);
    $logourl = $getlogo->get_logo_url(null, 75, false);
}

$data['bodyheading'] = get_string('socialwalladminmessage', 'local_social_wall');
$data['admin_name'] = 'Admin User';
$data['content'] = $content;
$data['timestamp'] = time();
$data['action_url'] = new moodle_url('/local/social_wall', ['id'=>78, 't'=>'announcement']);
$data['companyname'] = $companyname;
$data['logourl'] = $logourl;
// $announcement = $OUTPUT->render_from_template('local_social_wall/announcement', $data);
$announcement = prepare_email_template($data);

mtrace("Processign email");
foreach ($users as $user) {
     // change $email to $user below
    if(email_to_user($user, $from_email, $subject, $announcement, text_to_html($announcement))){
        $status = 'sent';
    } else {
        $status = 'failed';
    }
    if($sendreport){
        $csv_output .= create_csv_report($user, $status);
    }
}
mtrace("Done email processign");

if($sendreport){
    send_report_to_admin($csv_output, $email);
}


function create_csv_report($user, $status){
    $csv_output .= '"' . str_replace('"', '""',str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($user->firstname))))).'",';
    $csv_output .= '"' . str_replace('"', '""',str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($user->lastname))))).'",';
    $csv_output .= '"' . str_replace('"', '""',str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($user->email))))).'",';
    $csv_output .= '"' . str_replace('"', '""',str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($status))))).'",';
    $csv_output .= "\n";
    return $csv_output;
}

function send_report_to_admin($csv_output, $email){
    global $CFG;

    $filename = 'report';
    $extension = '.csv';
    $path = $CFG->tempdir;

    $csv_output_header .= get_string('firstname').',';
    $csv_output_header .= get_string('lastname').',';
    $csv_output_header .= get_string('email').',';
    $csv_output_header .= get_string('status').',';
    $csv_output_header .= "\n";

    $csv_data = $csv_output_header . $csv_output;

    $filename = clean_filename($filename);
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= clean_filename("-comma_separated");
    $filename .= $extension;
    
    $uploadfile = $path .'/files/' . $filename;
    file_put_contents($uploadfile, $csv_data);
    

    $subject = 'Post notify email report';
    $message = 'Please find the attached report';

    // Send Email report to admin
    $mail = email_to_user($email, $from_email, $subject, $message, text_to_html($message), $uploadfile, $filename);
    mtrace("Report sent to admin");

    shell_exec('rm -rf '.$uploadfile);
    mtrace("Report file removed from temp");
    exit();
}

function prepare_email_template($data){
    return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="x-apple-disable-message-reformatting" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="color-scheme" content="light dark" />
        <meta name="supported-color-schemes" content="light dark" />
        <title></title>
        
      </head>
      <body style="width: 100% !important; height: 100%; margin: 0; -webkit-text-size-adjust: none; background-color: #F2F4F6; color: #51545E;">
        <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 0; padding: 0; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #F2F4F6;">
          <tr>
            <td align="center" style="word-break: break-word; font-family: \'Nunito Sans\', Helvetica, Arial, sans-serif; font-size: 16px;">
              <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 0; padding: 0; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                <tr>
                  <td class="email-masthead" style="word-break: break-word; font-family: \'Nunito Sans\', Helvetica, Arial, sans-serif; font-size: 16px; padding: 25px 0; text-align: center;">
                    <img src="'.$data['logourl'].'" alt="'.$data['companyname'].'" class="img-fluid" height="75">
                  </td>
                </tr>
                <!-- Email Body -->
                <tr>
                  <td class="email-body" width="570" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0; padding: 0;-premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; word-break: break-word; font-family: \'Nunito Sans\', Helvetica, Arial, sans-serif; font-size: 16px;">
                    <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; margin: 0 auto; padding: 0; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #FFFFFF;">
                      <!-- Body content -->
                      <tr>
                        <td class="content-cell" style="word-break: break-word; font-family: \'Nunito Sans\', Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
                          <div class="f-fallback">
                            <h4>'.$data['bodyheading'].'</h4>
                            <div style="background-color: #F4F4F7; padding: 16px;">
                              '.$data['content'].'
                            </div>
                            <p style="margin: .4em 0 1.1875em; font-size: 16px; line-height: 1.625; color: #51545E;">By '.$data['admin_name'].' at '. userdate($data['timestamp']).'</p>
                            <p style="margin: .4em 0 1.1875em; font-size: 13px; line-height: 1.625; color: #51545E;"><a href="'.$data['action_url'].'" style="color: #3869D4; margin: .4em 0 1.1875em; line-height: 1.625;">'. get_string('viewthepost', 'local_social_wall').'</a></p>
                          </div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td>
                    <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; margin: 0 auto; padding: 0; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; text-align: center;">
                      <tr>
                        <td class="content-cell" align="center" style="word-break: break-word; font-family: \'Nunito Sans\', Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
                          <p class="f-fallback sub" style="margin: .4em 0 1.1875em; font-size: 16px; line-height: 1.625; text-align: center; color: #A8AAAF;">'.$data['companyname'].'</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </body>
    </html>';
}