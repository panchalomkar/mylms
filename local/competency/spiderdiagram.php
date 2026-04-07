<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Competency Spider Diagram - Radar Chart View
 * Shows user competency scores as a spider/radar diagram.
 * Visible to the user (own data), their manager, and L&D team.
 *
 * @package    local_competency
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/competency/pagination.php');
require_once($CFG->dirroot . '/local/competency/lib.php');

$activepage = 'spiderdiagram';
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_title(get_string('spiderdiagram', 'local_competency'));
$PAGE->set_url($CFG->wwwroot . '/local/competency/spiderdiagram.php');
$PAGE->set_heading(get_string('spiderdiagram', 'local_competency'));
$PAGE->navbar->add(get_string('spiderdiagram', 'local_competency'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/competency/competency_pro.css'));

// Determine role access
$isManager     = has_capability('local/competency:managerrating', $context);
$isLandD       = has_capability('local/competency:landdreport', $context);
$isSelfOnly    = !$isManager && !$isLandD;

echo $OUTPUT->header();

require_once($CFG->dirroot . '/local/competency/header.php');
require_once($CFG->dirroot . '/local/competency/tabs.php');

global $USER, $DB;

// --- Filter params ---
$selectedUserId  = optional_param('filteruserid', $USER->id, PARAM_INT);
$selectedTerms   = optional_param('filterterms', 1, PARAM_INT);
$selectedDept    = optional_param('filterdept', '', PARAM_TEXT);

// Self-only users can only see their own data
if ($isSelfOnly) {
    $selectedUserId = $USER->id;
}

// Fetch all users for manager/L&D dropdown (filter by department if set)
$usersForFilter = [];
if ($isManager || $isLandD) {
    $deptClause = '';
    $deptParams = [];
    if (!empty($selectedDept)) {
        $deptClause = " AND uif.data = ? ";
        $deptParams[] = $selectedDept;
    }
    $userFilterSql = "SELECT DISTINCT u.id, u.firstname, u.lastname, uif.data as department
                      FROM {user} u
                      LEFT JOIN {user_info_data} uif ON uif.userid = u.id
                      LEFT JOIN {user_info_field} uiff ON uiff.id = uif.fieldid AND uiff.shortname = 'department'
                      WHERE u.deleted = 0 AND u.id != 1
                      $deptClause
                      ORDER BY u.firstname, u.lastname";
    $usersForFilter = $DB->get_records_sql($userFilterSql, $deptParams);

    // Fetch unique departments for filter
    $deptsSql = "SELECT DISTINCT uif.data as department
                 FROM {user_info_data} uif
                 JOIN {user_info_field} uiff ON uiff.id = uif.fieldid AND uiff.shortname = 'department'
                 WHERE uif.data != ''
                 ORDER BY uif.data";
    $departments = $DB->get_records_sql($deptsSql, []);
}

// --- Fetch competency data for radar chart ---
// Get all main competencies
$mainCompetencies = $DB->get_records_sql(
    "SELECT ct.id, ct.title FROM {competency_title} ct WHERE ct.isdeleted = 0 ORDER BY ct.id"
);

$radarLabels  = [];
$radarSelf    = [];
$radarManager = [];
$radarLandD   = [];

foreach ($mainCompetencies as $ct) {
    // Get sub-competencies under this main competency
    $subComps = $DB->get_records_sql(
        "SELECT cc.id, cc.name FROM {competency_category} cc WHERE cc.ctid = ? AND cc.isdeleted = 0",
        [$ct->id]
    );

    if (empty($subComps)) {
        continue;
    }

    // Self rating avg for this main competency
    $selfAvgSql = "SELECT AVG(sr.rating) as avg_rating
                   FROM {sudent_rating} sr
                   JOIN {competency_users} cu ON cu.id = sr.master_competencyid
                   WHERE cu.userid = ? AND cu.ctid = ? AND sr.tearms = ?";
    $selfAvg = $DB->get_record_sql($selfAvgSql, [$selectedUserId, $ct->id, $selectedTerms]);

    // Manager rating avg for this main competency
    $managerAvgSql = "SELECT AVG(mr.rating) as avg_rating
                      FROM {manager_rating} mr
                      JOIN {competency_users} cu ON cu.id = mr.master_competencyid
                      WHERE cu.userid = ? AND cu.ctid = ? AND mr.tearms = ?";
    $managerAvg = $DB->get_record_sql($managerAvgSql, [$selectedUserId, $ct->id, $selectedTerms]);

    // L&D final rating avg for this main competency
    $landDAvgSql = "SELECT AVG(lr.rating) as avg_rating
                    FROM {landd_rating} lr
                    JOIN {competency_users} cu ON cu.id = lr.master_competencyid
                    WHERE cu.userid = ? AND cu.ctid = ? AND lr.tearms = ? AND lr.landdstatus = 1";
    $landDAvg = $DB->get_record_sql($landDAvgSql, [$selectedUserId, $ct->id, $selectedTerms]);

    $radarLabels[]  = addslashes($ct->title);
    $radarSelf[]    = $selfAvg    ? round((float)$selfAvg->avg_rating, 2)    : 0;
    $radarManager[] = $managerAvg ? round((float)$managerAvg->avg_rating, 2) : 0;
    $radarLandD[]   = $landDAvg   ? round((float)$landDAvg->avg_rating, 2)   : 0;
}

// Selected user info
$selectedUserObj = $DB->get_record('user', ['id' => $selectedUserId]);
$selectedUserName = $selectedUserObj ? fullname($selectedUserObj) : 'Unknown User';

// Encode for JS
$jsLabels   = json_encode(array_values($radarLabels));
$jsSelf     = json_encode(array_values($radarSelf));
$jsManager  = json_encode(array_values($radarManager));
$jsLandD    = json_encode(array_values($radarLandD));

?>

<div class="comp-spider-container">

    <!-- Header -->
    <div class="comp-spider-header">
        <div class="comp-spider-title">
            <i class="fa fa-spider" aria-hidden="true"></i>
            <?php echo get_string('spiderdiagram', 'local_competency'); ?>
        </div>

        <?php if ($isManager || $isLandD): ?>
        <div class="comp-spider-filters">
            <form method="get" action="spiderdiagram.php" class="d-flex flex-wrap gap-2 align-items-end" style="gap:12px; display:flex;">
                <?php if (!empty($departments)): ?>
                <div class="comp-form-group" style="margin-bottom:0;min-width:160px;">
                    <label class="comp-form-label">Department</label>
                    <select name="filterdept" class="form-control comp-form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept->department); ?>"
                                <?php if ($selectedDept === $dept->department) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($dept->department); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="comp-form-group" style="margin-bottom:0;min-width:200px;">
                    <label class="comp-form-label">Employee</label>
                    <select name="filteruserid" class="form-control comp-form-select">
                        <?php foreach ($usersForFilter as $u): ?>
                            <option value="<?php echo $u->id; ?>" <?php if ($selectedUserId == $u->id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars(fullname($u)); ?>
                                <?php if (!empty($u->department)): ?> — <?php echo htmlspecialchars($u->department); ?><?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="comp-form-group" style="margin-bottom:0;min-width:120px;">
                    <label class="comp-form-label">Term</label>
                    <select name="filterterms" class="form-control comp-form-select">
                        <option value="1" <?php if ($selectedTerms == 1) echo 'selected'; ?>>Term 1</option>
                        <option value="2" <?php if ($selectedTerms == 2) echo 'selected'; ?>>Term 2</option>
                    </select>
                </div>

                <div class="comp-form-group" style="margin-bottom:0;">
                    <label class="comp-form-label">&nbsp;</label>
                    <button type="submit" class="btn-comp-primary">
                        <i class="fa fa-search fa-fw"></i> View
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="comp-spider-filters">
            <form method="get" action="spiderdiagram.php" style="display:flex;gap:12px;align-items:flex-end;">
                <div class="comp-form-group" style="margin-bottom:0;min-width:120px;">
                    <label class="comp-form-label">Term</label>
                    <select name="filterterms" class="form-control comp-form-select">
                        <option value="1" <?php if ($selectedTerms == 1) echo 'selected'; ?>>Term 1</option>
                        <option value="2" <?php if ($selectedTerms == 2) echo 'selected'; ?>>Term 2</option>
                        <option value="3" <?php if ($selectedTerms == 3) echo 'selected'; ?>>Term 3</option>
                    </select>
                </div>
                <div class="comp-form-group" style="margin-bottom:0;">
                    <label class="comp-form-label">&nbsp;</label>
                    <button type="submit" class="btn-comp-primary"><i class="fa fa-sync fa-fw"></i> Refresh</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- User name banner -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:14px 20px;
                background:#003152;border-radius:10px;">
        <div style="width:40px;height:40px;border-radius:50%;background:rgba(0,180,216,0.2);
                    border:2px solid #ec9707;display:flex;align-items:center;justify-content:center;
                    color:#000;font-weight:700;font-size:15px;">
            <?php echo strtoupper(substr($selectedUserName, 0, 1)); ?>
        </div>
        <div>
            <div style="color:white;font-weight:600;font-size:15px;font-family:'DM Sans',sans-serif;">
                <?php echo htmlspecialchars($selectedUserName); ?>
            </div>
            <div style="color:rgba(255,255,255,0.5);font-size:12px;">
                Competency Profile — Term <?php echo $selectedTerms; ?>
            </div>
        </div>
        <?php
        // Compute overall avg for quick score
        $overallAvg = 0;
        if (!empty($radarLandD) && array_sum($radarLandD) > 0) {
            $nonZero = array_filter($radarLandD, fn($v) => $v > 0);
            $overallAvg = count($nonZero) > 0 ? array_sum($radarLandD) / count($nonZero) : 0;
        } elseif (!empty($radarManager) && array_sum($radarManager) > 0) {
            $nonZero = array_filter($radarManager, fn($v) => $v > 0);
            $overallAvg = count($nonZero) > 0 ? array_sum($radarManager) / count($nonZero) : 0;
        } elseif (!empty($radarSelf) && array_sum($radarSelf) > 0) {
            $nonZero = array_filter($radarSelf, fn($v) => $v > 0);
            $overallAvg = count($nonZero) > 0 ? array_sum($radarSelf) / count($nonZero) : 0;
        }
        $overallRounded = round($overallAvg, 1);
        $zoneClass = $overallRounded >= 8 ? 'green' : ($overallRounded >= 5 ? 'yellow' : ($overallRounded > 0 ? 'red' : ''));
        $zoneLabel = $overallRounded >= 8 ? 'Green Zone' : ($overallRounded >= 5 ? 'Yellow Zone' : ($overallRounded > 0 ? 'Red Zone' : 'No Data'));
        ?>
        <?php if ($overallRounded > 0): ?>
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
            <div class="comp-score-display <?php echo $zoneClass; ?>">
                <?php echo $overallRounded; ?>
            </div>
            <div>
                <div class="zone-badge zone-<?php echo $zoneClass; ?>"><?php echo $zoneLabel; ?></div>
                <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:3px;">Overall Score</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($radarLabels)): ?>
    <div class="comp-empty-state">
        <i class="fa fa-chart-radar fa-3x" style="opacity:0.3;color:var(--comp-teal);font-size:48px;"></i>
        <p>No competency data found for this user. Please ensure competencies are assigned and rated.</p>
    </div>
    <?php else: ?>

    <!-- Spider Chart Canvas -->
    <div style="position:relative;width:100%;max-width:600px;margin:0 auto;">
        <canvas id="comp-spider-chart" height="460"></canvas>
    </div>

    <!-- Legend -->
    <div class="comp-spider-legend">
        <div class="legend-item">
            <div class="legend-dot" style="background:#00B4D8;box-shadow:0 0 6px rgba(0,180,216,0.5);"></div>
            Self Rating
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#FFB703;box-shadow:0 0 6px rgba(255,183,3,0.5);"></div>
            Manager Rating
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#059669;box-shadow:0 0 6px rgba(5,150,105,0.5);"></div>
            Final L&amp;D Rating
        </div>
    </div>

    <!-- Zone Legend -->
    <div class="comp-zone-legend" style="margin-top:20px;">
        <span class="legend-title">Zones:</span>
        <span class="legend-item"><span class="circle_green"></span> Green Zone: 8–10 (Strong)</span>
        <span class="legend-item"><span class="circle_yellow"></span> Yellow Zone: 5–7 (Developing)</span>
        <span class="legend-item"><span class="circle_red"></span> Red Zone: 1–4 (Needs Improvement)</span>
    </div>

    <!-- Per-competency breakdown table -->
    <div style="margin-top:32px;">
        <div class="comp-section-title">
            <i class="fa fa-table fa-fw"></i>
            Competency Breakdown
        </div>
        <div class="comp-table-wrapper">
        <table class="comp-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Competency</th>
                    <th>Self Rating</th>
                    <th>Manager Rating</th>
                    <th>L&amp;D Final</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($radarLabels as $idx => $label):
                $sr = $radarSelf[$idx]    ?? 0;
                $mr = $radarManager[$idx] ?? 0;
                $lr = $radarLandD[$idx]   ?? 0;
                $displayScore = $lr > 0 ? $lr : ($mr > 0 ? $mr : $sr);
                $zone = $displayScore >= 8 ? 'green' : ($displayScore >= 5 ? 'yellow' : ($displayScore > 0 ? 'red' : ''));
                $zoneText = $displayScore >= 8 ? 'Strong' : ($displayScore >= 5 ? 'Developing' : ($displayScore > 0 ? 'Needs Improvement' : 'Not Rated'));
            ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($label); ?></td>
                <td style="text-align:center;">
                    <?php if ($sr > 0): ?>
                    <span class="comp-score-display <?php echo $sr >= 8 ? 'green' : ($sr >= 5 ? 'yellow' : 'red'); ?>" style="width:36px;height:36px;font-size:13px;">
                        <?php echo $sr; ?>
                    </span>
                    <?php else: echo '<span style="color:#94A3B8;font-size:12px;">—</span>'; endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php if ($mr > 0): ?>
                    <span class="comp-score-display <?php echo $mr >= 8 ? 'green' : ($mr >= 5 ? 'yellow' : 'red'); ?>" style="width:36px;height:36px;font-size:13px;">
                        <?php echo $mr; ?>
                    </span>
                    <?php else: echo '<span style="color:#94A3B8;font-size:12px;">—</span>'; endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php if ($lr > 0): ?>
                    <span class="comp-score-display <?php echo $lr >= 8 ? 'green' : ($lr >= 5 ? 'yellow' : 'red'); ?>" style="width:36px;height:36px;font-size:13px;">
                        <?php echo $lr; ?>
                    </span>
                    <?php else: echo '<span style="color:#94A3B8;font-size:12px;">—</span>'; endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php if ($zone): ?>
                    <span class="zone-badge zone-<?php echo $zone; ?>"><?php echo $zoneText; ?></span>
                    <?php else: echo '<span style="color:#94A3B8;font-size:12px;">Not Rated</span>'; endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <?php endif; // end if not empty labels ?>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var labels   = <?php echo $jsLabels; ?>;
    var selfData     = <?php echo $jsSelf; ?>;
    var managerData  = <?php echo $jsManager; ?>;
    var landDData    = <?php echo $jsLandD; ?>;

    if (!labels.length) return;

    var ctx = document.getElementById('comp-spider-chart');
    if (!ctx) return;

    // Determine zone background bands for radar (as annotation in canvas)
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Self Rating',
                    data: selfData,
                    backgroundColor: 'rgba(0, 180, 216, 0.08)',
                    borderColor: '#00B4D8',
                    pointBackgroundColor: '#00B4D8',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2.5,
                    fill: true,
                },
                {
                    label: 'Manager Rating',
                    data: managerData,
                    backgroundColor: 'rgba(255, 183, 3, 0.08)',
                    borderColor: '#FFB703',
                    pointBackgroundColor: '#FFB703',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2.5,
                    fill: true,
                },
                {
                    label: 'L&D Final Rating',
                    data: landDData,
                    backgroundColor: 'rgba(5, 150, 105, 0.12)',
                    borderColor: '#059669',
                    pointBackgroundColor: '#059669',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2.5,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                r: {
                    min: 0,
                    max: 10,
                    ticks: {
                        stepSize: 2,
                        font: { family: 'Inter, sans-serif', size: 10 },
                        color: '#94A3B8',
                        backdropColor: 'transparent',
                        callback: function(value) { return value; }
                    },
                    grid: {
                        color: function(context) {
                            var v = context.tick ? context.tick.value : 0;
                            if (v === 4) return 'rgba(220, 38, 38, 0.35)';
                            if (v === 7) return 'rgba(217, 119, 6, 0.35)';
                            if (v === 10) return 'rgba(5, 150, 105, 0.4)';
                            return 'rgba(226, 232, 240, 0.5)';
                        },
                        lineWidth: function(context) {
                            var v = context.tick ? context.tick.value : 0;
                            return (v === 4 || v === 7 || v === 10) ? 2 : 1;
                        }
                    },
                    pointLabels: {
                        font: { family: 'DM Sans, sans-serif', size: 12, weight: '600' },
                        color: '#1E293B',
                        padding: 12,
                    },
                    angleLines: {
                        color: 'rgba(203, 213, 225, 0.5)',
                        lineWidth: 1,
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // We use custom legend
                },
                tooltip: {
                    backgroundColor: '#0D1B2A',
                    titleFont: { family: 'DM Sans, sans-serif', size: 13, weight: '600' },
                    bodyFont: { family: 'Inter, sans-serif', size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    borderColor: 'rgba(0,180,216,0.3)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            var val = context.raw;
                            var zone = val >= 8 ? '🟢 Green Zone' : val >= 5 ? '🟡 Yellow Zone' : val > 0 ? '🔴 Red Zone' : '—';
                            return ' ' + context.dataset.label + ': ' + val + '/10  (' + zone + ')';
                        }
                    }
                }
            },
            animation: {
                duration: 900,
                easing: 'easeInOutQuart'
            }
        }
    });
})();
</script>

<?php
echo $OUTPUT->footer();
?>
