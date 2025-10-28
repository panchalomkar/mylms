<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
//Globalized required vars
global $CFG, $OUTPUT, $PAGE, $DB;

require_login();

$PAGE->set_title(get_string('pluginname', 'local_properties'));
$PAGE->set_heading(get_string('pluginname', 'local_properties'));

echo $OUTPUT -> header();
require_once('userprofile.php');

echo html_writer::start_tag('div');
    echo html_writer::start_tag('div', array('id' => 'learning-paths-container', 'class' => 'mar-no'));
        echo html_writer::start_tag('div');
				/* Panel heading */
            echo html_writer::start_div('div');
                echo html_writer::start_tag('div', array('class' => 'tab-base mar-no'));
                    echo html_writer::start_tag('ul',array('class'=>'nav nav-tabs tbs', 'role'=>'tablist'));
                		    
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>'active'));
                            echo html_writer::tag('a',get_string('userprop', 'local_properties'),array('href'=>'#user_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'tab-learnig'));
                        echo html_writer::end_tag('li');
                           
				
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('cohortprop', 'local_properties'),array('href'=>'#cohort_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'tab-learnig'));
                        echo html_writer::end_tag('li');
                
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('coursesprop', 'local_properties'),array('href'=>'#course_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'tab-learnig'));
                        echo html_writer::end_tag('li');
                
                        echo html_writer::start_tag('li',array('role'=>'presentation', 'class'=>''));
                            echo html_writer::tag('a',get_string('learningpathprop', 'local_properties'),array('href'=>'#learningpath_tab', 'aria-controls'=>'previoussessions', 'aria-expanded'=>'false', 'role'=>'tab', 'data-toggle'=>'tab', 'class'=>'tab-learnig'));
                        echo html_writer::end_tag('li');
                                
                    echo html_writer::end_tag('ul');
                echo html_writer::end_tag('div');
            echo  html_writer::end_tag('div');
                
            $stredit = get_string('edit','local_properties');
            $strdelete   = get_string('delete','local_properties');
              
                
            echo html_writer::start_div('div', array('id' => 'user_tab', 'class' => 'tab-pane fade active in'));

                $editstr = '<a title="'.$stredit.'" href="index.php?id='."1".'&amp;action=editfield"><img src="'.$OUTPUT->pix_url('t/edit') . '" alt="'.$stredit.'" class="iconsmall" /></a> ';
               // Delete.
                $editstr .= '<a title="'.$strdelete.'" href="index.php?id='."2".'&amp;action=deletefield&amp;sesskey='.sesskey();
                $editstr .= '"><img src="'.$OUTPUT->pix_url('t/delete') . '" alt="'.$strdelete.'" class="iconsmall" /></a> ';
                $editstr  .=  '<a title="'.$stredit.'" href="index.php?id='."1".'&amp;action=editfield"><img src="'.$OUTPUT->pix_url('t/add') . '" alt="'.$stredit.'" class="iconsmall" /></a> ';
                echo html_writer::tag('html',$editstr);
                $table = getCategories();

                if (count($table->data)) {
    
                    echo html_writer::table($table);
                } else {
                    echo $OUTPUT->notification($strnofields);
                }
    
            echo html_writer::end_tag('div');
                
            echo html_writer::start_tag('div', array('id' => 'cohort_tab', 'class' => 'tab-pane fade'));
                echo html_writer::tag('html', 'Hi I am in cohort');
            echo html_writer::end_tag('div');
                
            echo html_writer::start_tag('div', array('id' => 'course_tab', 'class' => 'tab-pane fade'));
                echo html_writer::tag('html', 'Hi I am in course');
            echo html_writer::end_tag('div');
                
            echo html_writer::start_tag('div', array('id' => 'learningpath_tab', 'class' => 'tab-pane fade'));
                echo html_writer::tag('html', 'Hi I am in Learningpath');
            echo html_writer::end_tag('div');

        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
                
    
                
                
                
                