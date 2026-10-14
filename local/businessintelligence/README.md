# Business Intelligence (local_businessintelligence)

Executive KPI ribbon + eight BI report cards with Organization → Department → Designation → Learner drill-down.
All filtering is AJAX (`local_businessintelligence_get_dashboard`, `local_businessintelligence_get_drilldown`) – no page reloads.

Install: copy this folder to `local/businessintelligence`, visit Site administration → Notifications.
Open: Site administration → Reports → Business Intelligence (`/local/businessintelligence/index.php`).
Capability: `local/businessintelligence:view` (managers + site admins).
Settings: Site administration → Plugins → Local plugins → Business Intelligence settings.

## Data sources
| KPI / report | Source |
|---|---|
| Total / active learners | user (not deleted/suspended, admins & guest excluded), logstore_standard_log |
| Completion rate, overdue, learning progress | user_enrolments (teachers excluded), course_completions, user_lastaccess; due = enrolment end or course end date |
| Learning paths | learningpaths, learningpath_courses (required + course_active), learningpath_users, learningpath_cohorts |
| Sessions, hours, attendance, no-shows, trainers | ILT (`<iltmodule>_sessions_dates`, `_signups`, `_signups_status`, `_session_roles`), zoom_meeting_details / zoom_meeting_participants, googlemeet_events |
| Competencies assessed, skill gap | local_skillgap_score (status competent / moderate / needs_improvement) |
| OJT ratings | core competency ratings (competency_usercomp) |
| AI role play | airoleplay_session (status = completed – every attempt counts) |

## Filters
Profile field short names (configurable): `department`, `designation`, `branchname`, `reportingmanager` (value = manager e-mail,
optional `reportingmanager:` prefix), `trainertype` (contains "guest"/"external" → guest trainer).
Competency filter = learners with a Skill Gap result for that competency.
Missing source plugins are detected at runtime and simply show 0.
