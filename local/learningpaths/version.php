<?php
defined('MOODLE_INTERNAL') || die();

// Plugin version bumped: Moodle 5.0 compatibility + UI redesign release.
// No business logic changed - see /custom-ui/README.md for details of this update.
$plugin->version   = 2026071300;        // YYYYMMDDXX - this release.
$plugin->requires  = 2024100700;        // Minimum: Moodle 4.5 (safe floor; fully tested up to 5.0).
$plugin->component = 'local_learningpaths';
$plugin->maturity  = MATURITY_STABLE;
$plugin->cron      = 0;

// Explicitly declare the range of Moodle major versions this plugin supports.
// 405 = Moodle 4.5 (LTS), 500 = Moodle 5.0.
$plugin->supported = [405, 500];

// Release label shown on the plugin admin screen.
$plugin->release   = '2.0.0 (Moodle 5.0 compatible)';
