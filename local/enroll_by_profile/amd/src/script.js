 /*
  * author: Vaibhav G
  * Description :got this code from Esteban
  * made changes by vaibhav 21Jan 2019
  */
  /**
  * @module local_enroll_by_profile/script
  */

  define(['jquery',
      'jqueryui',
      'theme_remui/bootbox',
      'theme_remui/notify','core/str',
      'core/templates',
      'core/notification',
      'core/ajax'
      //for some reason they woul not work!!!
    ], function($, jqueryui, bootbox, notify,str,Templates,notification,Ajax) {

    function specialalert(msg){
      bootbox.alert(msg);
    }

    function specialconfirm(msg,action){
      bootbox.confirm({
        size: "small",
        message: msg,
        callback: action
      });
    }
    var strings = str.get_strings([
      { key: 'no_rule_selected', component: 'local_enroll_by_profile' },
      { key: 'confirm_message_disable_selected_rule', component: 'local_enroll_by_profile' },
      { key: 'confirm_message_keepenroll_selected_rule', component: 'local_enroll_by_profile' },
      { key: 'confirm_message_keepunenroll_selected_rule', component: 'local_enroll_by_profile' },
      { key: 'error5', component: 'local_enroll_by_profile' },
      { key: 'errorname', component: 'local_enroll_by_profile' },
      { key: 'errornamechar', component: 'local_enroll_by_profile' }
    ]);

    return {
      init: function() {
        $(document).ready(function(){
          var delay = (function(){
            var timer = 0;
            return function(callback, ms){
              clearTimeout (timer);
              timer = setTimeout(callback, ms);
          };
        })();
          /*
          * @author VaibhavG
          * @since 5th March 2021
          * @desc 509 Rules Engine issues fixes. Main search bar enhancement.
          */
          $(document).on("keyup","#search", function() { 
            var value = $(this).val().toLowerCase();
            var page = getUrlParameter('page');
            delay(function(){
              var request = {
                methodname: 'local_enroll_by_profile_search',
                args: {search_value: value , search_page: page}
            };

            Ajax.call([request])[0].done(function(response) {
              Templates.render('local_enroll_by_profile/enrollbyprofile', response)
                .done(function(html) {
                  if(value) {
                    $('#rules_table .pagination').hide();
                  } else {
                    $('#rules_table .pagination').show();
                  }
                    $('#id_elements_content').empty();
                    $('#mortalEngines').html(html);
                    checkboxes_selection_();
                    dropdown_hide_show();
                });
            }).fail(function(ex) {
                  var localstring = str.get_string("no_rules","local_enroll_by_profile")
                  $.when(localstring).done(function(errmsg) {
                    $('#mortalEngines').html(errmsg);
                  });
              console.log('error is ' + ex);
            });
            }, 800 );          
                 

          });

          var getUrlParameter = function getUrlParameter(sParam) {
            var sPageURL = window.location.search.substring(1),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                }
            }
            return false;
          };


          /*
          * @author VaibhavG
          * @since 3rd March 2021
          * @desc 509 Rules Engine issues fixes. All selected Delete/ disable enable rule
          */
          $(document).on("click",".checkAll", function(e) {   
            if (this.checked) {
               $(".selectall").prop("checked", true);
               $('.delete_rule_all').css("pointer-events", "auto");
               $('.disable_rule_all').css("pointer-events", "auto");
               $('#choise_select').val(0);
               var rule_ids = new Array();
               $(".selectall:checked").each(function() {
                 rule_ids.push($(this).attr('data-ruleid'));
               });
               $('#choise_selected').val(rule_ids);
            } else {
               $(".selectall").prop("checked", false);
               $('#choise_select').val(0);
               $('#choise_selected').val(' ');
               items = [];
            } 
          });

          var items = [];
          $(document).on("click",".selectall", function(e) {   
              var numberOfCheckboxes = $(".selectall").length;
              var numberOfCheckboxesChecked = $('.selectall:checked').length;
              var i = $(this).attr('id');
              i = i.replace(/[^0-9.]/g, "");
              if (this.checked) {
                var exist_data = $('#choise_selected').val();
                items = exist_data.split(',');
                 items.push(i); 
                 $('#choise_selected').val(items);
              }else{
                var nameArr = [];
                var its = [];
                its = $('#choise_selected').val();
                nameArr = its.split(',');
                removeElement(nameArr,i);
                $('#choise_selected').val(nameArr);
              }
                
              if(numberOfCheckboxes == numberOfCheckboxesChecked) {
                 $(".checkAll").prop("checked", true);
                 $('.delete_rule_all').css("pointer-events", "auto");
                 $('.disable_rule_all').css("pointer-events", "auto");
              } else {
                 $(".checkAll").prop("checked", false);
                 $('.delete_rule_all').css("pointer-events", "auto");
                 $('.disable_rule_all').css("pointer-events", "auto");
                 $('#choise_select').val(0);
                
              }

          });

          function removeElement(array, elem) {
             var index = array.indexOf(elem);
             if (index > -1) {
                array.splice(index, 1);
              }
          } 
          $(document).on('click', 'a.add.rules_btn.delete_rule_all', function(e) {
            e.preventDefault();
            var choise_select = $('#choise_select').val();
            var choise_selected = $('#choise_selected').val();
            var rule_ids = new Array();
            $(".selectall:checked").each(function() {
              rule_ids.push($(this).attr('data-ruleid'));
            });
            var ids=rule_ids.length;
            if(ids==0){
              $(".error_notification").html(M.util.get_string('no_rule_selected','local_enroll_by_profile'));
              $('.error_notification').addClass("alert alert-danger");
            }else{
              $(".error_notification").html("");
              $('.error_notification').removeClass("alert alert-danger");
              var result = specialconfirm(M.util.get_string('confirm_message_delete_rule','local_enroll_by_profile'),deletemultiplerules );
              function deletemultiplerules(result){
                if(result){
                  var request = {
                      methodname: 'local_enroll_by_profile_delete_rule_btn',
                      args: {allselect: choise_select , selectedrule: choise_selected}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    $.each(response, function(index,element){
                        if(index == 'msg'){
                          $('#label_notify').text(element);
                        }
                    });                      
                    $("#rules_table").load(window.location + " #rules_table");
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    });
                    //location.reload();
                    window.location.href = M.cfg.wwwroot + '/local/enroll_by_profile/index.php';
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });

                }
              }
            }
          });

          /*
          * @author VaibhavG
          * @since 3rd March 2021
          * @desc 509 Rules Engine issues fixes. All selected keep unenroll users
          */
          
          checkboxes_selection_();
          function checkboxes_selection_(){
            var numberOfUnenrollCheckboxes = $(".keepunenroll").length;
            
            var numberOfUnenrollCheckboxesChecked = $('.keepunenroll:checked').length;
            
            var numberOfUnenrollCheckboxesNonChecked = $('.keepunenroll:checkbox:not(:checked)').length;

            if(numberOfUnenrollCheckboxesChecked >= 1) {
              var checkedstring = str.get_string("checked_all","local_enroll_by_profile")
              $.when(checkedstring).done(function(checkedstringmsg) {
                $('.unenrollAll').attr('title',checkedstringmsg);
              });
              $(".unenrollAll").prop("checked", true);
            }
            if((numberOfUnenrollCheckboxesChecked == 0)) {
              var uncheckedstring = str.get_string("unchecked_all","local_enroll_by_profile")
              $.when(uncheckedstring).done(function(uncheckedstringmsg) {
                $('.unenrollAll').attr('title',uncheckedstringmsg);
              });
              $(".unenrollAll").prop("checked", false);
            }
            if((numberOfUnenrollCheckboxesChecked === numberOfUnenrollCheckboxes)) {
              var checkedstring = str.get_string("checked_all","local_enroll_by_profile")
              $.when(checkedstring).done(function(checkedstringmsg) {
                $('.unenrollAll').attr('title',checkedstringmsg);
              });
              $(".unenrollAll").prop("checked", true);
            }
            if((numberOfUnenrollCheckboxesNonChecked === numberOfUnenrollCheckboxes)) {
              var uncheckedstring = str.get_string("unchecked_all","local_enroll_by_profile")
              $.when(uncheckedstring).done(function(uncheckedstringmsg) {
                $('.unenrollAll').attr('title',uncheckedstringmsg);
              });
              $(".unenrollAll").prop("checked", false);
            }
          }

            
          $(document).on("click",".unenrollAll", function(e) {   
            if (this.checked) {
              e.preventDefault(); 
              var keep_unenroll_msg = M.util.get_string('confirm_message_keepunenroll_selected_rule','local_enroll_by_profile');
              var result = specialconfirm(keep_unenroll_msg, keep_unenroll_the_rule_all );                
              function keep_unenroll_the_rule_all(result){
                if(result){
                  e.preventDefault(); 
                  var checked_rule_ids = new Array();
                  $("input[type=checkbox]").each(function() {
                      checked_rule_ids.push($(this).attr('data-ruleid'));
                  });

                  e.preventDefault();

                  var request = {
                      methodname: 'local_enroll_by_profile_keep_unenroll_all',
                      args: {rid: checked_rule_ids}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    var element = str.get_string('keep_unenroll','local_enroll_by_profile');
                    $.when(element).done(function(errmsg) {
                      $("#label_notify").html(errmsg);
                    });
                                          
                    $("#rules_table").load(window.location + " #rules_table");
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    }); 
                    location.reload();
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });
                }else{
                    $(this).prop("checked", false);
                }
              }
            } else {
              e.preventDefault(); 
              var keep_enroll_msg = M.util.get_string('confirm_message_keepenroll_selected_rule','local_enroll_by_profile');
              var result = specialconfirm(keep_enroll_msg, keep_enroll_the_rule_all );                
              function keep_enroll_the_rule_all(result){                
                if(result){
                  e.preventDefault(); 
                  var nonchecked_rule_ids = new Array();
                  $("input[type=checkbox]").each(function() {
                    nonchecked_rule_ids.push($(this).attr('data-ruleid'));
                  });
                  e.preventDefault();

                  var request = {
                      methodname: 'local_enroll_by_profile_keep_enroll_all',
                      args: {rid: nonchecked_rule_ids}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    var element = str.get_string('keep_enroll','local_enroll_by_profile');
                    $.when(element).done(function(errmsg) {
                      $("#label_notify").html(errmsg);
                    });
                                          
                    $("#rules_table").load(window.location + " #rules_table");
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    }); 
                    location.reload();
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });
                  location.reload();
                }else{
                    $(this).prop("checked", false);
                }
              }
            }              
          });
           
             
          $(document).on("click",".keepunenroll", function(e) {   
            var checkme = (this.checked ? 'checked' : 'unchecked');
            
            if(checkme == "unchecked") {
              var rule_id = $(this).attr('data-ruleid');
              
              e.preventDefault();
              var keep_single_enroll_msg = M.util.get_string('confirm_message_keepenroll_selected_rule','local_enroll_by_profile');
              var result = specialconfirm(keep_single_enroll_msg, keep_enroll_the_rule );
              function keep_enroll_the_rule(result){                               
                if(result){

                  var request = {
                    methodname: 'local_enroll_by_profile_keep_enroll',
                    args: {rid: rule_id}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    var element = str.get_string('keep_enroll','local_enroll_by_profile');
                    $.when(element).done(function(errmsg) {
                      $("#label_notify").html(errmsg);
                    });
                                          
                    $("#rules_table").load(window.location + " #rules_table");
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    });  
                    location.reload();
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });
                  location.reload();
                }else{
                  $(this).prop("checked", false);
                }
              }                  
            }else if(checkme == "checked") {
              var rule_id = $(this).attr('data-ruleid');
             
              e.preventDefault();
              var keep_unenroll_msg = M.util.get_string('confirm_message_keepunenroll_selected_rule','local_enroll_by_profile');
              var result = specialconfirm(keep_unenroll_msg, keep_unenroll_the_rule );
              function keep_unenroll_the_rule(result){                               
                if(result){

                  var request = {
                    methodname: 'local_enroll_by_profile_keep_unenroll',
                    args: {rid: rule_id}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    var element = str.get_string('keep_unenroll','local_enroll_by_profile');
                    $.when(element).done(function(errmsg) {
                      $("#label_notify").html(errmsg);
                    });
                                          
                    $("#rules_table").load(window.location + " #rules_table");
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    }); 
                    location.reload();
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });
                  
                  location.reload();
                }else{
                  $(this).prop("checked", false);
                }
              }                  
            }                
         });


        /*
        * @author VaibhavG
        * @since 19th Feb 2021
        * @desc 509 Rules Engine issues fixes.
        */
        $(document).on("keyup","#search_cat", function() {        
            var value = $(this).val().toLowerCase();
            $("#id_elements_content div label").filter(function() {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
            $('.emptyrow').hide();
        });

        function init_modal(){

          var request = {
              methodname: 'local_enroll_by_profile_addcond',
              args: {condid: 1}
          };

          Ajax.call([request])[0].done(function(response) {
            Templates.render('local_enroll_by_profile/newcondition_form', response)
            .done(function(html) {
              $('.conditions-view').empty();
              $('.conditions-view').append(response.rowdata.boolop_html);
              $('.conditions-view').append(html);
              $(".btn-delete").off().on("click",delete_conditional);
              $("#id_conditions_num").val(id);
              $(".boolopstch").off("click").on("click",booloptoggle);
              $('.profile-field').off().on('change', get_cond_html);
              $("input[name=negated]").off().on("click", negate_condition);
              remove_boolop_first_cond();
              conditions = load_conditions_from_list();
              render_rule_statement(conditions);
            });
          }).fail(function(ex) {
            console.log('error is ' + ex);
          });
          

        }

        $('#id_category_type').on('change',function(){
          var category = $(this).val();

          var request = {
            methodname: 'local_enroll_by_profile_get_category',
            args: {category: category}
          };

          Ajax.call([request])[0].done(function(response) {
            var templatename = '';
            if(category == 1){
              templatename = 'get_courses';
            }else if(category == 2){
              templatename = 'get_tenant';
            }else if(category == 3){
              templatename = 'get_lp';
            }else if(category == 4){
              templatename = 'get_cohort';
            }else if(category == 5){
              templatename = 'get_role';
            }else if(category == 6){
              templatename = 'get_learning_plans';
            }
            Templates.render('local_enroll_by_profile/'+templatename, response)
            .done(function(html) {
              $('#id_elements_content').empty();
              $('#id_elements_content').append(html);
            });
          }).fail(function(ex) {
            console.log('error is ' + ex);
          });
        });

        $.datepicker.setDefaults({
          changeMonth: true,
          changeYear: true,
          onSelect:update_date
        });

        /*
        * @author VaibhavG
        * @since 17th Feb 2021
        * @desc 509 Rules Engine issues fixes.
        */
        var profile_field_id = $('.profile-field').attr('data-id');
        $('#id_profile_field'+profile_field_id).on('change',function(){
          var ftype = $('option:selected', '#id_profile_field'+profile_field_id).attr('data-type');
          if(ftype === "checkbox"){
            $("#conditional_actions"+profile_field_id+" label").hide();
          }else{
            $("#conditional_actions"+profile_field_id+" label").show();
          }

          $('#id_content').removeAttr("readonly");
          $('#id_category_type').attr("disabled",false);
        });

        $('#id_content').on('blur',function(){
          if($('#id_content').val()){
            $('#id_category_type').attr("disabled",false);
          }else
          {
            $('#id_category_type').attr("disabled",true);
          }
        });

        $('.content-value').on('keyup', update_value);

        /*
        * @author VaibhavG
        * @since 11th Feb 2021
        * @desc 509 Rules Engine issues fixes.
        */
        $(document).on("change",".select-conditional", function() {   
          $('.content-select').on('change', update_value);
          var datarule = $(".condition-item[data-id="+profile_field_id+"]").attr('data-rule');
          
          if(datarule == 'isselected'){
            $(".condition-item[data-id="+profile_field_id+"]").attr('data-value','["'+$("#id_content"+profile_field_id).val()+'"]');
          }
          
          //it gives info lable just after role field select.
          var ftype = $('#id_profile_field'+profile_field_id).val();
          if(ftype === "role"){
            $("#id_input_role_shortname_label").css("display", "block");
          }else{
            $("#id_input_role_shortname_label").css("display", "none");
          }

        });

        /*
        * @author VaibhavG
        * @since 19th Feb 2021
        * @desc 509 Rules Engine issues fixes.
        */
        $(document).on("click",".content-select", function() {  
          var datarule = $(".condition-item[data-id="+profile_field_id+"]").attr('data-rule');
          $('.content-select').on('change', update_value);
          $(".condition-item[data-id="+profile_field_id+"]").attr('data-value','["'+$("#id_content"+profile_field_id).val()+'"]');
          if(datarule == 'hasanyselected'){
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }
        });

        function update_value(e){
        
          var self = $(e.currentTarget);

          if(self.hasClass("interval")){
            var value = [];
            var id = self.parent().parent().parent().attr("data-id");
            
            var negatedstatement = self.parent().parent().parent().children("[type=hidden]").val();
            var negatedrule = self.parent().parent().parent().children("[type=hidden]").attr("name");
            var content = $("#id_content"+id).val()||"";
            var subcontent = $("#id_subcontent"+id).val()||"";
            
            if(self.attr("id") == "id_subcontent"+id){
                subcontent = self.val();
            }else{
                content = self.val();
            }
            
            value.push(content);
            value.push(subcontent);
          }else {
            var value = [];
            var id = self.parent().parent().attr("data-id");
            var negatedstatement = self.parent().parent().children("[type=hidden]").val();
            var negatedrule = self.parent().parent().children("[type=hidden]").attr("name");
           
            value.push(self.val());
          }
          
          $(".condition-item[data-id="+id+"]").attr("data-value",JSON.stringify(value));
          $(".condition-item[data-id="+id+"]").attr("data-negatedrule",negatedrule);
          $(".condition-item[data-id="+id+"]").attr("data-negatedstatement",negatedstatement);

           conditions = load_conditions_from_list();
           render_rule_statement(conditions);

        }

        function update_date(date,datepicker){
          
          var condition = $(this).parent().parent().parent().parent().parent();
          var value = [];
          if(condition.hasClass("condition-item")){
            var id = condition.attr("data-id");
            var negatedstatement = condition.find("[type=hidden]").val();
            var negatedrule = condition.find("[type=hidden]").attr("name");
            value.push(date);

          }else {
            var id = condition.parent().attr("data-id");
            var negatedstatement = condition.parent().find("[type=hidden]").val();
            var negatedrule = condition.parent().find("[type=hidden]").attr("name");
            
             if($(this).hasClass("end_date")){
               var val = $(this).parent().parent().parent().find(".start_date").val();
               value.push(val);
               value.push(date);
             }else{
               var val = $(this).parent().parent().parent().find(".end_date").val();
               value.push(date);
               value.push(val);
             }


          }

          $(".condition-item[data-id="+id+"]").attr("data-value",JSON.stringify(value));
          $(".condition-item[data-id="+id+"]").attr("data-negatedrule",negatedrule);
          $(".condition-item[data-id="+id+"]").attr("data-negatedstatement",negatedstatement);

          conditions = load_conditions_from_list();
          render_rule_statement(conditions);

        }

        $('#id_content').on('keyup',function(){
          if($('#id_content').val()){
              $('#id_category_type').attr("disabled",false);
          }else
          {
            $('#id_category_type').attr("disabled",true);
          }
        });


        $('#saveelements').click(function(e){ 
          $("#wait").css("display", "block");
          var rulename = $(".rule_statement").text();
          var namerule = $(".namerule").val();

          if(namerule == ""){
            $('#alert_content').empty();
            $('#alert_content').parent('.alert').css("display", "block");
            $('#alert_content').html(M.util.get_string('errorname','local_enroll_by_profile'));
            $('#alert_content').parent('.alert').removeClass('out');
            $('#alert_content').parent('.alert').addClass('in');
            $('#alert_content').parent('.alert').slideUp(3000, function(){
            $("#done_alert").slideUp(3000);});
            $("#wait").css("display", "none");
            return false ;
          }
          if(namerule.length>100){
            $('#alert_content').empty();
            $('#alert_content').parent('.alert').css("display", "block");
            $('#alert_content').html(M.util.get_string('errornamechar','local_enroll_by_profile'));
            $('#alert_content').parent('.alert').removeClass('out');
            $('#alert_content').parent('.alert').addClass('in');
            $('#alert_content').parent('.alert').slideUp(3000, function(){
            $("#done_alert").slideUp(3000);});
            $("#wait").css("display", "none");
            return false ;
          }
          var conditions = load_conditions_from_list();
          var category = $('#id_category_type').val();
          var statement = create_statement_from_conditions(conditions);
          if(conditions.length < 1){
            $('#alert_content').empty();
            $('#alert_content').html(M.util.get_string('check_condition_create_update','local_enroll_by_profile'));
            $('#alert_content').parent('.alert').removeClass('out');
            $('#alert_content').parent('.alert').addClass('in');
            var element = str.get_string('rulesuccessalert','local_enroll_by_profile');
            $.when(element).done(function(errmsg) {
              $("#label_notify").html(errmsg);
            });
            $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
              $('#label_notify').text('');
              $("#done_alert").slideUp(500);
            });
            
            return false ;
          }
          if(category != 2 ){
            var elements = new Array() ;
            var x = document.getElementsByName("elements[]");
            var profile = $("#id_profile_field").val();
            var panel= $("#content_input_element");
            var inputs = panel.find("input");

            if(inputs.length == 0){
              inputs = panel.find("select");

              if(inputs.length == 0){
                inputs = panel.find("textarea");
              }
            }

            var la_content = '';
            la_content += '[{';

            for (var i = 0; i <= inputs.length-1; i++) {
              if( $(inputs[i]).val() ) {
                if($(inputs[i])[0].type == 'radio'){
                  if($(inputs[i])[0].checked) la_content += $(inputs[i]).attr('name')+':'+$(inputs[i]).val()+',';
                }else{
                  if($(inputs[i]).val()) la_content += $(inputs[i]).attr('name')+':'+$(inputs[i]).val()+',';
                }
              }
            }

            la_content += '}]';

            for (var i = 0 ; i < x.length; i++) {
              if(x[i].checked){
                elements.push(x[i].value);
              }
            }

            profile = JSON.stringify(conditions);
            emptyconditions = [];
            for (var i = 0 ; i < conditions.length; i++) {
              if(conditions[i].value.length == 0){
                emptyconditions.push(conditions[i]);
              }
            }

            if(conditions[0].rule != 'isempty' || emptyconditions[0].rule != 'isempty'){
              if(emptyconditions.length > 0){
                $('#alert_content').empty();
                $('#alert_content').html("you still have "+emptyconditions.length+" empty conditions");
                for (var i = 0 ; i < emptyconditions.length; i++) {
                  $(".condition-item[data-id="+emptyconditions[i].id+"]").addClass("border-danger");
                }

                $('#alert_content').parent('.alert').removeClass('out');
                $('#alert_content').parent('.alert').addClass('in');
                $('#alert_content').parent('.alert').fadeTo(4000, 500).slideUp(500, function(){
                for (var i = 0 ; i < emptyconditions.length; i++) {
                  $(".condition-item[data-id="+emptyconditions[i].id+"]").removeClass("border-danger");
                }
                  $("#wait").css("display", "none");
                  $("#done_alert").slideUp(500);

                });
                return false ;
              }else {
                
              }
            }

            content = statement;
            la_content = content;
            if(elements.length > 0){
              if($('#id_editrule').val())
              {
                set_data(elements,category, profile ,content,$('#id_editrule').val(),rulename,namerule);
              }else if(!$('#id_editrule').val())
              {
                var rule;
                rule = 0;
                set_data(elements,category, profile ,la_content,rule,rulename,namerule);
              }
            }else{
              $('#alert_content').empty();
              $('#alert_content').html(M.util.get_string('error'+category,'local_enroll_by_profile'));
              $('#alert_content').parent('.alert').removeClass('out');
              $('#alert_content').parent('.alert').addClass('in');
              $('#alert_content').parent('.alert').fadeTo(4000, 500).slideUp(500, function(){
                $("#wait").css("display", "none");
                $("#done_alert").slideUp(500);});                                        
              return false ;
            }

            var element = str.get_string('rulesuccessalert','local_enroll_by_profile');
            $.when(element).done(function(errmsg) {
              $("#label_notify").html(errmsg);
            });
            
            $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
              $('#label_notify').text('');
              $("#done_alert").slideUp(500);
            });
          }else{
            if(category==2){
              var elements = new Array() ;
              var x = $("#selected_tenant").val();
              var profile = $("#id_profile_field").val();
              var content = $("#id_content").val();

              if(typeof content == 'object'){
                var la_content = '';
                la_content += '[{content:';
                for (var i = 0; i <= content.length-1; i++) {
                        la_content += content[i]+',';
                }
                la_content += '}]';
                content = la_content;
              }

              profile = JSON.stringify(conditions);
              content = statement;


              if(x){
                elements.push(x);
                if($('#id_editrule').val()){
                  set_data(elements,category, profile ,content,$('#id_editrule').val(),rulename,namerule);
                }else if(!$('#id_editrule').val()){
                  var rule;
                  rule = 0;
                  set_data(elements,category, profile ,content,rule,rulename,namerule);
                }else{
                  $('#alert_content').empty();
                  $('#alert_content').html(M.util.get_string('error'+category,'local_enroll_by_profile'));
                  $('#alert_content').parent('.alert').removeClass('out');
                  $('#alert_content').parent('.alert').addClass('in');
                  $('#alert_content').parent('.alert').slideUp(500, function(){
                      $("#done_alert").slideUp(500);});
                  $("#wait").css("display", "none");
                  return false ;
                }
              }
              $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                  $("#done_alert").slideUp(500);
              });
            }
          }

          var request = {
            methodname: 'local_enroll_by_profile_action_count',
            args: {}
          };

          Ajax.call([request])[0].done(function(response) {
            var data = JSON.stringify(response);
            $("#id_rulescount").val(data);
          }).fail(function(ex) {
            console.log('error is ' + ex);
          }); 
          $("#wait").css("display", "none");
          setTimeout(function(){
            dropdown_hide_show();
          },500);

        });


        var request = {
          methodname: 'local_enroll_by_profile_action_count',
          args: {}
        };

        Ajax.call([request])[0].done(function(response) {
          var data = JSON.stringify(response);
          $("#id_rulescount").val(data);
        }).fail(function(ex) {
          console.log('error is ' + ex);
        });                   
        $("#wait").css("display", "none");
            
        $('#id_elements_content').on('click', 'label.form-checkbox', function() {
          if($(this).hasClass('active'))
          {
            $(this).removeClass('active');
            $(this).children('input').prop('checked', false);
          }else
          {
            $(this).addClass('active');
            $(this).children('input').prop('checked', true);
          }
        });

          function set_data(la_elements,cat,profile,content_value,rule,rulename,namerule)
          {
            var request = {
              methodname: 'local_enroll_by_profile_save_category',
              args: {category: cat ,elements: la_elements , prof_field: profile , content: content_value ,rid: rule,rulename: rulename,name: namerule}
            };

            Ajax.call([request])[0].done(function(response) {
              $('#enroll_by_profile_modal').modal('toggle');
               init_modal();
               $("#rules_table").load(window.location + " #rules_table");
            }).fail(function(ex) {
              console.log('error is ' + ex);
            }); 

          }

          /*
          * @author VaibhavG
          * @since 11th Feb 2021
          * @desc 509 Rules Engine issues fixes.
          */
          $('#rules_table').on('click', 'a.disable_rule', function(e) { 
            e.preventDefault();
            var ruleid = 0 ;
            ruleid = $(this).data('ruleid');
            e.preventDefault();
            var disable_rule_msg = "Are you sure, you want to disable this rule ?";
            var result = specialconfirm(disable_rule_msg, disabletherule );
            function disabletherule(result){                               
              if(result){

                var request = {
                  methodname: 'local_enroll_by_profile_disable_rule',
                  args: {rid: ruleid}
                };

                Ajax.call([request])[0].done(function(response) {
                  var element = str.get_string('ruledisablealert','local_enroll_by_profile');
                  $.when(element).done(function(errmsg) {
                    $("#label_notify").html(errmsg);
                  });
                                        
                  $("#rules_table").load(window.location + " #rules_table");
                  $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                    $('#label_notify').text('');
                    $("#done_alert").slideUp(500);
                  });
                }).fail(function(ex) {
                  console.log('error is ' + ex);
                }); 
                
                location.reload();
              }                          
            }  
          });

          /*
          * @author VaibhavG
          * @since 2nd March 2021
          * @desc 509 Rules Engine issues fixes.
          */
        
          $(document).on('click', 'a.disable_rule_all', function(e) {
            var choise_select = $('#choise_select').val();
            var rule_ids = $('#choise_selected').val();
            var rule_ids1 = new Array();
            $(".selectall:checked").each(function() {
              rule_ids1.push($(this).attr('data-ruleid'));
            });
            var ids=rule_ids1.length;
            if(ids==0){
              $(".error_notification").html(M.util.get_string('no_rule_selected','local_enroll_by_profile'));
              $('.error_notification').addClass("alert alert-danger");
            }else{
              $(".error_notification").html("");
              $('.error_notification').removeClass("alert alert-danger");
              e.preventDefault();
              var disable_single_rule_msg = M.util.get_string('confirm_message_disable_selected_rule','local_enroll_by_profile');
              var result = specialconfirm(disable_single_rule_msg, disabletheruleall );
              function disabletheruleall(result){                               
                if(result){

                  var request = {
                    methodname: 'local_enroll_by_profile_disable_all_rule',
                    args: {allselect: choise_select ,selectedrule: rule_ids}
                  };

                  Ajax.call([request])[0].done(function(response) {
                    var disablelength = $('.ruledisable').length;
                    if(parseInt(disablelength) == 0){
                      var element = str.get_string('ruledisablealert','local_enroll_by_profile');
                    }else{
                      var element = str.get_string('ruleenablealert','local_enroll_by_profile');
                    }                           
                    
                    $.when(element).done(function(errmsg) {
                      $("#label_notify").html(errmsg);
                    });
                                          
                   
                    $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                      $('#label_notify').text('');
                      $("#done_alert").slideUp(500);
                    });
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });                   
                  location.reload();
                }else{
                                        
                } 

              }  
            }
          });

          /*
          * @author VaibhavG
          * @since 11th Feb 2021
          * @desc 509 Rules Engine issues fixes.
          */
          $('#rules_table').on('click', 'a.enable_rule', function(e) {
            e.preventDefault();
            var ruleid = 0 ;
            ruleid = $(this).data('ruleid');
            e.preventDefault();
            var enable_rule_msg = "Are you sure you want to enable this rule ?";
            var result = specialconfirm(enable_rule_msg, enabletherule );
            function enabletherule(result){                               
              if(result){

                var request = {
                  methodname: 'local_enroll_by_profile_enable_rule',
                  args: {rid: ruleid}
                };

                Ajax.call([request])[0].done(function(response) {
                  var element = str.get_string('ruleenablealert','local_enroll_by_profile');
                  $.when(element).done(function(errmsg) {
                    $("#label_notify").html(errmsg);
                  });
                                        
                  $("#rules_table").load(window.location + " #rules_table");
                  $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                    $('#label_notify').text('');
                    $("#done_alert").slideUp(500);
                  });
                }).fail(function(ex) {
                  console.log('error is ' + ex);
                });
                location.reload();
              }                          
            }  
          });
            
          $('#rules_table').on('click', 'a.delete_rule', function(e) {
            e.preventDefault();
            var ruleid = 0 ;
            ruleid = $(this).data('ruleid');
            e.preventDefault();
            var result = specialconfirm(M.util.get_string('confirm_message_delete_rule','local_enroll_by_profile'),deletetherule );

            function deletetherule(result){                            
              if(result){
                $("#wait").css("display", "block");

                var request = {
                  methodname: 'local_enroll_by_profile_delete_rule',
                  args: {rid: ruleid}
                };

                Ajax.call([request])[0].done(function(response) {
                  var element = str.get_string('deleterecord','local_enroll_by_profile');
                  $.when(element).done(function(errmsg) {
                    $("#label_notify").html(errmsg);
                  });
                                        
                  $("#rules_table").load(window.location + " #rules_table");
                  $("#done_alert").fadeTo(2000, 500).slideUp(500, function(){
                    $('#label_notify').text('');
                    $("#done_alert").slideUp(500);
                  }); 

                  var requestdata = {
                    methodname: 'local_enroll_by_profile_action_count',
                    args: {}
                  };

                  Ajax.call([requestdata])[0].done(function(responsedata) {
                    var data = JSON.stringify(responsedata);
                    $("#id_rulescount").val(data);
                  }).fail(function(ex) {
                    console.log('error is ' + ex);
                  });
                }).fail(function(ex) {
                  console.log('error is ' + ex);
                });
                
                location.reload();
              }
                
              var rowCount = $('#id_rulescount').val();
              var perpage = 10;
              var sPageURL = window.location.search.substring(1),
                  sURLVariables = sPageURL.split('&'),
                  sParameterName,
                  i;

              for (i = 0; i < sURLVariables.length; i++) {
                  sParameterName = sURLVariables[i].split('=');

                  if (sParameterName[0] === page) {
                    sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                  }
              }
              if((perpage == 1 || (rowCount % perpage == 0)) && (sParameterName[1] != 0))
              {
                var mypage = location.href.replace("page="+sParameterName[1], "page="+(sParameterName[1] - 1));
                window.location = mypage;
              }                          
              $("#wait").css("display", "none");
            }  
          });


          $('#rules_table').on('click', 'a.edit_rule', function(e) {
            e.preventDefault();
            var ruleid = $(this).data('ruletoedit');
            $(".modal-title").text('Edit A rule');

            var request = {
              methodname: 'local_enroll_by_profile_edit_rule',
              args: {rid: ruleid}
            };

            Ajax.call([request])[0].done(function(response) {
              var res = response['table'];
              var fieldtype = response['fieldtype'];
              var htmlinput = response['html'];
              var conditions = res[0]['profile_field'];
              var category = res[0]['category'];
              var selected_elements = res[0]['selected_elements'];
              
              render_conditions_list(JSON.parse(conditions));
              render_rule_statement(JSON.parse(conditions));
              
              $('.btn-delete').off().on('click',delete_conditional);
              $('#id_content').removeAttr("readonly");
              $('#id_category_type').attr("disabled",false);

              $('#id_editrule').val(ruleid);
              var namerule = res[0]['name'];
              
              $('#id_name_field1').val(namerule);

              get_content_title(fieldtype);
              $('#id_content_title').show();


              $('#id_category_type').val(category);

              var requestdata = {
                methodname: 'local_enroll_by_profile_get_category',
                args: {category: category , selected: selected_elements}
              };

              if(category == 1){
                templatename = 'get_courses';
              }else if(category == 2){
                templatename = 'get_tenant';
              }else if(category == 3){
                templatename = 'get_lp';
              }else if(category == 4){
                templatename = 'get_cohort';
              }else if(category == 5){
                templatename = 'get_role';
              }else if(category == 6){
                templatename = 'get_learning_plans';
              }

              Ajax.call([requestdata])[0].done(function(responsedata) {
                Templates.render('local_enroll_by_profile/'+templatename, responsedata)
                  .done(function(html) {
                    var namerule = res[0]['name'];
                    $('#id_name_field1').val(namerule);
                    $('#id_elements_content').empty();
                    $('#id_elements_content').html(html);
                  });
                $('#id_elements_content').empty();
                $('#id_elements_content').html(responsedata);
              }).fail(function(ex) {
                console.log('error is ' + ex);
              });
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
            
          });

          $("input[name=negated]").off().on("click", negate_condition);

          $('.btn-add-cond').on('click', function(){
            var id = get_conditional_id();

            var request = {
              methodname: 'local_enroll_by_profile_addcond',
              args: {condid: id}
            };

            Ajax.call([request])[0].done(function(response) {
              Templates.render('local_enroll_by_profile/newcondition_form', response)
              .done(function(html) {
                $('.conditions-view').append(response.rowdata.boolop_html);
                $('.conditions-view').append(html);
                $(".btn-delete").off().on("click",delete_conditional);
                $("#id_conditions_num").val(id);
                $(".boolopstch").off("click").on("click",booloptoggle);
                $('.profile-field').off().on('change', get_cond_html);
                $("input[name=negated]").off().on("click", negate_condition);
                remove_boolop_first_cond();
                conditions = load_conditions_from_list();
                render_rule_statement(conditions);
              });
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
            
          });


          $('.titleenrollbyprofile .add').click(function (e){
            $('#alert_content').empty();                      
            $('#id_content_title').empty();
            $('#id_profile_field').val('0');
            $('#id_category_type').val('0');
            $('#id_elements_content').empty();
            $('.conditions-list').empty();
            $('.rule_statement').empty();
            $('.boolop').empty();
            $('#id_category_type').attr('disabled','disabled');
          });

          $('.modal-content .close ,.modal-content .close_lms_mod').click(dont_close_modal);
          
          /*   
          * @desc function to give Confirm message before closing the modal 
          */ 
          function dont_close_modal(e){
            e.preventDefault();
             $("#wait").css("display", "none");
             
              specialconfirm("are you sure you want to discard changes?",function(result){
                if(result){
                  init_modal();
                  $(".modal-title").text('Add A rule');
                  $("#enroll_by_profile_modal").modal('hide');
                  return true;

                }else{
                  return true;
                }
                return false;
              });
              return false;
          }

          $(document).on('change','.profile-field',get_cond_html);
          $('.profile-field').on('change', get_cond_html);

          $(".boolopstch").on("click",booloptoggle);

          /*   
          * @desc function to get conditional field html and data in add rules  on change of profile field
          */
          function get_cond_html(e){
            var ftype = $('option:selected', this).attr('data-type');
            var fshortname = $('option:selected', this).val();
            var id = $(this).attr("data-id");
            $(".condition-item[data-id="+id+"]").attr('data-field',fshortname);

            var request = {
              methodname: 'local_enroll_by_profile_conditional_html',
              args: {fieldselected: "yes" , fieldsn: fshortname, fieldtype: ftype, condid:id}
            };

            Ajax.call([request])[0].done(function(response) {
              Templates.render('local_enroll_by_profile/get_conditional_html', response)
              .done(function(html) {
                  $('#content_conditional_element'+id+'').empty();
                  $('#id_category_type').attr("disabled",false);

                  $('#content_conditional_element'+id+'').html(html);

                  $('.datepicker').datepicker({
                    format          : "m/d/yyyy",
                    autoclose       : true,
                    todayHighlight  : true
                  });
                  $('.select-conditional').on('change', get_input_html);
                  conditions = load_conditions_from_list();
                  render_rule_statement(conditions);
              });
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
            $('.select-conditional').on('change', get_input_html);
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }

          function booloptoggle(e){
            var self = $(e.currentTarget);
            var id = self.parent().attr("data-id");
            var boolop = "";
            var conditions = $(".condition-item[data-id="+id+"]");
            var condition = $(conditions[0]);

            if(condition.attr("data-boolop") === "AND"){
              boolop = "OR";
            }else if (condition.attr("data-boolop") === "OR") {
              boolop = "AND";
            }else{
              boolop = "";
            }
            
            condition.attr("data-boolop", boolop);
            self.text(boolop);
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }


          /*   
          * @desc function to get conditional id
          */
          function get_conditional_id(){
            cont = parseInt($("#id_conditions_num").val()) || 1;
            cont++;
            return cont;
          }

          function delete_conditional(e){
            var self = $(e.currentTarget);
            var id = self.parent().parent().attr("data-id");
            $("[data-id="+id+"]").remove();
            remove_boolop_first_cond();
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }

          /*   
          * @desc function to get input value html in add rules form
          */
          function get_input_html(e){
            var self = $(e.currentTarget);
            var id = self.parent().parent().attr("data-id");
            var ftype = $('option:selected', '#id_profile_field'+id).attr('data-type');
            var fshortname = $('option:selected', '#id_profile_field'+id).val();
            var conditional = $('option:selected', e.currentTarget).val();
            var statement = $('option:selected', e.currentTarget).text();
            $(".condition-item[data-id="+id+"]").attr('data-rule',conditional);
            $(".condition-item[data-id="+id+"]").attr('data-statement',statement);

            var request = {
              methodname: 'local_enroll_by_profile_input_html',
              args: {conditional: conditional, fieldsn: fshortname, fieldtype: ftype, condid:id}
            };

            Ajax.call([request])[0].done(function(response) {
              
              if((conditional == 'contains') || (conditional == 'beginswith') || (conditional == 'endswith') || (conditional == 'exactmatch')){
                templatename = 'condition_contains';
              }else if((conditional == 'greaterthan') || (conditional == 'lessorequalthan') || (conditional == 'greaterorequalthan') || (conditional == 'lessthan') || (conditional == 'equalsto')){
                templatename = 'condition_greaterthan';
              }else if(conditional == 'insideinterval'){
                templatename = 'condition_insideinterval';
              }else if(conditional == 'isbetween'){
                templatename = 'condition_isbetween';
              }else if(conditional == 'isbefore'){
                templatename = 'condition_isbefore';
              }else if(conditional == 'isafter'){
                templatename = 'condition_isafter';
              }else if(conditional == 'itson'){
                templatename = 'condition_itson';
              }else if(conditional == 'isselected'){
                templatename = 'condition_isselected';
              }else if((conditional == 'hasanyselected') || (conditional == 'hasselected')){
                templatename = 'condition_hasanyselected';
              }else if(conditional == 'menu'){
                templatename = 'condition_menu';
              }else if((conditional == 'ischecked') || (conditional == 'isnotchecked') || (conditional == 'isempty')){
                templatename = 'multiple_conditions';
              }else{
                templatename = '';
              }

              if(templatename != '' && templatename != null){
                Templates.render('local_enroll_by_profile/'+templatename, response)
                  .done(function(html) {
                      $('#content_input_element'+id).empty();
                      $('#content_input_element'+id).html(html);

                      if(templatename == 'condition_isselected'){
                        $(".condition-item[data-id="+id+"]").attr('data-value','["'+$("#id_content"+id).val()+'"]');
                      }
                      get_conditional_buttons(id);
                      $('.content-value').off().on('keyup', update_value);
                      $("input[name=negated]").off().on("click", negate_condition);

                      $('.datepicker').datepicker({
                        format          : "m/d/yyyy",
                        autoclose       : true,
                        todayHighlight  : true
                      });
                  });
              }
              get_conditional_buttons(id);
              $('.content-value').off().on('keyup', update_value);
              $("input[name=negated]").off().on("click", negate_condition);

              $('.datepicker').datepicker({
                format          : "m/d/yyyy",
                autoclose       : true,
                todayHighlight  : true
              });
              
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
            
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }

          /*   
          * @desc function to get conditional buttons like NOT and Delete icon
          */
          function get_conditional_buttons(id)
          {
            var request = {
              methodname: 'local_enroll_by_profile_conditional_buttons',
              args: {buttons: "true", condid:id}
            };

            Ajax.call([request])[0].done(function(response) {
              Templates.render('local_enroll_by_profile/action_buttons', response)
                .done(function(html) {
                  $('#conditional_actions').html(html);
                  $('.btn-add-condition').on('click',add_condition_to_list);
                });
                $('#conditional_actions').html(response.responseText);
                $('.btn-add-condition').on('click',add_condition_to_list);
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
          }

          /*   
          * @desc function to stop deleting first condition, when only 1 condition is present else we can remove
          */
          function remove_boolop_first_cond()
          {
            var firstitem = $(".conditions-view").children(":first");
            var id = firstitem.attr('data-id');
            if(firstitem.children('.boolopstch').length > 0 ){
              firstitem.remove();
              $(".condition-item[data-id="+id+"]").attr("data-boolop","");
              $(".condition-item[data-id="+id+"]").attr("data-text","");
            }else {
              check = false;

            }
          }

          /*   
          * @desc function to boolop dropdown values
          */
          function load_boolop_select(){
            var request = {
              methodname: 'local_enroll_by_profile_bool_opdropdown',
              args: {dropdown: "true"}
            };

            Ajax.call([request])[0].done(function(response) {
              Templates.render('local_enroll_by_profile/get_bool_opdropdown', response)
                .done(function(html) {
                  conditions = load_conditions_from_list();

                  if(conditions.length > 0){
                    $(".boolop").html(response.responseText);
                  }else{
                    $(".boolop").html("");

                  }
                });

               conditions = load_conditions_from_list();

                if(conditions.length > 0){
                  $(".boolop").html(response.responseText);
                }else{
                  $(".boolop").html("");

                }
            }).fail(function(ex) {
              console.log('error is ' + ex);
            });
                             
          }

          /*   
          * @desc function to get neagted condition if present
          */
          function negate_condition(e){
            var self = $(e.currentTarget);
            var id = self.parent().parent().attr("data-id");
            var condition = $(".condition-item[data-id="+id+"]");
            if(self.is(":checked")){
              condition.attr("data-negated",true);
            }else {
              condition.attr("data-negated",false);
            }
            conditions = load_conditions_from_list();
            render_rule_statement(conditions);
          }

          /*   
          * @desc function to create rule statement
          */
          function create_statement_from_conditions(conditions){
            var str = "";
            var counter = 0;
            var condrule = "";
            conditions.forEach(function(condition){
              if(counter !== 0){
                if(condition.boolop === "AND"){
                  str += " AND ";
                }else{
                  str += " OR ";
                }
              }

              counter++;

              if(condition.negated == true || condition.negated == "true"){
                condrule = condition.negatedrule;
              }else{
                condrule = condition.rule;
              }
              switch (condrule) {
                case "contains":

                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " LIKE '%"+condition.value+"%' ";
                  str += ")";
                  break;

                case "beginswith":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " LIKE '"+condition.value+"%' ";
                  str += ")";
                  break;

                case "endswith":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " LIKE '%"+condition.value+"' ";
                  str += ")";
                  break;

                case "isempty":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " =  '' ";
                  str += ")";
                  break;
                case "exactmatch":
                case "equalsto":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = '"+condition.value+"'";
                  str += ")";
                  break;

                case "notlessthan":
                case "greaterorequalthan":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " >= '"+condition.value+"'";
                  str += ")";
                  break;

                case "notlessorequalthan":
                case "greaterthan":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " > '"+condition.value+"'";
                  str += ")";
                  break;

                case "notgreaterthan":
                case "lessorequalthan":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " <= '"+condition.value+"'";
                  str += ")";
                  break;

                case "notgreaterorequalthan":
                case "lessthan":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " < '"+condition.value+"'";
                  str += ")";
                  break;

                case "isbefore":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " < date('"+condition.value+"')";
                  str += ")";
                  break;

                case "isafter":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " > date('"+condition.value+"')";
                  str += ")";
                  break;

                case "isbetween":
                str += "(";
                str += "`"+condition.field+"`";
                str += " > date('"+condition.value[0]+"')";
                str += " AND `"+condition.field+"`";
                str += " < date('"+condition.value[1]+"')";
                str += ")";
                break;

                case "itson":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = date('"+condition.value+"')";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 11th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "ischecked":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 11th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "isnotchecked":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 11th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "isselected":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = '"+condition.value+"'";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 11th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "notisselected":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " != '"+condition.value+"'";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 11th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "hasselected":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = '"+condition.value+"'";
                  str += ")";
                  break;

                /*
                * @author VaibhavG
                * @since 26th Feb 2021
                * @desc 509 Rules Engine issues fixes.
                */
                case "hasanyselected":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = '"+condition.value+"'";
                  str += ")";
                  break;

                case "notcontains":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " NOT LIKE '%"+condition.value+"%'";
                  str += ")";
                  break;

                case "notbeginswith":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " NOT LIKE '"+condition.value+"%'";
                  str += ")";
                  break;

                case "notendswith":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " = '"+condition.value+"'";
                  str += ")";
                  break;

                case "notisempty":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " != ''";
                  str += ")";
                  break;

                case "notequalsto":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " != '"+condition.value+"'";
                  str += ")";
                  break;

                case "notisbefore":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " >= date('"+condition.value+"')";
                  str += ")";
                  break;

                case "notisafter":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " >= date('"+condition.value+"')";
                  str += ")";
                  break;

                case "notisbetween":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " < date('"+condition.value[0]+"')";
                  str += " AND `"+condition.field+"`";
                  str += " > date('"+condition.value[1]+"')";
                  str += ")";
                  break;

                case "notitson":
                  str += "(";
                  str += "`"+condition.field+"`";
                  str += " != date('"+condition.value+"')";
                  str += ")";
                  break;

                case "nothasselected":

                  break;
                default:

              }

            });
            return str;
          }

          /*   
          * @desc function to get all conditions present in form and make a rule statement
          */
          function load_conditions_from_list(){
            var conditions = [];
            $(".condition-item").each(function(){
              condition = {};
              condition.id = $(this).attr("data-id");
              condition.field = $(this).attr("data-field");
              condition.boolop = $(this).attr("data-boolop");
              condition.value = JSON.parse($(this).attr("data-value"));
              condition.rule = $(this).attr("data-rule");
              condition.negatedrule = $(this).attr("data-negatedrule");
              condition.negatedstatement = $(this).attr("data-negatedstatement");
              condition.statement = $(this).attr("data-statement");
              condition.negated = $(this).attr("data-negated");
              condition.text = condition_create_text(condition);

              conditions.push(condition);

            });
           return conditions;
          }

          function condition_create_text(condition){
            /*
            * @author VaibhavG
            * @since 11th Feb 2021
            * @desc 509 Rules Engine issues fixes.
            */
            if(condition.rule === "ischecked")
              condition.value[0] = "";
            if(condition.rule === "isnotchecked")
              condition.value[0] = "";

            text = "<strong>"+condition.boolop+"</strong>";
            text += "<em> "+condition.field+"</em><strong> ";
            if(condition.negated == true || condition.negated == "true"){

              text += condition.negatedstatement+"</strong> <em>";
            }else{
              text += condition.statement+"</strong> <em>";

            }

            if(condition.rule === "isempty"){
              text += "</em>";
            }else{
              if(condition.value.length > 1){
                text += condition.value[0]+" and "+condition.value[1]+"</em>";
              }else {
                text += condition.value[0]+"</em>";
              }
            }
            return text;
          }

          /*   
          * @desc function to add new conditions to list in rules form
          */
          function add_condition_to_list(){
            conditions = load_conditions_from_list();
            condition = {};
            condition.id = get_conditional_id();
            condition.boolop = $('option:selected', "#id_boolop").text() || "";
            condition.field = $('option:selected', "#id_profile_field").val();
            condition.value = $(".content-select").val();
            if($(".end_date").val()){
              condition.value = [
                $(".start_date").val(),
                $(".end_date").val()
              ];
            }else if ($(".start_date").val()) {
              condition.value = $(".start_date").val();
            }else if($(".content-value").val()){
              condition.value = $(".content-value").val();
            }else if ($(".content-select").val()) {
              condition.value = $(".content-select").val();
            }else {
              condition.value = "";
            }

            condition.negated = $("#id_negated").is(":checked");
            condition.rule = $("#id_conditional").val();
            condition.negatedstatement = $("#id_negatedrule").val();
            condition.negatedrule = $("#id_negatedrule").attr("name");
            condition.statement = $('option:selected', "#id_conditional").text();
            condition.text = condition_create_text(condition);
            conditions.push(condition);
            $("#id_conditions_num").val(condition.id);              
           
            render_conditions_list(conditions);
            render_rule_statement(conditions);
            load_boolop_select();
            $('#content_conditional_type').html(html_type_default);
            $('#content_input_element').html(html_input_default);
            $('#content_conditional_element').html(html_cond_default);
            $('#conditional_actions').empty();
            $('#id_profile_field').val('0');
            $('.btn-delete').on('click',delete_conditional);
          }

          /*   
          * @desc function to show conditions list present in edit rules form
          */
          function render_conditions_list(conditions){
            $('.conditions-view').html("");
            
            $(conditions).each(function(index,condition){
              var id=condition.id;
              var params = JSON.stringify(condition);

              var request_editcond = {
                methodname: 'local_enroll_by_profile_addcond',
                args: {condid: id , params:params}
              };

              Ajax.call([request_editcond])[0].done(function(response_editcond) {
                Templates.render('local_enroll_by_profile/newcondition_form', response_editcond)
                  .done(function(html) {
                      $('.conditions-view').append(html);
                      $('.profile-field[data-id='+id+']').val(condition.field);
                      var ftype = $('option:selected', '#id_profile_field'+id).attr('data-type');

                      var request_conditional_html = {
                        methodname: 'local_enroll_by_profile_conditional_html',
                        args: {fieldselected: "yes" , fieldsn: condition.field, fieldtype: ftype, condid:id}
                      };
                      
                      Ajax.call([request_conditional_html])[0].done(function(response_conditional_html) {
                        Templates.render('local_enroll_by_profile/get_conditional_html', response_conditional_html)
                          .done(function(html) {
                              $('#content_conditional_element'+id+'').empty();
                              $('#content_conditional_element'+id+'').html(html);

                              var request_input_html = {
                                methodname: 'local_enroll_by_profile_input_html',
                                args: {conditional: condition.rule, fieldsn: condition.field, fieldtype: ftype, condid:id}
                              };
                              
                              Ajax.call([request_input_html])[0].done(function(response_input_html) {
                                if((condition.rule == 'contains') || (condition.rule == 'beginswith') || (condition.rule == 'endswith') || (condition.rule == 'exactmatch')){
                                  templatename = 'condition_contains';
                                }else if((condition.rule == 'greaterthan') || (condition.rule == 'lessorequalthan') || (condition.rule == 'greaterorequalthan') || (condition.rule == 'lessthan') || (condition.rule == 'equalsto')){
                                  templatename = 'condition_greaterthan';
                                }else if(condition.rule == 'insideinterval'){
                                  templatename = 'condition_insideinterval';
                                }else if(condition.rule == 'isselected'){
                                  templatename = 'condition_isselected';
                                }else if((condition.rule == 'hasanyselected') || (condition.rule == 'hasselected')){
                                  templatename = 'condition_hasanyselected';
                                }else if(condition.rule == 'menu'){
                                  templatename = 'condition_menu';
                                }else if((condition.rule == 'ischecked') || (condition.rule == 'isnotchecked') || (condition.rule == 'isempty')){
                                  templatename = 'multiple_conditions';
                                }else if(condition.rule == 'isbetween'){
                                  templatename = 'condition_isbetween';
                                }else if(condition.rule == 'isbefore'){
                                  templatename = 'condition_isbefore';
                                }else if(condition.rule == 'isafter'){
                                  templatename = 'condition_isafter';
                                }else if(condition.rule == 'itson'){
                                  templatename = 'condition_itson';
                                }else{
                                  templatename = '';
                                }
                                
                                if(templatename != '' || templatename != null){
                                  Templates.render('local_enroll_by_profile/'+templatename, response_input_html)
                                    .done(function(html) {
                                      
                                        $('#content_input_element'+id).empty();
                                        $('#content_input_element'+id).html(html);
                                        
                                        $('#id_conditional'+id+'').val(condition.rule);
                                        if(templatename == 'condition_isselected'){
                                          $(".condition-item[data-id="+id+"]").attr('data-value','["'+$("#id_content"+id).val()+'"]');
                                        }
                                        values = condition.value;

                                        $(".btn-delete").off().on("click",delete_conditional);
                                        $("#id_conditions_num").val(id);

                                        $('.profile-field').off().on('change', get_cond_html);
                                        $('.select-conditional').on('change', get_input_html);

                                        remove_boolop_first_cond();

                                        $(".boolopstch").off("click").on("click",booloptoggle);
                                        $("input[name=negated]").off().on("click", negate_condition);

                                        $('.content-value').off().on('keyup', update_value);
                                        
                                        if(values.length > 1){
                                          $('#id_content'+id+'').val(values[0]);
                                          $('#id_subcontent'+id+'').val(values[1]);
                                        }else{
                                          $('#id_content'+id+'').val(values[0]);
                                        }

                                        $('.datepicker').datepicker({
                                          format          : "m/d/yyyy",
                                          autoclose       : true,
                                          todayHighlight  : true
                                        });
                                    });
                                }else{
                                  $('#content_input_element'+id).empty();
                                  $('#content_input_element'+id).html(jqXHR.responseText);
                                }
                                values = condition.value;

                                $(".btn-delete").off().on("click",delete_conditional);
                                $("#id_conditions_num").val(id);

                                $('.profile-field').off().on('change', get_cond_html);
                                $('.select-conditional').on('change', get_input_html);

                                remove_boolop_first_cond();

                                $(".boolopstch").off("click").on("click",booloptoggle);
                                $("input[name=negated]").off().on("click", negate_condition);

                                $('.content-value').off().on('keyup', update_value);
                                
                                if(values.length > 1){
                                  $('#id_content'+id+'').val(values[0]);
                                  $('#id_subcontent'+id+'').val(values[1]);
                                }else{
                                  $('#id_content'+id+'').val(values[0]);
                                }

                                $('.datepicker').datepicker({
                                  format          : "m/d/yyyy",
                                  autoclose       : true,
                                  todayHighlight  : true
                                });
                                 
                              }).fail(function(ex) {
                                console.log('error is ' + ex);
                              });
                          });

                         
                      }).fail(function(ex) {
                        console.log('error is ' + ex);
                      });
                  });

                 
              }).fail(function(ex) {
                console.log('error is ' + ex);
              });

              
            });
          }

          /*   
          * @desc function to show rule statement
          */
          function render_rule_statement(conditions){
            var html = "";
            
            $(conditions).each(function(index,condition){
              html +='<span class="muted-text" data-id="'+condition.id+'">'+condition_create_text(condition)+'</span>&nbsp;&nbsp;';
            });
            $(".rule_statement").html(html);
          }

          function get_content_title(fieldtype)
          {
            switch(fieldtype)
            {
              case 'datetime':
                      $('#id_content_title').html(M.util.get_string('content_title_between','local_enroll_by_profile'));
                      break;
              case 'menu':
              case 'checkbox':
                      $('#id_content_title').html(M.util.get_string('content_title_equal_to','local_enroll_by_profile'));
                      break;
              case 'text':
              case 'textarea':
              case 'multiselectlist':
              case 'multiselect':
                      $('#id_content_title').html( M.util.get_string('content_title_contain','local_enroll_by_profile') ) ;
                      break;
            }

          }

          $('.add-content-link').click(function()
          {
            init_modal();
            $('#id_category_type').attr('disabled','disabled');
          });
          /**
          * Dropdown show only if courses is available 
          * @author Sanyogita D.
          * @since 31-08-2021
          * @ticket #990
          */
          dropdown_hide_show();
          function dropdown_hide_show(){
              $('body#page-local-enroll_by_profile-index tr').each(function() {
                    var id=$(this).find('.collapse').attr('id');
                    var matched = $("#"+id+" a");
                    if(matched.length>0){
                      $(this).find('i').addClass("showicon");
                    }else{
                      $(this).find('i').addClass("hideicon");

                    }
              });
         }
            

        });
      }
    }
  });


