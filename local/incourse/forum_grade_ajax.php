<?php
require_once("../../config.php");
require_login();
global $DB, $CFG, $OUTPUT, $PAGE;

// Ensure proper context for rendering pictures
$PAGE->set_context(context_system::instance());

// Parameters
$forumid = required_param('forumid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

// Fetch forum info: courseid, section name, forum name
$forumRecords = $DB->get_records_sql("
    SELECT cm.course, f.name AS forumname, cs.name AS sectionname
    FROM {forum} f
    JOIN {course_modules} cm ON cm.instance = f.id
    LEFT JOIN {course_sections} cs ON cs.id = cm.section
    WHERE f.id = :forumid
", ['forumid' => $forumid]);

$forumInfo = reset($forumRecords);
$course = $DB->get_record('course', ['id' => $forumInfo->course], '*', MUST_EXIST);

// Fetch posts + grade + question name + email
$posts = $DB->get_records_sql("
    SELECT fp.*, 
           u.*, 
           fd.name AS question,
           fg.grade
    FROM {forum_posts} fp
    JOIN {forum_discussions} fd ON fd.id = fp.discussion
    JOIN {user} u ON u.id = fp.userid
    LEFT JOIN {forum_grades} fg 
           ON fg.userid = fp.userid 
          AND fg.forum = fd.forum
    WHERE fd.forum = :forumid 
      AND fp.deleted = 0
    ORDER BY fp.created ASC
", ['forumid' => $forumid]);

$data = [];
$i = 1;
foreach ($posts as $p) {
    $userpicture = $OUTPUT->user_picture($p, ['size'=>35, 'class'=>'rounded-full inline-block mr-2'], null, false);

    // Convert grade 1-10 → scaled 10-100
    $scaledGrade = ($p->grade !== null) ? intval($p->grade) * 10 : 0;

    $data[] = [
        'srno'     => $i++,
        'picture'  => $userpicture, // HTML for popup
        'student'  => fullname($p),
        'email'    => $p->email,
        'response' => strip_tags($p->message),
        'question' => $p->question,
        'grade'    => $scaledGrade
    ];
}


// --------------------
// CSV / Excel Download
// --------------------
if ($download === 'xlsx') {

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="forum_responses.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $output = fopen('php://output', 'w');

    // Top info
    fputcsv($output, ['Course', $course->fullname]);
    fputcsv($output, ['Section', $forumInfo->sectionname ?? 'General']);
    fputcsv($output, ['Forum', $forumInfo->forumname]);
    fputcsv($output, []); // empty row

    // Column headers
    fputcsv($output, ['Sr.No', 'Student', 'Email', 'Response', 'Grade']);

    foreach ($data as $row) {
        // Pictures cannot be exported, use placeholder
        fputcsv($output, [$row['srno'], $row['student'], $row['email'], $row['response'], $row['grade']]);
    }

    fclose($output);
    exit;
}

// --------------------
// PDF Download
// --------------------
if ($download === 'pdf') {

    require_once($CFG->libdir.'/pdflib.php');
    $pdf = new pdf();
    $pdf->AddPage();

    // Header info
    $pdf->SetFont('helvetica','B',14);
    $pdf->MultiCell(0, 8, 'Course: '.$course->fullname, 0, 'L', false, 1);
    $pdf->MultiCell(0, 8, 'Section: '.($forumInfo->sectionname ?? 'General'), 0, 'L', false, 1);
    $pdf->MultiCell(0, 8, 'Forum: '.$forumInfo->forumname, 0, 'L', false, 1);
    $pdf->MultiCell(0, 10, 'Question: '.($data[0]['question'] ?? ''), 0, 'L', false, 1);
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica','B',12);
    $pdf->SetFillColor(230, 230, 230);

    // Adjusted column widths
    $widths = [
        'srno'     => 10,
        'student'  => 45,
        'email'    => 50,
        'response' => 70,
        'grade'    => 15
    ];

    $pdf->Cell($widths['srno'], 8, '#',1,0,'C',true);
    $pdf->Cell($widths['student'], 8, 'Student',1,0,'C',true);
    $pdf->Cell($widths['email'], 8, 'Email',1,0,'C',true);
    $pdf->Cell($widths['response'], 8, 'Response',1,0,'C',true);
    $pdf->Cell($widths['grade'], 8, 'Grade',1,1,'C',true);

    $pdf->SetFont('helvetica','',11);
    $srno = 1;

    foreach ($data as $row) {
        // Calculate max row height
        $studentHeight  = $pdf->GetStringHeight($widths['student'], $row['student']);
        $emailHeight    = $pdf->GetStringHeight($widths['email'], $row['email']);
        $responseHeight = $pdf->GetStringHeight($widths['response'], $row['response']);
        $gradeHeight    = $pdf->GetStringHeight($widths['grade'], $row['grade']);
        $rowHeight = max($studentHeight, $emailHeight, $responseHeight, $gradeHeight);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell($widths['srno'], $rowHeight, $srno++, 1, 'C', false, 0);
        $pdf->MultiCell($widths['student'], $rowHeight, $row['student'], 1, 'L', false, 0);
        $pdf->MultiCell($widths['email'], $rowHeight, $row['email'], 1, 'L', false, 0);
        $pdf->MultiCell($widths['response'], $rowHeight, $row['response'], 1, 'L', false, 0);

        // Grade
        $pdf->SetXY($x + $widths['srno'] + $widths['student'] + $widths['email'] + $widths['response'], $y);
        $pdf->MultiCell($widths['grade'], $rowHeight, $row['grade'], 1, 'C', false, 1);
    }

    $pdf->Output('forum_responses.pdf', 'D');
    exit;
}


// --------------------
// Default → return JSON for popup
// --------------------
echo json_encode($data);
exit;
