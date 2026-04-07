<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../../../config.php');
global $DB, $SESSION;
header("Content-type: text/css; charset: UTF-8");
require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
require_once($CFG->dirroot . '/theme/remui/lib.php');
$company_id = 0;
if (isset($SESSION->currenteditingcompany)) {
    $company_id = $SESSION->currenteditingcompany;
} else if (iomad::is_company_user() || iomad::is_company_admin()) {
    $company_id = iomad::is_company_user();
} else if (get_company_by_host() > 0) {
    $company_id = get_company_by_host();
}


$brandarr = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'brandprimary_' . $company_id));
$brandcolor = (isset($brandarr->value) && $brandarr->value != "") ? $brandarr->value : "#1ba2dd";

$bgColor = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'bodybackground_' . $company_id));
$bodycolor = (isset($bgColor->value) && $bgColor->value != "") ? $bgColor->value : "#fff";

$bgotherColor = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'bodybackground_' . $company_id));
$bodycolorotherpages = (isset($bgotherColor->value) && $bgotherColor->value != "") ? $bgotherColor->value : "#f6f6f6";

$marketingColor = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'marketimagebg_' . $company_id));
$marketingtiles = (isset($marketingColor->value) && $marketingColor->value != "") ? $marketingColor->value : "#1ba2dd";

$extracss = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'css_' . $company_id));
$postcss = (isset($extracss->value) && $extracss->value != "") ? $extracss->value : "";

$loginboxopacityval = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'loginboxopacity_' . $company_id));
$loginboxopacity = (isset($loginboxopacityval->value) && $loginboxopacityval->value != "") ? $loginboxopacityval->value : "1";

$fontnameval = $DB->get_record('config_plugins', array('name' => 'theme_remui', 'name' => 'fontnametheme_' . $company_id));
$fontname = (isset($fontnameval->value) && $fontnameval->value != "") ? $fontnameval->value : 'Poppins';

$inputsshadow = "0 1px 5px 0 rgba($brandcolor, .9)";

$css = '';

$theme = \theme_config::load('remui');

//$css .= 'body {font-family: ' . $fontname . ' !important;}';
/**
 * Apply Uploaded Font Family File for Company Tenant.
 * Developer: Abhishek Vaidya
 * @autor: remui.
 * @date: 19/11/2020
 * Ticket: Feature #89: Option for change system font
 * @remui
 */

// $fontid = $fontnameval->value; 
// $fontid = ltrim($fontid, 'id_'); 
// $fontuploaddata = $DB->get_record('font_upload_setting', array('id' => $fontid));
// if(isset($fontuploaddata->font_family)) {
//     $prescss .= "@font-face {
//        font-family: ".$fontuploaddata->font_family.";
//        font-style: ".$fontuploaddata->font_style.";
//        font-weight: ".$fontuploaddata->font_weight.";
//        src: url([[font:theme|/".$fontuploaddata->font_file."]]); 
//        src: local('".$fontuploaddata->fontfamily."'), local(".$fontuploaddata->font_type.")}";

//     $css .= 'body {font-family: ' . $fontuploaddata->font_family . ' !important;font-style: ' . $fontuploaddata->font_style . ' !important;font-weight: ' . $fontuploaddata->font_weight . ' !important;}';
// }else {
//     $css .= 'body {font-family: ' . $fontname . ' !important;font-style: normal !important;font-weight: 400 !important;}';
//     $css .= ".icon_text_left_nav{
//         font-family: $fontname !important;font-style: normal !important;font-weight: 400 !important;
//     } ";
// }
// END
//Other page background  css
// $theme->setting_file_url("tenant_loginimage_$company_id", "tenant_loginimage_$company_id");
function get_tenant_login_image_url($company_id) {
    global $CFG;

    if (empty($company_id)) {
        return null;
    }

    $context = context_system::instance();
    $fs = get_file_storage();

    $files = $fs->get_area_files(
        $context->id,
        'theme_remui',
        'tenant_loginimage_'.$company_id,
        0,
        'itemid, filepath, filename',
        false
    );

    if ($files) {
        $file = reset($files);

        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    return null;
}
$loginbg = get_tenant_login_image_url($company_id);
/*Body background secondary(otherpage)*/
$css .= "#page-local-learningpaths-index.company$company_id, 
                .company$company_id.path-local, 
                #page-local-people-index.company$company_id, 
                #page-admin-user-editadvanced.company$company_id, 
                #page-local-reports-index.company$company_id ,
                .company$company_id.path-blocks-configurable_reports, 
                .company$company_id.pagelayout-standard,
                .company$company_id.pagelayout-admin,
                .company$company_id.path-blocks-xpremui, 
                
                #page-local-learningpaths-edit  .bootstrap-datetimepicker-widget .datepicker table.table-condensed  thead tr:nth-child(2),
                #page-local-learningpaths-index .bootstrap-datetimepicker-widget .datepicker table.table-condensed  thead tr:nth-child(2),
                body#page-local-reports-report_schedule-index div#plms-course-wizard ul.nav-pills li.nav-item a{
                    background: #f5f5f5 !important;
                }";
$css .= ".company$company_id.pagelayout-mypublic {
background-color: #f6f6f6!important;
}";

$css .= "body{
                    background: #f6f6f6!important;
                }";

$css .= ".sidebar{
            background-color: $bodycolor !important;

        }";
$css .= ".navbar{
            background-color: $marketingtiles !important;

        }";

$css .= ".company$company_id.pagelayout-login #page{
                    background-image: url($loginbg) !important; 
                    background-size:cover; 
                    background-position:center;
                    background-color: $brandcolor !important;
                } ";


$css .= "#page-login-signup.company$company_id form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler{
                    border: none !important;  
                } ";

$css .= ".company$company_id.pagelayout-signup #page{
                    background-image: url(" . $loginbg . ") !important; 
                    background-size:cover; 
                    background-position:center;
                    background-color: $brandcolor !important;
                } ";
$css .= "#banner-img-slider {
    background-image: url(" . $loginbg . ") !important; }";
$css .= "#page-login-signup.company$company_id form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler{
                    border: none !important;  
                } ";

$css .= "#page-course-index.company$company_id .search-filters .coursecataloglist #learningpath_cat:focus{
                    background-color: $brandcolor !important; 
                    color:#fff !important; 
                    border: 1px solid $brandcolor !important;
                }";

$css .= "body.company$company_id #sidebar>ul {
                    float: none!important;
                }";

/*Button color*/
$css .= " 
              
            
                #back-to-top,
                .next-step,
                form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler,
                button#columns_button, 
                .sidebar aa:hover,
                .bulk-actions a.btn-primary,
                a.mt-button, 
                #changenumsections a,
                #plms-course-wizard1 .btn #form-bulk-actions a.btn-primary,
                button.btn.btn-lw-coursesstart, 
                .modal-footer button.btn-primary, 
                .tag-info, 
                .adminwarning.availableupdatesinfo .moodleupdateinfo.maturity200 .info.release,
                .assignfeedback_editpdf_widget .label,
                .new-btn-rnd,
                .coursecataloglist li.ui-sortable-handle:hover, 
                .coursecataloglist li.active,
                .lp-course-prerequisites button.add-prerequisites,
                .modal-dialog .btn-primary,.modal-dialog .btn-primary,
                #page-course-index .accordion .parent.selected.active,
                #block_lpd_content .icon_ok,
                div.moodle-has-zindex a.btn.btn-round.btn-secondary.done.active,
                table.generaltable td.cell a.btn{
                    background-color: $brandcolor !important; 
                    color:#fff !important; 
                    border: 1px solid $brandcolor !important;
                }";
/*Dashed border*/
//        $css .= ".mtdash .mt_btn_add{
//                    border: 1px dashed $brandcolor !important;
//                }";
$css .= "#page-local-iomad-dashboard-index .mt_boxing_btn{
                    border: 1px dashed $brandcolor !important;
                }";
/*solid border*/
$css .= " div.contentblock div.selected a,
                  div.contentblock div.blocks_pd a:hover{
                    border: 1px solid $brandcolor !important;
                }";
/*Non Background button*/
$css .= ".new-button,.new-button-line-l,
                 #edw-quick-menu .quick-menu-nav .menu-item,
                 #edw-quick-menu .quick-menu-nav .menu-item .customizer-editing-icon,
                 #edw-quick-menu .close-quick-menu,
                #page-site-index .block_remui_team .content-search,
                div.moodle-has-zindex div.modal-footer button[data-action='cancel']{
                    color:$brandcolor !important; 
                    // border: 1px solid $brandcolor !important;
                }";

$css .= "body#page-local-lms_reports-index a.btn.btn-primary.new-button{color:#fff !important;}";

/*Header Point*/
$css .= ".total-point{
                    color : $brandcolor !important;
                    font-size: 14px !important;
                    font-weight: 700;
                }";
$css .= ".total-point span.counter,
                   div.settings-menu a#add-block i,
                   div.settings-menu a#cm_setting_btn i,
                   body.format-remui header a.back_page i,
                   table.minicalendar.calendartable th.header.text-xs-center,
                   aside.block.block_calendar_month .card-block span.current a,
                   label #sidebar_btn, 
                   .navbar #usernavigation .popover-region:not(.collapsed) .popover-region-toggle .edw-icon,
                   .score-desplay i.fa.fa-trophy,
                   button.colortoggle,
                   .navbar .edw-icon, 
                   body#page-admin-search h4 a,
                   .card-block .calendarwrapper span.current a, #nav-mail-popover-container i.icon.fa.fa-envelope.fa-fw{
                    color : $brandcolor !important;
                }";

$css .= ".navbar .simplesearchform span.edw-icon.edw-icon-Search {color:#fff !important;}";

#learningcontent .search-filters .new-button, 
$css .= ".new-button:hover,
                .new-button-line:hover, 
                .new-button-line-l:hover,
                #page-local-learningpaths-index .row.course-lp.checkbbg, #page-local-learningpaths-view .row.course-lp.checkbbg{
                    color: #fff!important;
                    background-color: $brandcolor !important;
                    border-color: $brandcolor !important;
                }";

/*Background color white*/
$css .= "div.siwitch_content .checkbox.checbox-switch label span:before{
                    background-color: #fff !important;
                }";

/*Text and Button Hover*/
$css .= ".headerfade .breadcrumb-nav ul li a,
                div[role=main] h2,
                .headerfade .breadcrumb-nav ol li a,
                div.settings-menu a#add-block,div.siwitch_content .txt_switch,
                .generaltable_remui .header .span-header,
                .current-courses table tr th,
                #sidebar ul li:not(.tab_active):hover a i,
                .mt-dashboard-header .editcompanieslink, 
                .mt-dashboard-header .editcompanieslink fa:before,
                table.report-table thead th,
                .btn-back,
                div.panel-settings div.btn-group button.dropdown-toggle,
                #sidebar ul li.selected:not(.tab_active) a:hover span.menu-title,
                #sidebar ul li:not(.tab_active) a:hover span.menu-title,
                #sidebar ul li:not(.tab_active) i:hover span.media-left,                
                header.navbar-light div#navbar-nav span.dropdown-toggle,
                header.navbar-light div#navbar-nav div.dropdown_lang a.dropdown-toggle,
                #block_remui_lpd_content div.headinglpd span.hd-lp,
                .block_courserecords .generaltable .item a, 
                .block_courserecords .generaltable .item .aspan,
                .block_iomad_company_admin .fa, 
                .block_iomad_company_admin .wid,
                div.panel-collapse div.row.item-menu span.item:hover a.reportname,
                .coursecataloglist .categorylist:hover i,
                .coursecataloglist .categorylist:hover i,
                .dropdown.nav-item .accordion-toggle:hover:after,
                .dropdown-toggle::after,
                .wid-icon-phback-to,
                ul.overview-item li.section_overview.active .content h3.overview-title a,
                #block-region-side-main-admin .fa, 
                #block-region-side-main-admin .wid:not(.icon_ok),
                #block-region-side-pre-admin .fa,
                header.navbar-light div#navbar-nav div.dropdown-menu-right,
                div.roletxt,a.back_page, 
                .plms-learningpaths .new-button,
                ul.section li.current_activity div.activityinstance i, 
                ul.section_overview li.current_activity div.activityinstance i, 
                ul.section li.current_activity span.editing_move i, 
                .section_overview li.current_activity span.editing_move i,
                ul.section li.current_activity a span.instancename,
                div.table-filters div.content-form form#form-bulk-actions table.table-people thead tr th.header a,
                div.table-filters div.content-form form#form-bulk-actions table.table-people thead tr th.header,
                .title_lp_index,
                header#navbar-remui div.teacherdashbutton_format_remui a, 
                header#navbar-remui div.studentdashbutton_format_remui a,
                #navbar-remui h3.course-title,
                .course-content h3, 
                .course-content h2,
                .user-info .content-desc i, 
                .remui-user-profile .user-info .content-desc a,
                .remui-login-activity .login-access div i,
                .remui-user-profile .user-panel .tab-content .tab-pane .text a,
                .avatar_initials span,
                div#page-header-desc div.singlebutton form .btn-secondary:hover,
                .button-view .view_button .viewitem.active,
                .button-view .view_button .viewitem.active .labelview,
                .button-view .view_button .viewitem:hover,
                .button-view .view_button .viewitem:hover .labelview,
                .plms-category-listing .category-listing-actions a,
                .ps-course-listing .course-listing-actions a,
                .ps-course-listing li.listitem .icon:not(.fa-eye),
                .plms-category-listing .listitem.listitem-category .icon:not(.fa-eye),
                .coursecataloglist .categorylist:not(.selected):hover span,
                .button-view .view_button .viewicontag:focus,
                ul#showhidecolumns li label.form-checkbox:hover,
                #block_lpd_content .lpd-lp-detail-head-column,
                .header-title span,
                ul#tabs-course-format li.nav-item div.menubar div.dropdown a.dropdown-toggle i,
                header.navbar-light .multi-column-dropdown li a:hover,
                header.navbar-light span.new_button div.dropdown .dropdown-menu ul a:hover,
                #sidebar li.dropdown.nav-item:not(.tab_active).selected > a i:before,
                #sidebar li.menu-exp:not(.tab_active).selected > a i:before,
                .title-upcoming a, 
                .title-upcoming i,
                div#course_content div.menubar div.dropdown div.dropdown-menu-right a[role=menuitem]:hover,
                div.section_action_menu div.dropdown-menu-right a.dropdown-item:hover,
                div.table-filters div.options-table div.dropdown-menu a:hover,
                h3.sectionname:before,
                .coursecataloglist #learningpath_cat:not(.active):hover a,
                .coursecataloglist #learningpath_cat:not(.active):focus,
                div.dropdown div.dropdown-menu-right a[role=menuitem]:hover span,
                #page-local-learningpaths-edit  .bootstrap-datetimepicker-widget .datepicker table.table-condensed  thead tr:nth-child(2) th.dow,
                #page-local-learningpaths-edit  .bootstrap-datetimepicker-widget .datepicker table.table-condensed tr:nth-child(1) th,
                #page-local-learningpaths-index  .bootstrap-datetimepicker-widget .datepicker table.table-condensed  thead tr:nth-child(2) th.dow,
                #page-local-learningpaths-index  .bootstrap-datetimepicker-widget .datepicker table.table-condensed tr:nth-child(1) th,
                .mtdash .mt_btn_color.mt_btn_add .fa:before,
                div.settings-menu a#add-block i,
                div.settings-menu a#cm_setting_btn i,
                #page-local-iomad-dashboard-index #page-content .backtotenant a span.fa,
                #page-mod-ilt-view a[href='sessions.php?f=1'],
                #page-mod-ilt-view .exporttofile .addnewsession,
                #page-mod-ilt-view .addnewsession,
                body.format-remui header a.back_page i,
                header.navbar-light div#navbar-nav .dropdown-item:hover,
                #block_lpd_content .wid-icon-phcertificate.issued
                {
                    color: $brandcolor !important;
                }";

//@author: akshay pingale 141 - Top navigation new UI
$light_color = adjustBrightness($brandcolor, '0.9');
$css .= "body#page-user-profile div.user-panel div.tab-content #tab-4 .search-filter-wrap .search-box-wrap .men-search-phx{
            background-color: $brandcolor !important;
        }";
$css .= ".select2-container--default .select2-results__option--highlighted[aria-selected] {
                  background-color: $light_color !important;
                  color: $brandcolor !important;
                  }";
$css .= ".select2-container--default .select2-results__option[aria-selected=true] {
                 background-color: $light_color !important;
                 }";
$css .= "aside.block_lpd div.headinglpd {
                   background-color: $light_color !important;
                 }";
$css .= "header.navbar-light div#navbar-nav span.avatar.current {
                   color: $brandcolor !important;
                 }";
$css .= "header.navbar-light div#navbar-nav button.add-btn,
                #page-site-index .block_remui_team .content-search i, 
                #page-my-index .block_remui_team .content-search i,
                td.hasevent a, aside.block.block_calendar_upcoming .date_style{
                 background-color: $brandcolor !important;
                }";
$css .= "header.navbar-light div#navbar-nav .men-search-phx{
                  background-color: $brandcolor !important;
                  color: #fff !important;
                }";
$css .= "header.navbar-light .form-inline .form-control{
                  border: solid 2px $brandcolor !important;
                }";

$css .= "header.navbar-light div#navbar-nav button.dropdown-toggle{
                    
                }";

$css .= "header.navbar-light div#navbar-nav div.check-switchrole div.roletxt{
                   
                }";

$css .= "header.navbar-light div#navbar-nav .btn-i i,
                    #page-site-index .block_remui_team .team-icon.right i{
                 color: $brandcolor !important;
                }";

$css .= "header.navbar-light div#navbar-nav a.btn_settings i{
                 color: $brandcolor !important; 
                }";

$css .= "header.navbar-light div#navbar-nav a.btn_settings span{
                 color: $brandcolor !important; 
                }";
$css .= "#page-local-enroll_by_profile-index input#search {
                   border: 2px solid $brandcolor !important;
                }";

$css .= "#page-local-enroll_by_profile-index div.card-block i.men-search-phx {
                  background-color: $brandcolor !important;
                }";

$css .= "#page-local-enroll_by_profile-index a.rules_btn {
                 background: $brandcolor 0% 0% no-repeat padding-box !important; 
                }";

$css .= "#page-local-enroll_by_profile-index a.delete_rule_all,
                 #page-local-enroll_by_profile-index a.disable_rule_all {
                 background-color: $light_color !important;
                }";

$css .= "#page-local-enroll_by_profile-index div.card-block i.delete_icon,
              #page-local-enroll_by_profile-index div.card-block i.disable_icon {
              color: $brandcolor !important;
               }";

$css .= "#page-local-enroll_by_profile-index table#mortalEngines thead tr th {
              color: $brandcolor!important;
        }";

$css .= "#page-local-enroll_by_profile-index table#mortalEngines thead {
              background-color: $light_color !important;
         }";

$css .= "#page-local-enroll_by_profile-index .modal-footer button.close_lms_mod {
           background-color: $light_color !important;
           color: $brandcolor !important;
         }";
$css .= "#page-user-profile .userprofile ul.nav-tabs a.nav-link.active, #page-user-profile .userprofile ul.nav-tabs a.nav-link.active:focus, #page-user-profile .userprofile ul.nav-tabs .nav-link.active:hover{ 
            color: $brandcolor !important;
            border-bottom: 5px solid $brandcolor !important; }";

$css .= "body#page-user-profile div.user-panel div.tab-content div.contentnode span:first-child { color: $brandcolor; }";

$css .= "body#page-user-profile div.user-panel div.tab-content #tab-3 .tool_policy a, body#page-user-profile div.user-panel div.tab-content #tab-3 .retentionsummary a {
              background: $light_color 0% 0% no-repeat padding-box!important;
              border: 2px solid $brandcolor!important;
          }";

$css .= "body#page-user-profile .select2-container--default .select2-selection--single {
            border: 2px solid $brandcolor;
          }";

/*$css .= ".men.men-search-phx{
  background-color: $brandcolor!important
}";*/

$css .= ".search-filter-wrap .search-box-wrap .search-course-box {
            border: 2px solid $brandcolor;
          }";

$css .= ".generaltable .header{
            background: $light_color !important;
            }";

$css .= "body#page-user-profile div.remui-user-profile div.img-user div.edit-profile {
            background: $light_color !important;
           }";
/*$css .= "header.navbar-light div#navbar-nav div.btn_settings_on span.avatar.current{
         border: 2px solid $brandcolor !important;
        }";*/

$css .= ".total-point{
                    color : $brandcolor !important;
                    font-size: 14px !important;
                    font-weight: 700;
    
                }";

$css .= ".total-point span.counter{
                    color : $brandcolor !important;
                }";

$css .= "ul li.tab_active a, ul li.tab_active a:hover, #nav-drawer ul li.dropdown.nav-item.tab_active_new {
                 border-left: 5px solid $brandcolor !important;
                 background-color: $light_color !important;
               }";

$css .= "#page-local-reports-index #system.reports-panel{  display:none; }";

$css .= "#learningpath-view-tab .description i{  color: $brandcolor !important; }";
$css .= "ul.lpd-courses li.header div span{
                    color: $brandcolor !important;
                }";

$css .= "#block_lpd_content .icon_ok.wid-icon-checked{
                    padding : 6px 0 0 6px !important;
                }           
                #page-local-social_wall-index .comment_button .men.men-send:before,
                #table_users tr.head td,
                body.format-remui .section li.current_activity a span.instancename,
                body.format-remui .section li.current_activity span.editing_move i,
                body.format-remui [role=main]>h2,
                body.format-remui h3,
                #learningpath-notifications-tab .mform .txt_area_txt label{
                    color: $brandcolor !important;
                }
                #block_lpd_content div.lpd-lp-content div.lpd-lp-content-header:hover, 
                #block_lpd_content div.lpd-lp-content div.lpd-lp-content-header:not(.collapsedlpd),
                div.lpd-lp-content-header:not(.collapsedlpd) + div.lpd-lp-detail,
                .plms-learningpaths div.plms-learningpath:hover{
                    border-left : 4px solid $brandcolor !important;
                }
                
                h3.sectionname.selected,
                #edit-reports-form .bg-plms-foreground-forced,
                #report_accordion .reports-panel h6.panel-title a.plms-reports-dropdown:hover, 
                #report_accordion .reports-panel h6.panel-title a.plms-reports-dropdown.active,
                .panel-group .panel h4.panel-title a.plms-reports-dropdown:hover, 
                #plms-course-wizard ul.wz-steps li.new-tab.active, 
                #plms-course-wizard ul.wz-steps a.add-tooltip.active,
                .panel-group .panel h4.panel-title a.plms-reports-dropdown.active,
                .plms-category-listing li.listitem[data-selected='1'], 
                .plms-category-listing .listitem[data-selected='1']>div,
                .ps-course-listing #course-listing-title,
                .choosercontainer.remuichooser_item #chooseform .moduletypetitle.catselected,
                .coursecataloglist .categorylist:active,.coursecataloglist .categorylist:active, 
                .coursecataloglist .categorylist:visited, 
                .coursecataloglist .categorylist:link,
                .coursecataloglist .categorylist:link, 
                .coursecataloglist .categorylist:focus,
                .coursecataloglist .categorylist:focus, 
                .coursecataloglist .categorylist.selected,
                .coursecataloglist .categorylist.selected,
                .page-item.active .page-link, 
                .page-item.active .page-link:focus, 
                .page-item.active .page-link:hover,
                div#plms-course-wizard.create-course div#edit-plugin-form.editplugin form div.panel-body div.coursecataloglist div#accordion div.panel-heading h4.panel-title a.plms-reports-dropdown[aria-expanded=true],
                div.search-filter-buttons div.report-left-block-acordeon div.filterpeople div.content-filtter div.filterslist div.panel-heading:hover h5.panel-title a.plms-reports-dropdown,
                body#page-course-index .explore-course-category.parentcat.selected.active .header-category,
                body#page-local-reports-index .reports-panel h6.panel-title a.plms-reports-dropdown:hover, 
                body#page-local-reports-index .reports-panel h6.panel-title a.plms-reports-dropdown.active,
                #block_lpd_content .icon_ok,
                .plms-learningpaths .new-button:hover{
                    background-color: $brandcolor !important; 
                    color:#fff !important; 
                    border: 1px solid $brandcolor !important;
                }
    
                body#page-course-index .explore-course-category.parentcat.selected.active .header-category i,
                body.format-remui header#page-header ul.nav.nav-tabs .nav-link.active,
                .coursecataloglist .categorylist.selected .namecategory:hover{
                    color:#fff !important;
                }
                
                /*Only Background*/
    
                #remui-chatbot .chatbot-heading,
                .markettiles .market-tile .btn-market .text_to_html,
                .progressbar,.block_slideshow .wid-icon-arrowright,
                .block_slideshow .wid-icon-back-large,
                .progress-bar,
                .mynotes-pos-inline, .mynotes-pos-rb, 
                .mynotes-pos-lb, 
                .mynotes-pos-rt, 
                div.siwitch_content .checkbox.checbox-switch.switch-primary label>input:checked + span, div.siwitch_content .checkbox-inline.checbox-switch.switch-primary>input:checked + span,
                .mynotes-pos-lt,
                #page-local-people-index .plms-reports-dropdown.active,
                #edit-reports-form .coursecataloglist .ui-sortable-handle.active,
                input:checked + .slider , 
                #block_lpd_content .icon_ok,
                div.contentblock div.selected span.block_select,
                body#page-local-reports-report_schedule-index div#plms-course-wizard ul.nav-pills li.nav-item a.active{
                    background-color: $brandcolor !important;
                    color:#fff !important
                }
                
                body:not(.pagelayout-frontpage) a.visibleifjs:focus,
                body:not(.pagelayout-mydashboard) a.visibleifjs:focus, 
                body:not(.pagelayout-coursecategory) a.visibleifjs:focus,
                body:not(.pagelayout-frontpage) div.form-radio input[type='radio']:checked + label:before,
                body:not(.pagelayout-mydashboard) div.form-radio input[type='radio']:checked + label:before,
                body:not(.pagelayout-coursecategory) div.form-radio input[type='radio']:checked + label:before,
                body:not(.pagelayout-frontpage) input[type=text]:focus, select:focus,
                body:not(.pagelayout-mydashboard) input[type=text]:focus, select:focus,
                body:not(.pagelayout-coursecategory) input[type=text]:focus, select:focus,
                body:not(.pagelayout-frontpage) .select2-container--default .select2-selection--multiple:focus,
                body:not(.pagelayout-mydashboard) .select2-container--default .select2-selection--multiple:focus,
                body:not(.pagelayout-coursecategory) .select2-container--default .select2-selection--multiple:focus,
                body:not(.pagelayout-frontpage) input[type=password]:focus,
                body:not(.pagelayout-mydashboard) input[type=password]:focus,
                body:not(.pagelayout-coursecategory) input[type=password]:focus,
                body:not(.pagelayout-frontpage) textarea.form-control:focus,
                body:not(.pagelayout-mydashboard) textarea.form-control:focus,
                body:not(.pagelayout-coursecategory) textarea.form-control:focus{
                    box-shadow: $inputsshadow;
                }
    
                
                /*Border Color Only*/
                h3.sectionname{
                    border-color : $brandcolor !important;
                }
                
                /*Border*/
                .path-blocks-configurable_reports input#id_cancelbutton,
                .button-view .view_button .viewicontag:active,
                div.modal-footer button[type=button].btn-primary,
                div#addcontrols input#add{
                    border: 1px solid $brandcolor !important;
                }
                /*Loader*/
                header.navbar-light .loader {
                    border-top: 2px solid $brandcolor !important;
                }
    
                /*File Picker CSS*/
               
               
                #element_custom_navigation  #item_lefnavigation  a span.menu-title,
                body.format-remui ul.topics li a.active,
                .block_currentcourses th.header{
                    color: $brandcolor !important;
                }
                #element_custom_navigation  #item_lefnavigation a.active_nav{
                    background-color: $brandcolor !important; 
                    border: 1px solid $brandcolor  !important;
                }
                #element_custom_navigation  #item_lefnavigation a.active_nav span.menu-title,
                form.mform :not(.collapsed) legend.ftoggler a.fheader{
                    color: #fff !important;
                }
      
                li.current h3.sectionname,
                .course-content li.current .content ul.section{
                    background-color: lighten($brandcolor, 75%);
                }
                .cardcoursescontent div.content-card:hover a.img-courses div.hoverimg{
                    background-color: lighten($brandcolor, 75%);
                }
    
                body#page-login-index.company$company_id .content-form .forgetpass p a, 
                body#page-login-index.company$company_id div.newacoount a b, 
                body#page-login-index.company$company_id div.loginfooter span b,
                body#page-login-signup.company$company_id div.loginfooter span b, 
                body.company$company_id header.navbar-light div#navbar-nav span.login, 
                body.company$company_id .table_team th.header{
                    color: $brandcolor !important;
                }
                body#page-login-index.company$company_id div.content-login .card {
                    background-color: rgba( #FFFFFF, $loginboxopacity ) !important;
                }
                body#page-login-index.company$company_id div.content-login{
                    background-color: rgba( #FFFFFF, $loginboxopacity ) !important;
                }
                body#page-login-index.company$company_id #login_center .card {
                    background-color: rgba( #FFFFFF, $loginboxopacity ) !important;
                }
                .markettiles .marketcontent,
                .markettiles .market_tile{
                    background-color: $marketingtiles !important;
                }
                body.company$company_id .btn-primary:not(.new-button) {
                    background-color: $brandcolor !important;
                    border-color: $brandcolor !important; color:#fff !important
                }
                #page-local-learningpaths-edit  .bootstrap-datetimepicker-widget .datepicker ,
                #page-local-learningpaths-index  .bootstrap-datetimepicker-widget .datepicker{
                    border: solid 1px $brandcolor;
                    box-shadow: 0 1px 5px 0 $brandcolor;
                    -webkit-box-shadow: 0 1px 5px 0 $brandcolor;
                    -moz-box-shadow: 0 1px 5px 0 $brandcolor;
                    -ms-box-shadow: 0 1px 5px 0 $brandcolor;
                    -o-box-shadow: 0 1px 5px 0 $brandcolor;
                }";

$css .= "body#page-course-view-remui .span_align_overview { color: $brandcolor !important;}";


$css .= "aside.block.block_courses_statistics .element-groups a.view-course {
                     color: $brandcolor !important;
                     font-size: 13px;
               }";

$css .= "#page-local-tenant_appearance-index .font_trash {
                font-size: 16px;
                color: $brandcolor !important;
                }";
$css .= ".left_power_icon {
                color: $brandcolor !important;
               }";
$css .= ".left_text_power_icon {
                color: $brandcolor !important;
               }";
$css .= "ul li.tab_active a i,ul li.tab_active a h6,ul li.tab_active a{
                color: $brandcolor !important;
                }";
$css .= ".block_user_profile .card.hovercard .cardheader {
                background-color: $brandcolor !important;
                }";
$css .= ".block_user_profile .viewprofile, .userprofile_icon, .count_no_class {
                color: $brandcolor !important;
                }";
$css .= "#page-course-index-category .course_search , 
                #page-course-index-category #appendalltags , 
                #page-course-index-category .tagsearch {
                border: solid 2px $brandcolor !important;
                }";
$css .= "#page-course-index-category .coursesearch_icon , 
                #page-course-index-category .searchicontag, 
                #page-course-index-category .appendtagname, #page-course-index-category .coursecatalogtag a.active{
                    background-color: $brandcolor !important;
                }";
$css .= "#page-course-index-category .filter-button {
                    color: $brandcolor;
                }";
$css .= "#page-course-index-category .coursecataloglist .all-categories.categorylist > a {
                    color: $brandcolor;
                 }";
$css .= "#page-course-index-category .coursecataloglist .all-categories.categorylist {
                    border: solid 2px $brandcolor !important;
                 }";
$css .= " body#page-course-index-category .accordion .parent.selected.active {
                    color: $brandcolor;
                    border: solid 2px $brandcolor !important;
                 }";
$css .= "body#page-course-index-category .singlebutton button[type='submit']{
                    background-color:none !important;
                    color :$brandcolor !important;
                    border: none!important;
                 }";
$css .= "body#page-course-index-category div#page-header-desc div.singlebutton button:hover{
                    color :$brandcolor !important;
                 }";
$css .= "#nav-drawer.active div.logo_responsive{
                    height: 87px !important;
                 }";
$css .= "body#page-admin-search h4.heading-settings a{
                    color: $brandcolor !important;
                 }";
$css .= "body#page-site-index table.minicalendar.calendartable td.hasevent a{
                    background-color: $brandcolor !important;
                 }";
$css .= ".hidden-print.moodle-has-zindex.active span i.siteCss{
                    padding: .75rem 1.25rem;
                 }";
$css .= ".hidden-print.moodle-has-zindex.active .iconcolor{
                    background-color: white;
                    border: none;
                 }";
$css .= "body#page-site-index table.minicalendar.calendartable td.hasevent a,
                 body#page-calendar-view table.minicalendar.calendartable td.hasevent a,
                 body#page-my-index table.minicalendar.calendartable td.hasevent a{
                    background-color: $brandcolor !important;
                 }";
$css .= "body:not(.pagelayout-frontpage) form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler, 
                 body:not(.pagelayout-mydashboard) form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler, 
                 body:not(.pagelayout-coursecategory) form.mform fieldset.collapsible:not(.collapsed) legend.ftoggler,
                 div.current-courses .generaltable.fixed-table td a.btn{
                    background-color: $brandcolor !important;
                 }";
$css .= "body#page-site-index div.moodle-has-zindex div.modal-footer button[data-action='cancel']:hover{
                    color :$brandcolor !important;
                 }";
/*$css .="ul li.tab_active a,ul li.tab_active a:hover {
    background-color: $brandcolor !important;
}";*/
$css .= "#page-course-index-category .coursecataloglist .categorylist.selected{
                    background: none;
                }";
$css .= "#page-course-index-category .coursecataloglist #learningpath_cat.categorylist.selected {
                    background-color: $brandcolor !important;
                    color: $brandcolor !important;
                    border: 1px solid $brandcolor !important;
                }";

$css .= "#page-course-index-category .coursecataloglist #learningpath_cat:focus {
                    background: none !important;
                }";
$css .= "#block-region-side-pre-admin .block_user_profile .icon {
                color: #575757 !important;
                }";
$css .= "aside.block.block_calendar_upcoming .not_date_style {
                   background-color: $brandcolor !important;
                }";
$css .= "aside.block.block_calendar_upcoming .current_date_style {
                   border: 1px solid $brandcolor !important;
                   }";
$css .= "aside.block.block_calendar_upcoming .current_date_style span.col-lg-2.col-md-2.col-xs-2 {
                    color: $brandcolor !important;
                }";
$css .= "#page-course-index-category .close_popup_search_box {
                    color: $brandcolor !important;
                }";
$css .= "aside.block.block_calendar_upcoming .dottedclass {
                    border-left: 3px dotted $brandcolor !important;
                }";
$css .= "aside.block ul.pagination li.page-item.active a {
                    color: $brandcolor !important;
                    background-color: #fff !important;
                }";
//$light_color = adjustBrightness($brandcolor,'0.9');
$css .= "#sidebar ul:not(.in) li:not(.tab_active) a:hover {
                    background-color: $light_color !important;
                }";
$css .= "#page-site-index .table_team th.header {
                    background-color: $light_color !important;
                }";
$css .= ".block_remui_team .table_team .avatar_initials, header.navbar-light div#navbar-nav .avatar_initials, body#page-user-profile .avatar_initials,
                    body#page-site-index .carousel-indicators li {
                    background-color: $light_color !important;
                }";

$css .= ".block_courserecords div.course-myrecords-table div.header, aside.block_remui_lpd div.headinglpd {
                    background-color: $light_color !important;
                }";
$css .= "div.current-courses .generaltable.fixed-table thead {
                    background-color: $light_color !important;
                }";
$css .= ".card.block.mt_company_admin .iomadlink_container > div .iomadlink > div.iomadicon {
                    background-color: transparent !important;
                }";
$css .= "aside.block.block_calendar_upcoming .row_outer {
                    background-color: $light_color !important;
                }";
$css .= "#nav-drawer.active li.selected:not(.tab_active) {
                    background-color: #fff !important;
                }";
$css .= ".block_course_records .course_records_list .coursebox .coursedetails .coursefoot .btn-lw-coursesstart, .block_course_records .course_records_list .coursebox .coursedetails .coursefoot .btn-lw-coursescontinue{
                    background-color: $light_color !important;
                    color :$brandcolor !important;
                }";
$css .= ".block_course_records .course_records_list .coursebox .coursedetails .coursefoot .btn-lw-coursesstart:hover, .block_course_records .course_records_list .coursebox .coursedetails .coursefoot .btn-lw-coursescontinue:hover{
                    background-color: $brandcolor !important;
                    color :#fff !important;
                }";
$css .= "#element_custom_navigation #subitem20 a.active_nav{
                    background-color: #fff !important; 
                    border: 1px solid #fff  !important;
                    color :$brandcolor !important;
                }";
$css .= "#element_custom_navigation #subitem20 a.active_nav span.menu-title, aside.block_remui_lpd div.lpd-lp-content-header:before{
                    color :$brandcolor !important;
                }";
$css .= "#element_custom_navigation #item_lefnavigation ul.list-unstyled.collapse.show a.active_nav{
                    background-color: #fff !important; 
                    border: 1px solid #fff  !important;
                    color :$brandcolor !important;
                }";
$css .= "#element_custom_navigation #item_lefnavigation ul.list-unstyled.collapse.show a.active_nav span.menu-title{
                    color :$brandcolor !important;
                }";
$css .= "aside.block.block_calendar_upcoming .date, .block_online_users .online_cls{
                    color :$brandcolor !important;
                }";
$css .= ".slimScrollDiv::-webkit-scrollbar-thumb,div.moodle-has-zindex div.modal-body div.contentblock::-webkit-scrollbar-thumb {
                    background-color: $brandcolor !important;
                }";
$css .= "aside.block_news_items .newsitem .date, body#page-site-index .fa.fa-bullhorn, body#page-my-index .fa.fa-bullhorn {
                    color :$brandcolor !important;
                }";
$css .= "body#page-site-index .slick-prev::before, body#page-site-index .slick-next::before, #page-my-index .slick-prev::before, #page-my-index .slick-next::before{
                    color :$brandcolor !important;
                }";
$css .= "#page-local-people-index .select2-container--default .select2-results__option--highlighted[aria-selected] ,#page-local-people-index div.table-filters div.options-table div.dropdown-menu a:hover {
                         background-color: $light_color !important;
                }";
/*$css .="body:not(#page-local-remui_coursewizard-createcourse) ul.nav-tabs a.nav-link.active,body:not(#page-local-remui_coursewizard-createcourse) ul.nav-tabs a.nav-link.active:focus, body:not(#page-local-remui_coursewizard-createcourse) ul.nav-tabs .nav-link.active:hover{
    background-color: $brandcolor !important;
    color :#fff !important;
}";*/
$css .= "body#page-site-index .carousel-indicators .active {
                    background-color: $brandcolor !important;
                }";
$css .= ".arrow_link i.fa, #page-course-index .cardcoursescontent .card-header-image .numperclass, #page-course-index-category .cardcoursescontent .card-header-image .numperclass {
                    color :$brandcolor !important;
                }";
$css .= "#nav-drawer.active li.dropdown:hover,.path-blocks-configurable_reports .filter button.new-button:hover {
                    background-color: #fff !important;
                 }";
$css .= "header.navbar-light div#navbar-nav .dropdown-item:hover,body#page-blocks-configurable_reports-viewreport .avatar_initials {
                    background-color: $light_color !important;
                 }";
$css .= ".block_xpremui .xp-total,.block_xpremui nav .nav-button i.fa, #page-local-people-index div.options-table div.dropdown-menu a:hover {
                    color :$brandcolor !important;
                 }";
$css .= ".form-control:focus {
                    border-color :$brandcolor !important;
                 }";
$css .= "#page-blocks-iomad_company_admin-company_edit_form form.mform fieldset.collapsed legend.ftoggler{
                     background-color: $light_color !important;
                }";
$css .= "#page-local-mt_dashboard-cohort-assign #assignform .generaltable.generalbox.boxaligncenter div#removecontrols input, #page-local-mt_dashboard-cohort-assign #assignform .generaltable.generalbox.boxaligncenter input.btn-secondary , #page-local-mt_dashboard-cohort-assign #assignform .generaltable.generalbox.boxaligncenter div#addcontrols input#add {
                    background-color: $brandcolor !important;
                    color :#fff !important;
                 }";
$light_color_xp = adjustBrightness($brandcolor, '0.5');
$css .= ".block_xpremui-level-progress .xp-bar {
                    background-color: $brandcolor !important;
                    background-image: linear-gradient(to bottom, $brandcolor, $light_color_xp);
                 }";
$css .= "body.format-remui .section_search #search_activities {
                          border: solid 2px $brandcolor!important;
                      }";
$css .= "fieldset.input_field_remui i.men-search-phx {
                            background-color: $brandcolor!important;
                        }";
$css .= "body.format-remui header#page-header ul.nav.nav-tabs .nav-link.active:after {
                            border-bottom: 4px solid $brandcolor!important;
                        }";
$css .= "body.format-remui header#page-header ul.nav.nav-tabs .nav-link.active {
                            color: $brandcolor!important;
                        }";
$css .= "body.format-remui div.section_search ul.overview-item li.section_overview.active .sectionname.overview-title {
                            background: $light_color;
                        }";
$css .= "body.format-remui #course_content .btn-lw-courses.callajax, body.format-remui #course_content .btn-lw-courses,body.format-remui .header_bar_left_course {
                            background: $brandcolor;
                        }";
$css .= "body.format-remui .activity_btn_nm {
                              color: $brandcolor;
                          }";
$css .= "body.format-remui .Activities_list .coursefea_count{
                              color: $brandcolor;
                          }";
$css .= "body.format-remui table#participants thead tr th {
                            background-color: $light_color !important;
                            color: $brandcolor;
                        }";
$css .= "body.format-remui table#participants thead tr th a,#page-my-index .block_remui_team .team-icon.right i,#page-local-people-index #send,body.format-remui div.activities_navigator a i {
                            color: $brandcolor !important;
                        }";
$css .= "body.format-remui .custom-select:disabled , div.autocomplete_selection input.form-control,body#page-user-profile div.user-panel div.tab-content #tab-6 div.text span:hover{
                         border: solid 2px $brandcolor !important;
                        }";
$css .= "#page-local-people-index div.search-filter-buttons div.header-search div.custom-search-form input#txt, body#page-local-people-index .select2-container--default .select2-selection--single, #page-local-reports-index #txt,#page-my-index .block_remui_team .content-search, body.format-remui .custom-select {
                            border: 2px solid $brandcolor!important;
                        }";
$css .= "#page-local-people-index div.search-filter-buttons div.header-search div.custom-search-form i.men-search-phx,.path-blocks-configurable_reports input#id_cancelbutton {
                           color: #fff;
                           background: $brandcolor;
                       }";
$css .= "body#page-local-people-index #reset-form,#page-my-index .block_remui_team .content-search i,body.format-remui .activity_btn {
                         background-color: $brandcolor;
                     }";
$css .= "body#page-local-people-index .icon_css,body#page-local-people-index .btn_css {
                            color: #fff;
                        }";
$css .= ".select2-container--default .select2-selection--single .select2-selection__arrow b {
                    border-color: $brandcolor transparent transparent transparent!important;
                }";
$css .= ".select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
                    border-color: transparent transparent $brandcolor transparent!important;
                }";
$css .= ".select2-container--default .select2-results__option--highlighted[aria-selected] {
                    color: #fff;
                }";
$css .= "#page-local-people-index .select2-container--default .select2-selection--single .select2-selection__rendered {
                    color: $brandcolor !important;
                }";
$css .= "#page-local-people-index div.table-filters div.content-form form#form-bulk-actions table.table-people thead tr th a i.men-icon-phsorting, #page-local-reports-index .active_rep_name, #page-local-reports-index .dropdown-menu.report_cate_elli_icon_menu.show a.dropdown-item:hover, #page-local-reports-index .dropdown-menu.report_cate_elli_icon_menu.show a.dropdown-item:focus {
                    color: $brandcolor !important;
                }";
$css .= "#page-local-people-index thead,aside.block_remui_team .mt-table thead,#page-blocks-configurable_reports-viewreport form#sendemail table.report-table thead th {
                    background-color: $light_color !important;
                }";
$css .= "body#page-local-people-index .dropdwn_people {
                    background-color: $light_color !important;
                }";
$css .= "#page-local-social_wall-index .select2-container--default .select2-results__option--highlighted[aria-selected], #sidebar .tab_active_new .media-left i, ul li.tab_active_new a h6 {
                    color: $brandcolor !important;
                }";
$css .= "#page-local-social_wall-index .select2-container--default .select2-results__option[aria-selected='true'], #page-local-reports-index .table thead th, #page-local-reports-index .new-button-create_report {
                    background-color: $light_color !important;
                    color: $brandcolor !important;
                }";
$css .= "body#page-local-reports-index a.btn.btn-primary.new-button, #page-local-reports-index .reportsearch_icon {
                    background-color: $brandcolor !important;
                     color: #fff !important;
                }";
$css .= "#nav-drawer ul li.dropdown.nav-item.tab_active_new {
                color: $brandcolor !important;
                border-left: 5px solid $brandcolor !important;
                }";
$css .= "#sidebar .tab_active_new .media-left i,#page-local-reports-index .paginate_active {
                    color: $brandcolor !important;
                }";

$css .= "ul li.tab_active_new a h6,#page-blocks-configurable_reports-viewreport .filter button.new-button i,#page-blocks-configurable_reports-viewreport .totalrecordsnum , #navbar-nav .dropdown_lang li.dropdown.nav-item a.dropdown-toggle.nav-link:before {
                    color: $brandcolor !important;
                }";
$css .= "#page-local-social_wall-index #social-container-1 {
                    background-color:$light_color !important;
                }";
$css .= ".new-button-line {
                          color: #fff!important;
                          background-color: $brandcolor!important;
                          border-color: $brandcolor!important;
                      }";
$css .= ".new-button-line-reset {
                          color: $brandcolor!important;
                          background-color: $light_color!important;
                      }";
$css .= "#page-blocks-configurable_reports-viewreport ul.nav-tabs a.nav-link.active {
                            border-bottom: 3px solid $brandcolor!important;
                            color: $brandcolor!important;
                            background-color: #fff!important;
                        }";

$css .= "body.format-remui ul.nav-tabs a.nav-link.active,body.format-remui ul.nav-tabs a.nav-link.active:focus,body.format-remui ul.nav-tabs .nav-link.active:hover, body.format-remui .grade-navigation ul.nav.nav-tabs a.nav-link.active{
                    background-color: #fff !important;
                    color: $brandcolor !important;
                    border-bottom: 4px solid $brandcolor!important;
                }";
$css .= "#page-local-people-index button#send {
                         background: transparent;
                         border: 0 !important;
                }";
$css .= ".choosercontainer.remuichooser_item #chooseform .moduletypetitle.catselected,.choosercontainer.remuichooser_item #chooseform .moduletypetitle.catselected .catlabel {
                         background-color: $light_color !important;
                         color: $brandcolor !important;
                         border: 0 !important;
                }";
$css .= ".choosercontainer.remuichooser_item .searchmod .searchmodicon {
                         background-color: $brandcolor !important;
                }";
$css .= ".choosercontainer #chooseform .selected {
                        background-color: $light_color!important;
                        border: solid 2px $brandcolor!important;
                }";
$css .= ".choosercontainer.remuichooser_item #chooseform .category .typesummary .option .itemsummary {
                        background-color: $light_color;
                        border-bottom: 5px solid $light_color;
                }";
$css .= ".choosercontainer.remuichooser_item #chooseform .category .typesummary .option.selected label,#page-course-index-category .coursecatbtn_grp .dropdown-item:hover {
                         color: $brandcolor !important;
                }";
$css .= ".choosercontainer.remuichooser_item #chooseform .category .typesummary .option .itemsummary::-webkit-scrollbar-thumb{
                         background-color: $brandcolor !important;
                }";
$css .= ".path-blocks-configurable_reports li.ui-sortable-handle:hover, .path-blocks-configurable_reports li.active, #page-admin-tool-lp-edittemplate.path-admin legend, #page-admin-tool-lp-editcompetency legend, #page-user-profile .userprofile .user-info .user-content.row .profile-user-link .loin-as-div{
                         background-color: $brandcolor !important;
                }";
$css .= "body#page-blocks-configurable_reports-viewreport .dropdown-item:hover,.path-blocks-configurable_reports div.coursecataloglist.second-section div.panel-group ul#id_column_select div.values i.men-icon-phpencil {
                        color: $brandcolor !important;
                }";
$css .= ".btn-secondary,.btn-secondary:hover {
                        background-color: $light_color!important;
                        border: solid 1px $light_color!important;
                        color: $brandcolor !important;
                }";
$css .= "input[type='submit'].btn-secondary,button[type='submit'].btn-secondary,input.btn.btn-secondary,#page-user-profile .userprofile .user-info .user-content.row .profile-user-link .send_message-div a,#page-user-profile .userprofile .user-info .user-content.row .profile-user-link .send_message-div i {
                        color: $brandcolor !important;
                }";
$css .= "body#page-blocks-configurable_reports-viewreport ul#showhidedownload.dropdown-menu li.list-link>a.link:hover,#page-user-profile .userprofile .user-info .user-content.row .profile-user-link .send_message-div{
                        color: $brandcolor !important;
                        background-color: $light_color !important;
                }";
$css .= "body.format-remui div.dropdown div.dropdown-menu-right a[role=menuitem]:hover,div.options-header .drop_elements:hover{
                        background-color: $light_color !important;
                }";
$css .= "body#page-user-profile .select2-container--default .select2-selection--single .select2-selection__arrow b,body#page-user-profile .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
                        border-top: 2px solid $brandcolor;
                        border-left: 2px solid $brandcolor;
                        border-color: $brandcolor!important;
                    }";
$css .= "#page-message-index .message-app .conversationcontainer .d-flex.align-items-center .msg_input_br {
                        border: 2px solid $brandcolor;
                    }";
$css .= "#page-message-index .message-app .conversationcontainer .d-flex.align-items-center .msg_input_br .input-group-prepend span, #page-message-index .message-app .conversationcontainer .mgs_search_show .msg_input_br .input-group-append button.btn.btn-outline-secondary, #page-message-index .message-app .footer-container .emoji_send .btn.btn-link.btn-icon.icon-size-3, .nav-pills .nav-link.active {
                        background-color: $brandcolor !important;
                    }";
$css .= "#page-message-index .message-app .conversationcontainer .view-overview-body .section.card.expanded .list-group a.list-group-item strong.m-0.text-truncate, #page-message-index .message-app .conver_list_txt .conver_date,#page-course-view-remui.format-remui.path-user nav .pagination li.page-item.active .page-link, #page-mod-ilt-view #page-content .box.generalbox nav .pagination li.page-item.active .page-link {
                        color: $brandcolor !important;
                    }";
$css .= "#page-message-index .message-app .conversationcontainer .view-overview-body .section.card.expanded .list-group {
                        border-left: 6px solid $brandcolor;
                    }";
$css .= "#page-message-index .message-app .conversationcontainer .view-overview-body .section.card.expanded .list-group span.badge.badge-pill[data-region='unread-count'],#page-message-index .message-app .conversationcontainer .view-overview-body .card-header button.btn.btn-link.overview-section-toggle span.badge.badge-pill[data-region='section-unread-count'] {
                        background-color:  $brandcolor !important;
                    }";
$css .= "#page-message-index .message-app .message.send,#nav-drawer ul li.dropdown.nav-item.tab_active_new a {
                        background-color: $light_color !important;
                    }";
$css .= "#page-blocks-xpremui-index .block_xpremui-page-nav ul.nav-tabs a.nav-link.active, #page-blocks-xpremui-index .block_xpremui-page-nav ul.nav-tabs a.nav-link.active:focus, #page-blocks-xpremui-index .block_xpremui-page-nav ul.nav-tabs a.nav-link.active:hover{
                        background-color: #fff !important;
                        border:0!important;
                        color: $brandcolor !important;
                        border-bottom: 4px solid $brandcolor!important;
                }";
$css .= "#page-blocks-xpremui-index .block_xpremui-table thead tr th{
                        background-color: $light_color !important;
                        color: $brandcolor !important;
                }";
$css .= "#page-my-index .moodle-has-zindex .modal-footer a, #page-site-index .moodle-has-zindex .modal-footer a {
                        background-color: $light_color !important;
                        border: solid 1px $brandcolor !important;
                        color: $brandcolor !important;
                }";
$css .= "#page-blocks-configurable_reports-viewreport nav ul.pagination li.page-item.active a.page-link, #page-blocks-xpremui-index .paginate_active,#page-course-view-topics.format-topics.path-user nav .pagination li.page-item.active .page-link {
                        color: $brandcolor!important;
                        border: 0 !important;
                }";
$css .= "#page-blocks-xpremui-index table.generaltable.generalbox thead tr th,#page-blocks-xpremui-index table.generaltable.generalbox thead tr th a,#page-grade-report-overview-index table#overview-grade thead tr th, #page-grade-report-quizanalytics-index table#overview-grade thead tr th, #page-grade-edit-scale-index table#overview-grade thead tr th, #page-grade-edit-letter-index table#overview-grade thead tr th, #page-grade-report-overview-index table.generaltable thead tr th, #page-grade-report-quizanalytics-index table.generaltable thead tr th, #page-grade-edit-scale-index table.generaltable thead tr th, #page-grade-edit-letter-index table.generaltable thead tr th, #page-grade-report-user-index table.generaltable thead tr th, body.format-remui .gradeparent #user-grades tbody tr.heading th,body.format-remui .gradeparent #user-grades tbody tr.heading th a,#page-course-view-topics.format-topics.path-user table#participants thead tr th,#page-course-view-topics.format-topics.path-user table#participants thead tr th a {
                        color: $brandcolor!important;
                        background-color: $light_color!important;
                }";
$css .= "#page-grade-report-grader-index #course_content .tab-content .gradeparent::-webkit-scrollbar-thumb {
                        background-color: $brandcolor!important;
                }";
$css .= "#page-local-people-index .close_icon_search {
                            background-color: $brandcolor!important;
                        }";
$css .= ".format-topics.path-grade .grade-navigation ul.nav-tabs a.nav-link.active, .format-topics.path-grade .grade-navigation ul.nav-tabs a.nav-link.active:focus, .format-topics.path-grade .grade-navigation ul.nav-tabs a.nav-link.active:hover{
                color: $brandcolor!important;
                border:0!important;
                border-bottom: 4px solid $brandcolor!important;
                }";
$css .= ".format-topics.path-grade .gradeparent #user-grades tbody tr.heading,.format-topics.path-grade .gradeparent #user-grades tbody tr.heading th a,.format-topics.path-grade .gradeparent #user-grades tbody tr.heading th,#page-grade-report-singleview-index table.generaltable thead tr td,#page-grade-report-singleview-index table.generaltable thead tr th {
                            color: $brandcolor!important;
                        background-color: $light_color!important;
                }";

$css .= "#page-local-download_reports-view table#reports_table thead tr th {
                            color: $brandcolor!important;
                        background-color: $light_color!important;
                        }";
$css .= "#page-local-download_reports-view div.searchreport i.men-search-phx {
                            background-color: $brandcolor!important;
                        }";
$css .= "#page-local-download_reports-view div.searchreport input#search {
                            border: 2px solid $brandcolor!important;
                        }";
$css .= "#page-mod-ilt-view #page-content .box.generalbox .Add_new_session, #page-mod-ilt-view #page-content .box.generalbox .Add_new_session a.addnewsession {
                            background-color: $brandcolor!important;
                            color: #fff !important;
                        }";
$css .= "#page-mod-ilt-view #page-content .box.generalbox table.generaltable thead tr th, #page-mod-ilt-view #page-content .box.generalbox table.generaltable tbody tr .dropdown-item:hover, #page-mod-ilt-view #page-content .box.generalbox table.generaltable tbody tr .dropdown-item:focus{
                            color: $brandcolor!important;
                            background-color: $light_color!important;
                        }";
$css .= "#page-blocks-courserecords-index .generaltable.course-myrecords-table.generaltable_remui .header.row .span-header {
                            color: $brandcolor!important;
                        background-color: $light_color!important;
                        }";
$css .= "#page-local-enroll_by_profile-index #enroll_by_profile_modal div.add-rule-loader-modal .loader-box .loader-animation {
                            border-top: 10px solid $brandcolor;
                        }";
$css .= "#page-local-enroll_by_profile-index div.rulelist::-webkit-scrollbar-thumb {
                            background-color: $brandcolor!important;
                        }";
$css .= "#page-local-reports-index .reportclear_icon, body#page-course-view-remui.format-remui .userprofile .profile_tree ul li.contentnode .course-records.no-overflow::-webkit-scrollbar-thumb {
                        background-color: $brandcolor!important;
                        }";
$css .= "body#page-course-view-remui.format-remui .userprofile .profile_tree ul li.contentnode .course-records.no-overflow .generaltable.course-myrecords-table.generaltable_remui .header.row .span-header {
                        color: $brandcolor!important;
                        background-color: $light_color!important;
                        }";
$css .= "#page-local-people-index .report-left-block-acordeon .aply-filtter .content-apply button#send {
                        background-color: $brandcolor!important;
                        color: #fff !important;
                        }";
$css .= "#page-local-people-index .report-left-block-acordeon .aply-filtter .icon-filter a.clear-button,body#page-my-index .generaltable.course-myrecords-table .header.row .span-header, body#page-site-index .generaltable.course-myrecords-table .header.row .span-header, #page-blocks-courserecords-index .generaltable.course-myrecords-table .header.row .span-header {
                            color: $brandcolor!important;
                            background-color: $light_color!important;
                        }";

$css .= "#page-local-people-index .report-left-block-acordeon .aply-filtter .icon-filter a.clear-button{padding: 4px 7px!important;border-radius: 4px;font-size: .75rem;font-weight: 600; height: 30px;}";
$css .= "div.search-filter-buttons div.report-left-block-acordeon div.filterpeople div.content-filtter div.filterslist div.panel-heading:hover h5.panel-title a.plms-reports-dropdown i.reports-dropdown {
    color: #fff !important;
}";
$css .= "#page-local-people-index .plms-reports-dropdown.active i.reports-dropdown {
    color: #fff !important;
}";

$css .= "#page-contentbank .content-bank-container .cb-search-container .searchbar.input-group, #page-contentbank .core_contentbank_viewcontent .cb-          search-container .searchbar.input-group, #page-local-learningpaths-index .plms-learningpaths .add-learning .search-course-learning                  input#id_search, #page-local-learningpaths-view .plms-learningpaths .add-learning .search-course-learning input#id_search {
                            border: 0;
                        }";
$css .= "#page-local-learningpaths-index .plms-learningpaths .add-learning .new-learning .create-buttons.cbtn_lp .new-button, #page-local-                    learningpaths-view .plms-learningpaths .add-learning .new-learning .create-buttons.cbtn_lp .new-button, #page-local-people-index .search-filter-buttons .content-actions a.btn-primary.create-user-top {
                            background-color: $brandcolor!important;
                            color:#fff !important;
                        }";
$css .= "#page-contentbank .content-bank-container .cb-search-container .searchbar.input-group .input-group-append .input-group-text, #page-contentbank .core_contentbank_viewcontent .cb-search-container .searchbar.input-group .input-group-append .input-group-text{
                            background-color: $brandcolor!important;
                        }";
$css .= "#page-local-learningpaths-view .tab-content #learningpath-courses-tab .new-course a.btn.btn-primary, #page-local-learningpaths-view .tab-content #learningpath-users-tab .new-course a.btn.btn-primary, #page-local-learningpaths-view .tab-content #learningpath-cohorts-tab .new-course a.btn.btn-primary, #page-local-learningpaths-view .tab-content #learningpath-courses-tab .new-cohort a.btn.btn-primary, #page-local-learningpaths-view .tab-content #learningpath-users-tab .new-cohort a.btn.btn-primary, #page-local-learningpaths-view .tab-content #learningpath-cohorts-tab .new-cohort a.btn.btn-primary,#page-local-iomad-dashboard-index .mt-dashboard-header .editcompanieslink{
                            background-color: $brandcolor!important;
                            color: #fff !important;
                            padding:8px 14px;
                            border-radius:4px;
                            font-size:14px; 
                        }";


$css .= "#page-local-iomad-dashboard-index .mt-dashboard-header div.managecompany{
                padding-top: 7px;
               }";
$css .= "#page-local-learningpaths-view .tab-content #learningpath-users-tab .new-course a#learningpath-remove-users ,#page-local-learningpaths-view .tab-content #learningpath-users-tab .new-course a#learningpath-remove-cohorts{
                            color: $brandcolor!important;
                            background-color: $light_color!important;
                        }";
$css .= "header.navbar.navbar-full div#navbar-nav div.options-header div.dropdown_lang a.dropdown-toggle,header.navbar.navbar-full div#navbar-nav div.options-header div.dropdown_lang a.dropdown-toggle:before,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole div.roletxt,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole.cont-mycourse button.dropdown-toggle i,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole.cont-mycourse button.dropdown-toggle span,header.navbar.navbar-full div#navbar-nav div.options-header div.settings a.btn_settings i,header.navbar.navbar-full div#navbar-nav div.options-header div.settings a.btn_settings span,header.navbar.navbar-full div#navbar-nav div.options-header .total-point a.btn_settings span.counter-txt{
                            color: #484b5a !important;
                        }";
$css .= "header.navbar.navbar-full div#navbar-nav div.options-header div.dropdown_lang a.dropdown-toggle:hover,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole div.roletxt:hover,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole.cont-mycourse button.dropdown-toggle:hover,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole.cont-mycourse button.dropdown-toggle:hover i.fa.fa-laptop.mr-1,header.navbar.navbar-full div#navbar-nav div.options-header div.settings a.btn_settings:hover i, header.navbar.navbar-full div#navbar-nav div.options-header div.settings a.btn_settings:hover span,header.navbar.navbar-full div#navbar-nav div.options-header div.check-switchrole.cont-mycourse button.dropdown-toggle span:hover,div.dropdown_lang a.dropdown-toggle:hover:before {
                            color: $brandcolor!important;
                        }";
$css .= "#page-admin-setting-modsettingcertificate form#adminsettings div.form-item#admin-uploadimage .form-setting a,#page-admin-setting-modsettingcertificate form#adminsettings div.form-item#admin-uploadimage .form-setting a:focus{
                            background-color: $brandcolor!important;
                        }";
$css .= "#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div#quiz-timer #quiz-time-left, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div#quiz-timer #quiz-time-left, #page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div#quiz-timer i.fa.fa-clock-o, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div#quiz-timer i.fa.fa-clock-o,#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box form#responseform .que .flag_cls, #page-mod-quiz-review #page-content div#region-main-box form#responseform .que .flag_cls,#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons select.section_quiz_change, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons select.section_quiz_change,#page-mod-quiz-review #page-content div#region-main-box form.questionflagsaveform .que .flag_cls span.questionflagtext{
                            color: $brandcolor!important;
                        }";
$css .= "#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage .thispageholder, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage .thispageholder{
                            border: 2px solid $brandcolor;
                        }";
$css .= ".path-mod-quiz #mod_quiz_navblock .qnbutton{
                            background-color: #fff !important;
                        }";
$css .= "#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage,#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box form#responseform .que .content .formulation .ablock .answer>div, #page-mod-quiz-review #page-content div#region-main-box form#responseform .que .content .formulation .ablock .answer>div,#page-mod-quiz-review #page-content div#region-main-box form.questionflagsaveform .que .content .formulation .ablock .answer>div{
                            background-color: $light_color!important;
                        }";
$css .= "#page-mod-quiz-attempt.path-mod-quiz #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div.singlebutton button, #page-mod-quiz-review #page-content div#region-main-box aside#mod_quiz_navblock .card-text.content .othernav div.singlebutton button{
                            border: 0!important;
                        }";
$css .= "#hide_on_mobile{
                            background: $brandcolor!important;
                        }";
$css .= "#page-local-learningpaths-index .plms-learningpaths .search-course-learning #search_lp_form .input-group .input-group-btn button,#hide_on_mobile{
                            background: $brandcolor!important;
                        }";
$css .= "#page-local-iomad-dashboard-index .search-mtn #searchbox .input-group-btn span.input-group-btn.search-lp,#page-local-iomad-dashboard-index .search-mtn #searchbox .input-group-btn span.input-group-btn.search-lp i {
                           color: #fff;
                           background: $brandcolor;
                       }";
$css .= "#page-local-iomad-dashboard-index .search-mtn #searchbox .input-group-btn #search-mt{
                           border:1px solid #ced4da;
                       }";
$css .= "#page-local-iomad-dashboard-index .mt-dashboard-header div.managecompany a {
                        border:solid 1px $brandcolor !important;
                        color:$brandcolor;
                        margin-top:0;

                       }";
$css .= "#page-local-iomad-dashboard-index .mt_company_admin a.nav-link.active{
color:$brandcolor;
border-bottom: solid 1px $brandcolor;

                       }";



$css .= "#page-local-iomad-dashboard-index .mt-dashboard-header div.managecompany {
    padding-top: 0;
}";

$css .= "#page-local-iomad-dashboard-index .search-mtn.company_search #searchbox .input-group-btn #search-mt {
                           width: 290px;
                       }";
$css .= "#page-admin-tag-manage form.tag-management-form table#tag-management-list thead tr th, #page-admin-tag-manage form.tag-management-form    table#tag-management-list thead tr th a {
                           color: $brandcolor!important;
                       }";
$css .= "#page-admin-tag-manage form.tag-management-form table#tag-management-list thead tr th, #page-admin-tag-manage form.tag-management-form    table#tag-management-list thead tr th a,#page-local-people-index div.search-filter-buttons div.header-search .dropdown-menu.show li a.bulk-action.loadiframe:hover,#page-contentbank .cb-toolbar-container .button-view div.menu_view_chooser .dropdown-item.viewitem.grid button:hover, #page-contentbank .cb-toolbar-container .button-view div.menu_view_chooser .dropdown-item.viewitem.list button:hover {
                           color: $brandcolor!important;
                           background-color: $light_color!important;
                        }";
$css .= "#page-local-learningpaths-index .plms-learningpaths .pagination_lp ul.count_select ul.pagination .page-item.active .page-link{
                           color: $brandcolor!important;
                           border: 0 !important;
                       }";
$css .= "#page-local-venuemanangement-index .btn-success, #page-local-venuemanangement-locations .btn-success {
                           background-color: $brandcolor!important;
                           border-color: $brandcolor!important;
                       }";
$css .= "div#element_custom_navigation ul li a:not(.active_nav):hover,#page-course-index-category .coursecatbtn_grp .dropdown-item:hover,body#page-contentbank a.icon-no-margin {
                           background-color: $light_color!important;
                       }";
$css .= "#page-local-people-index div.search-filter-buttons div.header-search .dropdown-menu.show li a.bulk-action.loadiframe:hover {
                           background-color: $light_color!important;
                       }";
$css .= "#page-blocks-configurable_reports-editreport #edit-reports-form .w-overviewreport form.mform .form-group.fitem.btn-cancel                    input#id_cancelbutton {
                           background-color: $light_color!important;
                           border: solid 1px $light_color!important;
                           color: $brandcolor!important;
                       }";
$css .= "#page-local-venuemanangement-listresource .btn-success, #page-local-venuemanangement-listbu .btn-success, #page-local-venuemanangement-locations .btn-success, #page-local-venuemanangement-index .btn-success{
                            background-color: $brandcolor!important;
                            border-color: $brandcolor!important;
                        }";
$css .= " body#page-remui_upcoming-view #scheduler .jqx-button-material.jqx-fill-state-pressed  {
                            background-color: $brandcolor!important;
                            border-color: $brandcolor!important;
                            color:#fff!important;
                        }";

$css .= "#page-local-venuemanangement-listresource .btn-info, #page-local-venuemanangement-listbu .btn-info, #page-local-venuemanangement-locations .btn-info, #page-local-venuemanangement-index .btn-info,#page-local-venuemanangement-listresource input.btn.btn-default, #page-local-venuemanangement-listbu input.btn.btn-default, #page-local-venuemanangement-locations input.btn.btn-default, #page-local-venuemanangement-index input.btn.btn-default,#page-contentbank .cb-toolbar-container .dropdown-menu.dropdown-scrollable.show a.dropdown-item:hover,#page-local-people-index div.moodle-has-zindex div.modal-footer button[data-action=cancel],#page-local-enroll_by_profile-index .bootbox.bootbox-confirm .modal-footer button.btn.btn-default,#page-admin-tool-lp-plan.path-admin .moodle-dialogue-base .moodle-dialogue .moodle-dialogue-bd div[data-region=user-competency-full-info] div[data-region=competency-summary] .mdl-left .comment-area .fd a,body#page-remui_upcoming-view #scheduler .jqx-button-material, body#page-remui_upcoming-view #scheduler jqx-button-material.jqx-fill-state-normal-material {
                                background-color: $light_color!important;
                                border-color: $light_color!important;
                                color: $brandcolor!important;
                        }";
$css .= "#page-local-people-index div.search-filter-buttons div.header-search .dropdown-menu.show li a.bulk-action.loadiframe i,#page-mod-quiz-attempt .slick-prev::before, #page-mod-quiz-attempt .slick-next::before,#page-mod-quiz-attempt .slick-prev::before, #page-mod-quiz-attempt .slick-next::before {
                        color: $brandcolor!important;
                            }";

$css .= "#page-course-index-category .select2-selection,#page-site-index .block_team .content-search, #page-my-index .block_team .content-search {
                                border: solid 2px $brandcolor!important;
                        }";

$css .= "#page-local-people-index div.search-filter-buttons div.header-search .dropdown-menu.show li a.bulk-action.loadiframe i,.generaltable thead th a,.form-autocomplete-suggestions li:hover,aside.block_lpd div.headinglpd,aside.block_lpd div.lpd-lp-content-header:before,#page-site-index .block_team .team-icon.right i, #page-my-index .block_team .team-icon.right i,.dropdown-item:hover .lang_text,.dropdown-item:hover div.dropdown_lang a.dropdown-toggle,.dropdown-item:hover div.dropdown_lang a.dropdown-toggle i,.dropdown-item:hover div.dropdown_lang a.dropdown-toggle .languagecls,div.settings-menu a#add-block span,body#page-user-profile div.user-panel div.tab-content div.contentnode>span:first-child,aside.block.block_remui_upcoming h5,aside.block.block_remui_upcoming table tbody tr.calendar-row:nth-child(1) td.calendar-day-head{
                                color: $brandcolor!important;
                         }";
$css .= "#page-theme-remui-pages-course_management-management .plms-category-listing .category-listing,.block_team .table_team .avatar_initials,#page-local-coursewizard-createcourse .nav-tabs .nav-link,#page-local-social_wall-index .update-container ul#notifications li.viewed-notification,#page-local-social_wall-index .notification-container ul#notification-list li.viewed-notification,aside.block_tags .tag_cloud .inline-list li,aside.block .dropdown-item.active,aside.block .dropdown-item:active {
                                background-color: $light_color!important;
                         }";
$css .= "#page-theme-remui-pages-course_management-management .btn-secondary-hover,#page-admin-tool-lp-plan .plan-competencies table.generaltable thead tr th,#page-admin-tool-lp-plan .modal.moodle-has-zindex #self-rating table.self_rating_table thead tr th,#page-admin-tool-lp-plan .moodle-dialogue-base .moodle-dialogue-bd table.wdmCourseProgressTable thead tr th,.generaltable thead th {
                                background-color: $light_color!important;
                                color: $brandcolor!important;
                         }";
$css .= "#page-admin-tool-lp-plan.path-admin .moodle-dialogue-base .moodle-dialogue .moodle-dialogue-bd div[data-region=user-competency-full-info] div[data-region=competency-summary] .mdl-left .comment-ctrl ul.comment-list::-webkit-scrollbar-thumb,#page-admin-tool-lp-plan.path-admin .moodle-dialogue-base .moodle-dialogue .moodle-dialogue-bd div[data-region=user-competency-full-info] div[data-region=competency-summary] dl.evidance::-webkit-scrollbar-thumb,#page-site-index .block_team .content-search i, #page-my-index .block_team .content-search i,#page-local-people-index #send {
                                background-color: $brandcolor!important;
                         }";
$css .= "#page-local-coursewizard-createcourse .nav-tabs .nav-link.active, .sidebar a:hover{
                            background-color: $brandcolor!important;
}";

$css .= ".sidebar li:hover span.icon svg{fill:#fff !important;}";

$css .= ".sidebar li:hover a, .sidebar li:hover a .icon, .sidebar li:hover i.fa.fa-chevron-down{
color:#fff !important;
}";
$css .= ".sidebar .menu li ul li a{color:#85888a !important;}";

$css .= ".sidebar .menu li:hover{
 background-color: $brandcolor!important;
}";

$css .= "#page-mod-quiz-attempt.format-remui.path-mod-quiz #page-content div#region-main-box #course_content #linkmaincontent aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage .thispageholder, #page-mod-quiz-review.format-remui #page-content div#region-main-box #course_content #linkmaincontent aside#mod_quiz_navblock .card-text.content .qn_buttons .qnbutton.thispage .thispageholder {
                                border: 3px solid $brandcolor;
                         }";
$css .= "#page-site-index aside.block.block_calendar_upcoming .card-text.calendarwrapper .event .not_date_style:before, #page-my-index aside.block.block_calendar_upcoming .card-text.calendarwrapper .event .not_date_style:before {
                                background-color: $light_color!important;
                         }";
$css .= "div.moodle-has-zindex div.modal-footer button[data-action='cancel']:hover,aside.block_tags .tag_cloud .inline-list li a{
                                color: $brandcolor!important;
                         }";
$css .= ".mynotes_base .tabs-menu li.current {
                            background-color: $brandcolor!important;
                            border: 1px solid $brandcolor;
                        }";
$css .= "aside.block.block_get_report table.table thead th,#page-admin-tag-manage table.generaltable thead tr th{
                                background-color: $light_color!important;
                                color: $brandcolor!important;
                        }";
$css .= "table.generaltable td.cell a.btn{
                    color:#fff !important; }";

$css .= ".path-local.dark-mode, #page-admin-user-user_bulk.dark-mode.pagelayout-admin, .dark-mode.pagelayout-admin{
    background: #171e32!important;
}";

$css .= ".dark-mode .mt_company_admin a.nav-link{
color: $brandcolor!important;
}";

// Append the css after this company to overrride the company branding colors.
$css .= $postcss;

// print 
if ($CFG->cachejs)
    echo trim(preg_replace('/\s\s+/', ' ', $css));
else
    echo $css;
// Return light color of tenant brand color
function adjustBrightness($hexCode, $adjustPercent)
{
    $hexCode = ltrim($hexCode, '#');
    if (strlen($hexCode) == 3) {
        $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    }
    $hexCode = array_map('hexdec', str_split($hexCode, 2));
    foreach ($hexCode as &$color) {
        $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
        $adjustAmount = ceil($adjustableLimit * $adjustPercent);

        $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }
    return '#' . implode($hexCode);
}