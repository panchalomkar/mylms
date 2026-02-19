define(['jquery', 'jqueryui', 'core/str'], function ($, jui, str) {
    return {
        init: function () {
            $(document).ready(function () {

                // Select manager
                $(document).on('change', '#select_team', function() {
                    var managerid = $('#select_team option:selected').val();
                    show_team_users(managerid);
                });

                // Add to team
                $(document).on('click', '#addtoteam', function() {
                    var managerid = $('#select_team option:selected').val();
                    var users = $('#availableusersbox').val();

                    if(managerid == 0 || managerid == undefined){
                        console.log('manager id not found');
                        return false;
                    }
                    if(users == '' || users == undefined){
                        console.log('users not found');
                        return false;
                    }

                    var object = {}
                    object['managerid'] = managerid;
                    object['action'] = 'assignusers';
                    object['users'] = users;

                    var ajaxUrl = M.cfg.wwwroot + '/local/my_team/ajax/my_team_ajax.php';
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        if(res.status == "success"){
                            var options = $('#availableusersbox').find('option:selected');
                            $.each(options, function( i, v ) {
                                $('#assignedusersbox').append($('<option class="assigned-users-option">').val(v.value).text(v.innerText));
                            });
                            $('#availableusersbox').find('option:selected').remove();
                        } else {
                        }
                    });
                });
                
                // Remove from team
                $(document).on('click', "#removefromteam", function() {
                    var managerid = $('#select_team option:selected').val();
                    var users = $('#assignedusersbox').val();

                    if(managerid == 0 || managerid == undefined){
                        console.log('manager id not found');
                        return false;
                    }
                    if(users == '' || users == undefined){
                        console.log('users not found');
                        return false;
                    }

                    var object = {}
                    object['managerid'] = managerid;
                    object['action'] = 'removeusers';
                    object['users'] = users;

                    var ajaxUrl = M.cfg.wwwroot + '/local/my_team/ajax/my_team_ajax.php';
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: object,
                        dataType: 'json',
                        async: false,
                    }).done(function (res) {
                        if(res.status == "success"){
                            var options = $('#assignedusersbox').find('option:selected');
                            $.each(options, function( i, v ) {
                                $('#availableusersbox').append($('<option class="assigned-users-option">').val(v.value).text(v.innerText));
                            });
                            $('#assignedusersbox').find('option:selected').remove();
                        } else {
                        }
                    });
                });

                // search assigned, available course
                var typing_timer; // timer identifier
                $(document).on('input', "#assigneduserssearch, #availableuserssearch", function() {
                    clearTimeout(typing_timer);
                    typing_timer = setTimeout(() => {
                        var identifier = $(this).attr('id').replace('userssearch', '');
                        var managerid = $('#select_team option:selected').val();
                        
                        if(managerid == 0 || managerid == undefined){
                            console.log('manager id not found');
                            return false;
                        }
                        done_search_typing(managerid, identifier)
                    }, 1000);
                });
            });
        }
    }
});

function show_team_users(managerid){
    if(managerid == 0 || managerid == undefined){
        $('#assignedusersbox').empty();
        $('#availableusersbox').empty();
        return false;
    }

    var object = {}
    object['managerid'] = managerid;
    object['action'] = 'showusers';

    var ajaxUrl = M.cfg.wwwroot + '/local/my_team/ajax/my_team_ajax.php';
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: object,
        dataType: 'json',
        async: false,
    }).done(function (res) {
        $('#assignedusersbox').empty();
        $('#availableusersbox').empty();
        if(res.status == "success"){
            $.each(res.assignedusers, function( index, value ) {
                $('#assignedusersbox').append($('<option class="assigned-users-option">').val(index).text(value.firstname +' '+ value.lastname +' ( '+ value.email +' )'));
            });
            $.each(res.availableusers, function( index, value ) {
                $('#availableusersbox').append($('<option class="available-users-option">').val(index).text(value.firstname +' '+ value.lastname +' ( '+ value.email +' )'));
            });
        }
    });
    return false;
}

function done_search_typing(managerid, identifier) {
    var search = $('#'+identifier+'userssearch').val();

    var object = {}
    object['managerid'] = managerid;
    object['action'] = 'searchusers';
    object['search'] = search;
    object['searchin'] = identifier;

    var ajaxUrl = M.cfg.wwwroot + '/local/my_team/ajax/my_team_ajax.php';
    $.ajax({
        method: 'POST',
        url: ajaxUrl,
        data: object,
        dataType: 'json',
        async: false,
    }).done(function (res) {
        var prevselected = $('#'+identifier+'usersbox').val();
        $('#'+identifier+'usersbox').empty();
        if(res.status == "success"){
            var selected = '';
            $.each(res.users, function( index, value ) {
                if($.inArray(value.id, prevselected) !== -1){
                    selected = ' selected';
                } else {
                    selected = '';
                }
                console.log(value);
                $('#'+identifier+'usersbox').append($('<option class="'+identifier+'-users-option" '+selected+'>').val(index).text(value.firstname +' '+ value.lastname +' ( '+ value.email +' )'));
            });
        }
    });
    return false;
}