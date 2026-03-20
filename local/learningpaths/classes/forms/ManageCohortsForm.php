<?php
defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class ManageCohortsForm extends moodleform
{
    public function definition() {
        $mform = $this->_form;

        // Important Hidden fields.
        $mform->addElement('hidden', 'id', $this->_customdata['learningpath']);
        $mform->addElement('hidden', 'form', "ManageCohortsForm");

        // Form definition.
        $cohorts = $this->_customdata['cohorts'];

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'content-search')));
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'row searchbox-add')));
                $mform->addElement('html', html_writer::start_tag('div', array('class' => 'col-sm-12')));
                    $params = array('id' => 'searchbox', 'class' => 'searchbox', 'role' => 'search');
                    $mform->addElement('html', html_writer::start_tag('div', $params));
                        $params = array('class' => 'mt-search input-group custom-search-form');
                        $mform->addElement('html', html_writer::start_tag('div', $params));
                            $mform->addElement('html', html_writer::start_tag('span', array('class' => 'input-group-btn')));
                                $params = array(
                                    'class' => 'text-muted btn btn-default',
                                    'type' => 'button',
                                    'onclick' => '',
                                    'aria-label' => ''
                                );
                                $mform->addElement('html', html_writer::start_tag('button', $params));
                                        $params = array(
                                            'class' => 'men men-search-phx fa fa-search header-txtmen men-icon-search i-search',
                                            'aria-hidden' => 'true'
                                        );
                                        $mform->addElement('html', html_writer::tag('i', '', $params));
                                    $mform->addElement('html', html_writer::end_tag('button'));
                                $mform->addElement('html', html_writer::end_tag('span'));

                            $params = array(
                                'class' => 'form-control add-cohorts-search',
                                'type' => 'text',
                                'placeholder' => get_string('search_cohort', 'local_learningpaths'),
                                'aria-label' => '',
                                'onkeyup' => '',
                                'data-ttype' => 'id',
                                'data-target' => 'available-cohorts-list',
                                'data-parent' => 'yes'
                            );
                            $mform->addElement('html', html_writer::tag('input', '', $params));
                        $mform->addElement('html', html_writer::end_tag('div'));
                    $mform->addElement('html', html_writer::end_tag('div'));
                $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));


        $checkbox = \theme_remui\widget::checkbox('', false, 'selec_allcohorts','selec_allcohorts', false,array('class' => 'form-check-input') );
        


        $mform->addElement('html', '<div id="available-cohorts-list" class="content-addcohorts card-box">');
          $sallcohort =get_string('cohort_name_lp', 'local_learningpaths').': '. html_writer::tag('span', count($cohorts), $params = array('class' => 'cohorts-count'));
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'count_tittle')));
                $mform->addElement('html', html_writer::tag('span', $sallcohort));
              $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', '
            <div class="container header-add-cohorts">
                <div class="header-cohorts col-sm-12">
                    <div class="header-title">
                        '.$checkbox.'
                        <span>' . get_string('cohort_name', 'local_learningpaths') . '</span>
                    </div>
                </div>
            </div>
        ');
        foreach ($cohorts as $cohort) {
            $input = "
                <span class='name'>{$cohort->name}</span>
                ";

            // Add company name label along with cohort name, if company's cohort
            $checkcompanycohort = $this->get_companyname_label_lp($cohort->id, $this->_customdata['learningpath']);
          
            $checkbox = \theme_remui\widget::checkbox($cohort->name  . $checkcompanycohort, false, '','cohorts[]', false,array('class' => 'cohort-learninpath form-check-input' ,'value' => $cohort->id) );
            $mform->addElement('html', "
                <div class='row cohorts-lp'>
                <div class='name col-xs-12 col-sm-12'>
                ".$checkbox."
                </div>
                </div>
            ");
  
        }
        $mform->addElement('html', '</div>');

        // Action buttons.
        $this->add_action_buttons();
    }

    // Add action buttons.
    public function add_action_buttons ($cancel = false, $submitlabel = null) {
        $mform = $this->_form;
        $buttonarray = array();

        if ($cancel) {
            $params = array('class' => 'btn btn-cancel');
            $buttonarray[] = &$mform->createElement('html', html_writer::tag('button', get_string('cancel'), $params));
        }

        if ($submitlabel !== false) {
            $submitlabel = get_string('savechanges');
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Method to check if company's cohort, add companyname label
     * @author Manisha M.
     * @since  26-11-2019
     * @paradiso
    */
    public function get_companyname_label_lp($cohortid ,$lpid){
        global $DB;

        $mform = $this->_form;
        $companylabel = '';
        $lp = $DB->get_record('learningpaths', ['id' => $lpid]);
         
        if($lp->companyid > 0){
            return false;
        }else{
           
            $record = $DB->get_record('company_cohorts', ['cohortid' => $cohortid]);
            if($record){ 
                $companyname = $DB->get_record('company', ['id' => $record->companyid]);
                $companylabel = '<span class="badge badge-primary companylabel">'.$companyname->name.'</span>';
            }
            return $companylabel;
        }
    }
}