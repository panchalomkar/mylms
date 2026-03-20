function check_all ( checkboxId = '', checkboxesClass = '' ) {
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

}

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
    $('#learningpath-courses-list').html(data.courses_list);
    $("#courses-popup #courses-popup-content").html(data.course_list_add);
    $('#courses-popup-content').html(data.add_courses_form).find('form').show();
    //add_course_to_learningpath();
    add_required_switch();
    $('#learningpath-courses-list').find('form').show().find('.btn-cancel, #id_submitbutton').addClass('btn btn-primary btn-round');

    // If user is searching a course, then he can't drag and drop it.
    if (!get_url_param('coursename')) {
        courses_drag_and_drop();

    }
    no_backend_searchers();
    prerequisites_drag_and_drop();
    $('.tooltipelement_html').tooltip({html:true});
    removeCourseAction();
    /*DCarmona*/
    /*Count total courses to refresh the overview and refresh the course list in the overview*/
    var tCourses = $("#list-course li.course-description").length || 0;
    var elementInitial = $("#strTCourses").find("p");
    var elementText = $("#strTCourses p b").html();
    
    $(elementInitial).html("<b>" + elementText + "</b>" + tCourses);
    id = get('id');
    $.ajax({
        type    : "POST",
        dataType: 'json',
        data    : { action: 'getLpDetail', learningPath: id, 'ispagetypelocal': true},
        url     : M.cfg.wwwroot + '/blocks/rlms_lpd/lib/ajax.php', 
        async   : true
    }).done(function(response) {
        if(response.view && response.view !== '') {
            $('#block_rlms_lpd_content .lpd-lp-content').html(response.view);
            //lphtmlobject.parent().css('height', 'auto');  
        }
    });
}

function removeCourseAction(){
    $('.delete-course-learning-path').click(function(event) {
        e = this;
        require(['jquery','local_learningpaths/bootbox'],function($,bootbox){
        bootbox.confirm("Are you sure you want to delete this course from this Learning Path?", function(result){
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
        $(this).toggleClass('active');
    });

        // Add courses button.
    $('.add-prerequisites').off().click(function (event) {
        //$('#available-prerequisites li.active').appendTo('#added-prerequisites').removeClass('active');
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
    $('[data-class="submit-lpcourse"]').click(function(event) {
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
        }, function(error) {
            console.log(error)
        });
	location.reload();
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
    var learningpath = get_url_param('id');
    $('#search-courses, #search-users, #search-cohorts').keypress(function(event) {
        if (event.which == 13) {
            var search = '';
            switch ($(this).attr('id')) {
                case "search-courses":
                    search = "tab=courses&coursename=" + $(this).val();
                    break;
                case "search-users":
                    search = "tab=users&user=" + $(this).val();
                    break;
                case "search-cohorts":
                    search = "tab=cohorts&cohort=" + $(this).val();
                    break;
            }
            window.location.href = M.cfg.wwwroot + "/local/learningpaths/view.php?id=" + learningpath + "&" + search;
        }
    });
     $("#btn-search-courses, #btn-search-users, #btn-search-cohorts").click(function(){
         e = $(this).closest(".mt-search").find("input");
        var search = '';
            switch ($(e).attr('id')) {
                case "search-courses":
                    search = "tab=courses&coursename=" + $(e).val();
                    break;
                case "search-users":
                    search = "tab=users&user=" + $(e).val();
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
    $("#add-courses-search, .add-cohorts-search, .available-courses-search, .assigned-courses-search").on("keyup change", function(event) {
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

        // Find results using target
        $(target).find('.name').each(function() {
            var name = $(this).text().toLowerCase();

            // Show or hide items.
            $item = (parent == 'no') ? $(this) : $(this).closest('.row');
            if (name.search(searching) != -1) {
                $item.show();
            } else {
                $item.hide();
            }
        })
    })
}

function learningpath_ajax_request(parameters, success_callback, error_callback) {
    parameters.ajax = true;
    parameters.sesskey = M.cfg.sesskey;
    $.ajax({
        method: "POST",
        url: M.cfg.wwwroot + "/local/learningpaths/actions.php",
        data: parameters,
        dataType: "json"
    }).done(function(data){
        if (success_callback != false) {
            success_callback(data);
        }
    }).fail(function(error){
        if (error_callback != false) {
            error_callback(error);
        }
    });
}