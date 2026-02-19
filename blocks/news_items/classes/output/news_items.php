<?php
namespace block_news_items\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class news_items implements renderable, templatable {
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function export_for_template(renderer_base $output) {
        global $DB, $COURSE, $USER;

        // ✅ Get current course ID (fallback to Site Home if dashboard)
        $courseid = isset($COURSE->id) ? $COURSE->id : 1;

        // ✅ Find the "Announcements" forum in that course
        $forum = $DB->get_record('forum', ['course' => $courseid, 'type' => 'news']);

        $announcements = [];

        if ($forum) {
            // ✅ Get the latest 5 top-level discussion posts (announcements)
            $sql = "SELECT d.id AS discussionid, d.name AS title, p.message, p.modified
                    FROM {forum_discussions} d
                    JOIN {forum_posts} p ON p.discussion = d.id
                    WHERE d.forum = :forumid AND p.parent = 0
                    ORDER BY p.modified DESC";
            
            $posts = $DB->get_records_sql($sql, ['forumid' => $forum->id], 0, 5);

            foreach ($posts as $post) {
                $announcements[] = [
                    'title' => format_string($post->title),
                    'message' => shorten_text(format_text($post->message, FORMAT_HTML), 150),
                    'timecreated' => userdate($post->modified, '%d %b %Y, %I:%M %p'),
                    'url' => (new \moodle_url('/mod/forum/discuss.php', ['d' => $post->discussionid]))->out(false)
                ];
            }
        }

        $result = new \stdClass();
        $result->announcements = $announcements;
        $result->hasannouncements = !empty($announcements);
        return $result;
    }
}
