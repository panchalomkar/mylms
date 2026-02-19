define(['jquery'], function($) {
  return {
    init: function(width,height) {
    	$(document).ready(function() {    
          setTimeout(function(){  
              $('video').parents('.video-js').first().css('width', width); 
              $('video').parents('.video-js').first().css('height', height);   
              }, 300);    
      });
    }
  };
});