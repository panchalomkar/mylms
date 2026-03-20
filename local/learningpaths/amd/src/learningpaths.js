define(['jquery', 'core/str', 'jqueryui','local_learningpaths/bootbox','core/ajax','core/notification'], function($, str, ju, bootbox,ajax, notification) {
	return {
        lpactions: function() {
            var translation = str.get_strings([
                            
                {key: 'confirm_delete', component: 'local_learningpaths'},
                {key: 'users_delete_success', component: 'local_learningpaths'},
                {key: 'cohorts_delete_success', component: 'local_learningpaths'},
                {key: 'selectusers', component: 'local_learningpaths'},
                {key: 'delete_success', component: 'local_learningpaths'},
                {key: 'selectcohort', component: 'local_learningpaths'}
            ]);

            var refresh_courses = 0;        
            prerequisites_drag_and_drop();
            save_courses_positions();
            add_required_switch();
            learningpaths_pagination();
            searchers();
            removeCourseAction();

            // Select all elements of a list.
            check_all('course-all', 'course-learninpath');
            check_all('id_all_cohorts', 'learningpath-cohort');
            check_all('selec_allcohorts', 'cohort-learninpath');
            check_all('id_all_users', 'learningpath-user');
            

            if (!get_url_param('coursename')) {
                courses_drag_and_drop();
            }

            $('.learningpath-user').click(function() {
                var usersselected = new Array();
                $('.learningpath-user').each(function() {
                    if($(this)[0].checked)
                    {
                        usersselected.push( $(this)[0].dataset.userid ); 
                    }
                });
                if(usersselected.length > 0) {
                    $('#learningpath-remove-users').attr('style','background-color:#77b300;cursor:pointer;visibility:visible;display:initial;');
                    } else {
                    $('#learningpath-remove-users').attr('style','background-color:#FFFFFF');
                    $('#page-local-learningpaths-view div.select-add div.button-delete a.delete-btn').attr('style','display:none;visibility:hidden;');


                }
            });

            $("#learningpath-remove-users").unbind().click(function(event) {
           // $('#learningpath-remove-users').click(function(event) {
                event.preventDefault();
                $('#lpstatus > div.alert').removeAttr('style').html('');
                var users = [];
                $("#lpstatus").hide();
                $('.learningpath-user:checked').each(function() {
                    users.push($(this).data('userid'));
                });

                var usersselect = M.util.get_string('selectusers', 'local_learningpaths');
                // Check if users are selected
                if( users.length <= 0 ) {
                    $("#lpstatus").show().removeClass('hidden').children().addClass('alert-warning').html(usersselect).fadeOut(5000);
                    return false;
                }
                bootbox.confirm(M.util.get_string('confirm_delete', 'local_learningpaths'), function(result) {
                    if(result) {
                        var parameters = new Object();
                        parameters.users = users;
                        parameters.action = 'remove-users';

                        learningpath_ajax_request(parameters, function(data) {
                            $.each(users, function( index, value ) {
                                $("#table_users tr#user-"+value).remove();
                                var userdelete = M.util.get_string('users_delete_success', 'local_learningpaths');
                                $("#lpstatus").show().removeClass('hidden').children().addClass('alert-danger').html(userdelete).fadeOut(3000);
                            });
                            location.reload(); 
                            //setTimeout(function() { window.location.reload(true); }, 3000);
                        }, function(error) {
                            console.log(error);
                        });
                    }
                });
            });


            $("#learningpath-remove-cohorts").unbind().click(function(event) {
          //  $('#learningpath-remove-cohorts').click(function(event) {
                $('#cohortst').hide().children().removeAttr('style').html('');
                var cohorts = [];
                $('.learningpath-cohort:checked').each(function() {
                    cohorts.push($(this).data('cohortid'));
                })

                var cohortselect = M.util.get_string('selectcohort', 'local_learningpaths');
                // Check if users are selected
                if(cohorts.length <= 0) {
                    $("#cohortst").show().removeClass('hidden').children().addClass('alert-warning').html(cohortselect).fadeOut(5000);
                    return false;
                }
                bootbox.confirm(M.util.get_string('confirm_delete', 'local_learningpaths'), function(result) {
                    if(result) {
                        var parameters = new Object();
                        parameters.cohorts = cohorts;
                        parameters.action = 'remove-cohorts';

                        learningpath_ajax_request( parameters, function( data ) {
                            $.each(cohorts, function( index, value ) {
                                $("#table_cohorts tr#cohort-"+value).remove();
                                var cohortsdelete = M.util.get_string('cohorts_delete_success', 'local_learningpaths');
                                $("#cohortst").show().removeClass('hidden').children().addClass('alert-danger').html(cohortsdelete).fadeOut(5000);
                            }); 
                            setTimeout(function() { window.location.reload(true); }, 3000);
                        }, function(error) {
                            console.log(error);
                        });
                    }
                });
            });

            $('#page-local-learningpaths-view div.check form div.fitem_fcheckbox').removeClass('col-sm-6');
            $('#page-local-learningpaths-view div.check form div.fitem_fcheckbox').addClass('col-sm-2');
            $('#page-local-learningpaths-view div.check form div.fitem_feditor').removeClass('col-sm-6');
            $('#page-local-learningpaths-view div.check form div.fitem_feditor').addClass('col-sm-12');
            $('#page-local-learningpaths-view div.check form div.fitem_ftext').removeClass('col-sm-6');
            $('#page-local-learningpaths-view div.check form div.fitem_ftext').addClass('col-sm-12');
            $('#page-local-learningpaths-view table.mceLayout').addClass('card-box');

            $('a.notifications_enroll').click(function() {
                changes_icon( '#' + $(this)[0].id );
                var actualid = $(this)[0].id;

                if(!$(this).hasClass('collapsed')) {
                    $(this).closest('div[class*="collapse_"]').removeClass('active');
                }else{
                    $(this).closest('div[class*="collapse_"]').addClass('active');
                }
                
                $('a.notifications_enroll').each(function() {
                    if (actualid != $(this)[0].id) {
                        if (!$(this).hasClass('collapsed')) {
                            $(this).addClass('collapsed');
                            $(this).parent().parent().children('.collapse-color.collapse').removeClass('in').addClass('out');
                            $(this).children('i').removeClass('wid wid-icon-up').addClass('wid wid-icon-down');
                            $(this).closest('div[class*="collapse_"]').removeClass('active');
                        }
                    }
                });

            });


            // For learningpaths block embeded.
            $('.lpd-lp-detail-body canvas').each(function() {
                var canvas = this;
                switch ($(this).data('element')) {
                    case 'first':
                        var context = canvas.getContext('2d');
                        context.beginPath();
                        context.moveTo(15, 0);
                        context.lineTo(15,30);
                        context.lineWidth = 2;
                        context.strokeStyle = $(this).parent().children('i').data('color');
                        context.stroke();
                    break;

                    case 'middle':
                        var context = canvas.getContext('2d');
                        context.beginPath();
                        context.moveTo(15, 0);
                        context.lineTo(15,150);
                        context.lineWidth = 2;
                        context.strokeStyle = $(this).parent().children('i').data('color');
                        context.stroke();
                    break;

                    case 'last':
                        var context = canvas.getContext('2d');
                        context.beginPath();
                        context.moveTo(15, 0);
                        context.lineTo(15,150);
                        context.lineWidth = 2;
                        context.strokeStyle = $(this).parent().children('i').data('color');
                        context.stroke();
                    break;
                }
            });

            // Courses tab.
            $('#courses-tab-button').click(function(event) {
                $('.tbs-content ul li a').removeClass('active').attr('aria-expanded','false');
                $('#courses-button a').addClass('active').attr('aria-expanded','true');
                $('.tab-pane').removeClass('active in');
                var target = this.href.split('#');
                $("#"+target[1]).addClass("active in");
            });
            
            $("#courses-button").unbind().click(function(event) {
           // $('#courses-button').click(function(event) {
              //alert('courses tab clicked');
                if( refresh_courses ) {
                    var parameters = new Object();
                    parameters.action = "refresh_courses";
                    parameters.learningpathid = get_url_param('id');
                    // Call ajax function.
                   // alert('hi');
                    learningpath_ajax_request(parameters, function(data) {
                        reload_courses_list(data);
                    }, function(error) {
                        console.log(error);
                    });
                    // learningpath_ajax_request(parameters,0);
                    refresh_courses = 0;

                    //alert('there');

                }
            });
            
            $('a.close').click(function() {
                $('div.contentenrollusers input.users-lpall, input.users_lpall').prop('checked', false);
                $('div#available-courses-list input.course-learninpath').prop('checked', false);
                $('div#available-courses-list .course-lp').removeClass('checkbbg');
                $('#add-courses-search, .add-cohorts-search').val('');

            });

            $('a#add-users-lp').click(function() {
                $('div.contentenrollusers input.users-lpall, input.users_lpall').prop('checked', false);
            });

            // update course active status after LP publish added by ShivkumarY
            //$('#btn-lp_publish').click(function(e){
            $("#btn-lp_publish").unbind().click(function(e) {
                e.preventDefault();
                var confirmation = confirm("Are you sure you want to publish this learningpath?");
                
                var lpid = $("input[name=learningpathid]").val();
                if(confirmation){
                    var requests = ajax.call([{
                      methodname: 'local_learningpath_publishlp',
                      args: {lpid:lpid}
                  }]);
                  requests[0].done(function(response) {
                    $('#btn-lp_publish').prop('disabled', true); // disable button
                    location. reload();

                  }).fail(notification.exception);
                }
            });
            // js/functions.js code
            
          
          function save_courses_positions()
          {
              var save_course_positions = $('#save-course-positions');
              save_course_positions.find("#id_submitbutton").click(function(event){
                  var positions = [];
                  $('#list-course').find('li').each(function() {
                      positions.push($(this).data('id'))
                  })
                  save_course_positions.find('input[name="coursesposition"]').val(positions.toString());
              })
          }
          
          function get( name ){
              var regexS = "[\\?&]"+name+"=([^&#]*)";
              var regex = new RegExp ( regexS );
              var tmpURL = window.location.href;
              var results = regex.exec( tmpURL );
              if( results == null )
                  return"";
              else
                  return results[1];   
          }
          
          function reload_courses_list(data)
          { 
              //console.log(data.course_list_add);
              $('#learningpath-courses-list').html(data.courses_list);
              $("#courses-popup #courses-popup-content").html(data.course_list_add);
              $('#courses-popup-content').html(data.add_courses_form).find('form').show();
              $('#courses-popup-content').html(data.course_list_add).find('form').show();
              //add_course_to_learningpath();
              add_required_switch();
              $('#learningpath-courses-list').find('form').show().find('.btn-cancel, #id_submitbutton').addClass('btn btn-primary btn-round');
          
              // If user is searching a course, then he can't drag and drop it.
              if (!get_url_param('coursename')) {
                  courses_drag_and_drop();
          
              }
              no_backend_searchers();
              prerequisites_drag_and_drop();
              //$('.tooltipelement_html').tooltip({html:true});
              removeCourseAction();
              /*DCarmona*/
              /*Count total courses to refresh the overview and refresh the course list in the overview*/
              var tCourses = $("#list-course li.course-description").length || 0;
              var elementInitial = $("#strTCourses").find("p");
              var elementText = $("#strTCourses p b").html();
              
              $(elementInitial).html("<b>" + elementText + "</b>" + tCourses);
              id = get('id');
              var requests = ajax.call([{
                methodname: 'block_lpd_getlpdetail',
                args: {action: 'getLpDetail', learningPath: id, 'page': '', 'lpid_selected': true}
              }]);
              requests[0].done(function(response) { 
                if(response.data && response.data !== '') {
                  $('#block_lpd_content').empty();
                  $('#block_lpd_content').html(response.data);
                  $('#block_lpd_content .progress-column').hide();
              }
            }).fail(notification.exception);
          }
          
          function removeCourseAction(){
              $(".delete-course-learning-path").unbind().click(function(event) {
              //$('.delete-course-learning-path').click(function(event) {
                  e = this;
                  require(['jquery','local_learningpaths/bootbox'],function($,bootbox){
                  bootbox.confirm(M.util.get_string('lp_confirm_delete', 'local_learningpaths'), function(result){
                      if(result){
                          // Get prerequsites array.
                          var item = $(e).closest(".course-description").attr('data-id');
                          // Parameters to send in ajax request.
                          var parameters = new Object();
                          parameters.action = "remove_course";
                          parameters.item = item;
                          parameters.learningpathid = get_url_param('id');
                          // Call ajax function.
                          learningpath_ajax_request(parameters, function(data) {
                              reload_courses_list(data);

                          }, function(error) {
                              console.log(error);
                              });
                          }
                          setTimeout(function() { window.location.reload(true); }, 1000);
                      });
                   });
               });
           }
          
          function add_required_switch()
          {
              
              // Convert checkbox to swiches.
              var elems = Array.prototype.slice.call(document.querySelectorAll('.tooglebutton'));
              elems.forEach(function(html) {
                  var switchery = new Switchery(html, {
                      color : '#64bd63', secondaryColor : '#dfdfdf', className : 'js-course-switch'
                  });
              });
          
              // When a switch be clicked execute ajax function.
              $('.course-switch').change(function(event) {
                  var parameters = new Object();
                  parameters.action = "update-required";
                  parameters.courseid = $(this).data('courseid');
                  parameters.required = ($(this).is(':checked')) ? 1 : 0;
                  
                  var count = ($(this).is(':checked')) ? 1 : -1;
                  /*Count the total of required courses and refresh the count in the overview*/
                  var elementInitial = $("#strTRequired").find("p");
                  var elementText = $("#strTRequired p b").html();
                  /*Remove the text*/
                  $("#strTRequired p b").remove();
                  var requiredCourses = parseInt($("#strTRequired p").html());
                  count += requiredCourses;
                  $(elementInitial).html("<b>" + elementText + "</b>" + count);
                  learningpath_ajax_request(parameters, function(data) {
                      console.log(data);
                  }, function(error) {
                      console.log(error);
                  });
              })
          }
          
          function get_url_param(name)
          {
              var regexS = "[\\?&]" + name + "=([^&#]*)";
              var regex = new RegExp (regexS);
              var tmpURL = window.location.href;
              var results = regex.exec(tmpURL);
              if (results == null){
                  return "";
              } else {
                  return results[1];
              }
          }
          
          function courses_drag_and_drop()
          {
            
              // Courses list sortable.
              $("#list-course.showsortable").sortable({
                  stop: function(event, ui) {
                      // Build parameters for the request.
                      var parameters = new Object();
                      parameters.action = "save-course-positions";
                      parameters.learningpathid = get_url_param('id');
                      parameters.pageno = get_url_param('page_course');
                      parameters.order = [];
                      $('#list-course.showsortable > li').each(function(){
                          parameters.order.push($(this).data('id'));
                      });
          
                      // Ajax request.
                      learningpath_ajax_request(parameters, false, false);
                  }
              });
              $("#list-course.showsortable").disableSelection();
          }
          
          function prerequisites_drag_and_drop()
          {
              // Prerequisites formularies.
              $('#available-prerequisites, #added-prerequisites').sortable({
                  connectWith: ".drag-and-drop-connected",
                  helper: function(event, ui){
                      var $clone = $(ui).clone();
                      $clone.css('position','absolute');
                      return $clone.get(0);
                  },
              });
          
              // Clicked courses will be marked as active.
              $('#available-prerequisites li, #added-prerequisites li').click(function (event) {
                  //remove class "active" to keep single selection in list
                  $('#available-prerequisites li, #added-prerequisites li').removeClass('active');
                  $(this).toggleClass('active');
              });
          
                  // Add courses button.
              $('.add-prerequisites').off().click(function (event) {
                  //only one course can be added as prerequisite added by ShivkumarY
                  //$('#added-prerequisites li').closest(".prerequisites-drag-and-drop").find("#available-prerequisites").append($('#added-prerequisites li'));
                  $('#available-prerequisites li.active').closest(".prerequisites-drag-and-drop").find("#added-prerequisites").append($('#available-prerequisites li.active')).removeClass('active');
                  
                  event.preventDefault();
                  return false;
              });
          
              // Remove courses button.
              $('.remove-prerequisites').off().click(function (event) {
                  $('#added-prerequisites li.active').closest(".prerequisites-drag-and-drop").find("#available-prerequisites").append($('#added-prerequisites li.active')).removeClass('active');
                  event.preventDefault();
                  return false;
              });
          
              // Save prerequisites.
              $('[data-class="submit-lpcourse"]').unbind().click(function(event) {
                  event.preventDefault();
                  // Get prerequsites array.
                  
                  var courseid = $(this).data('courseid');
                  var prerequisites = [];
                  $('#prerequisites-popup-' + courseid + '-content #added-prerequisites li').each(function(){
                      prerequisites.push($(this).data('courseid'));
                  })
          
                  // Parameters to send in ajax request.
                  var parameters = new Object();
                  parameters.action = "assign-prerequisites";
                  parameters.courseid = courseid;
                  parameters.prerequisites = prerequisites;
                  parameters.learningpathid = get_url_param('id');
          
                  $('#prerequisites-popup-' + courseid).modal('toggle');
                  // Call ajax function.
                  learningpath_ajax_request(parameters, function(data) {
                      reload_courses_list(data);
                      $(".modal-backdrop.fade.show").attr("style", "display: none !important");
                      window.location.reload();
                  }, function(error) {
                      console.log(error)
                  });
          
                  return false;
              })
          }
          
          function changes_icon(elementid)
          {
              if($(elementid).hasClass('collapsed')) {
                  $(elementid + ' i').removeClass('wid wid-icon-down').addClass('wid wid-icon-up');
              } else {
                  $(elementid + ' i').removeClass('wid wid-icon-up').addClass('wid wid-icon-down');
              }
          }
          
          function learningpaths_pagination()
          {
              $('#jump-to-page-button').click(function(){
                  var jump_page = $('#jump-to-page-field').val();
                  var learningpath = get_url_param('id');
                  var items = get_url_param('items') ? get_url_param('items') : 10;
                  window.location.href = M.cfg.wwwroot + '/local/learningpaths/view.php?id=' + learningpath + '&tab=users&page=' + jump_page + '&items=' + items;
              })
          
              // Change number of users per page.
              $('#users-per-page').change(function(){
                  var learningpath = get_url_param('id');
                  var items = $(this).val();
                  window.location.href = M.cfg.wwwroot + '/local/learningpaths/view.php?id=' + learningpath + '&tab=users&page=1&items=' + items;
              })
          }
          
          function searchers()
          {
              // Search Courses, users and cohorts.
             // alert();
              var learningpath = get_url_param('id');
              $('#search-courses, #search-users, #search-cohorts,#search-cohortusers').keypress(function(event) {
                  if (event.which == 13) {
                      var search = '';
                      switch ($(this).attr('id')) {
                          case "search-courses":
                              search = "tab=courses&coursename=" + $(this).val();
                              break;
                          case "search-users":
                              search = "tab=users&user=" + $(this).val();
                              break;
                          case "search-cohortusers":
                                search = "tab=users&cohortuser=" + $(this).val();
                                break;
                          case "search-cohorts":
                              search = "tab=cohorts&cohort=" + $(this).val();
                              break;
                      }
                      window.location.href = M.cfg.wwwroot + "/local/learningpaths/view.php?id=" + learningpath + "&" + search;
                  }
              });
               $("#btn-search-courses, #btn-search-users, #btn-search-cohorts,#btn-search-cohort-users").click(function(){
                   e = $(this).closest(".mt-search").find("input");
                  var search = '';
                      switch ($(e).attr('id')) {
                          case "search-courses":
                              search = "tab=courses&coursename=" + $(e).val();
                              break;
                          case "search-users":
                              search = "tab=users&user=" + $(e).val();
                              break;
                          case "search-cohortusers":
                                search = "tab=users&cohortuser=" + $(this).val();
                                break;
                          case "search-cohorts":
                              search = "tab=cohorts&cohort=" + $(e).val();
                              break;

                      }
                      window.location.href = M.cfg.wwwroot + "/local/learningpaths/view.php?id=" + learningpath + "&" + search;
               });
              no_backend_searchers();
          }
          
          function no_backend_searchers()
          {
              $('#add-courses-search, .add-cohorts-search, .available-courses-search, .assigned-courses-search').off().keyup(function(event) {
                  var target = $(this).data('target');
                  var searching = $(this).val().toLowerCase();
                  var parent = $(this).data('parent');
                
                  // Type of target
                  var type = $(this).data('ttype');
                  if (typeof type != 'undefined' && type == 'class') {
                      target = '.' + target;
                  } else {
                      target = '#' + target;
                  }
                  //var ccount = $('#available-courses-list div.course-lp:not([style*="display: none"])').length;
                  var ccount = 1;
                  // Find results using target
                  $(target).find('.name').each(function() {
                      var name = $(this).text().toLowerCase();
          
                      // Show or hide items.
                      $item = (parent == 'no') ? $(this) : $(this).closest('.row');
                      if (name.search(searching) != -1) {
                          ccount++;
                          $item.removeClass('remove');
                          $item.addClass('show');
                          $item.show();
                      } else {
                        $item.addClass('remove');
                         $item.removeClass('show');
                          $item.hide();
                      }
                  })
                  $('#courses-popup-content .count_tittle span.course-count').text(ccount - parseInt(1));
                  $('#cohorts-popup-content .count_tittle span.cohorts-count').text(ccount - parseInt(1));
              })
          }
          
          // END JS/functions.js

          function learningpath_ajax_request(parameters, success_callback, error_callback) {


              //alert('inside');

              parameters.ajax = true;
              parameters.sesskey = M.cfg.sesskey;
              var courseid = '';
              if(parameters.courseid) {
                var courseid = parameters.courseid;
              }
              var learningpathid = '';
              if(parameters.learningpathid) {
                var learningpathid = parameters.learningpathid;
              }
              var prerequisites = '';
              if(parameters.prerequisites) {
                var prerequisites = String(parameters.prerequisites);
              }
              var pageno = '';
              if(parameters.pageno) {
                var pageno = parameters.pageno;
              }
              var order = '';
              if(parameters.order) {
                var order = String(parameters.order);
              }
              var required = '';
              if(parameters.required) {
                var required = parameters.required;
              }
              var item = '';
              if(parameters.item) {
                var item = parameters.item;
              }
              var cohorts = '';
              if(parameters.cohorts) {
                var cohorts = String(parameters.cohorts);
              }
              var users = '';
              if(parameters.users) {
                var users = String(parameters.users);
              }

        ajax.call([{
            //alert('inside ajax call');
            methodname: 'local_learningpath_ajaxnew',
            args: {action: parameters.action, learningpathid: learningpathid, 
                      courseid:courseid,prerequisites:prerequisites,pageno:pageno,
                      order:order,required:required,item:item,
                    cohorts:cohorts,users:users,ajax:true,sesskey:M.cfg.sesskey},
            //alert('inside ajax call 22');
            done: function(data) {
             // alert(data);
              if (success_callback != false) {
              success_callback(data);
              }
             },
             fail: function(error) {
               if (error_callback != false) {
              error_callback(error);
            }
             },

           
        }]);

          }
		}
	};
});

function check_all ( checkboxId = '', checkboxesClass = '' ) {
    $('#' + checkboxId).change(function() {
        var  checkboxes = $('.' + checkboxesClass);
        if (!$(this).is(':checked')) {
             checkboxes.each(function(){
                if ($(this).is(':checked')) {
                  
                  if(checkboxId == 'selec_allcohorts'){
                   $("#available-cohorts-list div.cohorts-lp.show").each(function( index ) {
                      $('div.cohorts-lp.show .cohort-learninpath').attr('enabled', 'enabled');
                      $('div.cohorts-lp.show .cohort-learninpath').click();
                    });
                    }
                    $(this).click();
                    if(checkboxId == 'id_all_users'){
                        $('#learningpath-remove-users').attr('style','visibility:hidden');
                    }      
                }   
            });
        } else {
            checkboxes.each(function(){
                if (!$(this).is(':checked')) {
                  $(this).click();
                  if(checkboxId == 'selec_allcohorts'){
                    $("#available-cohorts-list div.cohorts-lp.remove").each(function( index ) {
                      $('div.cohorts-lp.remove .cohort-learninpath').attr('disabled', 'disabled');
                    });
                  
                  }
                    
                    if(checkboxId == 'id_all_users'){
                       $('#learningpath-remove-users').attr('style','background-color:#77b300;visibility:visible;display:initial;');
                    }
                    
                }
            });
        }
    });

    uncheck_all(checkboxId, checkboxesClass);
}
/**
 * Uncheck main select if any one checkbox is unchecked
 * 
 * @param {*} checkboxId 
 * @param {*} checkboxesClass 
 */
function uncheck_all( checkboxId = '', checkboxesClass = '' ) {
    $('.' + checkboxesClass).change(function() {
        var  checkboxes = $('.' + checkboxesClass);
        selectedchecks = 0;
        checkboxes.each(function(){
            if ($(this).is(':checked'))
                selectedchecks++;
        });
        if( checkboxes.length == selectedchecks ){
            if( !$('#'+checkboxId).is(':checked') )
                $('#'+checkboxId).prop('checked', true);
        }else{
            if( $('#'+checkboxId).is(':checked') )
                $('#'+checkboxId).prop('checked', false);
        }
    });
};
$(document).ready(function () {
    $(document).on('click', '#course-all',function(e) {
        var checkboxId = "course-all";
        var checkboxesClass = "course-learninpath";
        if ($("#course-all").is(":checked")) {
            $(".course-lp").addClass(" checkbbg");
            $('#' + checkboxId).change(function() {
                var  checkboxes = $('.' + checkboxesClass);
                if (!$(this).is(':checked')) {
                    checkboxes.each(function(){
                        if ($(this).is(':checked')) {
                            $(this).click();
                            if(checkboxId == 'id_all_users'){
                                $('#learningpath-remove-users').attr('style','visibility:hidden');
                            }      
                        }   
                    });
                } else {
                    checkboxes.each(function(){
                        if (!$(this).is(':checked')) {
                            $(this).click();
                            if(checkboxId == 'id_all_users'){
                            $('#learningpath-remove-users').attr('style','background-color:#77b300;visibility:visible;display:initial;');
                            }
                        }
                    });
                }
            });
            uncheck_all(checkboxId, checkboxesClass);
        } else {
            $(".course-lp").removeClass(" checkbbg");
        }
    });
    $('body').click(function (event) {
        if ($(event.target).is('.prerequisites-popup')) {
            location.reload();
        }         
    });
    $(document).on('click', '.prerequisites-popup .close',function(e) {
        location.reload();
    });
});