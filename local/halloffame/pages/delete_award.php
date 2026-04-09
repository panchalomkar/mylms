<?php
// local/halloffame/pages/delete_award.php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

use local_halloffame\manager;

require_login();
require_capability('local/halloffame:manageawards', context_system::instance());
require_sesskey();

$id = required_param('id', PARAM_INT);
manager::delete_award($id);

redirect(
    new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'awards']),
    'Award deleted.',
    null, \core\output\notification::NOTIFY_WARNING
);
