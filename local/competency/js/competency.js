/* Js Main Heading*/
function subhead(str){
	document.getElementById("competencyAddfms").submit();
}
function competencyHeadingDelete(cid){
$('#deletecid').val(cid);
}
function competencyHeadingEdit(cid){
	 $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            cid: cid,
			ccase:'competencyHeading'
		},
        success: function (response) {
			 var resp = JSON.parse(response);
            $("#editcid").val(resp.cid);
			$("#competencyEditHeading").val(resp.title);
        },
        error: function (e, msg) {
        }
    });
}
/* Js compentency category*/
function competencyCategoryDelete(ccid){
$('#deletecompetencyid').val(ccid);
}
function competencyCategoryEdit(cid){
	 $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            cid: cid,
			ccase:'competencyCategory'
		},
        success: function (response) {
			 var resp = JSON.parse(response);
            $("#editccid").val(resp.ccid);
			 $("#catid").val(resp.catid);
			$("#competencyEditCategory").val(resp.title);			
			$('#mainHeadingEdit').html(resp.headingdata);
			$('#editcourseid').html(resp.courseid);
			$('#buEdit').html(resp.budata);
			$('#editroleid').html(resp.roleid);
        },
        error: function (e, msg) {
        }
    });
}

/*JS sub competency*/
function competencySubCategoryDelete(ccid){
$('#deleteccsid').val(ccid);
}
function competencySubCategoryEdit(cid){
	 $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            cid: cid,
			ccase:'competencySubCategory'
		},
        success: function (response) {
			 var resp = JSON.parse(response);
            $("#editccsid").val(resp.ccid);
			$("#editCompetencySubCategory").val(resp.title);
			$('#compentencyCategoryEdit').html(resp.categorydata);
			$('#editSubCourseid').html(resp.courseid);
			//$('#editroleid').html(resp.roleid);
        },
        error: function (e, msg) {
        }
    });
}
//view compentency filter JS
function filtclickfun(){
 
	var svbuid= $('#svbuid :selected').val();
	var svroleid= $('#svroleid :selected').val();	
	if(svbuid ==''){
		$('#errormessage').show();
		$('#errormessage').html("Please select bussiness unit and role!");
		setTimeout(function() {
			$('#errormessage').hide();
		}, 3000);
	}
    
    // else if(svroleid ==''){
	// 	$('#errormessage').show();
	// 	$('#errormessage').html("Please select bussiness unit and role!");
	// 	setTimeout(function() {
	// 		$('#errormessage').hide();
	// 	}, 3000);
	// }
    
    else{
  $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            roleid: svroleid,
			buid :svbuid,
			ccase:'viewcompetency'
        },
        success: function (response) {
			$('#errormessage').hide();
            $("#accordionEx78").html(response);
        },
        error: function (e, msg) {
			
        }
    });
	}
}
function getcourses(cid,cctid){
   $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
            cid: cid,
			cctid:cctid,
			ccase:'viewcompetencycourse'
        },
        success: function (response) {
            $(".courselistclass").html(response);
        },
        error: function (e, msg) {
        }
    });
}

//view compentency filter JS

function subcompetencyfunc(hiddeuserId,step,hiddenmainId,hiddensubcompId,hiddensubsubcompId,hiddenroleId,chkevent){	
 if(chkevent == 4){
	$.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
			userId: hiddeuserId,
            mainId: hiddenmainId,
			subcompId :hiddensubcompId,
			roleId :hiddenroleId,
			subsubcompId:hiddensubsubcompId,
			ccase:'approvalformAdd'
        },
        success: function (response) {
			//$('#errormessage').hide();
            $(".errormessage").html(response);
        },
        error: function (e, msg) {
			
        }
    });
 }else{
	 $.ajax({
        type: "POST",
        url: "competencyAjax.php",
        data: {
			userId: hiddeuserId,
            mainId: hiddenmainId,
			subcompId :hiddensubcompId,
			roleId :hiddenroleId,
			subsubcompId:hiddensubsubcompId,
			ccase:'approvalformDelete'
        },
        success: function (response) {
			//$('#errormessage').hide();
            $(".errormessage").html(response);
        },
        error: function (e, msg) {
			
        }
    });
 }
}



 setTimeout(function() {
    $('.successmessgae').fadeOut('fast');
 }, 3000);