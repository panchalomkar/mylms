<?php
namespace local_halloffame\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use local_halloffame\manager;

class renderer extends plugin_renderer_base {

    /** Render the full awards grid from a filter array. */
    public function render_awards(array $filters = []): string {
        $awards = [];
        foreach (manager::get_awards($filters) as $a) {
            $awards[] = $this->award_to_ctx($a);
        }
        return $this->render_from_template('local_halloffame/awards', ['awards' => $awards]);
    }

    /** Render the full achievements feed from a filter array. */
    public function render_achievements(array $filters = []): string {
        $items = [];
        foreach (manager::get_achievements($filters) as $a) {
            $items[] = $this->achievement_to_ctx($a);
        }
        return $this->render_from_template('local_halloffame/achievements', ['achievements' => $items]);
    }

    /** Render the admin review queue. */
    public function render_review_queue(): string {
        $subs = [];
        foreach (manager::get_submissions('pending') as $s) {
            $ext     = $s->fileurl ? strtolower(pathinfo($s->fileurl, PATHINFO_EXTENSION)) : '';
            $isimage = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
            $subs[]  = [
                'id'            => $s->id,
                'fullname'      => $s->fullname,
                'title'         => $s->title,
                'issuer'        => $s->issuer ?? '',
                'issuedatefmt'  => $s->issuedate
                    ? userdate($s->issuedate, get_string('strftimedate', 'langconfig')) : '',
                'type'          => $s->type   ?? '',
                'notes'         => $s->notes  ?? '',
                'fileurl'       => $s->fileurl ?? '',
                'fileisimage'   => $isimage,
                'timecreated'   => userdate($s->timecreated),
                'approveurl'    => (new \moodle_url('/local/halloffame/pages/review.php', [
                    'action' => 'approve', 'sid' => $s->id, 'sesskey' => sesskey(),
                ]))->out(false),
                'rejecturl'     => (new \moodle_url('/local/halloffame/pages/review.php', [
                    'action' => 'reject', 'sid' => $s->id, 'sesskey' => sesskey(),
                ]))->out(false),
                'comments'      => manager::get_comments($s->id, 'submission'),
                'commentcount'  => manager::get_comment_count($s->id, 'submission'),
                'addcommenturl' => (new \moodle_url('/local/halloffame/pages/review.php'))->out(false),
            ];
        }
        return $this->render_from_template('local_halloffame/admin_review', [
            'submissions'    => $subs,
            'hassubmissions' => !empty($subs),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function award_to_ctx(\stdClass $a): array {
        $user      = \core_user::get_user($a->userid);
        $canmanage = has_capability('local/halloffame:approve', \context_system::instance());
        return [
            'id'           => $a->id,
            'userid'       => $a->userid,
            'fullname'     => $a->fullname,
            'userpicture'  => $this->output->user_picture($user, ['size' => 80, 'class' => 'hof-avatar']),
            'title'        => $a->title,
            'department'   => $a->department ?? '',
            'category'     => $a->category   ?? '',
            'month'        => $a->month,
            'monthname'    => $a->monthname  ?? '',
            'year'         => $a->year,
            'message'      => $a->message    ?? '',
            'image'        => $a->image      ?? '',
            'likecount'    => $a->likecount,
            'userliked'    => (bool) $a->userliked,
            'commentcount' => $a->commentcount,
            'gradient'     => $a->gradient,
            'canmanage'    => $canmanage,
            // Edit URL for pencil icon — only populated when user can manage.
            'editurl'      => $canmanage
                ? (new \moodle_url('/local/halloffame/pages/edit_award.php',
                    ['id' => $a->id]))->out(false)
                : '',
            'deleteurl'    => $canmanage
                ? (new \moodle_url('/local/halloffame/pages/delete_award.php',
                    ['id' => $a->id, 'sesskey' => sesskey()]))->out(false)
                : '',
        ];
    }

   private function achievement_to_ctx(\stdClass $a): array {
        global $USER;
        $user        = \core_user::get_user($a->userid);
        $canmanage   = has_capability('local/halloffame:approve', \context_system::instance());
        $isowner     = isloggedin() && (int)$a->userid === (int)$USER->id;
        $candownload = $canmanage || $isowner;
        return [
            'id'                 => $a->id,
            'userid'             => $a->userid,
            'fullname'           => $a->fullname,
            'userpicture'        => $this->output->user_picture($user, ['size' => 52, 'class' => 'hof-ach-avatar']),
            'userpictureurl'     => manager::user_picture_url($user, 52),
            'title'              => $a->title,
            'issuer'             => $a->issuer             ?? '',
            'issuedateformatted' => $a->issuedateformatted ?? '',
            'type'               => $a->type               ?? '',
            'fileurl'            => $a->fileurl            ?? '',
            'fileisimage'        => $a->fileisimage        ?? false,
            'notes'              => $a->notes              ?? '',
            'likecount'          => $a->likecount,
            'userliked'          => (bool) $a->userliked,
            'commentcount'       => $a->commentcount,
            'department'         => $a->department ?? '',
            'candownload'        => $candownload ? 1 : 0,
            'canmanage'          => $canmanage,
            'editurl'            => $canmanage
                ? (new \moodle_url('/local/halloffame/pages/edit_achievement.php',
                    ['id' => $a->id]))->out(false)
                : '',
            'deleteurl'          => $canmanage
                ? (new \moodle_url('/local/halloffame/pages/delete_achievement.php',
                    ['id' => $a->id, 'sesskey' => sesskey()]))->out(false)
                : '',
        ];
    }
}
