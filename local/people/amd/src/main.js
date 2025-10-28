// Standard license local omitted.
/*
 * @package    local_people
 * @copyright  2018 Daniel Carmona <daniel.carmona@remuisolutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module local_people/main
 */

/**
 * Declare var lang_locale to get current language and get moment locale accord to lang.
 * 
 * @author Manisha M
 * @author remui
 * @since 13-08-2019
 * @ticket #362
 */
 var lang_locale='',
 lang = document.getElementsByTagName('html')[0].getAttribute('lang');
 if( lang != '' && lang != 'en' && lang != 'en-us'){
   lang_locale=  "local_people/" + lang;
}
define(['jquery',
    'core/ajax',
    'core/notification',
    'theme_remui/select2',
    lang_locale,
    'theme_remui/bootbox',
    'theme_remui/notify',
    'core/modal_factory',
    'local_people/bootstrap-datetimepicker',
    'core/modal_events',
    'core/str'
    ], function ($, Ajax, Notification, Select2 ,lang_locale, bootbox, notify, ModalFactory, datetimepicker, ModalEvents, str) {



        function courseValid() {

            if($("#selectcourses").val() == "") {
                $("span.select2-selection.select2-selection--multiple").css("border", "1px solid red");
                return false;
            }
        }


        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + "; " + expires;
        }
        
        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        function getUrlParameter(sParam) {

            var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : sParameterName[1];
                }
            }
        }

        function invite_user() {
            bootbox.dialog({
                message: M.util.get_string('invite_message', 'local_people') + ' ' + $(".custom-search-form #txt").attr('value') + ' ?',
                title: M.util.get_string('invite_title', 'local_people'),
                buttons: {
                    success: {
                        label: M.util.get_string('send_invite', 'local_people'),
                        className: "btn btn-round btn-primary",
                        callback: function () {
                            $.post("invite_people.php", {email: $(".custom-search-form #txt").attr('value')}).done(function (data) {
                                var lo_data = jQuery.parseJSON(data);

                                if (lo_data.status == true) {
                                    $.notify(
                                        M.util.get_string('invitation_sent', 'local_people'),
                                        {
                                            className: 'success',
                                            autoHideDelay: 3000
                                        }
                                        );
                                } else {
                                    $.notify(
                                        M.util.get_string('invite_error', 'local_people'),
                                        {
                                            className: 'error',
                                            autoHideDelay: 3000
                                        }
                                        );
                                }
                            });
                        }
                    },
                }
            });
        }

        function find_object_byclass(object, classname) {
            var li_counter = 0;
            var lb_status = false;
            do {
                if (object.hasClass(classname) == true) {
                    lb_status = true;
                } else {
                    li_counter++;
                    if (object.parent()) {
                        object = object.parent();
                    }
                }
            } while (li_counter < 7 && lb_status == false);
            return (lb_status == true) ? object : false;
        }

        function clear_people_filter(context) {

            context.find("input[type=text], textarea").val("");
            context.find('select').val('').trigger('select2:updated');
            context.find("form[id^='form-'] .date input").val("");
            context.find("small.help-block").hide();
            context.find("form[id^='form-'] input[name^='never']").removeAttr('checked');
            context.find("form[id^='form-'] input[name^='never']").closest('label').removeClass('active');
            context.find('.has-error').removeClass("has-error");
            context.find('.has-success').removeClass("has-success");
            context.find('.form-control-feedback').remove();
            context.find(".form-check").removeClass('active');
            context.find(".form-check input:radio").prop('checked', false);
            
        }
        return {
            init: function (params) {

                $(document).on('click','.enroll', function(e) {

                    e.preventDefault();

                    return courseValid();

                });

                $(document).on('submit','#EnrolForm', function(e) {

                    return courseValid();

                });
                
                $(document).ready(function () {

                    $('.filterslist .panel-heading a[data-toggle="collapse"]').on('click', function () {   
                        $('.filterslist .panel-heading a[data-toggle="collapse"]').removeClass('active');
                        $(this).addClass('active');
                    });
                    
                //Remove custom added .form-radio-inactive class on radio button reset
                $("#form-suspended .form-radio").click(function(){
                    $("#form-suspended .form-radio label").removeClass("form-radio-inactive");
                });
                
                $("#form-confirmed .form-radio").click(function(){
                    $("#form-confirmed .form-radio label").removeClass("form-radio-inactive");
                });
                
                var message_frm = $('#message_bulk_container').html();
                //$('#message_bulk_container').remove();
                $("#btn_invite_user").click(function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    invite_user();
                });
                
                $('form[id^=form-]').submit(function(e){
                    event.preventDefault();
                    event.stopPropagation();
                    filter(e);
                });
                
                $('#id_userperpage').change(function () {
                    $(this).parents('form:first').submit();
                });

                var selected_bulk = [];


                if (getCookie('selected_bulks')) {
                    //if(false){

                        var selected_bulk = $.parseJSON(getCookie('selected_bulks'));
                    }

                    $(".chosen-people").select2();
                    
                    $('#form-firstaccessd .form-checkbox').click(function (event) {
                        var $lo_object = $(this).find('input[name=neveraccess]');
                        if ($lo_object.is(":checked")) {
                            $lo_object.parent().addClass('active');
                        } else {
                            $lo_object.parent().removeClass('active');
                        }
                    });

                    $('#form-lastaccessed .form-checkbox').click(function (event) {
                        var $lo_object = $(this).find('input[name=neveraccess]');
                        if ($lo_object.is(":checked")) {
                            $lo_object.parent().addClass('active');
                        } else {
                            $lo_object.parent().removeClass('active');
                        }
                    });

                    $('#lastaccessid').click(function() {
                        if ($(this).is(':checked')) {
                            var d = new Date();
                            var strDate =    (d.getMonth()+1)  +  "/"  +  d.getDate()  +  "/"  +  d.getFullYear();
                            $('#lastaccessedgt').val(strDate);
                            $('#lastaccessedlt').val(strDate);
                        } else {
                            $('#lastaccessedgt').val('');
                            $('#lastaccessedlt').val(''); 
                        }
                    });

                    $('#form-lastmodified .form-checkbox').click(function (event) {
                        var $lo_object = $(this).find('input[name=nevermodified]');
                        if ($lo_object.is(":checked")) {
                            $lo_object.parent().addClass('active');
                        } else {
                            $lo_object.parent().removeClass('active');
                        }
                    });

                    function insertParam(paramName, paramValue) {

                        var url = window.location.href;
                        var hash = location.hash;
                        url = url.replace(hash, '');

                        if (url.indexOf(paramName + "=") >= 0)
                        {
                            var prefix = url.substring(0, url.indexOf(paramName));
                            var suffix = url.substring(url.indexOf(paramName));
                            suffix = suffix.substring(suffix.indexOf("=") + 1);
                            suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
                            url = prefix + paramName + "=" + paramValue + suffix;
                        } else
                        {
                            if (url.indexOf("?") < 0)
                                url += "?" + paramName + "=" + paramValue;
                            else
                                url += "&" + paramName + "=" + paramValue;
                        }

                        return url + hash;
                    }

                    $('#menucompany').change(function () {

                        window.history.pushState("object", "title", insertParam('company', this.value));

                        if ($(document).find('.reset').length != 0) {

                            var form = $(document).find('a.reset').closest('.panel').find('form');

                            var val = form.find(".btn").attr("value");

                            var nam = form.find(".btn").attr("name");

                            $('<input>').attr({name: nam, value: val, type: 'hidden'}).appendTo(form);

                            form.get(0).submit();

                        } else {

                            window.location.href = insertParam('company', this.value);
                        }



                    });

                    $("#accordion form").click(function (e) {

                        window.history.pushState("object", "title", insertParam('page', "0"));
                    });

                    var callback = function (e) {

                        e.preventDefault();

                        window.history.pushState("object", "title", insertParam('search', $('#txt').val()));

                        window.location.href = insertParam('search', $('#txt').val());
                    };

                    $("#txt").keypress(function (e) {

                        if (e.which == 13)
                            callback(e);
                    });

                    $("#txt").on('input', function (e) {
                        if ($.trim($(this).val()) != '') {
                            $('#searchbutton i').addClass('fa-close');
                            $('#searchbutton').css('cursor', 'pointer');
                            $('#searchbutton').removeClass('hidden');
                            $('#searchbutton').addClass('d-block');

                        } else {
                            $('#searchbutton i').removeClass('fa-close');
                            $('#searchbutton').removeClass('d-block');
                            $('#searchbutton').addClass('hidden');
                            $('#searchbutton').css('cursor', 'default');
                        }

                    });

                    $("#search-icon").click(function (e) {
                        callback(e);
                    });

                    $("#searchbutton").click(function (e) {

                        if ($('i', this).hasClass('fa-close')) {

                            $('#txt').val('');
                            $('#searchbutton i').removeClass('fa-close');
                            $('#searchbutton').removeClass('d-block');
                            $('#searchbutton').addClass('hidden');
                            $('#searchbutton').css('cursor', 'default');

                            if (getUrlParameter('search')) {
                                callback(e);
                            }
                        }
                    });

                    var filter = function (e) {

                        e.preventDefault();

                        if (!validateDateFields()) {

                            return false;
                        }

                        var values = {};
                        var $request = {};

                        var $forms = $('form');
                        var name = null;
                        var id = null;

                        var la_objectos = new Array();
                        $forms.each(function () {

                            values = {};

                            var $inputs = $('input, select', this);
                            $inputs.each(function () {

                                id = $(this).closest("form").attr("id");
                                if (id && id.indexOf("form-") > -1) {

                                    name = $(this)[0].name;
                                    if ($(this).is(':checkbox') || $(this).is(':radio')) {

                                        if ($(this).prop('checked')) {
                                            values[name] = ($(this).prop('checked')) ? true : '';
                                        }
                                    } else {

                                        values[name] = $(this).val();

                                    }

                                }

                            });


                            if (id && id.indexOf("form-") > -1 || $('.filter-true', this).length) {
                                $request[id] = values;
                            }

                        });
                    //return  true;
                    data = JSON.stringify($request);
                    var $form = $("<form method='post'>").append($('<input type="hidden" name="filters" id="data">').val(data));

                    $form.appendTo("body").submit();

                }

                $("#send, #send-footer").click(function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return filter(e);
                });

                $('.report-left-block-acordeon.col-md-3').keypress(function (e) {

                    if (e.which == '13') {
                        filter(e);
                    }
                });

                function validateDateFields( ) {

                    var isValid = true;


                    $.each(['firstaccessd', 'lastaccessed', 'lastmodified'], function (index, value) {

                        if ($('#form-' + value + ' .filter-true').length) {

                            var $reset = $('#form-' + value).closest(".filterslist");
                        }

                    });



                    if ($('#form-courserole .filter-true').length) {
                    }

                    return isValid;
                }

                var onInput = function (e) {

                    e.preventDefault();

                    var $form = $(this).closest("form");

                    var input = $("<input>").attr("type", "hidden").attr("class", "filter-true").val('true');

                    input.attr("name", "filter-true");

                    $($form).append(input);

                    if (!isEmptyForm(this)) {

                        var $reset = $(this).closest(".filterslist");
                        var editaPresent = str.get_string('clear', 'local_people');
                        var reset = '';
                        $.when(editaPresent).done(function(localizedEditString) {

                            $('.reset', $reset).remove();
                            reset = $("<a>").attr("class", "reset").text(localizedEditString);

                            $('.panel-body div.clr_button', $reset).append(reset);
                            $('.reset', $reset).click(function (e) {
                                var lo_object = find_object_byclass($(this), 'filterslist');
                                if (lo_object != false) {
                                    clear_people_filter(lo_object);
                                }
                                //resetForm(this);
                            });
                            
                            //Add custom added .form-radio-inactive class
                            $(".suspend-form .reset").click(function (e) {
                                $(".suspend-form .form-radio label").addClass("form-radio-inactive");
                                $(".suspend-form .filter-true").remove();
                                resetForm(this, e);
                            });
                            
                            $(".confirm-form .reset").click(function (e) {
                                $(".confirm-form .form-radio label").addClass("form-radio-inactive");
                                $(".confirm-form .filter-true").remove();
                                resetForm(this, e);
                            });
                            
                            $(".select-city .reset").click(function(){
                                $(".select-city .custom-select").select2('val', 'All');
                            });
                            
                            $(".select-country .reset").click(function(){
                                $(".select-country .custom-select").select2('val', 'All');
                            });
                            
                            $(".select-course .reset").click(function(){
                                $(".select-course .custom-select").select2('val', 'All');
                            });
                            
                        });
                    } else {

                        resetForm(this, e);
                    }
                }

                $("form[id^='form-'] input[name^='never']").change(onInput);

                $("form[id^='form-'] input:radio").change(onInput);

                $("form[id^='form-'] input[name$='t'], form[id^='form-'] select").change(onInput);

                $("form[id^='form-'] input, form[id^='form-'] select").on('input', onInput);

                function isEmptyForm(context) {

                    var id = $(context).closest('form').attr('id');

                    if (id == 'form-courserole') {
                        return false;
                    }

                    var panel = $(context).closest(".panel");

                    var regex = /^never.*/;

                    if ($('.form-checkbox', panel).length && $(context).attr('name').match(regex)) {

                        if ($(context).attr('checked') == undefined) {

                            return true;
                        }
                    }

                    if ($.trim($(context).val()) == '') {

                        return true;
                    }

                    return false;
                }

                function resetForm(context, event) {
                    var clickedId = event.currentTarget.id;

                    var reset = $(context).closest(".filterslist");
                    
                    $("input:radio[name='suspended']").each(function(i) {
                        this.checked = false;
                    });
                    $("#confirmed").each(function(i) {
                        this.checked = false;
                    });
                    if (clickedId == 'reset-form') {
                        reset = $(context).closest("#SearchParameters");
                    }

                    $('.reset', reset).remove();

                    $('.filter-true', reset).remove();

                    $("input[type=text], textarea", reset).val("");

                    $('select', reset).val('').trigger('select2:updated');

                    $("form[id^='form-'] .date input", reset).val("");

                    $("small.help-block", reset).hide();

                    $("form[id^='form-'] input[name^='never']", reset).removeAttr('checked');
                    $("form[id^='form-'] input[name^='never']", reset).closest('label').removeClass('active');

                    $("form[id^='form-'] input[name^='confirmed']", reset).removeAttr('checked');
                    $("form[id^='form-'] input[name^='unconfirmed']", reset).removeAttr('checked');
                    $("form[id^='form-'] input[name^='suspended']", reset).removeAttr('checked');
                    $("form[id^='form-'] input[name^='unsuspended']", reset).removeAttr('checked');

                    $(reset).find('.has-error').removeClass("has-error");
                    $(reset).find('.has-success').removeClass("has-success");
                    $(reset).find('.form-control-feedback').remove();
                }

                $("#reset-form").click(function (e) {

                    resetForm(this, e);

                    $('#send').click();
                });

                $('a.reset', this).click(function (e) {

                    resetForm(this, e);

                    $('#send').click();
                });

                $('a.reset').click(function (e) {

                    e.preventDefault();

                });

                $('div.reset').click(function (e) {
                    $('#form-bulk-actions input:checkbox').prop('checked', false); 
                    e.preventDefault();
                    $('#send').click();
                    var id = $(this).closest('div.btn-primary').data('name');                    
                    $("#form-" + id).closest(".filterslist").find('.panel-body .reset').click();  
                });


                $("form[id^='form-'] input[name^='never']").change(function (e) {

                    var id = "#" + $(this).closest('form').attr('id');

                    $("form[id^='form-'] .date input").val("");

                    if (!$(id + " input[name^='never']").is(':checked')) {

                        $(id).find('.has-error').removeClass("has-error");
                        $(id).find('.has-success').removeClass("has-success");
                        $(id).find('.form-control-feedback').remove();
                    }

                });


                $("form[id^='form-'] .date input").change(function () {

                    var id = "#" + $(this).closest('form').attr('id');

                    $(id + " input[name^='never']").removeAttr('checked');
                    ;
                    $(id + " input[name^='never']").closest('label').removeClass('active');

                });


                $('.btn-bulk-action').click(function (e) {

                    if ($(this).attr('id') == 'enrol_user') {

                        return false;
                    }

                    e.preventDefault();

                    var users = [];
                    var $name = $(this).data('action');

                    $('input[name^=id]:checked:enabled').each(function () {
                        users.push($(this).val());
                    });

                    if (users.length > 0) {
                        setCookie('selected_bulks', [], 1);
                        if(!$(this).data('showmodal')){
                            var $data = JSON.stringify({name: $name, data: users});
                            var $form = $("<form method='post'>").append($('<input type="hidden" name="bulk_action" id="data">').val($data));

                            $form.appendTo("body").submit();
                        }else{
                            var trigger = $('#modal-bulk-action');
                            //var ajaxUrl = M.cfg.wwwroot + '/local/people/ajax/request.php';
                            var body = '';
                            var title = $name;
                            var q = true;
                            var message = '';
                            var strChoose = '';
                            var fieldToValidate = '';

                            var promises = Ajax.call([{
                                methodname : 'local_people_get_people',
                                args: { form: $name, message: 'na', q: false, title: 'na', user: JSON.stringify(users), getter: true, action: 'na', modaldata: 'na'}}]);

                            promises[0].done(function(response) {
                                var data = JSON.parse(JSON.stringify(response));


                                body = data.form;
                                title = data.title;
                                if(!data.q){
                                    q = data.q;
                                    message = data.message;
                                }



                                var editaPresent = str.get_string('choose', 'core');
                                $.when(editaPresent).done(function(localizedEditString) {
                                    strChoose  = localizedEditString;
                                });
                                if(q){
                                    console.log();
                                    ModalFactory.create({
                                      type: ModalFactory.types.SAVE_CANCEL,
                                      title: title,
                                      body: body,
                                      large: true
                                  }, trigger)
                                    .done(function(modal) {
                                        modal.show();
                                        switch ($name) {
                                            case 'cohortadd':
                                            fieldToValidate = '#frmcohortsadd #cohortlist';
                                            break;
                                            case 'message':
                                            $("#message_bulk_container").appendTo(".modal-dialog .modal-body");
                                            $('.modal-dialog .modal-body').find('form').removeClass('hidden');
                                            $('.modal-dialog .modal-body form input[name="users"]').val(users.join(','));
                                            fieldToValidate = '.modal-dialog .modal-body #id_messagebody';
                                            break;
                                            default:
                                            break;
                                        }
                                        modal.getRoot().on(ModalEvents.save, function(e) {
                                            var error = 'error';
                                            var message_accept = '';
                                        // Stop the default save button behaviour which is to close the modal.
                                        e.preventDefault();
                                        
                                        if($(fieldToValidate).length > 0 && $(fieldToValidate).val() != ''){

                                            var promises = Ajax.call([{
                                                methodname : 'local_people_get_people',
                                                args: { form: 'na', message: 'na', q: false, title: 'na', user: 'na', getter: false, action: $name, modaldata: JSON.stringify({users: users, input_value: $(fieldToValidate).val()})}}]);

                                            promises[0].done(function(response) {
                                                var data = JSON.parse(JSON.stringify(response));
                                                error = (!data.error)?'success':'';
                                                if (!$.trim(data)){
                                                    var editaPresent = str.get_string('success_message_users', 'local_people');
                                                    $.when(editaPresent).done(function(localizedEditString) {
                                                        $.notify(localizedEditString, 'success');
                                                    });
                                                } else {
                                                    message_accept = data.message;
                                                }
                                            }).fail(function(exception) {
                                             fail: Notification.exception
                                         });
                                            
                                            $.notify(message_accept, error); 
                                        }else{
                                            var editaPresent = str.get_string('warning_fields', 'local_people');
                                            $.when(editaPresent).done(function(localizedEditString) {
                                                $.notify(localizedEditString, 'error');
                                            });
                                        }
                                        modal.hide();
                                    });
                                    });
                                }else{
                                 $.notify(message, "error"); 
                             }


                         }).fail(function(exception) {
                             fail: Notification.exception
                         });

                     }
                 }
             });

$('.btn-bulk-suspend_unsuspend').click(function (e) {

    e.preventDefault();

    var users = [];
    var $name = 'suspend_unsuspend';
    var $action = $(this).data('action')

    $('input[name^=id]:checked:enabled').each(function () {

        users.push($(this).val());
    });

    if (users.length > 0) {
        setCookie('selected_bulks', [], 1);
        var $data = JSON.stringify({name: $name, data: users});
        var $form = $("<form method='post' action='" + M.cfg.wwwroot + "/local/people/actions/user_bulk_suspend_unsuspend.php'>").append($('<input type="hidden" name="bulk_action" id="data">').val($data));
        $form.append($('<input type="hidden" name="' + $action + '">').val(1));
        $form.append($('<input type="hidden" name="sesskey">').val(M.cfg.sesskey));
        $form.appendTo("body").submit();
    }
});

$("input:radio[name='unsuspended']").click(function(){
    this.checked = true;
    $("input:radio[name='suspended']").each(function(i) {
        this.checked = false;
    });
});
$("input:radio[name='suspended']").click(function(){
 this.checked = true;
 $("input:radio[name='unsuspended']").each(function(i) {
     this.checked = false;
 });
});

                //Unconfimed radio button
                $("label[for='unconfirmed']").click(function(){
                  this.checked = true;
                  $("#confirmed").each(function(i) {
                      this.checked = false;
                  });
              });
                $("label[for='confirmed']").click(function(){
                  this.checked = true;
                  $("#unconfirmed").each(function(i) {
                      this.checked = false;
                  });
              });

                $('#enrol_user').click(function (e) {
                    var users = [];

                    $('input[name^=id]:checked:enabled').each(function () {
                        users.push($(this).val());
                    });
                    if (users.length <= 0) {
                        return;
                    }


                    var trigger = $('#enrollModal');
                    ModalFactory.create({
                      title: params.title,
                      body: params.body,
                      footer: params.footer,
                  }, trigger)
                    .done(function(modal) {
                        modal.show();
                        // var lan = moment.locale('es') 
                        // alert(lan);
                        var datepicker_config = {
                            icons : {
                                'time' : 'fa fa-clock-o',
                                'date' : 'fa fa-calendar',
                                'up' : 'fa fa-chevron-up',
                                'down' : 'fa fa-chevron-down',
                                'previous' : 'fa fa-chevron-left',
                                'next' : 'fa fa-chevron-right',
                                'today' : 'fa fa-screenshot',
                                'clear' : 'fa fa-trash',
                                'close' : 'fa fa-remove',
                            },
                            format: 'MM/DD/YYYY',
                            // locale: 'es'
                        };
                        
                        $('#EnrolForm #selectcourses').select2({
                            width: '50%',
                        });
                        $('#EnrolForm #startdate').datetimepicker(datepicker_config);
                        $('#EnrolForm #enddate').datetimepicker(datepicker_config);
                        /**
                         * @desc #1446 Plugin rewrite beta/ Manage People
                         * Added the button to clear the date picker value from input text
                         * @author Gautam Shukla
                         * @since  24 Feb 2022
                         * @remui
                         */
                        $("#clearstartdate").click(function(){
                            $("#startdate").val("");
                        });
                        $("#clearenddate").click(function(){
                            $("#enddate").val("");
                        });
                        //END

                        $('#EnrolForm #selectroles').select2({
                            width: '50%',
                        });
                        var users = [];

                        $('form#EnrolForm input[name^="id"]').remove();

                        $('input[name^=id]:checked:enabled').each(function () {

                            users.push($(this).val());
                        });
                        if (users.length > 0) {

                            setCookie('selected_bulks', [], 1);

                            $.each(users, function (key, value) {
                                $('form#EnrolForm').append('<input type="hidden" name="id[]" value="' + value + '" />');
                            });
                        }

                        $('button#enroll').click(function(){
                            $('form#EnrolForm').submit();
                        });
                    });

                });

                // assign initial value
                $('#form-bulk-actions input:checkbox').eq(0).attr('value', '0');

                $('#bulk-select-all').click(function () {
                    var checked = document.getElementById("bulk-select-all").checked;
                    if (checked) {
                        $('#form-bulk-actions input:checkbox').attr('checked', 'checked');
                        $('#form-bulk-actions input:checkbox').prop('checked', true); 
                        $('#form-bulk-actions tbody tr').addClass('table-active');

                        $("#form-bulk-actions label.form-checkbox").each(function (index) {

                            if ($.inArray($('input', this).val(), selected_bulk) == -1 && $('input', this).val() != undefined) {

                                selected_bulk.push($('input[name^=id]:enabled', this).val());
                            }
                        });

                    } else {
                        $('#form-bulk-actions input:checkbox').removeAttr('checked');
                        $('#form-bulk-actions input:checkbox').prop('checked', false); 
                        $('#form-bulk-actions tbody tr').removeClass('table-active');

                        var removeItem = $('input[name^=id]:enabled', this).val();

                        $("#form-bulk-actions label.form-checkbox").each(function (index) {

                            var removeItem = $('input[name^=id]:enabled', this).val();

                            selected_bulk = $.grep(selected_bulk, function (value) {
                                return value != removeItem;
                            });
                        });

                    }

                    setCookie('selected_bulks', JSON.stringify(selected_bulk), 1);
                });
                //check people multitenant value from user preferences table and show/hide respective column
                var multi_val=$("#show_tenat_company_val").val();
                if(multi_val==1){
                   $('#show_tenat_company').prop('checked', true);
                   $('td:nth-child(7),th:nth-child(7)').show();
               }else  if(multi_val==0){
                $('#show_tenat_company').prop('checked', false);
                $('td:nth-child(7),th:nth-child(7)').hide();
            }
                //ajax call for multitent checkbox and respective column
                $("#show_tenat_company").click(function(){
                    var ajaxUrll = M.cfg.wwwroot + '/local/people/ajax/multitenant_user_ajax.php?sesskey=' + M.cfg.sesskey;
                    if($('#show_tenat_company').is(":checked")){
                        var promises = Ajax.call([{
                            methodname : 'local_people_get_local_people',
                            args: { tenant_val: 1, activity: 'na', status: 'na', message: 'na' }}]);

                        promises[0].done(function(response){
                            $('td:nth-child(7),th:nth-child(7)').show();
                        }).fail(function(exception) {
                         fail: Notification.exception
                     });
                    }else{
                        var promises = Ajax.call([{
                            methodname : 'local_people_get_local_people',
                            args: { tenant_val: 0, activity: 'na', status: 'na', message: 'na' }}]);

                        promises[0].done(function(response){
                            $('td:nth-child(7),th:nth-child(7)').hide();
                        }).fail(function(exception) {
                         fail: Notification.exception
                     });
                    }
                });

                $('#bulk-select-all').click(function () {

                    // $('#show_tenat_company').prop('checked', false);
                    var status = ($('#form-bulk-actions input[checked="checked"]').length > 1) ? true : false;

                    enableButtons(status);
                });

                var enableButtons = function (status) {

                    var buttons = ['delete', 'download', 'message', 'cohortadd', 'suspend', 'unlock'];

                    $(buttons).each(function (index, element) {
                        if (status) {
                            $('#enrol_user, a[data-action="' + element + '"]').css('opacity', '1');
                            $('#enrol_user, a[data-action="' + element + '"]').css('cursor', 'pointer');
                            $('#enrol_user, a[data-action="' + element + '"]').addClass('active-bulk');                            
                            $('#enrol_user, a[data-action="' + element + '"]').find('.add-tooltip').tooltip('enable');
                            $('#enrol_user').attr("data-target", "#enrollModal");

                        } else {
                            $('#enrol_user, a[data-action="' + element + '"]').css('opacity', '0.3');
                            $('#enrol_user, a[data-action="' + element + '"]').css('cursor', 'default');
                            $('#enrol_user, a[data-action="' + element + '"]').removeClass('active-bulk');
                            $('#enrol_user, a[data-action="' + element + '"]').find('.add-tooltip').tooltip('disable');
                            $('#enrol_user').attr("data-target", "");
                        }
                    })
                };

                $('#form-bulk-actions input').change(function (e) {

                    var status = ($('#form-bulk-actions input:checked').length > 0) ? true : false;

                    enableButtons(status);


                    if (!$(this).closest('tr').hasClass('table-active')) {

                        $(this).closest('tr').addClass('table-active');
                    } else {

                        $(this).closest('tr').removeClass('table-active');
                    }
                });

                var status = (parseInt($(".table-people input:checked").length) > 0) ? true : false; // C. Alcaraz - By Default, disabled

                enableButtons(status);

                $('#form-bulk-actions .form-checkbox').change(function (e) {

                    if ($('input[name^=id]:checked:enabled', this).val()) {
                        if ($.inArray($('input', this).val(), selected_bulk) == -1) {

                            selected_bulk.push($('input[name^=id]:checked:enabled', this).val());
                        }

                    } else {

                        var removeItem = $('input[name^=id]:enabled', this).val();

                        selected_bulk = $.grep(selected_bulk, function (value) {
                            return value != removeItem;
                        });
                    }

                    setCookie('selected_bulks', JSON.stringify(selected_bulk), 1);
                });


                $("#form-bulk-actions label.form-checkbox").each(function (index) {

                    if ($.inArray($('input', this).val(), selected_bulk) !== -1) {

                        $(this).click();
                    }
                });

                $("input:radio").click(function () {
                    var label = $(this).parent();
                    if (!label.hasClass("active")) {
                        label.addClass("active");
                    }

                });
                //show panel-collapse when searching for a user using various searching criteria
                $('.filter-true').each(function() {
                    var formid= $(this).closest("form").attr('id');
                    if(formid!=''){
                        var divid = $("#"+formid).closest('div.panel-collapse').attr('id');
                        $("#"+divid).addClass("show");
                    }
                });
            });
}
};
});