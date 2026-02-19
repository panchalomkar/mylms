var config = ''; 
alert("Hello");
if (!window.jQuery) {
    
	var script = document.createElement("SCRIPT");
    script.src = 'https://code.jquery.com/jquery-1.11.3.js';
    script.type = 'text/javascript';
    script.onload = function() {
        var $ = window.jQuery;

		$(document).ready(function(){
			config = M.cfg; 
			$('a.star').click(function(e){
				e.preventDefault();
				e.stopPropagation();

				destacar(this);

			});
			$('a.delete').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				deletereport(this);
			});
			/*$('.loadiframe').click(function(e){
				e.preventDefault();
				e.stopPropagation(); 
				$('#charts').fadeOut('fast');
				//cargar($(this).attr('href'),'#iframecontent');
				var i='<iframe src="'+$(this).attr('href')+'" name="iframen" id="iframen" style="border: 0px; overflow: hidden; width: 100%; height: 100%" onload="resize()" frameborder="0"></iframe>';
				$('#iframecontent').html(i);
			});/**/ 
			
			/*$('.panel-title a').click(function(e){
				//alert('hola');
				e.preventDefault();
				e.stopPropagation();
				animateaccordion(this);
			});
			$('.panel-title a').trigger( "click" );
			$('.subtitle').click(function(){
				jQuery(this).parent().children('.opt').animate({
					top: "+=50",
					height: "toggle"
				}, 700, function() {
				});
			});/**/
			$('#searchform').submit(function(e){
                            
				e.preventDefault();
				e.stopPropagation();
				searchreport();
				return false;
			});	
			$('#searchbutton').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				searchreport();
				return false;
			});

		});
    };
    document.getElementsByTagName("head")[0].appendChild(script);
}


function animateaccordion(obj){
	var idtab = jQuery(obj).attr('href');
	jQuery(idtab).animate({
		top: "+=50",
		height: "toggle"
	}, 700, function() {
	});
}
function deletereport(obj){ 
	bootbox.confirm(confirm_delete, function(result) {
		if (result) {
			var url=config.wwwroot+'/local/lms_reports/actions.php';
			var res = $(obj).attr('alt').split('-');
			//alert($(obj).attr('alt'));
			var id=res[0];
			var idcr = res[1];
			var parametros= {}
			parametros.id= id;
			parametros.idcr=idcr;
			parametros.task='delete';
			jQuery.getJSON(
				url,
				parametros,
				function(r1){
					if(r1.success){ 
						window.location.href = $(obj).attr('href');
					}
				}
			);
		} 

	}); 
}


function reload(){
	document.location.reload(true);
}
function resize(){
    if (document.all){
            $("#iframen").css('height',window.frames.iframen.document.body.scrollHeight + 20);
    }else{
            $("#iframen").css('height',window.frames.iframen.document.body.offsetHeight + 20);
    }
}
function searchreport(){
	var txt = $('#txt').val();
	txt=txt.trim(); 
	var url=config.wwwroot+'/local/lms_reports/actions.php';  
	var parametros= {}
	parametros.txt= txt; 
	parametros.task='searchreport';
	jQuery.getJSON(
		url,
		parametros,
		function(r1){
			if(r1.success){ 
				//console.log(r1.menu);
				$('.accordion#accordion').html( r1.menu);
				
				$(document).ready(function(){
					$('a.star').click(function(e){
						e.preventDefault();
						e.stopPropagation();
						destacar(this);
					});  
					$('a.delete').click(function(e){
						e.preventDefault();
						e.stopPropagation();
						deletereport(this);
					});
					/*/$('.panel-title a').click(function(e){
						e.preventDefault();
						e.stopPropagation();
						animateaccordion(this);
					});/**/
					//$('.panel-title a').trigger( "click" );
				});
			}
		}
	);  
}

function checkiframe() { }
