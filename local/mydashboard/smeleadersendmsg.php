<?php
require_once('../../config.php');
global $USER, $DB, $SITE, $PAGE;
$studentid=$_POST['userid'];
//echo $studentid;
$textareavalue = $_POST['textareavalue'];
$PAGE->set_context(context_system::instance());
$userfrom = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
$userto = $DB->get_record('user', array('id' => $studentid), '*', MUST_EXIST);

$message = new \core\message\message();
$message->courseid = $SITE->id;
$message->component = 'moodle';
$message->name = 'instantmessage';
$message->userfrom = $userfrom;
$message->userto = $userto;
$message->subject = 'send message';
$message->fullmessage = "$textareavalue";
$message->fullmessageformat = FORMAT_MARKDOWN;
$message->fullmessagehtml = "<p>$textareavalue</p>";
$message->smallmessage = "$textareavalue";
$message->notification = 0;
$message->contexturl = '';
$message->contexturlname = '';
$message->replyto = $userfrom->email;
    // User image.
    $userpicture = new user_picture($userfrom);
    $userpicture->size = 1; // Use f1 size.
    $userpicture->includetoken = $userto->id; // Generate an out-of-session token for the user receiving the message.
    $message->customdata = [
        'notificationiconurl' => $userpicture->get_url($PAGE)->out(false),
        'actionbuttons' => [
            'send' => get_string_manager()->get_string('send', 'message', null, $message->userto->lang),
        ],
        'placeholders' => [
            'send' => get_string_manager()->get_string('writeamessage', 'message', null, $message->userto->lang),
        ],
    ];
$messageid = message_send($message);
echo $messageid;
?>