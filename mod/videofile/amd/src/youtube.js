define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, ajax, notification, str) {
    return {
        init: function(cmids,courseid,videoprogress,width,height,youtubeurl,wwwroot) {
            var attempt;   
            save = false;   
            seek_to = 0;    
            var tag = document.createElement('script');
            $(document).ready(function() { 
                tag.src = "https://www.youtube.com/iframe_api";  
                var firstScriptTag = document.getElementsByTagName('script')[0];    
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);    
                var player;
            });
            window.onYouTubeIframeAPIReady = function() {
                player = new YT.Player('plms-video', {  
                    height: height,   
                    width: width, 
                    videoId: youtubeurl, 
                    events: {   
                        'onReady': onPlayerReady,   
                        'onStateChange': onPlayerStateChange    
                    }   
                }); 
            } 
                  
            function onPlayerReady(event) {  
                ajax.call([{
                    methodname : 'mod_videofile_data_for_video_attempt',
                    args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: 0, second: 0, course: 0, id: 0},
                    done: function(response){
                        var res = JSON.parse(JSON.stringify(response)); 
                        res.last.second = parseInt(res.last.second);    
                        attempt = res;  
                        if(res.last.second > 0){    
                            player.seekTo(res.last.second); 
                        }  
                    },
                    fail: notification.exception
                }]); 
            }   
                
            var done = false;   
            function onPlayerStateChange(event){
               if(event.data == YT.PlayerState.PLAYING && event.data != YT.PlayerState.BUFFERING && !done){ 
                    ajax.call([{
                        methodname : 'mod_videofile_data_for_video_attempt',
                        args: { attempt_id: 0, countstatus: 0, cmid: cmids, seekval: 0, percentage: 0, second: 0, course: courseid, id: 0},
                        done: function(response){
                            var res = JSON.parse(JSON.stringify(response)); 
                            res.last.second = parseInt(res.last.second);    
                            attempt = res;   
                        },
                        fail: notification.exception
                    }]);    
                 
                    var count = 0;
                    var flag = 1;   
                    setInterval(function(){ 
                        percent = parseInt((100 / player.getDuration()) * player.getCurrentTime());   
                        attemptid =( typeof(attempt.current_id) != 'undefined' ) ? parseInt(attempt.current_id) : 0; 
                        seconds = parseInt(player.getCurrentTime());  
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
                            args: { attempt_id: attemptid, countstatus: count, cmid: cmids, seekval: 0, percentage: percent, second: seconds, course: courseid, id: attempt.current_id},
                            done: function(response){ 
                                var result = JSON.parse(JSON.stringify(response));
                                if(result.status == 'completed'){   
                                    $('#module-'+cmids).find('.autocompletion img').remove();   
                                    $('#module-'+cmids).find('.autocompletion').append('<img class=icon src='+wwwroot+'/theme/image.php/paradiso/core/1564492370/i/completion-manual-y>');  
                                }   
                            },
                            fail: notification.exception
                        }]);  
                        }, 5000);   
                    done = true;    
                }   
                                
                if (event.data == YT.PlayerState.ENDED){
                    percent = (100 / player.getDuration()) * player.getCurrentTime();    
                    seconds = player.getCurrentTime();  
                    if(percent > 98){    
                        player.seekTo(5);   
                        player.pauseVideo();    
                    } 
                }   
            }
        }
    };
});