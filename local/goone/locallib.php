<?php

/**
 * This is a custom code to centralize functions at one place
 * @author Kalpana Patil
 * @author kalpana.t@paradisosolutions.com
 * @ticket #1683
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_once $CFG->dirroot . '/course/lib.php';
function get_go1_onetime_loginurl(){
    // get go one auth token first 
    $goone_config = get_config('local_goone');
    $url = "https://auth.go1.com/oauth/token"; 
    $return=array('url'=>'');
    // call api endpoint with data
    //set the url, number of POST vars, POST data
    if($goone_config->enable_go1 && $goone_config->go1clientid && $goone_config->go1clientsecretkey){
        $token_data=get_goone_token(); 
        if($token_data['status'] == 'ok'){
            $token=$token_data['token'];
            $url= 'https://api.go1.com/v2/me/login?redirect_url=/r/app/content-selector?embedded=true';
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
            curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_ENCODING , ''); 
            curl_setopt($ch, CURLOPT_MAXREDIRS , 10);
            curl_setopt($ch, CURLOPT_TIMEOUT , 0);                                                             
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$token
            ),                                                                       
            );
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($result) {
                $result_arr = json_decode($result);
            }
            //close connection
            curl_close($ch);
            if($httpcode == '200' && isset($result_arr->url) && $result_arr->url) {
                $return['status'] = 'ok';
                $return['url'] = $result_arr->url;
            }
            elseif(isset($result_arr->code)){
                $return['status'] = 'error';
                $return['message']=$result_arr['message'];
            }
        } 
        else{
            $return['status']=$token_data['status'];
            $return['message']=$token_data['message'];
        }  
        return $return;   
    }
}

function get_goone_token()
{
    $goone_config = get_config('local_goone');
    $url = "https://auth.go1.com/oauth/token";
    // call api endpoint with data
    $return = array();
    if($goone_config->enable_go1 && $goone_config->go1clientid && $goone_config->go1clientsecretkey){
        $ch = curl_init();
        $post_data = array('grant_type' => 'client_credentials','client_id' => trim($goone_config->go1clientid), 'client_secret' => trim($goone_config->go1clientsecretkey));

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
        curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_ENCODING , ''); 
        curl_setopt($ch, CURLOPT_MAXREDIRS , 10);
        curl_setopt($ch, CURLOPT_TIMEOUT , 0);                                                             
        /*curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            /'Content-Type: application/x-www-form-urlencoded'
        ),                                                                       
        );*/
        //execute post
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($result) {
            $result_arr = json_decode($result, 1);
        }
       //close connection
        curl_close($ch);
        if(isset($result_arr['error']) && $result_arr['error']) {
            $return['status'] = 'error';
            $return['message']=$result_arr['message'];
        }
        elseif($httpcode == '200' && isset($result_arr['access_token'])){
            $return['status'] = 'ok';
            $return['token'] = $result_arr['access_token'];
        }
    }
    return $return;
}

function get_go1_learning_object_mylib(){
    global $DB;
    $goone_config = get_config('local_goone');
    $return=array();
    // call api endpoint with data
    if($goone_config->enable_go1 && $goone_config->go1clientid && $goone_config->go1clientsecretkey){
        $limit=50; $offset=0; $newOffset=0;
        $sql = "SELECT * FROM {goone_learning_sync_limits}";
        $record=$DB->get_record_sql($sql);
        if($record){
            $offset=$record->offset;
            $newOffset=$offset;
        }
        $url='https://api.go1.com/v2/learning-objects?limit='.$limit.'&offset='.$offset;
        $token_data=get_goone_token(); 
        if($token_data['status'] == 'ok'){
            $token=$token_data['token'];
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
            curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_ENCODING , ''); 
            curl_setopt($ch, CURLOPT_MAXREDIRS , 10);
            curl_setopt($ch, CURLOPT_TIMEOUT , 0);                                                             
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$token
            ),                                                                       
            );
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($result) {
                $result_arr = json_decode($result);
            }
            if($httpcode == '200' && count($result_arr->hits) > 0){
                foreach($result_arr->hits as $l_object){   
                    $sql = "SELECT * FROM {goone_learning_object} where course_id='$l_object->id'";                    
                    $courseRecord=$DB->get_record_sql($sql);
                    $data = new stdClass();
                    $data->course_id  =  $l_object->id;
                    $data->course_title = $l_object->title;
                    $data->image = $l_object->image;
                    $data->course_description=$l_object->description;
                    
                    if(!$courseRecord){                        
                        $data->created_on = time();
                        $data->updated_on = time();                        
                        $data->course_sync = 0; 
                        $DB->insert_record('goone_learning_object', $data);
                    } /*
                    else if($courseRecord->id == 0){
                        $data->id = $courseRecord->id;
                        $data->updated_on = time();
                        $DB->update_record('goone_learning_object', $data);
                    }*/					
					$newOffset++;
                }
                $sync_data = new stdClass();                
                $total_courses=$sync_data->total_courses=$result_arr->total;
				if($newOffset < $limit) {$sync_data->offset = $offset;}
				else if($limit == $newOffset) {$sync_data->offset = $limit;}
                else if($newOffset <  $total_courses) { 
                    $sync_data->offset = $newOffset;
                }
                $sync_data->updated_on = time();
                if($record){
                    $sync_data->id='1';
                    $DB->update_record('goone_learning_sync_limits', $sync_data);
                }
                else{
                    $DB->insert_record('goone_learning_sync_limits', $sync_data);
                }
                $hits=count($result_arr->hits);
                //  total records > 50 and hits != 50  then do nothing
                //  total records > 50 and hits == 50 and offset < total records then call synch objects API 
                //if($total_courses >= $limit && $hits == $limit && $offset < $total_courses){ 
                if($newOffset <  $total_courses){
                    get_go1_learning_object_mylib();                    
                }
            }		  
        } 
        if($record){
            $sync_data = new stdClass();
            $sync_data->offset = 0;
            $sync_data->updated_on = time();
            $sync_data->id='1';
            $DB->update_record('goone_learning_sync_limits', $sync_data);
        }
    
        $return['total_courses']=$DB->count_records('goone_learning_object', array('course_sync'=> 0));
        $return['course_title'] ='No courses to sync';
        if($return['total_courses'] > 0)
        {
            $return['course_title']=$DB->get_field('goone_learning_object', 'course_title', array('course_sync' => 0));
        }   

        return $return;
        exit;
		//return download_course_scorm();  
    } 
}
function download_single_course_scorm()
{
    global $DB,$CFG,$USER;
    $goone_config = get_config('local_goone');
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->libdir.'/completionlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot.'/course/modlib.php');
    require_once("$CFG->dirroot/mod/scorm/lib.php");
    require_once("$CFG->dirroot/mod/scorm/locallib.php");
    // call api endpoint with data
    $course_count=array('course_title'=>'');
    if($goone_config->enable_go1 && $goone_config->go1clientid && $goone_config->go1clientsecretkey){
        $records = $DB->get_record_select('goone_learning_object','course_sync = ? ', array('0'));
        $cnt=0;
        if($records){
            $token_data=get_goone_token(); 
            if($token_data['status'] == 'ok'){
                $token=$token_data['token'];
                $learning_obj=$records;
                $lo_id=$learning_obj->course_id;
                $imagepath='';
                $title=preg_replace('/[^A-Za-z0-9\-]/', '', $learning_obj->course_title);
                $filename=substr(preg_replace('/\s+/', '_', $title ),0,50).'_'.time().'.zip';
                if (!is_dir($CFG->dataroot.'/trashdir/zip')){ 
                    mkdir($CFG->dataroot.'/trashdir/zip', 0777);
                }
                $filepath=$CFG->dataroot.'/trashdir/zip/'.$filename;
                    
                $imageURLArray = explode('/',$learning_obj->image);
                $downloadFileName=$imageURLArray[count($imageURLArray)-1];
                if($downloadFileName) $imagepath=$CFG->dataroot.'/trashdir/zip/'.$downloadFileName;
                
                $shortname=preg_replace('/\s+/', '_', $title );
                $sql_course = "SELECT * FROM {course} where shortname ='$shortname'";
                $course_record=$DB->get_record_sql($sql_course);
                $cflag=1;
                if($course_record){
                    $gid=$DB->get_field('goone_learning_object', 'course_id', array('plms_id' => $course_record->id));
                    if(trim($gid) == trim($lo_id)) { $cflag=0; }
                    else {
                        $ccnt=1;
                        $course_cnt = $DB->get_record_sql ('SELECT count(id) cnt FROM {course} WHERE shortname like(?)', array ('%'.$shortname.'%') );
                        if($course_cnt) $ccnt=$course_cnt->cnt;
                        $shortname=$shortname.'_'.$ccnt;
                    }
                }
                if($cflag){
                    $url='https://api.go1.com/v2/learning-objects/'.$lo_id.'/scorm';
                    $ch = curl_init(); 
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
                    curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
                    curl_setopt($ch, CURLOPT_ENCODING , ''); 
                    curl_setopt($ch, CURLOPT_MAXREDIRS , 10);
                    curl_setopt($ch, CURLOPT_TIMEOUT , 0);                                                             
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: Bearer '.$token
                    ),                                                                       
                    );
                    $result = curl_exec($ch);
                    curl_close($ch); //$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if(!isJSON($result)) { 
                        //Save the scorm at temp directory
                        file_put_contents($filepath, $result);
                        if($imagepath)
                            file_put_contents($imagepath, file_get_contents($learning_obj->image));
                        if(filesize($filepath) > 0) {
                            $defaultcategory = $DB->get_field_select('course_categories', "MIN(id)", "parent=0");                        
                            //create course for this scorm
                            $course = new \stdClass();
                            $course->fullname =$learning_obj->course_title;
                            $course->shortname =  $shortname;
                            $course->description = $learning_obj->course_description;
                            $format = get_config('moodlecourse', 'format');
                            $course->format = $format;                     
                            $course->visible = 1;
                            $course->category = $defaultcategory;
                            $course->summary = $learning_obj->course_description;
                            $course->summaryformat = FORMAT_HTML;
                            $course->startdate = time();
                            $course->numsections = 2; //get_config('moodlecourse', 'numsections');
                            $course->enablecompletion = 1; 
                            $newcourse = create_course($course);
                            $cid=$newcourse->id;
                            $c_context = context_course::instance($cid, MUST_EXIST);
                            //add course image
                            $fs = get_file_storage();
                            if(file_exists($imagepath)){
                                $file_record = array('contextid' => $c_context->id, 'component'=>'course', 'filearea'=>'overviewfiles',
                                'itemid'=>0, 'filepath'=>'/', 'filename'=> $downloadFileName);
                                $fs->create_file_from_pathname($file_record, $imagepath);
                                unlink($imagepath);
                            }
                            //$course->id=$course_record->id;$newcourse = update_course($course);                            
                            if($cid){
                                // create scorm activity for course
                                $section = 1;
                                $add='scorm';       
                                $courseObj = $DB->get_record('course', array('id'=>$cid), '*', MUST_EXIST);                             
                                list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($courseObj, $add, $section);

                                // create user context and add scorm as draft file 
                                $tempfile = $filepath;
                                $fs = get_file_storage();
                                $draftitemid = rand(1, 999999999);
                                $user_context = context_user::instance($USER->id);
                                $filerecord = array('contextid'=>$user_context->id, 'component'=>'user', 'filearea'=>'draft', 'itemid'=> $draftitemid, 'filepath'=>'/', 'filename'=> $filename, 'timecreated'=>time(), 'timemodified'=>time(), 'userid'  => $USER->id); 
                                $storedfile = $fs->create_file_from_pathname($filerecord, $tempfile);                        
                                //$storedfile = $fs->create_file_from_string($filerecord, $result);
                    
                                $fromform = new \stdClass();
                                $fromform->name = $learning_obj->course_title;
                                $fromform->showdescription = 0;
                                $fromform->scormtype = SCORM_TYPE_LOCAL;
                                $fromform->packagefile = $draftitemid;
                                $fromform->width= 100;
                                $fromform->height = 500;
                                $fromform->displayactivityname = 1;
                                $fromform->redirecturl = '../mod/scorm/view.php?id=';
                                $fromform->course = 60;
                                $fromform->coursemodule = 0;
                                $fromform->section = 1;
                                $fromform->module= $module->id;
                                $fromform->modulename = 'scorm';
                                $fromform->instance = 0;
                                $fromform->add = 'scorm';
                                $fromform->visible = 1 ;
                                $fromform->reference = $storedfile->get_filename();
                                $fromform->intro = '';
                                $fromform->introformat = 1;
                                $fromform->maxgrade = 100;
                                $fromform->maxattempt = 0;
                                $fromform->grademethod = 1;
                                $fromform->hidetoc = 3;
                                $fromform->skipview = 0;
                                $fromform->hidebrowse = 1;
                                $fromform->displaycoursestructure = 0;
                                $fromform->lastattemptlock= 0;
                                $fromform->masteryoverride = 0;
                                $fromform->completionunlocked = 1;
                                $fromform->completion = 2;
                                $fromform->completionview = 1;
                                $fromform->mustbecompleted = 1;
                                $fromform->completionscoredisabled = 1;
                                $fromform = add_moduleinfo($fromform, $courseObj);
                                
                                //update course sync
                                $updata = new stdClass();
                                $updata->id = $learning_obj->id;
                                $updata->updated_on = time();
                                $updata->course_sync = 1;
                                $updata->plms_id=$cid;
                                $DB->update_record('goone_learning_object', $updata);
                            }
                        }                        
                    }
                }
                else{
                    $cid=$course_record->id;
                }
                /*if(file_exists($filepath)){ 
                    //unlink($filepath);
                }*/   
                $course_count['course_title']=$DB->get_field('goone_learning_object', 'course_title', array('course_sync' => 0));
            }
            return $course_count;
        }        
    }
	return $course_count;	
}

function isJSON($string){
    return (is_string($string) && is_array(json_decode($string, true))) /*&& (json_last_error() == JSON_ERROR_NONE)*/ ? true : false;
}

/**
 * Event observer for local go1 plugin.
 *
 * @package    local_goone
 * @copyright  2022 Kalpana Patil Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function course_deleted(\core\event\course_deleted $event) {
    global $DB;
    $courseid = $event->objectid;
    $params['plms_id'] = $courseid;
    $DB->delete_records_select('goone_learning_object', 'plms_id = :plms_id', $params);
    return true;
}