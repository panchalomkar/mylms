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

namespace local_businessintelligence\local;

/**
 * Validation and storage of the Trainer Data page: external trainers, training feedback and business KPI values,
 * entered one by one or uploaded as CSV.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datamanager {
    /** Training modes. */
    const MODES = ['classroom', 'virtual', 'hybrid', 'onthejob'];

    /** CSV columns. */
    const CSV = [
        'trainers' => ['fullname', 'email', 'phone', 'organisation', 'designation', 'expertise', 'city', 'experience_years',
            'notes'],
        'feedback' => ['trainer_email', 'trainer_name', 'trainer_type', 'training_name', 'mode', 'training_date', 'start_time',
            'duration_minutes', 'attendees', 'rating', 'feedback', 'department', 'location'],
        'kpi' => ['employee_id', 'kpi', 'period', 'value', 'unit', 'direction', 'source'],
    ];

    /** Required CSV columns. */
    const REQUIRED = [
        'trainers' => ['fullname', 'email'],
        'feedback' => ['trainer_email', 'training_name', 'mode', 'training_date', 'duration_minutes', 'attendees'],
        'kpi' => ['employee_id', 'kpi', 'period', 'value'],
    ];

    /** Sample rows. */
    const SAMPLES = [
        'trainers' => [
            ['Meera Gupta', 'meera.gupta@trainingpartner.com', '+91 98765 43210', 'Skillbridge Learning', 'Lead Trainer',
                'Sales, Negotiation', 'Mumbai', '12', 'Certified sales coach'],
            ['Rahul Verma', 'rahul.verma@acmeconsult.in', '+91 99887 76655', 'Acme Consulting', 'Senior Facilitator',
                'Leadership', 'Pune', '8', ''],
        ],
        'feedback' => [
            ['meera.gupta@trainingpartner.com', 'Meera Gupta', 'external', 'Financial Modelling Workshop', 'classroom',
                '2026-09-12', '10:00', '240', '22', '4.8', 'Very practical, great examples', 'Finance', 'Mumbai HQ'],
            ['rahul.verma@acmeconsult.in', 'Rahul Verma', 'external', 'Leadership Foundations', 'virtual', '2026-09-20', '14:30',
                '120', '38', '4.4', 'Engaging session', 'HR', 'Online'],
        ],
        'kpi' => [
            ['EMP1000', 'Sales Pipeline', '2026-04', '1504911', '₹', 'higher', 'CRM (API)'],
            ['EMP1000', 'Sales Pipeline', '2026-05', '1555141', '₹', 'higher', 'CRM (API)'],
            ['EMP1000', 'Defect Rate', '2026-08', '2.4', '%', 'lower', 'MES (CSV)'],
        ],
    ];

    /**
     * Shortcut for a lang string.
     *
     * @param string $k
     * @param mixed $a
     * @return string
     */
    protected static function s(string $k, $a = null): string {
        return get_string($k, 'local_businessintelligence', $a);
    }

    // ---------------------------------------------------------------------------------------------------------------
    // External trainers.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Validate an external trainer.
     *
     * @param array $d raw values
     * @param int $id record being edited (0 = new)
     * @return array [clean record, errors field => message]
     */
    public static function validate_trainer(array $d, int $id = 0): array {
        global $DB;
        $e = [];
        $r = [
            'fullname' => trim(clean_param($d['fullname'] ?? '', PARAM_TEXT)),
            'email' => \core_text::strtolower(trim((string)($d['email'] ?? ''))),
            'phone' => trim((string)($d['phone'] ?? '')),
            'organisation' => trim(clean_param($d['organisation'] ?? '', PARAM_TEXT)),
            'designation' => trim(clean_param($d['designation'] ?? '', PARAM_TEXT)),
            'expertise' => trim(clean_param($d['expertise'] ?? '', PARAM_TEXT)),
            'city' => trim(clean_param($d['city'] ?? '', PARAM_TEXT)),
            'experience' => trim((string)($d['experience'] ?? $d['experience_years'] ?? '')),
            'notes' => trim(clean_param($d['notes'] ?? '', PARAM_TEXT)),
        ];
        if ($r['fullname'] === '') {
            $e['fullname'] = self::s('td_err_required');
        } else if (\core_text::strlen($r['fullname']) < 3 || \core_text::strlen($r['fullname']) > 120) {
            $e['fullname'] = self::s('td_err_namelength');
        } else if (!preg_match("/^[\\p{L}][\\p{L} .'\\-]+$/u", $r['fullname'])) {
            $e['fullname'] = self::s('td_err_namechars');
        }
        if ($r['email'] === '') {
            $e['email'] = self::s('td_err_required');
        } else if (!validate_email($r['email'])) {
            $e['email'] = self::s('td_err_email');
        } else if ($DB->record_exists_select('local_bi_ext_trainer', 'email = :e AND id <> :id', ['e' => $r['email'], 'id' => $id])) {
            $e['email'] = self::s('td_err_emailexists');
        } else if ($DB->record_exists('user', ['email' => $r['email'], 'deleted' => 0])) {
            $e['email'] = self::s('td_err_emailuser');
        }
        if ($r['phone'] !== '' && !preg_match('/^\+?[0-9][0-9 \-()]{6,18}$/', $r['phone'])) {
            $e['phone'] = self::s('td_err_phone');
        }
        if ($r['experience'] === '') {
            $r['experience'] = 0;
        } else if (!ctype_digit($r['experience']) || (int)$r['experience'] > 60) {
            $e['experience'] = self::s('td_err_experience');
        } else {
            $r['experience'] = (int)$r['experience'];
        }
        foreach (['organisation', 'designation', 'expertise', 'city'] as $k) {
            if (\core_text::strlen($r[$k]) > 255) {
                $e[$k] = self::s('td_err_toolong');
            }
        }
        return [$r, $e];
    }

    /**
     * Save an external trainer.
     *
     * @param array $r clean record
     * @param int $id
     * @return int id
     */
    public static function save_trainer(array $r, int $id = 0): int {
        global $DB, $USER;
        $r['timemodified'] = time();
        $r['usermodified'] = $USER->id;
        if ($id) {
            $r['id'] = $id;
            $DB->update_record('local_bi_ext_trainer', (object)$r);
            return $id;
        }
        $r['timecreated'] = time();
        $r['active'] = 1;
        return (int)$DB->insert_record('local_bi_ext_trainer', (object)$r);
    }

    // ---------------------------------------------------------------------------------------------------------------
    // Feedback.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Resolve a trainer reference ("u123", "x5" or an email) to [type, userid, extid].
     *
     * @param string $ref
     * @param string $type requested type (internal|external|'')
     * @param string $name name to create an unknown external trainer with
     * @param bool $create create a missing external trainer
     * @return array|null
     */
    public static function resolve_trainer(string $ref, string $type = '', string $name = '', bool $create = false): ?array {
        global $DB;
        $ref = trim($ref);
        if (preg_match('/^u(\d+)$/', $ref, $m)) {
            $u = $DB->get_record('user', ['id' => (int)$m[1], 'deleted' => 0], 'id');
            return $u ? [$type === 'external' ? 'external' : 'internal', (int)$u->id, 0] : null;
        }
        if (preg_match('/^x(\d+)$/', $ref, $m)) {
            return $DB->record_exists('local_bi_ext_trainer', ['id' => (int)$m[1]]) ? ['external', 0, (int)$m[1]] : null;
        }
        $email = \core_text::strtolower($ref);
        if (!validate_email($email)) {
            return null;
        }
        $u = $DB->get_record_select('user', 'LOWER(email) = :e AND deleted = 0', ['e' => $email], 'id', IGNORE_MULTIPLE);
        if ($u) {
            if ($type === '') {
                $type = isset(metrics::guest_trainers([(int)$u->id])[(int)$u->id]) ? 'external' : 'internal';
            }
            return [$type, (int)$u->id, 0];
        }
        $x = $DB->get_record('local_bi_ext_trainer', ['email' => $email], 'id');
        if ($x) {
            return ['external', 0, (int)$x->id];
        }
        if ($create && trim($name) !== '') {
            [$rec, $err] = self::validate_trainer(['fullname' => $name, 'email' => $email]);
            if (!$err) {
                return ['external', 0, self::save_trainer($rec)];
            }
        }
        return null;
    }

    /**
     * Validate a feedback record.
     *
     * @param array $d raw values (trainer = ref, or trainer_email / trainer_name / trainer_type for CSV)
     * @param bool $csv
     * @return array [clean record, errors]
     */
    public static function validate_feedback(array $d, bool $csv = false): array {
        $e = [];
        $ref = trim((string)($csv ? ($d['trainer_email'] ?? '') : ($d['trainer'] ?? '')));
        $type = \core_text::strtolower(trim((string)($d['trainer_type'] ?? '')));
        if ($type !== '' && !in_array($type, ['internal', 'external'], true)) {
            $e['trainer_type'] = self::s('td_err_trainertype');
            $type = '';
        }
        $trainer = null;
        if ($ref === '') {
            $e[$csv ? 'trainer_email' : 'trainer'] = self::s('td_err_required');
        } else {
            $trainer = self::resolve_trainer($ref, $type, (string)($d['trainer_name'] ?? ''), $csv);
            if (!$trainer) {
                $e[$csv ? 'trainer_email' : 'trainer'] = self::s($csv ? 'td_err_trainerunknown_csv' : 'td_err_trainerunknown');
            }
        }
        $r = [
            'trainingname' => trim(clean_param($d['training_name'] ?? $d['trainingname'] ?? '', PARAM_TEXT)),
            'mode' => \core_text::strtolower(str_replace([' ', '-', '_'], '', trim((string)($d['mode'] ?? '')))),
            'date' => trim((string)($d['training_date'] ?? $d['trainingdate'] ?? '')),
            'starttime' => trim((string)($d['start_time'] ?? $d['starttime'] ?? '')),
            'duration' => trim((string)($d['duration_minutes'] ?? $d['duration'] ?? '')),
            'attendees' => trim((string)($d['attendees'] ?? '')),
            'rating' => trim((string)($d['rating'] ?? '')),
            'feedback' => trim(clean_param($d['feedback'] ?? '', PARAM_TEXT)),
            'department' => trim(clean_param($d['department'] ?? '', PARAM_TEXT)),
            'location' => trim(clean_param($d['location'] ?? '', PARAM_TEXT)),
        ];
        if ($r['trainingname'] === '') {
            $e['training_name'] = self::s('td_err_required');
        } else if (\core_text::strlen($r['trainingname']) > 255) {
            $e['training_name'] = self::s('td_err_toolong');
        }
        $modes = ['classroom' => 'classroom', 'f2f' => 'classroom', 'facetoface' => 'classroom', 'offline' => 'classroom',
            'ilt' => 'classroom', 'virtual' => 'virtual', 'online' => 'virtual', 'vilt' => 'virtual', 'hybrid' => 'hybrid',
            'blended' => 'hybrid', 'onthejob' => 'onthejob', 'ojt' => 'onthejob'];
        if ($r['mode'] === '') {
            $e['mode'] = self::s('td_err_required');
        } else if (!isset($modes[$r['mode']])) {
            $e['mode'] = self::s('td_err_mode');
        } else {
            $r['mode'] = $modes[$r['mode']];
        }
        $ts = 0;
        if ($r['date'] === '') {
            $e['training_date'] = self::s('td_err_required');
        } else {
            $dt = \DateTime::createFromFormat('!Y-m-d', $r['date'], \core_date::get_server_timezone_object());
            if (!$dt || $dt->format('Y-m-d') !== $r['date']) {
                $e['training_date'] = self::s('td_err_date');
            } else {
                $ts = $dt->getTimestamp();
                if ($ts > time() + 366 * DAYSECS || $ts < strtotime('2000-01-01')) {
                    $e['training_date'] = self::s('td_err_daterange');
                }
            }
        }
        if ($r['starttime'] !== '') {
            if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $r['starttime'], $m)) {
                $e['start_time'] = self::s('td_err_time');
            } else {
                $r['starttime'] = sprintf('%02d:%02d', $m[1], $m[2]);
                $ts += (int)$m[1] * HOURSECS + (int)$m[2] * MINSECS;
            }
        }
        if ($r['duration'] === '') {
            $e['duration_minutes'] = self::s('td_err_required');
        } else if (!ctype_digit($r['duration']) || (int)$r['duration'] < 15 || (int)$r['duration'] > 1440) {
            $e['duration_minutes'] = self::s('td_err_duration');
        }
        if ($r['attendees'] === '') {
            $e['attendees'] = self::s('td_err_required');
        } else if (!ctype_digit($r['attendees']) || (int)$r['attendees'] > 10000) {
            $e['attendees'] = self::s('td_err_attendees');
        }
        if ($r['rating'] !== '' && (!is_numeric($r['rating']) || (float)$r['rating'] < 1 || (float)$r['rating'] > 5)) {
            $e['rating'] = self::s('td_err_rating');
        }
        if (\core_text::strlen($r['feedback']) > 2000) {
            $e['feedback'] = self::s('td_err_feedbacklength');
        }
        $rec = [
            'trainertype' => $trainer[0] ?? 'external', 'traineruserid' => $trainer[1] ?? 0, 'extrainerid' => $trainer[2] ?? 0,
            'trainingname' => $r['trainingname'], 'mode' => $r['mode'], 'trainingdate' => $ts, 'starttime' => $r['starttime'],
            'duration' => (int)$r['duration'], 'attendees' => (int)$r['attendees'],
            'rating' => $r['rating'] === '' ? null : round((float)$r['rating'], 1), 'feedback' => $r['feedback'],
            'department' => $r['department'], 'location' => $r['location'],
        ];
        return [$rec, $e];
    }

    /**
     * Save feedback.
     *
     * @param array $r
     * @param string $batch
     * @return int
     */
    public static function save_feedback(array $r, string $batch = ''): int {
        global $DB, $USER;
        $r['batchid'] = $batch;
        $r['timecreated'] = time();
        $r['usermodified'] = $USER->id;
        return (int)$DB->insert_record('local_bi_feedback', (object)$r);
    }

    // ---------------------------------------------------------------------------------------------------------------
    // KPI values.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Validate a KPI value row.
     *
     * @param array $d
     * @return array [clean record, errors]
     */
    public static function validate_kpi(array $d): array {
        global $DB;
        $e = [];
        $emp = trim((string)($d['employee_id'] ?? ''));
        $uid = 0;
        if ($emp === '') {
            $e['employee_id'] = self::s('td_err_required');
        } else {
            $u = $DB->get_record('user', ['idnumber' => $emp, 'deleted' => 0], 'id', IGNORE_MULTIPLE)
                ?: $DB->get_record('user', ['username' => \core_text::strtolower($emp), 'deleted' => 0], 'id', IGNORE_MULTIPLE)
                ?: $DB->get_record_select('user', 'LOWER(email) = :e AND deleted = 0', ['e' => \core_text::strtolower($emp)], 'id',
                    IGNORE_MULTIPLE);
            if (!$u) {
                $e['employee_id'] = self::s('td_err_employee');
            } else {
                $uid = (int)$u->id;
            }
        }
        $kpi = trim(clean_param($d['kpi'] ?? '', PARAM_TEXT));
        if ($kpi === '') {
            $e['kpi'] = self::s('td_err_required');
        } else if (\core_text::strlen($kpi) > 100) {
            $e['kpi'] = self::s('td_err_toolong');
        }
        $period = trim((string)($d['period'] ?? ''));
        if (!preg_match('/^(20\d\d)-(0[1-9]|1[0-2])$/', $period)) {
            $e['period'] = self::s('td_err_period');
        }
        $value = str_replace([',', ' '], '', trim((string)($d['value'] ?? '')));
        if ($value === '' || !is_numeric($value)) {
            $e['value'] = self::s('td_err_number');
        }
        $dir = \core_text::strtolower(trim((string)($d['direction'] ?? '')));
        if ($dir !== '' && !in_array($dir, ['higher', 'lower'], true)) {
            $e['direction'] = self::s('td_err_direction');
        }
        return [[
            'employeeid' => $emp, 'userid' => $uid, 'kpi' => $kpi, 'period' => $period, 'value' => (float)$value,
            'unit' => \core_text::substr(trim(clean_param($d['unit'] ?? '', PARAM_TEXT)), 0, 30),
            'direction' => $dir === 'lower' ? 'lower' : 'higher',
            'source' => \core_text::substr(trim(clean_param($d['source'] ?? '', PARAM_TEXT)), 0, 50),
        ], $e];
    }

    /**
     * Save (replace) a KPI value.
     *
     * @param array $r
     * @param string $batch
     */
    public static function save_kpi(array $r, string $batch = ''): void {
        global $DB;
        $DB->delete_records('local_bi_kpi_value', ['userid' => $r['userid'], 'kpi' => $r['kpi'], 'period' => $r['period']]);
        $r['batchid'] = $batch;
        $r['timecreated'] = time();
        $DB->insert_record('local_bi_kpi_value', (object)$r);
    }

    // ---------------------------------------------------------------------------------------------------------------
    // CSV.
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Import an uploaded CSV file.
     *
     * @param string $kind trainers|feedback|kpi
     * @param string $path temp file
     * @return array ['imported' => n, 'errors' => [[row, message]], 'fatal' => string]
     */
    public static function import_csv(string $kind, string $path): array {
        $res = ['imported' => 0, 'errors' => [], 'fatal' => '', 'total' => 0];
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            $res['fatal'] = self::s('td_err_csvempty');
            return $res;
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $delim = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(function($h) {
            return \core_text::strtolower(str_replace([' ', '-'], '_', trim($h)));
        }, str_getcsv(array_shift($lines), $delim));
        $missing = array_diff(self::REQUIRED[$kind], $header);
        if ($missing) {
            $res['fatal'] = self::s('td_err_csvheader', implode(', ', $missing));
            return $res;
        }
        $batch = substr(md5(uniqid('', true)), 0, 16);
        $rowno = 1;
        foreach ($lines as $line) {
            $rowno++;
            if (trim($line) === '') {
                continue;
            }
            if ($res['total'] >= 5000) {
                $res['errors'][] = [$rowno, self::s('td_err_csvlimit')];
                break;
            }
            $res['total']++;
            $cells = str_getcsv($line, $delim);
            $row = [];
            foreach ($header as $i => $h) {
                $row[$h] = $cells[$i] ?? '';
            }
            if ($kind === 'trainers') {
                [$rec, $err] = self::validate_trainer($row);
            } else if ($kind === 'feedback') {
                [$rec, $err] = self::validate_feedback($row, true);
            } else {
                [$rec, $err] = self::validate_kpi($row);
            }
            if ($err) {
                $msgs = [];
                foreach ($err as $f => $m) {
                    $msgs[] = $f . ': ' . $m;
                }
                $res['errors'][] = [$rowno, implode(' · ', $msgs)];
                continue;
            }
            if ($kind === 'trainers') {
                self::save_trainer($rec);
            } else if ($kind === 'feedback') {
                self::save_feedback($rec, $batch);
            } else {
                self::save_kpi($rec, $batch);
            }
            $res['imported']++;
        }
        return $res;
    }

    /**
     * Send a sample CSV.
     *
     * @param string $kind
     */
    public static function send_sample(string $kind): void {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::CSV[$kind]);
        foreach (self::SAMPLES[$kind] as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        send_file($csv, 'sample-' . $kind . '.csv', 0, 0, true, true, 'text/csv');
    }
}
