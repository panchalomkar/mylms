<?php
require_once(__DIR__ . '/../../config.php');
require 'vendor/autoload.php';
require_login();

global $DB;

$courseid = required_param('courseid', PARAM_INT);
$sections = $DB->get_records('course_sections', ['course' => $courseid]);
$context = context_course::instance($courseid);

$fs = get_file_storage();

// Get all overview files (usually only one image)
$files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);

$courseimageurl = '';

foreach ($files as $file) {
    if (!$file->is_directory()) {
        // Correctly construct URL without hardcoding /0/
        $courseimageurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out();
        break;
    }
}

if (empty($courseimageurl)) {
    $courseimageurl = 'https://cdn.disco.co/courses/covers/2025/02/18/99d5af99-cf57-4ea6-9484-8fe3d8b8c4b2.png';
}
$courseimageurl = str_replace('/0/', '/', $courseimageurl);

$modinfo = get_fast_modinfo($courseid);
// Build a map: sectionnum -> cmid for the first videofile module in that section.
$video_cmid_by_section = [];
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'videofile' && $cm->uservisible) {
        $sectionnum = $cm->sectionnum;
        if (!isset($video_cmid_by_section[$sectionnum])) {
            // store course module id
            $video_cmid_by_section[$sectionnum] = $cm->id;
        }
    }
}

$first_quiz_url_by_section = [];

foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'quiz' && $cm->uservisible) {
        $sectionnum = $cm->sectionnum;

        // Store only the first quiz per section
        if (!isset($first_quiz_url_by_section[$sectionnum])) {
            $first_quiz_url_by_section[$sectionnum] = $cm->url->out(false);
        }
    }
}
$video_records = $DB->get_records('course_section_video', ['courseid' => $courseid]);

$first_video_url_by_section = [];
foreach ($video_records as $record) {
    if (!isset($first_video_url_by_section[$record->sectionid])) {
        // Clean markdown-style [YouTube Video URL](URL) to extract URL only
        if (preg_match('/\((https?:\/\/[^\)]+)\)/', $record->videourl, $matches)) {
            $clean_url = $matches[1];
        } else {
            $clean_url = $record->videourl;
        }
        $first_video_url_by_section[$record->sectionid] = $clean_url;
    }
}

// Function to convert YouTube URL to embed URL
function convert_youtube_url_to_embed($url)
{
    if (strpos($url, 'youtube.com/watch') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        if (isset($query['v'])) {
            return 'https://www.youtube.com/embed/' . $query['v'];
        }
    }
    if (strpos($url, 'youtu.be/') !== false) {
        $parts = explode('/', $url);
        $videoId = end($parts);
        return 'https://www.youtube.com/embed/' . $videoId;
    }

    return $url;
}

?>




<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-2 headerContainer-0-4-521 ">
        <div class="header-left-inner">
            <a href="<?php $CFG->dirroot . '/local/course_ai' ?>" class="pe-2">
  <svg fill="#003152" height="30px" width="30px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-0.8 -0.8 28.28 28.28" xml:space="preserve" stroke="#003152"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M26.105,21.891c-0.229,0-0.439-0.131-0.529-0.346l0,0c-0.066-0.156-1.716-3.857-7.885-4.59 c-1.285-0.156-2.824-0.236-4.693-0.25v4.613c0,0.213-0.115,0.406-0.304,0.508c-0.188,0.098-0.413,0.084-0.588-0.033L0.254,13.815 C0.094,13.708,0,13.528,0,13.339c0-0.191,0.094-0.365,0.254-0.477l11.857-7.979c0.175-0.121,0.398-0.129,0.588-0.029 c0.19,0.102,0.303,0.295,0.303,0.502v4.293c2.578,0.336,13.674,2.33,13.674,11.674c0,0.271-0.191,0.508-0.459,0.562 C26.18,21.891,26.141,21.891,26.105,21.891z"></path> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </g> </g></svg>
</a>
            <div class="badgeContainer-0-4-239 badgeContainer-d30-0-4-2926" style="background-image: url(<?php echo $courseimageurl; ?>);
        background-size: cover;"></div>
            <h5 class="body-md-500-0-4-276 ms-md-2 ms-sm-1">${courseName}</h5>
        </div>
        <div class="btn-group d-none" style="gap:2px;" role="group" aria-label="Button group with nested dropdown">

            <button type="button" class="btn btn-primary rounded-2 showalert">+ Add Module</button>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary rounded-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="MuiIconButton-label-132" class><svg viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="moreIcon-0-4-618 rotateIcon-0-4-616">
                            <path
                                d="M4 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z"
                                fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"></path>
                        </svg></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">
                            <div class="content-0-4-1531 content-d4-0-4-1548">
                                <div class="lhs-0-4-1534"><span class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            data-testid="DiscoIcon.user"
                                            class="root-0-4-376 root-d394-0-4-10152 root-0-4-374 root-d394-0-4-10151">
                                            <path
                                                d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round"></path>
                                        </svg></span>
                                    <div class="textContainer-0-4-1537">
                                        <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                            data-testid="DiscoDropdownItem.title">
                                            Members</p>
                                    </div>
                                </div>
                            </div>
                        </a></li>
                    <li><a class="dropdown-item" href="#">
                            <div class="content-0-4-1531 content-d4-0-4-1548">
                                <div class="lhs-0-4-1534"><span class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            data-testid="DiscoIcon.user-add"
                                            class="root-0-4-376 root-d395-0-4-10163 root-0-4-374 root-d395-0-4-10162">
                                            <path
                                                d="M17.12 20.6c0-3.405-3.39-6.16-7.56-6.16C5.388 14.44 2 17.195 2 20.6M19.22 9.485v2.78m0 0v2.779m0-2.78H22m-2.78 0h-2.778M13.96 7.4a4.4 4.4 0 1 1-8.8 0 4.4 4.4 0 0 1 8.8 0Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        </svg></span>
                                    <div class="textContainer-0-4-1537">
                                        <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                            data-testid="DiscoDropdownItem.title">
                                            Invite
                                            Members</p>
                                    </div>
                                </div>
                            </div>
                        </a></li>
                </ul>
            </div>

        </div>

    </div>
    <!-- tab place ul element -->
</div>
<div class="container module-cc mb-5">
    <!-- <div class="container-fluid mb-3">
       
    </div> -->

    <div id="static-content" class="mb-5">

        <div class="course-view-card1 chaptertab p-2 row">

 <div data-testid="BannerImage" class="container-0-4-1291 cover-0-4-1279 col-md-3"
            style="background-image: url(<?php echo $courseimageurl ?>);">
        </div>
            <div class="card-body col-md-9 p-4">
                <h4 style="font-size:18px;" class=mt-2">Admin Overview</h4>
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="course-view-card1 mt-3 w-75 chaptertab">
                            <h1>0</h1>
                            <div class="subtitleContainer-0-4-1350">
                                <p class="MuiTypography-root-680 root-0-4-241 root-d495-0-4-10560 root-0-4-299 root-d495-0-4-10561 body-md-0-4-275 MuiTypography-body1-682 MuiTypography-noWrap-699"
                                    data-testid="ProductAdminStatisticsReportCard.avgCurriculumProgress.subtitle">
                                    Average Curriculum Progress</p><button
                                    class="MuiButtonBase-root-718 MuiIconButton-root-710 root-0-4-313 root-d1562-0-4-10566 iconButton-0-4-234 iconButton-0-4-1351"
                                    tabindex="0" type="button"><span class="MuiIconButton-label-717"><svg
                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M12.833 7.278a.833.833 0 1 1-1.666 0 .833.833 0 0 1 1.666 0ZM12 9.778a.833.833 0 0 0-.833.833v6.111a.833.833 0 1 0 1.666 0v-6.11A.833.833 0 0 0 12 9.777Z"
                                                fill="currentColor"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10Zm0-1.667a8.333 8.333 0 1 0 0-16.666 8.333 8.333 0 0 0 0 16.666Z"
                                                fill="currentColor"></path>
                                        </svg></span></button>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="course-view-card1 mt-3 w-75 chaptertab">
                            <h1>0</h1>
                            <div class="subtitleContainer-0-4-1350">
                                <p class="MuiTypography-root-680 root-0-4-241 root-d495-0-4-10560 root-0-4-299 root-d495-0-4-10561 body-md-0-4-275 MuiTypography-body1-682 MuiTypography-noWrap-699"
                                    data-testid="ProductAdminStatisticsReportCard.avgCurriculumProgress.subtitle">Active
                                    Members</p><button
                                    class="MuiButtonBase-root-718 MuiIconButton-root-710 root-0-4-313 root-d1562-0-4-10566 iconButton-0-4-234 iconButton-0-4-1351"
                                    tabindex="0" type="button"><span class="MuiIconButton-label-717"><svg
                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M12.833 7.278a.833.833 0 1 1-1.666 0 .833.833 0 0 1 1.666 0ZM12 9.778a.833.833 0 0 0-.833.833v6.111a.833.833 0 1 0 1.666 0v-6.11A.833.833 0 0 0 12 9.777Z"
                                                fill="currentColor"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10Zm0-1.667a8.333 8.333 0 1 0 0-16.666 8.333 8.333 0 0 0 0 16.666Z"
                                                fill="currentColor"></path>
                                        </svg></span></button>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="course-view-card1 mt-3 w-75 chaptertab">
                            <h1>0 %</h1>
                            <div class="subtitleContainer-0-4-1350">
                                <p class="MuiTypography-root-680 root-0-4-241 root-d495-0-4-10560 root-0-4-299 root-d495-0-4-10561 body-md-0-4-275 MuiTypography-body1-682 MuiTypography-noWrap-699"
                                    data-testid="ProductAdminStatisticsReportCard.avgCurriculumProgress.subtitle">
                                    Average Curriculum Progress</p><button
                                    class="MuiButtonBase-root-718 MuiIconButton-root-710 root-0-4-313 root-d1562-0-4-10566 iconButton-0-4-234 iconButton-0-4-1351"
                                    tabindex="0" type="button"><span class="MuiIconButton-label-717"><svg
                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M12.833 7.278a.833.833 0 1 1-1.666 0 .833.833 0 0 1 1.666 0ZM12 9.778a.833.833 0 0 0-.833.833v6.111a.833.833 0 1 0 1.666 0v-6.11A.833.833 0 0 0 12 9.777Z"
                                                fill="currentColor"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10Zm0-1.667a8.333 8.333 0 1 0 0-16.666 8.333 8.333 0 0 0 0 16.666Z"
                                                fill="currentColor"></path>
                                        </svg></span></button>
                            </div>

                        </div>
                    </div>
                </div>

                <div id="inner-accordionel">
                    <div class="course-view-card1 mt-3 chaptertab">
                        <div class="card-header h_border
                                    chaptertab" id="innerHeadingel">
                            <strong class="mb-0">
                                <button class="btn chapterbtn
                                            btn-light
                                            d-flex
                                            justify-content-between
                                            align-content-center
                                            w-100 text-decoration-none
                                            accordion-button collapsed" data-toggle="collapse"
                                    data-target="#innerCollapseel" aria-expanded="true" aria-controls="innerCollapseel">
                                    <strong class="d-flex justify-content-center"><span
                                            class="MuiIconButton-label-132"><svg viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg" class="collapsibleCaret-0-4-1378">
                                                <path
                                                    d="M17.919 8.18H6.079c-.96 0-1.44 1.16-.76 1.84l5.18 5.18c.83.83 2.18.83 3.01 0l1.97-1.97 3.21-3.21c.67-.68.19-1.84-.77-1.84Z"
                                                    fill="currentColor"></path>
                                            </svg></span>Suggested
                                        Admin Actions</strong>

                                </button>
                            </strong>
                        </div>
                        <div id="innerCollapseel" class="collapse" aria-labelledby="innerHeadingel"
                            data-parent="#inner-accordion">
                            <div class="card-body">
                                <div class="course-view-card1 mt-3
                                            chaptertab">
                                    <div class="card-header
                                                h_border
                                                chaptertab">
                                        <h5 class="mb-0">
                                            <button class="btn
                                                        chapterbtn
                                                        btn-light
                                                        d-flex
                                                        justify-content-between
                                                        align-content-center
                                                        w-100
                                                        text-decoration-none
                                                        collapsed">
                                                overview
                                            </button>
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Add more inner accordion items as needed -->
                </div>

            </div>

        </div>
        <!-- Add more outer accordion items as needed -->
    </div>
    <!-- Start first accordion -->
    <div id="outer-accordion">
        <div class="course-view-card1 chaptertab" id="course-view1">
            <div class="card-header h_border h_border chaptertab" id="headingOne">
                <strong class="mb-0">
                    Curriculum
                </strong>
            </div>

            <div id="collapseOne" class="" aria-labelledby="headingOne" data-parent="#outer-accordion">

                <div class="card-body">
                    <!-- this is my courseid -<?= $courseid ?> -->

                    <?php foreach ($sections as $section): ?>
                        <?php
                        // Skip section 0 (general section) if not needed
                        if ($section->section == 0 || !$section->visible) {
                            continue;
                        }

                        $id = $section->id;
                        $name = $section->name ?: 'Section ' . $section->section;
                        $summary1 = format_text($section->summary, $section->summaryformat);

                        $plainText = preg_replace('/\*\*(.*?)\*\*/', '$1', $summary1);

                        // Strip headings like ### or ##
                        $plainText = preg_replace('/^#+\s*/m', '', $plainText);

                        // Optional: Convert newlines to <br> for Moodle HTML display
                        $plainText = nl2br($plainText);
                        $summary = format_text($plainText, FORMAT_HTML);
                        ?>

                        <div id="inner-accordion<?= $id ?>">
                            <div class="course-view-card1 mt-3 chaptertab">
                                <div class="card-header h_border chaptertab" id="innerHeading<?= $id ?>">
                                    <h5 class="mb-0">
                                        <button
                                            class="btn chapterbtn d-flex align-content-center w-100 text-decoration-none accordion-button collapsed"
                                            data-toggle="collapse" data-target="#innerCollapse<?= $id ?>"
                                            aria-expanded="true" aria-controls="innerCollapse<?= $id ?>">
                                            <h5 class="d-flex justify-content-center">
                                                <span class="MuiIconButton-label-132">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                                        class="collapsibleCaret-0-4-1378">
                                                        <path
                                                            d="M17.919 8.18H6.079c-.96 0-1.44 1.16-.76 1.84l5.18 5.18c.83.83 2.18.83 3.01 0l1.97-1.97 3.21-3.21c.67-.68.19-1.84-.77-1.84Z"
                                                            fill="currentColor"></path>
                                                    </svg>
                                                </span>
                                                <?= format_string($name) ?>
                                            </h5>
                                            <?php
                                            $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, visible');
                                            if (is_siteadmin() && empty($course->visible)) { ?>
                                                <button class="btn btn-sm btn-outline-warning ms-2"
                                                    onclick="regenerateSection(<?= $courseid ?>, <?= $section->id ?>, this)">
                                                    <i class="fa fa-sync-alt"></i> Regenerate
                                                </button>
                                            <?php } ?>



                                        </button>
                                    </h5>
                                </div>

                                <div id="innerCollapse<?= $id ?>" class="collapse" aria-labelledby="innerHeading<?= $id ?>"
                                    data-parent="#inner-accordion<?= $id ?>">
                                    <div class="card-body">
                                        <div><span><?= $summary ?></span></div>
                                        <!-- Placeholder for section content -->
                                        <div class="course-view-card1 mt-3 chaptertab">
                                            <div class="card-header h_border chaptertab">
                                                <h5 class="mb-0 d-flex">
                                                    <?php
                                                    $video_url = $first_video_url_by_section[$section->id] ?? null;
                                                    ?>
                                                    <?php if ($video_url):
                                                        $embed_url = convert_youtube_url_to_embed($video_url);
                                                        $section_cmid = $video_cmid_by_section[$section->section] ?? 0; // note: $section->section is section number
$has_cmid = !empty($section_cmid);
                                                        ?>
                                                        <button 
                                                            class="btn col-md-6 chapterbtn btn-light d-flex justify-content-between align-content-center w-100 text-decoration-none collapsed play-video-btn"
                                                            type="button" data-bs-toggle="modal" data-bs-target="#videoModal"
                                                            data-course-name="<?= format_string($name) ?>"
                                                            data-sectionid="<?= $section->id ?>"
                                                            data-video-url="<?= htmlspecialchars($embed_url) ?>"  <?= $has_cmid ? 'data-cmid="'.intval($section_cmid).'"' : '' ?>>
                                                            ▶️ Play Video
                                                        </button>

                                                        <?php if (is_siteadmin()) { ?>
                                                            <div class="video-edit-container justify-content-end col-md-6 d-flex gap-2"
                                                                data-sectionid="<?= $section->id ?>"
                                                                data-video-url="<?= htmlspecialchars($video_url) ?>">
                                                                <!-- Hidden input and Save button initially -->
                                                                <input type="text" class="form-control video-url-input d-none mt-2"
                                                                    placeholder="Video URL"
                                                                    style="height: 25px;font-size: 10px !important;" />

                                                                <button type="button"
                                                                    class="btn btn-sm btn-primary-t save-video-btn d-none mt-2"
                                                                    data-courseid="<?= $courseid ?>"
                                                                    data-sectionid="<?= $section->id ?>"
                                                                    onclick="saveVideoUrl(this)">
                                                                    <i class="fa fa-save"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="btn btn-sm btn-primary-t edit-video-btn"
                                                                    data-courseid="<?= $courseid ?>"
                                                                    data-sectionid="<?= $section->id ?>"
                                                                    onclick="toggleVideoEdit(this)">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>

                                                                <!-- Optional message -->
                                                                <div class="message-box"></div>
                                                            </div>
                                                        <?php } ?>
                                                    <?php else: ?>
                                                        <button class="btn chapterbtn btn-secondary w-100" disabled>No
                                                            Video</button>
                                                    <?php endif; ?>

                                                </h5>
                                            </div>
                                        </div>

                                        <div class="course-view-card1 mt-3 chaptertab">
                                            <div class="card-header h_border chaptertab">
                                                <h5 class="mb-0">
                                                    <?php
                                                    $quizurl = $first_quiz_url_by_section[$section->section] ?? null;
                                                    ?>

                                                    <?php if ($quizurl): ?>
                                                        <a href="<?= $quizurl ?>"
                                                            class="btn chapterbtn btn-light d-flex justify-content-between align-content-center w-100 text-decoration-none collapsed">
                                                            📝 MCQ
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn chapterbtn btn-secondary w-100" disabled>No Quiz
                                                            Available</button>
                                                    <?php endif; ?>

                                                </h5>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
    <!-- Add more outer accordion items as needed -->
</div>
</div>