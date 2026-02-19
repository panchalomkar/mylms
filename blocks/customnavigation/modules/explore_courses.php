<?php
class customnavigation_explore_courses
{
    public $name = "Explore Courses";


    function get_link()
    {
        global $CFG;
        return "{$CFG->wwwroot}/course/explore_courses.php";
    }


    function get_child()
    {
        return array();
    }
    

    function isVisible()
    {
        return true;
    }
}