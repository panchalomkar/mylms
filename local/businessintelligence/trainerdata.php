<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Trainer & business data: external trainers, training feedback and business KPI values (form or CSV).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_businessintelligence\local\datamanager;
use local_businessintelligence\local\icons;

require_login(null, false);
$context = context_system::instance();
require_capability('local/businessintelligence:managedata', $context);

$tab = optional_param('tab', 'trainers', PARAM_ALPHA);
if (!in_array($tab, ['trainers', 'feedback', 'kpi'], true)) {
    $tab = 'trainers';
}
$sample = optional_param('sample', '', PARAM_ALPHA);
if ($sample !== '' && isset(datamanager::CSV[$sample])) {
    datamanager::send_sample($sample);
    die;
}
$editid = optional_param('edit', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$p = 'local_businessintelligence';
$url = new moodle_url('/local/businessintelligence/trainerdata.php', ['tab' => $tab]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('td_title', $p));
$PAGE->set_heading(get_string('pluginname', $p));
$PAGE->add_body_class('lbi-page');
$PAGE->navbar->add(get_string('pluginname', $p), new moodle_url('/local/businessintelligence/index.php'));
$PAGE->navbar->add(get_string('td_title', $p));

$values = [];
$errors = [];
$result = null;
$notice = '';

if ($action !== '' && confirm_sesskey()) {
    switch ($action) {
        case 'savetrainer':
            $id = optional_param('id', 0, PARAM_INT);
            $values = [];
            foreach (['fullname', 'email', 'phone', 'organisation', 'designation', 'expertise', 'city', 'experience', 'notes']
                    as $k) {
                $values[$k] = optional_param($k, '', PARAM_RAW_TRIMMED);
            }
            [$rec, $errors] = datamanager::validate_trainer($values, $id);
            if (!$errors) {
                datamanager::save_trainer($rec, $id);
                redirect($url, get_string($id ? 'td_trainerupdated' : 'td_traineradded', $p, s($rec['fullname'])), null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }
            $editid = $id;
            break;
        case 'deltrainer':
            $id = required_param('id', PARAM_INT);
            $DB->delete_records('local_bi_feedback', ['extrainerid' => $id]);
            $DB->delete_records('local_bi_ext_trainer', ['id' => $id]);
            redirect($url, get_string('td_trainerdeleted', $p), null, \core\output\notification::NOTIFY_SUCCESS);
            break;
        case 'savefeedback':
            foreach (['trainer', 'training_name', 'mode', 'training_date', 'start_time', 'duration_minutes', 'attendees',
                    'rating', 'feedback', 'department', 'location'] as $k) {
                $values[$k] = optional_param($k, '', PARAM_RAW_TRIMMED);
            }
            [$rec, $errors] = datamanager::validate_feedback($values);
            if (!$errors) {
                datamanager::save_feedback($rec);
                redirect($url, get_string('td_feedbackadded', $p, s($rec['trainingname'])), null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
        case 'delfeedback':
            $DB->delete_records('local_bi_feedback', ['id' => required_param('id', PARAM_INT)]);
            redirect($url, get_string('td_feedbackdeleted', $p), null, \core\output\notification::NOTIFY_SUCCESS);
            break;
        case 'delkpi':
            $DB->delete_records('local_bi_kpi_value', ['kpi' => required_param('kpi', PARAM_TEXT)]);
            redirect($url, get_string('td_kpideleted', $p), null, \core\output\notification::NOTIFY_SUCCESS);
            break;
        case 'upload':
            $kind = $tab;
            $file = $_FILES['csvfile'] ?? null;
            if (!$file || (int)$file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $result = ['fatal' => get_string('td_err_nofile', $p)];
            } else if ((int)$file['size'] > 5 * 1024 * 1024) {
                $result = ['fatal' => get_string('td_err_filesize', $p)];
            } else if (!preg_match('/\.csv$/i', (string)$file['name'])) {
                $result = ['fatal' => get_string('td_err_filetype', $p)];
            } else {
                core_php_time_limit::raise(300);
                $result = datamanager::import_csv($kind, $file['tmp_name']);
            }
            break;
    }
}

// Form values when editing a trainer.
if ($tab === 'trainers' && $editid && !$values) {
    $t = $DB->get_record('local_bi_ext_trainer', ['id' => $editid], '*', MUST_EXIST);
    $values = (array)$t;
}

/**
 * Field context for the form template.
 *
 * @param string $name
 * @param string $icon
 * @param array $opts type, required, placeholder, help, options, min, max, step, pattern, wide
 * @param array $values
 * @param array $errors
 * @return array
 */
function local_businessintelligence_td_field(string $name, string $icon, array $opts, array $values, array $errors): array {
    $p = 'local_businessintelligence';
    $type = $opts['type'] ?? 'text';
    $value = (string)($values[$name] ?? $opts['default'] ?? '');
    $f = [
        'name' => $name, 'id' => 'lbi-td-' . $name, 'icon' => icons::svg($icon), 'label' => get_string('td_f_' . $name, $p),
        'required' => !empty($opts['required']), 'value' => $value,
        'placeholder' => isset($opts['placeholder']) ? get_string($opts['placeholder'], $p) : '',
        'help' => isset($opts['help']) ? get_string($opts['help'], $p) : '',
        'error' => $errors[$name] ?? '', 'haserror' => isset($errors[$name]),
        'wide' => !empty($opts['wide']), 'min' => $opts['min'] ?? '', 'max' => $opts['max'] ?? '',
        'step' => $opts['step'] ?? '', 'pattern' => $opts['pattern'] ?? '', 'maxlength' => $opts['maxlength'] ?? '',
        'is' . $type => true,
    ];
    if (isset($opts['options'])) {
        $f['options'] = [];
        foreach ($opts['options'] as $k => $label) {
            $f['options'][] = ['value' => $k, 'label' => $label, 'selected' => (string)$k === $value];
        }
        $f['isselect'] = true;
    }
    if (isset($opts['groups'])) {
        $f['groups'] = [];
        foreach ($opts['groups'] as $glabel => $items) {
            $g = ['label' => $glabel, 'options' => []];
            foreach ($items as $k => $label) {
                $g['options'][] = ['value' => $k, 'label' => $label, 'selected' => (string)$k === $value];
            }
            $f['groups'][] = $g;
        }
        $f['isgroupselect'] = true;
    }
    $f['type'] = $type;
    $f['isinput'] = empty($f['isselect']) && empty($f['isgroupselect']) && $type !== 'textarea';
    return $f;
}

$field = function(string $name, string $icon, array $opts = []) use ($values, $errors) {
    return local_businessintelligence_td_field($name, $icon, $opts, $values, $errors);
};

$ctx = [
    'tabs' => [],
    'sesskey' => sesskey(),
    'action' => $url->out(false),
    'backurl' => (new moodle_url('/local/businessintelligence/report.php', ['report' => $tab === 'kpi' ? 'outcome' : 'trainer']))
        ->out(false),
    'backlabel' => get_string($tab === 'kpi' ? 'td_backoutcome' : 'td_backtrainer', $p),
    'iconback' => icons::svg('arrow-left'),
    'iconupload' => icons::svg('upload'),
    'icondownload' => icons::svg('download'),
    'iconsheet' => icons::svg('file-spreadsheet'),
    'iconcheck' => icons::svg('check'),
    'iconalert' => icons::svg('circle-alert'),
    'iconsave' => icons::svg('circle-check'),
    'iconinfo' => icons::svg('info'),
    'samplefile' => (new moodle_url('/local/businessintelligence/trainerdata.php', ['tab' => $tab, 'sample' => $tab]))->out(false),
    'columns' => [],
    'tab' => $tab,
    'is' . $tab => true,
    'haserrors' => !empty($errors),
];
foreach (['trainers' => 'user-plus', 'feedback' => 'message-square', 'kpi' => 'database'] as $k => $icon) {
    $ctx['tabs'][] = ['key' => $k, 'label' => get_string('td_tab_' . $k, $p), 'desc' => get_string('td_tab_' . $k . '_desc', $p),
        'icon' => icons::svg($icon), 'active' => $k === $tab,
        'url' => (new moodle_url('/local/businessintelligence/trainerdata.php', ['tab' => $k]))->out(false)];
}
foreach (datamanager::CSV[$tab] as $c) {
    $ctx['columns'][] = ['name' => $c, 'required' => in_array($c, datamanager::REQUIRED[$tab], true)];
}
$ctx['csvhelp'] = get_string('td_csvhelp_' . $tab, $p);
if ($result !== null) {
    $ctx['result'] = [
        'fatal' => $result['fatal'] ?? '',
        'imported' => $result['imported'] ?? 0,
        'total' => $result['total'] ?? 0,
        'errors' => array_map(function($e) {
            return ['row' => $e[0], 'message' => $e[1]];
        }, array_slice($result['errors'] ?? [], 0, 200)),
        'haserrors' => !empty($result['errors']),
        'errorcount' => count($result['errors'] ?? []),
        'success' => empty($result['fatal']) && ($result['imported'] ?? 0) > 0,
    ];
}

if ($tab === 'trainers') {
    $ctx['form'] = [
        'title' => get_string($editid ? 'td_edittrainer' : 'td_addtrainer', $p),
        'id' => $editid,
        'editing' => (bool)$editid,
        'cancelurl' => $url->out(false),
        'submit' => get_string($editid ? 'td_update' : 'td_savetrainer', $p),
        'actionname' => 'savetrainer',
        'sections' => [
            ['title' => get_string('td_sec_identity', $p), 'icon' => icons::svg('users'), 'fields' => [
                $field('fullname', 'users', ['required' => true, 'placeholder' => 'td_ph_fullname', 'maxlength' => 120]),
                $field('email', 'mail', ['type' => 'email', 'required' => true, 'placeholder' => 'td_ph_email',
                    'help' => 'td_help_email']),
                $field('phone', 'phone', ['type' => 'tel', 'placeholder' => 'td_ph_phone',
                    'pattern' => '\+?[0-9][0-9\s\(\)\-]{6,18}']),
                $field('city', 'map-pin', ['placeholder' => 'td_ph_city']),
            ]],
            ['title' => get_string('td_sec_professional', $p), 'icon' => icons::svg('briefcase'), 'fields' => [
                $field('organisation', 'building', ['placeholder' => 'td_ph_organisation']),
                $field('designation', 'award', ['placeholder' => 'td_ph_designation']),
                $field('expertise', 'target', ['placeholder' => 'td_ph_expertise', 'help' => 'td_help_expertise']),
                $field('experience', 'clock', ['type' => 'number', 'min' => 0, 'max' => 60, 'placeholder' => 'td_ph_experience']),
                $field('notes', 'message-square', ['type' => 'textarea', 'wide' => true, 'placeholder' => 'td_ph_notes']),
            ]],
        ],
    ];
    $list = [];
    $counts = $DB->get_records_sql_menu("SELECT extrainerid, COUNT(1) FROM {local_bi_feedback} WHERE extrainerid > 0
                                          GROUP BY extrainerid");
    foreach ($DB->get_records('local_bi_ext_trainer', null, 'fullname') as $t) {
        $list[] = ['id' => $t->id, 'name' => format_string($t->fullname), 'email' => s($t->email),
            'initials' => \local_businessintelligence\local\analysis::initials($t->fullname),
            'organisation' => format_string((string)$t->organisation), 'designation' => format_string((string)$t->designation),
            'expertise' => format_string((string)$t->expertise), 'sessions' => (int)($counts[$t->id] ?? 0),
            'editurl' => (new moodle_url('/local/businessintelligence/trainerdata.php', ['tab' => 'trainers', 'edit' => $t->id]))
                ->out(false)];
    }
    $ctx['trainers'] = $list;
    $ctx['hastrainers'] = !empty($list);
}

if ($tab === 'feedback') {
    // Trainers to pick: Moodle users who are instructors of sessions + all users with a trainer type + external.
    $internal = [];
    $sessions = \local_businessintelligence\local\sessions::fetch(0, time() + 365 * DAYSECS, true);
    foreach ($sessions as $s) {
        foreach ($s['trainers'] as $t) {
            $internal[(int)$t] = (int)$t;
        }
    }
    foreach ($DB->get_fieldset_sql("SELECT DISTINCT traineruserid FROM {local_bi_feedback} WHERE traineruserid > 0") as $t) {
        $internal[(int)$t] = (int)$t;
    }
    $intopts = [];
    if ($internal) {
        foreach ($DB->get_records_list('user', 'id', array_values($internal), 'firstname, lastname',
                'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, email') as $u) {
            $intopts['u' . $u->id] = fullname($u) . ' — ' . $u->email;
        }
    }
    $extopts = [];
    foreach ($DB->get_records('local_bi_ext_trainer', null, 'fullname', 'id, fullname, email') as $t) {
        $extopts['x' . $t->id] = format_string($t->fullname) . ' — ' . $t->email;
    }
    $groups = [];
    if ($intopts) {
        $groups[get_string('td_internaltrainers', $p)] = $intopts;
    }
    if ($extopts) {
        $groups[get_string('td_externaltrainers', $p)] = $extopts;
    }
    $modes = [];
    foreach (datamanager::MODES as $m) {
        $modes[$m] = get_string('td_mode_' . $m, $p);
    }
    $ctx['form'] = [
        'title' => get_string('td_addfeedback', $p),
        'submit' => get_string('td_savefeedback', $p),
        'actionname' => 'savefeedback',
        'notrainers' => !$groups,
        'sections' => [
            ['title' => get_string('td_sec_training', $p), 'icon' => icons::svg('calendar-check'), 'fields' => [
                $field('trainer', 'users', ['required' => true, 'groups' => $groups, 'help' => 'td_help_trainer']),
                $field('training_name', 'layers', ['required' => true, 'placeholder' => 'td_ph_training', 'maxlength' => 255]),
                $field('mode', 'monitor', ['required' => true, 'options' => ['' => get_string('td_choose', $p)] + $modes]),
                $field('training_date', 'calendar', ['type' => 'date', 'required' => true]),
                $field('start_time', 'clock', ['type' => 'time']),
                $field('duration_minutes', 'timer', ['type' => 'number', 'required' => true, 'min' => 15, 'max' => 1440,
                    'step' => 5, 'placeholder' => 'td_ph_duration', 'help' => 'td_help_duration']),
            ]],
            ['title' => get_string('td_sec_feedback', $p), 'icon' => icons::svg('star'), 'fields' => [
                $field('attendees', 'users-round', ['type' => 'number', 'required' => true, 'min' => 0, 'max' => 10000,
                    'placeholder' => 'td_ph_attendees']),
                $field('rating', 'star', ['type' => 'number', 'min' => 1, 'max' => 5, 'step' => '0.1',
                    'placeholder' => 'td_ph_rating', 'help' => 'td_help_rating']),
                $field('department', 'building', ['placeholder' => 'td_ph_department']),
                $field('location', 'map-pin', ['placeholder' => 'td_ph_location']),
                $field('feedback', 'message-square', ['type' => 'textarea', 'wide' => true, 'placeholder' => 'td_ph_feedback',
                    'maxlength' => 2000]),
            ]],
        ],
    ];
    $rows = [];
    $names = [];
    foreach ($DB->get_records('local_bi_feedback', null, 'trainingdate DESC, id DESC', '*', 0, 50) as $r) {
        $tkey = $r->traineruserid ? 'u' . $r->traineruserid : 'x' . $r->extrainerid;
        $tname = $intopts[$tkey] ?? $extopts[$tkey] ?? '';
        if ($tname === '' && $r->traineruserid) {
            $u = core_user::get_user($r->traineruserid);
            $tname = $u ? fullname($u) : '#' . $r->traineruserid;
        }
        $rows[] = ['id' => $r->id, 'training' => format_string($r->trainingname), 'trainer' => explode(' — ', $tname)[0],
            'type' => get_string($r->trainertype === 'internal' ? 'internal' : 'external', $p),
            'typekey' => $r->trainertype === 'internal' ? 'internal' : 'external',
            'mode' => get_string('td_mode_' . (in_array($r->mode, datamanager::MODES) ? $r->mode : 'classroom'), $p),
            'date' => userdate($r->trainingdate, get_string('strftimedatefullshort', 'core_langconfig')) .
                ($r->starttime ? ' · ' . $r->starttime : ''),
            'duration' => round($r->duration / 60, 1), 'attendees' => (int)$r->attendees,
            'rating' => $r->rating === null ? '—' : number_format((float)$r->rating, 1)];
    }
    $ctx['feedbacks'] = $rows;
    $ctx['hasfeedbacks'] = !empty($rows);
}

if ($tab === 'kpi') {
    $ctx['kpis'] = [];
    foreach ($DB->get_records_sql("SELECT kpi, COUNT(1) AS n, COUNT(DISTINCT userid) AS users, MIN(period) AS fromp,
                                          MAX(period) AS top, MAX(unit) AS unit, MAX(direction) AS direction,
                                          MAX(source) AS source
                                     FROM {local_bi_kpi_value} GROUP BY kpi ORDER BY kpi") as $r) {
        $ctx['kpis'][] = ['kpi' => format_string($r->kpi), 'raw' => $r->kpi, 'rows' => (int)$r->n, 'users' => (int)$r->users,
            'range' => $r->fromp . ' → ' . $r->top, 'unit' => $r->unit ?: '—', 'source' => $r->source ?: '—',
            'direction' => get_string('td_dir_' . ($r->direction === 'lower' ? 'lower' : 'higher'), $p)];
    }
    $ctx['haskpis'] = !empty($ctx['kpis']);
}

$PAGE->requires->js_call_amd('local_businessintelligence/trainerdata', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_businessintelligence/trainerdata', $ctx);
echo $OUTPUT->footer();
