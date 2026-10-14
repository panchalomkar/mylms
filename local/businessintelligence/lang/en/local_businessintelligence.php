<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for local_businessintelligence.
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Business Intelligence';
$string['businessintelligence:view'] = 'View the Business Intelligence dashboard';
$string['cachedef_reportdata'] = 'Business Intelligence computed report data';
$string['privacy:metadata'] = 'The Business Intelligence plugin does not store any personal data. It only reads data held by other plugins.';
$string['settings'] = 'Business Intelligence settings';

// Page.
$string['dashboardtitle'] = 'Executive Dashboard';
$string['eyebrow'] = 'Business Intelligence';
$string['heading'] = 'Executive Dashboard';
$string['intro'] = 'Admin-only intelligence layer for {$a}. Ten executive KPIs followed by eight drill-down analytics reports covering the complete learning lifecycle.';
$string['exportcsv'] = 'CSV';
$string['exportpdf'] = 'PDF';
$string['moreinfo'] = 'More information';
$string['loading'] = 'Loading…';
$string['loaderror'] = 'The data could not be loaded. Please try again.';
$string['nodata'] = 'No data for the selected filters';
$string['updated'] = 'Updated {$a}';

// Filters.
$string['filters'] = 'Filters';
$string['filtersactive'] = '{$a} filters applied';
$string['resetfilters'] = 'Reset Filters';
$string['filter_range'] = 'Date range';
$string['filter_all_department'] = 'All Department';
$string['filter_all_designation'] = 'All Designation';
$string['filter_all_branch'] = 'All Branch';
$string['filter_all_manager'] = 'All Reporting Manager';
$string['filter_all_competency'] = 'All Competency';
$string['range_30d'] = 'Last 30 Days';
$string['range_3m'] = 'Last 3 Months';
$string['range_6m'] = 'Last 6 Months';
$string['range_12m'] = 'Last 12 Months';
$string['range_ytd'] = 'This Year';
$string['range_all'] = 'All Time';

// KPI ribbon.
$string['kpiribbon'] = 'Executive KPI Ribbon';
$string['kpiribbon_info'] = 'Ten headline indicators for the selected date range and learner filters. The percentage compares the selected period with the previous period of the same length; the sparkline shows the trend across the period.';
$string['kpi_totallearners'] = 'Total Learners';
$string['kpi_totallearners_info'] = 'Active (not suspended or deleted) learner accounts that match the filters. Trend: growth since the start of the period.';
$string['kpi_activelearners'] = 'Active Learners';
$string['kpi_activelearners_info'] = 'Learners with at least one recorded activity (log entry) during the selected period.';
$string['kpi_completionrate'] = 'Course Completion Rate';
$string['kpi_completionrate_info'] = 'Completed course assignments ÷ all course assignments made in the selected period (teachers excluded).';
$string['kpi_overduelearners'] = 'Overdue Learners';
$string['kpi_overduelearners_info'] = 'Learners with at least one assigned course past its due date (enrolment end or course end date) and not completed.';
$string['kpi_lpcompleted'] = 'Learning Paths Completed';
$string['kpi_lpcompleted_info'] = 'Learner × learning path completions: every required course in the path is completed. Source: Learning Paths plugin.';
$string['kpi_sessions'] = 'Training Sessions Conducted';
$string['kpi_sessions_info'] = 'Sessions held in the period across ILT face-to-face activities, Zoom meetings and Google Meet events.';
$string['kpi_traininghours'] = 'Total Training Hours';
$string['kpi_traininghours_info'] = 'Attended session duration summed across learners (ILT, Zoom, Google Meet attendance). Used for compliance hour reporting.';
$string['kpi_trainers'] = 'Trainers';
$string['kpi_trainers_info'] = 'Distinct instructors who delivered at least one session in the period: ILT session instructors, plus the instructors selected on Zoom and Google Meet activities (their "Select instructor" field).';
$string['kpi_competencies'] = 'Competencies Assessed';
$string['kpi_competencies_info'] = 'Competencies with at least one pass/fail assessment result in the Skill Gap plugin during the period.';
$string['kpi_roleplay'] = 'AI Role Play Attempts';
$string['kpi_roleplay_info'] = 'Total submitted AI role play attempts, counting every attempt (e.g. learner A 10 attempts + learner B 3 attempts = 13).';

// Reports section.
$string['bireports'] = 'Business Intelligence Reports';
$string['bireports_info'] = 'Each card summarises one area for the current filters. Use View Analysis to open the Organization → Department → Designation → Learner drill-down.';
$string['bireports_desc'] = 'Eight analytics reports. Each opens the full Organization → Department → Designation → Learner drill-down.';
$string['drilldownenabled'] = 'Drill-down enabled';
$string['viewanalysis'] = 'View Analysis';

$string['report_learning'] = 'Learning Progress Analytics';
$string['report_learning_desc'] = 'How assigned learning flows from assignment to completion, and where it stalls.';
$string['report_learning_info'] = 'Course assignments made in the period: how many were started and completed, and how many learners are overdue.';
$string['stat_learning_learners'] = 'Total Learners';
$string['stat_learning_assigned'] = 'Total Enrolments';
$string['stat_learning_completedlearners'] = 'Completed Learners';
$string['stat_learning_overduelearners'] = 'Overdue Learners';

$string['report_sessions'] = 'Training Sessions Analytics';
$string['report_sessions_desc'] = 'Participation across Face-to-Face and Online training.';
$string['report_sessions_info'] = 'Face-to-face = ILT sessions; Online = Zoom and Google Meet. Attendance % and no-shows use ILT attendance marking.';
$string['stat_sessions_total'] = 'Total Sessions';
$string['stat_sessions_attendance'] = 'Attendance %';
$string['stat_sessions_hours'] = 'Training Hours';
$string['stat_sessions_noshow'] = 'No-show Learners';

$string['report_learningpaths'] = 'Learning Path Analytics';
$string['report_learningpaths_desc'] = 'Learner progression through role-based learning journeys.';
$string['report_learningpaths_info'] = 'Status of every learner assigned to a learning path (directly or via cohort). Completed = all required courses completed.';
$string['stat_learningpaths_total'] = 'Total Learning Paths';
$string['stat_learningpaths_completed'] = 'Completed';
$string['stat_learningpaths_inprogress'] = 'In Progress';
$string['stat_learningpaths_notstarted'] = 'Not Started';

$string['report_trainer'] = 'Trainer Analytics';
$string['report_trainer_desc'] = 'Internal vs external trainer contribution and feedback quality.';
$string['report_trainer_info'] = 'Trainers are the instructors selected on ILT sessions and on Zoom / Google Meet activities. Guest trainers are identified by the trainer type profile field.';
$string['stat_trainer_inhouse'] = 'In-house Trainers';
$string['stat_trainer_guest'] = 'Guest Trainers';
$string['stat_trainer_hours'] = 'Hours Delivered';
$string['stat_trainer_sessions'] = 'Sessions Conducted';

$string['report_skillgap'] = 'Skill Gap Analytics';
$string['report_skillgap_desc'] = 'Competency coverage and where skill gaps exist.';
$string['report_skillgap_info'] = 'From Skill Gap assessments in the period. Weak = average score below the weak threshold; Need Improvement = learners with a "needs improvement" result.';
$string['stat_skillgap_total'] = 'Total Competencies';
$string['stat_skillgap_users'] = 'Users Assessed';
$string['stat_skillgap_weak'] = 'Weak Competencies';
$string['stat_skillgap_needimprovement'] = 'Need Improvement';

$string['report_ojt'] = 'OJT Competency Rating Analytics';
$string['report_ojt_desc'] = 'Which competencies have the most learners in the Red zone.';
$string['report_ojt_info'] = 'On-the-job competency ratings (Moodle competencies rated in the period). Green = proficient or ≥ green threshold, Yellow = ≥ weak threshold, Red = below.';
$string['stat_ojt_total'] = 'Total Competencies';
$string['stat_ojt_learners'] = 'Learners Assessed';
$string['stat_ojt_assessed'] = 'Comp. Assessed';
$string['stat_ojt_competent'] = 'Competent';

$string['report_roleplay'] = 'AI Role Play Analytics';
$string['report_roleplay_desc'] = 'Scenario → department → learner → attempt performance.';
$string['report_roleplay_info'] = 'Submitted AI role play attempts in the period. Chart: average score of the most practised scenarios.';
$string['stat_roleplay_scenarios'] = 'Scenarios';
$string['stat_roleplay_learners'] = 'Learners Participated';
$string['stat_roleplay_avgscore'] = 'Average Score';
$string['stat_roleplay_passrate'] = 'Pass Rate';

$string['report_outcome'] = 'Training Outcome Analytics';
$string['report_outcome_desc'] = 'Training → learning evidence → real-world performance.';
$string['report_outcome_info'] = 'Trained = completed a course or attended a session in the period. Outcome data = at least two results on the same competency/scenario; change = latest minus first score.';
$string['stat_outcome_trained'] = 'Learners Trained';
$string['stat_outcome_withdata'] = 'With Outcome Data';
$string['stat_outcome_avgchange'] = 'Avg Perf. Change';
$string['stat_outcome_positive'] = 'Positive Outcome';

// Chart labels.
$string['chart_overduetrend'] = 'Overdue learners trend';
$string['chart_attendance'] = 'Monthly attendance %';
$string['chart_trainertype'] = 'Sessions by trainer type';
$string['chart_avgrating'] = 'Avg rating';
$string['chart_weaklist'] = 'Top Weak Competencies · avg score · users in weak zone';
$string['funnel_assigned'] = 'Assigned';
$string['funnel_started'] = 'Started';
$string['funnel_completed'] = 'Completed';
$string['funnel_trained'] = 'Learners Trained';
$string['funnel_assessed'] = 'Assessment Completed';
$string['funnel_outcome'] = 'Outcome Data Available';
$string['funnel_positive'] = 'Positive Change';
$string['facetoface'] = 'Face-to-Face';
$string['online'] = 'Online';
$string['notstarted'] = 'Not Started';
$string['inprogress'] = 'In Progress';
$string['completed'] = 'Completed';
$string['inhouse'] = 'In-house';
$string['guest'] = 'Guest';
$string['zone_red'] = 'Red';
$string['zone_yellow'] = 'Yellow';
$string['zone_green'] = 'Green';
$string['avgscore'] = 'Avg score';
$string['value'] = 'value';
$string['users'] = 'users';
$string['outof5'] = '/ 5';
$string['norating'] = 'No ratings yet';

// Drill-down.
$string['drilldown'] = 'Drill-down';
$string['backtodashboard'] = 'Back to dashboard';
$string['level_org'] = 'Organization';
$string['level_department'] = 'Department';
$string['level_designation'] = 'Designation';
$string['level_learner'] = 'Learner';
$string['col_name'] = 'Name';
$string['col_learners'] = 'Learners';
$string['col_trainers'] = 'Trainers';
$string['col_active'] = 'With data';
$string['col_assigned'] = 'Assigned';
$string['col_started'] = 'Started';
$string['col_completed'] = 'Completed';
$string['col_completionrate'] = 'Completion %';
$string['col_overduecourses'] = 'Overdue courses';
$string['col_sessionsattended'] = 'Sessions attended';
$string['col_noshows'] = 'No-shows';
$string['col_attendance'] = 'Attendance %';
$string['col_traininghours'] = 'Training hours';
$string['col_pathsassigned'] = 'Paths assigned';
$string['col_inprogress'] = 'In progress';
$string['col_notstarted'] = 'Not started';
$string['col_sessionsdelivered'] = 'Sessions delivered';
$string['col_hoursdelivered'] = 'Hours delivered';
$string['col_learnerstrained'] = 'Learner seats';
$string['col_guesttrainers'] = 'Guest';
$string['col_compsassessed'] = 'Competencies assessed';
$string['col_avgscore'] = 'Avg score';
$string['col_needimprovement'] = 'Needs improvement';
$string['col_moderate'] = 'Moderate';
$string['col_competent'] = 'Competent';
$string['col_compsrated'] = 'Ratings';
$string['col_red'] = 'Red';
$string['col_yellow'] = 'Yellow';
$string['col_green'] = 'Green';
$string['col_greenpct'] = 'Green %';
$string['col_attempts'] = 'Attempts';
$string['col_scenarios'] = 'Scenarios';
$string['col_participated'] = 'Learners participated';
$string['col_passed'] = 'Passed';
$string['col_passrate'] = 'Pass rate';
$string['col_trained'] = 'Trained';
$string['col_assessed'] = 'Assessed';
$string['col_withoutcome'] = 'With outcome data';
$string['col_positive'] = 'Positive change';
$string['col_avgchange'] = 'Avg change';
$string['total'] = 'Total';
$string['notset'] = 'Not set';
$string['searchlearners'] = 'Search learners';
$string['truncated'] = 'Showing the first 1,000 learners. Narrow the filters to see more.';
$string['switchreport'] = 'Report';

// Settings.
$string['set_profilehdr'] = 'Custom profile fields';
$string['set_profilehdr_desc'] = 'Short names of the custom user profile fields used by the filters. The reporting manager field holds the manager\'s e-mail address (an optional "reportingmanager:" prefix is ignored).';
$string['set_field_department'] = 'Department field';
$string['set_field_designation'] = 'Designation field';
$string['set_field_branch'] = 'Branch field';
$string['set_field_manager'] = 'Reporting manager field';
$string['set_field_trainertype'] = 'Trainer type field';
$string['set_guestpattern'] = 'Guest trainer values';
$string['set_guestpattern_desc'] = 'Comma separated words. A trainer whose trainer type field contains one of them counts as a guest (external) trainer.';
$string['set_sessionhdr'] = 'Training sessions';
$string['set_sessionhdr_desc'] = 'Sources for sessions, attendance and trainers.';
$string['set_iltmodule'] = 'ILT activity module';
$string['set_iltmodule_desc'] = 'Module name of the face-to-face (ILT) activity. Its tables must follow the facetoface layout: <mod>_sessions_dates, <mod>_signups, <mod>_signups_status, <mod>_session_roles.';
$string['set_iltattended'] = 'ILT "fully attended" status codes';
$string['set_iltpartial'] = 'ILT "partially attended" status codes';
$string['set_iltnoshow'] = 'ILT "no show" status codes';
$string['set_trainerroles'] = 'Trainer roles';
$string['set_trainerroles_desc'] = 'Comma separated role short names in ILT session roles that count as trainers. Empty = every session role.';
$string['set_usezoom'] = 'Include Zoom meetings';
$string['set_gmeetmodule'] = 'Google Meet activity module';
$string['set_gmeetmodule_desc'] = 'Module whose <mod>_events table lists Google Meet sessions (default: googlemeet).';
$string['set_thresholdhdr'] = 'Thresholds';
$string['set_weakthreshold'] = 'Weak / red threshold (%)';
$string['set_greenthreshold'] = 'Green threshold (%)';
$string['set_cachettl'] = 'Cache lifetime';
$string['set_cachettl_desc'] = 'How long computed dashboard numbers are reused. 0 disables caching.';

// Instructors.
$string['set_iltinstructorfield'] = 'ILT instructor column';
$string['set_iltinstructorfield_desc'] = 'Column on the <mod>_sessions table holding the instructor user id(s) chosen in "Select instructor". Leave empty to auto-detect (instructor, instructors, trainer, teacherid…, or a <mod>_session_instructors table). Session roles are always included.';

// Learning Progress Analytics page.
$string['lp_intro'] = 'Course enrolment, completion velocity and overdue exposure across {$a}.';
$string['lp_lastrefreshed'] = 'Last refreshed';
$string['lp_kpi_totalusers'] = 'Total Users';
$string['lp_kpi_totalusers_info'] = 'Active learner accounts matching the filters. Trend: growth since the start of the period.';
$string['lp_kpi_assigned'] = 'Total Enrolments';
$string['lp_kpi_assigned_info'] = 'Course assignments (enrolments) made in the selected period.';
$string['lp_kpi_completed'] = 'Completed';
$string['lp_kpi_completed_info'] = 'Assigned courses that are completed.';
$string['lp_kpi_completionpct'] = 'Completion %';
$string['lp_kpi_completionpct_info'] = 'Completed ÷ assigned courses.';
$string['lp_kpi_overdueusers'] = 'Overdue Users';
$string['lp_kpi_overdueusers_info'] = 'Learners with at least one course past its due date and not completed.';
$string['lp_chart_status'] = 'Completion Status';
$string['lp_chart_status_info'] = 'Assigned courses split into Completed, Overdue (past due, not completed) and Pending (still within the due date).';
$string['lp_chart_funnel'] = 'Completion Funnel';
$string['lp_chart_funnel_info'] = 'Assigned → Started (course accessed) → Completed.';
$string['lp_chart_completiontrend'] = 'Monthly Completion Trend';
$string['lp_chart_completiontrend_info'] = 'Course completions recorded in each month.';
$string['lp_chart_overduetrend'] = 'Overdue Trend';
$string['lp_chart_overduetrend_info'] = 'Learners with at least one overdue course at the end of each month.';
$string['lp_status_completed'] = 'Completed';
$string['lp_status_overdue'] = 'Overdue';
$string['lp_status_pending'] = 'Pending';
$string['lp_completed_upper'] = 'COMPLETED';
$string['lp_st_completed'] = 'Completed';
$string['lp_st_overdue'] = 'Overdue';
$string['lp_st_inprogress'] = 'Not Completed';
$string['lp_st_notstarted'] = 'Not Started';
$string['lp_orgoverview'] = 'Organization Overview';
$string['lp_level'] = 'Level {$a->n} of 3 — {$a->name}';
$string['lp_level_dept'] = 'Department Report';
$string['lp_level_desig'] = 'Designation Report';
$string['lp_level_user'] = 'User Report';
$string['lp_backdept'] = 'Back to Department Report';
$string['lp_backdesig'] = 'Back to Designation Report';
$string['lp_bydept'] = 'Learning Progress Analytics — by Department';
$string['lp_bydesig'] = '{$a} — by Designation';
$string['lp_byusers'] = '{$a} — Users';
$string['lp_overview'] = '{$a} — Overview';
$string['lp_chart_level_info'] = 'Completion % of each group against the target: red below 40%, yellow 40–70%, green from 70%. The black marker is the goal. Users and courses of each group are shown on the right.';
$string['lp_chart_users_info'] = 'Completion % per learner against the target (top 15 by enrolments). The black marker is the goal.';
$string['lp_table_info'] = 'Click the orange course count to see the courses, or View More to drill down.';
$string['lp_table_users_info'] = 'Click View Detail Report to see every assigned course of a learner.';
$string['lp_users'] = 'Users';
$string['lp_courses'] = 'Courses';
$string['lp_enrolled'] = 'Enrolled';
$string['lp_completed'] = 'Completed';
$string['lp_col_department'] = 'Department';
$string['lp_col_designation'] = 'Designation';
$string['lp_col_users'] = 'Users';
$string['lp_col_users_info'] = 'Learners in this group.';
$string['lp_col_courses'] = 'Courses';
$string['lp_col_courses_info'] = 'Distinct courses assigned to the group. Click to see them.';
$string['lp_col_enrolments'] = 'Enrolments';
$string['lp_col_completed'] = 'Completed';
$string['lp_col_completed_info'] = 'Completed course assignments.';
$string['lp_col_pct'] = 'Completion %';
$string['lp_col_pct_info'] = 'Completed ÷ enrolments.';
$string['lp_col_overdueusers'] = 'Overdue Users';
$string['lp_col_action'] = 'Action';
$string['lp_col_user'] = 'User';
$string['lp_col_user_info'] = 'Learner name.';
$string['lp_col_email'] = 'Email';
$string['lp_col_email_info'] = 'Learner e-mail address.';
$string['lp_col_branch'] = 'Branch';
$string['lp_col_branch_info'] = 'Branch profile field.';
$string['lp_col_manager'] = 'Reporting Manager';
$string['lp_col_manager_info'] = 'Reporting manager profile field.';
$string['lp_col_enrolled'] = 'Enrolled';
$string['lp_col_enrolled_info'] = 'Courses assigned in the period.';
$string['lp_col_overdue'] = 'Overdue';
$string['lp_col_overdue_info'] = 'Assigned courses past their due date and not completed.';
$string['lp_viewmore'] = 'View More';
$string['lp_viewdetail'] = 'View Detail Report';
$string['lp_showing'] = 'Showing {$a->from}–{$a->to} of {$a->total} records';
$string['lp_allbranch'] = 'All — Branch';
$string['lp_allmanager'] = 'All — Reporting Manager';
$string['lp_coursespopup'] = '{$a} — Courses';
$string['lp_coursespopup_info'] = 'Courses assigned to this group in the period, with how many assignments are completed.';
$string['lp_col_course'] = 'Course';
$string['lp_col_coursename'] = 'Course Name';
$string['lp_col_assigned'] = 'Assigned';
$string['lp_userpopup'] = '{$a} — Learning Progress';
$string['lp_userpopup_desc'] = 'Every assigned course with completion and due dates.';
$string['lp_reportingmanager'] = 'Reporting Manager: {$a}';
$string['lp_col_completiondate'] = 'Completion Date';
$string['lp_col_duedate'] = 'Due Date';
$string['lp_col_status'] = 'Status';
$string['lp_col_status_info'] = 'Completed; Overdue = past due and not completed; Not Completed = started but not finished; Not Started = never opened.';
$string['lp_completionpct_upper'] = 'Completion %';
$string['lp_goal'] = 'Goal ({$a}%)';

// Training Sessions Analytics page.
$string['ts_intro'] = 'Face-to-Face and Online sessions, attendance discipline and training hours delivered.';
$string['ts_internal'] = 'Internal Training';
$string['ts_external'] = 'External Training';
$string['ts_kpi_sessions'] = 'Total Sessions';
$string['ts_kpi_sessions_info'] = 'ILT, Zoom and Google Meet sessions held in the period (with at least one learner from the filters).';
$string['ts_kpi_enrolled'] = 'Enrolled';
$string['ts_kpi_enrolled_info'] = 'Learner registrations for those sessions (ILT sign-ups and online participants).';
$string['ts_kpi_attendance'] = 'Attendance %';
$string['ts_kpi_attendance_info'] = 'Attended (present or partially attended) ÷ enrolled.';
$string['ts_kpi_hours'] = 'Training Hours';
$string['ts_kpi_hours_info'] = 'Attended session time summed across learners.';
$string['ts_kpi_noshows'] = 'No-shows';
$string['ts_kpi_noshows_info'] = 'Registrations marked as no-show in ILT attendance.';
$string['ts_chart_mix'] = 'Session Mix';
$string['ts_chart_mix_info'] = 'Face-to-Face (ILT) vs Online (Zoom, Google Meet) sessions.';
$string['ts_chart_attendancetrend'] = 'Attendance Trend';
$string['ts_chart_attendancetrend_info'] = 'Attendance % of the sessions held in each month.';
$string['ts_chart_hourstrend'] = 'Training Hours Trend';
$string['ts_chart_hourstrend_info'] = 'Attended training hours in each month.';
$string['ts_hours'] = 'Training Hours';
$string['ts_hrs'] = 'hrs';
$string['ts_bydept'] = 'Training Sessions Analytics — by Department';
$string['ts_chart_level_info'] = 'Attendance % of each group against the target: red below 40%, yellow 40–70%, green from 70%. The black marker is the goal. Users and total sessions of each group are shown on the right.';
$string['ts_chart_users_info'] = 'Attendance % per learner against the target (top 15 by enrolments). The black marker is the goal.';
$string['ts_table_info'] = 'Click the orange session count to see the sessions, or View More to drill down.';
$string['ts_table_users_info'] = 'Click View Detail Report to see every session of a learner.';
$string['ts_col_sessions'] = 'Total Sessions';
$string['ts_col_sessions_info'] = 'Sessions with at least one learner of the group. Click to see them.';
$string['ts_col_enrolled'] = 'Enrolled';
$string['ts_col_enrolled_info'] = 'Session registrations of the group.';
$string['ts_col_attended'] = 'Attended';
$string['ts_col_pct'] = 'Attendance %';
$string['ts_col_hours'] = 'Training Hours';
$string['ts_col_hours_short'] = 'Hours';
$string['ts_col_hours_info'] = 'Attended session hours.';
$string['ts_sessionspopup'] = '{$a} — Sessions';
$string['ts_sessionspopup_info'] = 'Sessions held in the period with learners from this group: registrations and how many were present.';
$string['ts_col_session'] = 'Session';
$string['ts_col_sessionname'] = 'Session Name';
$string['ts_col_trainer'] = 'Trainer';
$string['ts_col_trainer_info'] = 'Instructor selected on the ILT session or the Zoom / Google Meet activity.';
$string['ts_col_present'] = 'Present';
$string['ts_col_attstatus'] = 'Attendance Status';
$string['ts_col_sessstatus'] = 'Session Status';
$string['ts_col_duration'] = 'Duration';
$string['ts_userpopup'] = '{$a} — Training Sessions';
$string['ts_userpopup_desc'] = 'Every enrolled session with attendance and status.';
$string['ts_att_present'] = 'Present';
$string['ts_att_absent'] = 'Absent';
$string['ts_att_registered'] = 'Registered';
$string['ts_ss_completed'] = 'Completed';
$string['ts_ss_upcoming'] = 'Upcoming Session';
$string['ts_ss_ongoing'] = 'Ongoing Session';

// Learning Path Analytics page.
$string['lpa_intro'] = 'Role-based learning journeys and user progression from Not Started to Completed.';
$string['lpa_kpi_enrolled'] = 'Path Enrolments';
$string['lpa_kpi_enrolled_info'] = 'Learner assignments to learning paths (direct or via cohort). Trend: change since the start of the period.';
$string['lpa_kpi_completed'] = 'Completed';
$string['lpa_kpi_completed_info'] = 'Path assignments where every required course is completed.';
$string['lpa_kpi_inprogress'] = 'In Progress';
$string['lpa_kpi_inprogress_info'] = 'Path assignments with at least one course started but not every required course completed.';
$string['lpa_kpi_notstarted'] = 'Not Started';
$string['lpa_kpi_notstarted_info'] = 'Path assignments where no course of the path has been opened yet.';
$string['lpa_chart_funnel'] = 'Learning Path Funnel';
$string['lpa_chart_funnel_info'] = 'Path enrolments, how many were started and how many were completed.';
$string['lpa_chart_bypath'] = 'Started vs Completed by Path';
$string['lpa_chart_bypath_info'] = 'Completed, in progress and not started learners for the 10 largest learning paths.';
$string['lpa_funnel_enrolled'] = 'Enrolled';
$string['lpa_funnel_started'] = 'Started';
$string['lpa_funnel_completed'] = 'Completed';
$string['lpa_bydept'] = 'Learning Path Analytics — by Department';
$string['lpa_chart_level_info'] = 'Completion % of each group against the target: red below 40%, yellow 40–70%, green from 70%. The black marker is the goal. Users and learning paths of each group are shown on the right.';
$string['lpa_chart_users_info'] = 'Completion % per learner against the target (top 15 by path enrolments). The black marker is the goal.';
$string['lpa_table_info'] = 'Click the orange learning path count to see the paths, or View More to drill down.';
$string['lpa_table_users_info'] = 'Click View Detail Report to see every learning path of a learner.';
$string['lpa_col_users_info'] = 'Learners in this group.';
$string['lpa_col_paths'] = 'Learning Paths';
$string['lpa_col_paths_info'] = 'Distinct learning paths assigned to the group. Click to see them.';
$string['lpa_col_enrolled'] = 'Enrolled';
$string['lpa_col_enrolled_info'] = 'Learning path assignments.';
$string['lpa_col_completed'] = 'Completed';
$string['lpa_col_completed_info'] = 'Assignments with every required course completed.';
$string['lpa_col_inprogress'] = 'In Progress';
$string['lpa_col_inprogress_info'] = 'Assignments started but not completed.';
$string['lpa_col_notstarted'] = 'Not Started';
$string['lpa_col_notstarted_info'] = 'Assignments with no course opened yet.';
$string['lpa_col_pct'] = 'Completion %';
$string['lpa_col_pct_info'] = 'Completed ÷ enrolled learning paths.';
$string['lpa_pathspopup'] = '{$a} — Learning Paths';
$string['lpa_pathspopup_info'] = 'Learning paths assigned to this group with enrolled, in progress and completed learners.';
$string['lpa_col_path'] = 'Learning Path';
$string['lpa_userpopup'] = '{$a} — Learning Paths';
$string['lpa_userpopup_desc'] = 'Every learning path of the learner with progress and status.';
$string['lpa_col_pathname'] = 'Learning Path Name';
$string['lpa_col_coursescount'] = 'Courses Count';
$string['lpa_col_progress'] = 'Progress %';
$string['lpa_col_status_info'] = 'Current status of the record.';
$string['chart_alarming'] = 'Alarming';
$string['chart_moderate'] = 'Moderate';
$string['chart_ontarget'] = 'On Target';
