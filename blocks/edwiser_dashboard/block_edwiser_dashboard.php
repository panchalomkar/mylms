<?php
defined('MOODLE_INTERNAL') || die();

class block_edwiser_dashboard extends block_base {

    // public function init(): void {
    //     $this->title = 'Dashboard Overview';
    //     global $PAGE;

    //     if (is_siteadmin()) {
    //         $PAGE->add_body_class('is-site-admin');
    //     }
    // }
public function init(): void {
    $this->title = 'Dashboard Overview';
    global $PAGE;

    // Only add class if user is admin AND on dashboard
    if (is_siteadmin() && $PAGE->pagelayout === 'mydashboard') {
        $PAGE->add_body_class('is-site-admin');
    }
}
    public function applicable_formats(): array {
        return [
            'my'   => true,
            'site' => true
        ];
    }

    public function get_content(): stdClass {
        global $CFG, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        require_once($CFG->dirroot . '/blocks/edwiser_dashboard/classes/helper.php');

        $data = \block_edwiser_dashboard\helper::get_dashboard_data();
// $block = new \local_edwiserreports\blocks\customreportsblock();
// $block->blockid = 1;

// $layout = $block->get_layout();

// $data['customreporthtml'] = $layout->blockview ?? '';

// $PAGE->requires->js_call_amd(
//     'local_edwiserreports/blocks/customreport',
//     'init',
//     [
//         $layout->id,
//         $layout->params
//     ]
// );


// // print_r($layout); // For debugging purposes
// $data['customreporthtml'] = $layout->blockview;

        // Encode arrays safely
        $data['viewdetailsurl'] = $CFG->wwwroot . '/local/edwiserreports/allcoursessummary.php';
        $data['viewdetailsurl1'] = $CFG->wwwroot . '/local/edwiserreports/activeusers.php';
        $data['siteoverview']['trendActiveUsers'] =
            json_encode($data['siteoverview']['trendActiveUsers'], JSON_NUMERIC_CHECK);
        $data['siteoverview']['trendEnrollments'] =
            json_encode($data['siteoverview']['trendEnrollments'], JSON_NUMERIC_CHECK);
        $data['siteoverview']['trendCompletions'] =
            json_encode($data['siteoverview']['trendCompletions'], JSON_NUMERIC_CHECK);
            $data['siteoverview']['trendDates'] =json_encode($data['siteoverview']['trendDates']);

$edwisersecret = (string) get_config('local_edwiserreports', 'secret');
$lang = (string) current_language();

$edwiserjs = [
    'secret' => 'gKwIKkmURJ',
    'lang'   => $lang
];

// in renderer.php or in your PHP output


// First, add CSS/JS links via heredoc
$this->content->text .= <<<HTML
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
HTML;

// Then safely inject EDWISER JS using json_encode
$this->content->text .= '<script>';
$this->content->text .= 'window.EDWISER = ' . json_encode($edwiserjs) . ';';
$this->content->text .= '</script>';

// Now you can append your custom JS in another heredoc
$this->content->text .= <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {

  var courseSelect = document.getElementById('cpCourse');
  var donutCanvas  = document.getElementById('progressDonut');
  var avgEl        = document.getElementById('cpAverage');
  var totalEl      = document.getElementById('cpTotal');
  var legendWrap   = document.getElementById('cpLegend');

  var progressChart = null;

  // ✅ SINGLE COLOR SOURCE (DONUT + LEGEND)
  var donutColors = [
    '#0f172a', // 0–20
    '#f97316', // 21–40
    '#3b82f6', // 41–60
    '#22c55e', // 61–80
    '#9333ea'  // 81–100
  ];

  /* ================= DONUT ================= */

  function renderDonut(values) {
    if (!donutCanvas || typeof Chart === 'undefined') return;

    if (progressChart) progressChart.destroy();

    progressChart = new Chart(donutCanvas, {
      type: 'doughnut',
      data: {
        labels: ['0–20%', '21–40%', '41–60%', '61–80%', '81–100%'],
        datasets: [{
          data: values,
          backgroundColor: donutColors
        }]
      },
      options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
      }
    });
  }

  /* ================= LEGEND ================= */

  function renderLegend(distribution) {
    if (!legendWrap) return;

    legendWrap.innerHTML = '';

    distribution.forEach(function(item, index) {
      legendWrap.insertAdjacentHTML(
        'beforeend',
        '<div style="justify-content: space-between;" class="cp-legend cursor-pointer mb-2 flex items-center gap-2 d-flex bg-[#f6f7f8] rounded-3 p-2" data-range="' + item.range + '">' +
          '<span style="background:' + donutColors[index] + ';width:12px;height:12px;border-radius:100%;display:inline-block"></span>' +
          '<strong>' + item.label + '</strong> (' + item.value + ')' +
        '</div>'
      );
    });

    document.querySelectorAll('.cp-legend').forEach(function(el) {
      el.addEventListener('click', function () {
        
      // loadModal(el.dataset.range);
      });
    });
  }

  /* ================= MODAL (EDWISER API) ================= */

  function loadModal(range) {
    if (!range || !courseSelect) return;

    var modal = document.getElementById('courseProgressModal');
    var tbody = document.getElementById('cpModalBodyCourse');
    var courseid = courseSelect.value;

    modal.classList.remove('hidden');

    tbody.innerHTML =
      '<tr><td colspan="3" class="text-center py-6">Loading...</td></tr>';

    var payload = {
      filter: { cohort: 0, course: parseInt(courseid), group: 0, dir: 'ltr' },
      range: range
    };

    var url =
      M.cfg.wwwroot +
      '/local/edwiserreports/request_handler.php' +
      '?action=get_courseprogress_modal_data' +
      '&secret=' + encodeURIComponent(window.EDWISER.secret) +
      '&lang=' + encodeURIComponent(window.EDWISER.lang) +
      '&data=' + encodeURIComponent(JSON.stringify(payload));

    fetch(url)
      .then(res => res.json())
      .then(json => {
        tbody.innerHTML = '';

        if (json.error || !json.rows?.length) {
          tbody.innerHTML =
            '<tr><td colspan="3" class="text-center py-6">No students found</td></tr>';
          return;
        }

        json.rows.forEach(row => {
          tbody.insertAdjacentHTML(
            'beforeend',
            '<tr>' +
              '<td>' + row[0] + '</td>' +
              '<td>' + row[1] + '</td>' +
              '<td>' + row[2] + '</td>' +
            '</tr>'
          );
        });
      })
      .catch(() => {
        tbody.innerHTML =
          '<tr><td colspan="3" class="text-center py-6 text-red-600">Error loading data</td></tr>';
      });
  }

  document.getElementById('cpModalCloseCourse')?.addEventListener('click', function() {
    document.getElementById('courseProgressModal').classList.add('hidden');
  });

  document.getElementById('courseProgressModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
  });

  /* ================= LOAD COURSE ================= */

  function loadCourseProgress(courseid) {
    fetch(
      M.cfg.wwwroot +
      '/blocks/edwiser_dashboard/ajax/courseprogress_summary.php?courseid=' + courseid
    )
    .then(res => res.json())
    .then(json => {
      if (!json || !json.distribution) return;

      avgEl.textContent = json.average + '%';
      totalEl.textContent = json.totallearners;

      renderDonut(json.distribution.map(d => d.value));
      renderLegend(json.distribution);
    });
  }

  /* ================= FIRST LOAD + CHANGE ================= */

  if (courseSelect) {
    // ✅ First page load → select first course
    if (courseSelect.options.length > 0) {
      courseSelect.selectedIndex = 0;
      loadCourseProgress(courseSelect.value);
    }

    // Change event
    courseSelect.addEventListener('change', function () {
      loadCourseProgress(courseSelect.value);
    });
  }

  /* ================= LINE CHART (UNCHANGED) ================= */

var chart = document.getElementById('overviewChart');

if (chart && typeof Chart !== 'undefined') {

 const rawDates = JSON.parse(chart.dataset.trenddates || '[]');

const labels = rawDates.map(d => {
  const date = new Date(d * 86400000); // ✅ days → ms
  return date.toLocaleDateString('en-IN', {
    day: 'numeric',
    month: 'short'
  });
});



  new Chart(chart.getContext('2d'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Active Users',
          data: JSON.parse(chart.dataset.trendactiveusers || '[]'),
          borderColor: '#0f172a',
          tension: 0.35
        },
        {
          label: 'Enrollments',
          data: JSON.parse(chart.dataset.trendenrollments || '[]'),
          borderColor: '#f97316',
          tension: 0.35
        },
        {
          label: 'Completions',
          data: JSON.parse(chart.dataset.trendcompletions || '[]'),
          borderColor: '#22c55e',
          tension: 0.35
        }
      ]
    },
    options: {
       responsive: true,
  maintainAspectRatio: false, 
      plugins: { legend: { position: 'bottom' } },
      scales: {
        y: {
  beginAtZero: true,
  suggestedMax: 4,   // ensures top is 5
  ticks: {
    stepSize: 1,     // 🔥 show 0,1,2,3,4,5
    precision: 0
  }
}

      }
    }
  });
}


});
</script>



HTML;

        /* ================= TEMPLATE ================= */
        $this->content->text .= $OUTPUT->render_from_template(
            'block_edwiser_dashboard/dashboard',
            $data
        );

        return $this->content;
    }
}
