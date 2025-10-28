define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, ajax, notification, str) {
	return {
		init: function() {

			window.addEventListener("load", function () {
				$(document).ready(function() {
					var lstr = str.get_string("file_type_not_supported","mod_videofile");
					
					
			        //File formate validation message
			        if($("#id_error_videos").text().trim() !="" ){
			        	$.when(lstr).done(function(msg) {
				          	$("#video-upload-button .pad-no label").html(msg).css("color","#f55145");
				        });
			        	
			        	$("#upload-video-button").removeClass("disabled");
			        }

			        $("#upload-video-button").click(function(){
			        	if( $(this).hasClass("disabled") ) return;
			        	if($("#video-upload-hidden .fm-loaded ").hasClass("fm-noitems") 
			        		|| $("#video-upload-hidden .fm-loaded ").hasClass("fm-nomkdir") ){
			        		$("#video-upload-hidden .dndupload-arrow").click();
			        	} else {
			        		$("#video-upload-hidden .fp-file").click();
			        	}
			    	})

			        $("#upload-posters-button").click(function(){
			        	if($("#posters-upload-hidden .fm-loaded ").hasClass("fm-noitems") 
			        		|| $("#posters-upload-hidden .fm-loaded ").hasClass("fm-nomkdir") ){
			        		$("#posters-upload-hidden .dndupload-arrow").click();
			        	} else {
			        		$("#posters-upload-hidden .fp-file").click();
			        	}
			    	})

			        //Poster Image Delete
			        $("#posters-upload-placeholder").click(function(){  
			        	if($("#posters-upload-hidden .fp-filename").text().trim() != "" && $("#posters-upload-hidden .fp-filename").text().trim() != "Files"){
			        		if($("#posters-upload-placeholder").hasClass("posters-placeholder")){
			        			$("#posters-upload-hidden .fp-filename").click();
			        		}
			        	}
			        })

			        $("#upload-captions-button").click(function(){
			        	if($("#captions-upload-hidden .fm-loaded ").hasClass("fm-noitems")
			        		|| $("#captions-upload-hidden .fm-loaded ").hasClass("fm-nomkdir") ){
			        		$("#captions-upload-hidden .dndupload-arrow").click();
			        	} else {
			        		$("#captions-upload-hidden .fp-file").click();
			        	}
			    	})

			       /**
			        * Delete upload video using moodle file manager
			        * @author Dnyaneshwar K,
			        * @since 24-04-2019
			        * @ticket #389
			        */
			        //Video Delete
			        $("#video-upload-placeholder").click(function(){
			        	if($("#video-upload-hidden .fp-filename").text().trim() != "" && $("#video-upload-hidden .fp-filename").text().trim() != "Files"){
			        		if($("#video-upload-placeholder").hasClass("video-placeholder")){
			        			$("#video-upload-hidden .fp-filename").click();
			        		}
			        	}
			        })

			        /**
			         * Video Validation on form submit
			         * Delete upload video using moodle file manager
			         * @author Dnyaneshwar K,
			         * @since 24-04-2019
			         * @ticket #389
			         */
			         var pstr = str.get_string("file_type_not_supported","mod_videofile");
			         $("#id_submitbutton2").click(function(){                    
			         	if($("#video-upload-placeholder").text().indexOf("Upload") == 0 && $("#id_video_url").val() == ""){
			         		$.when(pstr).done(function(msg) {
				          		$("#video-upload-button .pad-no label").html(msg).css("color","#f55145");
				        	});
			         		
			         		return false;
			         	}

			         })

			        /**
			         * video_enabled input values switched and the id_video_url 
			         * value set empty when we select a video
			         * @author Hugo S.
			         * 07-06-2018
			         * @paradiso
			         * @ticket 8
			         */
		        	window.setInterval(function(){
			            // Remove style and class attribute on poster delete action
			            if($("#posters-upload-placeholder").text().indexOf("Choose") !== -1){

			            	if($("#posters-upload-placeholder").attr("style")){

			            		$("#posters-upload-placeholder").removeClass("posters-placeholder");
			            		$("#posters-upload-placeholder").removeAttr("style");
			            	} 
			            	if($("#posters-upload-hidden .fm-loaded .ygtvtable").hasClass("fp-folder")){

			            		$("#posters-upload-placeholder").text($("#posters-upload-hidden .fp-filename").text());
			            	}
			                //Click only file details view
			                if($("a.fp-vb-details")[0].length > 0){
			                	$("a.fp-vb-details")[0].click();
			                }
			            } 

			            /**
			             * Remove style and class attribute on video delete action
			             * @author Dnyaneshwar K,
			             * @since 24-04-2019
			             * @ticket #389
			             */
			             
			             if($("#video-upload-placeholder").text().indexOf("Upload") !== -1 || $("#video-upload-placeholder").text().indexOf("Files") !== -1){
			             	if($("#video-upload-placeholder").attr("style")){
			             		$("#video-upload-placeholder").removeClass("video-placeholder");
			             		$("#video-upload-placeholder").removeAttr("style");
			             	} 
			             	if($("#video-upload-hidden .fm-loaded .ygtvtable").hasClass("fp-folder")){
			             		var t = $("#video-upload-hidden .fp-filename").text();
			             		$("#video-upload-placeholder").text(t);
			             	}
			                //Click only file details view
			                if($("a.fp-vb-details")[0].length > 0){
			                	$("a.fp-vb-details")[0].click();
			                }
			            }

			            if($("#video-upload-hidden .fm-loaded").hasClass("fm-noitems")){
			            	var localstr = str.get_string("upload_video_placeholder","mod_videofile");
			            	$.when(localstr).done(function(msg) {
					          $("#video-upload-placeholder").text(msg);
					        });
			            	
			            	$("#id_video_url").prop("disabled", false);
			            	$("input[name=\'video_enabled\']").val("0");
			            } else {
			            	if($("#video-upload-hidden .fp-filename").text().trim() != "" && $("#video-upload-hidden .fp-filename").text().trim() !== "Files"){
			            		$("#video-upload-placeholder").addClass("video-placeholder");
			            		$("#video-upload-placeholder").text($("#video-upload-hidden .fp-filename").text()).css({"color":"#1ba2dd","cursor":"pointer"});
			            	}
			            }

			            if($("#id_video_url").val().trim() != ""){
			            	$("#upload-video-button").addClass("disabled");
			            } else {
			            	$("#upload-video-button").removeClass("disabled");
			            }
			            var localstr = str.get_string("choose_file","mod_videofile");	
			            if($("#posters-upload-hidden .fm-loaded ").hasClass("fm-noitems")){
			            	$.when(localstr).done(function(txt) {
					          $("#posters-upload-placeholder").text(txt);
					        });
					        $("#upload-posters-button").prop("disabled", false);
			            } else {
			            	if($("#posters-upload-hidden .fp-filename").text(localstr) != ""){
			            		$("#posters-upload-placeholder").addClass("posters-placeholder");
			            		$("#posters-upload-placeholder").text($("#posters-upload-hidden .fp-filename").text()).css({"color":"#1ba2dd","cursor":"pointer"});
			            	}
			            	$("#upload-posters-button").prop("disabled", true)
			            }

			            if($("#captions-upload-hidden .fm-loaded ").hasClass("fm-noitems")){
			            	$.when(localstr).done(function(txt) {
					          $("#captions-upload-placeholder").text(txt);
					        });
			            	
			            } else {
			            	if($("#captions-upload-hidden .fp-filename").text().trim() != ""){
			            		$("#captions-upload-placeholder").text($("#captions-upload-hidden .fp-filename").text());
			            	}
			            }

    				}, 1000);

				})
			}, false)
		}
	};
});