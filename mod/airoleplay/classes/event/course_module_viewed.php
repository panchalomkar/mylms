<?php
namespace mod_airoleplay\event;
defined('MOODLE_INTERNAL') || die();
class course_module_viewed extends \core\event\course_module_viewed {
    protected function init(): void {
        $this->data['crud']        = 'r';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'airoleplay';
    }
}
