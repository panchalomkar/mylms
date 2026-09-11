# AI-Based Skill Gap Analysis — Moodle Local Plugin

**Component:** `local_skillgap`  
**Version:** 3.0.0  
**Moodle:** 4.0+ (requires 2022041900)  
**PHP:** 8.1+

---

## Features

| Module | Description |
|---|---|
| Departments | Auto-synced from Moodle user profile `department` field |
| Designations | Manual or CSV-imported hierarchy per department |
| Competencies | 3-level hierarchy (main → sub → sub-sub), course mapping |
| Questions | Manual MCQ, CSV upload, or AI-generated (GPT-4o) — exactly 5 per competency |
| Assessments | Quarterly / Half-Yearly / Yearly, multi-competency |
| Adaptive reassessment | Subsequent attempts show only weak competencies |
| Competency scoring | 5/5 = Competent, 3–4 = Moderate, 0–2 = Needs Improvement |
| Auto course enrolment | Users auto-enrolled in mapped courses if not Competent |
| User dashboard | Spider/radar chart, progress score popup, promotion track |
| Manager dashboard | Team analytics, filters, drilldown, key insights |
| Team mapping | Uses custom profile field `repotingto` (value = manager username) |
| Competency Clustering | Group competencies, avg cluster score with Strong/Moderate/Weak status |
| Weak Zone Analysis | Flags competencies where >50% users are in red or avg <90% |
| Promotion Panel | Readiness-based promotion with admin action |
| Cron | Daily reassessment scheduler + Moodle message notifications |

---

## Installation

### 1. Copy plugin files

```bash
cp -r local_skillgap /path/to/moodle/local/
```

### 2. Run Moodle upgrade

Visit **Site Administration → Notifications** or run:

```bash
php admin/cli/upgrade.php --non-interactive
```

### 3. Configure OpenAI API key (for AI question generation)

**Site Administration → Plugins → Local plugins → Skill Gap Analysis → Settings**

Enter your OpenAI API key (GPT-4o is used for question generation).

### 4. Create the `repotingto` profile field

**Site Administration → Users → User profile fields → Create a new profile field**

- Field type: **Text input**
- Short name: `repotingto`  *(exactly — note the single 't')*
- Full name: Reporting To (Manager Username)

This field stores the manager's Moodle **username** for each user. The manager can then see their team in the Manager Dashboard.

### 5. Build AMD JavaScript (production)

```bash
cd /path/to/moodle
npm install
npx grunt amd --root=local/skillgap
```

> The plugin ships with a pre-built `amd/build/skillgap.min.js` — grunt rebuild is only required if you modify `amd/src/skillgap.js`.

### 6. Grant capabilities

| Role | Capabilities |
|---|---|
| Site admin / Manager | `local/skillgap:admin` |
| Line manager | `local/skillgap:managerviewteam` |
| All users | `local/skillgap:viewown`, `local/skillgap:takeassessment` |

---

## URL Reference

| URL | Description |
|---|---|
| `/local/skillgap/admin/index.php` | Admin dashboard |
| `/local/skillgap/admin/departments.php` | Manage departments |
| `/local/skillgap/admin/designations.php` | Manage designation hierarchy |
| `/local/skillgap/admin/competencies.php` | Manage competencies & course mapping |
| `/local/skillgap/admin/questions.php` | Manage questions (manual/CSV/AI) |
| `/local/skillgap/admin/assessments.php` | Create & manage assessments |
| `/local/skillgap/admin/clusters.php` | Competency clustering |
| `/local/skillgap/admin/promotion.php` | Promotion panel |
| `/local/skillgap/admin/weakzones.php` | Weak zone analysis |
| `/local/skillgap/user/dashboard.php` | User competency dashboard |
| `/local/skillgap/user/assessment.php?id=N` | Take a specific assessment |
| `/local/skillgap/manager/dashboard.php` | Manager team analytics |

---

## Scoring Rules

| Score (out of 5) | Status | Action |
|---|---|---|
| 5 | Competent | None |
| 3–4 | Moderate | Course recommendation |
| 0–2 | Needs Improvement | Auto-course enrolment |

**Progress Score** = Current attempt score − Previous attempt score  
Shown as `+1.5` or `−0.5` with ↑/↓ indicator. Displays "First Attempt" when no prior score exists.

**Cluster Score** = Average of all mapped competency scores  
≥ 80% = Strong | 60–79% = Moderate | < 60% = Weak

---

## Weak Zone Triggers

A competency is flagged as a weak zone when **either**:
- Average score < 90% of maximum, **or**
- More than 50% of users are in "Needs Improvement"

---

## File Structure

```
local/skillgap/
├── version.php
├── lib.php                          Core functions, hooks
├── settings.php                     Admin settings menu
├── db/
│   ├── install.xml                  15-table schema
│   ├── install.php                  Post-install hook
│   ├── access.php                   Capabilities
│   ├── tasks.php                    Cron tasks
│   ├── services.php                 Web services (AJAX)
│   └── messages.php                 Moodle message providers
├── lang/en/local_skillgap.php       Language strings
├── classes/
│   ├── output/renderer.php          Plugin renderer
│   ├── local/helper.php             Data builder for all pages
│   ├── local/data_validator.php     Centralised validation
│   ├── external/api.php             External API (AJAX endpoints)
│   └── task/
│       ├── reassessment_task.php    Daily cron — reassessment scheduling
│       └── notification_task.php    30-min cron — message processing
├── admin/                           Admin page controllers
├── user/                            User page controllers
├── manager/                         Manager page controllers
├── templates/                       Mustache templates (Bootstrap/RemUI)
└── amd/
    ├── src/skillgap.js              AMD source
    └── build/skillgap.min.js        Pre-built minified JS
```

---

## Compatibility

- ✅ Edwiser RemUI (all versions)  
- ✅ Moodle Boost  
- ✅ Moodle Classic  
- ✅ Mobile responsive (Bootstrap grid)  
- ✅ No custom sidebar or layout override  
- ✅ Uses `$OUTPUT->header()` / `$OUTPUT->footer()` standard layout  
