<?php
/* 
 * @file event to unenrol cohort user from lp when deleted from cohort
 * @author Manisha M
 * @since 23-07-2019
 */
$observers = array(

    array (
        'eventname'   => '\core\event\cohort_member_removed',
        'includefile' => '/local/lp_cohort/classes/lp_cohort.php',
        'callback'    => 'observer::unenrol_cohort_user'
    ),
);
