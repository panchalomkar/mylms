<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$functions = array(


    'quiz_attempt_wrong_questions' => [
        'classname' => local_mod_webservices\external\quiz_attempt_wrong_questions::class,
        'methodname' => 'quiz_attempt_wrong_questions',
        'classpath' => '',
        'description' => 'quiz_attempt_wrong_questions',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ],

    'get_clt_wrong_questions' => [
        'classname' => local_mod_webservices\external\get_clt_wrong_questions::class,
        'methodname' => 'get_clt_wrong_questions',
        'classpath' => '',
        'description' => 'get_clt_wrong_questions',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ],
);
