<?php global $CFG; ?>
<link href="css/local.css">

<!-- form.php -->
<div class="modal fade" id="programModal" tabindex="-1" role="dialog" aria-labelledby="programModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header" style="background: #003152;">
                <h5 class="modal-title text-light d-flex justify-content-center col-md-11" id="programModalLabel">
                    Create Folder
                </h5>
                <button type="button" class="close col-md-1 text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="overflow: auto;">
                <!-- Your existing form goes here -->
                <form autocomplete="off" action="index.php" method="post" accept-charset="utf-8" id="mform1"
                    class="mform" enctype="multipart/form-data">
                    <div style="display: none;"><input name="id" type="hidden" value="<?php echo @$record->id; ?>">
                        <input name="sesskey" type="hidden" value="pA3oLNIPGP">
                        <input name="_qf__local_content_structure_form" type="hidden" value="1">
                    </div>

                    <div class="form-group row  fitem mb-4 ">
                        <div class="col-md-3">
                            <span class="pull-xs-right text-nowrap">
                                <img width="50" height="50"
                                    src="<?php echo $CFG->wwwroot . BASEURL ?>/images/folderimg.png">
                                <abbr class="initialism text-danger d-none" title="Required">
                                    <i class="icon fa fa-exclamation-circle text-danger fa-fw " title="Required"
                                        aria-label="Required"></i></abbr>


                            </span>
                            <label class="col-form-label d-inline " style="color:#000; font-weight: bold;"
                                for="id_name">
                                <?php echo get_string('programname', 'local_content_structure'); ?>
                            </label>
                        </div>
                        <div class="col-md-6 form-inline felement" data-fieldtype="text">
                            <input type="text" class="custom-file-label" name="name" id="id_name"
                                value="<?php echo @$record->name; ?>" size="" required>

                        </div>
                    </div>
                    <div class="form-group row fitem   ">
                        <div class="col-md-3">
                            <span class="pull-xs-right text-nowrap">
                                <img width="50" height="50"
                                    src="<?php echo $CFG->wwwroot . BASEURL ?>/images/imagefolder.png">
                                <abbr class="initialism text-danger d-none" title="Required">
                                    <i class="icon fa fa-exclamation-circle text-danger fa-fw " title="Required"
                                        aria-label="Required"></i></abbr>


                            </span>
                            <label class="col-form-label d-inline " style="color:#000; font-weight: bold;"
                                for="id_name">
                                <?php echo get_string('progimage', 'local_content_structure'); ?>
                            </label>
                        </div>
                        <div class="col-md-6 d-flex flex-column form-inline felement" data-fieldtype="text">
                            <div id="drop-area" class="upload-box text-center"
                                style="border: 2px dashed #ccc; padding: 20px; cursor: pointer;">
                                <img id="preview-img"
                                    src="<?php echo @$record->image ? $CFG->wwwroot . '/' . 'images/media/' . $record->image : $CFG->wwwroot . BASEURL . '/images/uploadimg.png'; ?>"
                                    alt="Preview" style="width: 60px; height: auto; margin-bottom: 10px;">
                                <p class="drag-text mb-1">Drag & Drop</p>
                                <small class="text-muted">Your files here or browse to upload</small><br>
                                <small class="text-primary">Only folder image file. Max 15 MB.</small>
                                <br><br>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    id="browseBtn">Browse</button>
                                <input type="file" name="file" id="id_file" class="d-none"
                                    accept=".jpg,.jpeg,.png,.gif">
                            </div>

                            <div id="file-info" class="mt-3 text-left" style="display: none;">
                                <p><strong>File name:</strong> <span id="file-name"></span></p>
                                <p><strong>File size:</strong> <span id="file-size"></span></p>
                                <p class="text-success" id="upload-status">Image is uploaded</p>
                            </div>
                        </div>

                    </div>
                    <div class="form-group col-sm-2">

                    </div>
                    <div class="form-group row mb-0 mt-2 fitem femptylabel  " data-groupname="buttonar">
                        <div class="col-md-12 pt-2 d-flex form-inline felement" data-fieldtype="group"
                            style="justify-content:center; border-top: solid 1px;">

                            <div class="form-group mr-3 fitem  ">

                                <span data-fieldtype="submit" class="submit-icon">
                                    <input type="submit" class="btn pl-4" name="submitbutton" id="id_submitbutton"
                                        value=" <?php echo get_string('save'); ?>"
                                        style="background: #003152;color: #fff;">
                                </span>
                            </div>

                            <div class="form-group ml-3 fitem   btn-cancel">

                                <span data-fieldtype="submit" class="cancel-icon">
                                    <input type="button" class="btn pl-4" id="id_cancel"
                                        value="<?php echo get_string('cancel'); ?>"
                                        style="background: #003152;color: #fff;">

                                </span>
                                <div class="form-control-feedback" id="id_error_cancel" style="display: none;">

                                </div>
                            </div>
                            <div class="form-control-feedback" id="id_error_buttonar" style="display: none;">

                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>


<!-- <form autocomplete="off" action="index.php" method="post" accept-charset="utf-8" id="mform1" class="mform"
    enctype="multipart/form-data">
   
</form> -->
<!--<input type="text" id="myInput" onkeyup="myFunction()" placeholder=" <?php echo get_string('searchforprogram', 'local_content_structure'); ?>" title="Type in a name">-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="js/local.js"></script>