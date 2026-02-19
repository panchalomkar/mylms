function filtreport(){
	 var uid= $('#userid :selected').val();
	var mainid= $('#svmainid :selected').val();	
	var subid= $('#svsubid :selected').val();	
	var subsubid= $('#svsubsubid :selected').val();	
	var ratid= $('#tearmsid :selected').val();	
	if(uid ==''){
		$('#errormessage').show();
		$('#errormessage').html("Please select user!");
		setTimeout(function() {
			$('#errormessage').hide();
		}, 3000);
	}else{
  $.ajax({
        type: "POST",
        url: "reportAjax.php",
        data: {
            userid: uid,
			ctid:mainid,
			ccid:subid,
			competenciesid:subsubid,
			rateid:ratid,
			ccase:'userwisereport'
        },
        success: function (response) {
			$('#errormessage').hide();
            $("#competencytable").html(response);
        },
        error: function (e, msg) {
			
        }
    });
	} 
}

function changeMaincomp(buid){    

    if(buid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select main comptency!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }else{
  $.ajax({
        type: "POST",
        url: "reportAjax.php",
        data: {
            ctid: buid,
            ccase:'fitermaincomp'
        },
        success: function (response) {
            $('#errormessage').hide();
            $("#subcompshow").html(response);
        },
        error: function (e, msg) {
            
        }
    });
    }
}
function changeSubcomp(subid){    
    if(subid ==''){
        $('#errormessage').show();
        $('#errormessage').html("Please select sub comptency!");
        setTimeout(function() {
            $('#errormessage').hide();
        }, 3000);
    }else{
  $.ajax({
        type: "POST",
        url: "reportAjax.php",
        data: {
            ccid: subid,
            ccase:'fitersubcomp'
        },
        success: function (response) {
            $('#errormessage').hide();
            $("#cometenciesshow").html(response);
        },
        error: function (e, msg) {
            
        }
    });
    }
}


function enrollcourse(userid, subsubcomptencyid = 0, subcomptencyid = 0){
    $.ajax({
        type: "POST",
        url: "enrollAjax.php",
        data: {
            userid: userid,
            subsubcomptencyid:subsubcomptencyid,
            subcomptencyid:subcomptencyid
        },
        success: function (response) {
            if(response=='true'){
               $('#successmessgae').show();
               $("#successmessgae").html("You enrolled successfully..");
            }
        },
        error: function (e, msg) {
            
        }
    });
}