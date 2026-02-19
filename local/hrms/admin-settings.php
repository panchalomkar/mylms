<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

if(!is_siteadmin())
{   
    echo $OUTPUT->header();
    echo "<h1>Invalid Access</h1>";
    echo $OUTPUT->footer();
    exit;
}

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$params = array();
$pagetitle = 'Admin Setting';
$PAGE->set_context($context);
// $PAGE->set_url('/my/passport_withdrawal.php', $params);
$PAGE->set_url('/local/hrms/admin-setting.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$all_users = $DB->get_records_sql("SELECT id, username FROM `glms_user`");
$hrms_admin = $DB->get_record_sql("SELECT * FROM `glms_hrms_admin_user`");

$admin1_id = $hrms_admin->admin1_id ?? '';
$admin2_id = $hrms_admin->admin2_id ?? '';
$admin3_id = $hrms_admin->admin3_id ?? '';
 
echo $OUTPUT->header();

?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.error {
    color: red;
    font-size: 12px;
}

.loader {
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

</style>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">Admin Settings</a>
    </li>  
</ul>

<form name="frmAdminSettings" id="frmAdminSettings" method="post">
    <input type="hidden" name="requesttype" value="add">  
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Admin 1</label>
                <select name="admin1_id" id="admin1_id" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach($all_users as $adminu) { ?>
                        <option value="<?php echo $adminu->id; ?>" <?php if($admin1_id == $adminu->id) { echo " selected "; } ?>><?php echo $adminu->username; ?></option>  
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Admin 2</label>
                <select name="admin2_id" id="admin2_id" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach($all_users as $adminu) { ?>
                        <option value="<?php echo $adminu->id; ?>" <?php if($admin2_id == $adminu->id) { echo " selected "; } ?>><?php echo $adminu->username; ?></option>  
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Admin 3</label>
                <select name="admin3_id" id="admin3_id" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach($all_users as $adminu) { ?>
                        <option value="<?php echo $adminu->id; ?>" <?php if($admin3_id == $adminu->id) { echo " selected "; } ?>><?php echo $adminu->username; ?></option>  
                    <?php } ?>
                </select>
            </div>
        </div>
         
        <div class="col-md-12">
            <div id="loaderdisp" style="display: none;"><div class="loader"></div></div> 
            <div id="btndisp"> 
                <button class="btn btn-success" id="btnsubmit">Submit</button> 
            </div>
        </div>
    </div>

</form>
<script>
$(document).ready(function(){
    $("#frmAdminSettings").validate({
        
        submitHandler: function() {
            $("#loaderdisp").show();
            $("#btndisp").hide();
 
            let frmdata = $("#frmAdminSettings").serialize();
            $.ajax({
                method: "post",
                url: 'ajax_admin-settings.php',
                data: frmdata,
                beforeSend() {

                },
                success: function(response) {
                    let parseResp = $.parseJSON(response);
                    console.log(parseResp);
                    if(parseResp.success == 1) {
                        Swal.fire({
                            title: 'Successfully Saved!',
                            text: parseResp.message,
                            icon: 'success'
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 5000);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: parseResp.message,
                            icon: 'error'
                        });

                        $("#loaderdisp").hide();
                        $("#btndisp").show();
                    }
                    
                }
            });
        }
    });
});
</script>


<?php
echo $OUTPUT->footer();