<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Creates a hidden (draft) course.
 *
 * @param string $coursename
 * @param int $userid
 * @return object created course
 * @throws moodle_exception
 */
function local_course_ai_create_draft_course(string $coursename, string $summary, int $userid): object
{
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');

    // Create safe shortname
    $shortname = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $coursename));
    if ($DB->record_exists('course', ['shortname' => $shortname])) {
        $shortname .= '-' . time();
    }

    // Prepare course object
    $course = new stdClass();
    $course->fullname = $coursename;
    $course->shortname = $shortname;
    $course->category = 1; // Default category (update if needed)
    $course->summary = $summary;
    $course->summaryformat = FORMAT_HTML;
    $course->format = 'topics';
    $course->visible = 0; // Hidden = draft
    $course->timecreated = time();
    $course->timemodified = time();

    // Create course using Moodle API
    return create_course($course);
}


// generate course content using prompt 

// function generate_course_modules_from_prompt($prompt, $api_key)
// {
//     if (empty($prompt)) {
//         return '';
//     }

//     $formatted_prompt = "{$prompt} Generate exactly 5 course module titles with one-line descriptions and relevant public access YouTube video URLs for the topic: '{$prompt}'. 

//     Output only plain text. No Markdown formatting. No asterisks or bold text. Use this exact format for each line:

//     Module Title | One-line Description | YouTube Video URL";

//     $data = [
//         "model" => "gpt-4",
//         "messages" => [
//             [
//                 "role" => "system",
//                 "content" => "You will receive a text input from the user. Your task is to generate text based on their request. Follow these important instructions:1. A clear, concise module title (under 10 words)
// 2. A one-line description (max 20 words)
// 3. A real, public, and relevant YouTube video URL (prefer educational channels like CrashCourse, TED-Ed, Khan Academy,simplilearn etc.)"
//             ],
//             ["role" => "user", "content" => $formatted_prompt]
//         ],
//         "temperature" => 0.7
//     ];


//     $ch = curl_init();
//     curl_setopt_array($ch, [
//         CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_POST => true,
//         CURLOPT_HTTPHEADER => [
//             "Content-Type: application/json",
//             "Authorization: Bearer {$api_key}"
//         ],
//         CURLOPT_POSTFIELDS => json_encode($data)
//     ]);

//     $response = curl_exec($ch);
//     curl_close($ch);

//     if (!$response) {
//         return '';
//     }

//     $result = json_decode($response, true);
//     return $result['choices'][0]['message']['content'] ?? '';
// }
function generate_course_modules_from_prompt($prompt, $api_key)
{
    if (empty($prompt)) {
        return '';
    }

    //     $formatted_prompt = <<<PROMPT
// You are a senior instructional designer tasked with generating five high-quality e-learning course modules.

    // Topic: "{$prompt}"

    // Please output exactly 5 course modules. Each module should include:
// 1. A clear, concise module title (under 10 words)
// 2. A one-line description (max 20 words)
// 3. A real, public, and relevant YouTube video URL (prefer educational channels like CrashCourse, TED-Ed, Khan Academy, etc.)

    // Very important:
// - Only use real, public, accessible YouTube links
// - Format exactly as shown below with pipes:
// Module Title | One-line Description | YouTube Video URL
// - Do NOT use Markdown, asterisks, numbers, or bullets
// - Output only 5 lines (one per module), no headers or explanations

    // Make sure the YouTube links are real and relevant to the topic. Do not make up fake links.
// PROMPT;
    $formatted_prompt = <<<PROMPT
You are a senior instructional designer tasked with generating five high-quality e-learning course modules.

Topic: "{$prompt}"

Please output exactly 5 course modules. Each module should include:
1. A clear, concise module title (under 10 words)
2. A one-line description (max 20 words)

Important:
- Format exactly like this: Module Title | One-line Description
- Do not include any links
- Do not number the modules
- Output exactly 5 lines with no extra text
PROMPT;


    $data = [
        "model" => "gpt-4-turbo",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a professional e-learning content generator. Respond only in plain text with no extra formatting. Use strictly pipe-separated format."
            ],
            [
                "role" => "user",
                "content" => $formatted_prompt
            ]
        ],
        "temperature" => 0.4,
        "max_tokens" => 800
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return '';
    }

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? '';
}
function search_youtube_video($query, $api_key)
{
    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($query) . "&key={$api_key}&maxResults=1&type=video";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (!empty($data['items'][0]['id']['videoId'])) {
        return 'https://www.youtube.com/watch?v=' . $data['items'][0]['id']['videoId'];
    }

    return null;
}

function generate_section_summary($title, $api_key)
{
    $payload = [
        "model" => "gpt-4",
        "messages" => [
            ["role" => "system", "content" => "Summarize the following topic in plain HTML text, no markdown or greetings."],
            ["role" => "user", "content" => "Generate a module summary for: $title"]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? 'Summary not available.';
    }

    return 'Summary generation failed.';
}


// create section 
function create_quiz_with_mcqs($courseid, $sectionnumber, $title, $api_key, $quizmodule, $questioncategory)
{
    global $DB, $USER;

    // === 1. Create the quiz activity ===
    $quiz = new stdClass();
    $quiz->course = $courseid;
    $quiz->name = $title . ' Quiz';
    $quiz->intro = 'This is an auto-generated quiz.';
    $quiz->introformat = FORMAT_HTML;
    $quiz->timeopen = 0;
    $quiz->timeclose = 0;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->attempts = 1;
    $quiz->grademethod = 1;
    $quiz->grade = 100;
    $quiz->sumgrades = 0;
    $quiz->section = $sectionnumber;
    $quiz->visible = 1;
    $quiz->timemodified = time();

    $quiz->coursemodule = null;
    $quiz->instance = null;
    $quiz->module = $quizmodule;

    // Create the course module first
    $module = new stdClass();
    $module->course = $courseid;
    $module->module = $quizmodule;
    $module->section = $sectionnumber;
    $module->visible = 1;
    $module->visibleoncoursepage = 1;
    $module->modulename = 'quiz';
    $module->name = $quiz->name;
    $module->instance = 0;
    $cmid = add_course_module($module);
    $quiz->coursemodule = $cmid;
    $quizid = quiz_add_instance($quiz);
    set_coursemodule_instance($cmid, $quizid);
    course_add_cm_to_section($courseid, $cmid, $sectionnumber);
}

// add question
function get_embedded_video_html(string $ai_videourl, $courseid, $sectionid): array
{
    global $DB;
    // === Fetch or insert video URL
    $videorecord = $DB->get_record('course_section_video', ['courseid' => $courseid, 'sectionid' => $sectionid]);
    if (empty($videorecord)) {
        $newvideorecord = new stdClass();
        $newvideorecord->courseid = $courseid;
        $newvideorecord->sectionid = $sectionid;
        $newvideorecord->videourl = $ai_videourl;
        $newvideorecord->timecreated = time();
        $newvideorecord->timemodified = time();
        $DB->insert_record('course_section_video', $newvideorecord);
        $videourl = $ai_videourl;
    } else {
        $videourl = $videorecord->videourl;
    }

    $currentUrlValue = htmlspecialchars($videourl ?? '');
    $videoHTML = '';

    // Extract URL from Markdown format if needed
    if (preg_match('/\((https?:\/\/[^\s)]+)\)/', $videourl, $matches)) {
        $videourl = $matches[1];
    }

    if (!empty($videourl) && filter_var($videourl, FILTER_VALIDATE_URL)) {
        if (strpos($videourl, 'youtube.com/watch') !== false || strpos($videourl, 'youtu.be') !== false) {
            parse_str(parse_url($videourl, PHP_URL_QUERY), $query);
            $videoId = $query['v'] ?? '';

            if (empty($videoId) && strpos($videourl, 'youtu.be') !== false) {
                $videoId = basename(parse_url($videourl, PHP_URL_PATH));
            }

            if (!empty($videoId)) {
                $embedUrl = "https://www.youtube.com/embed/" . htmlspecialchars($videoId);
                $videoHTML .= "<div class='video-container' style='text-align:center; margin-bottom: 10px;'>
                    <iframe width='480' height='270' src='$embedUrl' frameborder='0' allowfullscreen></iframe>
                </div>";
            }
        } else {
            $safeurl = htmlspecialchars($videourl);
            $videoHTML .= "<div class='video-container' style='text-align:center; margin-bottom: 10px;'>
                <iframe width='480' height='270' src='$safeurl' frameborder='0' allowfullscreen></iframe>
            </div>";
        }
    }
    return [
        'html' => $videoHTML,
        'url' => $currentUrlValue
    ];
}

function regenerate_specific_section($courseid, $sectionid, $title)
{
    global $DB,$CFG;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    $api_key = $CFG->openaiapikey; // 🔒 Regenerate it now!
    $youtube_key = 'AIzaSyDbNZjjZHw285wITRZr1sEfQTOCtEY-zis';

    // 1. Regenerate Summary
    $summary = generate_section_summary($title, $api_key);
    $videourl = search_youtube_video($title, $youtube_key);

    // 2. Update section summary
    $sectionupdate = (object)[
        'id' => $sectionid,
        'summary' => "<p>$summary</p>",
        'summaryformat' => FORMAT_HTML
    ];
    $DB->update_record('course_sections', $sectionupdate);

    // 3. Store the video in course_section_video table
    $time = time();
    $params = ['courseid' => $courseid, 'sectionid' => $sectionid];
    $record = $DB->get_record('course_section_video', $params);

    if ($record) {
        $record->videourl = $videourl;
        $record->timemodified = $time;
        $DB->update_record('course_section_video', $record);
    } else {
        $new = (object)[
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'videourl' => $videourl,
            'timecreated' => $time,
            'timemodified' => $time
        ];
        $DB->insert_record('course_section_video', $new);
    }
$questions = [];
    // 4. Regenerate Quiz (if already exists in this section)
    $modules = $DB->get_records_sql("
        SELECT cm.id as cmid, q.id as quizid
        FROM {course_modules} cm
        JOIN {quiz} q ON q.id = cm.instance
        WHERE cm.course = ? AND cm.section = ? AND cm.module = (
            SELECT id FROM {modules} WHERE name = 'quiz'
        )
        LIMIT 1
    ", [$courseid, $sectionid]);

    if (!empty($modules)) {
        foreach ($modules as $mod) {
            $cmid = $mod->cmid;

            quiz_delete_all_attempts($mod->quizid);
            quiz_delete_all_questions($mod->quizid);

            // Generate new questions
              $questions = regenerate_quiz_questions($courseid, $sectionid, $title, $cmid, $api_key,$summary);
        }
    }

    return [
        'success' => true,
        'videourl' => $videourl,
        'sectionid' => $sectionid,
        'courseid' => $courseid,
        'questions' => $questions, // Array of generated questions
        'message' => 'Section content, video, and quiz regenerated successfully ✅'
    ];
}


function quiz_delete_all_questions($quizid) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    // Delete attempts first
    quiz_delete_all_attempts($quizid);

    // Load quiz
    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
   $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Use question_delete_activity() - this is the correct API
    question_delete_activity($quiz, $context);

    // Reset layout string (optional)
    $quiz->questions = '';
    $DB->update_record('quiz', $quiz);
}


function regenerate_quiz_questions($courseid, $sectionid, $title, $cmid, $api_key,$summary)
{
    global $DB;

    $questiongeneratorprompt = "
You are an AI tutor. Based on the following module summary, generate 4 multiple choice questions that test understanding of the topic.

🔸 **Title:** $title  
🔸 **Summary:** $summary

💡 **Instructions for question format**:
- Each question should be exactly 2 lines:
  1. First line: The question number followed by the question.
  2. Second line: Options A, B, C, D separated by ' | ' and ending with 'Correct Option: X' (X = A/B/C/D).
  
✅ **Example:**
1. What is the capital of France?  
A. Berlin | B. Madrid | C. Paris | D. Rome | Correct Option: C

Now, generate 4 such questions:
";

    $mcq_data = [
        "model" => "gpt-4-turbo",
        "messages" => [["role" => "user", "content" => $questiongeneratorprompt]],
        "temperature" => 0.7
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ],
        CURLOPT_POSTFIELDS => json_encode($mcq_data)
    ]);
    $mcq_response = curl_exec($ch);
    curl_close($ch);

    if (!$mcq_response) return;

    $mcq_result = json_decode($mcq_response, true);
    $mcq_text = $mcq_result['choices'][0]['message']['content'] ?? '';
    if (!$mcq_text) return;

    $lines = array_values(array_filter(explode("\n", trim($mcq_text))));
 $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cmid);

// Create a new unique question category for this quiz instance
$category = question_make_default_categories(array($context));

    $quiz = $DB->get_record('quiz', ['id' => $DB->get_field('course_modules', 'instance', ['id' => $cmid])], '*', MUST_EXIST);
    $qtypeobj = question_bank::get_qtype('multichoice');

    $optionMap = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
    $questions = [];
    $index = 1;

    for ($i = 0; $i < count($lines) - 1; $i += 2) {
        if (!preg_match('/^\d+\.\s*(.+)/', $lines[$i], $qm)) continue;
        if (!preg_match('/A\.\s*(.*?)\s*\|\s*B\.\s*(.*?)\s*\|\s*C\.\s*(.*?)\s*\|\s*D\.\s*(.*?)\s*\|\s*Correct Option:\s*([ABCD])/i', $lines[$i + 1], $om)) continue;

        $questiontext = $qm[1];
        $correctIndex = ord(strtoupper($om[5])) - ord('A');

        $choices = [];
        for ($j = 1; $j <= 4; $j++) {
 $choices[] = [
    'text' => trim($om[$j]),
    'correct' => ($j - 1 === $correctIndex),
    'feedback' => ''
];


        }

$questions[] = [
    'name' => 'Q' . $index,
    'text' => $questiontext,
    'type' => 'multichoice',
    'mark' => 1,
    'feedback' => '',
    'choices' => $choices
];


        $index++;
    }

    if (!empty($questions)) {
        add_questions_to_quiz($courseid, $cmid, $questions);
    }
    return $questions;
}


// function regenerate_specific_section($courseid, $sectionid, $title)
// {
//     global $DB;


//     // 1. Regenerate summary
//     $summary = generate_section_summary($title, $api_key);
//     $videourl = search_youtube_video($title, $youtube_key);

//     // 2. Update section content
//     $sectionupdate = (object) [
//         'id' => $sectionid,
//         'summary' => "<p>$summary</p>",
//         'summaryformat' => FORMAT_HTML
//     ];
//     $DB->update_record('course_sections', $sectionupdate);

//     // 3. Create video/quiz/assignment
//     $section = $DB->get_record('course_sections', ['id' => $sectionid]);
// // Store the video in course_section_video table directly
// $time = time();
// $params = ['courseid' => $courseid, 'sectionid' => $sectionid];
// $record = $DB->get_record('course_section_video', $params);

// if ($record) {
//     $record->videourl = $videourl;
//     $record->timemodified = $time;
//     $DB->update_record('course_section_video', $record);
// } else {
//     $new = new stdClass();
//     $new->courseid = $courseid;
//     $new->sectionid = $sectionid;
//     $new->videourl = $videourl;
//     $new->timecreated = $time;
//     $new->timemodified = $time;
//     $DB->insert_record('course_section_video', $new);
// }


//     return [
//         'success' => true,
//         'videourl' => $videourl,
//         'sectionid' => $sectionid,
//         'courseid' => $courseid,
//         'message' => 'Regenerated successfully ✅'
//     ];
// }
// create video module 
function create_video_module($courseid, $sectionnumber, $videourl, $title)
{
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/mod/supervideo/lib.php');

    $videourl = trim($videourl);

    $module = $DB->get_record('modules', ['name' => 'supervideo'], '*', MUST_EXIST);

    // ===============================
    // 1️⃣ Create course module shell
    // ===============================
    $cm = new stdClass();
    $cm->course   = $courseid;
    $cm->module   = $module->id;
    $cm->section  = $sectionnumber;
    $cm->visible  = 1;
    $cm->added    = time();

    $cmid = add_course_module($cm);

    if (!$cmid) {
        throw new moodle_exception('Failed to create supervideo module');
    }

    // ===============================
    // 2️⃣ Prepare Supervideo data
    // ===============================
    $supervideo = new stdClass();
    $supervideo->course       = $courseid;
    $supervideo->name         = $title;
    $supervideo->intro        = '';
    $supervideo->introformat  = FORMAT_HTML;
    $supervideo->origem       = detect_video_source($videourl); // youtube/drive/link
    $supervideo->videourl     = $videourl; // ✅ THIS IS THE REAL FIELD
    $supervideo->timemodified = time();

    // Optional fields (based on your DB)
    $supervideo->playersize        = 640;
    $supervideo->showcontrols      = 1;
    $supervideo->autoplay          = 0;
    $supervideo->grade_approval    = 0;
    $supervideo->completionpercent = 0;

    // ===============================
    // 3️⃣ Create instance
    // ===============================
    $instanceid = supervideo_add_instance($supervideo, null);

    if (!$instanceid) {
        throw new moodle_exception('Failed to add supervideo instance');
    }

    $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

    course_add_cm_to_section($courseid, $cmid, $sectionnumber);
    set_coursemodule_visible($cmid, 1);

    return $instanceid;
}

/* =====================================================
   Detect Video Source
   ===================================================== */

function detect_video_source($url)
{
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        return 'youtube';
    }

    if (strpos($url, 'drive.google.com') !== false) {
        return 'drive';
    }

    return 'link';
}

function create_file_module($courseid, $sectionnum, $title, $content) {
    global $DB, $CFG, $USER;

    require_once($CFG->dirroot . '/mod/resource/lib.php');
    require_once($CFG->libdir . '/filelib.php');

    $module = $DB->get_record('modules', ['name' => 'resource'], '*', MUST_EXIST);

    // Create course module
    $cm = new stdClass();
    $cm->course     = $courseid;
    $cm->module     = $module->id;
    $cm->section    = $sectionnum;
    $cm->visible    = 1;
    $cm->groupmode  = 0;
    $cm->groupingid = 0;
    $cm->added      = time();

    $cmid = add_course_module($cm);
    if (!$cmid) return false;

    // Convert plain content into formatted HTML
    $formattedcontent = format_notes_html($title, $content);

    // Draft area
    $draftitemid = file_get_unused_draft_itemid();
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();

    $filename = clean_filename($title) . '.html';

    $fileinfo = [
        'contextid' => $usercontext->id,
        'component' => 'user',
        'filearea'  => 'draft',
        'itemid'    => $draftitemid,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $fs->create_file_from_string($fileinfo, $formattedcontent);

    // Resource instance
    $resource = new stdClass();
    $resource->course        = $courseid;
    $resource->name          = $title . " Notes";
    $resource->intro         = '';
    $resource->introformat   = FORMAT_HTML;
    $resource->files         = $draftitemid;
    $resource->coursemodule  = $cmid;

    $resourceid = resource_add_instance($resource, null);
    if (!$resourceid) return false;

    $DB->set_field('course_modules', 'instance', $resourceid, ['id' => $cmid]);

    $context = context_module::instance($cmid);

    file_save_draft_area_files(
        $draftitemid,
        $context->id,
        'mod_resource',
        'content',
        0,
        ['subdirs' => 0]
    );

    course_add_cm_to_section($courseid, $cmid, $sectionnum);

    return $cmid;
}
function format_notes_html($title, $content) {

    // Convert numbered headings into <h2>
    $content = preg_replace('/^\s*\d+\.\s*(.+)$/m', '<h2>$1</h2>', $content);

    // Convert line breaks to paragraphs
    $content = preg_replace('/\n\s*\n/', '</p><p>', trim($content));
    $content = '<p>' . $content . '</p>';

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                padding: 50px;
                max-width: 900px;
                margin: auto;
                line-height: 1.8;
                color: #333;
                background: #ffffff;
            }
            h1 {
                font-size: 32px;
                color: #003152;
                margin-bottom: 30px;
            }
            h2 {
                font-size: 22px;
                margin-top: 30px;
                color: #0f3f66;
                border-left: 5px solid #003152;
                padding-left: 10px;
            }
            p {
                font-size: 16px;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <h1>' . htmlspecialchars($title) . '</h1>
        ' . $content . '
    </body>
    </html>';
}



function local_course_ai_mark_cm_complete($cmid, $userid = null) {
    global $DB, $USER, $CFG;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Load cm and course, will throw if invalid
    $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $context = context_module::instance($cm->id);

    // permission check for viewing this module
    if (!has_capability('mod/videofile:view', $context, $userid, false)) {
        return false;
    }

    // Prefer loading completionlib from libdir (safer than completion/completion.php)
    $completionlib = $CFG->libdir . '/completionlib.php';
    if (!file_exists($completionlib)) {
        // Completion system unavailable — avoid fatal error, return false.
        debugging("Completion library missing: $completionlib", DEBUG_DEVELOPER);
        return false;
    }

    require_once($completionlib);

    // instantiate completion_info for the course
    try {
        $completion = new completion_info($course);
    } catch (Exception $e) {
        debugging('Failed to instantiate completion_info: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }

    // Try to set COMPLETE using API
    $ok = false;
    try {
        $ok = $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
    } catch (Exception $e) {
        debugging('update_state exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
        $ok = false;
    }

    if ($ok) {
        return true;
    }

    // If update_state didn't work, check if already complete
    $cmdata = $completion->get_data($cm, false, $userid);
    if (!empty($cmdata) && !empty($cmdata->completionstate) && (int)$cmdata->completionstate === COMPLETION_COMPLETE) {
        return true;
    }

    // Fallback: try to set as viewed (useful for "view" completion rules)
    if (function_exists('completion_set_module_viewed')) {
        try {
            $res = completion_set_module_viewed($userid, $cm);
            // Re-check state
            $cmdata = $completion->get_data($cm, false, $userid);
            if (!empty($cmdata) && !empty($cmdata->completionstate) && (int)$cmdata->completionstate === COMPLETION_COMPLETE) {
                return true;
            }
            // If viewed didn't produce COMPLETE, still consider viewed as success for view-only completions
            return (bool)$res;
        } catch (Exception $e) {
            debugging('completion_set_module_viewed exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    return false;
}
// create assignment module
function create_assignment_module($courseid, $sectionnumber, $title)
{
    global $DB;
    $assignmentmodule = $DB->get_record('modules', ['name' => 'assign'], '*', MUST_EXIST);
    if (!$assignmentmodule) {
        return;
    }
    $assigncm = new stdClass();
    $assigncm->course = $courseid;
    $assigncm->module = $assignmentmodule->id;
    $assigncm->section = $sectionnumber;
    $assigncm->visible = 1;
    $assigncm->groupmode = 0;
    $assigncm->groupingid = 0;
    $assigncm->added = time();
    $assigncm->showdescription = 0;

    $assigncmid = add_course_module($assigncm);
    if (!$assigncmid) {
        return;
    }
    $assignment = new stdClass();
    $assignment->modulename = 'assign';
    $assignment->course = $courseid;
    $assignment->name = "$title";
    $assignment->intro = "Please complete the assignment related to: $title";
    $assignment->introformat = FORMAT_HTML;
    $assignment->alwaysshowdescription = 1;
    $assignment->submissiondrafts = 1;
    $assignment->sendnotifications = 0;
    $assignment->duedate = time() + (7 * 24 * 60 * 60);
    $assignment->allowsubmissionsfromdate = time();
    $assignment->grade = 100;
    $assignment->cutoffdate = time() + (14 * 24 * 60 * 60);
    $assignment->requiresubmissionstatement = 0;
    $assignment->sendlatenotifications = 0;
    $assignment->gradingduedate = 0;
    $assignment->teamsubmission = 0;
    $assignment->requireallteammemberssubmit = 0;
    $assignment->blindmarking = 0;
    $assignment->markingworkflow = 0;
    $assignment->markingallocation = 0;
    $assignment->coursemodule = $assigncmid;

    $assignmentid = assign_add_instance($assignment);
    if (!$assignmentid) {
        return;
    }

    $DB->set_field('course_modules', 'instance', $assignmentid, ['id' => $assigncmid]);
    course_add_cm_to_section($courseid, $assigncmid, $sectionnumber);
    set_coursemodule_visible($assigncmid, 1);
}

function add_questions_to_quiz($courseid, $cmid, $structuredQuestions)
{
    global $DB, $USER, $CFG;

    require_once($CFG->dirroot . '/mod/quiz/lib.php');
    require_once($CFG->libdir . '/questionlib.php');


    debugging("=== START add_questions_to_quiz ===", DEBUG_DEVELOPER);

    try {

        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
        debugging("CM loaded: " . $cm->id, DEBUG_DEVELOPER);

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        debugging("Quiz loaded: " . $quiz->id, DEBUG_DEVELOPER);

        $context = context_module::instance($cmid);
        debugging("Context ID: " . $context->id, DEBUG_DEVELOPER);

          require_once($CFG->dirroot . '/question/lib.php');

         $category = question_get_default_category($context->id);


        debugging("Category ID: " . $category->id, DEBUG_DEVELOPER);

        $totalmark = 0;
        $slot = 1;

        foreach ($structuredQuestions as $index => $qdata) {

            debugging("Processing Question: " . ($index + 1), DEBUG_DEVELOPER);

            if (empty($qdata['name']) || empty($qdata['text'])) {
                debugging("Question data missing name/text", DEBUG_DEVELOPER);
                continue;
            }

            $questiondata = new stdClass();
           $questiondata->category = $category->id;
         $questiondata->contextid = $category->contextid;

            $questiondata->name = $qdata['name'];
            $questiondata->questiontext = [
                'text' => $qdata['text'],
                'format' => FORMAT_HTML
            ];
            $questiondata->qtype = 'multichoice';
            $questiondata->defaultmark = 3;
            $questiondata->generalfeedback = ['text' => '', 'format' => FORMAT_HTML];
            $questiondata->createdby = $USER->id;
            $questiondata->modifiedby = $USER->id;
            $questiondata->stamp = make_unique_id_code();
            $questiondata->status = 'ready';

            $questiondata->single = 1;
            $questiondata->shuffleanswers = 1;
            $questiondata->answernumbering = 'abc';

            $questiondata->correctfeedback = '';
            $questiondata->correctfeedbackformat = FORMAT_HTML;
            $questiondata->partiallycorrectfeedback = '';
            $questiondata->partiallycorrectfeedbackformat = FORMAT_HTML;
            $questiondata->incorrectfeedback = '';
            $questiondata->incorrectfeedbackformat = FORMAT_HTML;

            $questiondata->answer = [];
            $questiondata->fraction = [];
            $questiondata->feedback = [];

            foreach ($qdata['choices'] as $i => $choice) {

                if (!isset($choice['text'])) {
                    debugging("Choice missing text at index $i", DEBUG_DEVELOPER);
                    continue;
                }

                $questiondata->answer[$i] = [
                    'text' => $choice['text'],
                    'format' => FORMAT_HTML
                ];

                $questiondata->fraction[$i] = !empty($choice['correct']) ? 1 : 0;

                $questiondata->feedback[$i] = [
                    'text' => '',
                    'format' => FORMAT_HTML
                ];
            }

            debugging("Saving question...", DEBUG_DEVELOPER);

            $question = question_bank::get_qtype('multichoice')
                ->save_question($questiondata, $questiondata);

            if (empty($question->id)) {
                debugging("Question save failed!", DEBUG_DEVELOPER);
                continue;
            }

            debugging("Question created ID: " . $question->id, DEBUG_DEVELOPER);

            quiz_add_quiz_question($question->id, $quiz);

        $totalmark += 3;

        }

        quiz_update_sumgrades($quiz);

        $quiz->grade = $totalmark;
        $DB->update_record('quiz', $quiz);

        debugging("=== FINISHED SUCCESSFULLY ===", DEBUG_DEVELOPER);

        return "Questions added successfully. Total mark: $totalmark";

    } catch (Exception $e) {

        debugging("EXCEPTION: " . $e->getMessage(), DEBUG_DEVELOPER);
        debugging($e->getTraceAsString(), DEBUG_DEVELOPER);

        return "Error occurred: " . $e->getMessage();
    }
}




// function generate_course_image($coursename, $courseid)
// {
//     global $CFG;
//     require_once($CFG->libdir . '/filelib.php');
//     require_once($CFG->dirroot . '/course/lib.php'); // for reset_course_image

//     $api_key = 'AIzaSyDu-cy1aU8hzqybz_kyaeV1HhwKZF2RRss'; // Replace with your actual key
// $cse_id = 'e68a232bd35cc4659'; // Your Google Custom Search Engine ID

//     $query = urlencode($coursename . " course image");
//     $url = "https://www.googleapis.com/customsearch/v1?q={$query}&searchType=image&num=1&key={$api_key}&cx={$cse_id}";

//     $ch = curl_init($url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     $response = curl_exec($ch);
//     curl_close($ch);

//     $data = json_decode($response, true);

//     if (empty($data['items'][0]['link'])) {
//         return; // No image found
//     }

//     $image_url = $data['items'][0]['link'];
//     $tempimagepath = $CFG->dataroot . '/temp/courseimage_' . $courseid . '.jpg';

//     file_put_contents($tempimagepath, file_get_contents($image_url));

//     $context = context_course::instance($courseid);

//     $filerecord = new stdClass();
//     $filerecord->component = 'course';
//     $filerecord->filearea = 'overviewfiles';
//     $filerecord->contextid = $context->id;
//     $filerecord->itemid = 0;
//     $filerecord->filepath = '/';
//     $filerecord->filename = 'courseimage.jpg';

//     $fs = get_file_storage();

//     // Remove old overview images
//     $existingfiles = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'id', false);
//     foreach ($existingfiles as $file) {
//         $file->delete();
//     }

//     $fs->create_file_from_pathname($filerecord, $tempimagepath);
//     @unlink($tempimagepath);
// }
function generate_course_image($coursename, $courseid, $api_key) {
    global $CFG;
    require_once($CFG->libdir . '/filelib.php');
    require_once($CFG->dirroot . '/course/lib.php'); // For reset_course_image
    require_once($CFG->dirroot . '/lib/filelib.php'); // For download_file_content()

    // Step 1: Generate image using DALL·E 3
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    $postData = [
        "model" => "dall-e-3",
        "prompt" => $coursename,
        "n" => 1,
        "size" => "1024x1024"
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . $api_key
        ],
        CURLOPT_POSTFIELDS => json_encode($postData)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        debugging("DALL·E API error: $response", DEBUG_DEVELOPER);
        return;
    }

    $data = json_decode($response, true);
    if (empty($data['data'][0]['url'])) {
        debugging("No image URL returned by DALL·E", DEBUG_DEVELOPER);
        return;
    }

    // Step 2: Download image
    $image_url = $data['data'][0]['url'];
    $imageData = download_file_content($image_url);
    if (!$imageData) {
        debugging("Failed to download image from: $image_url", DEBUG_DEVELOPER);
        return;
    }

    $tempimagepath = $CFG->dataroot . '/temp/courseimage_' . $courseid . '.jpg';
    file_put_contents($tempimagepath, $imageData);

    // Step 3: Upload image to course overview
    $context = context_course::instance($courseid);
    $filerecord = new stdClass();
    $filerecord->component = 'course';
    $filerecord->filearea = 'overviewfiles';
    $filerecord->contextid = $context->id;
    $filerecord->itemid = 0;
    $filerecord->filepath = '/';
    $filerecord->filename = 'courseimage.jpg';

    $fs = get_file_storage();

    // Delete existing overview images
    $existing = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'id', false);
    foreach ($existing as $file) {
        $file->delete();
    }

    // Create new course image file
    $fs->create_file_from_pathname($filerecord, $tempimagepath);

    // Step 4: Cleanup and cache reset
    @unlink($tempimagepath); // remove temp file
}


// function generate_course_image($coursename, $courseid, $api_key)
// {

//     global $CFG;
//     require_once($CFG->libdir . '/filelib.php');
//     require_once($CFG->dirroot . '/course/lib.php'); // for reset_course_image
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/images/generations');
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//     curl_setopt($ch, CURLOPT_POST, 1);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Content-Type: application/json',
//         'Authorization: Bearer ' . $api_key
//     ]);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
//         "prompt" => $coursename,
//         "n" => 1,
//         "size" => "512x512"
//     ]));

//     $response = curl_exec($ch);
//     curl_close($ch);

//     $data = json_decode($response, true);
//     $image_url = $data['data'][0]['url'];
//     $tempimagepath = $CFG->dataroot . '/temp/courseimage_' . $courseid . '.jpg';
//     file_put_contents($tempimagepath, file_get_contents($image_url));

//     // Get course context
//     $context = context_course::instance($courseid);

//     // Define file record
//     $filerecord = new stdClass();
//     $filerecord->component = 'course';
//     $filerecord->filearea = 'overviewfiles';
//     $filerecord->contextid = $context->id;
//     $filerecord->itemid = 0;
//     $filerecord->filepath = '/';
//     $filerecord->filename = 'courseimage.jpg';

//     // Save file using file storage
//     $fs = get_file_storage();

//     // Clean up any existing images in overviewfiles
//     $existingfiles = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'id', false);
//     foreach ($existingfiles as $file) {
//         $file->delete();
//     }

//     $fs->create_file_from_pathname($filerecord, $tempimagepath);

//     // Optionally delete temp file
//     @unlink($tempimagepath);

// }


function generate_course_summary($coursename, $courseid, $api_key)
{
    global $DB;

    $prompt = "Write a concise, engaging summary for a course titled \"$coursename\". Mention key learning outcomes and benefits.";

    $data = [
        "model" => "gpt-4",
        "messages" => [
            [
                "role" => "system",
                "content" => "You will receive a text input from the user. Your task is to generate text based on their request. Follow these important instructions:
    1. Return the summary in plain text only.
    2. Do not include any markdown formatting, greetings, or platitudes."
            ],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.1
    ];
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key"
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) {
        return;
    }
    $result = json_decode($response, true);
    $summary = $result['choices'][0]['message']['content'] ?? '';
    if (!$summary) {
        return;
    }
    // Update course summary in Moodle
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $course->summary = $summary;
    $course->summaryformat = FORMAT_HTML;
    $DB->update_record('course', $course);
}
// function generate_course_summary($coursename, $courseid, $api_key)
// {
//     global $DB;

//     if (empty($coursename) || empty($courseid) || empty($api_key)) {
//         return;
//     }

//     $formatted_prompt = <<<PROMPT
// You are a senior instructional designer. Write a concise and engaging summary for a course titled: "{$coursename}".

// Guidelines:
// - Length: 3–5 sentences
// - Tone: Clear, professional, and inspiring
// - Purpose: Help learners understand what they will gain
// - Include: Key learning outcomes, skills gained, and real-world benefits
// - Avoid: Buzzwords, generic filler, or technical jargon

// Output only the summary paragraph. Do not include a title or formatting.
// PROMPT;

//     $data = [
//         "model" => "gpt-4-turbo",
//         "messages" => [
//             [
//                 "role" => "system",
//                 "content" => "You are a professional e-learning course creator. Always respond with high-quality, learner-focused summaries written in plain language."
//             ],
//             [
//                 "role" => "user",
//                 "content" => $formatted_prompt
//             ]
//         ],
//         "temperature" => 0.3,
//         "max_tokens" => 300
//     ];

//     $ch = curl_init("https://api.openai.com/v1/chat/completions");
//     curl_setopt_array($ch, [
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_POST => true,
//         CURLOPT_HTTPHEADER => [
//             "Content-Type: application/json",
//             "Authorization: Bearer $api_key"
//         ],
//         CURLOPT_POSTFIELDS => json_encode($data)
//     ]);

//     $response = curl_exec($ch);
//     curl_close($ch);

//     if (!$response) {
//         return;
//     }

//     $result = json_decode($response, true);
//     $summary = $result['choices'][0]['message']['content'] ?? '';

//     if (!$summary) {
//         return;
//     }

//     // Update course summary in Moodle
//     $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
//     $course->summary = $summary;
//     $course->summaryformat = FORMAT_HTML;
//     $DB->update_record('course', $course);
// }
