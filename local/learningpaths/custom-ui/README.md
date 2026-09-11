# local_learningpaths — Moodle 5.0 Compatibility + UI Redesign

This update does **not** change any business logic, database schema, capability
checks, form processing, or AJAX/webservice behaviour of the plugin. It is
strictly:

1. A small set of Moodle 5.0 compatibility fixes (markup-attribute level only).
2. A new, self‑contained visual redesign layer in this `custom-ui/` folder
   (plain CSS + plain vanilla JavaScript, no jQuery, no build step).

Everything the plugin already did — saving, deleting, enrolling, notifications,
cohorts, forms, webservices — behaves exactly as before.

---

## 1. What changed for Moodle 5.0 compatibility

Moodle 5.0 (released April 2025) migrated core/Boost to **Bootstrap 5**. Bootstrap
5 renamed its JS trigger attributes (e.g. `data-toggle` → `data-bs-toggle`,
`data-target` → `data-bs-target`, `data-dismiss` → `data-bs-dismiss`). Moodle
ships a temporary `theme_boost/bs4-compat` shim that silently translates the old
attributes until Moodle 6.0, so the plugin technically still worked — but to be
**natively** compatible (and not depend on a shim that will be removed later),
this update adds the modern `data-bs-*` attributes **alongside** the original
ones everywhere a Bootstrap modal, tab, or popover trigger is used:

| File | Attributes added |
|---|---|
| `templates/indexpage.mustache` | `data-bs-toggle`, `data-bs-target`, `data-bs-backdrop`, `data-bs-keyboard` (video popup) |
| `templates/add_learningpath_popup.mustache` | modal trigger + close button |
| `templates/learningpath_popup_standart.mustache` | close button (used by cohorts/users/courses/prerequisites popups) |
| `templates/learningpath_cohorts_tab.mustache` | modal trigger |
| `templates/learningpath_courses_tab.mustache` | modal trigger |
| `templates/learningpath_users_tab.mustache` | modal trigger |
| `templates/learningpath_view_tab.mustache` | tab trigger |
| `templates/navigation_tabs.mustache` | tab trigger |
| `renderer.php` | prerequisites modal trigger attrs (also removed a harmless duplicate array key) |
| `classes/forms/LearningPathForm.php` | start/end date help popover attrs |

**Nothing was renamed or removed** — the original `data-toggle`/`data-target`/
`data-dismiss` attributes are still present, so the plugin remains backward
compatible with Bootstrap‑4‑era Moodle (4.x) at the same time.

Other checks performed (no changes required):
* No PHP4-style constructors, `create_function()`, `each()`, or other
  functions removed in PHP 8.2+ were found anywhere in the codebase.
* No YUI usage was found.
* `version.php` was updated: `$plugin->requires` set to the Moodle 4.5 floor,
  `$plugin->supported = [405, 500]` explicitly declares tested compatibility
  with Moodle 4.5 and 5.0, and the version number was bumped so Moodle
  detects and offers the upgrade.

If you later also need Moodle 5.1/5.2 (which relocates code under `/public`
and removes the BS4 shim in 6.0), no further markup changes are required —
this update already uses native `data-bs-*` attributes.

---

## 2. UI Redesign — `custom-ui/`

A brand-new, isolated folder (does **not** touch `/css`, `/js`, or `/amd`):

```
custom-ui/
├── css/learningpaths-redesign.css   ← new visual theme (plain CSS)
├── js/learningpaths-enhance.js      ← new plain JavaScript (no jQuery)
└── README.md                        ← this file
```

Both files are loaded **in addition to** the plugin's existing assets (see the
two extra lines added at the bottom of the `$PAGE->requires->...` blocks in
`index.php`, `view.php`, and `edit.php`). Nothing already loaded was removed.

### Color system
```
--lp-primary : #003152   (deep navy — headers, primary buttons, links)
--lp-accent  : #ec9707   (amber — highlights, active states, CTAs, hover)
```
Full tonal ramps (light/dark variants) are generated from these two colors in
`:root`-style custom properties at the top of `learningpaths-redesign.css`, so
you can re-brand the whole plugin by editing two lines.

### What the redesign includes
* **Icons** — a small inline SVG icon set (route, users, cohort, book, warning,
  play, check) is injected into modal headers depending on which modal is
  opening (cohorts / users / courses / prerequisites / create / video),
  plus re-colored Font Awesome glyphs already used across the plugin.
* **Animation**
  - Learning path rows fade/slide in with a staggered delay.
  - Buttons and action icons get a subtle lift + ripple on click.
  - Modals zoom/slide in with a spring easing curve instead of the flat
    Bootstrap fade.
  - Tabs get an animated underline indicator.
  - A small spinner + busy-state overlay class (`.lp-modal-busy`) is available
    for any future async modal content.
* **"Perfected" modals** (`#largeModal`, all `learningpath_popup_standart`
  instances — cohorts/users/courses/prerequisites, the video popup, and the
  dynamically generated `bootbox` delete-confirmation dialogs):
  - Rounded corners, elevated shadow, blurred backdrop.
  - Gradient navy header with a themed icon + a circular amber close button
    that rotates on hover.
  - Sticky, clearly separated footer with full-width-friendly action buttons.
  - Custom slim scrollbar on long modal bodies.
  - Auto-focus moves to the first field when a modal opens (accessibility).
  - Fully responsive (near full-screen with tighter margins on mobile).
* **Everything else** — search box, tables, badges, pagination, popovers, and
  a small toast helper (`window.lpToast(message, 'success'|'danger')`) for
  optional future use.

### How it works technically
`learningpaths-enhance.js` is a single IIFE, plain JavaScript:
1. Adds `class="lp-ui-v2"` to `<html>` so every redesign CSS rule (all scoped
   under `html.lp-ui-v2 …`) activates. Removing that one class (or simply not
   loading the two new files) fully reverts to the original look with zero
   side effects, since no original markup, class, or id was deleted.
2. Uses a `MutationObserver` to detect when a modal gets the Bootstrap
   `show` class (however it was opened — jQuery `.modal('show')`, native
   Bootstrap 5 `Modal.show()`, or a dynamically created `bootbox` dialog) and
   layers the icon/animation/focus polish on top. It never calls `.modal()`
   itself and never intercepts the original click handlers, so all existing
   trigger logic (AMD `learningpaths.js`, `save_botton.js`, `bootbox.js`)
   keeps working unmodified.
3. Adds a lightweight ripple element and staggered animation delays — pure
   presentation, no state changes.

### Turning the redesign off
Comment out (or remove) these two lines from `index.php`, `view.php`, and
`edit.php`:
```php
$PAGE->requires->css(new moodle_url("/local/learningpaths/custom-ui/css/learningpaths-redesign.css"));
$PAGE->requires->js(new moodle_url("/local/learningpaths/custom-ui/js/learningpaths-enhance.js"), true);
```
The plugin will render exactly as it did before this update.

---

## 3. Install / Upgrade

1. Replace your existing `local/learningpaths` folder with this one (or copy
   just the changed files listed above plus the new `custom-ui/` folder).
2. Purge caches (`Site administration ▸ Development ▸ Purge caches`), since
   Mustache templates changed.
3. Visit *Site administration ▸ Notifications* to let Moodle pick up the new
   plugin version.
