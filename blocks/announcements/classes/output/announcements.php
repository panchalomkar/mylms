<?php 
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_announcements\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Renderable announcements block for admin dashboard and courses.
 */
class announcements implements renderable, templatable {
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function export_for_template(renderer_base $output) {
        global $DB, $COURSE, $USER;

        // ✅ Detect current course (or default to Site Home).
        $courseid = (isset($COURSE->id) && $COURSE->id > 1) ? $COURSE->id : 1;

        // ✅ Find “News forum” for that course or site.
        $forum = $DB->get_record('forum', ['course' => $courseid, 'type' => 'news']);
        $announcements = [];

        if ($forum) {
            // ✅ Fetch latest 4 visible announcements (was 5 before).
            $sql = "SELECT d.id AS discussionid, d.name AS title, p.message, p.modified, u.firstname, u.lastname
                    FROM {forum_discussions} d
                    JOIN {forum_posts} p ON p.discussion = d.id
                    JOIN {user} u ON u.id = p.userid
                    WHERE d.forum = :forumid AND p.parent = 0
                    ORDER BY p.modified DESC";

            $posts = $DB->get_records_sql($sql, ['forumid' => $forum->id], 0, 3);

            foreach ($posts as $post) {
                $announcements[] = [
                    'title' => format_string($post->title),
                    'author' => fullname($post),
                    'message' => shorten_text(format_text($post->message, FORMAT_HTML), 150),
                    'timeago' => $this->get_time_ago($post->modified),
                    'url' => (new \moodle_url('/mod/forum/discuss.php', ['d' => $post->discussionid]))->out(false),
                ];
            }
        }

        $result = new \stdClass();
        $result->announcements = $announcements;
        $result->hasannouncements = !empty($announcements);
        $result->canaddtopic = has_capability('mod/forum:startdiscussion', \context_system::instance());
        $result->addtopicurl = (new \moodle_url('/mod/forum/post.php', ['forum' => $forum->id ?? 0]))->out(false);

        // ✅ Add "View more" URL
        $result->viewmoreurl = (new \moodle_url('/mod/forum/view.php', ['id' => 13]))->out(false);

        return $result;
    }

    /**
     * Convert timestamp to "x days/hours/mins ago".
     */
    private function get_time_ago($timestamp) {
        $diff = time() - $timestamp;
        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);

        if ($days > 0) {
            return $days . ' day' . ($days > 1 ? 's ' : ' ') . $hours . ' hour' . ($hours > 1 ? 's ago' : ' ago');
        } elseif ($hours > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's ' : ' ') . $minutes . ' min ago';
        } else {
            return $minutes . ' min ago';
        }
    }
}
