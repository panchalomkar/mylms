<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if ($hassiteconfig) {
    $ADMIN->add('location', new admin_category('venuemanangementname', get_string('venuemanangementname','local_venuemanangement')));
    $ADMIN->add('venuemanangementname', new admin_externalpage('addvenuemanangement', get_string('addvenuemanangement','local_venuemanangement'),
               $CFG->wwwroot . '/local/venuemanangement/addvenuemanangement.php', array('moodle/site:approvecourse')));  
    
    $ADMIN->add('venuemanangementname', new admin_externalpage('venuemanangementlist', get_string('venuemanangementlist','local_venuemanangement'),
               $CFG->wwwroot . '/local/venuemanangement/index.php', array('moodle/site:approvecourse')));
    $ADMIN->add('venuemanangementname', new admin_externalpage('venuemanangementcsv', get_string('venuemanangementcsv', 'local_venuemanangement'), "$CFG->wwwroot/local/venuemanangement/upload.php",array('moodle/site:approvecourse')));
}
    