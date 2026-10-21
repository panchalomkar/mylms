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
 * External functions (AJAX).
 *
 * @package    local_businessintelligence
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_businessintelligence_get_dashboard' => [
        'classname'    => 'local_businessintelligence\external\get_dashboard',
        'description'  => 'Executive KPI ribbon and BI report card data for the given filters.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_drilldown' => [
        'classname'    => 'local_businessintelligence\external\get_drilldown',
        'description'  => 'Organization > Department > Designation > Learner drill-down for one report.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_learning' => [
        'classname'    => 'local_businessintelligence\external\get_learning',
        'description'  => 'Learning Progress Analytics page: overview, drill-down levels, course popup and learner detail.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_sessions_page' => [
        'classname'    => 'local_businessintelligence\\external\\get_sessions_page',
        'description'  => 'Training Sessions Analytics page: overview, drill-down levels, sessions popup and learner detail.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_lp_page' => [
        'classname'    => 'local_businessintelligence\\external\\get_lp_page',
        'description'  => 'Learning Path Analytics page: overview, drill-down levels, learning-path popup and learner detail.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_sg_page' => [
        'classname'    => 'local_businessintelligence\\external\\get_sg_page',
        'description'  => 'Skill Gap Analytics page: overview, drill-down levels, competency popups and learner detail.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
    'local_businessintelligence_get_analysis' => [
        'classname'    => 'local_businessintelligence\\external\\get_analysis',
        'description'  => 'Analysis pages: Trainer, AI Role-Play, Learning & Performance Intelligence and Competency Assessment.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/businessintelligence:view',
    ],
];
