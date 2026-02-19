<?php

function add_att_mod($modid, $courseid){
    global $DB;
    
    $attendance = new stdClass();
    $attendance->name = 'Attendance';
    $attendance->showdescription = 0;
    $attendance->grade = 100;
    $attendance->gradecat = 3;
    $attendance->visible = 1;
    $attendance->visibleoncoursepage = 1;
    $attendance->groupmode = 0;
    $attendance->groupingid = 0;
    $attendance->availabilityconditionsjson = '{"op":"&","c":[],"showc":[]}';
    $attendance->course = $courseid;
    $attendance->section = 1;
    $attendance->module = $modid;
    $attendance->modulename = 'attendance';
    $attendance->add = 'attendance';
    $attendance->update = 0;
    $attendance->return = 0;
    $attendance->sr = 0;
    $attendance->competency_rule = 0;
    $attendance->completion = 0;
    $attendance->completionview = 0;
    $attendance->completionexpected = 0;
    $attendance->introformat = 1;
    

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $modinfo = add_moduleinfo($attendance, $course);

    return $modinfo;
}