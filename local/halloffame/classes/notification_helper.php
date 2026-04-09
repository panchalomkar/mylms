<?php
namespace local_halloffame;

defined('MOODLE_INTERNAL') || die();

class notification_helper {

    public static function notify_submission(\stdClass $sub): void {
        if (!get_config('local_halloffame', 'notify_on_submission')) {
            return;
        }
        $context   = \context_system::instance();
        $approvers = get_users_by_capability($context, 'local/halloffame:approve');
        if (empty($approvers)) {
            return;
        }
        $from      = \core_user::get_user($sub->userid);
        $reviewurl = new \moodle_url('/local/halloffame/pages/review.php');
        $subject   = '[Hall of Fame] New Certificate Submission';
        $body      = "Hello,\n\n"
                   . fullname($from) . " submitted a certificate for review:\n\n"
                   . "  Title:  {$sub->title}\n"
                   . "  Issuer: {$sub->issuer}\n"
                   . "  Type:   {$sub->type}\n\n"
                   . "Review it here: {$reviewurl}\n\n-- Hall of Fame System";
        foreach ($approvers as $approver) {
            self::send($approver, $subject, $body);
        }
    }

    public static function notify_approved(\stdClass $ach): void {
        if (!get_config('local_halloffame', 'notify_user_on_approval')) {
            return;
        }
        $user    = \core_user::get_user($ach->userid);
        $galurl  = new \moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']);
        $subject = '[Hall of Fame] Your certificate has been approved!';
        $body    = "Dear " . fullname($user) . ",\n\n"
                 . "Congratulations! Your certificate \"{$ach->title}\" has been approved "
                 . "and is now visible in the Achievement Gallery.\n\n"
                 . "View it here: {$galurl}\n\n-- Hall of Fame System";
        self::send($user, $subject, $body);
    }

    public static function notify_rejected(\stdClass $sub): void {
        if (!get_config('local_halloffame', 'notify_user_on_approval')) {
            return;
        }
        $user    = \core_user::get_user($sub->userid);
        $subject = '[Hall of Fame] Certificate submission update';
        $body    = "Dear " . fullname($user) . ",\n\n"
                 . "Your submission \"{$sub->title}\" could not be approved at this time.\n"
                 . "Please contact your administrator for more details.\n\n"
                 . "-- Hall of Fame System";
        self::send($user, $subject, $body);
    }

    public static function notify_award_recipient(\stdClass $award): void {
        if (!get_config('local_halloffame', 'notify_user_on_award')) {
            return;
        }
        $user    = \core_user::get_user($award->userid);
        $hofurl  = new \moodle_url('/local/halloffame/pages/index.php', ['tab' => 'awards']);
        $subject = "[Hall of Fame] Congratulations — you've received an award!";
        $body    = "Dear " . fullname($user) . ",\n\n"
                 . "You have been recognised with: {$award->title}\n"
                 . "Department: {$award->department}\n"
                 . ($award->message ? "Message:    {$award->message}\n" : '')
                 . "\nView it here: {$hofurl}\n\n-- Hall of Fame System";
        self::send($user, $subject, $body);
    }

    private static function send(\stdClass $to, string $subject, string $body): void {
        $noreply = \core_user::get_noreply_user();
        email_to_user($to, $noreply, $subject, $body);
    }
}
