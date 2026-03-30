<?php
// This file is part of Moodle - http://moodle.org/
require_once('../../config.php');
require_once($CFG->dirroot . '/local/mt_dashboard/lib.php');
require_once($CFG->dirroot . '/local/mt_dashboard/menu.php');
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/local/tenant_control/locallib.php');

require_login();

global $USER, $CFG, $DB, $OUTPUT, $SESSION;

/* =======================
 * Parameters
 * ======================= */
$edit                     = optional_param('edit', null, PARAM_BOOL);
$company                  = optional_param('company', 0, PARAM_INT);
$companyss                = optional_param('companyss', 0, PARAM_INT);
$showsuspendedcompanies   = optional_param('showsuspendedcompanies', 0, PARAM_INT);
$noticeok                 = optional_param('noticeok', '', PARAM_CLEAN);
$noticefail               = optional_param('noticefail', '', PARAM_CLEAN);
$selectedtab              = optional_param('tabid', 0, PARAM_INT);
$page                     = optional_param('page', 0, PARAM_INT);
$search                   = optional_param('search-mt', '', PARAM_TEXT);

$perpage = 11;
$systemcontext = context_system::instance();

/* =======================
 * Session handling
 * ======================= */
$SESSION->showsuspendedcompanies = $showsuspendedcompanies;

if (!empty($company) &&
    (iomad::has_capability('block/iomad_company_admin:company_add', $systemcontext)
    || $DB->record_exists('company_users', [
        'managertype' => 1,
        'companyid'   => $company,
        'userid'      => $USER->id
    ]))
) {
    $SESSION->currenteditingcompany = $company;
}

/* =======================
 * Page setup
 * ======================= */
$linkurl  = new moodle_url('/local/iomad_dashboard/index.php');
$linktext = get_string('name', 'local_mt_dashboard');

$PAGE->set_context($systemcontext);
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);
$PAGE->set_pagetype('local-iomad-dashboard-index');
$PAGE->navbar->add(get_string('pluginname', 'local_mt_dashboard'), '/local/mt_dashboard/index.php');

$PAGE->requires->css('/local/mt_dashboard/styles.css');
$PAGE->blocks->add_region('content');
$PAGE->requires->js_init_call('M.local_iomad_dashboard.init');

/* External CDN (kept as requested) */
$PAGE->requires->js(new moodle_url('https://cdn.tailwindcss.com'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/icon?family=Material+Icons'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined'));
$PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded'));

/* =======================
 * Header
 * ======================= */
echo $OUTPUT->header();

/* =======================
 * Template data (IMPORTANT)
 * ======================= */
$data = [];

/* Notices */
if ($noticeok) {
    $data['noticeok'] = true;
    $data['noticeok_message'] = $noticeok;
}
if ($noticefail) {
    $data['noticefail'] = true;
    $data['noticefail_message'] = $noticefail;
}

/* Admin buttons */
if (is_siteadmin()) {
    $data['is_admin'] = true;

    if (has_capability('local/report_companies:view', $systemcontext)) {
        $data['report_companies'] = true;
        $data['report_companies_url'] = new moodle_url('/local/report_companies/index.php');
    }

    $data['edit_companies_url'] = new moodle_url('/blocks/iomad_company_admin/editcompanies.php');
    $data['company_edit_form']  = new moodle_url('/blocks/iomad_company_admin/company_edit_form.php?createnew=1');
}

/* =======================
 * Selected company
 * ======================= */
if (!empty($SESSION->currenteditingcompany)) {
    $selectedcompany = $SESSION->currenteditingcompany;
} else if (!empty($USER->profile->company)) {
    $usercompany = company::by_userid($USER->id);
    $selectedcompany = $usercompany->id;
} else {
    $selectedcompany = 0;
}

/* =======================
 * Company listing
 * ======================= */
$companylist = get_all_companies(
    $showsuspendedcompanies,
    $page * $perpage,
    $perpage,
    $search
);

$companydata = [];
foreach ($companylist as $id => $name) {
    //   if ($id == 1) {
    //     continue;
    // }
    $users   = $DB->count_records('company_users', ['companyid' => $id]);
    $courses = $DB->count_records('company_course', ['companyid' => $id]);
    $companyrecord = $DB->get_record('company', ['id' => $id]);

  $completionrate = get_company_completion_rate($id);
$logo_url = get_tenant_logo_url($id);
$companydata[] = [
    'logo_url'     => $logo_url,
    'cname'        => $name,
    'shortname'    => strtoupper(substr($name, 0, 2)),
    'status'       => $companyrecord->suspended ? 'suspended' : 'active',
    'active'       => !$companyrecord->suspended,
    'suspended'    => $companyrecord->suspended,
    'description'  => $companyrecord->description ?? '',
    'users'        => $users,
    'courses'      => $courses,
    'completion'   => $completionrate,
    'company_url'  => new moodle_url('/local/mt_dashboard/edit.php', ['company' => $id])
];

}

$data['companylisting'] = $companydata;

/* Filters */
$data['is_all']       = ($showsuspendedcompanies == 2);
$data['is_active']    = ($showsuspendedcompanies == 0);
$data['is_suspended'] = ($showsuspendedcompanies == 1);
$data['search']       = $search;
$data['action']       = $PAGE->url;

/* Pagination */
$total_count = get_tenant_count($showsuspendedcompanies);
if ($total_count > $perpage) {
    $url = new moodle_url('/local/mt_dashboard/index.php');
    $url->param('showsuspendedcompanies', $showsuspendedcompanies);
    $pagination = new paging_bar($total_count, $page, $perpage, $url, 'page');
    $data['pagination_data'] = $OUTPUT->render($pagination);
}

/* =======================
 * Render template
 * ======================= */
echo $OUTPUT->render_from_template('local_mt_dashboard/mt_dashboard', $data);

/* JS */
$PAGE->requires->js_call_amd('local_mt_dashboard/formsubmit', 'init');

echo $OUTPUT->footer();

?>
<!-- Tailwind CSS CDN -->
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: '#003152',
          secondary: '#ec9707'
        }
      }
    }
  }
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('form button');
    const checkbox = document.querySelector('input[name="showsuspendedcompanies"]');

filterButtons.forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        const status = btn.textContent.trim().toLowerCase();
        const url = new URL(window.location.href);

        if (status === 'all companies') url.searchParams.set('showsuspendedcompanies', '2');
        else if (status === 'active') url.searchParams.set('showsuspendedcompanies', '0');
        else if (status === 'suspended') url.searchParams.set('showsuspendedcompanies', '1');

        window.location.href = url.toString();
    });
});


    // Handle checkbox to show/hide suspended companies dynamically
    if (checkbox) {
        checkbox.addEventListener('change', () => {
            const url = new URL(window.location.href);

            // Remove search term if needed
            url.searchParams.delete('search-mt');

            // Update showsuspendedcompanies based on checkbox
            url.searchParams.set('showsuspendedcompanies', checkbox.checked ? '1' : '0');
            window.location.href = url.toString();
        });
    }
});
</script>


