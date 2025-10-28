define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, ajax, notification, str) {
  return {
    init: function(cmids,courseid,videoprogress,noforward,videoswitcher,videofileurl,videourlswitch,wwwroot) {
        var attempt;    
        no_save = false;
        var promises = ajax.call([{
        methodname : 'mod_videofile_data_for_video_attempt',
        args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: 0, second: 0, course: 0, id: 0}}
        ]);
        promises[0].done(function(response) {
        var data = JSON.parse(JSON.stringify(response));
        attempt = data;
        var attempts = JSON.parse(JSON.stringify(attempt.last));
        var miid_seek = $('video[id^=videofile-]').attr('id');  
        if(attempts[0].second > 0){   
            /*I have comment below code beacuse when video forward setting is enable , video started from last point*/  
				/*document.getElementById(miid_seek).currentTime = attempts[0].second;    
				document.getElementById(miid_seek).play();*/    
  			}

        attemptlastsid = ( typeof(attempt.last[0].id) != 'undefined' ) ? attempt.last[0].id : 0;
        attemptcurrentsid = ( typeof(attempt.current_id) != 'undefined' ) ? attempt.current_id : 0;
         }).fail(function(exception) {
             fail: notification.exception
         });
         var count = 0;  
         var flag = 1;
         var attemptid = 0;   
         var miid = $('video[id^=videofile-]').attr('id');   
         videojs(miid).on('ended', function(e){  
             e.preventDefault();   
             var videotime = video[0].duration;    
             clearInterval(stopattempt);   
             video = document.getElementsByTagName('video');   
             percentage = (100 / video[0].duration) * video[0].currentTime;
                 
             attemptid = ( typeof(attempt.last) != 'undefined' ) ? attempts[0].id : attempt.current_id;  
             if(videoprogress <= percentage && flag ){ 
                 count = 1;    
                 flag = 0; 
             } else {  
                count = 1;    
              }
              var promises = ajax.call([{
                  methodname : 'mod_videofile_data_for_video_attempt',
                  args: { attempt_id: attemptid, countstatus: count, cmid: cmids, seekval: 0, percentage: parseInt(100), second: video[0].duration, course: courseid, id: 0}}
                  ]);
              promises[0].done(function(response) {
                  var data = JSON.parse(JSON.stringify(response));
                  if(data.status == 'completed'){   
                      $('#module-'+cmids).find('.autocompletion img').remove();   
                      $('#module-'+cmids).find('.autocompletion').append('<img class=icon src='+wwwroot+'/theme/image.php/paradiso/core/1564492370/i/completion-manual-y>');    
                      window.location.reload();   
                  }
              }).fail(function(exception) {
               fail: notification.exception
              });
          }); 
          var stopattempt = setInterval(function(){    
            video = document.getElementsByTagName('video'); 
            percentage = (100 / video[0].duration) * video[0].currentTime;  
            if(percentage > 98 ){   
               percentage = 100;   
            }   

              if(percentage > 0 ){    
                 if(videoprogress <= percentage && flag ){   
                     count = 1;  
                     flag = 0;   
                 } else {    
                     count = 0;
                     count = 1;  
                 }

                  var promises = ajax.call([{
                  methodname : 'mod_videofile_data_for_video_attempt',
                  args: { attempt_id: attemptcurrentsid, countstatus: count, cmid: cmids, seekval: 0, percentage: parseInt(percentage), second: parseInt(video[0].currentTime), course: courseid, id: attemptlastsid}}
                  ]);
                  promises[0].done(function(response) {
                  var result = JSON.parse(JSON.stringify(response));  
                  if(result.status == 'completed'){
                      $('#module-'+cmids).find('.autocompletion img').remove();   
                      $('#module-'+cmids).find('.autocompletion').append('<img class=icon src='+wwwroot+'/theme/image.php/paradiso/core/1564492370/i/completion-manual-y>');    
                  }
              }).fail(function(exception) {
               fail: notification.exception
              });
            }   
          }, 5000);   
          videojs(miid).on('pause', function () { 
            clearInterval(stopattempt);   
          }); 
          videojs(miid).on('play', function () {  
            setInterval(stopattempt); 
          }); 

         if(noforward ==1){
          get_video = document.getElementsByTagName('video');  
          reseek = true;  
                  //If the user is seeking not save the progress  
                  get_video[0].onseeking = function(){    
                  	if(reseek == false){    
                         no_save = true; 
                     }   
                 }   
                 get_video[0].onseeked = function(){ 
                  if(reseek == false){    
                      reseek = true;  
                  } else {    
                      seek = true;
                      var promises = ajax.call([{
                          methodname : 'mod_videofile_data_for_video_attempt',
                          args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: get_video[0].currentTime, percentage: 0, second: 0, course: 0, id: 0}}
                          ]);
                      promises[0].done(function(response) {  
                          no_save = false;    
                          reseek = false;
                      }).fail(function(exception) {
                          fail: notification.exception
                      });    
                  }   
              }
          }
          if(videoswitcher == 1){
              //videojs(miid).videoJsResolutionSwitcher();    
              var player = videojs(miid, {    
                  plugins: {  
                      videoJsResolutionSwitcher: {    
                          default: 'low', 
                          dynamicLabel: true  
                      }   
                  }   
              }); 
              player.ready(function(){
              player.updateSrc([  
              {   
                  src: videofileurl, 
                  type: 'video/mp4',  
                  label: 'HD' 
              },  
              {   
                  src: videourlswitch, 
                  type: 'video/mp4',  
                  res: 480,   
                  label: 'SD' 
              }   
              ]); 
              player.on('resolutionchange', function(){   
                  console.info('Source changed to %s', player.src())  
              });
            });
          }

        }
    };
});