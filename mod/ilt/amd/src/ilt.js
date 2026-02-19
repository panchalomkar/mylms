/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/*
 * @Author VaibhavG
 * @desc selection of location it will get classroom according to it's location
 * @dat3e 13 Dec 2018
 * Start Code
 */
define(['jquery','core/str'], function($,str) {

    var strings = str.get_strings([
        { key: 'error:sessionstartafterend', component: 'ilt' },
    ]);
    return {
        init: function() {
        
            $(document).ready(function(){
                
                $('#id_sessionlocation').on('change',function(){
                    var location = $(this).val();
                    if(location)
                    {
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'location='+location,
                            success:function(html){
                                $('#id_classroom').html(html);
                            }
                        }); 
                    }
                    else{
                        $('#id_sessionlocation').html('<option value="">Select Location</option>');
                        $('#id_sessioncapacity').val('Classroom Capacity'); 
                    }
                });
                
            
                $('#id_classroom').on('change',function(){
                    var classroom = $(this).val();
                    if(classroom)
                    {
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'classroom='+classroom,
                            success:function(html){
                                $('#id_sessioncapacity').val(html);
                            }
                        }); 
                    }
                    else{
                        $('#id_classroom').html('<option value="">Select Classroom</option>');
                        $('#id_sessioncapacity').html('Classroom Capacity'); 
                    }
                    
                    var classroomres = $(this).val();
                    if(classroomres)
                    {
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'classroomres='+classroomres,
                            success:function(html){
                                //console.log(html);
                                $('#id_sessionresource').html(html);
                            }
                        }); 
                    }
                    else{
                        $('#id_classroom').html('<option value="">Select Classroom</option>');
                        $('#id_sessioncapacity').html('Classroom Capacity'); 
                    }
                });
                
                
                

                    //getting session capacity & notify 
                    var allowoverbook = $('#allowoverbook').val();
                    if(allowoverbook != '1')
                    {
                        var removecount = 0;
                        var selectcount = 0;
                        var remove_selected_count = 0;
                        var add_selected_count = 0;
                        var last_valid_selection = null;
                        var sessionids = $('#sessionid').val();            

                        $('#addselect').on('change',function(){                 
                            var count = $(this).val().length;
                            if($('#removeselect option').length == 1 && $.trim($('#removeselect option').val()) === '' )
                            {                 
                                var removecount = 0;
                            }
                            else
                            {
                                var removecount = $('#removeselect option').length;//console.log(removecount);
                            }                
                            var selectcount = $('#addselect option:selected').length;//console.log(removecount);

                            if(selectcount){
                                $.ajax({
                                    type:'POST',
                                    url:'fetch_sessionlocation.php',
                                    data:'sessionids='+sessionids,
                                    success:function(html)
                                    {
                                
                                        if(removecount)
                                        {                                
                                            var remove_selected_count = parseInt(removecount) + parseInt(selectcount);
                                        }
                                        else
                                        {                              
                                            var remove_selected_count = removecount ;
                                        }
                                                                    
                                        var add_selected_count =  selectcount;
                                        
                                        //it checks remove select count plus add select count and pop-up error
                                        if (remove_selected_count > parseInt(html))
                                        {
                                            $(".alert-danger").fadeTo(2000, 500).slideUp(500, function(){
                                                $(".alert-danger").slideUp(500);
                                            });
                                            $('#addselect option').prop('selected', false );
                                        }
                                        //it checks only add select count and pop-up error        
                                        if (add_selected_count > html)
                                        {                                
                                            $(".alert-danger").fadeTo(2000, 500).slideUp(500, function(){
                                                $(".alert-danger").slideUp(500);
                                            });
                                            $('#addselect option:selected').prop('selected', false);
                                        }
                                    }
                                }); 
                            }
                        });
                    }
                    
                    
                    
                //getting all course user
                    $("input:radio[id=id_allusers]").click(function() {       
                        var coursetype = $('#id_allusers').val();
                        var sessionid = $('#sessionid').val();
                        
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'coursetype='+coursetype+'&sessionid='+sessionid,
                            success:function(html){
                                $('#addselect').html(html);
                            }
                        }); 
                    });
                
                    //getting all system user
                $("input:radio[id=id_courseusers]").click(function() {       
                    var coursetype = $('#id_courseusers').val();
                    var sessionid = $('#sessionid').val();
                    var courseid = $('#courseid').val();
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'coursetype='+coursetype+'&sessionid='+sessionid+'&courseid='+courseid,
                            success:function(html){
                                $('#addselect').html(html);
                            }
                        }); 
                });
                
                
                
                //getting already booked session users
                $('#addselect').on('change',function(){
                        var last_valid_selection = null;          
                        var addselect = $(this).val();
                        var current_sessionid = $('#sessionid').val();
                        
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'addselect='+addselect+'&current_sessionid='+current_sessionid,
                            success:function(html){
                                if(html)
                                {
                                    $(".alert-info").html(html);
                                    $(".alert-info").fadeTo(1000, 500).slideUp(500, function(){
                                        $(".alert-info").slideUp(500);
                                        $(".alert-info").empty();
                                    });
                                    $('#addselect option').prop('selected', false);
                                }
                            }
                        }); 
                });
                
                
                //getting already booked session instructor
                $('#id_sessioninstructor').on('change',function(){
                        var last_valid_selection = null;          
                        var sessioninstructor = $(this).val();   
                        var current_session = $("input[name=s]").val();
                        var timestart = $('#id_timestart_0_hour').val();
                        var timestartmin = $('#id_timestart_0_minute').val();
                        var timestartday = $('#id_timestart_0_day').val();
                        var timestartmonth = $('#id_timestart_0_month').val();
                        var timestartyear = $('#id_timestart_0_year').val();
                        
                        var timefinish = $('#id_timefinish_0_hour').val();
                        var timefinishmin = $('#id_timefinish_0_minute').val();
                        var timefinishday = $('#id_timefinish_0_day').val();
                        var timefinishmonth = $('#id_timefinish_0_month').val();
                        var timefinishyear = $('#id_timefinish_0_year').val();
                        
                        var start = timestartmonth+'/'+timestartday+'/'+timestartyear+' '+timestart+':'+timestartmin+':'+'00';
                        var finish = timefinishmonth+'/'+timefinishday+'/'+timefinishyear+' '+timefinish+':'+timefinishmin+':'+'00';
                    
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'sessioninstructor='+sessioninstructor+'&current_session='+current_session+'&start='+start+'&finish='+finish,
                            success:function(html){
                                if(html)
                                {
                                    $(".alert-danger").html(html);
                                    $(".alert-danger").fadeTo(1000, 500).slideUp(500, function(){
                                        $(".alert-danger").slideUp(500);
                                        $(".alert-danger").empty();
                                    });
                                        $('#id_sessioninstructor option:selected').prop('selected', false);
                                }
                            }
                        }); 
                });
                
                //getting already booked session location and classroom
                $('#id_classroom').on('change',function(){
                        var last_valid_selection = null;          
                        var sessionclassroom = $(this).val();  
                        var sessionlocation = $('#id_sessionlocation').val();
                        var s = $("input[name=s]").val();
                        var f = $("input[name=f]").val();
                        if(s != 0)
                            var current_session = $("input[name=s]").val();
                        else if(f != 0)
                            var current_session = $("input[name=f]").val();
                        //console.log(current_session);
                        var timestart = $('#id_timestart_0_hour').val();
                        var timestartmin = $('#id_timestart_0_minute').val();
                        var timestartday = $('#id_timestart_0_day').val();
                        var timestartmonth = $('#id_timestart_0_month').val();
                        var timestartyear = $('#id_timestart_0_year').val();
                        
                        var timefinish = $('#id_timefinish_0_hour').val();
                        var timefinishmin = $('#id_timefinish_0_minute').val();
                        var timefinishday = $('#id_timefinish_0_day').val();
                        var timefinishmonth = $('#id_timefinish_0_month').val();
                        var timefinishyear = $('#id_timefinish_0_year').val();
                        
                        var start = timestartmonth+'/'+timestartday+'/'+timestartyear+' '+timestart+':'+timestartmin+':'+00;
                        var finish = timefinishmonth+'/'+timefinishday+'/'+timefinishyear+' '+timefinish+':'+timefinishmin+':'+00;
                    
                        $.ajax({
                            type:'POST',
                            url:'fetch_sessionlocation.php',
                            data:'sessionlocation='+sessionlocation+'&sessionclassroom='+sessionclassroom+'&current_session='+current_session+'&start='+start+'&finish='+finish,
                            success:function(html){
                                if(html)
                                {
                                    $(".alert-info").html(html);
                                    $(".alert-info").fadeTo(1000, 500).slideUp(500, function(){
                                        $(".alert-info").slideUp(500);
                                        $(".alert-info").empty();
                                    });
                                    $('#id_classroom option').prop('selected', false);
                                }
                            }
                        }); 
                });
                 
                /**
                 * Method to check if start date is greater than end date
                 * @author Manisha M.
                 * @since 29-07-2019
                 * @paradiso
                 * @ticket 598
                */
                $("#id_date_add_fields").click(function(){
                    $("#mform1").submit();
                })
                $("#mform1").submit(function(e){
                    var repeat = $("input[name*='date_repeats']").val();
                    var count = repeat-1;
                    for(var i =0; i<=count;i++){ 
 
                    var timestart = $('#id_timestart_'+i+'_hour').val();
                    var timestartmin = $('#id_timestart_'+i+'_minute').val();
                    var timestartday = $('#id_timestart_'+i+'_day').val();
                    var timestartmonth = $('#id_timestart_'+i+'_month').val();
                    var timestartyear = $('#id_timestart_'+i+'_year').val();
                    
                    var timefinish = $('#id_timefinish_'+i+'_hour').val();
                    var timefinishmin = $('#id_timefinish_'+i+'_minute').val();
                    var timefinishday = $('#id_timefinish_'+i+'_day').val();
                    var timefinishmonth = $('#id_timefinish_'+i+'_month').val();
                    var timefinishyear = $('#id_timefinish_'+i+'_year').val();
                   
                    var start = new Date(timestartmonth+'/'+timestartday+'/'+timestartyear+' '+timestart+':'+timestartmin+':'+'00');
                    var finish = new Date(timefinishmonth+'/'+timefinishday+'/'+timefinishyear+' '+timefinish+':'+timefinishmin+':'+'00');
                   
                    if(start > finish){
                        $('#id_timestart_'+i+'_day').parent().parent().parent().next().html(M.util.get_string('error:sessionstartafterend', 'ilt'));
                        $('#id_timestart_'+i+'_day').parent().parent().parent().next().css('display','');
                        $('#id_timestart_'+i+'_day').parent().parent().parent().next().css('color','#721c24');
                        $(window).scrollTop(0);
                        e.preventDefault(e);
                    }else{
                        $('#id_timestart_'+i+'_day').parent().parent().parent().next().html("");
                        $('#id_timestart_'+i+'_day').parent().parent().parent().next().css('display','none');
                    }
                    }
                })
        });
        }
    };
});