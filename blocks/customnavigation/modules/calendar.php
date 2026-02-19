<?php
class customnavigation_calendar
{
    public $name = "Calendar";


    function get_link()
    {
        global $CFG;
        return "{$CFG->wwwroot}/calendar/view.php?view=month&time=" . time ();
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