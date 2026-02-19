define(['jquery', 'core/modal_factory', 'core/templates', 'core/str'], function($, ModalFactory, Templates, str) {
    return {
        init: function () {
            setTimeout(function(){
                $('div.stimg.cardone-header .fa.fa-pencil-square-o').closest('a').css('display','none');
                $('.editor_atto_toolbar .atto_collapse_button').css('display','none');
                $('#id_error_message').html('');
                $('#id_error_message').css('display','none');
                
                $('.editor_atto_toolbar .atto_group.style1_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.list_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.links_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.style2_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.align_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.indent_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.insert_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.undo_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.accessibility_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.other_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.fontfamily_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.fontsize_group').css('display','none');
                $('.editor_atto_toolbar .atto_group.fullscreen_group').css('display','none');

                $('div.editor_atto_toolbar button.atto_wordimport_button').css('display','none');
                $('div.editor_atto_toolbar button.atto_media_button').css('display','none');
                $('div.editor_atto_toolbar button.atto_managefiles_button').css('display','none');
                $('div.editor_atto_toolbar button.atto_recordrtc_button_audio').css('display','none');
                $('div.editor_atto_toolbar button.atto_recordrtc_button_video').css('display','none');

                $('div.editor_atto_toolbar button.atto_image_button').css('display','none');
                var uploadimage = str.get_string('uploadimage','local_social_wall');
                $.when(uploadimage).done(function(gotastring) {
                    $('<button type=\"button\" class=\"atto_custom_upload_image\" tabindex=\"0\" id=\"atto_custom_upload_image\" title=\"'+gotastring+'\"><i class=\"icon fa fa-image fa-fw \" title=\"'+gotastring+'\" aria-label=\"'+gotastring+'\"></i></button>').appendTo('div.files_group');
                });
                var uploadvideo = str.get_string('uploadvideo','local_social_wall');
                $.when(uploadvideo).done(function(gotastring) {
                    $('<button type=\"button\" class=\"atto_custom_upload_video\" tabindex=\"0\" id=\"atto_custom_upload_video\" title=\"'+gotastring+'\"><i class=\"icon fa fa-video-camera fa-fw \" title=\"'+gotastring+'\" aria-label=\"'+gotastring+'\"></i></button>').appendTo('div.files_group');
                });
                var uploadfiles = str.get_string('uploadfiles','local_social_wall');
                $.when(uploadfiles).done(function(gotastring) {
                    $('<button type=\"button\" class=\"atto_custom_upload_files\" tabindex=\"0\" id=\"atto_custom_upload_files\" title=\"'+gotastring+'\"><i class=\"icon fa fa-file fa-fw \" title=\"'+gotastring+'\" aria-label=\"'+gotastring+'\"></i></button>').appendTo('div.files_group');
                });
                
                // Upload image
                var imagemodalhandle;
                $(document).on('click', 'button#atto_custom_upload_image', function () {
                    $('#socialfiles').val('');
                    $('#socialerror').html('');
                    $('#id_error_message').html('');
                    $('#id_error_message').css('display','none');
                    if($('#socialimageupload').length){
                        imagemodalhandle.show();
                    } else{
                        var trigger = $('#create-modal');
                        ModalFactory.create({
                            title: uploadimage,
                            body: Templates.render('local_social_wall/imagepopup', {}),
                            footer: '',
                        }, trigger)
                        .done(function(modal) {
                            imagemodalhandle = modal;
                            imagemodalhandle.show();
                        });
                    }
                    
                    $(document).on('change','#socialfiles', function(e){
                        $('#socialimageerror').html('');
                        $('#socialimageerror').removeClass('alert alert-danger');
                        var file = this.files[0];
                        var fileType = file.type;
                        var match = ['image/jpeg', 'image/png', 'image/jpg'];
                        if (file.size > 5000000) {
                            var maxfilesizeallowed = str.get_string('maxfilesizeallowed','local_social_wall',5);
                            $.when(maxfilesizeallowed).done(function(gotastring) {
                                $("#socialimageerror").addClass("alert alert-danger");
                                $('#socialimageerror').html(gotastring);
                                $('#socialfiles').val('');
                            });
                            return false;
                        }
                        
                        if(!((fileType == match[0]) || (fileType == match[1]) || (fileType == match[2]) )){
                            var extensionallowed = str.get_string('extensionallowed','local_social_wall');
                            $.when(extensionallowed).done(function(gotastring) {
                                $("#socialimageerror").addClass("alert alert-danger");
                                $('#socialimageerror').html(gotastring);
                                $('#socialfiles').val('');
                            });
                            return false;
                        }
                        
                    });
                    $(document).one('submit','#socialimageupload', function(e){
                        e.preventDefault();

                        var form_data = new FormData(this);           
                        for(var i = 0; i < $('#socialfiles')[0].files.length; i++){
                            form_data.append('file[]', $('#socialfiles')[0].files[i]);
                        }
                        form_data.append('sesskey', M.cfg.sesskey);
                        $('.uploadingloader').css('display','block');
                        $.ajax({
                            url: M.cfg.wwwroot + '/local/social_wall/ajax/upload_images.php', 
                            dataType: 'json',
                            async: true,
                            cache: false,
                            contentType: false,
                            processData: false,
                            data: form_data,                         
                            type: 'POST',
                            success: function(response){
                                if(response.status){
                                    for(var u = 0; u < response.url.length; u++){
                                        $('<img class=\"wallimage\" />').attr({
                                            src: response.url[u]
                                        }).appendTo('#messageeditable');
                                    }
                                    imagemodalhandle.hide();
                                }else{
                                    var err = document.createElement('span');    // Create with DOM
                                    err.innerHTML = response.message;
                                    err.setAttribute('class', 'alert alert-danger');
                                    $('#socialerror').html(err);
                                }
                                $('.uploadingloader').css('display','none');
                            }
                        });

                    });
                });

                // Upload video
                var videomodalhandle;
                $(document).on('click', 'button#atto_custom_upload_video', function () {
                    $('#socialvideofiles').val('');
                    $('#socialerror').html('');
                    $('#id_error_message').html('');
                    $('#id_error_message').css('display','none');
                    if ($("#messageeditable video").length > 0){
                        var filesallowed = str.get_string('filesallowed','local_social_wall');
                        $.when(filesallowed).done(function(gotastring) {
                            $('#id_error_message').html(gotastring);
                            $('#id_error_message').css('display','block');
                        });
                        return false;
                    }
                    if($('#socialvideoupload').length){
                        videomodalhandle.show();
                    } else{
                        var trigger = $('#create-video-modal');
                        ModalFactory.create({
                            title: uploadvideo,
                            body: Templates.render('local_social_wall/videopopup', {}),
                            footer: '',
                        }, trigger)
                        .done(function(modal) {
                            videomodalhandle = modal;
                            videomodalhandle.show();
                        });
                    }
                    $(document).on('change','#socialvideofiles', function(e){
                        $('#socialvideoerror').html('');
                        $('#socialvideoerror').removeClass('alert alert-danger');
                        var file = this.files[0];
                        var fileType = file.type;
                        var match = ['video/mp4'];
                        if (file.size > 50000000) {
                            var maxfilesizeallowed = str.get_string('maxfilesizeallowed','local_social_wall',50);
                            $.when(maxfilesizeallowed).done(function(gotastring) {
                                $("#socialvideoerror").addClass("alert alert-danger");
                                $('#socialvideoerror').html(gotastring);
                                $('#socialfiles').val('');
                                $('#socialvideofiles').val('');
                            });
                            return false;
                        }
                        
                        if(!( (fileType == match[0]) )){
                            var videoextensionallowed = str.get_string('videoextensionallowed','local_social_wall');
                            $.when(videoextensionallowed).done(function(gotastring) {
                                $("#socialvideoerror").addClass("alert alert-danger");
                                $('#socialvideoerror').html(gotastring);
                                $('#socialfiles').val('');
                                $('#socialvideofiles').val('');
                            });
                            return false;
                        }
                        
                    });
                    
                    $(document).one('submit','#socialvideoupload', function(e){
                        e.preventDefault();
                        $('#id_error_message').html('');
                        $('#id_error_message').css('display','none');
                        var form_data = new FormData(this);           
                        for(var i = 0; i < $('#socialvideofiles')[0].files.length; i++){
                            form_data.append('file[]', $('#socialvideofiles')[0].files[i]);
                        }
                        form_data.append('sesskey', M.cfg.sesskey);
                        $('.uploadingloader').css('display','block');
                        $.ajax({
                            url: M.cfg.wwwroot + '/local/social_wall/ajax/upload_video.php', 
                            dataType: 'json',
                            async: true,
                            cache: false,
                            contentType: false,
                            processData: false,
                            data: form_data,                         
                            type: 'POST',
                            success: function(response){
                                if(response.status){
                                    if ($("#messageeditable video").length > 0){ 
                                        var filesallowed = str.get_string('filesallowed','local_social_wall');
                                        $.when(filesallowed).done(function(gotastring) {
                                            $('#id_error_message').html(gotastring);
                                            $('#id_error_message').css('display','block');
                                        });
                                        return false;
                                    }
                                    for(var u = 0; u < response.url.length; u++){
                                        $('<video controls muted controlsList="nodownload" class=\"wallvideo\">Your browser does not support the video tag.</video>').attr({
                                            src: response.url[u]
                                        }).appendTo('#messageeditable');
                                    }
                                    videomodalhandle.hide();
                                }else{
                                    var err = document.createElement('span');    // Create with DOM
                                    err.innerHTML = response.message;
                                    err.setAttribute('class', 'alert alert-danger');
                                    $('#socialerror').html(err);
                                }
                                $('.uploadingloader').css('display','none');
                            }
                        });

                    });
                });

                // Upload files
                var filesmodalhandle;
                $(document).on('click', 'button#atto_custom_upload_files', function () {
                    $('#socialfilesfiles').val('');
                    $('#socialerror').html('');
                    $('#id_error_message').html('');
                    $('#id_error_message').css('display','none');
                    if ($("#messageeditable files").length > 10){
                        var filesallowed = str.get_string('filesallowed','local_social_wall');
                        $.when(filesallowed).done(function(gotastring) {
                            $('#id_error_message').html(gotastring);
                            $('#id_error_message').css('display','block');
                        });
                        return false;
                    }
                    if($('#socialfilesupload').length){
                        filesmodalhandle.show();
                    } else{
                        var trigger = $('#create-files-modal');
                        ModalFactory.create({
                            title: uploadfiles,
                            body: Templates.render('local_social_wall/filespopup', {}),
                            footer: '',
                        }, trigger)
                        .done(function(modal) {
                            filesmodalhandle = modal;
                            filesmodalhandle.show();
                        });
                    }
                    
                    $(document).on('change','#socialfilesfiles', function(e){
                        $('#socialfileerror').html('');
                        $('#socialfileerror').removeClass('alert alert-danger');
                        var file = this.files[0];
                        if (file.size > 5000000) {
                            var maxfilesizeallowed = str.get_string('maxfilesizeallowed','local_social_wall',5);
                            $.when(maxfilesizeallowed).done(function(gotastring) {
                                $("#socialfileerror").addClass("alert alert-danger");
                                $('#socialfileerror').html(gotastring);
                                $('#socialfiles').val('');
                                $('#socialfilesfiles').val('');
                            });
                            return false;
                        }
                        
                        
                    });
                    
                    $(document).one('submit','#socialfilesupload', function(e){
                        e.preventDefault();
                        $('#id_error_message').html('');
                        $('#id_error_message').css('display','none');
                        var form_data = new FormData(this);           
                        for(var i = 0; i < $('#socialfilesfiles')[0].files.length; i++){
                            form_data.append('file[]', $('#socialfilesfiles')[0].files[i]);
                        }
                        form_data.append('sesskey', M.cfg.sesskey);
                        $('.uploadingloader').css('display','block');
                        $.ajax({
                            url: M.cfg.wwwroot + '/local/social_wall/ajax/upload_files.php', 
                            dataType: 'json',
                            async: true,
                            cache: false,
                            contentType: false,
                            processData: false,
                            data: form_data,                         
                            type: 'POST',
                            success: function(response){
                                if(response.status){
                                    if ($("#messageeditable files").length > 10){ 
                                        var filesallowed = str.get_string('filesallowed','local_social_wall');
                                        $.when(filesallowed).done(function(gotastring) {
                                            $('#id_error_message').html(gotastring);
                                            $('#id_error_message').css('display','block');
                                        });
                                        return false;
                                    }
                                    for(var u = 0; u < response.url.length; u++){
                                        $('<a href=\"'+response.url[u]+'\" title=\"'+response.name[u]+'\" class=\"wallfiles\" target=\"_blank\"><i class=\"'+response.fileClass[u]+'\" ></i> '+response.name[u]+'</a><br>').appendTo('#messageeditable');
                                    }
                                    filesmodalhandle.hide();
                                }else{
                                    var err = document.createElement('span');    // Create with DOM
                                    err.innerHTML = response.message;
                                    err.setAttribute('class', 'alert alert-danger');
                                    $('#socialerror').html(err);
                                }
                                $('.uploadingloader').css('display','none');
                            }
                        });
                    });
                });

                // Video autoplay on scroll - start
                var IsInViewport = function(el) {
                    if (typeof jQuery === "function" && el instanceof jQuery) el = el[0];
                    var rect = el.getBoundingClientRect();
                    return (
                        rect.top >= 0 &&
                        rect.left >= 0 &&
                        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                    );
                };
                $(window).scroll(function() {
                    $('video').each(function(){
                        if (IsInViewport($(this))) {
                            $(this)[0].play();
                        } else {
                            $(this)[0].pause();
                        }
                    });
                });
                // Video autoplay on scroll - end

            }, 3000);
        }
    }
});