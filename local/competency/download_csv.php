<?php
// ⚠️ NO whitespace or empty line before this line
require_once('../../config.php');
require_login();

@error_reporting(E_ALL);
@ini_set('display_errors', 1);

global $DB;

$filename = "competency_report_" . date("Ymd_His") . ".csv";

// ✅ Main query (same as $searchSqlcomp, no limits)
$sql = "SELECT lr.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname, u.id AS userid, u.username, ct.title, 
        cc.id AS ccid, cc.name AS subcompetency, c.id AS comptencyid, c.comptencyname AS subsubcomp, lr.rating,
        MAX(CASE WHEN r.shortname = 'sbuhead' THEN ud.data END) AS sbuhead,
        MAX(CASE WHEN r.shortname = 'reportingmanager' THEN ud.data END) AS manager,
        MAX(CASE WHEN r.shortname = 'interimmanager' THEN ud.data END) AS finalmanager,
        MAX(CASE WHEN udf.shortname = 'department' THEN udd.data END) AS department,
        MAX(CASE WHEN r.shortname = 'landmanager' THEN ud.data END) AS ldmanager,
        lr.tearms, lr.progstatus, cu.year
    FROM {landd_rating} AS lr
    INNER JOIN {competency_users} AS cu ON lr.master_competencyid = cu.id
    INNER JOIN {competency_title} AS ct ON cu.ctid = ct.id
    LEFT JOIN {competency_category} AS cc ON lr.competencyid = cc.id
    LEFT JOIN {competencies} AS c ON lr.subcomptencyid = c.id
    INNER JOIN {user} AS u ON cu.userid = u.id
    INNER JOIN {user_info_data} AS ud ON cu.userid = ud.userid
    INNER JOIN {user_info_field} uf ON ud.fieldid = uf.id
    INNER JOIN {role} r ON uf.shortname = r.shortname
    LEFT JOIN {user_info_data} AS udd ON cu.userid = udd.userid
    LEFT JOIN {user_info_field} AS udf ON udd.fieldid = udf.id
    GROUP BY lr.id";

$results = $DB->get_records_sql($sql);

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

//  CSV Headings
fputcsv($output, [
    'Full Name', 'Username', 'Main Competency', 'Sub Competency', 'Sub Sub Competency',
    'Department', 'SBU Head', 'Reporting Manager', 'Interim Manager', 'L&D Manager',
    'Rating', 'Rating Status', 'Terms', 'Year'
]);

foreach ($results as $row) {
    $status = $row->rating <= 4 ? 'Red' : ($row->rating <= 7 ? 'Yellow' : 'Green');
    $terms = $row->tearms == 1 ? 'First Half' : 'Second Half';

    fputcsv($output, [
        $row->fullname,
        $row->username,
        $row->title,
        $row->subcompetency,
        $row->subsubcomp,
        $row->department,
        $row->sbuhead,
        $row->manager,
        $row->finalmanager,
        $row->ldmanager,
        $row->rating,
        $status,
        $terms,
        $row->year
    ]);
}

fclose($output);
exit;
