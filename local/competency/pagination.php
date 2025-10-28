<?php
//main heading pagination
function custompagination($selectPageNo,$pages,$tabcase){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="mainheading.php?selectPageNo='.$prev.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="mainheading.php?selectPageNo='.$j.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $prev+1;
  $pagination .='<li class="page-item"><a href="mainheading.php?selectPageNo='.$next.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}
//sub competency pagination 
function custompagination1($selectPageNo, $pages, $mainid, $subcid, $tabcase){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="subcompetency.php?selectPageNo='.$prev.'&svmainid='.$mainid.'&svsubid='.$subcid.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="subcompetency.php?selectPageNo='.$j.'&svmainid='.$mainid.'&svsubid='.$subcid.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="subcompetency.php?selectPageNo='.$next.'&svmainid='.$mainid.'&svsubid='.$subcid.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}

//sub sub competenecy
function custompagination2($selectPageNo, $pages,  $subcid, $subccid, $tabcase){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="subsubcompetency.php?selectPageNo='.$prev.'&svsubid='.$subcid.'&svsubsubid='.$subccid.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="subsubcompetency.php?selectPageNo='.$j.'&svsubid='.$subcid.'&svsubsubid='.$subccid.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="subsubcompetency.php?selectPageNo='.$next.'&svsubid='.$subcid.'&svsubsubid='.$subccid.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}
//view competenecy pagination
function custompagination3($selectPageNo,$pages,$tabcase){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right!important;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="viewcompetency.php?selectPageNo='.$prev.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="viewcompetency.php?selectPageNo='.$j.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="viewcompetency.php?selectPageNo='.$next.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}
//Report
function viewreportpagination($selectPageNo,$pages,$userid='',$ctid='',$ccid='',$competenciesid='',$rateid=''){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="userwisereport.php?selectPageNo='.$prev.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="userwisereport.php?selectPageNo='.$j.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="userwisereport.php?selectPageNo='.$next.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}

function viewuserreportpagination($selectPageNo,$pages,$userid='',$ctid='',$ccid='',$competenciesid='',$rateid=''){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="userreport.php?selectPageNo='.$prev.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="userreport.php?selectPageNo='.$j.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="userreport.php?selectPageNo='.$next.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}

function viewmanagerreportpagination($selectPageNo,$pages,$userid='',$ctid='',$ccid='',$competenciesid='',$rateid=''){
$activecls = '';$pagination='';
$pagination .='<nav aria-label="Page navigation example" style="float:right;">
  <ul class="pagination">
    <li class="page-item">';
if($selectPageNo>=2){
	$prev =$selectPageNo;
	$prev = $prev-1;
  $pagination .='<li class="page-item"><a href="managerwisereport.php?selectPageNo='.$prev.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" /><<</a></li>';   
}  
for($i=1,$j=$selectPageNo;$i<=$pages;$i++,$j++){
	if($j <= $pages){
		if($i<4){
			if($j==$selectPageNo){
				$activecls = 'active';
			}else{
				$activecls = '';
			}
			$pagination .= '<li class="page-item '.$activecls.'"><a href="managerwisereport.php?selectPageNo='.$j.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />'.$j.'</a></li>';
		}	
	}
	
}
if($selectPageNo<$pages){
	$next =$selectPageNo;
	$next = $next+1;
  $pagination .='<li class="page-item"><a href="managerwisereport.php?selectPageNo='.$next.'&userid='.$userid.'&svmainid='.$ctid.'&svsubid='.$ccid.'&svsubsubid='.$competenciesid.'&tearmsid='.$rateid.'" class="page-link" />>></a></li>';   
}
$pagination .='</ul></nav>';
return $pagination;
}
?>