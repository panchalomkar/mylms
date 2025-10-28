/**
 * DisableForward Html5 Video
 *
 * @module      mod_videofile/videocontroll Html5 Video
 * Author	 	    2019 Bhgayavant Panhalkar
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
  return {
    init: function() {
     
      $(document).on('click','span.leave',function(){
          window.location.reload();
      });
      $(document).on('click','span.continue',function(){
         $('.aftervideocomplete').css('display','none');
      });

      if($("video[id^=videofile-]").attr("id")){
      $("video").prop('muted', false);
      var miid = $("video[id^=videofile-]").attr("id");
      $('.vjs-mute-control.vjs-control').addClass('vjs-vol-3');
      //videojs(miid).currentTime(0);
      var vid = videojs(miid);
      vid.on("ended", function(){
        vid.posterImage.show();
        
        vid.bigPlayButton.show();
        vid.currentTime(0);
        window.location.reload();
      });
      vid.Resume({
        uuid: miid  
      });
        $(document).ready(function() {
            var miid = $("video[id^=videofile-]").attr("id");
            $("#page-mod-videofile-view .videofile .vjs-tech").on("contextmenu",function(e) {
              return false;
            });
            
            $("#page-mod-videofile-view .videofile .vjs-tech").bind("cut copy paste",function(e) {
              e.preventDefault();
            });
            
            $(document).on("keydown keypress", "ctrl+s", function() {
              $("#save").click();
              return false;
            });

            $(document).on("keydown keypress", "ctrl+p", function() {
              return false;
            });

            /*Pause and Play using P keyword*/
            $(document).bind("keydown keypress", function(e) {  
              if (e.keyCode === 32 || e.keyCode === 80) {
                if($('.video-js').hasClass('vjs-playing')){
                  $('video').trigger('pause');
                }else{
                  $('video').trigger('play');
                }
              }
            });
          
            $(document).keydown(function(event){  

              var key = event.which; 
              var currnetvl = $('.vjs-volume-bar').attr('aria-valuenow');   
              var vid = document.getElementById("video[id^=videofile-]");

              switch(key) {
                case 38:
                  var currnetvl = $("video").prop('volume');
                  if(currnetvl <= '1'){
                    currnetvl = currnetvl + 0.1;
                    $("video").prop("volume", currnetvl);
                    $('.vjs-volume-bar').attr('aria-valuenow',currnetvl); 
                  } 
                break;
                case 40:
                  var currnetvl = $("video").prop('volume');
                  if(currnetvl > '0'){
                    currnetvl = currnetvl - 0.1;
                    $("video").prop("volume", currnetvl);
                    $('.vjs-volume-bar').attr('aria-valuenow',currnetvl);
                  }
                break;
              }   
            });

            $(document).keydown(function(event){  
              var miid_seek = $("video[id^=videofile-]").attr("id");
              var key = event.which; 
              var video_current_time = videojs(miid_seek).currentTime();
              
              switch(key) {
                case 37:
                  video_current_time = video_current_time - 3;
                  videojs(miid_seek).currentTime(video_current_time);
                break;
                case 39:
                  video_current_time = video_current_time + 3;
                  videojs(miid_seek).currentTime(video_current_time);
                break;
              }  
            });
        });
      }
    }
  };
});