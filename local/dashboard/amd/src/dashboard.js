define(['jquery', 'core/log'], function($ , log) {
    "use strict"; // ...jshint ;_; !!!
    return {
        init: function() {
            $(document).ready(function($) {
                $("#attend_course").on("change", function(event){
                    event.preventDefault();
                    var attdid =  $(this).val();
                    var user =  $(this).attr("data-user");
                    var ajaxUrl = M.cfg.wwwroot + '/local/dashboard/ajax.php';
                    $.ajax({
                        method: 'POST',
                        url: ajaxUrl,
                        data: {
                            attdid: attdid,
                            userid : user,
                            action : 'attendance'
                        },
                        
                        success: function(data) {
                            
                            $("#attend").html(data);
                       
                        }
                    });
                });

                

            });
        }
    };
});
/* jshint ignore:end */