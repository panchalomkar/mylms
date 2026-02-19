<?php
require_once("../../config.php");

// Security Validations.
global $PAGE, $CFG,$USER, $SESSION;
require_login();
  
require_once("{$CFG->dirroot}/local/social_wall/lib.php");
require_once("{$CFG->dirroot}/local/social_wall/classes/wallform.php");
require_once("{$CFG->dirroot}/local/social_wall/classes/uploadform.php");
require_once("{$CFG->dirroot}/local/social_wall/classes/urlform.php");
require_once("{$CFG->dirroot}/local/social_wall/classes/userbioform.php");
$isiframe = optional_param('isiframe','', PARAM_INT);
$msgid = optional_param('id', null, PARAM_INT);
$context = context_system::instance(); 
if($isiframe == 1){
   $PAGE->add_body_class('inframe');
}


$PAGE->requires->css(new moodle_url("/local/social_wall/socialwall.css"));
$PAGE->requires->jquery();
// Page configurations.
$PAGE->set_url('/local/social_wall/index.php');
//$PAGE->set_title(get_string('pluginname', 'local_social_wall'));
//$PAGE->set_heading(get_string('pluginname', 'local_social_wall'));
$PAGE->navbar->add(get_string('pluginname', 'local_social_wall'), '/local/social_wall/index.php');
$PAGE->requires->js_call_amd('local_social_wall/wall', 'init');
$PAGE->requires->js_call_amd('local_social_wall/upload_image', 'init');
$PAGE->requires->css(new moodle_url('/theme/remui/style/select2.min.css'));
// Default company
$company = 0 ;
if(!empty($USER->company->id) && !is_siteadmin($USER->id)){
  $company = $USER->company->id;
} else if( isset($SESSION->wall_selected_company) ){
  $company = $SESSION->wall_selected_company;
} else if(!empty($SESSION->currenteditingcompany)){
  $company = $SESSION->currenteditingcompany;
}

if($company){
  $socialbgimg = 'socialbgimg_'.$company;
} else {
  $company = '';
  $socialbgimg = 'socialbgimg';
}

if(isset($SESSION->wall_selected_company)){
  $company = $SESSION->wall_selected_company;
  $socialbgimg = 'socialbgimg_'.$company;
}

if(!empty($USER->company->id)){
  $brandarr = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'brandprimary_' . $USER->company->id) );
}else{
    $brandarr = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'brandprimary') );
}

// Show header.
$userprefrences = $DB->get_record('user_preferences', ['name' => 'user_background_color','userid'=>$USER->id]);
$timeline_background = $userprefrences->value;
$userdetails = $DB->get_record('user', ['id' => $USER->id]);


echo $OUTPUT->header();
$pagegrid = get_config('local_social_wall', 'pagecontainer');
$class = (empty($pagegrid) || $pagegrid == "") ? '' : '';
echo html_writer::start_tag('div', array('class' => "social-wall-container $class"));
echo '<div class="navbar navbar-default d-block d-sm-none float_notification">
  
  <i class="fa fa-bell" aria-hidden="true" data-toggle="collapse" data-target="#notify-box-1"></i>

</div>';
    echo html_writer::start_tag('div', array('class' => 'content-title row'));
//      echo html_writer::start_tag('div', array('class' => 'col-sm-12 col-md-5 p-0'));
//      $title = get_string('pluginname', 'local_social_wall');
//      $descriptionlp = get_string('descriptionname', 'local_social_wall');
//      echo html_writer::tag('h2', $title, ['class' => 'title-learning']);
//      echo html_writer::end_tag('div');
      if(is_siteadmin()){
        echo social_wall_company_list();
      }
      echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'current_company', 'id' => 'current_company', 'value'=>$company));
    echo html_writer::end_tag('div');
    $theme = theme_config::load('remui');
    $hasBgimage = $theme->setting_file_url($socialbgimg, $socialbgimg);
    if($hasBgimage !=""){
        $pagebg = $theme->setting_file_url($socialbgimg, $socialbgimg);
    } else {
        $pagebg = $theme->setting_file_url('loginimage', 'loginimage');
    }
   
   $pagebg = $CFG->wwwroot.'/local/social_wall/slider.jpg';
    $prescss = "";
    if (isset($pagebg)) {
        $prescss = 'background-image: url("' . $pagebg . '"); background-size:cover; background-position:center;height:300px;';
    } else {
        $prescss = 'background-size:cover; background-position:center;height:300px;';
    }
    echo html_writer::start_tag('div', array('class' => 'socail-img-container _custom-timeline', 'style' => $prescss,'id'=>'social-container-1'));
        echo html_writer::tag('img', "", array('src' => "#", "id" => 'uploaded_img'));
        if(is_siteadmin()){
            echo html_writer::start_tag('div', array('class' => 'upload-img','style'=>'float:left;width:50%;'));
                echo html_writer::tag('i', "" ,array('class' => "fa fa-camera"));
                 echo html_writer::tag('span', "", array('name' => "name"));
            echo html_writer::end_tag('div');
        }
        // echo html_writer::start_tag('div', array('class' => 'social-img','style'=>''));
        //         echo html_writer::start_tag('i' ,array('class' => "fa fa-cog fa-fw",'data-toggle'=>"modal", 'data-target'=>"#userBioModal"));
        //             //echo social_user_bio();
        //         echo html_writer::end_tag('i');
        //          echo html_writer::tag('span', "", array('name' => "name"));
        //     echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    
    // User Picture
    
        $userpicture = new user_picture($USER);
        $userpicture->size = 1; // Size f1.
        $profileimageurl = $userpicture->get_url($PAGE);
        
    // Count social wall timeline posts    
        $count_post=social_wall_no_of_post();
       
    /**
    * Get all posts count
    * @author Vinay B
    * @Dated 16-04-2020
    */
    // Count all social wall posts
        $all_posts_count = all_social_wall_posts_count();

    echo html_writer::start_tag('div', array('class' => 'socail-img-container social-img-container-2 _custom-timeline-2', 'style' => 'background-size:cover; background-position:center;background-color:'.($timeline_background?$timeline_background:$brandarr->value).';display:none;','id'=>'social-container-2'));
        //echo html_writer::tag('img', "", array('src' => "#", "id" => 'uploaded_img'));
        echo html_writer::start_tag('div', array('class' => 'container','style'=>''));
            echo html_writer::start_tag('div', array('class' => 'row','style'=>'padding-top:1%;'));
                echo html_writer::start_tag('div', array('class' => 'col-md-2 user-img','style'=>'clear:both;'));
                    echo html_writer::tag('img', "", array('src' => "$profileimageurl",'class'=>'rounded-circle us_profile_img','style'=>'margin-top:0px;width:135px;margin-left:20px;','title'=>'Go to timeline'));
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', array('class' => 'col-md-9 user-img popup_div','style'=>'clear:both;'));
                    echo html_writer::tag('h1', $USER->firstname.' '.$USER->lastname, array());
                    if(!empty($userdetails->city) || !empty($userdetails->country)){
                      echo html_writer::tag('i', "", array('class'=>'fa fa-map-marker','style'=>'float:left;margin-right:5px;'));  
                    }
                    
                    echo html_writer::tag('h3', $userdetails->city.' '.$userdetails->country, array());
                    echo html_writer::tag('span', $userdetails->description, array());
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', array('class' => 'col-md-1 social-img','style'=>''));
                    echo html_writer::start_tag('i' ,array('class' => "fa fa-cog fa-fw",'data-toggle'=>"modal", 'data-target'=>"#userBioModal",'style'=>'cursor:pointer;'));
                        //echo social_user_bio();
                    echo html_writer::end_tag('i');
                    echo html_writer::tag('span', "", array('name' => "name"));
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array('class' => 'container','style'=>'background-color: #f1ecec;'));
            echo html_writer::start_tag('div', array('class' => 'row','style'=>'margin-top:1%;'));
                echo html_writer::start_tag('div', array('class' => 'col-md-1','style'=>'clear:both;text-align:center;'));
                    echo html_writer::tag('h3', 'Posts', array());
                    echo html_writer::tag('h3', $count_post, array());
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', array('class' => 'col-md-2 ','style'=>'clear:both;text-align:center;'));
                    echo html_writer::tag('h3', 'Following', array());
                    echo html_writer::tag('h3', '37', array());
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', array('class' => 'col-md-2','style'=>'text-align:center;'));
                    
                    echo html_writer::tag('h3', 'Followers', array());
                    echo html_writer::tag('h3', '38', array());
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', array('class' => 'col-md-7 social-img','style'=>''));
                    echo html_writer::start_tag('button', ['id' => 'back_see_all_btn_1', 'class' => 'btn btn-secondary','type'=>'button']);
                        echo get_string('backtosocialwall', 'local_social_wall');
                    echo html_writer::end_tag('button');
                    echo html_writer::tag('button', "Follow", array('name' => "name",'class'=>'btn btn-round btn-primary'));
                echo html_writer::end_tag('div');

            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        
        

        
        echo html_writer::end_tag('div');
      
    echo html_writer::start_tag('div', ['class' => 'row','style'=>'','id'=>'grid-row-1']);
        echo html_writer::start_tag('div', ['class' => 'col-sm-4']);
                echo html_writer::start_tag('div', ['class' => 'cardone']);
                //echo html_writer::start_tag('a', ['href' => 'javascript(void)','title'=>'Go to timeline']);
                    echo html_writer::tag('img', "", array('src' => "$profileimageurl",'class'=>'rounded-circle us_profile_img bbb','id' => '','value'=>$USER->id));
                echo html_writer::end_tag('a');
                        echo html_writer::start_tag('div', ['class' => 'container']);
                            echo html_writer::tag('h2', $USER->firstname." ".$USER->lastname,['class'=>'profile_name']);
                                echo html_writer::tag('h3', get_string('noofpost', 'local_social_wall'), ['style'=>'padding: 10px;color: #333333 !important']);
                                    echo html_writer::tag('h3', $count_post,['class'=>'count_post']);
                                        echo html_writer::start_tag('div', ['class' => 'timeline_div','style'=>'padding:10px;']);
                                            // echo html_writer::start_tag('button', ['id' => 'btn_timeline', 'class' => 'btn btn-primary btn_timeline','type'=>'button']);
                                            //     echo get_string('timeline', 'local_social_wall');
                                            // echo html_writer::end_tag('button');
                                        echo html_writer::end_tag('div');
                                        echo html_writer::start_tag('div', ['class' => 'back_div','style'=>'display:none;padding:10px;']);
                                            echo html_writer::start_tag('button', ['id' => 'back_see_all_btn', 'class' => 'btn btn-secondary back_see_all_btn','type'=>'button']);
                                                echo get_string('backtosocialwall', 'local_social_wall');
                                                echo html_writer::end_tag('button');
                                        echo html_writer::end_tag('div');
                                echo html_writer::end_tag('div');
                            echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');

                    /**
                    * Print all posts count
                    * @author Vinay B
                    * @Dated 16-04-2020
                    */
                    // All posts count
                    echo html_writer::start_tag('div', array('class' => 'all_posts_count','style'=>'display:none;'));
                    echo $all_posts_count;
                    echo html_writer::end_tag('div');
        
    // 3 Button Post & Media & URL
    echo html_writer::start_tag('div', ['class' => 'col-sm-8']);
        echo html_writer::start_tag('div', ['class' => 'page-layout']);
            echo html_writer::start_tag('table', ['class' => 'table table-bordered', 'style' => ' background-color:#fff;']);
                echo html_writer::start_tag('tr', ['style' => 'text-align: center;']);
                    echo html_writer::start_tag('th', ['class' => 'click_show_post', 'style' => 'background-color:#1ba2dd;', 'title' => 'Start a Post']);
                        echo html_writer::tag('h3', '<i class="fa fa-pencil-square-o" aria-hidden="true"></i>');
                    echo html_writer::end_tag('th');
                    echo html_writer::start_tag('th', ['class' => 'click_show_media', 'title' => 'Media']);
                            echo html_writer::tag('h3', '<i class="fa fa-video-camera" aria-hidden="true"></i>');
                    echo html_writer::end_tag('th');
                    echo html_writer::start_tag('th', ['class' => 'click_show_url', 'title' => 'URL']);
                        echo html_writer::tag('h3', '<i class="fa fa-link" aria-hidden="true"></i>');
                    echo html_writer::end_tag('th');
                echo html_writer::end_tag('tr');
            echo html_writer::end_tag('table');
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div',  ['class' => 'txt-learning']);
            echo html_writer::tag('h3', $descriptionlp);
                echo html_writer::tag('p', get_string('writecontent', 'local_social_wall'), ['class' => 'write']);
        echo html_writer::end_tag('div');


        echo html_writer::start_tag('div', array('class' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12 show_post', 'id' => 'wall_container'));
            echo html_writer::start_tag('div', array('id' => 'updateboxarea'));
                echo html_writer::start_tag('div', array('id' => 'updateboxarea_container'));
                    $editoroptions = array('context' => $context, 'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true);
                    $customdata = array(
                        'editoroptions' => $editoroptions,
                    );
                    $mform = new wallform($PAGE->url, $customdata);
                    $mform->display();
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array('class' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12 show_media', 'id' => 'upload_container','style'=>'display:none;'));
            $mform2 = new uploadform($PAGE->url);
            $mform2->display();
        echo html_writer::end_tag('div');
        
        
        echo html_writer::start_tag('div', array('class' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12 show_url', 'id' => 'url_container','style'=>'display:none;'));
            $mform3 = new urlform($PAGE->url);
            $mform3->display();
        echo html_writer::end_tag('div');
        
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
        
        
    echo html_writer::start_tag('div', ['class' => 'row']);
        echo html_writer::start_tag('div', ['class' => 'notify-box col-md-4 col-xs-11 col-sm-11 sliding-sidebar','id'=>'notify-box-1']);
            echo html_writer::start_tag('div', ['class' => 'cardone ', 'id' => 'updates-box','style'=>'']);
                echo html_writer::start_tag('div', ['class' => 'update-container']);
                    echo html_writer::tag('h3', get_string('notifications', 'local_social_wall'),['id'=>'update-heading']);
                    echo html_writer::tag('h3', '<a href="#" id="back_update_seepost">Back</a>',['style'=>'display:none','class'=>'back_update_seepost']);
                        echo updates_like_comment();
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', ['class' => 'col-sm-4 notify-box','id'=>'notify-box-2','style'=>'display:none;']);
            echo html_writer::start_tag('div', ['class' => 'cardone ', 'id' => 'updates-box','style'=>'']);
                echo html_writer::start_tag('div', ['class' => 'update-container','style'=>'max-height:150px;']);
                    echo html_writer::tag('h3', get_string('top_posts', 'local_social_wall'),['id'=>'']);
                        echo updates_like_comment();
                echo html_writer::end_tag('div');

                echo html_writer::start_tag('div', ['class' => 'update-container','style'=>'max-height:200px;']);
                    echo html_writer::tag('h3', get_string('later_work', 'local_social_wall'),['id'=>'']);
                        echo updates_like_comment();
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div', ['class' => 'col-sm-8 rightside-content']);
            echo html_writer::start_tag('div', array('id'=>'flashmessage'));
                echo html_writer::start_tag('div', array('id'=>'flash','align'=>'left'));
                    echo html_writer::start_tag('div', array('id'=>'content','class'=>'render_msgs'));
                        echo html_writer::start_tag('div', array('id'=>'show_load_msgs'));
                        /**
                         * Shows specific message if passed id with url
                         * @author  Jayesh T
                         * @since   29 May 2020
                         */
                        if($msgid){
                            $type = optional_param('t', 0 ,PARAM_TEXT);
                            $userid = optional_param('u', 0 ,PARAM_INT);
                            $datecreated = optional_param('d', 0 ,PARAM_INT);
                            $notifiedpost = see_notified_post($type, $msgid, $userid, $datecreated);
                            $notifiedpostobj = json_decode($notifiedpost);
                            if($notifiedpostobj->status){
                                echo $notifiedpostobj->html;
                            } else {
                                echo html_writer::start_tag('div', ['class' => 'no-post']);
                                    echo '<h3>'. get_string('nonotification', 'local_social_wall').'</h3>';
                                echo html_writer::end_tag('div');
                            }
                        } else {
                            echo load_messages();
                        }
                        echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'modal fade','id'=>'myModal','style'=>'height: 400px;overflow-y: scroll;']);
        echo html_writer::start_tag('div', ['class' => 'modal-dialog','style'=>'width: 300px']);
            echo html_writer::start_tag('div', ['class' => 'modal-content']);
                echo html_writer::start_tag('div', ['style'=>'padding: 15px;border-bottom: 1px solid #e5e5e5;background-color: lightgray;']);
                    echo html_writer::tag('button','<i class="fa fa-close"></i>', ['class' => 'close','type'=>'button','data-dismiss'=>"modal",]);
                        echo html_writer::tag('h4', 'Likes',['style'=>'font-size: 18px;']);
                    echo html_writer::end_tag('div');
                    echo html_writer::start_tag('div', ['class' => 'modal-body','id'=>'whoslikedata']);
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', ['class' => 'modal fade','id'=>'userBioModal','style'=>'height: 500px;overflow-y: scroll;']);
        echo html_writer::start_tag('div', ['class' => 'modal-dialog','style'=>'width: auto']);
            echo html_writer::start_tag('div', ['class' => 'modal-content']);
                echo html_writer::start_tag('div', ['style'=>'padding: 15px;border-bottom: 1px solid #e5e5e5;background-color: lightgray;']);
                    echo html_writer::tag('button','<i class="fa fa-close"></i>', ['class' => 'close','type'=>'button','data-dismiss'=>"modal",]);
                        echo html_writer::tag('h4', 'User details',['style'=>'font-size: 18px;']);
                    echo html_writer::end_tag('div');
                    echo html_writer::start_tag('div', array("class" => "alert alert-danger", "style" => "display:none",'id'=>'validation-box'));
                        echo html_writer::start_tag('span', array("class" => "close", "data-dismiss" => "alert"));
                            echo html_writer::tag('span', 'x', array());
                            echo html_writer::end_tag('span');
                        echo html_writer::tag('p', '', array('id' => 'alert_content'));
                    echo html_writer::end_tag('div');
                   // echo html_writer::start_tag('form', ['class' => 'form-body','id'=>'user_bio_form','autocomplete'=>'off','action'=>new \moodle_url('/local/social_wall/index.php'),'method'=>'post']);
                    echo html_writer::start_tag('div', ['class' => 'modal-body','id'=>'whoslikedata']);
                         $mform4 = new userbioform($PAGE->url);
                         $mform4->display();            
                    echo html_writer::end_tag('div');

                    //footer
                    echo html_writer::start_tag('div', ['class' => 'modal-footer']);
                        echo html_writer::tag('button', 'Save',['class' => 'btn btn-round btn-primary','type'=>'button','id'=>'saveuserbio']);
                        echo html_writer::tag('button', 'Cancel',['class' => 'btn btn-round btn-secondary',"data-dismiss" => "modal"]);
                    echo html_writer::end_tag('div');
                   // echo html_writer::end_tag('form'); 
                echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

// Show footer.
echo $OUTPUT->footer();
if(!empty($mform4->get_data())){
    echo "<pre>";
    print_r($mform4->get_data());exit;
}
