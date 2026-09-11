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
 *
 * v3 changes:
 *  - Comments support: get_comments(), add_comment(), delete_comment().
 *  - get_comment_count() for card display.
 *
 * v4 changes:
 *  - get_award() single-record fetch for edit page.
 *  - update_award() already existed; edit page reuses it.
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
        $r->companyid   = (int)($data['companyid'] ?? iomad_helper::get_current_companyid());
        $r->timecreated = time();

        $newid = $DB->insert_record('halloffame_awards', $r);
        $r->id = $newid;
        notification_helper::notify_award_recipient($r);
        return $newid;
    }

    /**
     * Fetch a single award record by ID (for edit page).
     * Returns null if not found.
     */
    public static function get_award(int $id): ?\stdClass {
        global $DB;
        $r = $DB->get_record('halloffame_awards', ['id' => $id]);
        if (!$r) return null;
        $months       = self::months_list();
        $r->monthname = $months[$r->month] ?? '';
        $r->gradient  = self::gradient_for((int) $r->id);
        return $r;
    }

    /**
     * Get awards with optional filters, restricted to the current user's company.
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
            $r->likecount      = self::get_like_count($r->id, 'award');
            $r->userliked      = self::user_has_liked($r->id, 'award');
            $r->commentcount   = self::get_comment_count($r->id, 'award');
            $r->gradient       = self::gradient_for((int) $r->id);
            $months            = self::months_list();
            $r->monthname      = $months[$r->month] ?? '';
            $user              = \core_user::get_user((int) $r->userid);
            $r->userpictureurl = $user ? self::get_user_picture_url($user) : '';
            if (empty($r->department)) {
                $r->department = department_helper::get_user_department((int) $r->userid);
            }
        }
        unset($r);

        return array_values($records);
    }
/**
 * Get users who liked/reacted to an item, most recent first.
 *
 * @param int $itemid
 * @param string $itemtype
 * @param int $limit 0 = no limit
 * @return array of ['userid','fullname','userpictureurl','timecreated']
 */
public static function get_likers(int $itemid, string $itemtype, int $limit = 0): array {
    global $DB;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt, u.email,
                   u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                   l.timecreated
              FROM {halloffame_likes} l
              JOIN {user} u ON u.id = l.userid AND u.deleted = 0
             WHERE l.itemid = :itemid AND l.itemtype = :itemtype
          ORDER BY l.timecreated DESC";

    $records = $DB->get_records_sql(
        $sql,
        ['itemid' => $itemid, 'itemtype' => $itemtype],
        0,
        $limit
    );

    $result = [];
    foreach ($records as $r) {
        $user = \core_user::get_user((int) $r->id);
        $result[] = [
            'userid'         => (int) $r->id,
            'fullname'       => $user ? fullname($user) : '',
            'userpictureurl' => $user ? self::get_user_picture_url($user) : '',
            'timecreated'    => (int) $r->timecreated,
        ];
    }
    return $result;
}
    private static function build_award_where(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['month']))      { $where[] = 'a.month = :month';           $params['month']      = (int) $f['month']; }
        if (!empty($f['year']))       { $where[] = 'a.year = :year';             $params['year']       = (int) $f['year']; }
        if (!empty($f['quarter']))    {
            $q = (int) $f['quarter']; $sm = ($q-1)*3+1; $em = $sm+2;
            $where[] = 'a.month >= :qstart AND a.month <= :qend';
            $params['qstart'] = $sm; $params['qend'] = $em;
        }
        if (!empty($f['department'])) { $where[] = 'a.department = :department'; $params['department'] = $f['department']; }
        if (!empty($f['category']))   { $where[] = 'a.category = :category';     $params['category']   = $f['category']; }
        return [implode(' AND ', $where), $params];
    }

    public static function update_award(int $id, array $data): bool {
        global $DB;
        $r     = (object) $data;
        $r->id = $id;
        return $DB->update_record('halloffame_awards', $r);
    }

    public static function delete_award(int $id): bool {
        global $DB;
        $DB->delete_records('halloffame_likes',   ['itemid' => $id, 'itemtype' => 'award']);
        $DB->delete_records('halloffame_comments', ['itemid' => $id, 'itemtype' => 'award']);
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
public static function update_submission(int $id, array $data): bool {
    global $DB;

    // Allowed fields (whitelist)
    $allowed = ['title', 'issuer', 'type', 'notes', 'issuedate', 'fileurl'];

    // Use global stdClass (FIX)
    $record = new \stdClass();
    $record->id = $id;

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $data)) {
            continue;
        }

        $value = $data[$field];

        // Normalize string values
        if (is_string($value)) {
            $value = trim($value);
            $record->$field = ($value === '') ? null : $value;
        } else {
            $record->$field = $value;
        }
    }

    // Optional: ensure record exists before update (safer)
    if (!$DB->record_exists('halloffame_submissions', ['id' => $id])) {
        return false;
    }

    // Perform update
    return $DB->update_record('halloffame_submissions', $record);
}

 public static function delete_submission(int $id): bool {
        global $DB;
        $DB->delete_records('halloffame_comments', ['itemid' => $id, 'itemtype' => 'submission']);
        return (bool) $DB->delete_records('halloffame_submissions', ['id' => $id]);
    }
    // ── ACHIEVEMENTS ──────────────────────────────────────────────────────────

    public static function approve_achievement(int $sid): bool {
        global $DB, $USER;

        $sub = $DB->get_record('halloffame_submissions', ['id' => $sid], '*', MUST_EXIST);
        self::assert_same_company($sub->userid);

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

    public static function get_achievements(array $filters = []): array {
        global $DB;

        $params = ['status' => 1];
        $where  = ['a.status = :status'];

       // Type
if (!empty($filters['type'])) {
    $where[] = 'a.type = :type';
    $params['type'] = $filters['type'];
}

// Year
if (!empty($filters['year'])) {
    $y = (int) $filters['year'];
    $where[] = 'a.issuedate >= :ys AND a.issuedate <= :ye';
    $params['ys'] = mktime(0, 0, 0, 1, 1, $y);
    $params['ye'] = mktime(23, 59, 59, 12, 31, $y);
}

// Month (NEW)
if (!empty($filters['month'])) {
    $m = (int) $filters['month'];
    $where[] = "MONTH(FROM_UNIXTIME(a.issuedate)) = :month";
    $params['month'] = $m;
}

// Quarter (NEW)
if (!empty($filters['quarter'])) {
    $q = (int) $filters['quarter'];
    $sm = ($q - 1) * 3 + 1;
    $em = $sm + 2;

    $where[] = "MONTH(FROM_UNIXTIME(a.issuedate)) >= :qstart 
                AND MONTH(FROM_UNIXTIME(a.issuedate)) <= :qend";
    $params['qstart'] = $sm;
    $params['qend']   = $em;
}

// Department (NEW)
if (!empty($filters['department'])) {
    $where[] = "u.department = :department";
    $params['department'] = $filters['department'];
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
            $r->commentcount       = self::get_comment_count($r->id, 'achievement');
            $r->notes = $r->notes;
            $r->issuedateformatted = $r->issuedate
                ? userdate($r->issuedate, get_string('strftimedate', 'langconfig')) : '';
            $ext                   = $r->fileurl ? strtolower(pathinfo($r->fileurl, PATHINFO_EXTENSION)) : '';
            $r->fileisimage        = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
            $r->department         = department_helper::get_user_department((int) $r->userid);
            $user                  = \core_user::get_user((int) $r->userid);
            $r->userpictureurl     = $user ? self::get_user_picture_url($user) : '';
        }
        unset($r);

        return array_values($records);
    }
    /**
     * Update a single achievement record.
     */
    public static function update_achievement(int $id, array $data): bool {
        global $DB;
        $allowed = ['title', 'issuer', 'type', 'notes', 'issuedate'];
        $r = ['id' => $id];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $r[$k] = is_string($data[$k]) ? trim($data[$k]) : $data[$k];
            }
        }
        return $DB->update_record('halloffame_achievements', (object) $r);
    }

    /**
     * Delete an achievement and its comment thread.
     */
    public static function delete_achievement(int $id): bool {
        global $DB;
        $DB->delete_records('halloffame_likes',    ['itemid' => $id, 'itemtype' => 'achievement']);
        $DB->delete_records('halloffame_comments', ['itemid' => $id, 'itemtype' => 'achievement']);
        return (bool) $DB->delete_records('halloffame_achievements', ['id' => $id]);
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
                'userid'      => (int)$USER->id, 'itemid' => $itemid,
                'itemtype'    => $itemtype,       'timecreated' => time(),
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

    // ── COMMENTS ──────────────────────────────────────────────────────────────

    public static function get_comments(int $itemid, string $itemtype): array {
        global $DB, $USER;

        $sql = "SELECT c.*,
                       " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname,
                       u.picture, u.imagealt, u.email,
                       u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {halloffame_comments} c
                  JOIN {user} u ON u.id = c.userid AND u.deleted = 0
                 WHERE c.itemid = :itemid
                   AND c.itemtype = :itemtype
                   AND c.deleted = 0
              ORDER BY c.timecreated ASC";

        $records = $DB->get_records_sql($sql, ['itemid' => $itemid, 'itemtype' => $itemtype]);

        $isadmin  = has_capability('local/halloffame:approve', \context_system::instance());
        $comments = [];
        


        foreach ($records as $c) {
            $user = \core_user::get_user($c->userid);
            $pics = self::get_user_picture_urls($user);
            $user         = \core_user::get_user($c->userid);
            $isauthor     = ((int)$c->userid === (int)$USER->id);
            $comments[]   = [
                'id'             => (int) $c->id,
                'userid'         => (int) $c->userid,
                'fullname'       => $c->fullname,
                'userpictureurl'       => $pics['weburl'],
    'userpicturemobileurl' => $pics['mobileurl'],
                'message'        => format_text($c->message, FORMAT_PLAIN),
                'rawmessage'     => $c->message,
                'timecreated'    => (int) $c->timecreated,
                'timecreatedstr' => userdate($c->timecreated,
                    get_string('strftimedatetimeshort', 'langconfig')),
                'canbedeleted'   => ($isadmin || $isauthor),
                'canbeedited'    => $isauthor,   // Only the author can edit their own comment.
            ];
        }
        return $comments;
    }

    /**
     * Edit (update) the message of a comment.
     * Only the comment author may update; admins may not edit others' words.
     */
    public static function edit_comment(int $commentid, string $message): array {
        global $DB, $USER;

        $message = clean_param(trim($message), PARAM_TEXT);
        if ($message === '') {
            throw new \moodle_exception('invalidrequest', 'local_halloffame');
        }

        $c = $DB->get_record('halloffame_comments', ['id' => $commentid, 'deleted' => 0], '*', MUST_EXIST);

        // Only the author may edit their own comment.
        if ((int)$c->userid !== (int)$USER->id) {
            throw new \moodle_exception('cannoteditcomment', 'local_halloffame');
        }

        $DB->set_field('halloffame_comments', 'message', $message, ['id' => $commentid]);

        return [
            'success' => true,
            'message' => format_text($message, FORMAT_PLAIN),
        ];
    }

    public static function add_comment(int $itemid, string $itemtype, string $message): array {
        global $DB, $USER;

        $message = clean_param(trim($message), PARAM_TEXT);
        if ($message === '') {
            throw new \moodle_exception('invalidrequest', 'local_halloffame');
        }

        $c              = new \stdClass();
        $c->itemid      = $itemid;
        $c->itemtype    = $itemtype;
        $c->userid      = (int) $USER->id;
        $c->message     = $message;
        $c->timecreated = time();
        $c->deleted     = 0;

        $id   = $DB->insert_record('halloffame_comments', $c);
        $user = \core_user::get_user((int) $USER->id);

        return [
            'id'             => $id,
            'userid'         => (int) $USER->id,
            'fullname'       => fullname($user),
            'userpictureurl' => self::get_user_picture_url($user),
            'message'        => format_text($message, FORMAT_PLAIN),
            'rawmessage'     => $message,
            'timecreated'    => $c->timecreated,
            'timecreatedstr' => userdate($c->timecreated,
                get_string('strftimedatetimeshort', 'langconfig')),
            'canbedeleted'   => true,
            'canbeedited'    => true,  // Author of newly-added comment can always edit it.
        ];
    }

    public static function delete_comment(int $commentid): bool {
        global $DB, $USER;

        $c       = $DB->get_record('halloffame_comments', ['id' => $commentid], '*', MUST_EXIST);
        $isadmin = has_capability('local/halloffame:approve', \context_system::instance());

        if (!$isadmin && (int)$c->userid !== (int)$USER->id) {
            throw new \moodle_exception('accessdenied', 'local_halloffame');
        }
        return (bool) $DB->set_field('halloffame_comments', 'deleted', 1, ['id' => $commentid]);
    }

    public static function get_comment_count(int $itemid, string $itemtype): int {
        global $DB;
        return (int) $DB->count_records('halloffame_comments', [
            'itemid'  => $itemid, 'itemtype' => $itemtype, 'deleted' => 0,
        ]);
    }

     private static function get_user_picture_url(\stdClass $user): string {
    global $PAGE;

    $userpic = new \user_picture($user);
    $userpic->size = 200; // Returns f2

    return $userpic->get_url($PAGE)->out(false);
}
    private static function get_user_picture_urls(\stdClass $user, string $token = ''): array {
    global $PAGE, $CFG;

    $usercontext = \context_user::instance($user->id);

    // Web URL.
    $userpicture = new \user_picture($user);
    $userpicture->size = 100;
    $weburl = $userpicture->get_url($PAGE)->out(false);

    // Mobile/API URL.
    $mobileurl = $CFG->wwwroot .
        "/webservice/pluginfile.php/" .
        $usercontext->id .
        "/user/icon/remui/f2";

    if (!empty($token)) {
        $mobileurl .= '?token=' . urlencode($token);
    }

    return [
        'weburl' => $weburl,
        'mobileurl' => $mobileurl,
    ];
}

    // ── CATEGORIES ────────────────────────────────────────────────────────────

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

    // ── DEPARTMENTS ───────────────────────────────────────────────────────────

    public static function get_departments(): array {
        return department_helper::get_departments_for_filter(
            iomad_helper::get_current_companyid()
        );
    }

    public static function save_department(string $name, int $id = 0): int {
        global $DB;
        $name = trim($name);
        if ($id) {
            $DB->set_field('halloffame_departments', 'name', $name, ['id' => $id]);
            return $id;
        }
        return $DB->insert_record('halloffame_departments', (object)[
            'name' => $name, 'companyid' => iomad_helper::get_current_companyid(),
        ]);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    public static function months_list(): array {
        $keys = ['january','february','march','april','may','june',
                 'july','august','september','october','november','december'];
        $out  = [];
        foreach ($keys as $i => $k) {
            $out[$i + 1] = get_string($k, 'local_halloffame');
        }
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
        $rec     = [
            'contextid' => $context->id, 'component' => 'local_halloffame',
            'filearea'  => $filearea,     'itemid'    => $itemid,
            'filepath'  => '/',           'filename'  => clean_filename($file['name']),
        ];
        $stored = $fs->create_file_from_pathname($rec, $file['tmp_name']);
        if (!$stored) return '';
        return \moodle_url::make_pluginfile_url(
            $context->id, 'local_halloffame', $filearea, $itemid, '/', $rec['filename']
        )->out();
    }

    public static function assert_same_company(int $target_userid): void {
        global $USER;
        if (!iomad_helper::is_iomad() || is_siteadmin($USER)) return;
        $mine   = iomad_helper::get_user_companyid((int) $USER->id);
        $theirs = iomad_helper::get_user_companyid($target_userid);
        if ($mine !== $theirs) throw new \moodle_exception('accessdenied', 'local_halloffame');
    }
}
