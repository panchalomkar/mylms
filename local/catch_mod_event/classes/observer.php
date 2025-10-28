<?php
// defined('MOODLE_INTERNAL') || die();

// require_once($CFG->dirroot.'/local/catch_mod_event/lib.php');

// class local_catch_mod_event_observer {

//     function add_mod_event(\core\event\course_module_created $event) {
//         global $DB, $USER, $CFG;
//         $data = $event->get_data();

//         if($data['other']['modulename'] == 'bigbluebuttonbn'){
            
//             require_once($CFG->dirroot.'/mod/attendance/lib.php');
//             require_once($CFG->dirroot.'/mod/attendance/locallib.php');
    
//             $modid = $DB->get_field('modules', 'id', ['name'=>'attendance']);
//             $sql = 'SELECT
//                         att.id AS id,
//                         cm.id AS cmid
//                     FROM
//                         {attendance} att
//                         JOIN {course_modules} cm ON cm.instance = att.id
//                     WHERE
//                         att.name = :attname
//                         AND cm.deletioninprogress = 0
//                         AND cm.course = :course
//                         AND cm.module = :module';
    
//             $bbattid = $DB->get_record_sql($sql, ['course'=>$data['courseid'], 'attname'=>'Attendance', 'module'=>$modid]);
//             if(!$bbattid){
//                 $modinfo = add_att_mod($modid, $data['courseid']);
//                 $bbattid = $modinfo->coursemodule;
//             } else {
//                 $bbattid = $bbattid->cmid;
//             }
//             $course = $DB->get_record('course', array('id' => $data['courseid']), '*', MUST_EXIST);
//             $pageparams->action = 1;
//             $cm     = get_coursemodule_from_id('attendance', $bbattid, 0, false, MUST_EXIST);
//             $att    = $DB->get_record('attendance', array('id' => $cm->instance), '*', MUST_EXIST);
            
//             require_login($course, true, $cm);
    
//             $context = context_module::instance($cm->id);
//             if (!has_capability('mod/attendance:manageattendances', $context)){
//                 return;
//             }
    
//             $att = new mod_attendance_structure($att, $cm, $course, $context, $pageparams);
    
//             $bbbdata = $DB->get_record('bigbluebuttonbn', ['id'=>$data['other']['instanceid']]);
//             $sec = ($bbbdata->closingtime - $bbbdata->openingtime);
//             $session = new stdClass();
//             $session->sessdate = $bbbdata->openingtime;
//             $session->duration = $sec;
//             $session->description = trim($data['other']['name']);
//             $session->descriptionformat = 1;
//             $session->calendarevent = 1;
//             $session->timemodified = time();
//             $session->studentscanmark = 0;
//             $session->autoassignstatus = 0;
//             $session->automark = 0;
//             $session->automarkcompleted = 0;
//             $session->absenteereport = 1;
//             $session->includeqrcode = 0;
//             $session->statusset = 0;
//             $session->groupid = 0;
    
//             $sessions = array($session);
//             $att->add_sessions($sessions);
//         }
//     }

//     function update_mod_event(\core\event\course_module_updated $event){
//         global $DB, $USER, $CFG;
//         $data = $event->get_data();

//         if($data['other']['modulename'] == 'bigbluebuttonbn'){
//             require_once($CFG->dirroot.'/mod/attendance/lib.php');
//             require_once($CFG->dirroot.'/mod/attendance/locallib.php');
    
//             $modid = $DB->get_field('modules', 'id', ['name'=>'attendance']);
//             $sql = 'SELECT
//                         att.id AS id,
//                         cm.id AS cmid
//                     FROM
//                         {attendance} att
//                         JOIN {course_modules} cm ON cm.instance = att.id
//                     WHERE
//                         att.name = :attname
//                         AND cm.deletioninprogress = 0
//                         AND cm.course = :course
//                         AND cm.module = :module';
    
//             $bbattid = $DB->get_record_sql($sql, ['course'=>$data['courseid'], 'attname'=>'Attendance', 'module'=>$modid]);
//             if($bbattid->cmid < 1){
//                 return;
//             }
//             $course = $DB->get_record('course', array('id' => $data['courseid']), '*', MUST_EXIST);
//             $pageparams->action = 2;
//             $cm     = get_coursemodule_from_id('attendance', $bbattid->cmid, 0, false, MUST_EXIST);
//             $att    = $DB->get_record('attendance', array('id' => $cm->instance), '*', MUST_EXIST);
            
//             require_login($course, true, $cm);
    
//             $context = context_module::instance($cm->id);
//             if (!has_capability('mod/attendance:manageattendances', $context)){
//                 return;
//             }
    
//             $att = new mod_attendance_structure($att, $cm, $course, $context, $pageparams);
    
//             $bbbdata = $DB->get_record('bigbluebuttonbn', ['id'=>$data['other']['instanceid']]);
//             $session = $DB->get_record('attendance_sessions', ['attendanceid'=>$bbattid->id, 'description'=>trim($data['other']['name'])]);
//             if($session->id < 1){
//                 return;
//             }
//             $session->sessiondate = strtotime(date('m/d/Y', $bbbdata->openingtime));
//             $session->sestime = array(
//                 'starthour' => date('H', $bbbdata->openingtime),
//                 'startminute' => date('i', $bbbdata->openingtime),
//                 'endhour' => date('H', $bbbdata->closingtime),
//                 'endminute' => date('i', $bbbdata->closingtime)
//             );
//             $session->statusset = 0;

//             $session->sdescription = array(
//                 'text' => $data['other']['name'],
//                 'format' => 1,
//                 'itemid' => rand(100000000,999999999)
//             );
//             $session->timemodified = time();
//             $att->update_session_from_form_data($session, $session->id);
//         }
//     }
// }

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/catch_mod_event/lib.php');

class local_catch_mod_event_observer {

    function add_mod_event(\core\event\course_module_created $event) {
        global $DB, $USER, $CFG;

        $data = $event->get_data();
        $supportedmodules = ['bigbluebuttonbn', 'gmeet', 'zoom', 'teams','ilt','facetoface'];

        if (in_array($data['other']['modulename'], $supportedmodules)) {

            require_once($CFG->dirroot.'/mod/attendance/lib.php');
            require_once($CFG->dirroot.'/mod/attendance/locallib.php');

            $modid = $DB->get_field('modules', 'id', ['name'=>'attendance']);
            $sql = 'SELECT att.id AS id, cm.id AS cmid FROM {attendance} att
                    JOIN {course_modules} cm ON cm.instance = att.id
                    WHERE att.name = :attname AND cm.deletioninprogress = 0
                    AND cm.course = :course AND cm.module = :module';

            $attdata = $DB->get_record_sql($sql, [
                'course' => $data['courseid'],
                'attname' => 'Attendance',
                'module' => $modid
            ]);

            if (!$attdata) {
                $modinfo = add_att_mod($modid, $data['courseid']);
                $attcmid = $modinfo->coursemodule;
            } else {
                $attcmid = $attdata->cmid;
            }

            $course = $DB->get_record('course', ['id' => $data['courseid']], '*', MUST_EXIST);
            $pageparams = new stdClass();
            $pageparams->action = 1;

            $cm  = get_coursemodule_from_id('attendance', $attcmid, 0, false, MUST_EXIST);
            $att = $DB->get_record('attendance', ['id' => $cm->instance], '*', MUST_EXIST);
            require_login($course, true, $cm);

            $context = context_module::instance($cm->id);
            if (!has_capability('mod/attendance:manageattendances', $context)) {
                return;
            }

            $att = new mod_attendance_structure($att, $cm, $course, $context, $pageparams);

            $activitydata = $DB->get_record($data['other']['modulename'], ['id'=>$data['other']['instanceid']]);
            $sec = ($activitydata->closingtime - $activitydata->openingtime);

            $session = new stdClass();
            $session->sessdate = $activitydata->openingtime;
            $session->duration = $sec;
            $session->description = trim($data['other']['name']);
            $session->descriptionformat = 1;
            $session->calendarevent = 1;
            $session->timemodified = time();
            $session->studentscanmark = 0;
            $session->autoassignstatus = 0;
            $session->automark = 0;
            $session->automarkcompleted = 0;
            $session->absenteereport = 1;
            $session->includeqrcode = 0;
            $session->statusset = 0;
            $session->groupid = 0;

            $att->add_sessions([$session]);
        }
    }

    function update_mod_event(\core\event\course_module_updated $event) {
        global $DB, $USER, $CFG;

        $data = $event->get_data();
        $supportedmodules = ['bigbluebuttonbn', 'gmeet', 'zoom', 'teams','facetoface','ilt'];

        if (in_array($data['other']['modulename'], $supportedmodules)) {
            require_once($CFG->dirroot.'/mod/attendance/lib.php');
            require_once($CFG->dirroot.'/mod/attendance/locallib.php');

            $modid = $DB->get_field('modules', 'id', ['name'=>'attendance']);
            $sql = 'SELECT att.id AS id, cm.id AS cmid FROM {attendance} att
                    JOIN {course_modules} cm ON cm.instance = att.id
                    WHERE att.name = :attname AND cm.deletioninprogress = 0
                    AND cm.course = :course AND cm.module = :module';

            $attdata = $DB->get_record_sql($sql, [
                'course' => $data['courseid'],
                'attname' => 'Attendance',
                'module' => $modid
            ]);

            if (!$attdata || $attdata->cmid < 1) {
                return;
            }

            $course = $DB->get_record('course', ['id' => $data['courseid']], '*', MUST_EXIST);
            $pageparams = new stdClass();
            $pageparams->action = 2;

            $cm  = get_coursemodule_from_id('attendance', $attdata->cmid, 0, false, MUST_EXIST);
            $att = $DB->get_record('attendance', ['id' => $cm->instance], '*', MUST_EXIST);
            require_login($course, true, $cm);

            $context = context_module::instance($cm->id);
            if (!has_capability('mod/attendance:manageattendances', $context)) {
                return;
            }

            $att = new mod_attendance_structure($att, $cm, $course, $context, $pageparams);

            $activitydata = $DB->get_record($data['other']['modulename'], ['id'=>$data['other']['instanceid']]);
            $session = $DB->get_record('attendance_sessions', ['attendanceid'=>$attdata->id, 'description'=>trim($data['other']['name'])]);
            if (!$session || $session->id < 1) {
                return;
            }

            $session->sessiondate = strtotime(date('m/d/Y', $activitydata->openingtime));
            $session->sestime = array(
                'starthour' => date('H', $activitydata->openingtime),
                'startminute' => date('i', $activitydata->openingtime),
                'endhour' => date('H', $activitydata->closingtime),
                'endminute' => date('i', $activitydata->closingtime)
            );
            $session->statusset = 0;

            $session->sdescription = array(
                'text' => $data['other']['name'],
                'format' => 1,
                'itemid' => rand(100000000,999999999)
            );
            $session->timemodified = time();

            $att->update_session_from_form_data($session, $session->id);
        }
    }
}
