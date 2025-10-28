<?php
defined('MOODLE_INTERNAL') || die();

function local_incourse_render_course_index($course) {
    global $OUTPUT, $DB;

    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $completion = new completion_info($course);

    $html = html_writer::start_tag('div', ['class' => 'space-y-2', 'id' => 'accordion-container']);

    foreach ($sections as $section) {
        // Skip hidden sections or the "General / Announcements" section
        if (!$section->visible || $section->section == 0) {
            continue;
        }

        $sectionname = format_string($section->name ?: get_section_name($course, $section->section));

        // === Calculate section progress ===
        $activities = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $total = count($activities);
        $completed = 0;

        foreach ($activities as $cmid) {
            $cmid = (int)$cmid;
            if (empty($cmid) || !isset($modinfo->cms[$cmid])) continue;
            $cm = $modinfo->cms[$cmid];
            if ($completion->is_enabled($cm) && $completion->get_data($cm)->completionstate > 0) {
                $completed++;
            }
        }

        $progress = ($total > 0) ? round(($completed / $total) * 100) : 0;
        $progresswidth = $progress . '%';

        // === Accordion Header ===
        $html .= '
        <div class="rounded-lg overflow-hidden mb-2 shadow" style="background: #1a305f;">
            <button class="w-full flex items-center justify-between p-3 hover:bg-blue-800 transition accordion-header" data-section="' . $section->id . '">
                <div class="flex items-center">
                    <span class="material-icons ml-2 transform transition-transform duration-200">chevron_right</span>
                    <span class="font-semibold">' . $sectionname . '</span>
                </div>
                <div class="flex items-center">
                    <span class="text-xs mr-2">' . $progress . '%</span>
                    <div class="w-10 bg-blue-800 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full" style="width:' . $progresswidth . ';background:#ec9707;"></div>
                    </div>
                </div>
            </button>

            <!-- Accordion Content -->
            <div id="section-' . $section->id . '" class="accordion-content hidden bg-blue-950 p-3 pl-6">
        ';

        // === Activities in section ===
        if (!empty($section->sequence)) {
            $html .= html_writer::start_tag('ul', ['class' => 'space-y-2']);
            foreach ($activities as $cmid) {
                $cmid = (int)$cmid;
                if (empty($cmid) || !isset($modinfo->cms[$cmid])) continue;
                $cm = $modinfo->cms[$cmid];
                if (!$cm->uservisible) continue;

                // === Icon for each module ===
                $iconname = match ($cm->modname) {
                    'assign'            => 'assignment',
                    'quiz'              => 'quiz',
                    'resource'          => 'picture_as_pdf',
                    'customcert'        => 'workspace_premium',
                    'iomadcertificate'  => 'workspace_premium',
                    'url'               => 'play_circle',
                    'page'              => 'description',
                    'googlemeet'        => 'video_call',
                    'book'              => 'menu_book',
                    'videofile'         => 'video_library',
                    'pdf'               => 'picture_as_pdf',
                    'h5p'               => 'extension',
                    'choice'            => 'quiz',
                    default             => 'article',
                };

                // === Duration placeholders ===
                $duration = '';
                if (in_array($cm->modname, ['url', 'resource'])) {
                    $duration = '5 min';
                } elseif ($cm->modname === 'quiz') {
                    $duration = '10 min';
                } elseif ($cm->modname === 'assign') {
                    $duration = '15 min';
                }

                // === Completion status ===
                $completiondata = $completion->is_enabled($cm) ? $completion->get_data($cm) : null;
                $iscompleted = ($completiondata && $completiondata->completionstate > 0);
                $statusicon = $iscompleted
                    ? '<span class="material-icons text-green-400 text-sm ml-2 d-none">check_circle</span>'
                    : '<span class="material-icons text-gray-500 text-sm ml-2">radio_button_unchecked</span>';

                $modurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
                $linktext = format_string($cm->get_formatted_name());

                // === Activity Row ===
                $html .= '
                <li>
                    <a href="' . $modurl . '" 
   class="flex items-center p-2 rounded-lg hover:bg-blue-800 transition activity-link"
   data-modname="' . $cm->modname . '" 
   data-cmid="' . $cm->id . '">

                        <span class="material-icons mr-2 text-blue-300 text-base" style="background: #40537b;padding: 5px;border-radius: 50%;">' . $iconname . '</span>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium flex items-center">' . $linktext . ' ' . $statusicon . '</span>
                            ' . ($duration ? '<span class="text-xs text-gray-400">' . $duration . '</span>' : '') . '
                        </div>
                    </a>
                </li>';
            }
            $html .= html_writer::end_tag('ul');
        } else {
            $html .= html_writer::tag('div', 'No activities in this section.', ['class' => 'text-xs text-gray-300']);
        }

        $html .= '</div></div>'; // End accordion content
    }

    $html .= html_writer::end_tag('div');

    // === Accordion JS ===
    $html .= '
<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".accordion-header").forEach(header => {
        header.addEventListener("click", () => {
            const sectionId = header.getAttribute("data-section");
            const content = document.getElementById("section-" + sectionId);
            const icon = header.querySelector(".material-icons");
            const isOpen = !content.classList.contains("hidden");

            // Close all
            document.querySelectorAll(".accordion-content").forEach(c => c.classList.add("hidden"));
            document.querySelectorAll(".accordion-header .material-icons").forEach(i => {
                i.style.transform = "rotate(0deg)";
                i.style.transition = "transform 0.2s ease";
            });

            // Open selected
            if (!isOpen) {
                content.classList.remove("hidden");
                icon.style.transform = "rotate(90deg)";
                icon.style.transition = "transform 0.2s ease";
            }
        });
    });
});
</script>';

    return $html;
}
function local_incourse_render_forum_discussion($id) {
    global $DB, $OUTPUT, $PAGE, $USER;

    // Load discussion, forum, and course.
    $discussion = $DB->get_record('forum_discussions', ['id' => $id], '*', MUST_EXIST);
    $forum = $DB->get_record('forum', ['id' => $discussion->forum], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $forum->course], '*', MUST_EXIST);

    $context = context_module::instance($discussion->forum);
    $PAGE->set_context($context);
    $PAGE->set_url('/local/incourse/fetch_discussion.php', ['id' => $id]);

    // Load all posts in this discussion.
    $posts = $DB->get_records('forum_posts', ['discussion' => $discussion->id], 'created ASC');

    // Build a tree structure (parent-child).
    $posttree = [];
    foreach ($posts as $post) {
        $post->children = [];
        $posttree[$post->id] = $post;
    }
    foreach ($posts as $post) {
        if ($post->parent && isset($posttree[$post->parent])) {
            $posttree[$post->parent]->children[] = $post;
            unset($posttree[$post->id]);
        }
    }

    ob_start();

    echo '<div class="forum-discussion-container container my-5 text-left">';
    echo '<h2 class="fw-bold mb-3">' . format_string($discussion->name) . '</h2>';
    echo '<div class="mb-3">';
    echo '<div style="    background: #e4f2ff;color:#003152" class="bg-accent-light dark:bg-accent-dark font-medium text-sm inline-flex items-center px-3 py-1 rounded-full mb-6"><span class="material-icons text-base mr-1">school</span> Discussion Topic: ' . format_string($forum->name) . '</div>';
    echo '</div>';

    // Header controls
    echo '<div class="d-flex justify-content-between align-items-center mb-4">';
    echo '<select class="form-select form-select-sm w-auto rounded" style="    line-height: 16px !important;
    font-size: 13px !important;
    border-width: 1px;">';
    echo '<option>Display replies in nested form</option>';
    echo '</select>';
    echo '<button class="btn btn-light btn-sm"><i class="fa fa-cog me-1"></i> Settings</button>';
    echo '</div>';

    // Recursive render function.
    $render_post = function($post, $level = 0) use (&$render_post, $OUTPUT, $DB) {
        $user = $DB->get_record('user', ['id' => $post->userid], '*', MUST_EXIST);
        $author = fullname($user);
        $userpic = $OUTPUT->user_picture($user, ['size' => 50, 'class' => 'rounded-circle me-3']);
        $indent = $level > 0 ? 'ms-5 ps-4 border-start' : '';

        echo '<div class="mb-4 ' . $indent . '">';
        echo '<div class="d-flex align-items-start">';
        echo $userpic;
        echo '<div class="flex-grow-1">';
        echo '<div class="bg-white p-3 rounded shadow-sm border border-border-light dark:border-border-dark rounded-lg" >';
        echo '<p class="fw-semibold text-primary mb-1" style="color: #003152 !important;">' . format_string($post->subject) . '</p>';
        echo '<p class="small text-muted mb-3">by ' . $author . ' — ' . userdate($post->created) . '</p>';
        echo '<div class="text-dark small mb-3">' . format_text($post->message, $post->messageformat) . '</div>';

        echo '<div class="d-flex justify-content-end gap-3 small">';
        echo '<a href="#" class="text-muted text-decoration-none">Permalink</a>';
        if ($level > 0) {
            echo '<a href="#" class="text-muted text-decoration-none">Show parent</a>';
            echo '<a href="#" class="text-muted text-decoration-none">Split</a>';
        }
       echo '<button class="btn btn-primary btn-sm fw-bold px-3 py-1 reply-btn" data-postid="' . $post->id . '">Reply</button>';
        echo '</div>'; // buttons

        echo '</div>'; // card
        echo '</div>'; // flex-grow-1
        echo '</div>'; // flex container
        echo '</div>'; // post wrapper

        // Render child replies.
        foreach ($post->children as $child) {
            $render_post($child, $level + 1);
        }
    };

    // Render all top-level posts.
    foreach ($posttree as $post) {
        $render_post($post, 0);
    }

    echo '</div>'; // forum-discussion-container
    return ob_get_clean();
}
