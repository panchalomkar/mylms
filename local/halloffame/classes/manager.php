<?php
namespace local_halloffame;

defined('MOODLE_INTERNAL') || die();

/**
 * Core data-access and business-logic manager.
 *
 * v2 changes (IOMAD + dynamic departments):
 *  - All award/achievement/submission queries filtered by companyid.
 *  - Departments read from Moodle user profile fields via department_helper.
 *  - assert_same_company() guards approve/reject against cross-tenant access.
 *  - Brand gradients updated to design-system colours (#033252 / #eb980d).
 */
class manager {

    // ── Brand gradients ───────────────────────────────────────────────────────
    private static array $gradients = [
        'linear-gradient(135deg,#033252 0%,#065a96 100%)',
        'linear-gradient(135deg,#eb980d 0%,#f7c26b 100%)',
        'linear-gradient(135deg,#033252 0%,#eb980d 100%)',
        'linear-gradient(135deg,#065a96 0%,#0a8fd1 100%)',
        'linear-gradient(135deg,#c47a0a 0%,#eb980d 100%)',
        'linear-gradient(135deg,#033252 0%,#1a6b9e 60%,#eb980d 100%)',
    ];

    public static function gradient_for(int $id): string {
        return self::$gradients[$id % count(self::$gradients)];
    }

    // ── AWARDS ────────────────────────────────────────────────────────────────

    public static function create_award(array $data): int {
        global $DB, $USER;

        $r              = new \stdClass();
        $r->userid      = (int) $data['userid'];
        $r->department  = trim($data['department'] ?? '');
        $r->category    = trim($data['category']   ?? '');
        $r->title       = trim($data['title']);
        $r->month       = (int)($data['month'] ?? (int) date('n'));
        $r->year        = (int)($data['year']  ?? (int) date('Y'));
        $r->message     = trim($data['message'] ?? '');
        $r->image       = trim($data['image']   ?? '');
        $r->createdby   = (int) $USER->id;
        // IOMAD: stamp with current company.
        $r->companyid   = (int)($data['companyid'] ?? iomad_helper::get_current_companyid());
        $r->timecreated = time();

        $newid = $DB->insert_record('halloffame_awards', $r);
        $r->id = $newid;
        notification_helper::notify_award_recipient($r);
        return $newid;
    }

    /**
     * Get awards with optional filters, restricted to the current user's company.
     *
     * BEFORE: no company filter.
     * AFTER:  iomad_helper::company_sql_fragment() appended to WHERE.
     */
    public static function get_awards(array $filters = []): array {
        global $DB;

        [$where, $params] = self::build_award_where($filters);
        [$iomad_sql, $iomad_p] = iomad_helper::company_sql_fragment('a.userid', 'aw');
        $where  .= $iomad_sql;
        $params  = array_merge($params, $iomad_p);

        $sql = "SELECT a.*,
                       " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname,
                       u.picture, u.imagealt, u.email,
                       u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {halloffame_awards} a
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0
                 WHERE $where
              ORDER BY a.timecreated DESC";

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as &$r) {
            $r->likecount  = self::get_like_count($r->id, 'award');
            $r->userliked  = self::user_has_liked($r->id, 'award');
            $r->gradient   = self::gradient_for((int) $r->id);
            $r->monthname  = \local_halloffame_month_name((int) $r->month);
            // Fill department from profile field when not stored on the award itself.
            if (empty($r->department)) {
                $r->department = department_helper::get_user_department((int) $r->userid);
            }
        }
        unset($r);

        return array_values($records);
    }

    private static function build_award_where(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['month']))      { $where[] = 'a.month = :month';               $params['month']      = (int) $f['month']; }
        if (!empty($f['year']))       { $where[] = 'a.year = :year';                 $params['year']       = (int) $f['year']; }
        if (!empty($f['quarter']))    {
            $q = (int) $f['quarter']; $sm = ($q-1)*3+1; $em = $sm+2;
            $where[] = 'a.month >= :qstart AND a.month <= :qend';
            $params['qstart'] = $sm; $params['qend'] = $em;
        }
        if (!empty($f['department'])) { $where[] = 'a.department = :department';     $params['department'] = $f['department']; }
        if (!empty($f['category']))   { $where[] = 'a.category = :category';         $params['category']   = $f['category']; }
        return [implode(' AND ', $where), $params];
    }

    public static function update_award(int $id, array $data): bool {
        global $DB;
        $r = (object) $data; $r->id = $id;
        return $DB->update_record('halloffame_awards', $r);
    }

    public static function delete_award(int $id): bool {
        global $DB;
        $DB->delete_records('halloffame_likes', ['itemid' => $id, 'itemtype' => 'award']);
        return (bool) $DB->delete_records('halloffame_awards', ['id' => $id]);
    }

    // ── SUBMISSIONS ───────────────────────────────────────────────────────────

    public static function submit_achievement(array $data): int {
        global $DB, $USER;

        $r              = new \stdClass();
        $r->userid      = (int) $USER->id;
        $r->title       = trim($data['title']);
        $r->issuer      = trim($data['issuer']    ?? '');
        $r->issuedate   = (int)($data['issuedate'] ?? 0);
        $r->type        = trim($data['type']      ?? '');
        $r->notes       = trim($data['notes']     ?? '');
        $r->fileurl     = trim($data['fileurl']   ?? '');
        $r->companyid   = (int)($data['companyid'] ?? iomad_helper::get_current_companyid());
        $r->status      = 'pending';
        $r->timecreated = time();

        $newid = $DB->insert_record('halloffame_submissions', $r);
        $r->id = $newid;
        notification_helper::notify_submission($r);
        return $newid;
    }

    /**
     * BEFORE: WHERE status = ?
     * AFTER:  + company_sql_fragment for IOMAD isolation.
     */
    public static function get_submissions(string $status = 'pending'): array {
        global $DB;

        [$iomad_sql, $iomad_p] = iomad_helper::company_sql_fragment('s.userid', 'sb');

        $sql = "SELECT s.*,
                       " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname
                  FROM {halloffame_submissions} s
                  JOIN {user} u ON u.id = s.userid AND u.deleted = 0
                 WHERE s.status = :status {$iomad_sql}
              ORDER BY s.timecreated DESC";

        return array_values($DB->get_records_sql($sql,
            array_merge(['status' => $status], $iomad_p)));
    }

    // ── ACHIEVEMENTS ──────────────────────────────────────────────────────────

    public static function approve_achievement(int $sid): bool {
        global $DB, $USER;

        $sub = $DB->get_record('halloffame_submissions', ['id' => $sid], '*', MUST_EXIST);
        self::assert_same_company($sub->userid); // Cross-tenant guard.

        $a              = new \stdClass();
        $a->userid      = $sub->userid;
        $a->title       = $sub->title;
        $a->issuer      = $sub->issuer;
        $a->issuedate   = $sub->issuedate;
        $a->type        = $sub->type;
        $a->notes       = $sub->notes;
        $a->fileurl     = $sub->fileurl;
        $a->companyid   = (int)($sub->companyid ?? iomad_helper::get_user_companyid($sub->userid));
        $a->status      = 1;
        $a->timecreated = time();
        $a->approvedby  = (int) $USER->id;

        $DB->insert_record('halloffame_achievements', $a);
        $DB->set_field('halloffame_submissions', 'status', 'approved', ['id' => $sid]);
        notification_helper::notify_approved($a);
        return true;
    }

    public static function reject_achievement(int $sid): bool {
        global $DB;

        $sub = $DB->get_record('halloffame_submissions', ['id' => $sid], '*', MUST_EXIST);
        self::assert_same_company($sub->userid);

        $DB->set_field('halloffame_submissions', 'status', 'rejected', ['id' => $sid]);
        notification_helper::notify_rejected($sub);
        return true;
    }

    /**
     * BEFORE: no company filter.
     * AFTER:  company_sql_fragment appended; department backfilled from profile field.
     */
    public static function get_achievements(array $filters = []): array {
        global $DB;

        $params = ['status' => 1];
        $where  = ['a.status = :status'];

        if (!empty($filters['type'])) { $where[] = 'a.type = :type';                 $params['type'] = $filters['type']; }
        if (!empty($filters['year'])) {
            $y = (int) $filters['year'];
            $where[] = 'a.issuedate >= :ys AND a.issuedate <= :ye';
            $params['ys'] = mktime(0, 0, 0, 1, 1, $y);
            $params['ye'] = mktime(23, 59, 59, 12, 31, $y);
        }

        [$iomad_sql, $iomad_p] = iomad_helper::company_sql_fragment('a.userid', 'ac');
        if ($iomad_sql) {
            $where[]  = ltrim($iomad_sql, ' AND ');
            $params   = array_merge($params, $iomad_p);
        }

        $sql = "SELECT a.*,
                       " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname,
                       u.picture, u.imagealt, u.email,
                       u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {halloffame_achievements} a
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY a.timecreated DESC";

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as &$r) {
            $r->likecount          = self::get_like_count($r->id, 'achievement');
            $r->userliked          = self::user_has_liked($r->id, 'achievement');
            $r->issuedateformatted = $r->issuedate
                ? userdate($r->issuedate, get_string('strftimedate', 'langconfig')) : '';
            $ext            = $r->fileurl ? strtolower(pathinfo($r->fileurl, PATHINFO_EXTENSION)) : '';
            $r->fileisimage = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
            $r->department  = department_helper::get_user_department((int) $r->userid);
        }
        unset($r);

        return array_values($records);
    }

    // ── LIKES ─────────────────────────────────────────────────────────────────

    public static function toggle_like(int $itemid, string $itemtype): array {
        global $DB, $USER;
        $existing = $DB->get_record('halloffame_likes',
            ['userid' => $USER->id, 'itemid' => $itemid, 'itemtype' => $itemtype]);
        if ($existing) {
            $DB->delete_records('halloffame_likes', ['id' => $existing->id]);
            $liked = false;
        } else {
            $DB->insert_record('halloffame_likes', (object)[
                'userid' => (int)$USER->id, 'itemid' => $itemid,
                'itemtype' => $itemtype,    'timecreated' => time(),
            ]);
            $liked = true;
        }
        return ['liked' => $liked, 'count' => self::get_like_count($itemid, $itemtype)];
    }

    public static function get_like_count(int $itemid, string $itemtype): int {
        global $DB;
        return (int) $DB->count_records('halloffame_likes',
            ['itemid' => $itemid, 'itemtype' => $itemtype]);
    }

    public static function user_has_liked(int $itemid, string $itemtype): bool {
        global $DB, $USER;
        if (!isloggedin() || isguestuser()) return false;
        return $DB->record_exists('halloffame_likes',
            ['userid' => $USER->id, 'itemid' => $itemid, 'itemtype' => $itemtype]);
    }

    // ── CATEGORIES ────────────────────────────────────────────────────────────

    /** AFTER: delegates to iomad_helper for company-scoped categories. */
    public static function get_categories(): array {
        return iomad_helper::get_scoped_categories();
    }

    public static function save_category(string $name, int $id = 0, string $desc = ''): int {
        global $DB;
        $name = trim($name);
        if ($id) {
            $DB->update_record('halloffame_categories',
                (object)['id' => $id, 'name' => $name, 'description' => $desc]);
            return $id;
        }
        return $DB->insert_record('halloffame_categories', (object)[
            'name' => $name, 'description' => $desc,
            'companyid' => iomad_helper::get_current_companyid(),
        ]);
    }

    // ── DEPARTMENTS (dynamic) ──────────────────────────────────────────────────

    /**
     * BEFORE: SELECT * FROM {halloffame_departments}
     * AFTER:  department_helper reads Moodle user_info_data for the configured
     *         custom profile field (shortname = 'department' by default).
     */
    public static function get_departments(): array {
        return department_helper::get_departments_for_filter(
            iomad_helper::get_current_companyid()
        );
    }

    public static function save_department(string $name, int $id = 0): int {
        global $DB;
        $name = trim($name);
        if ($id) { $DB->set_field('halloffame_departments', 'name', $name, ['id' => $id]); return $id; }
        return $DB->insert_record('halloffame_departments', (object)[
            'name' => $name, 'companyid' => iomad_helper::get_current_companyid(),
        ]);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    public static function months_list(): array {
        $keys = ['january','february','march','april','may','june',
                 'july','august','september','october','november','december'];
        $out  = [];
        foreach ($keys as $i => $k) { $out[$i + 1] = get_string($k, 'local_halloffame'); }
        return $out;
    }

    public static function validate_upload(array $file): string {
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array_map('trim', explode(',',
            get_config('local_halloffame', 'allowed_filetypes') ?: 'pdf,jpg,jpeg,png'));
        if (!in_array($ext, $allowed, true)) return get_string('filetypeerror', 'local_halloffame');
        $maxmb = (int)(get_config('local_halloffame', 'max_filesize_mb') ?: 5);
        if ($file['size'] > $maxmb * 1024 * 1024) return get_string('filesizeerror', 'local_halloffame');
        return '';
    }

    public static function store_upload(array $file, string $filearea, int $itemid): string {
        $context = \context_system::instance();
        $fs      = get_file_storage();
        $rec     = ['contextid' => $context->id, 'component' => 'local_halloffame',
                    'filearea' => $filearea, 'itemid' => $itemid,
                    'filepath' => '/', 'filename' => clean_filename($file['name'])];
        $stored = $fs->create_file_from_pathname($rec, $file['tmp_name']);
        if (!$stored) return '';
        return \moodle_url::make_pluginfile_url(
            $context->id, 'local_halloffame', $filearea, $itemid, '/', $rec['filename']
        )->out();
    }

    /**
     * Guard: throws moodle_exception if $target_userid is outside the current user's company.
     * No-op for site admins and non-IOMAD installs.
     */
    public static function assert_same_company(int $target_userid): void {
        global $USER;
        if (!iomad_helper::is_iomad() || is_siteadmin($USER)) return;
        $mine   = iomad_helper::get_user_companyid((int) $USER->id);
        $theirs = iomad_helper::get_user_companyid($target_userid);
        if ($mine !== $theirs) throw new \moodle_exception('accessdenied', 'local_halloffame');
    }
}
