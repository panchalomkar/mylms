<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$capabilities = array(
    'local/venuemanangement:managevenue' => array(
        'captype' => 'write',
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'local/venuemanangement:viewvenuelist' => array(
        'captype' => 'write',
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
     'local/venuemanangement:uploadvenue' => array(
        'captype' => 'write',
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ) 
    
);