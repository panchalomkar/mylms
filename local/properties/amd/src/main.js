define(['jquery','local_properties/bootbox'], function($,bootbox) {
                            
    return {
        init: function (stringfield, stringcategory) {
            $(document).ready(function(){
                $('#page-admin-user-profile-index .nav-tabs li a').click(function(){
                    $('#page-admin-user-profile-index .nav-tabs a').removeClass('active');                  
                });  

                $('#userfields').change(function(){
                	if($(this).val() != '')
                	{
                		this.form.submit();
                	}
                });
                
                $( "#id_cancel" ).click(function() {
                  $('.close').click();
                });
                $("#id_cancelcohort").click(function() {
                  $('.close').click();
                });
                $( "#id_cancelcourse" ).click(function() {
                  $('.close').click();
                });
                $("#id_cancelLP").click(function() {
                  $('.close').click();
                });

                $('.content-categories table a[title="Delete"]').click(function(){
                    var id = $(this).data('id');
                    var action = $(this).data('action');
                    var sesskey = $(this).data('sesskey');
                    var count = $(this).data('count');

                    bootbox.confirm(stringfield.replace('{$a}',count), function(result){
                        var confirm = result;
                        $.ajax({
                            type: 'POST',
                            url: M.cfg.wwwroot+'/local/properties/index.php',
                            data: {action:action,id:id,confirm:confirm,sesskey:sesskey},
                            dataType:'json',
                            success: function(data) {  
                                window.location.href = data;
                                location.reload();
                            }   
                        });
                    });
                });

                $('.content-categories h2 a[title="Delete"]').click(function(){
                    var id = $(this).data('id');
                    var action = $(this).data('action');
                    var sesskey = $(this).data('sesskey');
                    
                    bootbox.confirm(stringcategory, function(result){
                        var confirm = result;
                        $.ajax({
                            type: 'POST',
                            url: M.cfg.wwwroot+'/local/properties/index.php',
                            data: {action:action,id:id,confirm:confirm,sesskey:sesskey},
                            dataType:'json',
                            success: function(data) {  
                                window.location.href = data;
                                location.reload();
                            }   
                        });
                    });
                });

                $('#cohortfields').change(function(){
                	if($(this).val() != '')
                	{
                		this.form.submit();
                	}
                });

                $('#coursefields').change(function(){
                	if($(this).val() != '')
                	{
                	 this.form.submit();
                	}
                });

                $('#lpfields').change(function(){
                	if($(this).val() != '')
                	{
                		this.form.submit();
                	}
                });
                $('.categories-content a.add').click(function(){
                     var catid = $(this).data('catid');
                     var action = $('#myModalcourse').find('form').attr('action'),
                     actionNew = action + '&catid=' + catid;                 
                     //$('#myModalcourse').find('form').attr('action', actionNew);
                     $('.urlclass').val(catid);
                });
                  
                var url = window.location.href;
                var ret = url.indexOf("#");
                var activeTab = url.substring(url.indexOf("#")+1);
                if(ret != "-1" && activeTab != "user-tab"){
                    if(activeTab == ""){
                        $('.tbs li a[href="#user_tab"]').addClass('active');
                        $('#learning-paths-container .tab-content div#user_tab').addClass("active in");
                    }
                    else if (activeTab != ""){       
                            $('.tbs li a[href="#'+activeTab+'"]').addClass('active');
                            $('#learning-paths-container .tab-content').removeClass(" active in");
                            $('#learning-paths-container #'+activeTab).addClass("active in");
                    }
                }else{
                    $('.tbs li a[href="#user_tab"]').addClass('active');
                    $('#learning-paths-container .tab-content div#user_tab').addClass("active in");
                }
            });
        }
    };        
});