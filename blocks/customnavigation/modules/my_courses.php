<?php
class customnavigation_my_courses
{
    public $name = "My Courses";


    function get_link()
    {
        global $CFG, $USER;

        return "{$CFG->wwwroot}/blocks/customnavigation/my_courses.php";
    }


    function get_child()
    {
        global $CFG, $USER;

        $m = array();

        $courses = enrol_get_all_users_courses ( $USER->id, true );
        foreach ($courses as $course)
        {
            $m[] = array(
                'label' => $course->fullname,
                'href' => "{$CFG->wwwroot}/course/view.php?id={$course->id}" 
            );
        }

        return $m;
    }


    function isVisible()
    {
        return true;
    }
}

