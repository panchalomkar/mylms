<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// Display iomad_dashboard.
require_once( '../../config.php' );
require_once( $CFG->dirroot . '/local/ticketing/lib.php' );
// We always require users to be logged in for this page.
require_login();
global $USER, $CFG, $DB, $OUTPUT, $SESSION;

// Check we are allowed to view this page.
$systemcontext = context_system::instance();
// Set the url.
$linkurl = new moodle_url('/local/ticketing/index.php');
$linktext = get_string('name', 'local_ticketing');
// Print the page header.
$PAGE->set_context($systemcontext);
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);
$PAGE->navbar->add(get_string('pluginname', 'local_ticketing'), '/local/ticketing/index.php');
$PAGE->set_pagetype('local-ticketing-index');
$PAGE->requires->jquery();
//$PAGE->set_pagelayout('mydashboard');
echo $OUTPUT->header();

$config = get_config('local_ticketing');
$emails = explode(',', $config->assingtoemails);

if (isset($_POST['submitt']) && $_POST['submitt'] == 'Submit') {

    if (save_data($_POST, $_FILES, $USER->id)) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
  <strong>Congratulations! </strong>Ticket has been raised successfully.
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>';
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Sorry! </strong>Something went wrong.
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>';
    }
}
?>


<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <div class="d-flex flex-row mb-5">
            <div class="col-12 col-lg-12 mx-auto px-0">

                <div class="shadow-sm rounded-3 overflow-hidden _ticket_form">
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <div class="p-3 px-lg-5 bg-light"><h1 class="h4 font-weight-bold mb-0">Create New Ticket</h1></div>
                        <div class="p-3 px-lg-5">
                            <div class="container-fluid px-0">
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Subject</label>
                                    <input type="text" name="title" class="_form-control" id="exampleInputEmail1"
                                           placeholder="Subject" required>
                                </div>
                                <!-- SELECT2 EXAMPLE -->
                                <div class="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Issue Type</label>
                                                <div class="select2-purple">
                                                    <select name="type" class="_form-control select2bs4"
                                                            style="width: 100%;" required>
                                                        <option value="">Select Type</option>
                                                        <option value="Functional">Functional</option>
                                                        <option value="Design">Design</option>
                                                        <option value="Feature">Feature</option>
                                                        <option value="Bug">Bug</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                        <!-- /.col -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Priority</label>
                                                <div class="select2-purple">
                                                    <select name="priority" class="_form-control select2bs4"
                                                            style="width: 100%;" required>
                                                        <option value="">Select Priority</option>
                                                        <option value="High">High</option>
                                                        <option value="Medium">Medium</option>
                                                        <option value="Low">Low</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                        <!-- /.col -->
                                    </div>
                                    <!-- /.row -->
                                    <div class="w-100 mb-5">
                                        <label> Ticket Description</label>
                                        <!-- /.card-header -->
                                        <textarea name="description" id="summernote" rows="20" class="_form-control"
                                                  style="height: 200px;" required>

                                        </textarea>
                                    </div>

                                    <div class="flex-row">
                                        <div class="w-100">
                                            <div class="form-group">
                                                <label for="exampleInputFile">Attachments</label>
                                                <div class="input-group">
                                                    <div class="custom-file d-flex align-items-center">
                                                        <input type="file" name="attachments"
                                                               class="custom-file-inputs" id="exampleInputFile">
                                                        <!--                                                            <label class="custom-file-label _form-control" for="exampleInputFile">Choose file</label>-->
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="w-100">
                                            <div class="form-group">
                                                <label>Assign To</label>
                                                <div class="select2-purple">
                                                    <select name="assignto" class="_form-control select2bs4"
                                                            style="width: 100%;" required>
                                                        <option value="">Select Email</option>
                                                        <?php
                                                        foreach ($emails as $value) {
                                                            echo ' <option value="' . $value . '">' . $value . '</option>';
                                                        }
                                                        ?>

                                                    </select>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                            <!-- /.container-fluid -->
                        </div>
                        <div class="p-3 px-lg-5 card-footer">
                            <button type="submit" name="submitt" value="Submit"  class="btn btn-primary px-5 py-2">Submit <i class="fa fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ./wrapper -->

</body>
<script src="https://cdn.ckeditor.com/4.20.0/standard/ckeditor.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bs-custom-file-input/1.1.1/bs-custom-file-input.min.js"
        integrity="sha512-LGq7YhCBCj/oBzHKu2XcPdDdYj6rA0G6KV0tCuCImTOeZOV/2iPOqEe5aSSnwviaxcm750Z8AQcAk9rouKtVSg=="
crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript">
    CKEDITOR.plugins.addExternal('ckeditor_wiris', 'https://www.wiris.net/demo/plugins/ckeditor/', 'plugin.js');

    CKEDITOR.replace('description');

    $(function () {
        bsCustomFileInput.init();
    });
</script>

<?php
echo $OUTPUT->footer();
?>