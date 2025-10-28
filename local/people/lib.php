<?php
use theme_remui\widget;

/** 
* Function For Displying New Design For Search Button Entire LMS and Action Buttons  
*  
* @param array $filter like username, role etc   
* @param array $multifilter group of filter name    
* @return Template for first row of buttons people index page
*/
function get_first_rowsearch_buttons_block($filter, $multifilter, $companyid) {
  require_once("../../config.php");
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
  if (!empty($SESSION->currenteditingcompany)) {
      $companyids = $SESSION->currenteditingcompany;
  } else if (!empty($USER->profile->company)) {
      $usercompany = company::by_userid($USER->id);
      $companyids = $usercompany->id;
  } else {
      $companyids = "";
  }
  $sitecontext = context_system::instance();

    /**
    * Descripcion : Add clear all case, Set filter an multifilter to null
    * @author Hernan A.
    * @since 17/08/2016
    * @remui
    */
    
    if(isset($_POST['clearall'])){
      $filter = null;
      $multifilter = null;
    }

    if (isset($_POST['search'])) {
        $search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_ENCODED);
    } else if (isset($_GET['search'])) {
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_ENCODED);
    } else {
        $search = '';
    }
    $classbutton = ($search) ? 'd-block' : 'hidden';
    $iconsearch = ($search) ? 'fa-close' : '';

    $contextdata['search'] = $search;
    $contextdata['classbutton'] = $classbutton;
    // $systemcontext = context_system::instance();
    // $companyids = iomad::get_my_companyid($systemcontext);
    if($companyids){
      $params['id'] = $companyids;
    }
    $params['suspended'] = 0;
    $companies = $DB->get_records('company', $params, $sort='', $fields='*', $limitfrom=0, $limitnum=0);
    /**
    * Defined temp variable as an empty array
    * @author Deyby G.
    * @since June 07 of 2017
    * @ticket 986
    * @remui 
    */

    if ($companyid == 'ALL') {

      $contextdata['all_selected'] = 'selected';
    } else if ($companyid == 'NONE') {

     $contextdata['none_selected'] = 'selected';
   }
   foreach($companies as $company) {
    $op['id'] = $company->id;
    $op['name'] = $company->name;

    if ($companyid == $company->id && $companyid >= 1) {
      $op['selected'] = 'selected';
    } else {
      $op['selected'] = '';
    }
    $compnyoption[] = $op;
  }
  $com = array();
  $companyid = optional_param('company', '', PARAM_ALPHANUM);
  if(empty($companyids)){
    $com['tenants'] = $compnyoption;
    $contextdata['companies'] = $com;
  }else{
    $contextdata['iscompanyselect'] = true;
    $contextdata['companies'] = $compnyoption;
  }

  $contextdata['popupurl'] = $CFG->wwwroot .'/help.php?component=local_people&identifier=select_tenant_people_input&lang='.current_language();

    /**
    * Descripcion : Add clear all filter button
    * @author Hernan A.
    * @since 17/08/2016
    * @remui
    */
    $contextdata['action'] = htmlspecialchars($_SERVER['PHP_SELF']);
    $uploadURL = $CFG->wwwroot.'/'.$CFG->admin.'/tool/uploaduser/index.php';
    $contextdata['uploadURL'] = $uploadURL;
    if(has_capability('moodle/user:delete',$sitecontext)){
      $contextdata['capability'] = true;
    }
    return $OUTPUT->render_from_template('local_people/first_rowsearch_buttons_block', $contextdata);
  }

/** 
* Function For Displying Filter Names On People Page For Search Data In Less Time   
*  
* @param array $filter like username, role etc   
* @param array $multifilter group of filter name    
* @return Template for filter names people index page
*/
function get_filter_block($filter, $multifilter) {
  require_once("../../config.php");
  global $CFG, $OUTPUT;

    /**
    * Descripcion : Add clear all case, Set filter an multifilter to null
    * @author Hernan A.
    * @since 17/08/2016
    * @remui
    */
    
    if(isset($_POST['clearall'])){
      $filter = null;
      $multifilter = null;
    }

    $search = optional_param('search', '', PARAM_TEXT); 
    // New row added here
    $ffilter = (isset($multifilter['firstname'])) ? $multifilter['firstname'] : null;
    $ffilter = (isset($multifilter['lastname'])) ? $multifilter['lastname'] : null;
    $ffilter = (isset($multifilter['userfullname'])) ? $multifilter['userfullname'] : null;
    if(isset($ffilter->field) && $ffilter->field=='userfullname') {
      $userfullnameop    = $ffilter->op;
      $userfullnamevalue = $ffilter->value;
    } else {
      $userfullnameop    = '';
      $userfullnamevalue = '';
    }

    $contextdata['userfullnamefilter'] = get_userfullname_filter($userfullnameop, $userfullnamevalue);

    $ffilter = (isset($multifilter['email'])) ? $multifilter['email'] : null;
    if(isset($ffilter->field) && $ffilter->field=='email') {
      $emailop    = $ffilter->op;
      $emailvalue = $ffilter->value;
    } else {
      $emailop    = '';
      $emailvalue = '';
    }

    $contextdata['emailfilter'] = get_email_filter($emailop, $emailvalue);
    $ffilter = (isset($multifilter['username'])) ? $multifilter['username'] : null;
    if(isset($ffilter->field) && $ffilter->field=='username') {
      $usernameop    = $ffilter->op;
      $usernamevalue = $ffilter->value;
    } else {
      $usernameop    = '';
      $usernamevalue = '';
    }

    $contextdata['usernamefilter'] = get_username_filter($usernameop, $usernamevalue);
    $ffilter = (isset($multifilter['city'])) ? $multifilter['city'] : null;
    if(isset($ffilter->field) && $ffilter->field=='city') {
      $cityop  = $ffilter->ar;
    } else {
      $cityop  = '';
    }

    $contextdata['cityfilter'] = get_city_filter($cityop);
    $ffilter = (isset($multifilter['country'])) ? $multifilter['country'] : null;
    if(isset($ffilter->field) && $ffilter->field=='country') {
      $countryop    = $ffilter->ar;
    } else {
      $countryop    = '';
    }

    $contextdata['countryfilter'] = get_country_filter($countryop);
    $ffilter = (isset($multifilter['courserole'])) ? $multifilter['courserole'] : null;

    if(isset($ffilter->field) && $ffilter->field=='courserole') {
      $courselist = $ffilter->ar;
      $courserole = $ffilter->role;
    } else {
      $courselist = '';
      $courserole = '';
    }

    $contextdata['courserolefilter'] = get_courserole_filter($courselist, $courserole);
    /*System role*/
    $ffilter = (isset($multifilter['systemrole'])) ? $multifilter['systemrole'] : null; 
    if(isset($ffilter->field) && $ffilter->field=='systemrole') {
      $systemrole = $ffilter->op;
    } else {
      $systemrole = '';
    }

    $contextdata['systemrolefilter'] = get_systemrole_filter($systemrole);
    $ffilter = (isset($multifilter['auth'])) ? $multifilter['auth'] : null;
    /*Autentification*/
    if(isset($ffilter->field) && $ffilter->field=='auth') {
      $authenticationop = $ffilter->op;
    } else {
      $authenticationop = null;
    }

    $contextdata['authfilter'] = get_auth_filter($authenticationop);
    $ffilter = (isset($multifilter['confirmed'])) ? $multifilter['confirmed'] : null;
    if(isset($ffilter->field) && $ffilter->field=='confirmed') {
      $authenticationop = $ffilter->value;
    } else {
      $authenticationop = null;
    }

    $contextdata['confirmedfilter'] = get_confirmed_filter($authenticationop);
    $ffilter = (isset($multifilter['suspended'])) ? $multifilter['suspended'] : null;
    if(isset($ffilter->field) && $ffilter->field=='suspended') {
      $authenticationop = $ffilter->value;
    } else {
      $authenticationop = null;
    }
    $contextdata['suspendedfilter'] = get_suspended_filter($authenticationop);
    $ffilter = (isset($multifilter['firstaccessd'])) ? $multifilter['firstaccessd'] : null;
    if(isset($ffilter->field) && $ffilter->field=='firstaccessd') {
      $firstaccessedgt = $ffilter->gt;
      $firstaccessedlt = $ffilter->lt;
      $neveraccessf    = $ffilter->access;
    } else {
      $firstaccessedgt = '';
      $firstaccessedlt = '';
      $neveraccessf    = '';
    }

    $contextdata['firstaccessdfilter'] = get_firstaccessd_filter($firstaccessedgt, $firstaccessedlt,$neveraccessf);
    $ffilter = (isset($multifilter['lastaccessed'])) ? $multifilter['lastaccessed'] : null;
    if(isset($ffilter->field) && $ffilter->field=='lastaccessed') {
      $lastaccessedgt = $ffilter->gt;
      $lastaccessedlt = $ffilter->lt;
      $neveraccessl   = $ffilter->access;
    } else {
      $lastaccessedgt = '';
      $lastaccessedlt = '';
      $neveraccessl   = '';
    }

    $contextdata['lastaccessedfilter'] = get_lastaccessed_filter($lastaccessedgt, $lastaccessedlt, $neveraccessl);
    $ffilter = (isset($multifilter['lastmodified'])) ? $multifilter['lastmodified'] : null;

    if(isset($ffilter->field) && $ffilter->field=='lastmodified') {
      $lastmodifiedgt = $ffilter->gt;
      $lastmodifiedlt = $ffilter->lt;
      $nevermodified  = $ffilter->edited;
    } else {
      $lastmodifiedgt = '';
      $lastmodifiedlt = '';
      $nevermodified  = '';
    }

    $contextdata['lastmodifiedfilter'] = get_lastmodified_filter($lastmodifiedgt, $lastmodifiedlt, $nevermodified);
    return $OUTPUT->render_from_template('local_people/get_filter_block', $contextdata);
  }

/** 
* Function For Displying All User List In People Page    
*  
* @param array $filter like username, role etc   
* @param array $multifilter group of filter name    
* @return Template for displaying all register users 
*/
/**
* @issue #6: Issue with translations in LMS, change search...
* @author Jonatan U.
* @since 2018-02-12
* @remui
*/
function get_user_block($filter, $sortfield='', $order='ASC', $multifilter) {
  global $CFG, $DB, $OUTPUT, $USER;
    /**
    * Descripcion : Add clear all case, Set filter an multifilter to null and set filter page to 0
    * @author Hernan A.
    * @since 17/08/2016
    * @remui
    */

    if(isset($_POST['clearall'])){
      $filter = new stdClass();
      $filter->page = 0;
      $filter->userperpage = $_SESSION['userperpage'];
      $multifilter = array('page'=>'0');
    }

    $sitecontext = context_system::instance();
    $page = '';
    $params = array();
    $companyid = optional_param('company', '', PARAM_ALPHANUM);

    if(isset($companyid)) {
      $param['company'] = $companyid;
      $params['company'] = $companyid;
    }

    if ($sortfield == 'firstname') {
      $neworder = $order == 'ASC' ? 'DESC' : 'ASC';
    } else {
      $neworder = 'ASC';
    }

    $param['sort']  = 'firstname';
    $param['order'] =  $neworder;

    $firstnameurl = new moodle_url($CFG->wwwroot . '/local/people/index.php', $param);

    if ($sortfield == 'lastname') {
      $neworder = $order == 'ASC' ? 'DESC' : 'ASC';
    } else {
      $neworder = 'ASC';
    }

    $param['sort']  = 'lastname';
    $param['order'] =  $neworder;

    $lastnameurl = new moodle_url($CFG->wwwroot . '/local/people/index.php',$param);

    if ($sortfield == 'lastaccess') {
      $neworder = $order == 'ASC' ? 'DESC' : 'ASC';
    } else {
      $neworder = 'ASC';
    }

    $param['sort']  =  'lastaccess';
    $param['order'] =   $neworder;

    $lastaccessurl = new moodle_url($CFG->wwwroot . '/local/people/index.php', $param);
    if($sortfield){
      $params['sort']  = $sortfield;
      $params['order'] = $order;
    }

    if($filter->page) {
      $params['page'] = $filter->page;
    }

    $search = optional_param('search', '', PARAM_TEXT);
    if($filter->userperpage) {
     $_SESSION['userperpage'] = $filter->userperpage;
    }
   $result    = get_user_list($filter, $multifilter);
   $users     = $result->data;
   $usercount = $result->count;

    /////////////////////// FILTER BTN ///////////////////////////////
   $arrayTypes = array(
    "textfilter"=>"value",
    "multipleselectfilter"=>"op",
    "courserole"=>"op",
    "systemrole"=>"op",
    "selectfilter"=>"op",
    "datefilter"=>"gt",
    "datefilter"=>"lt",
    "datefilterr"=>"sm"
  );
   $filtr = array();
   foreach($multifilter as $ffilter){
    if($ffilter->field && $ffilter->field != "filter-true" && str_replace('\'','',$ffilter->{$arrayTypes[$ffilter->type]}) != ""){
      //if ($ffilter->value != 1) 
      {
        $filters['fields'] = $ffilter->field;
        $filters['strfield'] = get_string($ffilter->field,'local_people');
        $filtr[] = $filters;
      }
    }
  }
  $contextdata['multifilter'] = $filtr;
  $params['search'] = $search;
  $baseurl   = new moodle_url('',$params);
  $header    = array();
  $checkbox = widget::checkbox('', false, 'bulk-select-all');

  if(isset($_GET["order"]) && $_GET['order'] == 'ASC'){
    $contextdata['phsortingup'] = true;
  }elseif((isset($_GET["order"]) && $_GET['order'] == 'DESC')){
    $contextdata['phsortingdown'] = true;
  }else{
    $contextdata['phsorting'] = true;
  }
  $contextdatainstance = context_system::instance();
  $isadmin = has_capability('moodle/site:config', $contextdatainstance);

  $head['checkbox'] = $checkbox;
  $head['firstnameurl'] = $firstnameurl;
  $head['lastnameurl'] = $lastnameurl;
  $head['lastaccessurl'] = $lastaccessurl;
  $tenant = false;
  if($isadmin){
    $tenant = true;
  }
  $head['tenant'] = $tenant;
  $contextdata['head'] = $head;
  $authconfir = get_config('auth/email');
  
  foreach ($users as $user){    $userdata = array();     
      //Button login as
    if(!$user->suspended){
      $url = '/course/loginas.php';
      $strloginas = get_string('loginas', 'local_people');
      if(!is_siteadmin($user) && $USER->id <> $user->id){
        $urlloginas =  $CFG->wwwroot.$url.'?sesskey='.sesskey().'&id=1&user='.$user->id;
        $userdata['urlloginas'] = $urlloginas;
        $userdata['loginasbtn'] = true;
      }else{
        if(has_capability('moodle/user:loginas',$sitecontext) && $USER->id <> $user->id && !is_siteadmin($user)){
          $urlloginas = $CFG->wwwroot.$url.'?sesskey='.sesskey().'&id=1&user='.$user->id ;
          $userdata['hasloginasbtn'] = true;
          $userdata['hasurlloginas'] = $urlloginas;
        }
      }    
      if(has_capability('moodle/user:update', $sitecontext)) {
          // prevent editing of admins by non-admins
        if (is_siteadmin($USER) or !is_siteadmin($user)) {
          $urledit = $CFG->wwwroot.'/user/editadvanced.php?id='.$user->id;
          $userdata['urledit'] = $urledit;
          $userdata['iseditbtn'] = true;
        }
      }
    }  
    else{
      $url = '/course/loginas.php';
      $strloginas = get_string('loginas', 'local_people');
      if(!is_siteadmin($user) && $USER->id <> $user->id){
        $urlloginas =  $CFG->wwwroot.$url.'?sesskey='.sesskey().'&id=1&user='.$user->id;
        $userdata['urlloginas'] = $urlloginas;
        $userdata['loginasbtn'] = true;
      } 
    }     

      /**
      * Add icons to suspend and
      * unsuspend user accounts
      * @author Esteban E.
      * @since September 23 of 2016
      * @remui
      */
      //Suspend user active/inactive button
      if (has_capability('moodle/user:update', $sitecontext)) {
        // Esteban E. Add suspend icon or unsuspend
        if (!is_siteadmin($user) ) {
          $stringsus = '';
          if($user->suspended){
            $stringsus = get_string('unsuspenduser', 'admin');
            $classsus = 'fa-eye-slash';
            $actionsus = 'unsuspend';
          }else{
            $stringsus = get_string('suspenduser', 'admin');
            $classsus = 'fa-eye';
            $actionsus = 'suspend';
          }

          if($USER->id <> $user->id) {
            $userdata['issuspendbtn'] = true;
            $userdata['urlsus'] =  $CFG->wwwroot.'/local/people/index.php?sort=name&dir=ASC&page'.$page.'&'.$actionsus."=".$user->id.'&sesskey='.sesskey();
            $userdata['suspendstring'] = $stringsus;
          } 
        }
      } 
      /**
      * If user is not confirmed and user has confirmed the email we show the button
      * @author Daniel Carmona
      * @since 20-03-2018
      * @remui
      */
      if ($user->confirmed == 0) {
        if (has_capability('moodle/user:update', $sitecontext)) {
          if(isset($authconfir->admin_confirmation) && $authconfir->admin_confirmation){
            $data_confirm = $DB->get_record('auth_email_confirm',['userid' => $user->id]);
            if($data_confirm && $data_confirm->email_confirmed){
              $userdata['hasconfirmbtn'] = true;
              $userdata['hasconfirmurl'] = $CFG->wwwroot.'/local/people/index.php?confirmuser='.$user->id.'&sesskey='.sesskey();
            }else{
              $userdata['isconfirmbtn'] = true;
              $userdata['confirmurl'] = $CFG->wwwroot.'/local/people/index.php?sendconfirmuser='.$user->id.'&sesskey='.sesskey();
            }
          }else{
            $userdata['isconfirmbuttn'] = true;
            $userdata['confirmurlbtn'] = $CFG->wwwroot.'/local/people/index.php?confirmuser='.$user->id.'&sesskey='.sesskey();
          }
        } else {
          $userdata['isconfirmbutton'] = true;
        }
      }

      $courses = count_user_courses($user->id);
      if ($user->lastaccess) {
        $strlastaccess = format_time(time() - $user->lastaccess);
      } else {
        $strlastaccess = get_string('never');
      }

     

      $checkbox = widget::checkbox('', false, '','id[]',false,array('value' => $user->id));
      //If multitenant checkbox checked a multitenant column show  
      if($isadmin){
        $sql = "SELECT c.name FROM {company_users} AS cu 
        JOIN {company} AS c ON c.id = cu.companyid AND cu.userid = :userid WHERE cu.suspended = 0";
        $multitenat_comp = $DB->get_record_sql($sql,['userid' => $user->id]);
        $tenant_comp = '-';
        if(!empty($multitenat_comp)){
          $tenant_comp = $multitenat_comp->name;
        } else {
         $tenant_comp = '-';
       }
     
     $sqlss = "SELECT d.name FROM {company_users} AS cu 
      JOIN {department} AS d ON d.id = cu.departmentid AND cu.userid = :userid WHERE cu.suspended = 0";
      $get_dept = $DB->get_record_sql($sqlss,['userid' => $user->id]);
      }

      $sqlssss = "SELECT * FROM {block_skilladd} WHERE userid = $user->id";
      $get_skill = $DB->get_record_sql($sqlssss);

      
      if ($get_skill->skilllevel == "level1") {
        $level = "Beginner";
      }elseif ($get_skill->skilllevel == "level2") {
        $level = "Intermediate";
      }elseif ($get_skill->skilllevel == "level3") {
        $level = "Advanced";
      }else{
        $level = "--";
      }

      $get_skillname = $DB->get_record('block_skilladd_items', array('id' => $get_skill->skill));
     $userdata['checkbox'] = $checkbox;
     $userdata['fullname'] = $user->firstname .' '.$user->lastname;
     $userdata['dept'] = $get_dept->name;
     $userdata['email'] = $user->email;
     $userdata['enroll'] = $courses['enroll'];
     $userdata['complete'] = $courses['complete'];
     $userdata['skill'] = $get_skillname->name;
     $userdata['position'] = $get_skill->position;
     $userdata['level'] = $level;
     $userdata['tenant_comp'] = $tenant_comp;
     $userdata['istenant'] = $tenant;
     $userdata['strlastaccess'] = $strlastaccess;
     $userdata['suspended'] = $user->suspended ? true : false;

     if($user->suspended) {
      $userdata['issuspenduser'] = true;
    }

    if(has_capability('moodle/user:update', $sitecontext)) {
      $userdata['isupdateuser'] = true;
      $userdata['isupdateuserurl'] =  new moodle_url($CFG->wwwroot . '/user/profile.php', array('id' => $user->id));
    }
    $userrows[] = $userdata;
  }

  $action = optional_param('action', NULL, PARAM_ALPHA);

  if($action == 'enroll'){
    $contextdata['actionenroll'] = true;
    $success = get_string('success_message','local_people',$message);
    $contextdata['enrollsuccess'] = $success;
  }

  if($usercount == 0){
    $contextdata['istableempty'] = true;
      /**
      * Create invite button when string searched is an email
      * @author Carlos Alcaraz
      * @since Mar 28/2018
      */
      $lc_invite_button = ( preg_match("/^[0-9a-z._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i",$search));
      $contextdata['lcinvitebutton'] = $lc_invite_button;
    } else {
      $contextdata['istable'] = true;
      $contextdata['istablerows'] = $userrows;
    }
    
    if($result->count){
      $contextdata['count'] = true;
      $contextdata['totalrecords'] = get_string('totalrecords', 'local_people', $result->count);
    }
     // Check if Disable tenant from setting then below condition working 
    $theme = theme_config::load('remui');
    $tenanthide = $theme->settings->enabletenantinfo;
      // END
    if($isadmin && empty($tenanthide)){
      $contextdata['tenanthide'] = true;
      $host2service = $DB->get_record('user_preferences', array('name'=>'people_multitenant','userid'=>$USER->id));
      $contextdata['host2service'] = $host2service->value;
    }     
    $contextdata['tablecontent'] = $tablecontent;     
      /**
      * Add dropdown list to select records per page by default 20
      * @author Esteban E.
      * @since October 10 of 2016
      * @remui
      */
      $option = array();  
      if($_SESSION['userperpage'] < $result->count ){
        $contextdata['userperpage'] = true;
        $vals = array(10,20,30,40,50,60,70,80,90,100);
        foreach ($vals  as $key) {
          $selectedperpage = '';
          if($_SESSION['userperpage'] == $key ) $selectedperpage = 'selected';
          $op['key'] = $key;
          $op['selected'] = $selectedperpage;
          $option[] = $op;
        }    
      }
      $contextdata['options'] = $option;
      $contextdata['pagingbar'] = $OUTPUT->paging_bar($usercount, $filter->page, $filter->userperpage, $baseurl);
      if(!empty($_SESSION['userperpage'])) {
        $filter->userperpage = $_SESSION['userperpage'] ;
      }else{
        $filter->userperpage = 20;
      }
      return $OUTPUT->render_from_template('local_people/get_user_block', $contextdata);
    }

/** 
* Function For Return Firsname Filter Block    
*  
* @param string $firstnameop   
* @param string $firstnamevalue    
* @return This method return the first name filter block 
*/
function get_first_name_filter($firstnameop='contain', $firstnamevalue='') {
  global $CFG, $DB, $OUTPUT, $USER;

  if($firstnamevalue) {
    $context['firstnamevalue'] = true;
  }
  $context['firstname'] = $firstnamevalue;
  return $OUTPUT->render_from_template('local_people/get_first_name_filter', $context);
}

/** 
* This Is 2'nd Filter Block For Lastname Filter    
*  
* @param string $lastnameop   
* @param string $lastnamevalue    
* @return This method return the last name filter block
*/
function get_last_name_filter($lastnameop = 'contain', $lastnamevalue = '') {
  global $CFG, $DB, $OUTPUT, $USER;

  if($lastnamevalue){
    $context['lastnamevalue'] = true;
  }
  $context['lastname'] = $lastnamevalue;
  return $OUTPUT->render_from_template('local_people/get_last_name_filter', $context);
}

/** 
* This Is 4'th Filter Block For City Filter    
*  
* @param string $cityop    
* @return This method return the city filter block
*/
function get_city_filter($cityop = 'contain') {
  global $CFG, $DB, $OUTPUT, $USER;
  $options = $DB->get_records_sql("SELECT city FROM {user} GROUP BY city");
  foreach($options as $option){
    $cities[$option->city] = $option->city;
  }

  $cities = array_filter($cities);
  $opt = array();
  foreach ($cities as $key) {
    $op['key'] = $key;
    $selectedperpage = '';
    if(!empty($cityop))
    {
      if(in_array($key,$cityop)){  
        $selectedperpage = 'selected';
       }
    }
    $op['selected'] = $selectedperpage;
    $opt[] = $op;
  }
  if($cityop) {
    $context['cityop'] = true;
  }
  $context['cityopt'] = $opt;
  return $OUTPUT->render_from_template('local_people/get_city_filter', $context);           
}

/** 
* This Is 5'th Filter Block For Country Filter    
*  
* @param string $countryop    
* @return This method return the country filter block
*/
function get_country_filter($countryop) {
  global $CFG, $DB, $OUTPUT, $USER; 
  $options = get_string_manager()->get_list_of_countries();
  if($countryop){
    $context['country'] = true;
  }

  foreach ($options as $key => $v) {
    $op['key'] = $key;
    $op['value'] = $v;
    $selectedperpage = '';
    if(!empty($countryop))
    {
      if(in_array($key,$countryop)){  
        $selectedperpage = 'selected';
      }
    }
    $op['selected'] = $selectedperpage;
    $opt[] = $op;
  }
  $context['countryopt'] = $opt;
  return $OUTPUT->render_from_template('local_people/get_country_filter', $context);           
}

/** 
* This Is 6'th Filter Block For Userfullname Filter    
*  
* @param string $lastnameop
* @param string $userfullnamevalue    
* @return This method return the userfullname filter block
*/
function get_userfullname_filter($lastnameop = 'contain', $userfullnamevalue = '') {
  global $CFG, $DB, $OUTPUT, $USER;
  if($userfullnamevalue) {
    $context['userfullnamevalue'] = true;
  }
  $context['userfullname'] = $userfullnamevalue;
  return $OUTPUT->render_from_template('local_people/get_userfullname_filter', $context);       
}


/** 
* This Is 7'th Filter Block For Email Filter    
*  
* @param string $emailop
* @param string $emailvalue    
* @return This method return the email filter block
*/
function get_email_filter($emailop = 'contain', $emailvalue = '') {
  global $CFG, $DB, $OUTPUT, $USER;
  if($emailvalue) {
    $context['emailvalue'] = true;
  }
  $context['email'] = $emailvalue;
  return $OUTPUT->render_from_template('local_people/get_email_filter', $context);      
}

/** 
* This Is 8'th Filter Block For Email Filter    
*  
* @param string $usernameop
* @param string $usernamevalue    
* @return This method return the email filter block
*/
function get_username_filter($usernameop = 'contain', $usernamevalue = '') {
  global $CFG, $DB, $OUTPUT, $USER;
  if($usernamevalue) {
    $context['usernamevalue'] = true;
  }              
  $context['username'] = $usernamevalue;
  return $OUTPUT->render_from_template('local_people/get_username_filter', $context);
}

/** 
* This Is 9'th Filter Block For Suspend Filter    
* 
* @param string $suspendedop    
* @return This method return the suspend filter block
*/
function get_suspended_filter($suspendedop = 'manual') {
  global $CFG, $DB, $OUTPUT, $USER, $SESSION;

  $options = array('1'=>get_string('selectyes', 'local_people'),'0'=>get_string('selectno', 'local_people'));
  if(!is_null($suspendedop)) {
    $context['suspendedvalue'] = true;
    //$context['unsuspendedvalue'] = false;
  }else if(isset ($SESSION->multifilter['unsuspended'])){
    $context['unsuspendedvalue'] = true;
  }else{
  }
  if( isset($SESSION->multifilter['unsuspended']) ){
    $SESSION->multifilter['unsuspended']->field = 'suspended';
    $SESSION->multifilter['unsuspended']->value = 0;
  }

  $checked1 = false;
  $checked2 = false;
  if( isset($SESSION->multifilter['unsuspended']) && $SESSION->multifilter['unsuspended']->value == 0 ){
    $checked1 = true;
  }elseif( isset($SESSION->multifilter['suspended']) && $SESSION->multifilter['suspended']->value == 1 ){
    $checked2 = true;
  }

  $input = widget::radio(get_string('no'), $checked1,'', 'unsuspended', false, array('value' => 0));        
  $input2 = widget::radio(get_string('yes'), $checked2,'', 'suspended', false, array('value' => 1));
  $context['input2'] = $input2;
  $context['input'] = $input;

  return $OUTPUT->render_from_template('local_people/get_suspended_filter', $context);
}


/** 
* This Is 10'th Filter Block For confirm Filter    
* 
* @param string $confirmedop    
* @return This method return the confirm filter block
*/
function get_confirmed_filter($confirmedop = 'yes') {
  global $CFG, $DB, $OUTPUT, $USER,$SESSION;

  $options = array('1'=>get_string('selectyes', 'local_people'),'0'=>get_string('selectno', 'local_people'));
  if(!is_null($confirmedop)) {
    $context['confirmedop'] = true;
  }elseif(isset($SESSION->multifilter['unconfirmed'])){
    $context['unconfirmed'] = true;
  }else{
  }

  if(isset($SESSION->multifilter['unconfirmed'])){
    $SESSION->multifilter['unconfirmed']->field = 'confirmed';
    $SESSION->multifilter['unconfirmed']->value = 0;
  }

  $checked1 = false;
  $checked2 = false;

  if( isset($SESSION->multifilter['unconfirmed']) && $SESSION->multifilter['unconfirmed']->value == 0 ){
    $checked1 = true;
  }elseif(isset($SESSION->multifilter['confirmed']) && $SESSION->multifilter['confirmed']->value == 1){
    $checked2 = true;
  }else{
    $checked2 = false;
    $checked1 = false;
  }

  $input2 = widget::radio(get_string('no'), $checked1,'unconfirmed', 'unconfirmed', false, array('value' => 0));        
  $input = widget::radio(get_string('yes'), $checked2,'confirmed', 'confirmed', false, array('value' => 1));
  $context['input2'] = $input2;
  $context['input'] = $input;

  return $OUTPUT->render_from_template('local_people/get_confirmed_filter', $context);    

}

/** 
* This Is 10'th Filter Block For systemrole Filter    
* 
* @param string $systemrole    
* @return This method return the systemrole filter block
*/
function get_systemrole_filter($system) { 
  global $CFG, $DB, $OUTPUT, $USER;
  $options =  enroll_display_roles(CONTEXT_SYSTEM);
  if($system) {
    $context['system'] = true;
  }

  $select = \theme_remui\widget::select($options, '', '', (string)$system, get_string('anyvalue', 'local_people'), 'systemroleop');
  $context['select'] = $select;
  return $OUTPUT->render_from_template('local_people/get_systemrole_filter', $context);             
}

/** 
* This Is 10'th Filter Block For courserole Filter    
* 
* @param string $courserole    
* @return This method return the courserole filter block
*/
function get_courserole_filter($courselist,  $role) {
  global $CFG, $DB, $OUTPUT, $USER;
  $options = enroll_display_roles(CONTEXT_COURSE); 
  if($courselist) {
    $context['courselistvalue'] = true;
  }

  $courses  = get_courses();
  $selector = array();

  foreach($courses as $key => $course){
    $op['key'] = $course->id;
    $op['value'] = $course->fullname;
    $opt[] = $op;
  }
  $context['selectoropt'] = $opt;
  $select = \theme_remui\widget::select($options, '', '', (string)$role, get_string('anyvalue', 'local_people'), 'courseroleop');
  $context['select'] = $select;

  return $OUTPUT->render_from_template('local_people/get_courserole_filter', $context);
}

/** 
* This Is 10'th Filter Block For firstaccessd Filter    
* 
* @param string $firstaccessdgt
* @param string $firstaccessdlt 
* @param string $checked     
* @return This method return the firstaccessd filter block
*/
function get_firstaccessd_filter($firstaccessdgt = '', $firstaccessdlt = '', $checked = '') {
  global $CFG, $DB, $OUTPUT, $USER;
    /**
    * Validate selection
    * @author Carlos Alcaraz
    * @since Apr 11/2018
    */
    if ( isset($SESSION->multifilter) && is_array($SESSION->multifilter) && isset( $SESSION->multifilter['firstaccessd'] ) && isset( $SESSION->multifilter['firstaccessd']->edited ) && $SESSION->multifilter['firstaccessd']->edited == "on" ) { $checked = 'on'; 
  }

  
  if($firstaccessdgt || $firstaccessdlt || $checked == 'on') {
    $context['firstaccessd'] = true;

  }
    //Datepicker's start here!
  $dateisafter = widget::datepicker(get_string('dateisafter', 'local_people'),
    $firstaccessdgt,
    'firstaccessdgt','firstaccessdgt',
    false,
    true,
    false,
    ['name_2' => 'firstaccessdlt', 'id_2' => 'firstaccessdlt','value_2' => $firstaccessdlt]);
  $context['dateisafter'] = $dateisafter;

  $checked = ($checked == 'on') ? true : false;
  
  $neveraccess = widget::checkbox(get_string('neveraccess', 'local_people'), $checked, '', 'neveraccess' );
  $context['neveraccesscheckbox'] = $neveraccess;

  return $OUTPUT->render_from_template('local_people/get_firstaccessd_filter', $context);
}

/** 
* This Is 10'th Filter Block For lastaccessed Filter    
* 
* @param string $lastaccessedgt
* @param string $lastaccessedlt 
* @param string $checked     
* @return This method return the lastaccessed filter block
*/
function get_lastaccessed_filter($lastaccessedgt = '', $lastaccessedlt = '', $checked = '') {
  global $CFG, $DB, $OUTPUT, $USER;
    /**
    * Validate selection
    * @author Carlos Alcaraz
    * @since Apr 11/2018
    */
    if ( isset($SESSION->multifilter) && is_array($SESSION->multifilter) && isset( $SESSION->multifilter['lastaccessed'] ) && isset( $SESSION->multifilter['lastaccessed']->edited ) && $SESSION->multifilter['lastaccessed']->edited == "on" ) { $checked = 'on'; }

    if($lastaccessedgt || $lastaccessedlt || $checked == 'on') {
      $context['lastaccessed'] = true;
      
    }

    $dateisafter = widget::datepicker(get_string('dateisafter', 'local_people'),
     $lastaccessedgt,
     'lastaccessedgt',
     'lastaccessedgt',
     false,
     true,
     false,
     ['name_2' => 'lastaccessedlt', 'id_2' => 'lastaccessedlt','value_2' => $lastaccessedlt]);
    $context['dateisafter'] = $dateisafter;

    $checked = ($checked == 'on') ? true : false;

    $neveraccess = widget::checkbox(get_string('neveraccess', 'local_people'), $checked, 'lastaccessid', 'neveraccess' );
    $context['neveraccesscheckbox'] = $neveraccess;
    return $OUTPUT->render_from_template('local_people/get_lastaccessed_filter', $context);         
  }

/** 
* This Is 10'th Filter Block For lastmodified Filter    
* 
* @param string $lastmodifiedgt
* @param string $lastmodifiedlt 
* @param string $checked     
* @return This method return the lastmodified filter block
*/
function get_lastmodified_filter($lastmodifiedgt = '', $lastmodifiedlt = '', $checked = '') {
  global $CFG, $DB, $OUTPUT, $USER;
    /**
    * Validate selection
    * @author Carlos Alcaraz
    * @since Apr 11/2018
    */
    if ( isset($SESSION->multifilter) && is_array($SESSION->multifilter) && isset( $SESSION->multifilter['lastmodified'] ) && isset( $SESSION->multifilter['lastmodified']->edited ) && $SESSION->multifilter['lastmodified']->edited == "on" ) { $checked = 'on'; }
    

    if($lastmodifiedgt || $lastmodifiedlt || $checked == 'on') {
      $context['lastmodified'] = true;
    }

    $dateisafter = widget::datepicker(get_string('dateisafter', 'local_people'),
      $lastmodifiedgt,
      'lastmodifiedgt',
      'lastmodifiedgt',
      false,
      true,
      false,
      ['name_2' => 'lastmodifiedlt', 'id_2' => 'lastmodifiedlt','value_2' => $lastmodifiedlt]);
    $context['dateisafter'] = $dateisafter;
    $checked = ($checked == 'on') ? true : false;
    $nevermodified = widget::checkbox(get_string('nevermodified', 'local_people'), $checked, '','nevermodified');
    $context['neveraccesscheckbox'] = $nevermodified;
    return $OUTPUT->render_from_template('local_people/get_lastmodified_filter', $context);         

  }

/** 
* This Is 10'th Filter Block For auth Filter    
* 
* @param string $authenticationop   
* @return This method return the auth filter block
*/
function get_auth_filter($authenticationop = 'manual') {
  global $CFG, $DB, $OUTPUT, $USER;
  $enableauths = get_enabled_auth_plugins();
  $options = array();

  foreach($enableauths  as $enableauth){
    $options[$enableauth] = $enableauth;
  }

  if($authenticationop) {
    $context['authenticationop'] = true;
  }
  $select = \theme_remui\widget::select($options, '', '', (string)$authenticationop, get_string('anyvalue', 'local_people'), 'authop');
  $context['select'] = $select;
  return $OUTPUT->render_from_template('local_people/get_auth_filter', $context);  
}

/** 
* Function For Validation name    
* 
* @param string $name   
* @return Returns name if in array
*/
function validate_name($name){
  $array = array (
    'firstname',
    'lastname',
    'userfullname',
    'email',
    'city',
    'country',
    'confirmed',
    'suspended',
    'unsuspended',
    'systemrole',
    'courserole',
    'firstaccess',
    'lastaccess',
    'neveraccess',
    'lastmodified',
    'timemodified',
    'nevermodified',
    'username',
    'auth'
  );

  if(in_array($name,$array)){
    return $name;
  } else {
    return false;
  }
}

/** 
* Function For get company user sql  
* 
* @param   
* @return Returns sql query for get company user
*/
function get_company_user(){
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
  if (!empty($SESSION->currenteditingcompany)) {
      $companyid = $SESSION->currenteditingcompany;
  } else if (!empty($USER->profile->company)) {
      $usercompany = company::by_userid($USER->id);
      $companyid = $usercompany->id;
  } else {
      $companyid = "";
  }
  //$systemcontext = context_system::instance();

    //Set the companyid
  // $companyid = iomad::get_my_companyid($systemcontext);

    // all companies?
  if (!empty($companyid)) {
    $companysql='';
    $company = new company($companyid);
    if ($parentslist = $company->get_parent_companies_recursive()) {
      $companysql = " AND c.id = :companyid AND u.id NOT IN (
      SELECT userid FROM {company_users}
      WHERE companyid IN (" . implode(',', array_keys($parentslist)) ."))";
    } else {
      $companysql = " AND cu.companyid = $companyid";
    }

    $sql = "SELECT CONCAT(cu.id, '-', u.id), u.*
    FROM {user} u, {company_users} cu
    WHERE cu.userid = u.id
    $companysql AND deleted <> 1";
    return $sql;                         
  }else{
  $sql = "SELECT CONCAT( u.id), u.*
  FROM {user} u
  WHERE deleted <> 1";
  return $sql; 
}
}

/** 
* Function For get user list
* 
* @param $filter
* @param $multifilter   
* @return Returns array of object for get user list
*/
function get_user_list($filter, $multifilter) { 
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
  if (!empty($SESSION->currenteditingcompany)) {
      $selectedcompany = $SESSION->currenteditingcompany;
  } else if (!empty($USER->profile->company)) {
      $usercompany = company::by_userid($USER->id);
      $selectedcompany = $usercompany->id;
  } else {
      $selectedcompany = "";
  }
  $sql = '';
  $countcondition = array();
  $result   = new stdClass();
  $countsql = 'SELECT count(*),id,firstname,email,lastname,lastaccess,suspended,confirmed FROM {user} u WHERE id > 1 AND deleted <> 1';
  $sql      = 'SELECT id,firstname,email,lastname,lastaccess,suspended,confirmed FROM {user} u WHERE id > 1 AND deleted <> 1'; 


  // $systemcontext = context_system::instance();
  // $companyid = iomad::get_my_companyid($systemcontext);
  if(!empty($selectedcompany)) {
    $sql = get_company_user();
  }

  $params = array();

    /**
    * Create array with filter types and associate key to compare with selected filters.
    * @author Yesid Valencia
    * @since Apr 10/2018
    */

    $arrayTypes = array(
      "textfilter"=>"value",
      "multipleselectfilter"=>"op",
      "courserole"=>"op",
      "systemrole"=>"op",
      "selectfilter"=>"op",
      "datefilter"=>"gt",
      "datefilter"=>"lt",
      "datefilterr"=>"sm"
    );

    foreach ($multifilter as $ffilter) {

      /**
      * Change validation to compare current filter with type of filter array so we can cover all type of filters
      * @author Yesid Valencia
      * @since Apr 10/2018
      */
      if ( $ffilter->type=='datefilter'|| trim($ffilter->{$arrayTypes[$ffilter->type]}) != "" && str_replace('\'','',$ffilter->{$arrayTypes[$ffilter->type]}) != "" && $ffilter->field != "filter-true" ) {

        //if ($ffilter->value == 1 && $ffilter->field = !'suspended') 
        {
          if(isset($ffilter->field)) {
            $sql      .= " AND ";
            $countsql .= " AND ";
          }
        }
        /**
        * Redefine datefilter neveraccess
        * @author Carlos Alcaraz
        * @since Apr 11/2018
        */
        if($ffilter->type == 'datefilterr' ) { 
          $ffilter->type = 'datefilter'; 
        }

        if (isset($ffilter->field) && $ffilter->type=='textfilter') { 
          $value = trim($ffilter->value);

          if ($ffilter->field == 'userfullname' ) {  
            $field = 'CONCAT(firstname," ", lastname)';
            $sql .= "$field LIKE CONCAT('%', :userfullname, '%')";
            $countsql .= "$field LIKE CONCAT('%', :userfullname, '%')";
            $params['userfullname'] = $value;
          } else {
            //if ($ffilter->value != 1) 
            {
              $field = validate_name($ffilter->field);
              $sql .= "u.$field LIKE CONCAT('%', :$field, '%')";
              $countsql .= "$field LIKE CONCAT('%', :$field, '%')";
              $params[$field]  = $value;
            }
          }

        }else if(isset($ffilter->field) && isset($ffilter->op) && $ffilter->type=='selectfilter') {
          $field = validate_name($ffilter->field);

          if($ffilter->field !=='auth') {
            $op = ($ffilter->op) ? " = 1" : " = 0";
            $sql .= "$field{$op}";
            $countsql .= "$field{$op}";
          }else {
            $sql .= "$field = :selection";
            $countsql .= "$field = :selection";
            $params['selection'] = $ffilter->op;
          }
        }

        if(isset($ffilter->field) && ($ffilter->type=='datefilterr' || $ffilter->type=='datefilter')) {

          if($ffilter->field == 'firstaccessd') {
            $field = 'firstaccess';
          }

          if($ffilter->field == 'lastaccessed') {
            $field = 'lastaccess';
          }

          if($ffilter->field == 'lastmodified') {
            $field = 'timemodified';
          }

          $field = validate_name($field);

          if ( $ffilter->edited == 'on' || $ffilter->access == 'on' ) {
            $sql .= " $field = 0";
            $countsql .= " $field = 0";

          }else {

            if(!is_null($ffilter->gt)) {
              $params["greater{$field}"] = $ffilter->gt;
              $sql .= " {$field} > UNIX_TIMESTAMP(STR_TO_DATE(:greater$field,'%m/%d/%Y'))";
              $countsql .= " {$field} > UNIX_TIMESTAMP(STR_TO_DATE(:greater$field,'%m/%d/%Y'))";
            }

            $sql = (!is_null($ffilter->lt)) ? $sql      .= ' AND ' : $sql;
            $countsql = (!is_null($ffilter->lt)) ? $countsql .= ' AND ' : $sql;

            if(!is_null($ffilter->lt)) {

              $params["less{$field}"] = $ffilter->lt;
              $sql .= " {$field} < UNIX_TIMESTAMP(DATE_ADD(STR_TO_DATE(:less$field,'%m/%d/%Y'),INTERVAL 1 DAY) )";
              $countsql .= " {$field} < UNIX_TIMESTAMP(DATE_ADD(STR_TO_DATE(:less$field,'%m/%d/%Y'),INTERVAL 1 DAY) )";
            }
          }
        }

        $invalues = array();

        if(isset($ffilter->field) && isset($ffilter->op) && $ffilter->type=='multipleselectfilter') {

          $field = validate_name($ffilter->field);
          foreach ($ffilter->ar as $key => $value) {
            $invalues[] = ":in$key$field";
            $params['in'.$key.$field]  = $value;
          }
          $invalues = implode(',', $invalues);
          $sql .= " $field IN({$invalues})";
          $countsql .= " $field IN({$invalues})";

        }
        if(isset($ffilter->field) && $ffilter->type=='systemrole') {
          $sql .= " u.id IN (SELECT userid FROM {role_assignments} a WHERE a.contextid=1 AND a.roleid= :roleid )";
          $countsql .= " u.id IN (SELECT userid FROM {role_assignments} a WHERE a.contextid=1 AND a.roleid= :roleid )";
          $params['roleid'] = $ffilter->op;
        }

        if(isset($ffilter->field) && $ffilter->type=='courserole' && !empty($ffilter->ar)) {
          $field = validate_name($ffilter->field);

          foreach ($ffilter->ar as $key => $value) {
            $invalues[] = ":in$key$field";
            $params['in'.$key.$field]  = $value;
          }

          $invalues = implode(', ', $invalues);

          $query = ($ffilter->role) ? "AND a.roleid = :courserole" : '';

          $query = " u.id IN (SELECT userid FROM {role_assignments} a 
          INNER JOIN {context} b ON a.contextid=b.id 
          INNER JOIN {course} c ON b.instanceid=c.id WHERE b.contextlevel=50 $query AND c.id IN ($invalues))";

          if($ffilter->role) {
            $params['courserole'] = $ffilter->role;
          }

          $sql .= $query;
          $countsql .= $query;
        } 
      }
    }

    $search = optional_param('search', null, PARAM_TEXT);

    if($search) {
      $params['search'] = $search;
      $fullname = 'CONCAT(firstname," ", lastname)';
      $sql .= " AND CONCAT(firstname,email,lastname,username,".$fullname.") LIKE CONCAT('%', :search, '%')";
      $countsql .= " AND CONCAT(firstname,email,lastname,username,".$fullname.") LIKE CONCAT('%', :search, '%')";
    }

    $count  = $DB->count_records_sql($countsql, $params);

    $result->count = $count;

    if (isset($filter->sort) && $filter->sort) {
      $sql .= " ORDER BY $filter->sort $filter->order";
    } else {
      $sql .= " ORDER BY firstname ASC";
    }

    if(empty($selectedcompany)) {
      $companySQL = $sql;
    }
    
    $companyids = optional_param('company', null, PARAM_ALPHANUM);
    if( !(isset($companyids) && !empty($companyids))  ){

      if ( (isset($filter->page) && $filter->page >= 0) ) {

        /**
        * Added validation to prevent than the pagination show an error when it is a number greater than the number of users  
        * @author Jorge M.
        * @since July 19 of 2017
        * @remui
        */

        if($filter->userperpage >= $count ){
          $filter->userperpage  = $count ;
          $filter->page = 0 ;
        } 
        $limitfrom = $filter->page*$filter->userperpage;
        $limitnum = $filter->userperpage;
      }
    }

    /**
    * added validations and new structure to prevent the pagination to display wrong  
    * @author Jorge M.
    * @since July 24 of 2017
    * @remui
    */
    if(empty($selectedcompany)) {
      $information = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
      if($information == null) {
        $sql = "";
        $sql  = 'SELECT id,firstname,lastname,email,lastaccess,suspended FROM {user} WHERE id > 1 AND deleted <> 1';

        $sql .= " ORDER BY firstname ASC";
        $filter->page = 0;
        $limitfrom = $filter->page*$filter->userperpage;
        $limitnum = $filter->userperpage;
        $data = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
      }else{
        $data = $information;
      }
    }  
    if(isset($selectedcompany) && !empty($selectedcompany)){
      $data = $DB->get_records_sql($sql, $params);
      $result->count = count($data); 
      $result->data = array_slice($data,$filter->page*$filter->userperpage, $filter->userperpage );
    }elseif (isset($companyids) && !empty($companyids)) {
      $users = $DB->get_records_sql($companySQL, $params);
      foreach($users as $key => $value ) {
        $condition = null;
        $usercompany = company::get_company_byuserid($value->id);
        $condition = is_object($usercompany) ? $usercompany->id !== $companyids : true;
        $condition = ($companyids == 'NONE')  ? $usercompany != false : $condition;
        $condition = ($companyids == 'ALL')   ? $usercompany == false : $condition;

        if($condition){
          unset($data[$key]);
          $result->count = $result->count - 1;
        }

        $result->data = array_slice($data,$filter->page*$filter->userperpage, $filter->userperpage );
      }
    }else{
      $result->data = $data;
    }

    return $result;
  }

/** 
* Function For display roles
* @param $param for contextlevel  
* @return Returns filter clear message
*/
function enroll_display_roles($param) {
  $roles   = array();
  $options = array();

  $rolescontext = get_roles_for_contextlevels($param);

  foreach ($rolescontext as $rolecontxt) {
    $role    = enroll_get_role_name($rolecontxt);
    $roles[] = $role;
  }

  foreach ($roles as $role) {
    $role->name = role_get_name($role);
    $options[$role->id] = $role->name;
  }

  return $options;
}

/**
 * Returns an array with the name and id of the roles
 * @author Sergio A.
 * @since  Sep 13 of 2018
 * @remui
 * 
*/
function enroll_display_roles_bulk() {
  $roles = array();
  $rolescontext = get_roles_for_contextlevels(CONTEXT_COURSE);

  foreach ($rolescontext as $rolecontxt) {
    $role = enroll_get_role_name($rolecontxt);
    $role->name = role_get_name($role);
    $roles[$role->id] = $role->name;
  }
  return $roles;
}

/**
 * Returns an array with the name and id of the courses
 * @author Sergio A.
 * @since  Sep 13 of 2018
 * @remui
 * 
*/
function get_courses_display() {
  $courses = get_courses();
  $list = array();
  foreach ($courses as $key => $course) {
    if($course->id ==1)continue;
    $list[$course->id] = $course->fullname;   
  }
  return $list;     
}

/** 
* Function For display role name
* @param $param for contextlevel  
* @return enroll role name
*/
function enroll_get_role_name($roleid) {
  global $DB;
  $sql = 'SELECT * FROM {role} WHERE id = ?';
  return $DB->get_record_sql($sql,[$roleid]);
}

/** 
* Function For count user courses
* @param $id course id  
* @return enroll courses and complete courses
*/
function count_user_courses($id) {
  $courses  = 0;
  $complete = 0;

  if($student_course_arry = enrol_get_users_courses($id, true, null, 'visible DESC,sortorder ASC')) {

    foreach($student_course_arry as $value) {
      $course = new core_course_list_element($value);
      $info   = new completion_info($course);

      if($info->is_course_complete($id)){
        $complete++;
      }

      $courses++;
    }
  }

  return array( 'enroll' => $courses, 'complete' => $complete);
}

/** 
* Function For bulk action
* @param $actionName
* @param $arrayValues   
* @return redirect to user action page
*/
function bulk_action($actionName, $arrayValues) {
  global $CFG, $SESSION;
  $SESSION->bulk_users = array();

  foreach ($arrayValues as $key => $value) {
    $SESSION->bulk_users[$value] = $value;
  }

  $url = $CFG->wwwroot."/local/people/actions/user_bulk_$actionName.php";
  redirect($url);
}

/**
 * Get all the cohorts defined anywhere in system.
 *
 * The function assumes that user capability to view/manage cohorts on system level
 * has already been verified. This function only checks if such capabilities have been
 * revoked in child (categories) contexts.
 *
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function cohort_get_all_cohorts_people() {
  global $DB, $CFG;
  require($CFG->dirroot.'/cohort/lib.php');

  $fields = "SELECT c.*, ".context_helper::get_preload_record_columns_sql('ctx');
  $countfields = "SELECT COUNT(*)";
  $sql = " FROM {cohort} c
  JOIN {context} ctx ON ctx.id = c.contextid ";
  $params = array();
  $wheresql = '';

  if ($excludedcontexts = cohort_get_invisible_contexts()) {
    list($excludedsql, $excludedparams) = $DB->get_in_or_equal($excludedcontexts, SQL_PARAMS_NAMED, 'excl', false);
    $wheresql = ' WHERE c.contextid '.$excludedsql;
    $params = array_merge($params, $excludedparams);
  }
  $wheresql .= 'AND c.visible = 1';
  $totalcohorts = $allcohorts = $DB->count_records_sql($countfields . $sql . $wheresql, $params);

  $order = " ORDER BY c.name ASC, c.idnumber ASC";
  $cohorts = $DB->get_records_sql($fields . $sql . $wheresql . $order, $params);

    // Preload used contexts, they will be used to check view/manage/assign capabilities and display categories names.
  foreach (array_keys($cohorts) as $key) {
    context_helper::preload_from_record($cohorts[$key]);
  }

  return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts' => $allcohorts);
}

/**
 * Ajax function to get the form of the cohort list
 *
 * @author  Daniel C
 * @since 13-07-2018
 * @param1  array $users List of user ids
 * @return  array Full form and data to be printed
 * @remui
 */
function get_cohortadd_form(array $users): array{
  global $DB,$OUTPUT,$PAGE;

  $PAGE->set_context(context_system::instance());
    // Iterate users to know if they exist
  foreach ($users as $key => $userid) {
    $record = $DB->get_record('user',['id' => $userid]);
    if(!$record){
      unset($users[$key]);
    }
  }

  $q = true;
  $output = '';
  $message = '';
  $cohorts = cohort_get_all_cohorts_people();
  if (empty($users)) {
    $q = false;
    $message = get_string('invalid_users','local_people');
  }elseif($cohorts['totalcohorts'] <= 0){
    $q = false;
    $message = get_string('invalid_cohorts','local_people');
  }else{
    $users = implode(',', $users);
      // Cohort list
    $cohortlist = array();
    foreach ($cohorts['cohorts'] as $key => $value) {
      $cohortlist[$key] = $value->name;
    }

    $cohortlist = \theme_remui\widget::select2(get_string('cohortlist','local_people'), $cohortlist, 'cohortlist', '-1', 'cohortlist', true);

    $hash = [
      'users' => $users,
      'cohortlist' => $cohortlist
    ];

    $output = $OUTPUT->render_from_template('local_people/get_cohortadd_form', $hash);
  }

  return ['form' => $output, 'message' => $message ,'q' => $q, 'title' => get_string('bulk_add_cohort', 'local_people')];
}

/**
 * function to get the form to send a message
 *
 * @author  Daniel C
 * @since 13-07-2018
 * @param1  array $users List of user ids
 * @return  string Full form to be printed
 * @remui
 */
function get_message_form( array $users ): array{
  global $DB;

    // Iterate users to know if they exist
  foreach ($users as $key => $userid) {
    $record = $DB->get_record('user',['id' => $userid]);
    if(!$record){
      unset($users[$key]);
    }
  }

  $q = true;
  $message = '';
  if (empty($users)) {
    $q = false;
    $message = get_string('invalid_users','local_people');
  }
  return ['form' => '', 'message' => $message ,'q' => $q, 'title' => get_string('bulk_send_message', 'local_people')];
}

/**
 * function to get the form to send a message
 *
 * @author  Daniel C
 * @since 13-07-2018
 * @param1  array $users List of user ids
 * @return  string Full form to be printed
 * @remui
 */
function get_message_form_html(): user_message_form{
  global $CFG, $DB;
  require_once($CFG->libdir.'/adminlib.php');
  require_once($CFG->dirroot.'/message/lib.php');
  require_once('classes/user_message_form.php');

  $msg = optional_param('msg', '', PARAM_CLEANHTML);
  $confirm = optional_param('confirm', 0, PARAM_BOOL);

  require_login();
  admin_externalpage_setup('userbulk');
  require_capability('moodle/site:readallmessages', context_system::instance());

  if (empty($CFG->messaging)) {
    print_error('messagingdisable', 'error');
  }

    //TODO: add support for large number of users

  if ($confirm and ! empty($msg) and confirm_sesskey()) {
    foreach ($users as $user) {
        //TODO we should probably support all text formats here or only FORMAT_MOODLE
        //For now bulk messaging is still using the html editor and its supplying html
        //so we have to use html format for it to be displayed correctly
      message_post_message($USER, $user, $msg, FORMAT_HTML);
    }
  }

  $msgform = new user_message_form('user_bulk_message.php',null,'post','',['class' => 'hidden']);
  $q = true;
  if ($msgform->is_cancelled()) {
    redirect($return);
  } else if ($formdata = $msgform->get_data()) {
    $options = new stdClass();
    $options->para = false;
    $options->newlines = true;
    $options->smiley = false;

    $msg = format_text($formdata->messagebody['text'], $formdata->messagebody['format'], $options);

    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,' . $DB->sql_fullname() . ' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
      echo $OUTPUT->box($msg, 'boxwidthnarrow boxaligncenter generalbox', 'preview'); //TODO: clean once we start using proper text formats here

      $formcontinue = new single_button(new moodle_url('user_bulk_message.php', array('confirm' => 1, 'msg' => $msg)), get_string('yes')); //TODO: clean once we start using proper text formats here
      $formcancel = new single_button(new moodle_url($SESSION->return), get_string('no'), 'get');
      echo $OUTPUT->confirm(get_string('confirmmessage', 'bulkusers', $usernames), $formcontinue, $formcancel);
      echo $OUTPUT->footer();
      die;
    }
    return $msgform;
  }

/**
 * function to save the users in the cohort selected
 *
 * @author  Daniel C
 * @since 13-07-2018
 * @param1  array $users List of user ids
 * @return  string Full form to be printed
 * @remui
 */
function save_cohortadd(array $data):array {
  global $DB, $CFG;
  require($CFG->dirroot.'/cohort/lib.php');
  $response = array('error' => true,'message' => get_string('error_cohortadd','local_people'));
  $cohortid = (int)$data['input_value'];
  if(!empty($data['users']) && $cohort = $DB->get_record('cohort', array('id'=>$cohortid), '*', MUST_EXIST)){
    $response['error'] = false;
    $response['message'] = get_string('success_cohortadd','local_people');
    foreach ($data['users'] as $uid) {
      if($user = $DB->get_record('user',['id' => $uid])){
        cohort_add_member($cohort->id, $user->id);
      }
    }
  }
  return $response;
}

/**
 * function to save|send the form to send a message
 *
 * @author  Daniel C
 * @since 13-07-2018
 * @param1  array $users List of user ids
 * @return  string Full form to be printed
 * @remui
 */
function save_message(array $data):array {
  global $DB, $CFG, $USER;
  require_once($CFG->dirroot.'/message/lib.php');
  require_once('classes/user_message_form.php');

  $response = array('error' => true,'message' => get_string('error_message','local_people'));
  $message = $data['input_value'];
  if(!empty($message)){
    $response['error'] = false;
    $response['message'] = get_string('success_message_users','local_people');
    foreach ($data['users'] as $uid) {
      if($user = $DB->get_record('user',['id' => $uid])){
          //TODO we should probably support all text formats here or only FORMAT_MOODLE
          //For now bulk messaging is still using the html editor and its supplying html
          //so we have to use html format for it to be displayed correctly
        message_post_message($USER, $user, $message, FORMAT_HTML);
      }
    }
  }
  return array();
}