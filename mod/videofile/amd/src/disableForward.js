/**
 * DisableForward Html5 Video
 *
 * @module      mod_videofile/DisableForward Html5 Video
 * Author	 	2019 J.Rodriguez
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
  return {
    init: function() {
      var miid = $("video[id^=videofile-]").attr("id");
      if (document.getElementById(miid)) {
        videojs(miid).ready(function() {
          var myPlayer = this;
            myPlayer.on('Resume', function() {
                var currentTime = Math.floor(myPlayer.currentTime());

            });

          //This example allows users to seek backwards but not forwards.
          //To disable all seeking replace the if statements from the next
          //two functions with myPlayer.currentTime(currentTime);

          myPlayer.on("seeking", function(event) {
            if (currentTime < myPlayer.currentTime()) {
              myPlayer.currentTime(currentTime);
            }
          });

          myPlayer.on("seeked", function(event) {
            if (currentTime < myPlayer.currentTime()) {
              myPlayer.currentTime(currentTime);
            }
          });

          setInterval(function() {
            if (!myPlayer.paused()) {
              currentTime = myPlayer.currentTime();
            }
          }, 1000);
        });
      }

      $(document).ready(function() {
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
      });
    }
  };
});