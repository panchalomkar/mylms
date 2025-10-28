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
$pagetitle = 'Leave Application';
$PAGE->set_context($context);
// $PAGE->set_url('/my/leave_application.php', $params);
$PAGE->set_url('/local/hrms/leave_application.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$leave_balance = "";

$leave_types = $DB->get_records_sql("SELECT * FROM glms_leave_type ORDER BY name ASC");

$selected_leave_type = strip_tags($_REQUEST['leavetype']);
if(!empty($selected_leave_type)) {
   
    $leaveTypeDetail = $DB->get_record_sql("SELECT * FROM glms_leave_type WHERE `key` = ?", [$selected_leave_type]);
    // print_r($leaveTypeDetail ); 
    $leave_balance = intval($leaveTypeDetail->total_leaves) - getAlreadyTakenLeaves($USER->id, $selected_leave_type);
}

echo $OUTPUT->header();

// echo $CFG->wwwroot; 
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
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">Apply For Leave</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="leave_applied.php">Leaves Already Applied</a>
    </li>
     
</ul>
<form name="frmLeave" id="frmLeave" method="post">
    <input type="hidden" name="requesttype" value="add">   
	<div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Leave Type</label>
                <select name="leave_type" id="leave_type" class="form-control form-control-sm">
                    <option value="">--SELECT LEAVE TYPE--</option>
                    <?php
                    foreach ($leave_types as $leavetype) { 
                        // print_r($leavetype);
                        ?>
                        <option value="<?php echo $leavetype->key; ?>" <?php if($selected_leave_type == $leavetype->key) { echo " selected "; } ?>><?php echo $leavetype->name; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div> 
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Leave Balance</label>
                <input type="text" class="form-control" name="leave_balance" id="leave_balance" value="<?php echo $leave_balance; ?>" readonly>
            </div>
        </div>
        <div class="col-md-12" style="display: none;">
            <div class="form-group">
                <label for="">I have submitted supporting medical certificate to HRD:</label>
                &nbsp;&nbsp;
                <label><input type="radio" name="supported_documents" value="YES"> Yes</label> &nbsp;&nbsp;&nbsp;&nbsp;<label><input type="radio" name="supported_documents" value="NO"> No</label>
            </div>
        </div>  
        <div class="col-md-12">
            <div class="form-group">
                <label for="">Address While On Leave</label>
                <textarea name="address_while_on_leave" id="address_while_on_leave" class="form-control"></textarea>
            </div>
        </div>  
        <div class="col-md-6"> 
            <div class="form-group">
                <label for="">Contact Local Person (Mob No.)</label>
                <input type="text" class="form-control" name="contact_local_person" id="contact_local_person" maxlength="20">
            </div>
        </div>  
        <div class="col-md-6"> 
            <div class="form-group">
                <label for="">From - To</label>
                <input type="text" class="form-control" name="leave_from_to" id="leave_from_to">
            </div>
        </div>
        <div class="col-md-6"> 
            <div class="form-group">
                <label for="">Half Day</label>
                <input type="checkbox" name="half_day" id="half_day" value="1">
            </div>
        </div>
        <div class="col-md-6" id="half-day-disp" style="display:none;"> 
            <div class="form-group">
                <label for="">Half Day</label>
                <select name="session" id="session" class="form-control">
                    <option value="">--Select Session--</option>
                    <option value="1">Morning Session</option>
                    <option value="2">Evening Session</option>
                </select>
                
            </div>
        </div>
    </div>
    <div class="row"> 
        <div class="col-md-4"> 
            <div class="form-group">
                <label for="">Number of Days leave</label>
                <input type="text" class="form-control" name="no_of_days_leave_view" id="no_of_days_leave_view" readonly>
            </div>
        </div>
        <div class="col-md-4"> 
            <div class="form-group">
                <label for="">LWP</label>
                <input type="text" class="form-control" name="lwp" id="lwp">
            </div>
        </div>
        <div class="col-md-4"> 
            <div class="form-group">
                <label for="">Reason For Leave</label>
                <input type="text" class="form-control" name="reason" id="reason">
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
    <div class="row" id="not-eligible-disp" style="display:none;">
        <div class="col-md-12">
            <span style="color: red;">* You are not eligible for this leave. Kindly apply LWP from leave type.</span>
        </div>
    </div>
    
</form>

<script type="text/javascript">
$(document).ready(function(){
    // alert(1234);
    let lwp = 0;
    $('#leave_from_to').daterangepicker({
        locale: {
           format: 'DD/MM/YYYY'
        }
    }, function(start, end, label) {
        console.log("a date is selected")
    }).on('apply.daterangepicker', function(ev, picker) {
        var start = moment(picker.startDate.format('YYYY-MM-DD'));
        var end   = moment(picker.endDate.format('YYYY-MM-DD'));
        var diff = start.diff(end, 'days'); // returns correct number
        let fromToDiff = (Math.abs(diff) + 1);
        $("#no_of_days_leave_view").val(fromToDiff);

        lwp = 0;
        let leave_balance = $("#leave_balance").val();
        if(fromToDiff > leave_balance) {
            lwp = fromToDiff-leave_balance;
            <?php if($selected_leave_type == "LEAVE_WITHOUT_PAY") { ?>
                $("#lwp").attr("readonly", true); 
            <?php } else { ?>
                $("#lwp").css("backgroundColor", "red");
                $("#not-eligible-disp").show();
                $("#btnsubmit").attr("disabled", true);
            <?php } ?>
        } else {
            $("#lwp").css("backgroundColor", "white");
            $("#not-eligible-disp").hide();
            $("#btnsubmit").removeAttr("disabled");

        }

        $("#lwp").val(lwp);

        if(fromToDiff == 1) {
            $("#half_day").attr("disabled", false);
            // $("#half-day-disp").show();
            $("#session").val("");
            
        } else {
            $("#half_day").attr("disabled", true);
            $("#half-day-disp").hide();
            $("#session").val("");
            
        }


    });

    $("#half_day").click(function() {
        $("#session").val("");
        if($(this).is(":checked")) {
            $("#half-day-disp").show();
            $("#lwp").val(0.5);
            $("#no_of_days_leave_view").val(0.5);
        } else {
            $("#half-day-disp").hide();
            $("#lwp").val(0);
            $("#no_of_days_leave_view").val(1);
        }
    })

    $("#leave_type").change(function(){
        window.location.href = '<?php echo $PAGE->url; ?>?leavetype=' + $(this).val();
    });

    $("#frmLeave").validate({
        rules: {
            leave_type: "required",
            leave_balance: "required",
            address_while_on_leave: "required",
            contact_local_person: "required",
            leave_from_to: "required",
            no_of_days_leave_view: "required",
            lwp: "required",
            reason: "required",
            session: {
                required:'#half_day:checked'
            }
        },
        submitHandler: function() {
            $("#loaderdisp").show();
            $("#btndisp").hide();

            let frmdata = $("#frmLeave").serialize();
            $.ajax({
                method: "post",
                url: 'ajax_leave_application.php',
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
