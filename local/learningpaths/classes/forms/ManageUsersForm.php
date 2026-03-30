<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG , $USER , $OUTPUT;
require_once("{$CFG->libdir}/formslib.php");

class ManageUsersForm extends moodleform
{
    public function definition() {

        global $CFG, $OUTPUT, $PAGE, $USER;
        $PAGE->requires->js_call_amd('local_learningpaths/learningpaths', 'lpactions');

        $page  = optional_param('page', 0, PARAM_INT);
        $action = optional_param('action','',PARAM_TEXT);
        $search = optional_param('search','',PARAM_TEXT);
        $dashboard_per_page = optional_param('perpage', 10, PARAM_INT);
        $mform = $this->_form;

        if($this->_customdata['pageno']) {
            $page = $this->_customdata['pageno'];
        }
        if($this->_customdata['dashboard_per_page']) {
            $dashboard_per_page = $this->_customdata['dashboard_per_page'];
        }
        // Important Hidden fields.
        $mform->addElement('hidden', 'id', $this->_customdata['learningpath']);
        $mform->addElement('hidden', 'form', "ManageUsersForm");

        // Form definition.
        $users = $this->_customdata['users'];
        $selectedusers = $this->_customdata['selected'];
        $selectedusers = explode(",",$selectedusers);
        
        $la_index  = array_keys($users);
        $la_pag_users = array();

        for( $record =($page * $dashboard_per_page); $record < (( $page * $dashboard_per_page ) + $dashboard_per_page) ; $record++ ) {
           if($users[ $la_index[$record] ]) $la_pag_users[ $la_index[$record] ] = $users[ $la_index[$record] ];
        }

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'content-search')));
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'row searchbox-add')));
                /*$mform->addElement('html', html_writer::start_tag('div', array('class' => 'col-sm-12')));
                    $params = array('id' => 'searchbox', 'class' => 'searchbox', 'role' => 'search');
                    $mform->addElement('html', html_writer::start_tag('div', $params));
                        $params = array('class' => 'mt-search input-group custom-search-form');
                        $mform->addElement('html', html_writer::start_tag('div', $params));
                        
                            $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-btn')));
                                $params = array('class' => 'text-muted btn','type' => 'button','onclick' => '','aria-label' => '');
                                $mform->addElement('html', html_writer::start_tag('button', $params));
                                    /*$params = array('class' => 'men men-search-phx header-txtmen i-search','aria-hidden' => 'true');
                                    $mform->addElement('html', html_writer::tag('i', '', $params));
                                $mform->addElement('html', html_writer::end_tag('button'));
                            $mform->addElement('html', html_writer::end_tag('span'));
                                    
                            /*$params = array(
                                'class' => 'add-users-search form-control',
                                'type' => 'text',
                                'data-target' => 'available-users-list',
                                'placeholder' => get_string('search', 'local_learningpaths'),
                                'aria-label' => '', 'onkeyup' => '',
                                'value' => $search
                            );
                                
                            $mform->addElement('html', html_writer::tag('input', '', $params));
                        $mform->addElement('html', html_writer::end_tag('div'));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));*/
            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', '<div id="available-users-list" class="content-addusers card-box">');
        
            $sallusers = get_string('user_name_lp', 'local_learningpaths').': '.count($users);
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'count_tittle')));
                $mform->addElement('html', html_writer::tag('span', $sallusers));
            $mform->addElement('html', html_writer::end_tag('div'));
              
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'container header-add-users')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'header-users col-sm-12')));
                    
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'header-title row')));
                        $checkbox = \theme_remui\widget::checkbox('', false, 'all_users','all_users', false,array('class' => 'users_lpall form-check-input','onclick'=>"check_all('all_users','users-lpall')") );
                        $mform->addElement('html',$checkbox );
                                
                        $mform->addElement('html', html_writer::start_tag('div' , array ('class' => 'col-xs-4 col-sm-4 col-md-4 col-lg-5 ml-4')));
                            $mform->addElement('html', html_writer::tag('span', get_string('user_name', 'local_learningpaths')));
                        $mform->addElement('html', html_writer::end_tag('div'));

                        $mform->addElement('html', html_writer::start_tag('div' , array ('class' => 'col-xs-4 col-sm-4 col-md-4 col-lg-3 ml-3')));
                            $mform->addElement('html', html_writer::tag('span', get_string('email_name', 'local_learningpaths')));
                        $mform->addElement('html', html_writer::end_tag('div'));
                    $mform->addElement('html', html_writer::end_tag('div'));

                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));

            $mform->addElement('html', html_writer::start_tag('div' , array ('class' => 'contentenrollusers')));
            if($la_pag_users) {
                foreach ($selectedusers as $userval) {
                    $mform->addElement('html', html_writer::tag('input', '', array('type'=>'text','style'=>'display:none;','name'=>'users[]','value'=>$userval)));
                }
                foreach ($la_pag_users as $user) {
                    $input = "
                        <span class='name form-check-sign'>{$user->firstname} {$user->lastname}</span>
                    ";

                    if(in_array($user->id,$selectedusers)){
                        $checkbox = \theme_remui\widget::checkbox($user->firstname.' '. $user->lastname, false, '','users[]', false,array('class' => 'users-lpall form-check-input' ,'value' => $user->id,'checked'=>true),'name' );
                    }else{
                        $checkbox = \theme_remui\widget::checkbox($user->firstname.' '. $user->lastname, false, '','users[]', false,array('class' => 'users-lpall form-check-input' ,'value' => $user->id),'name' );
                    }
                    $checkemail = \theme_remui\widget::checkbox($user->email, false, '','users[]', false,array('class' => 'users-lpall form-check-input vsl_2' ,'value' => $user->id),'name' );
                    $mform->addElement('html', "
                        <div class='row users-lp'>
                        ".$checkbox."
                        ".$checkemail."
                        </div>
                    ");
                } 
            } else {
                $mform->addElement('html', html_writer::tag('span', get_string('no_records_user', 'local_learningpaths')));
            }
            $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', '</div>');
            
        $a = $mform;
        $pages = count($users) / $dashboard_per_page;

        $mform->addElement('html',html_writer::start_tag('div', array('class'=>'pagination_lp')));
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'clase1')));
                $mform->addElement('html', html_writer::start_tag('ul'));
                    $mform->addElement('html', html_writer::start_tag('li'));
                        $mform->addElement('html', html_writer::start_tag('div', array('style'=>'float:left;padding-top: 7px;margin-right: 10px;')));
                            $mform->addElement('html',html_writer::tag('span',get_string('recordsperpage','local_people'), array()));
                        $mform->addElement('html', html_writer::end_tag('div'));

                        $mform->addElement('html',html_writer::start_tag('div', array('style'=>'float:left;')));
                            
                            $mform->addElement('html', html_writer::start_tag('select',array('type'=>'text','id'=>'id_userpopupperpage','name'=>'userperpage','class'=>'form-control','style'=>'width:70px;')));
                                $vals = array(10,20,30,40,50,60,70,80,90,100);
                                
                                foreach ($vals as $key) {
                                    $selectedperpage = '';
                                    if($dashboard_per_page == $key ) $selectedperpage = 'selected' ;
                                    $mform->addElement('html',html_writer::tag('option',$key, array($selectedperpage=>$selectedperpage)));
                                }

                            $mform->addElement('html',html_writer::end_tag('select'));
                        $mform->addElement('html',html_writer::end_tag('div'));
                    $mform->addElement('html',html_writer::end_tag('li'));
                        
                    $mform->addElement('html',html_writer::start_tag('li'));
                        if ($pages > 1)
                        $mform->addElement('html', $OUTPUT->paging_bar(count($users), $page, $dashboard_per_page,''));
                    $mform->addElement('html', html_writer::end_tag('li'));
                $mform->addElement('html', html_writer::end_tag('ul'));
            $mform->addElement('html', html_writer::end_tag("div"));

        $mform->addElement('html', html_writer::end_tag('div'));

        // Action buttons.
        $this->add_action_buttons();
    }


    // Add action buttons.
    public function add_action_buttons ($cancel = false, $submitlabel = null) {
        $mform = $this->_form;
        $buttonarray = array();

        if ($cancel) {
            $buttonarray[] = &$mform->createElement('html', html_writer::tag('button', get_string('cancel'), array(
                'class' => 'btn btn-cancel btn-round'
            )));
        }

        if ($submitlabel !== false) {
            $submitlabel = get_string('savechanges');
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }
}