<?php


defined('MOODLE_INTERNAL') || die();

require_once("lib/lib.php");

class block_lpd extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_lpd');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
            'admin' => false,
            'site-index' => true,
            'course-view' => true,
            'mod' => false,
            'my' => true
        );
    }

    public function specialization() {
        global $SESSION,$DB;
        
        // Add company name along with the heading, if tenant is selected by admin
        $companyname = '';
        if(is_siteadmin() && !empty($SESSION->currenteditingcompany)){
            $companyid = $SESSION->currenteditingcompany;
            $companyname = '('.$DB->get_field('company', 'name', array('id' => $companyid)).')';
        }
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_lpd'). ' '.$companyname;
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $USER, $PAGE, $CFG;
        $page = optional_param('page', 0, PARAM_INT);
        $lp_id = optional_param('lp_id', 0, PARAM_INT);
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        if (empty($this->config)) {
            $this->config = new stdClass();
        }
        
        //Create empty content
        $this->content = new stdClass();
        $this->content->text = '';
        
        $PAGE->requires->js_call_amd('block_lpd/paradisolpd', 'init');
        try {
            $config = get_config('block_lpd');
            if (isset($this->config->learningpath)) {
                $this->content->text .= displayLearningPathsViewDetail($this->config->learningpath, $USER->id, $page );
            } else {
                $this->content->text .= displayLearningPathsView($config->evaluate_progress, $USER->id, $page);
            }
        } catch (Exception $e) {
            throw $e;
        }
        
        return $this->content;
    }
    
    public function instance_create() {
        global $SESSION;
        $company = isset($SESSION->currenteditingcompany) ? $SESSION->currenteditingcompany : 0;
        $config = (object)array(
            'tenant' => $company,
        );
        $this->instance_config_save($config);
        return true;
    }
}
