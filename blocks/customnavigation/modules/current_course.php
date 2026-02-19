<?php
class customnavigation_current_course
{
    public $name = "Current Course";


    function get_link()
    {
        global $CFG;
        return "javascript:;";
    }


    function get_child()
    {
        global $CFG, $USER;
        $child = array();

        if($this->isVisible())
        {
            $courses = enrol_get_all_users_courses ( $USER->id, true );
            $course_id = isset($_GET['id']) ? $_GET['id'] : 0;

            if($courses[$course_id])
            {
                $course = $courses[$course_id];

                $child[] = array(
                    'label' => $course->fullname,
                    'href' => "{$CFG->wwwroot}/course/view.php?id=$course_id",
                );

                $child[] = array(
                    'label' => 'Participants',
                    'href' => "{$CFG->wwwroot}/user/index.php?id=$course_id",
                    'pages' => array(
                        array(
                            'label' => 'Course blogs',
                            'href' => "{$CFG->wwwroot}/blog/index.php?courseid=$course_id",
                        ),
                        array(
                            'label' => 'Notes',
                            'href' => "{$CFG->wwwroot}/notes/index.php?filtertype=course&filterselect=$course_id",
                        )
                    )
                );

                $child[] = array(
                    'label' => 'Course Badges',
                    'href' => "{$CFG->wwwroot}/badges/view.php?type=2&id=$course_id",
                );
            }
        }

        return $child;
    }
    

    function isVisible()
    {
        return ('course/view.php' == substr($_SERVER['SCRIPT_NAME'], -15));
    }
}