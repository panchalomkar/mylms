<?php
defined('MOODLE_INTERNAL') || die();
/**
 * Get human-readable restriction info for a course module.
 *
 * @param stdClass $cm     The course module object.
 * @param stdClass $course The course object.
 * @return string          HTML with lock icon and restriction info (empty if visible).
 */
function local_incourse_parse_availability_condition($cond, $modinfo) {
    global $DB;

    $msgs = [];
    $type = $cond->type ?? '';

    switch ($type) {

        /* Activity completion */
        case 'completion':
            if (!empty($cond->cm) && isset($modinfo->cms[$cond->cm])) {
                $name = format_string($modinfo->cms[$cond->cm]->get_formatted_name());
                $msgs[] = "Not available unless <strong>{$name}</strong> is completed.";
            }
            break;

        /* Date restriction */
        case 'date':
            if (!empty($cond->t)) {
                $msgs[] = "Not available until <strong>" . userdate($cond->t) . "</strong>.";
            }
            break;

        /* Grade restriction */
        case 'grade':
            if (!empty($cond->id)) {
                $item = $DB->get_record('grade_items', ['id' => $cond->id], '*', IGNORE_MISSING);
                if ($item) {
                    $min = $cond->min ?? 0;
                    $msgs[] = "Not available unless you achieve <strong>{$min} Grade</strong> in <strong>{$item->itemname}</strong>.";
                }
            }
            break;

        /* User profile restriction */
        case 'userprofile':
            if (!empty($cond->sf)) {
                $msgs[] = "Restricted by user profile field <strong>{$cond->sf}</strong>.";
            }
            break;

        /* Restriction set (AND / OR logic) */
        case 'restrictionset':
            if (!empty($cond->c)) {
                foreach ($cond->c as $child) {
                    $msgs = array_merge(
                        $msgs,
                        local_incourse_parse_availability_condition($child, $modinfo)
                    );
                }
            }
            break;
    }

    return $msgs;
}

function local_incourse_get_completion_and_restriction_string(cm_info $cm, $course) {

    $completionmsgs = [];
    $restrictionmsgs = [];

    /* ================= COMPLETION (UNCHANGED) ================= */

    if (!empty($cm->completion)) {

        if ($cm->completion == COMPLETION_TRACKING_MANUAL) {
            $completionmsgs[] = "To do: Mark as done";
        }

        if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
            $auto = [];

            if (!empty($cm->completionview)) {
                $auto[] = "View";
            }
            if (!empty($cm->completionusegrade)) {
                $auto[] = "Receive a grade";
            }
            if (!empty($cm->completionpassgrade)) {
                $auto[] = "Achieve passing grade";
            }

            if ($auto) {
                $completionmsgs[] = "To do: " . implode(", ", $auto);
            }
        }
    }

    /* ================= RESTRICTIONS (ONLY IF NOT AVAILABLE) ================= */

    if (!$cm->available && !empty($cm->availability)) {

        $availability = json_decode($cm->availability);
        $modinfo = get_fast_modinfo($course);

        if (!empty($availability->c)) {
            foreach ($availability->c as $cond) {
                $restrictionmsgs = array_merge(
                    $restrictionmsgs,
                    local_incourse_parse_availability_condition($cond, $modinfo)
                );
            }
        }
    }

    /* ================= OUTPUT ================= */

    if (empty($completionmsgs) && empty($restrictionmsgs)) {
        return '';
    }

    $html = '';

    foreach ($completionmsgs as $msg) {
        $html .= '<div class="text-xs text-yellow-400">' . $msg . '</div>';
    }

    foreach ($restrictionmsgs as $msg) {
        $html .= '<div class="flex items-start gap-2 text-xs text-red-300">';
        $html .= '<span class="material-icons" style="font-size:15px;">lock</span>';
        $html .= '<span>' . $msg . '</span></div>';
    }

    return $html;
}




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

            <div id="section-' . $section->id . '" class="accordion-content hidden bg-blue-950 p-3 pl-3">
        ';

        // === Activities List ===
        if (!empty($activities)) {
            $html .= html_writer::start_tag('ul', ['class' => 'space-y-2']);

foreach ($activities as $cmid) {
    $cmid = (int)$cmid;
    if (!isset($modinfo->cms[$cmid])) continue;

    $cm = $modinfo->cms[$cmid];

    if (!empty($cm->deletioninprogress) || (method_exists($cm, 'is_deleted') && $cm->is_deleted())) {
        continue;
    }

    // --- RESTRICTION ---
    $restricted = !$cm->available;

    $restrictionhtml = local_incourse_get_completion_and_restriction_string($cm, $course);
    $completionhtml = $restrictionhtml ? '<div class="mt-2">' . $restrictionhtml . '</div>' : '';

    // --- ICONS ---
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
        'scorm'=> 'inventory_2',
        default => 'article',
    };

    $completiondata = $completion->is_enabled($cm) ? $completion->get_data($cm) : null;
    $iscompleted = ($completiondata && $completiondata->completionstate > 0);

    $statusicon = $iscompleted
        ? '<span style="font-size: 15px;" class="material-icons text-green-400 text-sm">check_circle</span>'
        : '<span style="font-size: 15px;" class="material-icons text-gray-500 text-sm">radio_button_unchecked</span>';

    $modurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
    $linktext = format_string($cm->get_formatted_name());

    // --- Render Activity HTML ---
    $html .= '<li>
        <a href="' . ($restricted ? '#' : $modurl) . '" 
           class="flex items-center d-flex justify-content-between p-2 rounded-lg hover:bg-blue-800 transition '
           . ($restricted ? ' opacity-40' : 'activity-link') . '"
           data-modname="' . $cm->modname . '" 
           data-cmid="' . $cm->id . '">
           <div class="d-flex" style="align-items: center;">
                <span class="material-icons mr-2 text-blue-300 text-base"
                      style="background:#40537b;padding:5px;border-radius:50%;">' . $iconname . '</span>
                <div class="flex flex-col">
                    <span class="text-sm font-medium flex items-center">' . $linktext . '</span>
                    ' . $completionhtml . '
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
