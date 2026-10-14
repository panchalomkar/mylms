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
 * Normalised training sessions from ILT (face-to-face), Zoom and Google Meet.
 *
 * Every session becomes:
 *   [
 *     'id' => 'ilt-12', 'source' => 'ilt|zoom|gmeet', 'type' => 'f2f|online',
 *     'start' => int, 'secs' => int (scheduled length),
 *     'trainers' => int[] (ILT instructors / session roles, Zoom & Meet instructors set on the activity),
 *     'att' => [userid => ['state' => 'attended|partial|noshow|booked', 'secs' => int]],
 *   ]
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessions {
    /**
     * All sessions that started inside [start, end].
     *
     * @param int $start
     * @param int $end
     * @param bool $future include sessions that have not started yet (end is not capped at now)
     * @return array
     */
    public static function fetch(int $start, int $end, bool $future = false): array {
        if (!$future) {
            $end = min($end, time()); // Only sessions that have actually started count as "conducted".
        }
        $out = [];
        foreach (['ilt', 'zoom', 'gmeet'] as $src) {
            try {
                $method = 'fetch_' . $src;
                $out = array_merge($out, self::$method($start, $end));
            } catch (\dml_exception $e) {
                debugging('local_businessintelligence: ' . $src . ' source failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        usort($out, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        return $out;
    }

    /**
     * ILT / face-to-face (facetoface-style schema: <mod>_sessions_dates, <mod>_signups, <mod>_signups_status,
     * <mod>_session_roles).
     *
     * @param int $start
     * @param int $end
     * @return array
     */
    protected static function fetch_ilt(int $start, int $end): array {
        global $DB;
        $mod = clean_param((string)source::config('iltmodule', 'ilt'), PARAM_ALPHANUMEXT);
        $candidates = array_unique([$mod, 'ilt', 'facetoface']);
        $mod = null;
        foreach ($candidates as $c) {
            if ($c && source::has_table($c . '_sessions_dates')) {
                $mod = $c;
                break;
            }
        }
        if (!$mod) {
            return [];
        }

        $p = new params();
        $sessionsql = "SELECT d.sessionid, MIN(d.timestart) AS tstart, SUM(d.timefinish - d.timestart) AS secs
                         FROM {{$mod}_sessions_dates} d
                     GROUP BY d.sessionid
                       HAVING MIN(d.timestart) >= " . $p->add($start) . " AND MIN(d.timestart) <= " . $p->add($end);
        $rows = $DB->get_records_sql($sessionsql, $p->values);
        if (!$rows) {
            return [];
        }
        $sessions = [];
        foreach ($rows as $r) {
            $sessions[(int)$r->sessionid] = [
                'id' => 'ilt-' . $r->sessionid,
                'source' => 'ilt',
                'type' => 'f2f',
                'start' => (int)$r->tstart,
                'secs' => max(0, (int)$r->secs),
                'trainers' => [],
                'att' => [],
            ];
        }
        // Cancelled sessions are not "conducted".
        if (source::has_field($mod . '_sessions', 'cancelledstatus')) {
            $p = new params();
            $in = $p->in(array_keys($sessions));
            $cancelled = $DB->get_fieldset_sql("SELECT id FROM {{$mod}_sessions} WHERE id {$in} AND cancelledstatus <> 0",
                $p->values);
            foreach ($cancelled as $cid) {
                unset($sessions[(int)$cid]);
            }
        }

        self::ilt_names($mod, $sessions);

        $attended = source::config_ints('iltattended', '100');
        $partial = source::config_ints('iltpartial', '90');
        $noshow = source::config_ints('iltnoshow', '80');
        $booked = [70];

        foreach (array_chunk(array_keys($sessions), 1000) as $chunk) {
            if (source::has_table($mod . '_signups') && source::has_table($mod . '_signups_status')) {
                $p = new params();
                $in = $p->in($chunk);
                $super = source::has_field($mod . '_signups_status', 'superceded') ? 'AND ss.superceded = 0' : '';
                $rs = $DB->get_recordset_sql("SELECT ss.id, su.sessionid, su.userid, ss.statuscode
                                                FROM {{$mod}_signups} su
                                                JOIN {{$mod}_signups_status} ss ON ss.signupid = su.id {$super}
                                               WHERE su.sessionid {$in}", $p->values);
                foreach ($rs as $r) {
                    $sid = (int)$r->sessionid;
                    $code = (int)$r->statuscode;
                    $secs = $sessions[$sid]['secs'];
                    if (in_array($code, $attended, true)) {
                        $state = 'attended';
                    } else if (in_array($code, $partial, true)) {
                        $state = 'partial';
                        $secs = (int)round($secs / 2);
                    } else if (in_array($code, $noshow, true)) {
                        $state = 'noshow';
                        $secs = 0;
                    } else if (in_array($code, $booked, true)) {
                        $state = 'booked';
                        $secs = 0;
                    } else {
                        continue; // Cancelled / declined / waitlisted.
                    }
                    $sessions[$sid]['att'][(int)$r->userid] = ['state' => $state, 'secs' => $secs];
                }
                $rs->close();
            }
            if (source::has_table($mod . '_session_roles')) {
                $p = new params();
                $in = $p->in($chunk);
                $rolefilter = '';
                $roles = array_filter(array_map('trim', explode(',', (string)source::config('trainerroles', ''))));
                if ($roles) {
                    $rp = new params();
                    $roleids = $DB->get_fieldset_sql("SELECT id FROM {role} WHERE shortname " . $rp->in($roles), $rp->values);
                    $rolefilter = 'AND sr.roleid ' . $p->in($roleids ?: [-1]);
                }
                $rs = $DB->get_recordset_sql("SELECT sr.id, sr.sessionid, sr.userid
                                                FROM {{$mod}_session_roles} sr
                                               WHERE sr.sessionid {$in} {$rolefilter}", $p->values);
                foreach ($rs as $r) {
                    $sessions[(int)$r->sessionid]['trainers'][(int)$r->userid] = (int)$r->userid;
                }
                $rs->close();
            }
            foreach (self::ilt_instructors($mod, $chunk) as $sid => $uids) {
                foreach ($uids as $uid) {
                    $sessions[$sid]['trainers'][$uid] = $uid;
                }
            }
        }
        foreach ($sessions as &$s) {
            $s['trainers'] = array_values($s['trainers']);
            $s['end'] = $s['start'] + $s['secs'];
        }
        return array_values($sessions);
    }

    /**
     * Session names for ILT sessions: the session's own name column when the ILT plugin has one, otherwise the
     * activity name.
     *
     * @param string $mod
     * @param array $sessions sessionid => session (by reference)
     */
    protected static function ilt_names(string $mod, array &$sessions): void {
        global $DB;
        if (!$sessions || !source::has_table($mod . '_sessions')) {
            return;
        }
        $namecol = null;
        foreach (['name', 'sessionname', 'title'] as $c) {
            if (source::has_field($mod . '_sessions', $c)) {
                $namecol = $c;
                break;
            }
        }
        $fk = source::has_field($mod . '_sessions', $mod) && source::has_table($mod) ? $mod : null;
        if (!$namecol && !$fk) {
            return;
        }
        foreach (array_chunk(array_keys($sessions), 1000) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            $cols = ['s.id'];
            $join = '';
            if ($namecol) {
                $cols[] = "s.{$namecol} AS sname";
            }
            if ($fk) {
                $cols[] = 'a.name AS aname';
                $join = "LEFT JOIN {{$mod}} a ON a.id = s.{$fk}";
            }
            $rs = $DB->get_recordset_sql("SELECT " . implode(', ', $cols) . " FROM {{$mod}_sessions} s {$join}
                                           WHERE s.id {$in}", $p->values);
            foreach ($rs as $r) {
                $name = trim((string)($r->sname ?? ''));
                if ($name === '') {
                    $name = trim((string)($r->aname ?? ''));
                }
                if ($name !== '' && isset($sessions[(int)$r->id])) {
                    $sessions[(int)$r->id]['name'] = format_string($name);
                }
            }
            $rs->close();
        }
    }

    /**
     * Instructors picked in the ILT "Add/Edit session" form ("Select instructor").
     *
     * Supports the layouts ILT/face-to-face forks use: a column on <mod>_sessions holding one or more user ids
     * (comma separated), or a link table <mod>_session_instructors / <mod>_session_trainers / <mod>_instructors with
     * (sessionid, userid|instructorid|trainerid). The column can be forced with the "ILT instructor column" setting.
     *
     * @param string $mod
     * @param int[] $sessionids
     * @return array sessionid => int[] userids
     */
    protected static function ilt_instructors(string $mod, array $sessionids): array {
        global $DB;
        $out = [];
        if (!$sessionids) {
            return $out;
        }
        $add = function($sid, $raw) use (&$out) {
            foreach (preg_split('/[\s,;|]+/', (string)$raw) as $uid) {
                $uid = (int)$uid;
                if ($uid > 0) {
                    $out[(int)$sid][$uid] = $uid;
                }
            }
        };
        // 1. Column on the sessions table.
        $forced = clean_param((string)source::config('iltinstructorfield', ''), PARAM_ALPHANUMEXT);
        $columns = $forced ? [$forced] : ['instructor', 'instructors', 'instructorid', 'instructorids', 'trainer', 'trainers',
            'trainerid', 'teacher', 'teacherid'];
        foreach ($columns as $col) {
            if (source::has_field($mod . '_sessions', $col)) {
                $p = new params();
                $in = $p->in($sessionids);
                $rs = $DB->get_recordset_sql("SELECT id, {$col} AS val FROM {{$mod}_sessions} WHERE id {$in}", $p->values);
                foreach ($rs as $r) {
                    $add($r->id, $r->val);
                }
                $rs->close();
                break;
            }
        }
        // 2. Link table.
        foreach ([$mod . '_session_instructors', $mod . '_session_instructor', $mod . '_session_trainers',
                $mod . '_instructors', $mod . '_trainers'] as $table) {
            if (!source::has_field($table, 'sessionid')) {
                continue;
            }
            $col = null;
            foreach (['userid', 'instructorid', 'trainerid', 'instructor'] as $c) {
                if (source::has_field($table, $c)) {
                    $col = $c;
                    break;
                }
            }
            if (!$col) {
                continue;
            }
            $p = new params();
            $in = $p->in($sessionids);
            $rs = $DB->get_recordset_sql("SELECT id, sessionid, {$col} AS val FROM {{$table}} WHERE sessionid {$in}", $p->values);
            foreach ($rs as $r) {
                $add($r->sessionid, $r->val);
            }
            $rs->close();
        }
        return array_map('array_values', $out);
    }

    /**
     * Instructors selected on Zoom / Google Meet activities ("Select instructor" field, stored as comma separated
     * user ids in the activity table's "instructors" column).
     *
     * @param string $table activity table (zoom, googlemeet)
     * @param int[] $instanceids
     * @return array instanceid => int[] userids
     */
    public static function activity_instructors(string $table, array $instanceids): array {
        global $DB;
        $out = [];
        if (!$instanceids || !source::has_field($table, 'instructors')) {
            return $out;
        }
        foreach (array_chunk(array_values(array_unique($instanceids)), 1000) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            $rs = $DB->get_recordset_sql("SELECT id, instructors FROM {{$table}} WHERE id {$in}", $p->values);
            foreach ($rs as $r) {
                $ids = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;|]+/',
                    (string)$r->instructors)))));
                if ($ids) {
                    $out[(int)$r->id] = $ids;
                }
            }
            $rs->close();
        }
        return $out;
    }

    /**
     * Activity names by instance id.
     *
     * @param string $table
     * @param int[] $instanceids
     * @return array instanceid => name
     */
    public static function activity_names(string $table, array $instanceids): array {
        global $DB;
        $out = [];
        if (!$instanceids || !source::has_field($table, 'name')) {
            return $out;
        }
        foreach (array_chunk(array_values(array_unique($instanceids)), 1000) as $chunk) {
            $p = new params();
            $in = $p->in($chunk);
            foreach ($DB->get_records_sql("SELECT id, name FROM {{$table}} WHERE id {$in}", $p->values) as $r) {
                $out[(int)$r->id] = format_string($r->name);
            }
        }
        return $out;
    }

    /**
     * Zoom (mod_zoom meeting reports: zoom_meeting_details + zoom_meeting_participants).
     *
     * @param int $start
     * @param int $end
     * @return array
     */
    protected static function fetch_zoom(int $start, int $end): array {
        global $DB;
        if (!source::config('usezoom', 1) || !source::has_table('zoom_meeting_details')) {
            return [];
        }
        $p = new params();
        $hasz = source::has_field('zoom_meeting_details', 'zoomid');
        $rows = $DB->get_records_sql("SELECT id, start_time, end_time, duration" . ($hasz ? ', zoomid' : '') . "
                                        FROM {zoom_meeting_details}
                                       WHERE start_time >= " . $p->add($start) . " AND start_time <= " . $p->add($end),
            $p->values);
        $sessions = [];
        $inst = $hasz ? self::activity_instructors('zoom', array_map(function($r) {
            return (int)$r->zoomid;
        }, $rows)) : [];
        $names = $hasz ? self::activity_names('zoom', array_map(function($r) {
            return (int)$r->zoomid;
        }, $rows)) : [];
        foreach ($rows as $r) {
            $secs = ((int)$r->end_time > (int)$r->start_time) ? (int)$r->end_time - (int)$r->start_time : (int)$r->duration * 60;
            $sessions[(int)$r->id] = [
                'id' => 'zoom-' . $r->id, 'source' => 'zoom', 'type' => 'online',
                'start' => (int)$r->start_time, 'secs' => $secs,
                'trainers' => $hasz ? ($inst[(int)$r->zoomid] ?? []) : [], 'att' => [],
                'end' => (int)$r->start_time + $secs, 'name' => $hasz ? ($names[(int)$r->zoomid] ?? '') : '',
            ];
        }
        if ($sessions && source::has_table('zoom_meeting_participants')) {
            foreach (array_chunk(array_keys($sessions), 1000) as $chunk) {
                $p = new params();
                $in = $p->in($chunk);
                $rs = $DB->get_recordset_sql("SELECT MIN(id) AS id, detailsid, userid, SUM(duration) AS secs
                                                FROM {zoom_meeting_participants}
                                               WHERE detailsid {$in} AND userid IS NOT NULL AND userid > 0
                                            GROUP BY detailsid, userid", $p->values);
                foreach ($rs as $r) {
                    $sessions[(int)$r->detailsid]['att'][(int)$r->userid] = ['state' => 'attended', 'secs' => (int)$r->secs];
                }
                $rs->close();
            }
        }
        return array_values($sessions);
    }

    /**
     * Google Meet (mod_googlemeet events). Sessions and scheduled length only - the plugin stores no attendance.
     *
     * @param int $start
     * @param int $end
     * @return array
     */
    protected static function fetch_gmeet(int $start, int $end): array {
        global $DB;
        $mod = clean_param((string)source::config('gmeetmodule', 'googlemeet'), PARAM_ALPHANUMEXT);
        if (!$mod || !source::has_table($mod . '_events') || !source::has_field($mod . '_events', 'eventdate')) {
            return [];
        }
        $p = new params();
        $hasdur = source::has_field($mod . '_events', 'duration');
        $fk = source::has_field($mod . '_events', $mod . 'id') ? $mod . 'id' : null;
        $rows = $DB->get_records_sql("SELECT id, eventdate" . ($hasdur ? ', duration' : '') . ($fk ? ", {$fk} AS instanceid" : '') . "
                                        FROM {{$mod}_events}
                                       WHERE eventdate >= " . $p->add($start) . " AND eventdate <= " . $p->add($end),
            $p->values);
        $out = [];
        $inst = $fk ? self::activity_instructors($mod, array_map(function($r) {
            return (int)$r->instanceid;
        }, $rows)) : [];
        $names = $fk ? self::activity_names($mod, array_map(function($r) {
            return (int)$r->instanceid;
        }, $rows)) : [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => 'gmeet-' . $r->id, 'source' => 'gmeet', 'type' => 'online',
                'start' => (int)$r->eventdate, 'secs' => $hasdur ? (int)$r->duration * 60 : 0,
                'trainers' => $fk ? ($inst[(int)$r->instanceid] ?? []) : [], 'att' => [],
                'end' => (int)$r->eventdate + ($hasdur ? (int)$r->duration * 60 : 0),
                'name' => $fk ? ($names[(int)$r->instanceid] ?? '') : '',
            ];
        }
        return $out;
    }

    /**
     * Keep only sessions relevant to the filtered population and strip non-population attendees.
     *
     * @param array $sessions
     * @param array $pop userid => mixed
     * @param bool $narrowed true when user filters are active (drop sessions with nobody from the population)
     * @return array
     */
    public static function scope(array $sessions, array $pop, bool $narrowed): array {
        $out = [];
        foreach ($sessions as $s) {
            $att = array_intersect_key($s['att'], $pop);
            $trainersinpop = array_intersect($s['trainers'], array_keys($pop));
            if ($narrowed && !$att && !$trainersinpop) {
                continue;
            }
            $s['att'] = $att;
            $out[] = $s;
        }
        return $out;
    }

    /**
     * Aggregate statistics for sessions inside [start, end].
     *
     * @param array $sessions scoped sessions
     * @param int $start
     * @param int $end
     * @return array
     */
    public static function stats(array $sessions, int $start, int $end): array {
        $r = [
            'sessions' => 0, 'f2f' => 0, 'online' => 0, 'learnersecs' => 0, 'marked' => 0, 'attendedmarks' => 0,
            'noshowusers' => [], 'trainers' => [], 'iltsessions' => 0, 'iltsecs' => 0, 'attendees' => [],
        ];
        foreach ($sessions as $s) {
            if ($s['start'] < $start || $s['start'] > $end) {
                continue;
            }
            $r['sessions']++;
            $r[$s['type']]++;
            if ($s['trainers']) {
                $r['iltsessions']++;
                $r['iltsecs'] += $s['secs'];
            }
            foreach ($s['trainers'] as $t) {
                $r['trainers'][$t] = $t;
            }
            foreach ($s['att'] as $uid => $a) {
                $r['learnersecs'] += $a['secs'];
                if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $r['attendees'][$uid] = $uid;
                }
                if ($s['source'] !== 'ilt') {
                    continue; // Attendance % / no-shows need a registration list, which only ILT has.
                }
                if ($a['state'] === 'noshow') {
                    $r['noshowusers'][$uid] = $uid;
                    $r['marked']++;
                } else if ($a['state'] === 'attended' || $a['state'] === 'partial') {
                    $r['marked']++;
                    $r['attendedmarks']++;
                }
            }
        }
        $r['attendancepct'] = $r['marked'] ? round($r['attendedmarks'] * 100 / $r['marked'], 1) : null;
        return $r;
    }
}
