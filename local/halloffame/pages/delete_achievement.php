<?php
// local/halloffame/pages/delete_achievement.php
// Admin: delete an approved achievement from the gallery.

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/halloffame:approve', $context);

$id = required_param('id', PARAM_INT);

\local_halloffame\manager::delete_achievement($id);

redirect(
    new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']),
    get_string('achievementdeleted', 'local_halloffame'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
