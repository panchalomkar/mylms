require(['jquery', 'core/modal_factory'], function($, ModalFactory) {


  $(document).ready(function(){
    var show_btn=$('.smeleadersendmsg');
    var show_btn=$('.smeleadersendmsg');
    //$("#testmodal").modal('show');
    
      show_btn.click(function(){
        $("#smeleardermodal").modal('show');
    })
  });
  
  $(function() {
          $('.smeleadersendmsg').on('click', function( e ) {
              var userid = $(this).attr("value");
              $('#submitdatasmel').on('click', function( e ) {
          var textareavalue = $('#exampleFormControlTextarea2').val();
         // var userid = $('.smeleadersendmsg').attr('value');
         alert(textareavalue);
          // $.ajax({
          //             url: 'smeleadersendmsg.php',
          //             type: 'post',
          //             data: {userid: userid, textareavalue: textareavalue},
          //             success: function (response) {
          //                    window.location.reload();
          //             }
          //         });
      });
             
              e.preventDefault();
          });
      });

});