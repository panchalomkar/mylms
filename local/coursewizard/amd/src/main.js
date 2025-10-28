define(['jquery', 'theme_boost/loader', 'theme_boost/bootstrap/tooltip','theme_remui/notify','core/str','core/config'], function ($,loader,tooltip,notify,str,mdlcfg) {
    function nextTab(elem) {
        $(elem).parent().next().find('a[data-toggle="tab"]').click();
    }
    function prevTab(elem) {
        $(elem).parent().prev().find('a[data-toggle="tab"]').click();
    }
    function validateStep1(){
        var $active = $('.wizard .nav-tabs li a.active');
        var error = false;
        $('#step1').find('input').each(function(){
            if($(this).attr('required') && $(this).val() == ''){
                error = true;
                var label = $(this).closest('div.contentlabel').find('label').html();
                editaPresent = str.get_string('requiredfield', 'local_coursewizard', label);
                $.when(editaPresent).done(function(localizedEditString) { 
                    $.notify(
                        localizedEditString,{
                            className: 'error',
                            autoHideDelay: 3000
                        }
                    );
                });
            }
        });
        if (!error) {
            $('div.connecting-line').addClass('activeline');
        }else{
            $active.click();
        }
    }
    return {
        init: function () {
            $(document).ready(function () {

                $('body#page-local-coursewizard-createcourse #id_category option').each(function(){
                     $(this).attr('title',$(this).text());
                });
                //Initialize tooltips
                $('.nav-tabs > li a[title]').tooltip();
                $("li a[aria-controls=step2]").click(function (e) {
                    if(!$(this).hasClass('active')){
                        validateStep1();
                    }
                });
                $('.next-step').click(function(e){
                    var $active = $('.wizard .nav-tabs li a.active');
                    var closeId = $(this).closest('[id^=step]').attr('id');
                    var error = false;
                    if(closeId != ''){
                        $('#'+closeId).find('abbr.required-element').each(function(){   
                            var attribute = $(this).data('inputid');
                            if($('#'+attribute).val()== ''){
                                error = true;
                                $('#'+attribute).addClass('form-control-danger').closest('div.form-group.row').addClass('has-danger');   
                            }   
                        });
                    }
                    if (!error) {
                        nextTab($active);
                    }
                });
                $(".prev-step").click(function (e) {
                    e.preventDefault();
                    var $active = $('.wizard .nav-tabs li a.active');
                    prevTab($active);
                });
                $("a.img-courseswizard").click(function (e) {
                    if($(this).hasClass('selected_course_img')){
                        $(this).removeClass('selected_course_img');
                        $('[name=image_selected]').val('');
                    }else{
                        $('a.img-courseswizard').each(function(){
                            $(this).removeClass('selected_course_img');
                        });
                        $(this).addClass('selected_course_img');
                        var data = JSON.stringify($(this).find('img.wizard-img').data());     
                        $('[name=image_selected]').val(data);
                    }
                });
                $(".upload-btn").click(function (e) {
                    $('.fp-btn-add').click();
                });
                $('.wizard ul li a').click(function () {
                    var step = $(this).attr('href');
                    $('.wizard ul li a').removeClass('active');
                    $(this).addClass('active');
                    $('.wizard form .tab-pane').removeClass('active');
                    $('.wizard '+step).addClass('active'); 
                    if ($(this).hasClass('wizard2') && $(this).hasClass('active')){
                        var coursename = $('#step1 input#id_fullname').val();
                        var coursecategory = $('#step1 select#id_category').val();
                        if (coursename != '' && coursecategory != '' ) {
                            $(".wid-checkwizard").show();
                        }else{
                            $(".wid-checkwizard").hide();
                        }
                    }
                });
            $('a.nav-link.wizard2').click(function(){
                $('li.btn.btn-primary.btn-round.next-step').click();
            });       
            if($('div').hasClass('has-danger')){
                $('li.nav-item  a').click();
            }

            $('form.mform').submit(function(){
                $(this).find(':input[type=submit]').prop('disabled', true);
            });

            });
        }
    };
});
