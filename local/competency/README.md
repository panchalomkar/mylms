# Local Competency Plugin — Professional Edition
**Version:** 2026.03.26 | **Moodle:** 4.1+ and 5.0+

## Overview
On-the-job competency rating plugin for Moodle. Companies can upload main competencies, map sub and sub-sub competencies per designation/department/role. Managers and employees rate competencies; L&D gives final approval with zone-based scoring.

## Rating Zones
| Zone | Score Range | Meaning |
|------|-------------|---------|
| 🟢 Green | 8–10 | Strong Performer |
| 🟡 Yellow | 5–7 | Developing |
| 🔴 Red | 1–4 | Needs Improvement |

## New in This Version

### ✨ Professional Corporate UI
- Deep navy + electric teal enterprise design system
- Compatible with **Moodle 4.1** and **Moodle 5.0+**
- Google Fonts (DM Sans + Inter) for premium typography
- Animated rating inputs with real-time zone color feedback
- Professional modal dialogs, styled navigation tabs, enhanced tables
- Responsive for desktop and mobile

### 🕸️ Competency Spider Diagram (NEW FEATURE)
New page: `spiderdiagram.php`

- **Users** see their own radar/spider chart comparing self-rating vs manager rating vs L&D final rating
- **Managers** can filter by department and select any employee under their purview to view their spider diagram
- **L&D Team** can filter by department and view any employee's spider diagram
- Zone boundary lines (4, 7, 10) shown on the radar grid
- Competency breakdown table with zone badges
- Overall score with zone classification shown prominently

## Installation
1. Upload the `competency` folder to `/path/to/moodle/local/`
2. Visit Site Administration → Notifications to trigger the upgrade
3. Assign capabilities to roles as needed

## Files Changed / Added
- `tabs.php` — Complete UI overhaul, Spider Diagram tab added
- `header.php` — Cleaned up, CSS now in tabs.php
- `custom.css` — Refreshed professional palette
- `customtablelayout.css` — Refreshed dropdown and table styles
- `styles.css` — Refreshed main styles
- `competency_pro.css` — NEW: Full design system CSS with CSS variables
- `spiderdiagram.php` — NEW: Spider/radar chart feature
- `version.php` — Updated to 2026032601, requires Moodle 4.1+
- `db/access.php` — Added `viewspiderdiagram` capability
- `lang/en/local_competency.php` — Added spider diagram strings
- All rating pages — Dynamic color feedback on rating inputs
- Report pages — Spider diagram quick-access buttons added
