<?php
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
  if (!empty($SESSION->currenteditingcompany)) {
      $selectedcompany = $SESSION->currenteditingcompany;
  } else if (!empty($USER->profile->company)) {
      $usercompany = company::by_userid($USER->id);
      $selectedcompany = $usercompany->id;
  } else {
      $selectedcompany = "";
  }
$records = $DB->get_records('custom_level', array('companyid' => $selectedcompany));
$j = 1;
foreach ($records as $rec) {
    $array['id'][$j] = $rec->id;
    $array['level'][$j] = $rec->level;
    $array['point'][$j] = $rec->point;
    $array['grade'][$j] = $rec->grade;
    $array['icon'][$j] = $rec->icon;
    $j++;
}

?>
<form action="" method="POST" id="level_form1" enctype="multipart/form-data" onsubmit="return validateForm();">
    <div id="slotform">
        <div class="mt-3">
            <strong>
                <div class="row g-4 bord-bottom">
                    <div class="col-xl-1 col-md-1 col-sm-1">
                        Level
                    </div>
                    <div class="col-xl-3 col-md-3 col-sm-3">
                        Rank
                    </div>
                    <div class="col-xl-2 col-md-2 col-sm-2">
                        Points
                    </div>
                    <div class="col-xl-2 col-md-2 col-sm-2">
                        Course Grade %
                    </div>
                    <div class="col-xl-4 col-md-4 col-sm-4">
                        Icon
                    </div>
                    <!--                    <div class="col-1">
                                            Action
                                        </div>-->
                </div>
            </strong>
        </div>
        <?php for ($i = 1; $i <= 10; $i++) { ?>
            <div class="mt-3 formrow" id="1">
                <div class="row g-4">
                    <input type="hidden" name="id[]" value="<?php echo $array['id'][$i]; ?>">
                    <div class="col-xl-1 col-md-1 col-sm-1">
                        <?php echo $i; ?>
                    </div>
                    <div class="col-xl-3 col-md-3 col-sm-3">
                        <input type="text" name="level[]" class="form-control" placeholder="Level Name" value="<?php echo $array['level'][$i]; ?>" required>
                    </div>

                    <div class="col-xl-2 col-md-2 col-sm-2">
                        <input type="number" name="point[]" id="st1" tagid="1" class="form-control" placeholder="Point" value="<?php echo $array['point'][$i]; ?>" required>
                    </div>
                    <div class="col-xl-2 col-md-2 col-sm-2">
                        <input type="number" name="grade[]" id="et1" tagid="1" class="form-control" placeholder="Grade" value="<?php echo $array['grade'][$i]; ?>" required>
                    </div>
                    <div class="col-xl-4 col-md-4 col-sm-4 d-flex">
                        <input type="file" min="1" id="prof_<?php echo $i; ?>" name="icon[]" class="form-control" style="display:none">
                        <label for="prof_<?php echo $i; ?>" class="btn btn-success">Choose File</label>
                        <img src="<?php echo $CFG->wwwroot.'/local/mydashboard/images/'.$array['icon'][$i]; ?>" width="50" class="si-img mx-2">
                    </div>
                    <!--                    <div class="col-2">
                                            <button class="btn btn-primary" id="addrow">Add Row</button>
                                        </div>-->
                </div>
            </div>
        <?php } ?>
        <div class="actionbutton">
        <input type="submit" name="submitbutton" id="formsubmit" class="btn btn-primary" value="Save">
    </div>
    </div>
</form>

<script>
    function validateForm() {
        var isValid = true;
        var inputs = document.querySelectorAll('#level_form1 input[required]');
        
        inputs.forEach(function(input) {
            if (input.value.trim() === '') {
                isValid = false;
                alert('Please fill in all required fields.');
            }
        });

        return isValid;
    }
</script>