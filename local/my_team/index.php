<?php
/**
 * My Team report - Tailwind card UI (flat list)
 *
 * @package   local_my_team
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/local/my_team/lib.php');

require_login();

global $PAGE, $OUTPUT, $USER, $CFG;

// Params
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 5, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);
$s = optional_param('s', null, PARAM_TEXT);
$userid = optional_param('uid', 0, PARAM_INT);
if ($userid == 0) {
    $userid = $USER->id;
}

// Context & capability check (keep your original access logic)
$context = context_system::instance();
$access = false;
if ($roles = get_user_roles($context, $USER->id)) {
    foreach ($roles as $role) {
        if ($role->shortname == 'manager') {
            $access = true;
            break;
        }
    }
}
// Optionally uncomment access check
// if (!($access || is_siteadmin())) {
//    print_error('Access denied');
// }

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/my_team/index.php', ['uid' => $userid]));
$pluginname = get_string("pluginname", "local_my_team");
$PAGE->set_title($pluginname);

// enqueue nothing special; we will output Tailwind links in the HTML below
echo $OUTPUT->header();

// Fetch data
$data = get_my_team_data($userid);

// Helper: safe count (function may return arrays or ints)
function safe_count($val) {
    if (is_array($val)) return count($val);
    if (is_int($val) || is_float($val)) return (int)$val;
    return 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>My Team Report</title>

<!-- Fonts & icons (same as your template) -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#3B82F6",
                    "background-light": "#F9FAFB",
                    "background-dark": "#111827",
                    "card-light": "#FFFFFF",
                    "card-dark": "#1F2937",
                    "text-light": "#1F2937",
                    "text-dark": "#F9FAFB",
                    "text-secondary-light": "#6B7280",
                    "text-secondary-dark": "#9CA3AF",
                    "border-light": "#E5E7EB",
                    "border-dark": "#374151",
                    "level-gold": "#FFD700",
                    "level-silver": "#C0C0C0",
                    "level-bronze": "#CD7F32",
                    "progress-enrolled": "#4F46E5",
                    "progress-not-started": "#9CA3AF",
                    "progress-in-progress": "#EF4444",
                    "progress-completed": "#22C55E"
                },
                fontFamily: {
                    display: ["Poppins", "sans-serif"],
                },
                borderRadius: {
                    DEFAULT: "0.5rem",
                },
            },
        },
    };
</script>

<style type="text/tailwindcss">
    .progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.5s ease-in-out;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-left: 0.5rem;
        text-shadow: 0px 1px 2px rgba(0,0,0,0.2);
        box-shadow: inset 0px 0px 5px rgba(255,255,255,0.03);
    }
    .badge.top-learner {
        background-color: #FFD700;
        color: #8B4513;
    }
    .badge.course-master {
        background-color: #C0C0C0;
        color: #4A4A4A;
    }

    /* modal backdrop */
    .modal-backdrop {
        background-color: rgba(0,0,0,0.45);
    }
   #page-local-my_team-index #page-header{display:none;}
   #page-local-my_team-index div[role="main"] {
    padding: 0px !important;
}
</style>

<!-- jQuery (used to keep existing AJAX endpoints compatible) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="min-h-screen">
<header class="bg-card-light dark:bg-card-dark shadow-sm top-0 z-10 border-b border-border-light dark:border-border-dark" style="z-index: 1">
  <div class="max-w-7xl mx-auto px- sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <h1 class="text-xl font-bold text-text-light dark:text-text-dark">My Team</h1>
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-8">
  <div class="mb-1">
    <a href="index.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg shadow-md hover:bg-blue-700 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
      <span class="material-icons mr-2 text-base">arrow_back</span> Back to My Team
    </a>
  </div>

  <div class="">
    <div class="flex flex-col md:flex-row justify-between items-center mb-2 pt-3 gap-4">
      <h2 class="text-2xl font-semibold text-text-light dark:text-text-dark">My Team Report</h2>
      <div class="relative w-full md:w-auto">
        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary-light dark:text-text-secondary-dark">search</span>
        <input id="teamSearch" class="w-full md:w-64  pr-4 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-text-light dark:text-text-dark" style="padding-left: 40px !important;" placeholder="Search team members..." type="text"/>
      </div>
    </div>

    <div id="teamCards" class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-3">
      <?php
      if (!empty($data)) {
          foreach ($data as $d) {
         $summary = get_user_course_summary($d->id);

$enrolled_count   = $summary['enrolled'];
$notstarted_count = $summary['notstarted'];
$inprogress_count = $summary['inprogress'];
$completed_count  = $summary['completed'];

$pct_notstarted = ($enrolled_count > 0) ? round(($notstarted_count / $enrolled_count) * 100) : 0;
$pct_inprogress = ($enrolled_count > 0) ? round(($inprogress_count / $enrolled_count) * 100) : 0;
$pct_completed  = ($enrolled_count > 0) ? round(($completed_count / $enrolled_count) * 100) : 0;


              // image
              $usercontext = context_user::instance($d->id);
              $imageurl = $CFG->wwwroot . '/pluginfile.php/' . $usercontext->id . '/user/icon/f3';
              // badges logic
              $show_top = ($enrolled_count > 0 && $completed_count >= ceil($enrolled_count / 2));
              $show_master = ($completed_count >= 1);
              // level display (simple heuristic)
              $level = 1;
              if ($completed_count >= 10) $level = 5;
              elseif ($completed_count >= 6) $level = 4;
              elseif ($completed_count >= 4) $level = 3;
              elseif ($completed_count >= 2) $level = 2;
              else $level = 1;

              // level color class
              $level_class = ($level >= 5) ? 'bg-level-gold' : (($level >= 3) ? 'bg-level-silver' : 'bg-level-bronze');
              ?>
              <div class="bg-background-light dark:bg-background-dark p-2 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 border border-border-light dark:border-border-dark relative overflow-hidden group team-card" data-name="<?php echo htmlspecialchars($d->firstname . ' ' . $d->lastname); ?>">
                
                <div class="flex flex-col sm:flex-row items-center gap-6 z-10 static">
                  <div class="relative flex-shrink-0">
                    <img alt="<?php echo htmlspecialchars(fullname($d)); ?>" class="h-20 w-20 rounded-full object-cover border-4 border-primary shadow-lg transform group-hover:scale-105 transition-transform duration-300" src="<?php echo $imageurl; ?>"/>
                    <div class="absolute -bottom-1 -right-1 <?php echo $level_class; ?> text-white text-xs font-bold px-3 py-1 rounded-full shadow-md border-2 border-white dark:border-card-dark transform rotate-6 group-hover:rotate-0 transition-transform duration-300">
                      Level <?php echo $level; ?>
                    </div>
                  </div>

                  <div class="flex-grow text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row justify-between items-center sm:items-start" style="align-items: center;">
                      <div class="flex items-center flex-wrap justify-center sm:justify-start">
                        <h3 class="text-2xl font-extrabold text-text-light dark:text-text-dark mr-2"><?php echo htmlspecialchars($d->firstname . ' ' . $d->lastname); ?></h3>
                        <?php if ($show_top): ?>
                          <span class="badge top-learner"><span class="material-symbols-outlined text-base mr-1">star</span>Top Learner</span>
                        <?php endif; ?>
                        <?php if ($show_master): ?>
                          <span class="badge course-master mt-2 sm:mt-0"><span class="material-symbols-outlined text-base mr-1">verified</span>Course Master</span>
                        <?php endif; ?>
                      </div>

                      <a class=" mt-4 sm:mt-0 text-sm font-medium text-white bg-primary hover:bg-blue-700 rounded-full px-6 py-2 shadow-lg transition-all duration-300 transform hover:scale-105" href="<?php echo new moodle_url('/local/my_team/index.php', ['uid' => $d->id]); ?>">View Team</a>
                    </div>

                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark text-left mt-1" style="    font-size: 12px;">
                      <span class="material-icons text-sm align-middle mr-1" style="    font-size: 12px;">email</span> <?php echo htmlspecialchars($d->email); ?>
                    </p>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark text-left mt-1" style="    font-size: 12px;">
                      <span class="material-icons text-sm align-middle mr-1" style="    font-size: 12px;">business</span> Department: <?php echo htmlspecialchars($d->department); ?>
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mt-1 pt-3 border-t border-border-light dark:border-border-dark">
                  <!-- Courses Enrolled -->
                  <div class="flex flex-col items-center text-center text-progress-enrolled">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                      <div class="progress-bar-fill bg-progress-enrolled" style="width:100%;"></div>
                    </div>
                    <p class="text-lg font-bold text-primary" style="cursor:pointer;line-height: normal;"><?php echo $enrolled_count; ?></p>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1" style="cursor:pointer;">Courses Enrolled</p>
                  </div>

                  <!-- Not Started -->
                  <div class="flex flex-col items-center text-center text-progress-not-started">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3  overflow-hidden">
                      <div class="progress-bar-fill bg-progress-not-started" style="width:<?php echo $pct_notstarted; ?>%;"></div>
                    </div>
                    <p class="text-lg font-bold text-muted" style="cursor:pointer;line-height: normal;"><?php echo $notstarted_count; ?></p>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1" style="cursor:pointer;">Not Started</p>
                  </div>

                  <!-- In-progress -->
                  <div class="flex flex-col items-center text-center text-progress-in-progress">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3  overflow-hidden">
                      <div class="progress-bar-fill bg-progress-in-progress" style="width:<?php echo $pct_inprogress; ?>%;"></div>
                    </div>
                    <p class="text-lg font-bold text-danger" style="cursor:pointer;line-height: normal;"><?php echo $inprogress_count; ?></p>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1" style="cursor:pointer;">In-progress</p>
                  </div>

                  <!-- Completed -->
                  <div class="flex flex-col items-center text-center text-progress-completed">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3  overflow-hidden">
                      <div class="progress-bar-fill bg-progress-completed" style="width:<?php echo $pct_completed; ?>%;"></div>
                    </div>
                    <p class="text-lg font-bold text-success"style="cursor:pointer;line-height: normal;"><?php echo $completed_count; ?></p>
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1" style="cursor:pointer;">Completed</p>
                  </div>
                </div>
              </div>
              <?php
          } // end foreach
      } else {
          echo '<p class="text-text-secondary-light">No team members found.</p>';
      }
      ?>
    </div>
  </div>
</main>
</div>

<!-- Modals (Tailwind style) -->
<!-- Enrolled Courses Modal -->
<div id="enrolledCoursesModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop px-4 py-6" style="z-index: 999999999999 !important;">
  <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl w-full max-w-3xl" role="dialog" aria-modal="true" style="overflow:hidden;">
    <div class="flex items-start justify-between border-b pb-2 p-3" style="background-color: #003152; color: #fff;">
      <h3 id="enrolledCoursesLabel" class="text-lg font-semibold text-light dark:text-text-dark">Enrolled Courses</h3>
      <button id="closeEnrolled" class="text-light hover:text-text-light">&times;</button>
    </div>
    <div id="enrolledCoursesBody" class="mt-4 text-text-secondary-light dark:text-text-secondary-dark p-3">
      <!-- AJAX content -->
    </div>
  </div>
</div>

<!-- Progress Modal -->
<div id="progressModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop px-4 py-6"style="z-index: 999999999999 !important;">
  <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl w-full max-w-4xl p-0" role="dialog" aria-modal="true" style="overflow:hidden;">
    <div class="flex items-start justify-between border-b pb-2 p-3" style="background-color: #003152; color: #fff;">
      <h3 class="text-lg font-semibold text-light dark:text-text-dark"><span id="modalCourseType">Courses</span> for <span id="modalUsername"></span></h3>
      <button id="closeProgressModal" class="text-light hover:text-text-light">&times;</button>
    </div>

    <div class=" p-3">
      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-amber-400">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Sr No</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Course Name</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Percentage Completed</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Total Activities</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Completed Activities</th>
            </tr>
          </thead>
          <tbody id="progressModalBody" class="p-3 bg-white dark:bg-card-dark text-sm text-text-secondary-light dark:text-text-secondary-dark">
            <!-- filled by JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Activity Modal -->
<div id="activityModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop px-4 py-6"style="z-index: 999999999999 !important;">
  <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl w-full max-w-4xl p-0" style="overflow:hidden;">
    <div class="flex items-start justify-between border-b pb-2 p-3" style="background-color: #003152; color: #fff;">
      <h3 id="activityModalTitle" class="text-lg font-semibold text-light dark:text-text-dark">Activity Details</h3>
      <button id="closeActivityModal" class="text-light hover:text-text-light">&times;</button>
    </div>

    <div class="p-3">
      <div class="text-right mb-2">
        <button id="downloadActivityCSV" class="hidden inline-flex items-center px-3 py-1 rounded bg-green-600 text-white text-sm">
          <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="currentColor"><path d="M5 20h14v-2H5v2zM9 4h6v6h4l-7 7-7-7h4z"/></svg>
          Download CSV
        </button>
      </div>

      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-amber-400">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Sr. No.</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Activity Name</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Activity Type</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-white">Status</th>
            </tr>
          </thead>
          <tbody id="activityModalBody" class="bg-white dark:bg-card-dark text-sm text-text-secondary-light dark:text-text-secondary-dark p-3">
            <!-- filled by JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {

  /* --------------------------------
   * SIMPLE SEARCH FILTER BY NAME
   * -------------------------------- */
  $('#teamSearch').on('input', function () {
    const q = $(this).val().toLowerCase().trim();
    $('.team-card').each(function () {
      const name = $(this).data('name').toLowerCase();
      $(this).toggle(!q || name.indexOf(q) !== -1);
    });
  });

  /* --------------------------------
   * MODAL HELPERS
   * -------------------------------- */
  function showModal(sel) {
    $(sel).removeClass('hidden').addClass('flex');
  }
  function hideModal(sel) {
    $(sel).removeClass('flex').addClass('hidden');
  }

  /* --------------------------------
   * OPEN **ENROLLED** MODAL
   * ONLY FOR: .text-progress-enrolled
   * -------------------------------- */
  $('#teamCards').on('click', '.text-progress-enrolled', function (e) {
    e.stopPropagation(); // prevent duplicate modal opening

    const card = $(this).closest('.team-card');
    const href = card.find('a[href*="uid="]').attr('href') || '';
    const params = new URLSearchParams(href.split('?')[1] || '');
    const userid = params.get('uid') || '';
    const username = card.data('name') || '';

    $.ajax({
      url: 'getdata_ajax.php',
      method: 'POST',
      data: { userid: userid },
      success: function (res) {
        $('#enrolledCoursesLabel').text('Enrolled Courses for ' + username);
        $('#enrolledCoursesBody').html(res);
        showModal('#enrolledCoursesModal');
      },
      error: function () {
        $('#enrolledCoursesBody').html('<p class="text-red-600">Error loading enrolled courses.</p>');
        showModal('#enrolledCoursesModal');
      }
    });
  });

  $('#closeEnrolled').on('click', function () {
    hideModal('#enrolledCoursesModal');
  });

  /* --------------------------------
   * OPEN PROGRESS MODAL
   * ONLY FOR:
   * .text-progress-not-started
   * .text-progress-in-progress
   * .text-progress-completed
   * -------------------------------- */
  $('#teamCards').on('click', '.text-progress-in-progress, .text-progress-completed, .text-progress-not-started', function (e) {

    // prevent enrolled modal from double firing
    if ($(this).hasClass('text-progress-enrolled')) return;

    e.stopPropagation();

    const card = $(this).closest('.team-card');
    const href = card.find('a[href*="uid="]').attr('href') || '';
    const params = new URLSearchParams(href.split('?')[1] || '');
    const userid = params.get('uid') || '';
    const username = card.data('name') || '';

    const isCompleted = $(this).hasClass('text-progress-completed');
    const isNotStarted = $(this).hasClass('text-progress-not-started');

    const label = isCompleted
      ? 'Completed Courses'
      : isNotStarted
      ? 'Not Started Courses'
      : 'In-Progress Courses';

    $('#modalCourseType').text(label);
    $('#modalUsername').text(username);

    const tbody = $('#progressModalBody');
    tbody.html(`<tr><td colspan="5" class="px-4 py-2">Loading...</td></tr>`);

    $.ajax({
      url: 'get_inprogress_courses_ajax.php',
      method: 'GET',
      data: { userid: userid },
      dataType: 'json',
      success: function (data) {
        tbody.empty();

        const filtered = data.filter(row => {
          const p = parseFloat(row.percentage) || 0;
          if (isCompleted) return p === 100;
          if (isNotStarted) return p === 0;
          return p > 0 && p < 100;
        });

        if (!filtered.length) {
          tbody.html(`<tr><td colspan="5" class="px-4 py-2">No ${label.toLowerCase()}.</td></tr>`);
        } else {
          filtered.forEach((row, i) => {
            const totalAct = (!isCompleted && !isNotStarted)
              ? `<a href="#" class="activity-link text-primary" 
                    data-courseid="${row.courseid}" 
                    data-userid="${userid}"
                    data-coursename="${row.coursename}">
                    ${row.totalactivities}
                 </a>`
              : row.totalactivities;

            tbody.append(`
              <tr>
                <td class="px-4 py-2">${i + 1}</td>
                <td class="px-4 py-2">${row.coursename}</td>
                <td class="px-4 py-2">${row.percentage}%</td>
                <td class="px-4 py-2">${totalAct}</td>
                <td class="px-4 py-2">${row.completedactivities}</td>
              </tr>
            `);
          });
        }

        showModal('#progressModal');
      },
      error: function () {
        tbody.html(`<tr><td colspan="5" class="px-4 py-2 text-red-600">Error loading data.</td></tr>`);
        showModal('#progressModal');
      }
    });
  });

  $('#closeProgressModal').on('click', function () {
    hideModal('#progressModal');
  });

  /* --------------------------------
   * ACTIVITY MODAL INSIDE PROGRESS MODAL
   * -------------------------------- */
  $('#progressModalBody').on('click', '.activity-link', function (e) {
    e.preventDefault();

    const courseid = $(this).data('courseid');
    const userid = $(this).data('userid');
    const coursename = $(this).data('coursename');

    $('#activityModalTitle').text(`Activities in ${coursename}`);

    const tbody = $('#activityModalBody');
    tbody.html('<tr><td colspan="4" class="px-4 py-2">Loading...</td></tr>');

    let exportData = [];
    let first = '', last = '', cname = '';

    $.ajax({
      url: 'get_course_activities_ajax.php',
      method: 'GET',
      data: { courseid, userid },
      dataType: 'json',
      success: function (data) {
        exportData = data || [];
        tbody.empty();

        if (!exportData.length) {
          tbody.html('<tr><td colspan="4" class="px-4 py-2">No activities found.</td></tr>');
        } else {
          exportData.forEach((act, i) => {
            tbody.append(`
              <tr>
                <td class="px-4 py-2">${i + 1}</td>
                <td class="px-4 py-2">${act.activityname}</td>
                <td class="px-4 py-2">${act.moduletype}</td>
                <td class="px-4 py-2">${act.status}</td>
              </tr>
            `);
          });
        }

        $.ajax({
          url: 'get_user_course_info.php',
          method: 'GET',
          data: { userid, courseid },
          dataType: 'json',
          success: function (info) {
            first = info.firstname || '';
            last = info.lastname || '';
            cname = info.fullname || coursename;

            $('#downloadActivityCSV').removeClass('hidden');
            showModal('#activityModal');
          },
          error: function () {
            $('#downloadActivityCSV').addClass('hidden');
            showModal('#activityModal');
          }
        });
      },
      error: function () {
        tbody.html('<tr><td colspan="4" class="px-4 py-2 text-red-600">Error loading activities.</td></tr>');
        showModal('#activityModal');
      }
    });

    $('#downloadActivityCSV')
      .off('click')
      .on('click', function () {
        if (!exportData.length) return;

        let csv = 'First Name,Last Name,Course Name,Activity Name,Activity Type,Status\n';
        exportData.forEach(item => {
          const div = document.createElement('div');
          div.innerHTML = item.status || '';
          const clean = div.textContent;

          csv += `"${first}","${last}","${cname}","${item.activityname}","${item.moduletype}","${clean}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `activity_report_${first}_${cname}.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
      });
  });

  $('#closeActivityModal').on('click', function () {
    hideModal('#activityModal');
  });

  /* CLOSE WHEN CLICK OUTSIDE MODAL */
  $('.modal-backdrop').on('click', function (e) {
    if (e.target === this) hideModal('#' + $(this).attr('id'));
  });

});

</script>

</body>
</html>

<?php
echo $OUTPUT->footer();
