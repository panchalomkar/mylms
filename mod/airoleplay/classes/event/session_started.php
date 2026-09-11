<?php
namespace mod_airoleplay\event;
defined('MOODLE_INTERNAL') || die();
class session_started extends \core\event\base {
    protected function init(): void {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'airoleplay_session';
    }
    public static function get_name(): string { return 'Session started'; }
    public function get_description(): string { return "User {$this->userid} started session {$this->objectid}"; }
    public function get_url(): \moodle_url { return new \moodle_url('/mod/airoleplay/conversation.php', ['session' => $this->objectid]); }
}
