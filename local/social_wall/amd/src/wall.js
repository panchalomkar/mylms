/**
 * @module local_social_wall/wall
 */

// Return the count of likes
function countLikes(msgid) {

    var object = {}
    object['msg_id'] = msgid;
    object['mode'] = "count_likes";
    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: object,
        dataType: 'json',
        async: false,
    }).done(function (res) {
        $(".countlike" + msgid).text(res.data + " Likes");
    });
}

// function add_browse_icon() {
//     $("form[class='atto_form']").find("div").eq(0).append("<input class='openimagebrowser' onchange='upload_file_wall(this.value)' type='file' id='file' name='fileToUpload'>");
// }

// function upload_file_wall( obj ) {
//     var la_path = ( obj.indexOf('\\') != -1 ) ? obj.split('\\') : obj.split('/');
//     $("#message_atto_image_urlentry").val( la_path[ la_path.length - 1 ] );
//     // create an iframe to do the request without reload page
//     if ( $("#upload_image_wall").length == 0 )  { $('body').append("<iframe id='upload_image_wall' name='upload_image_wall' style='display:none;width:300px;height:300px;'></iframe>"); }
//     if ( $("#form_image_wall").length == 0 )    { 
//         $('body').append("<form id='form_image_wall' action='" + M.cfg.wwwroot +"/local/social_wall/upload.php' name='form_image_wall' method='post' enctype='multipart/form-data' target='upload_image_wall' ></form>");
//     } else {
//         $("#form_image_wall").empty();
//     }
//     $( "#form_image_wall" ).append( $("#file").clone( true ) );
//     $( "#form_image_wall" ).promise().done(function() { $(this).submit(); });
// }

function isTouchDevice() {
    return !!('ontouchstart' in window || navigator.msMaxTouchPoints);
}
 
function show_wall_message(json) {

    try {
        var obj = JSON.parse(json);
        if (typeof obj.filename != "undefined") {
            $("#message_atto_image_urlentry").val(obj.filename);
            $("#atto_image_preview").attr('src', obj.filename)
            $("#atto_image_preview").show();
            $("#message_atto_image_widthentry").val(obj.width);
            $("#message_atto_image_heightentry").val(obj.height);
            $("#message_atto_image_urlentry").focus();
            $("#message_atto_image_urlentry").click();
        } else {
            $("#message_atto_image_urlentry").val('');
            $.notify('Sorry, there was an error uploading your file', 'error');
        }
    } catch (err) {
        $.notify('Sorry, there was an error uploading your file', 'error');
    }
}

define(['jquery', 'theme_remui/select2','core/str', 'theme_remui/notify','theme_remui/popover'], function ($, Select2,str, notify,popover) {
    return {
        init: function () {
            

            
            
            var translation = str.get_strings([
                { key: 'delete_update', component: 'local_social_wall' },
                { key: 'delete_comment', component: 'local_social_wall' },
                { key: 'access_denied', component: 'local_social_wall' },
                { key: 'req_comment', component: 'local_social_wall' }

            ]);

            
            
            // switch to create a browser button
            // var image_event = false;

            $(document).ready(function () {
                
                $('#menusocial').select2(); 
                
                $.notify.defaults({ globalPosition: 'bottom right', timeout: 40000 });

                // Add new post
                $(".mform").submit(function (e) {
                    e.preventDefault();
                    var htmlData = $("#messageeditable").html();
                    if (htmlData =="") {
                        $.notify(M.util.get_string('req_comment', 'local_social_wall'), 'error');
                        return false;
                    }
                    var messagedatalenght = ($('#messageeditable .wallimage').length) + ($('#messageeditable .wallvideo').length) + ($('#messageeditable .wallfiles').length) + $('#messageeditable').text().length;
                    if(messagedatalenght == 0) {
                        $.notify(M.util.get_string('req_comment', 'local_social_wall'), 'error');
                        return false;
                    }
                    var formdata = $(this).serializeArray();
                    var data = {};
                    $(formdata ).each(function(index, obj){
                        data[obj.name] = obj.value;
                    });
                    data['message[text]'] = htmlData;
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/add_message.php?sesskey=' + M.cfg.sesskey;
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: data,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        $("#messageeditable").html('');

                        if (res.error) {
                            $.notify(res.error, 'error');
                        } else {
                            $(".render_msgs").html(res.data);
                            $("#id_submitbutton").val(res.btn);
                            $("#update_msg_id").val('');
                            if ($("#textarea_container").hasClass("boxopen")) {
                                $("#textarea_container").slideToggle("fast").removeClass("boxopen");
                                $(".txt-learning").removeClass("hidden");
                            }
                            $.notify(res.message, res.status);
                        }
                    });
                });
                
                // delete update
                $(document).on('click', ".stdelete", function () {

                    var object = {}
                    var ID = $(this).attr("id");
                    object['msg_id'] = ID;
                    object['mode'] = "delete_message";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    if (confirm(M.util.get_string('delete_update', 'local_social_wall'))) {

                        $.ajax({
                            method: 'POST',
                            url: ajaxUrl,
                            data: object,
                            dataType: 'json',
                            async: false,
                        }).done(function (res) {
                            if (res.status == "error") {
                                $.notify(res.message, res.status);
                            } else {
                                $("#stbody" + ID).slideUp();
                                $.notify(res.message, res.status);
                            }
                        });
                    }
                    return false;
                });

                // delete comment
                $(document).on('click', ".stcommentdelete", function () {

                    var ID = $(this).attr("id");
                    var object = {}
                    object['comid'] = ID;
                    object['mode'] = "delete_comment";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    if (confirm(M.util.get_string('delete_comment', 'local_social_wall'))) {

                        $.ajax({
                            method: 'POST',
                            url: ajaxUrl,
                            data: object,
                            dataType: 'json',
                            async: false,
                        }).done(function (res) {
                            if (res.status == "error") {
                                $.notify(res.message, res.status);
                            } else {
                                $("#stcommentbody" + ID).slideUp("slow");
                                $.notify(res.message, res.status);
                            }
                        });
                    }
                    return false;
                });

                // rating
                $(document).on('click', ".social_like", function () {

                    var ID = $(this).attr("id").replace("ratings","");
                    var rate_val = $(this).data("value");
                    var object = {}
                    object['id'] = ID;
                    object['rate_val'] = rate_val;
                    object['mode'] = "rating";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;

                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        if (res.error) {
                            $.notify(res.error, 'error');
                        } else {
                            if (res.data == "like") {
                                $('#ratings' + ID).addClass("active");
                            } else if (res.data == "no-like") {
                                $('#ratings' + ID).removeClass("active");
                            }
                            countLikes(ID);
                        }
                    });

                    return false;
                });

                //commment Submit
                $(document).on('click', ".comment_button", function (event) {
                    event.preventDefault();

                    var ID = $(this).data("id");
                    var cmntid = $("#upd_comid").val();
                    var comment = $("#ctextarea" + ID).val();
                    comment = comment.replace(/\+/g, "zx81plus");
                    cleanText = comment.replace(/<\/?[^>]+(>|$)/g, "");
                    flag = cleanText.replace(/\s/g, '');
                    if ((comment == '') || (flag == '')) {
                        alert(M.util.get_string('req_comment', 'local_social_wall'));
                    } else {

                        comment = comment.replace(/\+/g, "zx81plus");
                        comment = comment.replace(/\&/g, "zorilla");
                        comment = comment.replace(/[&]nbsp[;]/gi, " ");
                        comment = comment.replace(/[&]Acirc[;]/gi, " ");
                        comment = comment.replace(/[&]lt[;]/gi, "<");
                        comment = comment.replace(/[&]gt[;]/gi, ">");
                        comment = comment.replace(/[&]quot[;]/gi, "\"");
                        comment = comment.replace(/[&]amp[;]/gi, "pamzpam");
                        comment = comment.replace("&embed", "@@@@embed");
                        comment = comment.replace("&autoplay", "@@@@autoplay");

                        if (/WordLimit=1/.test(location.href)) {
                            wordNumCount = comment.match(/\S+/g).length;
                            if (wordNumCount > 1)
                                comment = comment + '\n' + " (" + wordNumCount + " words)";
                            else
                                comment = comment + '\n' + " (" + wordNumCount + " word)";
                        }
                        var object = {}
                        object['msg_id'] = ID;
                        object['cmntid'] = cmntid;
                        object['comment'] = comment;
                        object['mode'] = "add_comment";

                        var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;

                        $.ajax({
                            method: 'POST',
                            url: ajaxUrl,
                            data: object,
                            dataType: 'json',
                            async: false,
                        }).done(function (res) {

                            if (res.error) {
                                $.notify(res.error, 'error');
                            } else {
                                $(".render_msgs").html(res.data);
                                // $("#commentload"+ID).html(res.data);
                                $.notify(res.message, res.status);
                                $("#ctextarea" + ID).val('');
                                $("#upd_comid").val('');
                                $("#ctextarea" + ID).focus();
                                //$("#commentload"+ID).slideToggle('slow');
                            }
                        });
                    }
                    return false;
                });

                // commentopen 
                $(document).on('click', ".commentopen", function () {
                    var ID = $(this).attr("id");
                    $("#commentload" + ID).slideToggle('slow');
                    if ($("#" + ID + ".commentopen").find("i").hasClass("fa-angle-down")) {
                        $("#" + ID + ".commentopen").find("i").removeClass("fa-angle-down").addClass("fa-angle-up");
                        $("#stbody" + ID + " .action-box").slideToggle('slow');
                    } else {
                        $("#" + ID + ".commentopen").find("i").removeClass("fa-angle-up").addClass("fa-angle-down");
                        $("#stbody" + ID + " .action-box").slideToggle('slow');
                    }
                });

                $(document).on('click', ".stedit", function () {
                    var ID = $(this).attr("id");
                    var txt = $("#stbody" + ID + " .msg_content").html();
                    $("#messageeditable").html(txt);
                    $("#update_msg_id").val(ID);
                    $("#id_submitbutton").val('Update');
                    $(window).scrollTop($("#textarea_container").offset().top);
                    if ($("#textarea_container").hasClass("hidden")) {
                        $("#textarea_container").slideToggle("slow").addClass("boxopen");
                        $(".txt-learning").addClass("hidden");
                    }
                });

                $(document).on('click', ".stcommentedit", function () {
                    var ID = $(this).attr("id");
                    var msgid = $(this).data("id");
                    var cmnt = $(this).next().next().html();
                    //$("#commentbox"+msgid).css('display','block');
                    var cleanText = cmnt.replace(/<\/?[^>]+(>|$)/g, "");
                    $("textarea#ctextarea" + msgid).val(cleanText);
                    $("#upd_comid").val(ID);
                    // $("#id_submitbutton").val('Update');
                    // $(window).scrollTop($("#textarea_container").offset().top);
                });

                // load comments
                $(document).on('click', ".loadComments", function () {

                    var ID = $(this).data("id");
                    var object = {}
                    object['count'] = $(this).data("count");
                    object['msg_id'] = ID;
                    object['mode'] = "load_more_comment";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;

                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        if (res.error) {
                            $.notify(res.error, 'error');
                        } else {
                            $("#commentload" + ID).html(res.data);
                            $(".loadComments").hide();
                            $(".hideComments").show();
                            $("#commentload" + ID).css({ 'min-height': '270px', 'max-height': '270px', 'overflow-y': 'scroll' });
                        }
                    });
                });

                // Hide comments
                $(document).on('click', ".hideComments", function () {

                    var ID = $(this).data("id");
                    var object = {}
                    object['count'] = $(this).data("count");
                    object['msg_id'] = ID;
                    object['mode'] = "load_more_comment";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;

                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        if (res.error) {
                            $.notify(res.error, 'error');
                        } else {
                            $("#commentload" + ID).html(res.data);
                            $(".loadComments").show();
                            $(".hideComments").hide();
                            $("#commentload" + ID).removeAttr('style');
                        }
                    });
                })

                $(document).on('change', "#menusocial", function () {
                    var companyid = $(this).val();
                    $('#current_company').val(companyid);
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    var object = {}
                    object['companyid'] = companyid;
                    object['mode'] = "company_message";
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        var response = JSON.parse(res.data);
                        if (res.error) {
                            $.notify(res.error, 'error');
                        } else {
                            console.log(response.img);
                            $(".socail-img-container").css('background-image','url('+response.img+')');
                            $(".render_msgs").html(response.html);
                        }
                    });
                })

                /**
                 * New social wall design
                 * @author Dnyaneshwar K
                 * @ticket #56
                 * @dated 13-09-2019
                 * 
                 */
                $("p.write").on("click", function () {
                    
                  if ($("#textarea_container").hasClass("hidden")) {
                        $("#textarea_container").slideToggle("slow").addClass("boxopen");
                        $(".txt-learning").addClass("hidden");
                        $("#upload_container").hide();
                        $("#url_container").hide();
                    }
                });
                
                $(".custom-form-buttons").on("click", function () {
                    if ($("#textarea_container").hasClass("boxopen")) {
                        $("#textarea_container").slideToggle("fast").removeClass("boxopen");
                        $(".txt-learning").removeClass("hidden");
                        $("#messageeditable").html('');
                        
                    }
                });          

                $(".upload-img i").click(function () {
                    $(".upload-img input[type='file']").trigger('click');
                });

                $('.upload-img').on('click', function () {
                    var companyid = $('#current_company').val();
                    var companyidparam = '';
                    if(companyid != 0){
                        companyidparam = '&company='+companyid;
                    }
                    var redirecturl = M.cfg.wwwroot + '/admin/settings.php?section=local_social_wall'+companyidparam;
                    window.location = redirecturl;

                });
                
                /**
                 * Button Hide and Show Code
                 * @author Abhishek Vaidya
                 * @ticket 838
                 * Social Wall Feature
                 */
                
                $(".click_show_post").on("click", function () {
                    $(this).css("background-color", "#1ba2dd");
                    $(".click_show_media").css("background-color", "#fff");
                    $(".click_show_url").css("background-color", "#fff");
                     $(".show_post").show();
                     $(".show_media").hide();
                     $(".show_url").hide();
                });
                
                $(".click_show_media").on("click", function () {
                    $(this).css("background-color", "#1ba2dd");
                    $(".click_show_post").css("background-color", "#fff");
                    $(".click_show_url").css("background-color", "#fff");
                     $(".show_media").show();
                     $(".show_post").hide();
                     $(".show_url").hide();
                });
                
                $(".click_show_url").on("click", function () {
                    $(this).css("background-color", "#1ba2dd");
                    $(".click_show_post").css("background-color", "#fff");
                    $(".click_show_media").css("background-color", "#fff");
                     $(".show_url").show();
                     $(".show_post").hide();
                     $(".show_media").hide();
                });
                
                $('#btn_timeline').on('click', function(e){
                    $(".timeline_div").hide();
                    $(".back_div").show();
                    $("#social-container-1").hide();
                    $("#grid-row-1").hide();
                    $("#social-container-2").show();
                    $("#notify-box-1").hide();
                    $("#notify-box-2").show();
                    e.preventDefault();
                    var companyid = $('#menusocial').val();
                    if(companyid == undefined){
                        companyid = 0;
                    }
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    var object = {}
                    object['companyid'] = companyid;
                    object['mode'] = "timeline_messages";
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: true,
                        success: function(res){
                            var response = JSON.parse(res.data);
                            if (res.error) {
                                $.notify(res.error, 'error');
                            } else {
                                $(".render_msgs").html(response.html);
                                $('div.stimg.cardone-header .fa.fa-pencil-square-o').closest('a').css('display','none');
                            }
                        }
                    });
                });
                
                $("#back_see_all_btn").on("click", function (e) {
                    $(".back_div").hide();
                    $(".timeline_div").show();
                    e.preventDefault();
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    var object = {}
                    object['mode'] = "back_from_timeline";
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: true,
                        success: function(res){
                            var response = JSON.parse(res.data);
                            if (res.error) {
                                $.notify(res.error, 'error');
                            } else {
                                $(".render_msgs").html(response.html);
                                $('div.stimg.cardone-header .fa.fa-pencil-square-o').closest('a').css('display','none');
                            }
                        }
                    });
                });

                $("#back_see_all_btn_1").on("click", function (e) {
                    $(".back_div").hide();
                    $(".timeline_div").show();
                    $("#social-container-1").show();
                    $("#grid-row-1").show();
                    $("#social-container-2").hide();
                    $("#notify-box-1").show();
                    $("#notify-box-2").hide();
                    e.preventDefault();
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    var object = {}
                    object['mode'] = "back_from_timeline";
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: true,
                        success: function(res){
                            var response = JSON.parse(res.data);
                            if (res.error) {
                                $.notify(res.error, 'error');
                            } else {
                                $(".render_msgs").html(response.html);
                                $('div.stimg.cardone-header .fa.fa-pencil-square-o').closest('a').css('display','none');
                            }
                        }
                    });
                });
                
                $(document).on('click', ".getlikedata", function () {
                    var ID = $(this).attr("id");
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/whoslike_popup.php';
                    var object = {}
                    object['msgid'] = ID;
                    
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        async: false,
                    }).done(function (res) {
                        $("#whoslikedata").html(res);
                    });
                });
                
                $(document).on('click', "#notifications li", function () {
                    var notificationid = $(this).attr("id");
                    if(notificationid == 0){
                        see_all_notifications();
                    } else {
                        if($(this).hasClass('viewed-notification')){
                            var id = notificationid.split('-');
                            var object = {}
                            object['type'] = id[0];
                            object['messageid'] = id[1];
                            object['userid'] = id[2]
                            object['datecreated'] = id[3];
                            object['mode'] = "readcomment";
                            var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                            $.ajax({
                                method: 'POST',
                                url: ajaxUrl,
                                data: object,
                                dataType: 'json',
                                async: false,
                            }).done(function (res) {
                                var response = JSON.parse(res.data);
                                if (response.status) {
                                    $("#"+notificationid).removeClass('viewed-notification');
                                }
                            });
                        }
                        if($('.notification-container').length){
                            see_all_messages(notificationid);
                        } else {
                            var hash = $(this).closest('a')[0].hash;
                            $('html, body').animate({
                                scrollTop: $(hash).offset().top
                            }, 1000);
                        }
                    }
                });

                $(document).on('click', "#back_to_notification", function (e) {
                    see_all_messages(0);
                });

                $(document).on('click', "#notification-list li", function () {
                    var notificationid = $(this).attr("id");
                    var id = notificationid.split('-');
                    var object = {}
                    object['type'] = id[0];
                    object['messageid'] = id[1];
                    object['userid'] = id[2]
                    object['datecreated'] = id[3];
                    object['mode'] = "see_notified_post";
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;

                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        var response = JSON.parse(res.data);
                        if (response.status) {
                            $(".render_msgs").html(response.html);
                            $("#"+id[0]+'-'+id[1]+'-'+id[2]+'-'+id[3]).removeClass('viewed-notification');
                            $("#"+notificationid+"-notificationlist").removeClass('viewed-notification');
                        }
                    });
                });

                $(document).on('click', "#back-to-notification-btn", function () {
                    see_all_notifications();
                });
                // add browse icon
                // $("#wall_container").on('DOMSubtreeModified', function() {
                //     if ( image_event === false ) {
                //         var li_image_button = $("button[class='atto_image_button']").length;
                //         if ( li_image_button > 0 ) {
                //             image_event = true; console.log( 'add browser button' );
                //             $("#wall_container #messageeditable").empty();
                //             $("#wall_container button[class='atto_image_button']").click(function( e ) { e.preventDefault(); setTimeout("add_browse_icon()",500); });
                //         }
                //     }
                // });        


                /**
                 * New social wall posts lazy loading
                 * @author Vinay B
                 * @dated 16-04-2020
                 * 
                 */

                $(document).ready(function(){
                        windowOnScroll();
                });
                function windowOnScroll() {
                    $(window).on("scroll", function(e){
                        if(!$('.notification-container').length){
                            if ($(window).scrollTop() == $(document).height() - $(window).height()){
                                var timeline = $('.timeline_div').is(':visible');
                                // if default all posts are shown
                                if(timeline && $(".stbody").length < $('.all_posts_count').text()) {
                                    var lastId = $(".stbody:last").attr("id");
                                    lastId = lastId.split("y")[1];
                                    getMoreData(lastId, timeline);
                                }
                                // if clicked on timeline
                                if(!timeline && $(".stbody").length < $('.count_post').text()) {
                                    var lastId = $(".stbody:last").attr("id");
                                    lastId = lastId.split("y")[1];
                                    getMoreData(lastId, timeline);
                                }
                            }
                        }
                    });
                }
                // Load more data when user scrolls
                function getMoreData(lastId, timeline) {
                    $(window).off("scroll");
                    var loader = '<div id="socialwalloader" class="item row" style="display: block; text-align: center;"><img class="no result_img" src="' + M.cfg.wwwroot + '/pix/i/loading.gif"></div>';
                    $(".render_msgs").append(loader);
                    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
                    var object = {}
                    if (timeline) {
                        // if mode is default
                        object['mode'] = "back_from_timeline";
                    } else{
                        // if mode is selected as timeline
                        object['mode'] = "timeline_messages";
                        var companyid = $('#menusocial').val();
                        if(companyid == undefined){
                            companyid = 0;
                        }
                        object['companyid'] = companyid;

                    }
                    object['lastId'] = lastId;
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: true,
                        success: function(res){
                            var response = JSON.parse(res.data);
                            if (res.error) {
                                $.notify(res.error, 'error');
                            } else {
                                $(".render_msgs").append(response.html);
                                windowOnScroll();
                                var loader = $("#socialwalloader");
                                loader.remove();
                            }
                        }
                    });
                }

                // Lazy loading ends

                $(document).on('mouseover','.user-popover',function(){
                   
                    if($(this).attr('id')!='')
                        {
                            var ID = $(this).attr("id");
                            var splitId = ID.split('-');
                            var userid = splitId[1];
                            var msgid = splitId[2];
                            var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/popover_ajax.php?sesskey=' + M.cfg.sesskey;
                            
                            var content = '';
                            $.ajax({
                                method: 'POST',
                                url: ajaxUrl,
                                data: {userid:userid},
                                async: false,
                                success: function(res){
                                    if(res['city']!=''){
                                        var usercity = res['city']+',';
                                    }else{
                                        var usercity = '';
                                    }

                                    if(res['city']!='' || res['country']!=''){
                                        var markericon = 'class="fa fa-map-marker"';
                                    }else{
                                        var markericon = '';
                                    }
                                    var content = '<div class="media text-md-left">'
                                                    + res[0]
                                                    +    '<div class="media-body">'
                                                    +      '<h6 class="font-weight-bold mt-0" style="text-align:center;">'
                                                    +        '<a href="">'+res['firstname']+' '+res['lastname']+'</a>'
                                                    +      '</h6>'
                                                    +      '<div style="text-align:center;"><i '+markericon+'></i> <span>'+usercity+''+res['country']+'</span></div>'
                                                   // +      '<div style="text-align:center;"><span>'+res['email']+'</span></div>'
                                                    +    '</div>'
                                                    +'</div>'
                                                    +'<div class="media-body user-popover-body" style="margin-top:3%;">'
                                                   // +   '<div style="text-align:center;"><span>'+res['email']+'</span></div>'
                                                    +   '<div style="text-align:center;">'+res['description']+'</div>'
                                                    +'</div>';
                                    //$('#popover-'+userid).attr('data-content',content);

                                    $('#popover-'+userid+'-'+msgid).popover({
                                        title: "",
                                        trigger: "hover",
                                        container:"body",
                                        content: content,
                                        html: true,
                                        placement: "auto",
                                        viewport: {
                                          selector: '[id="popover-'+userid+'-'+msgid+'"]',
                                          padding: 20
                                        }
                                    });
                                    $('#popover-'+userid+'-'+msgid).popover('show');

                                }
                            });

                        }
                });

                $(document).on('click','#saveuserbio',function(){
                    var validate = validateForm();
                    
                    if(validate==true){
                        var form = $('#mform4').serialize();
                        var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/popover_ajax.php?sesskey=' + M.cfg.sesskey;
                        
                        $.ajax({
                            method: 'POST',
                            url: ajaxUrl,
                            data: form,
                            dataType: 'json',
                            async: true,
                            success: function(res){
                                if(res=='error'){
                                    alert('User bio not updated');
                                }else{
                                    alert('User bio updated');
                                    location.reload();
                                }

                            }
                        });
                    }
                });

                $(document).on('click','.div-color',function(){
                    var bgcolor = $(this).attr('bgcolor');

                    $('#user_background_colour').val(bgcolor);
                });

            });


            
            //valdation msg to social wall user details
            var maxLength = 250;
            $(".area_error").hide();
            $(".user_bio_social").on("keydown keyup change", function(){
                var value = $(this).val();
                if (value.length == maxLength){
                    $(".area_error").show();
                    $(".area_error").css("color","red");
                }else{
                    $(".area_error").hide();
            }
                
            });
            //Show popup of user information on hover of user picture  in a social wall              
            $(document).on('mouseover','#btn_timeline',function(){
                var userid = $(this).attr("value");
                var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/popover_ajax.php?sesskey=' + M.cfg.sesskey;
                var content = '';
                $.ajax({
                    method: 'POST',
                    url: ajaxUrl,
                    data: {userid:userid},
                    async: false,
                    success: function(res){
                        if(res['city']!=''){
                            var usercity = res['city']+',';
                        }else{
                            var usercity = '';
                        }

                        if(res['city']!='' || res['country']!=''){
                            var markericon = 'class="fa fa-map-marker"';
                        }else{
                            var markericon = '';
                        }
                        var content = '<div class="media text-md-left">'
                                        + res[0]
                                        +    '<div class="media-body">'
                                        +      '<h6 class="font-weight-bold mt-0" style="text-align:center;">'
                                        +        '<a href="">'+res['firstname']+' '+res['lastname']+'</a>'
                                        +      '</h6>'
                                        +      '<div style="text-align:center;"><i '+markericon+'></i> <span>'+usercity+''+res['country']+'</span></div>'
                                        +    '</div>'
                                        +'</div>'
                                        +'<div class="media-body user-popover-body" style="margin-top:3%;">'
                                        +   '<div style="text-align:center;">'+res['description']+'</div>'
                                        +'</div>';

                        $('#btn_timeline').popover({
                            title: "",
                            trigger: "hover",
                            container:"body",
                            content: content,
                            html: true,
                            placement: "auto",
                            viewport: {
                              selector: '[id="#btn_timeline"]',
                              padding: 20
                            }
                        });
                        $('#btn_timeline').popover('show');

                    }
                });
     });
    
        }
    }
});

function see_all_notifications(){
    $(".back_div").hide();
    $(".timeline_div").show();
    var object = {}
    object['mode'] = "see_all_notifications";
    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: object,
        dataType: 'json',
        async: false,
    }).done(function (res) {
        var response = JSON.parse(res.data);
        if (response.status) {
            $(".render_msgs").html(response.html);
        }
    });
}

function see_all_messages(nid){
    $(".back_div").hide();
    $(".timeline_div").show();
    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/message_ajax.php?sesskey=' + M.cfg.sesskey;
    var object = {}
    object['mode'] = "back_from_timeline";
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: object,
        dataType: 'json',
        async: true,
        success: function(res){
            var response = JSON.parse(res.data);
            if (res.error) {
                $.notify(res.error, 'error');
            } else {
                $(".render_msgs").html(response.html);
                $('div.stimg.cardone-header .fa.fa-pencil-square-o').closest('a').css('display','none');
                if(nid != 0){
                    var hash = $('#'+nid).closest('a')[0].hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top
                    }, 1000);
                }
            }
        }
    });
}


if(($('#page-local-social_wall-index').length > 0)
    && ($('#page-local-social_wall-index .render_msgs #show_load_msgs').length > 0)
    && (($(".cardone-header").length > 0) || ($(".notification-container").length > 0))
){

    var e = $('#updates-box');
    var f =$('.render_msgs');
    var lastScrollTop = -100;
    var firstOffset = e.offset().top;
    var firstWidth = e.width();
    var ww = $(window).width();
    var isFixed = false;
    if(ww > 767){
        $(window).scroll(function(event){
            if (isFixed) {
                return;
            }
            var a = e.offset().top;
            var b = e.height();
            var c = $(window).height();
            var d = $(window).scrollTop();
            var g = f.height();
            var h = f.offset().top;
            
            if(d >= firstOffset - 15 && (b + d <= g + h)){
                if (e.css("position") != "sticky" && c + d >= a + b) {
                    e.css({position: "sticky", bottom: "auto", top: 10, width: firstWidth});
                }
            } else if(b + d >= g + h){
                e.css({position: "static", bottom: 60, top: "auto", width: firstWidth});
            } else {
                e.css({position: "static", width: firstWidth});
            }
            lastScrollTop = d;
        });
    }
}

function details_in_popup(uid){
    alert(uid);
    var ajaxUrl = M.cfg.wwwroot + '/local/social_wall/ajax/popover_ajax.php?sesskey=' + M.cfg.sesskey;
    var userid = uid;
    var content;
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: {userid:userid},
        dataType: 'json',
        async: false,
        success: function(res){
            //var response = JSON.parse(res.data);
            
            var content = '<div class="media text-md-left">'
                            +  '<img class="cardone-img-100 d-flex z-depth-1 mr-3" src="https://mdbootstrap.com/img/Photos/Avatars/img%20(8).jpg"' 
                            +  'alt="Generic placeholder image" style="height: 60px;width: 60px;border-radius: 50%;">'
                            +    '<div class="media-body">'
                            +      '<h6 class="font-weight-bold mt-0" style="text-align:center;">'
                            +        '<a href="">'+res['firstname']+' '+res['lastname']+'</a>'
                            +      '</h6>'
                            +      '<div style="text-align:center;"><i class="fa-map-marker"></i><span>'+res['city']+','+res['country']+'</span></div>'
                            +    '</div>'
                            +'</div>'
                            +'<div class="media-body" style="margin-top:3%;">'
                            +   '<div style="text-align:center;">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.<span>Paris,FR</span></div>'
                            +'</div>';
                            console.log(content);
           
        }
    });
    console.log('jhjjkhjhj'+content);
    return content;
}

function validateForm() {
  var country = $('select[name="user_country"]').val();
  var city = $('input[name="user_city"]').val();
  
  if (country == "") {
    $('#validation-box').show();
    $('#alert_content').text('Please select country');
    $('#validation-box').show(0).delay(5000).hide(0);
    return false;
  }
  
  if (city == "" && country != "") {
    $('#validation-box').show();
    $('#alert_content').text('Please select city');
    $('#validation-box').show(0).delay(5000).hide(0);
    return false;
  }
  return true;
}