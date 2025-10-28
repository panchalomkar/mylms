<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/competency/lib.php');
global $CFG, $DB, $OUTPUT, $PAGE;
//$id = optional_param('cid','', PARAM_INT);
$case = optional_param('ccase','', PARAM_TEXT);

if($case == 'fitermaincomp'){

		$ctid = required_param('ctid', PARAM_TEXT);
        $getsubcomp = $DB->get_records('competency_category', array('ctid' => $ctid));
        $subselct = '';
        $subselct .= '<select name="svsubid" id="svsubid" class="form-control" onchange="changeSubcomp(this.value)">
            <option value="">Select Sub Competency</option>';
           foreach($getsubcomp as $subcomp) { 
	      $subselct .="<option value='".$subcomp->id."'>".$subcomp->name."</option>";
           }
          $subselct .='</select>';

        echo $subselct;
}
else if($case == 'fitersubcomp'){

		$ccid = required_param('ccid', PARAM_TEXT);
        $getsubsubcomp = $DB->get_records('competencies', array('ccid' => $ccid));
        $subsubselct = '';
        $subsubselct .= '<select name="svsubsubid" id="svsubsubid" class="form-control">
	<option value="">Select Sub Competency</option>';
           foreach($getsubsubcomp as $subsubcomp) { 
	     $subsubselct .="<option value='".$subsubcomp->id."'>".$subsubcomp->comptencyname."</option>";
          }
         $subsubselct .='</select>';
        echo $subsubselct;
}
?>