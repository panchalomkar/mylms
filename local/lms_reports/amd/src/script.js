

define(['jquery','core/str','local_lms_reports/notify','local_lms_reports/bootstrap_wizard'], function ($,str) {

    return {
        init: function () {
            var nowDate = new Date();
            nowDate.setDate(nowDate.getDate()-1);
            
            var translation = str.get_strings([
                            
                            {key: 'run_repo_schedule', component: 'local_lms_reports'},
                            {key: 'at_repo_schedule', component: 'local_lms_reports'},
                            {key: 'category_repo_schedule', component: 'local_lms_reports'},
                            {key: 'required_field_empty', component: 'local_lms_reports'},
                            {key: 'recipients_repo_schedule_help', component: 'local_lms_reports'},
                            
                ]);
            
      
             $('#slms-course-wizard').bootstrapWizard({
                tabClas: 'wz-steps',
                nextSelector: '.next',
                previousSelector: '.previous',
                onTabClick: function (tab, navigation, index) {
                    
                    
                    return true;
                },
                onInit: function () {
                },
                onTabShow: function (tab, navigation, index) {
                    var progress = true;
  
                    $('#slms-course-wizard ul li').each(function (index, value) {
                      

                        if (progress == true) {
                            progress = ($(this).hasClass('active')) ? false : true;
                            $('span', this).addClass('ready');
                        }

                    });

                    var $total = navigation.find('li').length;
                    var $current = index + 1;

                    var $percent = (index / $total) * 95.2;
                    var margin = ((120) / $total) / 2;

                    $('#slms-course-wizard').find('.progress-bar-co').css({width: $percent + '%', 'margin': 0 + 'px ' + margin + '%', 'transition': 'all .3s'});

                    navigation.find('li:eq(' + index + ') a').trigger('focus');


                    // If it's the last tab then hide the last button and show the finish instead
                   
                    if ($current >= $total) {
                        $('#slms-course-wizard').find('.next').hide();
                        $('#slms-course-wizard').find('.finish').show();
                        $('#slms-course-wizard').find('.finish').prop('disabled', false);
                    } else {
                   // alert( $('#slms-course-wizard').find('.next').show());
                        $('#slms-course-wizard').find('.next').show();
                        $('#slms-course-wizard').find('.finish').hide().prop('disabled', true);
                    }
                    var wdt = 100 / $total;
                    var lft = wdt * index;


                    // If it's the last tab then hide the last button and show the finish instead
                   
                    if ($current == 1) {
                                            
                             $('#slms-course-wizard').find('.previous').prop('disabled', true);
                             $('#slms-course-wizard').find('.previous').css({opacity: 0.5});
                             
                   
                    }   else{
                        $('#slms-course-wizard').find('.previous').prop('disabled', false);
                        $('#slms-course-wizard').find('.previous').css({opacity: 1});
                    }
                    
                    if ($current == 3) {
                        if ($('input[name=classtype]:radio:checked').val() == 'virtualclass' || $('input[name=classtype]:radio:checked').val() == 'classroom') {
                            $('#slms-course-wizard').find('.next').hide();
                            $('#slms-course-wizard').find('.finish').show();
                            $('#slms-course-wizard').find('.finish').prop('disabled', false);
                        }
                    } else if ($current >= $total) {
                        $('#slms-course-wizard').find('.next').hide();
                        $('#slms-course-wizard').find('.finish').show();
                        $('#slms-course-wizard').find('.finish').prop('disabled', false);
                    } else {
                        $('#slms-course-wizard').find('.next').show();
                        $('#slms-course-wizard').find('.finish').hide().prop('disabled', true);
                    }
                },
                onNext: function () {

                    if ($('#slms-course-wizard1').hasClass('active'))
                    {
                       
                        var value = $('#slms-course-wizard form input[name=label]').val();
                        
                        var message = $('textarea#description_id').val();
                      
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                         
                            var reqmessage = M.util.get_string('required_field_empty', 'local_lms_reports');
                         
                            if($("#errorlabel").text()!=reqmessage){
                                 $('#label_id').after('<div id="errorlabel">'+reqmessage+'</div>');
                            }
                            $('#slms-course-wizard form input[name=label]').css('border-color', '#ef5d84'); 
                            $('#slms-course-wizard form input[name=label]').focus();
                            return false;
                        } else
                        {
                            $('#slms-course-wizard form input[name=label]').css('border-color', 'none');
                        }

                    }
                    if ($('#slms-course-wizard2').hasClass('active'))
                    {
                        
                        $('#slms-course-wizard form input[name=run]').css('border-color', '#ad1319');
                        
                    }

                    if ($('#slms-course-wizard4').hasClass('active'))
                    {
                        var value = $('#slms-course-wizard form input[name=format]').val();
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                           
                            $('#slms-course-wizard form input[name=format]').css('border-color', '#ad1319');
                            $('#slms-course-wizard form input[name=format]').focus();
                            return false;
                        } else
                        {
                            $('#slms-course-wizard form input[name=format]').css('border-color', 'none');
                        }
                    }

                    if ($('#slms-course-wizard5').hasClass('active'))
                    {
                        //var value = $('#slms-course-wizard form input[name=start_date]').val();
                       
                        var value = $('#slms-course-wizard form input[name=recipients]').val();
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                           
                             var reqmessage = M.util.get_string('required_field_empty', 'local_lms_reports');
                         
                            if($("#errorreceipent").text()!=reqmessage){
                                 $('#receipent_id').after('<div id="errorreceipent">'+reqmessage+'</div>');
                            }
                            $('#slms-course-wizard form input[name=recipients]').css('border-color', '#ad1319');
                            $('#slms-course-wizard form input[name=recipients]').focus();
                            return false;
                        } else {
                            $('#slms-course-wizard form input[name=recipients]').css('border-color', 'none');
                            var elementos = document.getElementById("form-wizard-reports").elements;
                           
                            var content = '';
                           // content += "<div class='col-lg-12  center' style='float: center;text-align:left'>";
                            content += "<div style='display: inline-block; text-align: left;'>";
                            content += '<strong>' + M.util.get_string('info_to_save_repo_schedule', 'local_lms_reports') + '</strong><br/><br/>';
                            for (var i = 1; i <= elementos.length; i++)
                            {
                            

                                var name = $(elementos[i]).attr('name');
                                var class_var = $(elementos[i]).attr('class');
                          
                                if(name == "label"){
                                   // var value = $('#slms-course-wizard form input[name=start_date]').val();
                                    var values = $('#slms-course-wizard form input[name=label]').val();
                                    
                                }else if(name == "description"){
                                    var values = $('textarea#description_id').val();
                                                                
                                }else if(name == "category"){
                                    
                                    var values = $('#slms-course-wizard form select[name=category] option:selected').text();
                                }
                                else if(name == "run"){
                                    
                                    var values = $('#slms-course-wizard form select[name=run]').val();
                                }
                                 else if(name == "at"){
                                    
                                    var values = $('#slms-course-wizard form select[name=at] option:selected').html();
                                }
                                
                                 else if(name == "recipients"){
                                    
                                    var values = $('#slms-course-wizard form input[name=recipients]').val();
                                }
                                
                                else if(name == "message"){
  
                                    var values = $('textarea#message_id').val();
                                }
                      
                                if (!name && !values)
                                {
                                } else
                                {

                                   if (name == 'label' || name == 'description' || name == 'category' || name == 'run' || name == 'at' || name == 'recipients' || name == 'message')
                                    {
                                         
                                          name =  M.util.get_string(name + '_repo_schedule', 'local_lms_reports');
                                    
                                           content += '<strong>' + name + '</strong>: ' + values + '<br />';
                                    }
                                }
                            }
                            content += "</div>";
                            document.getElementById("final-data-form").innerHTML = content;
                        }
                    }
                },
                onPrevious: function () {

                    if ($('#slms-course-wizard1').hasClass('active'))
                    {
                        var value = $('#slms-course-wizard form input[name=label]').val();
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                          
                            $('#slms-course-wizard form input[name=label]').css('border-color', '#ad1319');
                            $('#slms-course-wizard form input[name=label]').focus();
                            return false;
                        } else
                        {
                            $('#slms-course-wizard form input[name=label]').css('border-color', 'none');
                        }

                    }
                    if ($('#slms-course-wizard2').hasClass('active'))
                    {
                        var value = $('#slms-course-wizard form input[name=start_date]').val();
                      
                    }
                    if ($('#slms-course-wizard4').hasClass('active'))
                    {
                        var value = $('#slms-course-wizard form input[name=format]').val();
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                          
                            $('#slms-course-wizard form input[name=format]').css('border-color', '#ad1319');
                            $('#slms-course-wizard form input[name=format]').focus();
                            return false;
                        } else
                        {
                            $('#slms-course-wizard form input[name=format]').css('border-color', 'none');
                        }
                    }

                    if ($('#slms-course-wizard5').hasClass('active'))
                    {
                        var value = $('#slms-course-wizard form input[name=recipients]').val();
                        if (value == null || value.length == 0 || /^\s*$/.test(value))
                        {
                          
                            $('#slms-course-wizard form input[name=recipients]').css('border-color', '#ad1319');
                            $('#slms-course-wizard form input[name=recipients]').focus();
                            return false;
                        } else
                        {
                            $('#slms-course-wizard form input[name=recipients]').css('border-color', 'none');
                            var elementos = document.getElementById("form-wizard-reports").elements;
                         

                            var content = '<strong>' + M.util.get_string('info_to_save_repo_schedule', 'local_lms_reports') + '</strong><br/><br/>';
                            content += "<div class='col-lg-7 left' style='float: right;'>";
                            for (var i = 1; i <= elementos.length; i++)
                            {

                                var name = $(elementos[i]).attr('name');
                              
                                var values = $(elementos[i]).attr('value');

                                if (name == 'start_date' || name == 'end_date')
                                {
                                    document.getElementById('hidden_' + name).value = values;
                                }


                                if (!name && !values)
                                {
                                } else
                                {

                                    if (name == 'label' || name == 'description' || name == 'start_date' || name == 'end_date' || name == 'recipients' || name == 'message')
                                    {
                                       // name = name.replace('_', ' ');
                                        
                                        newname = M.util.get_string(name + '_repo_schedule', 'local_lms_reports');
                                   
                                        content += '<strong>' + newname  + '</strong>: ' + values + '<br />';
                                    }

                                }
                            }
                            content += "</div>";
                            document.getElementById("final-data-form").innerHTML = content;
                        }
                    }
                }
            });    
        },
        myfavoritereport : function(){
            $("#report_accordion .panel-title").click(function( e ) {					
                e.preventDefault();
                e.stopPropagation();
                var lc_id = $(this).find('a').attr('href');
                if ( $( lc_id ).length > 0 ) {
                    if ( $( lc_id ).css('display') == 'none' ) { 
                        $(this).find('a').eq(0).find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
                        $(this).find('a').addClass('active');
                    } else { 
                        $(this).find('a').eq(0).find('i').removeClass('fa-angle-up').addClass('fa-angle-down');
                        $(this).find('a').removeClass('active');
                    }
                    $( lc_id ).slideToggle( "slow", function() {
                            // hide all other elements
                            
                        $("#report_accordion .panel-collapse:not([id='" + lc_id.replace(/\#/,'') + "'])").slideUp(); 

                        $("#report_accordion .panel-title").each(function( index ) {
                            var lc_hide_id = $(this).find('a').attr('href');
                            if ( lc_hide_id != lc_id ) {
                                $(this).find('a').eq(0).find('i').removeClass('fa-angle-up').addClass('fa-angle-down');
                                $(this).find('a').removeClass('active');
                            }
                        });
                          
                    });
                };
            });
            $('body').delegate('a.star','click',function(e) {
                e.preventDefault();
                e.stopPropagation();
                destacar(this);
            });


            function destacar(obj){
                var url = M.cfg.wwwroot + '/local/lms_reports/actions.php';
                var res = $(obj).attr('alt').split('-');
                var id 	= res[0];
                var fav = res[1];
                var my_favorites_div;
                if($('#my_favorites').length){
                    my_favorites_div = 1;
                }else{
                    my_favorites_div = 0;
                }

                var parametros= { id : id, fav : fav, task : 'dest', mfd : my_favorites_div }
                
                jQuery.getJSON( url, parametros, function(data) {   
                    if(data.success){
                        var cls ='';
                        if(data.fav === 1){
                            if( my_favorites_div === 0 ){
                                /**
                                * it replace current reports with output response html and shows all reports including My Favorites
                                */
                                $("#report_accordion").html(data.mfd);
                                $('#'+$(obj).closest(".show").attr("id")).addClass('show');
                            } else {
                                /**
                                * it clones current report for takes and shows in My Favorites
                                */
                                cls = "fa fa-star";
                                var alt = data.id +'-'+ data.fav;
                                $(obj).attr('alt', alt); // gets report id
                                $(obj).children('i').attr('class', cls);
                                $clone = $(obj).parents( ".item-menu" ).clone(true);
                                $("#my_favorites #collapsef .panel-body").prepend($clone);
                            }
                        } else {
                            var cls ='fa fa-star-o';
                            var alt = data.id +'-'+ data.fav;
                            $(obj).attr('alt', alt); // gets report id
                            $(obj).children('i').attr('class', cls);
                            $parent = $("#my_favorites #collapsef .panel-body").find('[rel=' + data.id +']');
                            if( $parent.length > 0 ) { // item-menu is in My Favorites
                                $report = $('.panel .panel-body .item-menu').find('[rel=' + data.id +']');
                                $report.attr('alt', alt);
                                $report.children('i').attr('class', cls);
                                $("#my_favorites #collapsef .panel-body").find('[rel=' + data.id +']').parents( ".item-menu" ).remove();
                            }
                            else { // item-menu is not in My Favorites
                                $parent = $(obj).parents( ".panel" );
                            }
                            if($("#my_favorites #collapsef .panel-body .row").length < 1){
                                // if row is not in My Favorites then remove my_favorites
                                $('#my_favorites').remove();
                            }
                        }
                    }
                }); 
            }
            
        },
        

        searchReport : function(){
            $('#txt').on('keyup', function() {
                var lc_string = document.getElementById('txt').value.toLowerCase();
                console.log(lc_string);
                // find coincidences
                if ( lc_string.replace(/\s/,'') != '' ) {
                    // hide all
                    $("#report_accordion .panel-collapse").hide();
                    $("#report_accordion .item-menu").hide();

                    var li_founds = $("#report_accordion a[search*='" + lc_string + "']").length;

                    if ( li_founds > 0 ) {
                        $("#report_accordion a[search*='" + lc_string + "']").each(function( index ) {
                            $(this).show();
                            $(this).closest('div .item-menu').show();
                            $(this).closest('div .panel-collapse').show();
                            $(this).closest("div .panel").eq(0).find("i.reports-dropdown").removeClass('fa-angle-down').addClass('fa-angle-up');
                        });
                    }
                } else {
                    $("#report_accordion div.panel-collapse").hide();
                    $("#report_accordion .item-menu").show();
                    $("#report_accordion .panel").find("i.reports-dropdown").removeClass('fa-angle-up').addClass('fa-angle-down');
                }
            });
           $('#searchform').submit(function(e){
                e.preventDefault();
                e.stopPropagation();
                searchreport();
                return false;
            });	
            $('#searchbutton').click(function(e){
                e.preventDefault();
                e.stopPropagation();
                searchreport();
                return false;
            });
            
            function searchreport(){
                var txt = $('#txt').val();
                txt=txt.trim(); 
                var url= M.cfg.wwwroot +'/local/lms_reports/actions.php';  
                var parametros= {}
                parametros.txt= txt; 
                parametros.task='searchreport';
                jQuery.getJSON(
                    url,
                    parametros,
                    function(r1){
                        if(r1.success){ 
                            //console.log(r1.menu);
                            $('.accordion#accordion').html( r1.menu);
                        }
                    }
                );  
            }
            function resize(){
                if (document.all){
                        $("#iframen").css('height',window.frames.iframen.document.body.scrollHeight + 20);
                }else{
                        $("#iframen").css('height',window.frames.iframen.document.body.offsetHeight + 20);
                }
            }
        }
    };
});
