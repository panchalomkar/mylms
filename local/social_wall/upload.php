<?php
    $la_request             = $_FILES;
    $la_result              = array();
    $la_result['status']    = "";

    if (!is_dir( dirname(__FILE__) .'/uploads/' )) {
        mkdir( dirname(__FILE__) .'/uploads/' , 0777, true);
    }
    

    if ( count($la_request) > 0 ) {
        //print_r( $la_request );
        $target_dir             = "./uploads/".time()."_";
        $target_file            = $target_dir.basename( preg_replace("/\s{1,}/","_",$_FILES["fileToUpload"]["name"]) );
        $la_result['filename']  = $target_file;
        $uploadOk               = 1;
        $imageFileType          = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            $la_result['filetype']  = $check["mime"];
            $la_result['width']     = $check[0];
            $la_result['height']    = $check[1];
            $uploadOk = 1;
        } else {
            $la_result['filetype']  = get_string('noimage', 'local_social_wall');
            $la_result['status']    = get_string('extensionallowed', 'local_social_wall');
            $uploadOk = 0;
        }
        // Check file size 500K
        if ($_FILES["fileToUpload"]["size"] > 5000000) {
            if ( trim($la_result['status']) == "" ) {
                $sizeinmb = 5000000/1e+6;
                $la_result['status'] = get_string('maxfilesizeallowed','local_social_wall', $sizeinmb);
            }
            $uploadOk = 0;
        }
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            if ( trim($la_result['status']) == "" ) {
                $la_result['status'] = get_string('extensionallowed', 'local_social_wall');
            }
            $uploadOk = 0;
        }
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            if ( trim($la_result['status']) == "" ) {
                $la_result['status'] = get_string('uploadfaild', 'local_social_wall');
            }
        } else {
            // if everything is ok, try to upload file
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $la_result['status'] = "ok";
            } else {
                $la_result['status'] = ( trim($la_result['status']) == "" ) ? get_string('probleminuploading','local_social_wall') : $la_result['status'];
            }
        }
    } else {
        $la_result['status'] = get_string('noimage', 'local_social_wall');;
    }
    echo "<script type='text/javascript'>parent.show_wall_message('".json_encode( $la_result )."');</script>";
?>