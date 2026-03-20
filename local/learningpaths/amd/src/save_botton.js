/**
 * Declare var lang_locale to get current language and get moment locale accord to lang.
 * 
 * @author Manisha M
 * @author Paradiso
 * @since 13-08-2019
 * @ticket #362
*/
var lang_locale='',
lang = document.getElementsByTagName('html')[0].getAttribute('lang');
if( lang != '' && lang != 'en' && lang != 'en-us'){
     lang_locale=  "local_people/" + lang;
}
define([
    "jquery",
    lang_locale,
    "local_learningpaths/bootbox",
    "local_people/bootstrap-datetimepicker","core/ajax","core/notification","core/str"
], function($, lang_locale,bootbox, datetimepicker,ajax, notification, str) {
    function delay(callback, ms) {
        var timer = 0;
        return function () {
            var context = this,
                args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }

    /**
     * Disable Save changes Learning path button while creating learning path
     * 
     * @author Manisha M
     * @author Paradiso
     * @since 20-06-2019
     * @ticket #550
     */
    function disabledButton() {
        // check if other required fields have value, then make button active
        var a_tag_html = $('.filepicker-filename').find('a').html();
        
        if(a_tag_html !== undefined  && a_tag_html !== "" && ($("#id_description").val() != "" && $("#id_description").val() != "<p><br></p>") && $("#id_name").val() !== ""){
            $("#id_submitbutton").attr("disabled", false);
        }else{
            $("#id_submitbutton").attr("disabled", true);
        }
    }

    return {
        init: function () {
            $(document).ready(function () {

                
                    $(document).on(
                        "keyup",
                        ".modal.add-learningspath .mform #id_name",
                        delay(function (e) {
                            duplicatelpname($(this).val());
                        }, 1000)
                    );

                    $(document).on(
                        "keyup",
                        "#page-local-learningpaths-edit .mform #id_name",
                        delay(function (e) {
                            var editid = parseInt($("#page-local-learningpaths-edit .mform input[name='id']").val());
                            duplicatelpname($(this).val(),editid);
                        }, 1000)
                    );

                function duplicatelpname(lname,id=0) {
                    var lstr = str.get_string("namealreadyexist","local_learningpaths");
                    ajax.call([{
                        methodname: 'local_learningpath_duplicatecheck',
                        args: {inputname: lname, editid:id},
                        done: function(data) {
                          if (data.result === true) {
                            $.when(lstr).done(function(msg) {
                                $(".mform #id_error_name").css('display','block').text(msg);
                            });
                            $(".mform #id_submitbutton").attr("disabled", true);
                          }else{
                            $(".mform #id_submitbutton").attr("disabled", false);
                            $(".mform #id_error_name").css('display','none').text('');
                          }
                         },
                         fail: function(error) {
                         },
                    }]);
                
                }
                
                $("#id_userperpage,#id_cohortuserperpage,#id_courseperpage,#id_cohortsperpage").change(function () {
                    $(this)
                        .parents("form:first")
                        .submit();
                });
                
                //learning path course form validation
                $('#courses-popup-content form').on('submit', function(e){
                    e.preventDefault();
                   if (!$('.course-learninpath').is(':checked')) {
                        bootbox.alert({
                            message: M.util.get_string("learningpath_required_course", "local_learningpaths")
                        });
                        return false;
                   } else {
                       this.submit();
                       $('#courses-popup').modal('hide');
                   }
                });
                
                //user form validation
                $('#users-popup-content form').on('submit', function(e){
                    e.preventDefault();
                   if (!$('.users-lpall').is(':checked')) {
                        bootbox.alert({
                            message: M.util.get_string("learningpath_required_user", "local_learningpaths")
                        });
                        return false;
                   } else {
                       this.submit();
                   }
                });
                
                //cohort validation
                 $('#cohorts-popup-content form').on('submit', function(e){
                    e.preventDefault();
                   if (!$('.cohort-learninpath').is(':checked')) {
                        bootbox.alert({
                            message: M.util.get_string("learningpath_required_cohorts", "local_learningpaths")
                        });
                        return false;
                   } else {
                       this.submit();
                   }
                });


                var icons = {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-chevron-up",
                    down: "fa fa-chevron-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-screenshot",
                    clear: "fa fa-trash",
                    close: "fa fa-remove"
                };
                $("#id_enddate").datetimepicker({
                    icons:icons,
                    format: "MM/DD/YYYY",
                    defaultDate : $("#lp_enddate").val()
                });
                $("#id_startdate").datetimepicker({
                    icons:icons,
                    format: "MM/DD/YYYY",
                    defaultDate : $("#lp_startdate").val()
                });

                $("#id_startdate").on("dp.change", function (e) {
                    $("#lp_startdate").val($("#id_startdate").val());
                });

                $("#id_enddate").on("dp.change", function (e) {
                    $("#lp_enddate").val($("#id_enddate").val());
                    if ($("#id_startdate").val() !== "") {
                        $("#id_enddate")
                            .data("DateTimePicker")
                            .minDate($("#id_startdate").val());
                    } else {
                        return false;
                    }
                });
            });
            if (document.querySelector("#page-local-learningpaths-index") !== null) {
                $("#id_submitbutton").attr("disabled", true);
                $("input[name*='learningpath_imagechoose']").on(
                    "click",
                    disabledButton
                );
                $('input[name="learningpath_image"], #id_description').on('change', disabledButton);
                $("#id_name").on('keyup', disabledButton);

                $("#id_submitbutton").on("click", validateFilePicker);
                function validateFilePicker() {
                    while (document.querySelector(".filepicker-container") !== null) {
                        $("#id_submitbutton").attr("disabled", true);
                        bootbox.alert(
                            M.util.get_string("mandatory_msg", "local_learningpaths")
                        );
                        break;
                    }
                    while (document.querySelector(".filepicker-container") == null) {
                        $("#id_submitbutton").attr("disabled", false);
                        return true;
                    }
                }
            }
            var selected = [];
            $(document).on(
                "keyup",
                ".add-users-search",
                delay(function (e) {
                    e.preventDefault();
                    var id = $("#users-popup input[name=id]").val();
                    var url = $("#users-popup ul.pagination li > a").attr("href");
                    var search = $(".add-users-search").val();
                    if(search == '') {
                        $('.contentenrollusers input:checked').each(function() {
                            selected.push($(this).val());
                        });
                        var selectedusers = selected.toString();
                    } else {
                        selectedusers = 0;
                    }
                    var requests = ajax.call([{
                      methodname: 'local_learningpath_ajax',
                      args: {action: "pagination", id: id, search: search,perpage: 10,page: "0",selected:selectedusers}
                  }]);
                  requests[0].done(function(response) {

                    if (response.msg) {
                      $("#users-popup #users-popup-content").empty();
                      $("#users-popup-content").html(response.html);
                      $(".add-users-search").focus();
                      var tmpStr = $(".add-users-search").val();
                      $(".add-users-search").val("");
                      $(".add-users-search").val(search);
                  }
                    
                  }).fail(notification.exception);
                    return false;
                }, 1000)
            );
            $('a#add-users-lp').click(function() {
                $(".add-users-search").val('');
                clear_search_user();
            });
            var selected = [];
            function clear_search_user() {
                var id = $("#users-popup input[name=id]").val();
                var url = $("#users-popup ul.pagination li > a").attr("href");
                var search = $(".add-users-search").val();
                $('.contentenrollusers input:checked').each(function() {
                    selected.push($(this).val());
                });
                var selectedusers = selected.toString();
                var requests = ajax.call([{
                    methodname: 'local_learningpath_ajax',
                    args: {action: "pagination", id: id, search: search,perpage: 10,page: 0,selected:selectedusers}
                }]);
                requests[0].done(function(response) {
                if (response.msg) {
                    $("#users-popup #users-popup-content").empty();
                    $("#users-popup-content").html(response.html);
                    $(".add-users-search").focus();
                    var tmpStr = $(".add-users-search").val();
                    $(".add-users-search").val("");
                    $(".add-users-search").val(search);
                }
                }).fail(notification.exception);
            }
            var selected = [];
            $(document).on("click", "#users-popup ul.pagination li > a", function (e) {
                e.preventDefault();
                // e.preventDefault();
                $('.contentenrollusers input:checked').each(function() {
                    selected.push($(this).val());
                });
                var selectedusers = selected.toString();
                var id = $("#users-popup input[name=id]").val();
                var url = $(this).attr("href");
                var urllast = url.replace("?", "#");
                urllast = urllast.replace("&", "#");
                var search = $(".add-users-search")
                    .val()
                    .toLowerCase();

                var URLSplited = urllast.split("#");
                var page = URLSplited[1].split("=");

                var requests = ajax.call([{
                  methodname: 'local_learningpath_ajax',
                  args: {action: "pagination", id: id, search: search,perpage: 10,page: page[1],selected:selectedusers}
                }]);
              requests[0].done(function(response) {
                if (response.msg) {
                  $("#users-popup #users-popup-content").empty();
                  $("#users-popup-content").html(response.html);
                } 
              }).fail(notification.exception);
                return false;
            });
            $(document).on("change", "#users-popup #id_userpopupperpage", function (e) {
                var perpage = this.value;
                var id = $("#users-popup input[name=id]").val();
                var search = $(".add-users-search")
                    .val()
                    .toLowerCase();
                var requests = ajax.call([{
                  methodname: 'local_learningpath_ajax',
                  args: {action: "pagination", id: id, search: search,perpage: parseInt(perpage),page: 0,selected:0}
                }]);
              requests[0].done(function(response) {
                if (response.msg) {
                  $("#users-popup #users-popup-content").empty();
                  $("#users-popup-content").html(response.html);
                  $(".add-users-search").val(search);
              } 
              }).fail(notification.exception);
                return false;
            });

            $(".course-lp .form-check-input").click(function () {
                if ($(this).is(":checked")) {
                    $(this)
                        .parent()
                        .parent()
                        .parent()
                        .addClass("checkbbg");
                } else {
                    $(this)
                        .parent()
                        .parent()
                        .parent()
                        .removeClass("checkbbg");
                }
            });
            
            $("#course-all").click(function () {
                if ($("#course-all").is(":checked")) {
                    $(".course-lp").addClass(" checkbbg");
                } else {
                    //$('.course-lp .form-check-input').removAttr('checked');
                    $(".course-lp").removeClass(" checkbbg");
                }
            });
            
            $(".course-learninpath .form-check-input").click(function () {
                if ($(this).is(":checked")) {
                    $(this)
                        .parent()
                        .parent()
                        .parent()
                        .addClass("checkbbg");
                } else {
                    $(this)
                        .parent()
                        .parent()
                        .parent()
                        .removeClass("checkbbg");
                }
            });
            
        } //end-if
    };
});


