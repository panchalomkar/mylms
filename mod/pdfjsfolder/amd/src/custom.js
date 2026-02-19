/**
 * DisableForward Html5 Video
 *
 * @module      mod_pdfjsfolder/openfile Html5 Video
 * Author	 	2019 Bhagyavant P.
 */

define(['jquery'], function($) {
    return {
        init: function () {
			var counter = 0;
			jQuery('.pdfjs-folder .fp-filename-icon a[href$=".pdf"]').each(function() {
			   	counter++;
			});		
			if(counter > 1){
				//showing all pdfs
			}else{
				//opening single pdf in new tab
				var go_to_url = $('.pdfjs-folder .fp-filename-icon a[href$=".pdf"]').attr('href');
	  			window.open(go_to_url, '_blank');
			}

		}
    };
});

