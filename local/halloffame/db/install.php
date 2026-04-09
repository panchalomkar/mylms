<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_halloffame_install(): bool {
    global $DB;

    $categories = [
        'Top Performer of the Month',
        'Rising Star of the Month',
        'Leader of the Month',
        'Best Team Player',
        'Innovation Award',
        'Customer Champion',
    ];
    foreach ($categories as $name) {
        if (!$DB->record_exists('halloffame_categories', ['name' => $name])) {
            $DB->insert_record('halloffame_categories',
                (object)['name' => $name, 'description' => '']);
        }
    }

    $departments = [
        'Human Resource', 'Sales', 'Engineering',
        'Marketing', 'Finance', 'Operations', 'Customer Success',
    ];
    foreach ($departments as $name) {
        if (!$DB->record_exists('halloffame_departments', ['name' => $name])) {
            $DB->insert_record('halloffame_departments', (object)['name' => $name]);
        }
    }

    return true;
}
