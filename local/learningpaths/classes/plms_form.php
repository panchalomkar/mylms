<?php

class plms_form
{

	private $javascript = '';

	public function fieldGeneralCheckbox($name='', $checked ='', $label='', $help,$attr=null) {
      global $OUTPUT,$CFG;

      $labelactive ='';
      if($checked) {
        $labelactive = ' active ';
      }
      $templatecontext = array(
        'site_url' => $CFG->wwwroot,
        'labelactive' => $labelactive,
        'label'=>$label,
        'attr'=>$attr,
        'help'=>$help,
        'img_src'=>$OUTPUT->pix_url('help'),
        'help_component'=>$help->component,
        'current_language'=>current_language(),
        'helpidentifier'=>$help->identifier,
        'checked'=>$checked,
        'name'=>$name
      );
      return $OUTPUT->render_from_template('local_learningpaths/plms_form', $templatecontext);
	}
}

