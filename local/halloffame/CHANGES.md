# Changelog — local_halloffame

## [1.0.1] — 2025-04-06 (current)

### Fixed
- `pages/admin.php` — `'PARAM_ALPHA'` string literal corrected to `PARAM_ALPHA` constant
- `pages/admin.php` — added `global $DB, $USER` declarations
- `pages/submit.php` — added `global $USER`; file itemid fixed from string to integer
- `pages/manage_categories.php` — added `global $DB`
- `pages/manage_departments.php` — added `global $DB`
- `pages/my_submissions.php` — added `global $DB, $USER`
- `lib.php` — `pluginfile()` corrected to use `array_shift($args)` for itemid (was hardcoded 0)
- `settings.php` — all labels now use `get_string()` with correct lang keys
- `lib.php` — respects `show_in_nav` config setting; uses plugin icon not core `i/trophy`
- `classes/manager.php` — removed unused `notify()` stub; added `validate_upload()` and `store_upload()` helpers; `get_awards()` now attaches gradient and monthname to each record
- `amd/src/filters.js` — switched from jQuery `.trigger()` to native `CustomEvent` for framework independence
- All 114 language strings validated against template usage; all cross-references confirmed clean

### Added
- `pages/manage_categories.php` — full CRUD for award categories
- `pages/manage_departments.php` — full CRUD for departments
- `pages/my_submissions.php` — user's own submission history with status pills
- `pages/delete_award.php` — secure award deletion action
- `templates/manage_categories.mustache`
- `templates/manage_departments.mustache`
- `templates/my_submissions.mustache`
- `classes/manager.php` — `save_department()` method
- Navigation header links to Categories, Departments, and My Submissions

## [1.0.0] — 2025-04-01

### Initial release
- Awards system (admin-created, gradient card grid, AJAX filters)
- Achievements Gallery (user-submitted certs, admin approval workflow)
- Likes with duplicate prevention (DB unique index + server-side toggle, optimistic UI)
- AJAX filter bar (month, quarter, year, department, category) with debounce
- Admin review queue (approve → published; reject → status updated)
- Email notifications (4 configurable triggers)
- GDPR privacy API (full export + delete)
- Monthly scheduled task (cleanup rejected submissions >90 days)
- 4 external web services, 4 capabilities, 6 DB tables
- PHPUnit test suite (15 manager tests + 8 external tests)
- Behat acceptance tests (11 scenarios)
- Pre-built AMD modules (no grunt required for installation)
