define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, ajax, notification, str) {
  return {
    init: function(cmids,forward,courseid,videoprogress,wwwroot) {
    	// Call to ajax for create the video attempt    
        var attempt;    
        save = false;   
        seek = false;   
        no_reseek = false;  
        var flag = 1;   
        attempt;    
        //This script is for vimeo progress 
        $(function() {  
        //Get the iframe of the video   
        var lastsecond = 0; 
        var iframe = $('#plms-video')[0];   
        var player = $('iframe');   
        var playerOrigin = '*'; 
        
        var vimeoplayer = new Vimeo.Player(iframe); 
        vimeoplayer.ready().then(function() {   
        onReady();  
        }).catch(function(error) {  
            alert(error.message);   
        }); 
        vimeoplayer.on('play', function(data) { 
            onPlayProgress(data);   
        }); 
        vimeoplayer.on('progress', function(data) { 
            onPlayProgress(data);   
        }); 
        vimeoplayer.on('pause', function(data) {    
            onPlayProgress(data);   
            //onPause();    
        }); 
        vimeoplayer.on('ended', function(data) {    
            onPlayProgress(data);   
        //onFinish();   
        });  
        // Helper function for sending a message to the player  
        function post(action, value) {  
            var data = {    
                method: action  
            };  
            if (value) {    
                data.value = value; 
            }   
            var message = JSON.stringify(data); 
            player[0].contentWindow.postMessage(data, playerOrigin);    
        }   
        function onReady() {    
            post('addEventListener', 'playProgress');   
            
            if(forward>0){ 
                post('addEventListener', 'seek');
            }

            ajax.call([{
                methodname : 'mod_videofile_data_for_video_attempt',
                args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: 0, second: 0, course: 0, id: 0},
                done: function(response){
                var data = JSON.parse(JSON.stringify(response));   
                attempt = data;
                var attempts = JSON.parse(JSON.stringify(attempt.last)); 
                        if(attempts.second > 0){   
                            post("seekTo", attempts.second); 
                            post('play');   
                        }   
                    },
                fail: notification.exception
            }]);  
        }   
        //Save the progress in the database 
        function onPlayProgress(data) { 
            if(save == true || seek == true){   
                return false;   
            }   
            var percent = parseInt(data.percent * 100);  
            if(percent > 98 ){   
                percent = 100;   
            }   
            if(videoprogress <= percent && flag ){   
                count = 1;  
                flag = 0;   
                } else {    
                    count = 0;  
                }

            ajax.call([{
                methodname : 'mod_videofile_data_for_video_attempt',
                args: { attempt_id: 0, countstatus: count, cmid: cmids, seekval: 0, percentage: 0, second: parseInt(data.seconds), course: courseid, id: attempt.current_id},
                done: function(response){ 
                        if(response){   
                            var result = JSON.parse(JSON.stringify(response));
                            if(result.status == 'completed'){   
                                $('#module-'+cmid).find('.autocompletion img').remove();   
                                $('#module-'+cmid).find('.autocompletion').append('<img class=icon src='+wwwroot+'/theme/image.php/paradiso/core/1564492370/i/completion-manual-y>');    
                            }   
                        }   
                    },
                fail: notification.exception
            }]);                      
            }   
        }); 
        //Intervals for save.   
        setInterval(function(){ 
            save = false;   
        }, 30000)
	}
  };
});    	