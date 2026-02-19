/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function(){
 

        $("#id_bucode").change(function(){
        var bucode = $(this).find(":selected").val();
        if(bucode!=0){ 
            $('#id_locationid').children('option:not(:first)').remove();
             
            $.getJSON("ajax.php?action=getlocation&bucode="+bucode, function(result){
                $.each(result, function(i, field){
                    $('#id_locationid').append($("<option></option>").attr("value",i).text(field));
                });
            });
        }
        else{
            $('#id_locationid').children('option:not(:first)').remove();
            $("#bu_code").val('NULL');

        }
        
    });

    //check already created classroom
    $('#id_classroom').on('blur',function(){
        var classroom = $(this).val();
        var locationid = $("#id_locationid").val();
        $.ajax({
                type:'POST',
                url:'ajax.php',
                data:{locationid:locationid,classroom:classroom},
                success:function(html){
                    if(html == 1)
                    {
                        $("#classroomalert").fadeTo(2000, 500).slideUp(500, function(){
                                                $("#classroomalert").slideUp(500);
                                            });
                        $('#id_classroom').val('');
                    }
                }
            });
    });
    
    //check already created location
    $('#id_location').on('blur',function(){
        var location = $(this).val();
        $.ajax({
                type:'POST',
                url:'ajax.php',
                data:{location:location},
                success:function(html){
                    if(html == 1)
                    {
                        $("#locationalert").fadeTo(2000, 500).slideUp(500, function(){
                                                $("#locationalert").slideUp(500);
                                            });
                        $('#id_location').val('');
                    }
                }
            });
    });
    
});


