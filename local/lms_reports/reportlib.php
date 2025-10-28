<?php

if ( !function_exists('lms_get_current_editing_company')) {

	function lms_get_current_editing_company($setting, $format = false) {

	    $company_id = '';
	    $company = lms_get_current_editing_company();
	    if($company){
	        $company_id = $company->id;
	    }
	    $original_setting = $setting;
	    $setting = $setting . $company_id;


	    global $CFG;
	    require_once ($CFG->dirroot . '/lib/weblib.php');
	    static $theme;
	    if(empty($theme))
	    {
	        $theme = theme_config::load('remui');
	    }
	    if(empty($theme->settings->$setting))
	    {
	        //List of settings and default value
	        $defaults = [
	            'usealternativelogotextcolor' => false,
	            'customnavigation' => true,
	            'marketingspotsdiplay' => false,
	            'logotext' => '',
	            'logotextcolor' => '#000000',
	            'backgroundcolor' => '#ffffff',
	            'foregroundcolor' => '#6ebc00',
	            'backgroundcontentcolor' => '#ffffff',
	            'backgroundcolorcustomnavigation' => '#ffffff',
	            'hovercolor' => '#6ebc00',

	            'alternativehovercolor' => '#444242',
	            'customnavigationhovercolor' => '#ffffff',
	            'lateralsheaderblocksbackground' => '#434040',
	            'lateralsheaderblockscolor' => '#ffffff',
	            'lateralscontentblocksbackgroundcolor' => '#ffffff',
	            'lateralscontentblockscolor' => '#000000',
	            'menusbackground' => '#222222',
	            'menuscolor' => '#ffffff',
	            'configbackground' => '#222222',
	            'configcolor' => '#ffffff',
	            'breadcrumbbackground' => '#222222',
	            'breadcrumbcolor' => '#ffffff',
	            'backgroundbutton' => '#6ebc00',
	            'textbutton' => '#ffffff',
	            'iconscolor' => '#6ebc00',
	            'calendarcolor' => '#6ebc00',
	            'linkscontentcolor' => '#000000',
	            'customnavigationiconscolor' => '#ffffff',
	            'custombackgroundcourseoverviewcolor' => '#95C905',
	            'enabletotalusersreport'=>'yes',
	            'enableregisteredtodayreport'=>'yes',
	            'enabletopcoursesviewedreport'=>'yes',
	            'enabletopcoursesenrolledreport'=>'yes',
	            'enableusersonlyreport'=>'yes',
	            'enableloginsperdayreport'=>'yes',
	            'enablecoursecatalogbutton'=>'yes',
	            // added by Miguel p. 01/06/2016 - add links colors in main content 
	            'custombackgroundtextlinkcolor' => '#515151'

	        ];

	        if(isset($defaults[$original_setting])){
	            return $defaults[$original_setting];
	        } else {
	            //print_object($setting);
	            return false;
	        }
	        //
	        //
	    }
	    else
	        if(! $format)
	        {
	            return $theme->settings->$setting;
	        }
	        else
	            if($format === 'format_text')
	            {
	                return format_text($theme->settings->$setting, FORMAT_PLAIN);
	            }
	            else
	                if($format === 'format_html')
	                {
	                    return format_text($theme->settings->$setting, FORMAT_HTML, array(
	                        'trusted' => true,
	                        'noclean' => true
	                    ));
	                }
	                else
	                {
	                    return format_string($theme->settings->$setting);
	                }
	}
}


    function lms_get_current_editing_company() {

        global $SESSION, $DB, $USER;

        $context = context_system::instance();
        $roles = get_user_roles($context, $USER->id);
        $companymanager = false;

        foreach ($roles as $role) {
            if ($role->shortname == 'companymanager') {
                $manager = true;
                break;
            }
        }

        $company = null;
        if ((is_siteadmin() || $companymanager == true ) || (isset($SESSION->currenteditingcompany) && !empty($SESSION->currenteditingcompany) )) {
            if (isset($SESSION->currenteditingcompany) && !empty($SESSION->currenteditingcompany)) {
                $company = $DB->get_record('company', array(
                    'id' => (int) $SESSION->currenteditingcompany
                ));
            }
        } else {
            if (isset($USER->company)) {
                $company = $DB->get_record('company', array('id' => (int) $USER->company->id));
            }
        }
        return $company;
    }


function companyname_list(){
	global $DB;

	$companies = array();
	$sql = "SELECT id,name from {company} WHERE suspended = 0";
	$tenants = $DB->get_records_sql($sql);

	foreach($tenants AS $tenant)
	$companies[$tenant->id] = $tenant->name;

	return $companies;
}

function get_reports_name(){
	global $DB;

	$reports 	 = array();
	$sql 		 = "SELECT id,name from {block_configurable_reports} where companyid = 0";
	$reportsdata =  $DB->get_records_sql($sql);

	foreach($reportsdata AS $report)
	$reports[$report->id] = $report->name;

	return $reports;
}

function create_duplicate_report($data,$courseid){
	global $DB,$CFG;
	
	if(isset($data->company) && isset($data->report)){
	 
            $companies  = $data->company;
            $reports 	= $data->report;
            $done = false;
            foreach($companies as $company){
                foreach ($reports as $report){
                    if($DB->record_exists('block_configurable_reports', ['id'=>$report])){
                        $record = $DB->get_record('block_configurable_reports',['id' => $report]);
                        $report_data = $record;
                        unset($report_data->id);
                        $report_data->companyid= $company;

                        $id = $DB->insert_record('block_configurable_reports', $report_data);

                        if(!empty($id)){
                            $sql = "INSERT INTO  {local_lms_reports}
                                                            (`name`, `summary`, `url`,`idcr`, `idtype`, `iduser`, `favorite`, `state`, `order`) 
                                                    SELECT `name`, `summary`, `url`, $id, `idtype`, `iduser`, `favorite`, `state`, `order` 
                                                    FROM {local_lms_reports}
                                                    WHERE `idcr` = $report";
                            $DB->execute($sql);
                            $done = true;
                        }
				
                    }
                }
            }
            if($done){
                $returnurl = new moodle_url('/local/lms_reports/duplicate_report.php',['courseid'=>$courseid]);
                redirect($returnurl, get_string('reportcreated', 'block_configurable_reports'), null, \core\output\notification::NOTIFY_SUCCESS,'','');
            }
	}else{
		redirect($returnurl, get_string('required_field', 'local_lms_reports'), null, \core\output\notification::NOTIFY_ERROR);
	}
}
?>