<?php
namespace mod_airoleplay\event;
defined('MOODLE_INTERNAL') || die();
class session_passed extends \core\event\base {
    protected function init(): void {
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'airoleplay_session';
    }
    public static function get_name(): string { return 'Session passed'; }
    public function get_description(): string { return "User {$this->userid} passed session {$this->objectid}"; }
    public function get_url(): \moodle_url { return new \moodle_url('/mod/airoleplay/results.php', ['session' => $this->objectid]); }
}
