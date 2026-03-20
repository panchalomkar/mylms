<?php

class plms_form
{

	private $javascript = '';

	public function fieldGeneralCheckbox($name='', $checked ='', $label='', $help,$attr=null)
		{
		global $OUTPUT,$CFG;

		$labelactive ='';
		if($checked) 
		{
			$labelactive = ' active ';
		}

		if(!empty($label)){
			$output  = html_writer::start_tag('label', array('class' => 'form-checkbox form-normal form-icon-text form-plms '.$labelactive));
			$output .= html_writer::tag('span', $label );
		}else{
			$output  = html_writer::start_tag('label', array('class' => 'form-check-label'.$labelactive));
		}


		if(!$attr)
		{
			$output .= html_writer::empty_tag('input', array('class' => 'form-check-input ','type' => 'checkbox', 'name' => $name, 'value' => 1, $checked => $checked, 'id' => 'id_' . $name));
			$output .= html_writer::start_tag('span', array('class' => 'form-check-sign'));
				$output .= html_writer::tag('span','', array('class' => 'check'));
			$output .= html_writer::end_tag('span');

		}else
		{
			$output .= html_writer::empty_tag('input',$attr);
		}

		if(!empty($help)){
		    $output .= html_writer::start_tag('span', array('class' => 'helptooltip'));
		    	$output .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component='.$help->component.'&identifier='.$help->identifier.'&lang='.current_language()));
		    		$output .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
		    	$output .= html_writer::end_tag('a');
		    $output .= html_writer::end_tag('span');
		}
		$output .= html_writer::end_tag('label');

		return $output;
		}
}

