<?php
/**
 * This file handles the file upload operations.
 * 
 * @package	local_social_wall
 * @version	9.3
 * @author	Jayesh T
 * @since 25 March 2020
 * @paradiso
*/

global $CFG;
require_once('../../../config.php');
require_once("{$CFG->dirroot}/local/social_wall/lib.php");

define('AJAX_SCRIPT', true);

require_login();

$response = array( 'status' => false, 'message' => get_string('noimage', 'local_social_wall') );
$upload = 1;
$target_dir = $CFG->dirroot ."/local/social_wall/uploads/";
$target_wwwroot = $CFG->wwwroot ."/local/social_wall/uploads/";
for($i = 0; $i < count($_FILES["file"]["name"]); $i++){
    $target_file = $target_dir . basename($_FILES["file"]["name"][$i]);

    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    //mkdir($target_dir, 0777)
    if(!is_dir($target_dir) || ! is_writable(dirname($target_file)) ) {
        $response['message'] = get_string('dirnotexist','local_social_wall');
        $response['status'] = false;
        $upload = 0;
    }


    // Check file size
    if ($_FILES["file"]["size"][$i] > 5000000 ) {
        $sizeinmb = 5000000/1e+6;
        $response['message'] = get_string('maxfilesizeallowed','local_social_wall', $sizeinmb);
        $response['status'] = false;
        $upload = 0;
    } 

    // Allow certain file formats
    $check = getimagesize($_FILES["file"]["tmp_name"][$i]);

    $extensions = array('png', 'jpg', 'jpeg', 'gif');
    if( !in_array( $imageFileType, $extensions ) || $check == false ) {
        $response['message'] = get_string('extensionallowed','local_social_wall');
        $response['status'] = false;
        $response['imageFileType'] = $imageFileType;
        $response['check'] = $check;
        $upload = 0;
    }

    if( $upload ){
        if ( 0 < $_FILES['file']['error'][$i] ) {
            $response['message'] =  get_string('probleminuploading','local_social_wall') . $_FILES['file']['error'][$i];
            $response['status'] = false;
        }else {
            $outputfile = time() .'-'. $_FILES['file']['name'][$i];
            //$ok = move_uploaded_file($_FILES['file']['tmp_name'][$i], $target_dir . $outputfile );
            if( $outputfile){
                $context = context_system::instance();
                $filename = $target_wwwroot . $outputfile;
                $convertedfile = $_FILES['file']['tmp_name'][$i];
                $fs = get_file_storage();
                $filerecord = array('contextid'=>$context->id, 'component'=>'local_social_wall', 'filearea'=>'social_wall_files', 'itemid'=>0, 'filepath'=>'/', 'filename'=>$outputfile, 'timecreated'=>time(), 'timemodified'=>time());
                $storedfile = $fs->create_file_from_pathname($filerecord, $convertedfile);
                $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",'/'. $context->id. '/local_social_wall/social_wall_files/0/'.$outputfile);
                $response['message'] = get_string('uploadsuccess','local_social_wall');
                $response['url'][$i] =  $imageurl;
                //$response['url'][$i] = $target_wwwroot . $outputfile;
                $response['status'] = true;
            }else{
                $response['message'] = get_string('movingissue','local_social_wall');
                $response['status'] = false;
            }
        }
    }
}
echo json_encode($response); 
die();
