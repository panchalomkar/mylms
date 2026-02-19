<?php

class slms_form
{
	private $javascript = '';

	public function fieldTextGroup($label, $attrs)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group row fitem'));
			$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4'));
				$output .= html_writer::tag('label', $label, array('class' => 'control-label'));
			$output .= html_writer::end_tag('div');

			$output .= html_writer::start_tag('div', array('class' => 'class=" col-sm-12 col-md-8 col-lg-8 col-xl-8 felement', 'data-fieldtype' =>'text'));
				$output .= html_writer::tag('input', '', $attrs);
			$output .= html_writer::end_tag('div');
			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}

	public function fieldTextGroupWizard($label, $attrs,$colwidth='')
	{
		
		$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4'));
			if(!empty($label)){
                $output .= html_writer::tag('label', $label, array('class' => 'col-form-label d-inline-block'));
            }
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'col-xs-12 col-sm-12 col-md-8 form-inline felement'));
           	$array = array('class' => 'form-control', 'type' => 'text');
			$output .= html_writer::tag('input', '', array_merge($array, $attrs));
		$output .= html_writer::end_tag('div');		
			
		return $output;
	}

	public function fieldSelectGroup($label, $attrs, $options)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group row fitem'));

			$output .= html_writer::tag('label', $label, array('class' => 'contentlabel col-sm-12 col-md-2 col-lg-2 col-xl-2'));
			
			$output .= html_writer::start_tag('div', array('class' => ' col-sm-12 col-md-10 col-lg-10 col-xl-10 felement'));

				$output .= html_writer::start_tag('select', $attrs);
					foreach ($options as $key => $value) {
						$output .= html_writer::tag('option', $value, array('value' => $key));
					}
				$output .= html_writer::end_tag('select');

			$output .= html_writer::end_tag('div');

			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}


	public function fieldSelectGroupWizard($label, $attrs, $options, $default = null)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));

		$output .= html_writer::tag('label', $label, array('class' => 'col-sm-12 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-12'));

		$output .= html_writer::start_tag('select', $attrs);
		foreach ($options as $key => $value) {

			if($default == $key){

				$output .= html_writer::tag('option', $value, array('value' => $key, 'selected' => 'selected'));

			} else {

				$output .= html_writer::tag('option', $value, array('value' => $key));
			}

		}
		$output .= html_writer::end_tag('select');

		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}

	public function fieldTextareaGroup($label, $attrs, $value)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-4 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-8'));
		$output .= html_writer::tag('textarea', $value, $attrs);
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}

	public function fieldSwitch($label, $value, $attrs = [])
	{
		$attrs['data-switchery'] = 'true';
		$attrs['type'] = 'checkbox';
		if($value == true)
		{
			$attrs['checked'] = 'checked';
		}
		$output = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-4 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-8'));
		$output .= html_writer::empty_tag('input', $attrs);
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));

		//Add the javascript for the switchery
		if(isset($attrs['id']))
			$this->javascript .= "new Switchery(document.getElementById('".$attrs['id']."'));";
		return $output;
	}

	public function fieldColorPicker($label, $value, $id)
	{
        global $PAGE, $OUTPUT;
        $PAGE->requires->js_init_call('M.util.init_colour_picker', array($id));
        $output  = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-4 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-8'));
        $output .= html_writer::tag('div', $OUTPUT->pix_icon('i/loading', get_string('loading', 'admin'), 'moodle', array('class'=>'loadingicon')), array('class'=>'admin_colourpicker clearfix'));
        $output .= html_writer::empty_tag('input', array('type' => 'text', 'id' => $id, 'name' => $id, 'value' => $value, 'size' => '12'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::tag('div', '', array('class' => 'clearfix'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::tag('div', '', array('class' => 'clearfix'));
        return $output;
    }

	public function getJavascript()
	{
		$js = '<script>
				$(document).on("ready", function(){
					' . $this->javascript . '
				})
			</script>';
		return $js;
	}


	public function radioWithImage($name, $value, $title, $image, $checked = false,$columnsizes = 'col-xs-4')
	{
		$output  = html_writer::start_tag('div', array('class' => $columnsizes . ' mar-btm pad-no'));
			$output .= html_writer::start_tag('div', array('class' => 'mar-hor bord-all pad-all'));
				$output .= html_writer::start_tag('div', array('class' => 'col-xs-7 pad-no'));
				$output .= html_writer::empty_tag('img', array('src' => $image, 'alt' => $title, 'class' => 'col-xs-12 pad-no'));
				$output .= html_writer::end_tag('div');
				$output .= html_writer::start_tag('div', array('class' => 'col-xs-5 pad-no pad-lft'));
					$output .= html_writer::start_tag('div', array('class' => 'bord-btm'));
						$output .= html_writer::start_tag('label', array('class' => 'form-radio form-icon form-slms-color form-text')) . $title;
						if($checked){
							$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => $value, 'checked' => 'checked'));
						} else {
							$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => $value));
						}
						$output .= html_writer::end_tag('label');
					$output .= html_writer::end_tag('div');
				$output .= html_writer::end_tag('div');
				$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
			$output .= html_writer::end_tag('div');
		$output .= html_writer::end_tag('div');
		return $output;
	}

	public function radioWithImageCourseWizard($name, $value, $place, $title, $image, $checked = false, $class = 'col-xs-4')
	{
		$output  = html_writer::start_tag('div', array("onclick"=>"ValidateCheckboxWizard('id-course-wizar-img-active_".$name."_".$value."')",'data-placement' => $place, 'data-toggle' => '', 'class' => $class . ' mar-btm pad-no', 'data-value' => $value, 'title' => $title));

		$output .= html_writer::tag('div','',array('id'=>'triangle-class-check-'.$name.'_'.$value,'class'=>'triangle-class-check-course-wizard'));

		if($checked)
		{
			$output .= html_writer::tag('div','', array('id'=>'id-course-wizar-img-active_'.$name.'_'.$value,'class' => 'block-gray-over-img course-wizar-img-active'));
		}else
		{
				$output .= html_writer::tag('div','', array('id'=>'id-course-wizar-img-active_'.$name.'_'.$value,'class'=>'block-gray-over-img'));
		}
			$output .= html_writer::start_tag('div', array('class' => 'mar-hor pad-all'));
				$output .= html_writer::start_tag('div', array('class' => 'col-xs-12 mar-btm course-wizard-text-lable'));
					$output .= html_writer::start_tag('div', array('class' => 'pad-btm'));
						$output .= html_writer::start_tag('label', array('class' => 'form-radio form-icon form-slms-color form-text')) ;
						$output .= html_writer::tag('span', $title ,array()) ;
						if($checked){
							$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => $value, 'checked' => 'checked'));
						} else {
							$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => $value));
						}
						$output .= html_writer::end_tag('label');
					$output .= html_writer::end_tag('div');
				$output .= html_writer::end_tag('div');

				$output .= html_writer::start_tag('div', array('class' => 'pad-no center col-xs-12 course-wizard-img'));
				$output .= html_writer::empty_tag('img', array("onclick"=>"ValidateCheckboxWizard('id-course-wizar-img-active_".$name."_".$value."')",'src' => $image, 'alt' => $title, 'class' => 'check-img-inner pad-no center','style'=>'width: 80%;'));
				$output .= html_writer::end_tag('div');
				$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
			$output .= html_writer::end_tag('div');

		$output .= html_writer::end_tag('div');
		return $output;
	}


  	public function radioWithImageCourseTypeWizard($name, $value, $place, $title, $image, $checked = false, $class = 'col-xs-4', $component = 'block_eledia_coursewizard',$tooltip, $id=''){


  			if (empty($id)) {
            $id = 'radio_' . \theme_remui\widget::randHash(10);
        }
/*		if(empty($tooltip)){
			$output  = html_writer::start_tag('div', array('data-placement' => $place, 'data-toggle' => 'tooltip', 'class' => $class . ' mar-btm pad-no tooltipelement_html', 'data-value' => $value, 'title' => $title));
		}else{
			$output  = html_writer::start_tag('div', array('data-placement' => $place , 'class' => $class . ' mar-btm pad-no tooltipelement_html', 'data-value' => $value));
		}*/

		$output  = html_writer::start_tag('div', array('data-placement' => $place, 'data-toggle' => '', 'class' => $class . ' mar-btm pad-no', 'data-value' => $value, 'title' => $title));


			$output .= html_writer::start_tag('div', array('class' => 'content-graph-one'));
				$output .= html_writer::start_tag('div', array('class' => 'input-graph col-sm-12'));
					
						if($checked){
							$output .= html_writer::empty_tag('input', array('type' => 'radio','id' => $id, 'name' => $name, 'value' => $value, 'checked' => 'checked'));
						} else {
							$output .= html_writer::empty_tag('input', array('type' => 'radio','id' => $id, 'name' => $name, 'value' => $value));
						}
						$output .= html_writer::start_tag('label', array('class' => 'form-radio form-icon form-slms-color', 'for' => $id)) .get_string( $value, $component);
						$output .= html_writer::end_tag('label');
					
				$output .= html_writer::end_tag('div');

				$output .= html_writer::start_tag('div', array('class' => 'pad-no center'));
				$output .= html_writer::empty_tag('img', array('src' => $image, 'alt' => $title, 'class' => 'pad-no center','style'=>'width: 44%;'));
				$output .= html_writer::end_tag('div');
				$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
			$output .= html_writer::end_tag('div');

		$output .= html_writer::end_tag('div');
		return $output;
	}

	public function fieldTextareaGroupCourseWizard($label, $attrs, $value = '')
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group row  fitem'));
			$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-2 col-lg-2 col-xl-2'));
				$output .= html_writer::tag('label', $label, array('class' => 'control-label'));
			$output .= html_writer::end_tag('div');
				
			$output .= html_writer::start_tag('div', array('class' => 'col-sm-12 col-md-10 col-lg-10 col-xl-10 felement'));
				$output .= html_writer::tag('textarea', $value, $attrs);
			$output .= html_writer::end_tag('div');

			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		return $output;
	}


	public function fieldTextareaGroupCourseWizardSteps($label, $attrs, $value, $extraLabel = '')
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group txt_area'));
		$output .= html_writer::tag('label', $label.$extraLabel, array('class' => 'col-sm-12 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-12'));
		$output .= html_writer::tag('textarea', $value, $attrs);
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}

	public function fieldCheckboxWithImage($name, $checked, $image, $label='', $help='')
	{
		global $OUTPUT,$CFG;
		echo html_writer::start_tag('div', array('class' => 'form-group row  fitem'));
			echo html_writer::start_tag('div',array('class'=>'col-sm-12 col-md-12 col-lg-12 col-xl-12'));
				if(!empty($label)){
					$output  = html_writer::start_tag('label', array('class' => 'form-checkbox form-normal form-icon-text form-slms'));
					$output .= html_writer::tag('span', $label,array('class' => 'order-2 mr-3') );
				}else{
					$output  = html_writer::start_tag('label', array('class' => 'form-checkbox form-normal form-slms'));
				}
				if(!empty($image)){
					$output .= html_writer::empty_tag('img', array('src' => $image));
				}
				$output .= html_writer::empty_tag('input', array('class' => 'order-1 mr-2','type' => 'checkbox', 'name' => $name, 'value' => 1, $checked => $checked, 'id' => 'id_' . $name));

				if(!empty($help)){
				    $output .= html_writer::start_tag('span', array('class' => 'helptooltip order-3'));
				    	$output .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component=block_configurable_reports&identifier='.$help.'&lang='.current_language()));
				    		//echo current_language();exit
				    		$output .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
				    	$output .= html_writer::end_tag('a');
				    $output .= html_writer::end_tag('span');
				}
				$output .= html_writer::end_tag('label');
			$output .= html_writer::end_tag('div');
		$output .= html_writer::end_tag('div');
		return $output;
	}

	public function fieldDataPicker($label, $value, $id, $required, $ExtraData)
	{
		
        if($required)
		{
			$label = $label;
			
			$requiredcontent = html_writer::start_tag('div', array('class' => 'options d-inline float-right'));
				$requiredcontent .= html_writer::start_tag('abbr', array('class' => 'initialism text-danger required-element', 'data-inputid' => '', 'title' => 'Required'));
					$requiredcontent .= html_writer::tag('i', '',array('class' => 'icon fa fa-exclamation-circle text-danger fa-fw', 'title' => 'Required', 'aria-label' => 'Required'));
				$requiredcontent.= html_writer::end_tag('abbr');

			$requiredcontent.= html_writer::end_tag('div');
		}else
		{
			$requiredcontent = '';
		}

		$output = html_writer::start_tag('div', array('class' => 'form-group row  fitem'));

			$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4'));
				$output .= html_writer::tag('label', $label, array('class' => 'd-inline-block'));
				
				$output .= $requiredcontent;

			$output .= html_writer::end_tag('div');

			$output .= html_writer::start_tag('div', array('id' => 'dp-component', 'class' => ' col-sm-12 col-md-8 col-lg-8 col-xl-8 felement'));
				
				$output .= html_writer::start_tag('div', array('class'=>'date','data-provide'=>"datepicker-inline-slms", 'data-fieldtype' => 'text') );
					$output .= html_writer::empty_tag('input', $ExtraData);
						
					/*$output .= html_writer::start_tag('span',array('class'=>'input-group-addon'));
						$output .= html_writer::tag('i','',array('class'=>'fa fa-calendar fa-lg'));
					$output .= html_writer::end_tag('span');*/

					$output .= html_writer::start_tag('a', array('id' => $id, 'class' => 'visibleifjs' , 'name' => 'startdate[calendar]', 'href' => '#'));
						$output .= html_writer::tag('i','', array('class' => 'fa fa-calendar fa-fw', 'title' => 'Calendar', 'aria-label' => 'Calendar'));
					$output .= html_writer::end_tag('a');

				$output .= html_writer::end_tag('div');

			$output .= html_writer::end_tag('div');
			
			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));

		return $output;
	}
        
        
           


	public function get_data_post_slms()
	{
		$output = array();
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			foreach ($_POST as $inputName => $inputValue) {
				$output[$inputName] = $inputValue;
			}
		}
		else
		{
			$output = array();
		}

		return $output;
	}
	public function fieldSimpleRadioButton($name, $checked,$label='',$value, $id='')
	{
		if(empty($id)){
			$id='id_' . $name;
		}
		if(!empty($label)){
			$output  = html_writer::start_tag('label', array('class' => 'radiobutons form-radio form-normal form-icon-text form-slms'));
			$output .= html_writer::tag('span', $label, array('class' => 'formatcsv') );
		}else{
			$output  = html_writer::start_tag('label', array('class' => 'form-radio form-normal form-slms'));
		}
		$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => $value, $checked => $checked, 'id' => $id));
		$output .= html_writer::end_tag('label');
		return $output;
	}

	public function fieldTextGroupRequired($label, $required, $attrs,$help_text='')
	{
		
		if($required)
		{
			$label = $label;
			
			$requiredcontent = html_writer::start_tag('div', array('class' => 'options d-inline float-right'));
			$requiredcontent .= html_writer::start_tag('abbr', array('class' => 'initialism text-danger required-element', 'data-inputid' => '', 'title' => 'Required'));
			$requiredcontent .= html_writer::tag('i', '',array('class' => 'icon fa fa-exclamation-circle text-danger fa-fw', 'title' => 'Required', 'aria-label' => 'Required'));
			$requiredcontent.= html_writer::end_tag('abbr');
			$requiredcontent.= html_writer::end_tag('div');
		}else
		{
			$requiredcontent = '';
		}

		$help_button .= html_writer::start_tag('div', array('class' => 'options d-inline float-right'));
                $help_button .= html_writer::start_tag('div', array('class' => 'btn btn-secondary p-a-0 buttonhelp','role' => 'button' ,'data-container' => 'body' ,'data-toggle' => 'popover', 'data-placement' => 'right', 'data-content'=>'<div class="no-overflow">'.$help_text.'</div>', 'data-html' => 'true' ,'tabindex' => '0' ,'data-trigger' => 'focus' ,'data-original-title' => '' ,'title' => ''));
              	$help_button .=html_writer::tag('i', '', array('class' => 'wid wid-icon-helpbutton', 'aria-hidden' => 'true'));
                $help_button .= html_writer::end_tag('div');
                $help_button .= html_writer::end_tag('div');

		$output = html_writer::start_tag('div', array('class' => 'form-group row  fitem'));
			
		$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-2 col-lg-2 col-xl-2'));
		$output .= html_writer::tag('label', $label, array('class' => 'control-label col-form-label  d-inline-block '));
		$output .= $help_button . $requiredcontent;
		$output .= html_writer::end_tag('div');

		$output .= html_writer::start_tag('div', array('class' => 'col-sm-12 col-md-10 col-lg-10 col-xl-10 form-inline felement', 'data-fieldtype'=>'text'));
		$output .= html_writer::tag('input', '', $attrs);
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;

	}

	public function fieldSimpleRadioButtonAttr($label='',$attrs)
	{
		
		$output = html_writer::start_tag('div', array('class' => 'form-group row fitem radiobutons'));
			$output .= html_writer::start_tag('div', array('class' => 'col-sm-12'));
				if(!empty($label)){
					$output  .= html_writer::start_tag('label', array('class' => 'form-radio form-normal form-icon-text form-slms'));
					$output .= html_writer::tag('span', $label );
				}else{
					$output  .= html_writer::start_tag('label', array('class' => 'form-radio form-normal form-slms'));
				}
				$output .= html_writer::empty_tag('input', $attrs );
				$output .= html_writer::end_tag('label');
			$output .= html_writer::end_tag('div');
		$output .= html_writer::end_tag('div');
		return $output;
	}

	public function fieldTextPlusSelectGroup($label,$options,$attrs2,$attrs,$attrs2div)
	{

		$output = html_writer::start_tag('div', array('class' => 'form-group row fitem'));
			$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4 col-lg-4 col-xl-4'));
				$output .= html_writer::tag('label', $label, array('class' => 'control-label'));
			$output .= html_writer::end_tag('div');


			$output .= html_writer::start_tag('div', array('class' => ' col-sm-12 col-md-8 col-lg-8 col-xl-8 felement'));
				$output .= html_writer::start_tag('div', array('class' => 'col-sm-5 pl-0 d-inline-block' , 'data-fieldtype' => 'text'));
					$output .= html_writer::tag('input', '', $attrs);
				$output .= html_writer::end_tag('div');

				$output .= html_writer::start_tag('div', array('class' => 'col-sm-6 d-inline-block'));
					$output .= html_writer::start_tag('select', $attrs2);
						foreach ($options as $key => $value) {
							$output .= html_writer::tag('option', $value, array('value' => $key));
						}	
					$output .= html_writer::end_tag('select');
				$output .= html_writer::end_tag('div');

			$output .= html_writer::end_tag('div');

			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
	 return $output;
	}

	public function fieldTextBelowLabelGroupRequired($label, $attrs)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-4 control-label text-danger text-bold'));
		$output .= html_writer::start_tag('div', array('class' => 'col-lg-8 right'));
		$output .= html_writer::tag('span', get_string('required'), array('class' => 'control-label error hidde_element','id'=>$attrs['id'].'_error_message' ));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('input', '', $attrs);
		$output .= html_writer::end_tag('div');

		return $output;
	}

		public function fieldTextBelowLabelGroup($label, $attrs)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));

		$output .= html_writer::tag('label', $label, array('class' => 'control-label'));
		$output .= html_writer::tag('input', '', $attrs);
		$output .= html_writer::end_tag('div');

		return $output;
	}

	public function fieldSelectByGroupsGroup($label, $attrs, $options,$default)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-2 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-10'));

		$output .= html_writer::start_tag('select', $attrs);
		$selected='-';
		foreach ($options as $key => $value) {
			$output .= html_writer::tag('optgroup', $key, array('label' => $key));
				foreach($value as $keys =>$values)
				{
					if(!empty($default))
					{ if($default==$keys)
						{
							$output .= html_writer::tag('option', $values, array('value' => $keys ,'selected'=>'selected'));
						}else
						{
							$output .= html_writer::tag('option', $values, array('value' => $keys));
						}
					}
					else
					{
						$output .= html_writer::tag('option', $values, array('value' => $keys));
					}
				}
		}
		$output .= html_writer::end_tag('select');

		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}


	public function fieldImageUploaderThubmnail($label,$newdir,$help = array())
	{
		global $PAGE,$CFG,$USER,$OUTPUT ;
		$newdir = trim($newdir,'/') ;

		$PAGE->requires->strings_for_js(array(
	    'drag_drop_images',
	    'click_open_file_browser',
	    'selected_file',
	    'status',
	    'uploading',
	    'upload_complete',
	    'has_not_allowed_extencion',
	    'browser_not_supported',
	    'upload',
	    'click_to_pick_manually'
	        ), 'theme_remui');

		$filesize = get_config('moodlecourse', 'maxbytes') ;

		if ($filesize >= 1073741824) {
		      $filesize = number_format($filesize / 1073741824, 2) . ' GB';
		  } elseif ($filesize >= 1048576) {
		      $filesize = number_format($filesize / 1048576, 2) . ' MB';
		  }
		  elseif ($filesize >= 1024) {
		      $filesize = number_format($filesize / 1024, 2) . ' KB';
		  }
		  elseif ($filesize > 1) {
		      $filesize = $filesize . ' filesize';
		  } elseif ($filesize == 1)
		  {
		      $filesize = $filesize . ' byte';
		  } else
		  {
		      $filesize = '0 bytes';
		  }

		$output= html_writer::tag('script', 'var upload_max_size = "' . get_string('maximum_size', 'theme_raplmsfull').'<br /> '.get_string('upload_multiple_images_size', 'theme_raplmsfull').' '.$filesize.'" ;');
		$output.= html_writer::tag('script', 'var newdir = "' . $newdir .'";');

		$PAGE->requires->js(new moodle_url('/theme/raplmsfull/plugins/ImageUploader/js/lightslider.js'));
		$PAGE->requires->js(new moodle_url('/theme/raplmsfull/plugins/ImageUploader/js/ImageUploader.js'));
		$PAGE->requires->js(new moodle_url('/theme/raplmsfull/plugins/ImageUploader/js/dmuploader.min.js'));

	   	$output.= html_writer::start_tag('div',array('class'=>''));
		   	if($label)
		   	{
		   		if($help)
		   		{

				$helphtml = html_writer::start_tag('span', array('class' => 'helptooltip'));
					$helphtml .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component='.$help['plugin'].'&identifier='.$help['stringname'].'&lang='.current_language()));
						$helphtml .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
					$helphtml .= html_writer::end_tag('a');
				$helphtml .= html_writer::end_tag('span');

				$label .= $helphtml ;

		   		}
			   	$output.= html_writer::start_tag('div',array('class'=>'col-lg-12 fitemtitle'));
			   		$output.= html_writer::tag('label',$label);
			   	$output.= html_writer::end_tag('div');
		   	}
			    $output.= html_writer::start_tag('div', array('id' => 'drag-drop-element-course-lms'));
				    
				    $output.= html_writer::tag('input', '', array('id' => 'id_thumbnail_file', 'type' => 'text', 'name' => 'thumbnail_file', 'value' => ''));
				    $output.= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'shortname', 'value' => null));
			    $output.= html_writer::end_tag('div');

			    $output.= html_writer::start_tag('div',array('class'=>'img-file-uploader-uploading'));
			    	$output.= html_writer::tag('i','',array('class'=>'fa fa-circle-o-notch'));
			    $output.= html_writer::end_tag('div');

	    $output.= html_writer::end_tag('div');


		return $output;

	}


	public function fieldSelectGroupWithSelectedValue($label, $attrs, $options,$default,$help,$colwidth='')
	{
		global $OUTPUT,$CFG ;
		
			if($help)
	   		{

			$helphtml = html_writer::start_tag('span', array('class' => 'helptooltip','style'=>'float:none !important'));
				$helphtml .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component='.$help['plugin'].'&identifier='.$help['stringname'].'&lang='.current_language()));
					$helphtml .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
				$helphtml .= html_writer::end_tag('a');
			$helphtml .= html_writer::end_tag('span');

			//$label .= $helphtml ;

	   		}


	   			/*$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4'));
			if(!empty($label)){
                $output .= html_writer::tag('label', $label, array('class' => 'col-form-label d-inline-block'));
            }
        	$output .= html_writer::end_tag('div');

        	$output .= html_writer::start_tag('div', array('class' => 'col-xs-12 col-sm-12 col-md-8 form-inline felement'));
           	$array = array('class' => 'form-control', 'type' => 'text');
			$output .= html_writer::tag('input', '', array_merge($array, $attrs));
			$output .= html_writer::end_tag('div');		
			
			return $output;*/
	

			$output .= html_writer::start_tag('div', array('class' => 'contentlabel col-sm-12 col-md-4'));
		        if(!empty($label)){        
					$output .= html_writer::tag('label', $label, array('class' => 'col-form-label d-inline-block'));
		        }
		    $output .= html_writer::end_tag('div');
        
	        $output .= html_writer::start_tag('div', array( 'class' => 'col-xs-12 col-sm-12 col-md-8 form-inline felement', 'data-fieldtype' => 'select'));

				$output .= html_writer::start_tag('select', $attrs);
					foreach ($options as $key => $value) {
			                   
			                   
						if($default==$key)
						{
			                $output .= html_writer::tag('option', $value, array('value' => $key,'selected'=>'selected'));
						}else
						{
							$output .= html_writer::tag('option', $value, array('value' => $key));
						}
					}
                 
				$output .= html_writer::end_tag('select');

			$output .= html_writer::end_tag('div');
			$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}


	public function FloatingAlertMessage($messagetitle,$message)
	{

	    $output = html_writer::start_tag('div',array("class"=>"floating-top-right-slms-alert floating-container"));
        $output .= html_writer::start_tag('div',array("class"=>"alert-wrap animated jellyIn"));
        $output .= html_writer::start_tag('div',array("class"=>"alert alert-danger", "role"=>"alert"));
            $icontimecircle =  html_writer::tag('i','',array("class"=>"fa fa-times-circle"));
            $output .= html_writer::tag('div',$icontimecircle,array("class"=>"close slms-close-alert"));
            $output .= html_writer::start_tag('div',array("class"=>"media"));
                $output .= html_writer::start_tag('div',array("class"=>"media-left col-md-1"));
                	$icontimes =  html_writer::tag('i','',array("class"=>"fa fa-exclamation-circle fa-lg"));
                 	$output .= html_writer::tag('span',$icontimes,array("class"=>"icon-wrap icon-wrap-xs icon-circle alert-icon"));
                $output .= html_writer::end_tag('div');
                     $output .= html_writer::start_tag('div',array("class"=>"media-body col-md-10"));
	                     $output .= html_writer::tag('h4',$messagetitle,array("class"=>"alert-title",'style'=>'margin-left:5px'));
	                     $output .= html_writer::tag('p',$message,array("class"=>"alert-message",'style'=>'margin-left:5px'));
                    $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
    $output .= html_writer::end_tag('div');

	return $output;

	}

	public function fieldTextGroupWithHelp($label, $attrs, $help=array(),$colwidth = 'col-sm-12')
	{
		global $OUTPUT, $CFG ;
		$output = html_writer::start_tag('div', array('class' => 'form-group col-lg-6'));
		if($help)
   		{

		$helphtml = html_writer::start_tag('span', array('class' => 'helptooltip','style'=>'float:none !important'));
			$helphtml .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component='.$help['plugin'].'&identifier='.$help['stringname'].'&lang='.current_language()));
				$helphtml .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
			$helphtml .= html_writer::end_tag('a');
		$helphtml .= html_writer::end_tag('span');

		$label .= $helphtml ;

   		}
		$output .= html_writer::tag('label', $label, array('class' => 'col-sm-8 col-md-10 col-lg-12'));
		
		$output .= html_writer::start_tag('div', array('class' => $colwidth));
		
			$output .= html_writer::tag('input', '', $attrs);
		
		$output .= html_writer::end_tag('div');
		
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		
		$output .= html_writer::end_tag('div');

		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}
	
	/**
	* General Checkbox generation with help icon
	* @param $name : field name string 
	* @param $checked : if element checked string - checked 
	* @param $label : label of the field text showed to user
	* @param $help : StdObject with two atributes component and identifier to handle help text
	* @return $output: HTML output of the field.
	*/
	public function fieldGeneralCheckbox($name='', $checked ='', $label='', $help,$attr=null)
	{
		global $OUTPUT,$CFG;

		$labelactive ='';
		if($checked) 
		{
			$labelactive = ' active ';
		}

		if(!empty($label)){
			$output  = html_writer::start_tag('label', array('class' => 'form-checkbox form-normal form-icon-text form-slms d-flex'.$labelactive));
			$output .= html_writer::tag('span', $label ,array('class' => 'order-2 mr-3'));
		}else{
			$output  = html_writer::start_tag('label', array('class' => 'form-checkbox form-normal form-slms '.$labelactive));
		}
		if(!$attr)
		{
			$output .= html_writer::empty_tag('input', array('class' => 'order-1 mr-2','type' => 'checkbox', 'name' => $name, 'value' => 1, $checked => $checked, 'id' => 'id_' . $name));

		}else
		{
			$output .= html_writer::empty_tag('input',$attr);
		}

		if(!empty($help)){
		    $output .= html_writer::start_tag('span', array('class' => 'helptooltip order-3'));
		    	$output .= html_writer::start_tag('a', array('target' => '_blank','aria-haspopup' => 'true','aria-haspopup' => 'true','href' => $CFG->wwwroot .'/help.php?component='.$help->component.'&identifier='.$help->identifier.'&lang='.current_language()));
		    		$output .= html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('help'), 'class'=>'iconhelp', 'alt'=>'help'));
		    	$output .= html_writer::end_tag('a');
		    $output .= html_writer::end_tag('span');
		}
		$output .= html_writer::end_tag('label');

		return $output;
	}
	
	

	public function fieldTimePicker($label, $value, $id, $required, $ExtraData)
	{
		if($required)
		{
			$label = $label."*";
			$class= 'control-label text-danger text-bold' ;
 		}
 		else {
			$class = ' control-label';
		}
		$output = html_writer::start_tag('div', array('class' => 'form-group'));

		$output .= html_writer::start_tag('div', array('class' => 'col-sm-6 no-pad'));
			$output .= html_writer::tag('label', $label, array('class' => $class ));
		$output .= html_writer::end_tag('div');

		$output .= html_writer::start_tag('div', array('class' => 'col-sm-12 no-pad'));
		$output .= html_writer::start_tag('div', array('class'=>'input-group bootstrap-timepicker','data-provide'=>"timepicker-inline-slms") );
			$output .= html_writer::empty_tag('input', $ExtraData);
				$output .= html_writer::start_tag('span',array('class'=>'input-group-addon'));
					$output .= html_writer::tag('i','',array('class'=>'fa fa-clock-o fa-lg'));
				$output .= html_writer::end_tag('span');
			$output .= html_writer::end_tag('div');
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));

		return $output;
	}
        
        public function fieldSelectByGroupsGroupNew($label, $attrs, $options,$default)
	{
		$output = html_writer::start_tag('div', array('class' => 'form-group'));
		$output .= html_writer::tag('label', $label, array('class' => 'col-lg-2 control-label'));
		$output .= html_writer::start_tag('div', array('class' => 'col-sm-4'));

		$output .= html_writer::start_tag('select', $attrs);
		$selected='-';
		foreach ($options as $key => $value) {
			$output .= html_writer::tag('optgroup', $key, array('label' => $key));
				foreach($value as $keys =>$values)
				{
					if(!empty($default))
					{ if($default==$keys)
						{
							$output .= html_writer::tag('option', $values, array('value' => $keys ,'selected'=>'selected'));
						}else
						{
							$output .= html_writer::tag('option', $values, array('value' => $keys));
						}
					}
					else
					{
						$output .= html_writer::tag('option', $values, array('value' => $keys));
					}
				}
		}
		$output .= html_writer::end_tag('select');

		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		$output .= html_writer::end_tag('div');
		$output .= html_writer::tag('div', '', array('class' => 'clearfix'));
		return $output;
	}
        
          
    public function filter($data) { //Filters data against security risks.
    
        $arr = array('=','>','<','Between','<=','>=');
        if(!(in_array($data,$arr))){
              $retdata = trim(htmlentities(strip_tags($data))); 
              if(get_magic_quotes_gpc()) $retdata = stripslashes($data);
              //$retdata = mysql_real_escape_string($data);
              $retdata = addslashes( $data );

             return $data;
      } else{
         return $data;
      }
    }
    
}