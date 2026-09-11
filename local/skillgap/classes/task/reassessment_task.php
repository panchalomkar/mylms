<?php
namespace local_skillgap\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task: checks frequency windows and sends assessment-due notifications.
 */
class reassessment_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_reassessment', 'local_skillgap');
    }

    public function execute(): void {
        global $DB;

        $now         = time();
        $assessments = $DB->get_records('local_skillgap_assessment', ['status' => 'active']);

        foreach ($assessments as $assessment) {
            $interval = match($assessment->frequency) {
                'quarterly'   => DAYSECS * 90,
                'half_yearly' => DAYSECS * 180,
                'yearly'      => DAYSECS * 365,
                default       => DAYSECS * 90,
            };

            // Skip if we are not near a cycle boundary (within 1 day).
            $elapsed = $now - $assessment->startdate;
            if ($elapsed <= 0 || ($elapsed % $interval) > DAYSECS) {
                continue;
            }

            // Find users in this dept/desig.
            $dept  = $DB->get_record('local_skillgap_dept',  ['id' => $assessment->deptid]);
            $desig = $DB->get_record('local_skillgap_desig_hierarchy', ['id' => $assessment->desigid]);
            if (!$dept) {
                continue;
            }

            $users = $DB->get_records_sql(
                "SELECT u.id FROM {user} u
                  WHERE u.department = :dept AND u.deleted = 0 AND u.suspended = 0",
                ['dept' => $dept->name]
            );

            foreach ($users as $u) {
                // Only notify if they have not already completed this cycle.
                $completed = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {local_skillgap_attempt}
                      WHERE userid = :uid AND assessmentid = :aid
                        AND status = 'completed' AND timecreated > :since",
                    ['uid' => $u->id, 'aid' => $assessment->id, 'since' => $now - $interval]
                );
                if ($completed > 0) {
                    continue;
                }

                \local_skillgap_send_notification(
                    $u->id,
                    'assessment_due',
                    get_string('notify_assessment_due_subject', 'local_skillgap', $assessment->title),
                    get_string('notify_assessment_due_body',    'local_skillgap', $assessment->title)
                );
            }

            // Update next cycle start date.
            $DB->set_field('local_skillgap_assessment', 'startdate', $now, ['id' => $assessment->id]);
            mtrace("Reassessment notifications sent for: {$assessment->title}");
        }
    }
}
