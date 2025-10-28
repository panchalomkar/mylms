<?php
class customnavigation_my_blogs
{
    public $name = "My Blogs";


    function get_link()
    {
        global $CFG, $USER;

        return "{$CFG->wwwroot}/blog/index.php?userid={$USER->id}";
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

