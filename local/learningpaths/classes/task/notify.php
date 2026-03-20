<?php
namespace local_learningpaths\task;
class notify extends \core\task\scheduled_task
{
    public function get_name()
    {
        // Shown in admin screens
        return get_string('pluginname', 'local_learningpaths');
    }
    public function execute()
    {
        GLOBAL $DB, $CFG;
        // Get notifications where type=cron
        $notifications = $DB->get_records('learningpath_notifications',['cron' => '1']);
        
        foreach ($notifications as $notification) {
            // Lets load the class for this notification
           $urlbase = str_replace('\\', '/', $CFG->dirroot);
           $fileName = $urlbase . "/local/learningpaths/classes/notify_cron.php";
           mtrace($fileName);
           if (file_exists($fileName)) {
               include_once($fileName);
                $obj = new \notify_cron();
                $obj->run($notification);
           }
        }
    }
    protected function _find_in_log($settingId) {
        GLOBAL $DB;
        $start = date('Y-m-d') . ' 00:00:00';
        $end = date('Y-m-d') . ' 23:59:59';
        $sql = "
        SELECT
        *
        FROM {block_paradiso_ntf_log}
        WHERE settings_id = '$settingId'
        AND created_on BETWEEN '$start' AND '$end'";
        $records = $DB->get_records_sql($sql);
        return count($records);
    }
}