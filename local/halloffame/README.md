# local_halloffame — Hall of Fame Plugin for Moodle 5.0+

A complete employee/learner recognition system. Admins post awards, users submit external certificates, everything is showcased in a responsive Hall of Fame gallery with AJAX filters and likes.

---

## Quick Start

```bash
# 1. Drop into Moodle
cp -r local_halloffame /path/to/moodle/local/

# 2. Upgrade database
php admin/cli/upgrade.php --non-interactive

# 3. Build AMD (optional — pre-built .min.js files are included)
php admin/cli/build_js.php --component=local_halloffame

# 4. Purge caches
php admin/cli/purge_caches.php
```

Then visit **Site Administration → Plugins → Local plugins → Hall of Fame** to configure, and assign capabilities to roles.

---

## File Structure

```
local/halloffame/
├── version.php               Plugin version metadata
├── lib.php                   Navigation hook, pluginfile server, helpers
├── settings.php              Admin settings page
├── styles.css                All UI styles
├── phpunit.xml               PHPUnit test config
│
├── db/
│   ├── install.xml           6 database tables
│   ├── install.php           Seed default categories & departments
│   ├── access.php            4 capabilities
│   ├── services.php          4 AJAX web services
│   ├── events.php            user_deleted observer
│   ├── tasks.php             Monthly cleanup task
│   └── upgrade.php           Future migration skeleton
│
├── classes/
│   ├── manager.php           All DB CRUD + business logic
│   ├── notification_helper.php  Email notifications (4 triggers)
│   ├── observer.php          Moodle event observer
│   ├── external/             4 external web-service classes
│   ├── output/               Renderer + renderable
│   ├── privacy/provider.php  Full GDPR privacy API
│   └── task/                 Scheduled task
│
├── pages/                    10 PHP page controllers
├── templates/                11 Mustache templates
├── amd/src/                  3 AMD JS modules (main, filters, likes)
├── amd/build/                Pre-built .min.js files
├── lang/en/                  114 language strings
├── pix/icon.svg              Plugin icon
└── tests/                    PHPUnit + Behat test suite
```

---

## Database Tables

| Table | Purpose |
|---|---|
| `halloffame_awards` | Admin-created recognition awards |
| `halloffame_achievements` | Admin-approved user achievements |
| `halloffame_submissions` | User-submitted certificates pending review |
| `halloffame_likes` | Per-user likes (unique index prevents duplicates) |
| `halloffame_categories` | Award category lookup (seeded with 6 defaults) |
| `halloffame_departments` | Department lookup (seeded with 7 defaults) |

---

## Capabilities

| Capability | Default roles | Purpose |
|---|---|---|
| `local/halloffame:view` | student, teacher, manager | View the Hall of Fame |
| `local/halloffame:submit` | student, teacher, manager | Submit external certificates |
| `local/halloffame:approve` | manager | Approve/reject submissions |
| `local/halloffame:manageawards` | manager | Create and manage awards |

---

## AJAX Web Services

| Function | Type | Description |
|---|---|---|
| `local_halloffame_get_awards` | read | Fetch awards with filters (month/quarter/year/dept/category) |
| `local_halloffame_get_achievements` | read | Fetch achievements with filters (type/year) |
| `local_halloffame_submit_certificate` | write | Submit certificate programmatically |
| `local_halloffame_like_item` | write | Toggle like — returns `{liked, count}` |

---

## Settings

Configure at **Site Admin → Plugins → Local plugins → Hall of Fame**:

- Enable/disable likes and user submissions
- Maximum upload file size (MB)
- Allowed file types (e.g. `pdf,jpg,png`)
- Award cards per row (1–4)
- Default sort order (newest / oldest / most liked)
- Email notifications (4 independent toggles)
- Navigation label and visibility

---

## Running Tests

```bash
# PHPUnit
vendor/bin/phpunit --configuration local/halloffame/phpunit.xml

# Behat (from Moodle root after behat init)
php admin/tool/behat/cli/run.php --tags=@local_halloffame
```

---

## AMD Build

```bash
# Using Moodle's built-in tool (no node required)
php admin/cli/build_js.php --component=local_halloffame

# Using grunt from Moodle root
grunt amd --root=local/halloffame
```

Pre-built `amd/build/*.min.js` files are included so the plugin works without a build step.

---

## URL Reference

| Page | URL |
|---|---|
| Hall of Fame (Awards) | `/local/halloffame/pages/index.php?tab=awards` |
| Hall of Fame (Achievements) | `/local/halloffame/pages/index.php?tab=achievements` |
| Submit certificate | `/local/halloffame/pages/submit.php` |
| My submissions | `/local/halloffame/pages/my_submissions.php` |
| Admin — create award | `/local/halloffame/pages/admin.php` |
| Admin — review queue | `/local/halloffame/pages/review.php` |
| Admin — manage categories | `/local/halloffame/pages/manage_categories.php` |
| Admin — manage departments | `/local/halloffame/pages/manage_departments.php` |

---

## License

GNU General Public License v3.0 — https://www.gnu.org/licenses/gpl-3.0.html
