<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_skillgap_generate_ai_questions' => [
        'classname'   => '\local_skillgap\external\api',
        'methodname'  => 'generate_ai_questions',
        'description' => 'Generate AI questions for a competency',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/skillgap:admin',
    ],
    'local_skillgap_get_progress_detail' => [
        'classname'   => '\local_skillgap\external\api',
        'methodname'  => 'get_progress_detail',
        'description' => 'Get competency progress detail for popup',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/skillgap:viewown',
    ],
    'local_skillgap_get_team_scores' => [
        'classname'   => '\local_skillgap\external\api',
        'methodname'  => 'get_team_scores',
        'description' => 'Get team competency scores for manager',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/skillgap:managerviewteam',
    ],
    'local_skillgap_save_response' => [
        'classname'   => '\local_skillgap\external\api',
        'methodname'  => 'save_response',
        'description' => 'Save an assessment question response',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/skillgap:takeassessment',
    ],
];

$services = [
    'Skill Gap Analysis API' => [
        'functions'  => array_keys($functions),
        'restricted' => 0,
        'enabled'    => 1,
    ],
];
