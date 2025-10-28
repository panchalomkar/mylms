define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, ajax, notification, str) {
  return {
    init: function(cmids) {
    	   var attempt;    
            no_save = false;
            ajax.call([{
                methodname : 'mod_videofile_data_for_video_attempt',
                args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: 0, second: 0, course: 0, id: 0},
                done: function(response){
                var data = JSON.parse(JSON.stringify(response));
                attempt = data;
                var attempts = JSON.parse(JSON.stringify(attempt.last));        
                var miid_seek = $('video[id^=videofile-]').attr('id');  
                if(attempts.second > 0){   
                    document.getElementById(miid_seek).currentTime = attempts.second;  
                    document.getElementById(miid_seek).play();  
                }   
                },
                fail: notification.exception
            }]); 
            setInterval(function(){ 
                video = document.getElementsByTagName('video'); 
                percent = (100 / video[0].duration) * video[0].currentTime;
                ajax.call([{
                    methodname : 'mod_videofile_data_for_video_attempt',
                    args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: parseInt(percent), second: video[0].currentTime, course: courseid, id: attempt.current_id},
                    done: function(response){
                    var data = JSON.parse(JSON.stringify(response));
                    attempt = data;
                    var attempts = JSON.parse(JSON.stringify(attempt.last));   
                        attempt = data; 
                        if(attempts.second > 0){   
                            post("seekTo", attempts.second); 
                            post('play');   
                        }   
                    },
                    fail: notification.exception
                }]);
            }, 10000)
        }
    };
});