<?php
// This file is part of Moodle - http://moodle.org/
// GNU General Public License v3 or later - https://www.gnu.org/licenses/gpl-3.0.html
defined('MOODLE_INTERNAL') || die();

/**
 * Extend Moodle's primary navigation with a Hall of Fame link.
 */
function local_halloffame_extend_navigation(global_navigation $nav): void {
    global $PAGE;
    if (!isloggedin() || isguestuser()) {
        return;
    }
    $context = context_system::instance();
    if (!has_capability('local/halloffame:view', $context)) {
        return;
    }
    if (!get_config('local_halloffame', 'show_in_nav')) {
        return;
    }
    $label = get_config('local_halloffame', 'nav_label');
    $label = ($label !== false && trim($label) !== '')
        ? trim($label)
        : get_string('halloffame', 'local_halloffame');

    $url  = new moodle_url('/local/halloffame/pages/index.php');
    $node = $nav->add($label, $url, navigation_node::TYPE_CUSTOM, null, 'halloffame',
                      new pix_icon('icon', '', 'local_halloffame'));
    $node->showinflatnavigation = true;
}

/**
 * Serve uploaded certificate and image files.
 *
 * URL structure built by make_pluginfile_url:
 *   /pluginfile.php/<contextid>/local_halloffame/<filearea>/<itemid>/<filename>
 * Moodle strips contextid then passes: $filearea, $args = [itemid, filename], ...
 */
function local_halloffame_pluginfile(
    $course, $cm, context $context, string $filearea,
    array $args, bool $forcedownload, array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }
    require_login();

    $allowed = ['certificates', 'awards_images', 'achiever_images'];
    if (!in_array($filearea, $allowed, true)) {
        return false;
    }

    $itemid   = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? ('/' . implode('/', $args) . '/') : '/';

    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'local_halloffame', $filearea,
                          $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
    return true;
}

/**
 * Return the localised month name for month number 1–12.
 */
function local_halloffame_month_name(int $month): string {
    $map = [
        1  => 'january',  2  => 'february', 3  => 'march',
        4  => 'april',    5  => 'may',       6  => 'june',
        7  => 'july',     8  => 'august',    9  => 'september',
        10 => 'october',  11 => 'november',  12 => 'december',
    ];
    $key = $map[$month] ?? '';
    return $key ? get_string($key, 'local_halloffame') : '';
}
