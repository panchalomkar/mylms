<?php
namespace local_skillgap\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task: send upcoming assessment-due notifications.
 * Runs every 30 min. Sends bell + email when an active assessment's
 * startdate falls within the next 24 hours (and hasn't been notified yet).
 */
class notification_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_notification', 'local_skillgap');
    }

    public function execute(): void {
        global $DB;

        $now        = time();
        $window_end = $now + (24 * HOURSECS);   // 24-hour look-ahead.
        $sent       = 0;

        // Find active assessments whose start date is upcoming and not yet notified.
        $assessments = $DB->get_records_sql(
            "SELECT a.id, a.title, a.deptid, a.desigid, a.frequency, a.startdate
               FROM {local_skillgap_assessment} a
              WHERE a.status = 'active'
                AND a.startdate > :now
                AND a.startdate <= :wend
                AND NOT EXISTS (
                    SELECT 1 FROM {local_skillgap_notif_log} nl
                     WHERE nl.assessmentid = a.id AND nl.type = 'assessment_due'
                )",
            ['now' => $now, 'wend' => $window_end]
        );

        foreach ($assessments as $assessment) {
            // Find users with matching dept + desig.
            $users = self::get_assigned_users($assessment);
            foreach ($users as $u) {
                \local_skillgap_send_notification(
                    $u->id,
                    'assessment_due',
                    get_string('notify_assessment_due_subject', 'local_skillgap', $assessment->title),
                    get_string('notify_assessment_due_body',    'local_skillgap', $assessment->title)
                );
                $sent++;
            }
            // Log so we don't re-send.
            self::log_notification($assessment->id, 'assessment_due');
        }

        mtrace("local_skillgap notification_task: sent {$sent} assessment-due notification(s).");
    }

    /**
     * Get users whose department + designation match the assessment.
     */
    private static function get_assigned_users(\stdClass $assessment): array {
        global $DB;

        $dept_rec = $DB->get_record('local_skillgap_dept', ['id' => $assessment->deptid]);
        if (!$dept_rec) { return []; }

        $desig_rec = $assessment->desigid
            ? $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $assessment->desigid])
            : null;

        // Match users by department name (stored in user.department).
        $params = ['dept' => $dept_rec->name];
        $desig_clause = '';
        if ($desig_rec) {
            $desig_clause = " AND " . $DB->sql_compare_text('ui.data') . " = :desig";
            $params['desig'] = $desig_rec->designation_name;
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                      JOIN {user_info_data} ui ON ui.userid = u.id
                      JOIN {user_info_field} uif ON uif.id = ui.fieldid AND uif.shortname = 'designation'
                     WHERE u.deleted = 0 AND u.suspended = 0
                       AND u.department = :dept
                       {$desig_clause}";
        } else {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                     WHERE u.deleted = 0 AND u.suspended = 0
                       AND u.department = :dept";
        }
        return array_values($DB->get_records_sql($sql, $params));
    }

    private static function log_notification(int $assessmentid, string $type): void {
        global $DB;
        // Ensure table exists (created in upgrade).
        try {
            $DB->insert_record('local_skillgap_notif_log', [
                'assessmentid' => $assessmentid,
                'type'         => $type,
                'timesent'     => time(),
            ]);
        } catch (\Exception $e) {
            // Table may not exist yet on older installs — silent fail.
        }
    }
}
