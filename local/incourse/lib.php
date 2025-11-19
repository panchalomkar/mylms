<?php
defined('MOODLE_INTERNAL') || die();

function local_incourse_get_completion_string($cm) {
    if (empty($cm->completion)) {
        return '';
    }

    $conditions = [];

    // Manual completion
    if ($cm->completion == COMPLETION_TRACKING_MANUAL) {
        $conditions[] = "Mark as done";
    }

    // Automatic completion
    if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC) {

        if (!empty($cm->completionview)) {
            $conditions[] = "View to complete";
        }

        if (!empty($cm->completionusegrade)) {
            $conditions[] = "Receive a grade to complete";
        }

        if (!empty($cm->completionpassgrade)) {
            $conditions[] = "Achieve passing grade";
        }
    }

    if (empty($conditions)) {
        return '';
    }

    return "To do: " . implode(", ", $conditions);
}


function local_incourse_render_course_index($course) {
    global $OUTPUT, $DB;

    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $completion = new completion_info($course);

    $html = html_writer::start_tag('div', ['class' => 'space-y-2', 'id' => 'accordion-container']);

    foreach ($sections as $section) {

        if (!$section->visible || $section->section == 0) {
            continue;
        }

        $sectionname = format_string($section->name ?: get_section_name($course, $section->section));

        // === Calculate Section Progress ===
        $activities = !empty($section->sequence) ? explode(',', $section->sequence) : [];
        $total = count($activities);
        $completed = 0;

        foreach ($activities as $cmid) {
            $cmid = (int)$cmid;
            if (!isset($modinfo->cms[$cmid])) continue;

            $cm = $modinfo->cms[$cmid];

            if ($completion->is_enabled($cm) &&
                $completion->get_data($cm)->completionstate > 0) {
                $completed++;
            }
        }

        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        // === Section Header ===
        $html .= '
        <div class="rounded-lg overflow-hidden mb-2 shadow" style="background:#1a305f;">
            <button class="w-full flex items-center justify-between p-3 hover:bg-blue-800 transition accordion-header"
                data-section="' . $section->id . '">

                <div class="flex items-center">
                    <span class="material-icons ml-2 transform transition-transform duration-200 section-icon">
                        chevron_right
                    </span>
                    <span class="font-semibold ml-2">' . $sectionname . '</span>
                </div>

                <div class="flex items-center">
                    <span class="text-xs mr-2">' . $progress . '%</span>
                    <div class="w-10 bg-blue-800 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full"
                            style="width:' . $progress . '%; background:#ec9707;"></div>
                    </div>
                </div>
            </button>

            <div id="section-' . $section->id . '" class="accordion-content hidden bg-blue-950 p-3 pl-6">
        ';

        // === Activities List ===
        if (!empty($activities)) {
            $html .= html_writer::start_tag('ul', ['class' => 'space-y-2']);

     foreach ($activities as $cmid) {

    $cmid = (int)$cmid;
    if (!isset($modinfo->cms[$cmid])) continue;

    $cm = $modinfo->cms[$cmid];
      // ❌ Skip deleted modules
    if (!empty($cm->deletioninprogress) ||
        (method_exists($cm, 'is_deleted') && $cm->is_deleted())) {
        continue;
    }
// === RESTRICTED ACTIVITIES FIX ===
$restricted = false;
$restrictioninfo = '';

$restricted = false;
$restrictioninfo = '';

if (!$cm->uservisible) {
    $restricted = true;

    if (!empty($cm->availableinfo)) {

        // Moodle formatted restriction HTML
        $formatted = format_text($cm->availableinfo, FORMAT_HTML);

        // --- Extract the restricted activity name (the dependency) ---
        // Matches:
        // The activity PDF Test is ...
        // The activity "PDF Test" is ...
        // The activity 'PDF Test' is ...
        // The activity <span class="instancename">PDF Test</span> is ...
        $pattern = '/The activity\s+(?:<[^>]+>)?["\']?([^"<>\']+)["\']?(?:<\/[^>]+>)?\s+is/i';

        if (preg_match($pattern, $formatted, $match)) {
            $dependencyname = trim($match[1]);

            // Highlight the dependency activity name
            $formatted = str_replace(
                $dependencyname,
                '<b>"' . $dependencyname . '"</b>',
                $formatted
            );
        }

        // --- Add material lock icon ---
        $restrictioninfo = '
            <div class="flex items-start gap-2 text-red-300 text-xs">
                <span class="material-icons text-base"style="font-size: 15px;">lock</span>
                <span>' . $formatted . '</span>
            </div>
        ';

    } else {

        // Default fallback restriction message
        $restrictioninfo = '
            <div class="flex items-start gap-2 text-red-300 text-xs">
                <span class="material-icons text-base" style="font-size: 15px;">lock</span>
                <span>You cannot access this activity yet.</span>
            </div>
        ';
    }
}



    // Icons
    $iconname = match ($cm->modname) {
        'assign' => 'assignment',
        'quiz' => 'quiz',
        'resource' => 'picture_as_pdf',
        'customcert','iomadcertificate' => 'workspace_premium',
        'url' => 'play_circle',
        'page' => 'description',
        'googlemeet' => 'video_call',
        'book' => 'menu_book',
        'videotime' => 'video_library',
        'pdfjsfolder' => 'picture_as_pdf',
        'h5p' => 'extension',
        default => 'article',
    };

    // Completion state
    $completiondata = $completion->is_enabled($cm) ? $completion->get_data($cm) : null;
    $iscompleted = ($completiondata && $completiondata->completionstate > 0);

    $statusicon = $iscompleted
        ? '<span style="font-size: 15px;" class="material-icons text-green-400 text-sm ">check_circle</span>'
        : '<span style="font-size: 15px;" class="material-icons text-gray-500 text-sm ">radio_button_unchecked</span>';

    $completionmsg = local_incourse_get_completion_string($cm);
    $completionhtml = $completionmsg
        ? '<span class="text-xs text-gray-400 mt-2 d-flex" style="align-items: center;gap: 4px;color:hsl(45 93% 47%);">' . $completionmsg . '</span>'
        : '';

    $restrictionhtml = $restricted
        ? '<span class="text-xs text-red-400">' . $restrictioninfo . '</span>'
        : '';

    $modurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
    $linktext = format_string($cm->get_formatted_name());

    $html .= '
    <li>
        <a href="' . $modurl . '" 
           class="flex items-center d-flex justify-content-between p-2 rounded-lg hover:bg-blue-800 transition '
           . ($restricted ? 'pointer-events-none opacity-40' : 'activity-link') . '"
           data-modname="' . $cm->modname . '" 
           data-cmid="' . $cm->id . '">
<div class="d-flex" style="align-items: center;">
            <span class="material-icons mr-2 text-blue-300 text-base"
                  style="background:#40537b;padding:5px;border-radius:50%;">' . $iconname . '</span>

            <div class="flex flex-col">
                <span class="text-sm font-medium flex items-center">'
                    . $linktext . ' 
                </span>

                ' . $completionhtml . '
                ' . $restrictionhtml . '
            </div>
            </div>
            <div>' . $statusicon . '</div>
        </a>
        
    </li>';
}


            $html .= html_writer::end_tag('ul');
        }

        $html .= '</div></div>';
    }

    $html .= html_writer::end_tag('div');

    // === FIXED ACCORDION JS ===
    $html .= '
<script>
document.addEventListener("DOMContentLoaded", function() {

    const headers = document.querySelectorAll(".accordion-header");

    headers.forEach(header => {
        header.addEventListener("click", function(e) {

            // Prevent activity links from closing accordion
            if (e.target.closest(".activity-link")) return;

            const id = header.dataset.section;
            const content = document.getElementById("section-" + id);
            const icon = header.querySelector(".section-icon");
            const open = !content.classList.contains("hidden");

            document.querySelectorAll(".accordion-content").forEach(c => c.classList.add("hidden"));
            document.querySelectorAll(".section-icon").forEach(i => i.style.transform = "rotate(0deg)");

            if (!open) {
                content.classList.remove("hidden");
                icon.style.transform = "rotate(90deg)";
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
