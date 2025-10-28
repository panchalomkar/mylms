<?php
// This file is part of the Contact Form plugin for Moodle - http://moodle.org/
//
// Contact Form is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Contact Form is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Contact Form.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin for Moodle is used to send emails through a web form.
 *
 * @package    local_contact
 * @copyright  2016-2019 TNG Consulting Inc. - www.tngconsulting.ca
 * @author     Michael Milette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('locallib.php');

global $DB;
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'local_scheduler'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_scheduler'));
$PAGE->navbar->add(get_string('slotscheduler', 'local_scheduler'));

$id = optional_param('id', 0, PARAM_INT);


$localObj = new scheduler();
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_scheduler/local', 'load');

if (isset($_POST['submitbutton']) && $_POST['submitbutton'] == 'Save') {

    $localObj->ad_save($_POST);
    //  redirect('table.php');
}
// Display page header.


include_once 'slot_form.php';
?>


<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.full.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $.noConflict();
       
            $("select").select2();
       
    });
</script>
<script src="js/jquery-3.5.1.js"></script>-->
<?php

echo $OUTPUT->footer();
?>