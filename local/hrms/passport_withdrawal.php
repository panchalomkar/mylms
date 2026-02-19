<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

if($USER->department != 'FACULTY')
{   
    echo $OUTPUT->header();
    echo "<h1>Invalid Access</h1>";
    echo $OUTPUT->footer();
    exit;
}

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$params = array();
$pagetitle = 'Passport Withdrawal';
$PAGE->set_context($context);
// $PAGE->set_url('/my/passport_withdrawal.php', $params);
$PAGE->set_url('/local/hrms/passport_withdrawal.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);


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
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">Apply</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="passport_withdrawal_list.php">List</a>
    </li>
     
</ul>

<form name="frmPassportWithdrawal" id="frmPassportWithdrawal" method="post">
    <input type="hidden" name="requesttype" value="add">  
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Contact Local Person</label>
                <input type="text" class="form-control" name="contact_local_person" id="contact_local_person" value="">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Contact Local No</label>
                <input type="text" class="form-control" name="contact_local_no" id="contact_local_no" value="" maxlength="13">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Contact International Person</label>
                <input type="text" class="form-control" name="contact_international_person" id="contact_international_person" value="">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Contact International No</label>
                <input type="text" class="form-control" name="contact_international_no" id="contact_international_no" value="" maxlength="13">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Date To Return</label>
                <input type="date" class="form-control" name="date_to_return" id="date_to_return" value="">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Reason</label>
                <textarea name="reason" id="reason" class="form-control"></textarea>
            </div>
        </div>
        <div class="col-md-12">
            <div id="loaderdisp" style="display: none;"><div class="loader"></div></div> 
            <div id="btndisp"> 
                <button class="btn btn-success" id="btnsubmit">Submit</button>
                <button type="reset" class="btn btn-danger">Cancel</button>
            </div>
        </div>
    </div>

</form>
<script>
$(document).ready(function(){
    $("#frmPassportWithdrawal").validate({
        rules: {
            contact_local_person: "required",
            contact_local_no: {
                required: true,
                number: true
            },
            contact_international_person: "required",
            contact_international_no: {
                required: true,
                number: true
            },
            date_to_return: "required",
            reason: "required"
        },
        submitHandler: function() {
            $("#loaderdisp").show();
            $("#btndisp").hide();
 
            let frmdata = $("#frmPassportWithdrawal").serialize();
            $.ajax({
                method: "post",
                url: 'ajax_passport_withdrawal.php',
                data: frmdata,
                beforeSend() {

                },
                success: function(response) {
                    let parseResp = $.parseJSON(response);
                    console.log(parseResp);
                    if(parseResp.success == 1) {
                        Swal.fire({
                            title: 'Successfully Applied!',
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