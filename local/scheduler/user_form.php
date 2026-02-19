<?php ?>
<!DOCTYPE html>

<form name=mform1" autocomplete="off" action="payment.php" method="post" accept-charset="utf-8" id="mform1" class="mform" enctype="multipart/form-data">
    <div style="display: none;">

        <input name="id" type="hidden" value="<?php echo @$ad->id; ?>">
        <input name="_qf__local_ads_management_ad_form" type="hidden" value="1">
    </div>


    <div class="form-group row  fitem">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="company">
                <?php echo get_string('company', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="company" id="id_company" value="<?php echo @$ad->company; ?>"  required>

        </div>
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="title">
                <?php echo get_string('title', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="title" id="id_title" value="<?php echo @$ad->title; ?>"  required>

        </div>
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="description">
                <?php echo get_string('description', 'local_ads_management'); ?>
            </label>
        </div>
        <!--<div class="col-md-9 form-inline felement" data-fieldtype="text">-->
        <?php include_once 'rich_text_editor.php'; ?>
        <!--</div>-->
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="showtime">
                <?php echo get_string('showtime', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="showtime" id="id_showtime" value="<?php echo @$ad->showtime; ?>"  required>

        </div>
    </div>



    <div id="fitem_id_adtype" class="form-group row  fitem   ">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">

            <label class="d-inline word-break " for="adtype">
                <?php echo get_string('adtype', 'local_ads_management'); ?>
            </label>

        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select"  name="adtype" id="id_adtype" style="width: 30%;" >
                <option value="image">Image</option>
                <option value="video">Video</option>

            </select>  

            <div class="form-control-feedback invalid-feedback" id="id_error_parent">

            </div>
        </div>
    </div>
    <div id="fitem_imageadsizetype" class="form-group row  fitem" >
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">

            <label class="d-inline word-break " for="adsizetype">
                <?php echo get_string('adsizetype', 'local_ads_management'); ?>
            </label>

        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select imgvidtype"  name="imagetype" id="imageadsizetype" style="width: 30%;" >
                <option value="large-5-300">Large(5 min, 300Rs)</option>
                <option value="mediium-5-200">Medium(5 min, 200Rs)</option>
                <option value="small-5-100">Small(5 min, 100Rs)</option>

            </select>  

            <div class="form-control-feedback invalid-feedback" id="id_error_imageadsizetype">

            </div>
        </div>
    </div>

    <div id="fitem_videodsizetype" class="form-group row  fitem" style="display: none;">
        <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">

            <label class="d-inline word-break " for="adsizetype">
                <?php echo get_string('adsizetype', 'local_ads_management'); ?>
            </label>

        </div>
        <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">

            <select class="custom-select imgvidtype"  name="videotype" id="videoadsizetype" style="width: 30%;">
                <option value="large-15-600">Large(15 min, 600Rs)</option>
                <option value="mediium-10-400">Medium(10 min, 400Rs)</option>
                <option value="small-5-200">Small(5 min, 200Rs)</option>

            </select>  

            <div class="form-control-feedback invalid-feedback" id="id_error_imageadsizetype">

            </div>
        </div>
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="id_name">
                <?php echo get_string('uploadfile', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="file" class="form-control " name="file" id="id_file">


        </div>


    </div>


    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="price">
                <?php echo get_string('adprice', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control adprice-class" name="adprice1" id="id_adprice1" value="<?php echo @$ad->adprice; ?>"  disabled>
            <input type="hidden" class="form-control adprice-class" name="adprice" id="id_adprice" value="<?php echo @$ad->adprice; ?>"  required>

        </div>
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="customerpay">
                <?php echo get_string('customerpay', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="customerpay" id="id_customerpay" value="<?php echo @$ad->customerpay; ?>"  required>

        </div>
    </div>

    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="priceperclick">
                <?php echo get_string('priceperclick', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="priceperclick" id="id_priceperclick" value="<?php echo @$ad->priceperclick; ?>"  required>

        </div>
    </div>


    <div class="form-group row  fitem   ">
        <div class="col-md-3">
            <label class="col-form-label d-inline " for="weburl">
                <?php echo get_string('weburl', 'local_ads_management'); ?>
            </label>
        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="text">
            <input type="text" class="form-control " name="weburl" id="id_weburl" value="<?php echo @$ad->weburl; ?>"  required>

        </div>
    </div>  

    <div class="form-group row  fitem femptylabel  " data-groupname="buttonar">
        <div class="col-md-3">

        </div>
        <div class="col-md-9 form-inline felement" data-fieldtype="group">

            <div class="form-group  fitem  ">

                <span data-fieldtype="submit">
                    <input type="submit" class="btn btn-primary" name="submitbutton" id="id_submitbutton" value="<?php echo get_string('next', 'local_ads_management'); ?>">
                </span>

            </div>

            <div class="form-group  fitem   btn-cancel">

                <span data-fieldtype="submit">
                    <a href="<?php echo $CFG->wwwroot; ?>" class="btn btn-secondary" name="cancel" id="id_cancel" ><?php echo get_string('cancel'); ?></a>
                </span>

            </div>

        </div>
    </div>
</form>
