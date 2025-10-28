<?php
$programs = get_programs();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.full.min.js"></script>
<script>
    $(document).ready(function () {
        $.noConflict();
        $(function () {
            $("select").select2();
        });

        //        function changeProgram() {
        //            var pid = $('#selectprogram').val();
        //            window.location.href = 'container.php?pid=' + pid;
        //        }

        $('body').on('change', '#selectprogram', function () {
            var pid = $('#selectprogram').val();
            window.location.href = 'container.php?pid=' + pid;
        })

    });
</script>
<form autocomplete="off" action="container.php" method="post" accept-charset="utf-8" id="mform1" class="mform"
    enctype="multipart/form-data">
    <div style="display: none;">
        <input name="pid" type="hidden" value="<?php echo $pid; ?>">
        <input name="id" type="hidden" value="<?php echo @$record->id; ?>">
        <input name="archtype" type="hidden" value="course">
        <input name="sesskey" type="hidden" value="pA3oLNIPGP">
        <input name="_qf__local_content_structure_form" type="hidden" value="1">
    </div>


    <div id="fitem_id_parent" class="form-group row  fitem   ">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0 ">

            <label class="d-inline word-break " for="id_parent" style="color:#000; font-weight: bold;">
                <?php echo get_string('programname', 'local_content_structure'); ?>
            </label>

        </div>
        <div class="col-md-6 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select col-md-12" name="parent" id="selectprogram" style="width: 30%;">

                <?php
                foreach ($programs as $program) {
                    if ($pid == $program->id) {
                        echo '<option value="' . $program->id . '" selected>' . $program->name . '</option>';
                    } else {
                        echo '<option value="' . $program->id . '">' . $program->name . '</option>';
                    }
                }
                ?>
            </select>

            <div class="form-control-feedback invalid-feedback" id="id_error_parent">

            </div>
        </div>
    </div>


    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name" style="color:#000; font-weight: bold;">
                <?php echo get_string('coursename', 'local_content_structure'); ?>
            </label>
        </div>
        <div class="col-md-6 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="name" id="id_name" value="<?php echo @$record->name; ?>"
                size="" required>

        </div>
    </div>
    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name" style="color:#000; font-weight: bold;">
                <?php echo get_string('courseimage', 'local_content_structure'); ?>
            </label>
        </div>
        <!-- <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="file" class="form-control " name="file" id="id_file">
            <?php
            // $path = '';
            // if (@$record->image != '') {
            //     $path = 'images/media/' . $record->image;
            // }
            ?>

        </div> -->
        <div class="col-md-6">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="fileUploadTabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo empty($record->link) ? 'active' : ''; ?>" id="upload-tab"
                        data-toggle="tab" href="#upload-panel">📁 Upload File</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo !empty($record->link) ? 'active' : ''; ?>" id="link-tab"
                        data-toggle="tab" href="#link-panel">🔗 Add Link</a>
                </li>
            </ul>

            <div class="tab-content border p-1" style="background: #f9f9f9;">
                <!-- Upload File Panel -->
                <div class="tab-pane fade <?php echo empty($record->link) ? 'show active' : ''; ?>" id="upload-panel">
                    <div id="drop-area-course" class="upload-box text-center p-2"
                        style="border: 2px dashed #ccc; cursor: pointer; border-radius: 10px;">
                        <div class="mb-3">
                            <i class="fa fa-cloud-upload" style="font-size:40px; color:#003152;"></i>
                        </div>
                        <p class="mb-1"><strong>Select a file</strong> or drag and drop</p>
                        <small class="text-muted">PDF, PPT, DOCX, MP4, MOV (Max 50MB)</small><br><br>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="browseCourseBtn">Select a
                            file</button>
                        <input type="file" name="file" id="id_coursefile" class="d-none"
                            accept=".pdf,.ppt,.pptx,.docx,.mp4,.mov">
                    </div>

                    <?php if (!empty($record->file)) { ?>
                        <div class="mt-3" id="existing-course-file">
                            <p><strong>Existing file:</strong> <?php echo htmlspecialchars($record->file); ?></p>
                            <input type="hidden" name="existing_file"
                                value="<?php echo htmlspecialchars($record->file); ?>">
                        </div>
                    <?php } ?>

                    <div id="course-file-info" class="mt-3" style="display: none;">
                        <p><strong>File name:</strong> <span id="course-file-name"></span></p>
                        <p><strong>File size:</strong> <span id="course-file-size"></span></p>
                        <p class="text-success" id="course-upload-status">File uploaded successfully.</p>
                    </div>
                </div>

                <!-- Add Link Panel -->
                <div class="tab-pane fade <?php echo !empty($record->link) ? 'show active' : ''; ?>" id="link-panel">
                    <input type="url" class="form-control" name="link" placeholder="Paste your link here..."
                        value="<?php echo isset($record->link) ? htmlspecialchars($record->link) : ''; ?>">
                </div>
            </div>
        </div>


    </div>
    <div class="form-group col-sm-2">

    </div>
    <div class="form-group row mt-3 fitem femptylabel  " data-groupname="buttonar">
        <div class="col-md-12 d-flex felement" data-fieldtype="group" style="justify-content:center;">

            <div class="form-group mr-2  fitem">

                <span data-fieldtype="submit" class="">
                    <input type="submit" class="btn" name="submitbutton" id="id_submitbutton"
                        value="<?php echo get_string('save'); ?>" style="background: #003152;color: #fff;">
                </span>

            </div>

            <div class="form-group  fitem  ml-2 btn-cancel">

                <span data-fieldtype="submit" class="">
                    <input type="submit" class="btn" name="cancel" id="id_cancel"
                        value="<?php echo get_string('cancel'); ?>" onclick="skipClientValidation = true; return true;"
                        style="background: #003152;color: #fff;">
                </span>

            </div>

        </div>
    </div>
</form>