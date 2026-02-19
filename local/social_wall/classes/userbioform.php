<?php
/**
 * Social wall post creation form.
 * 
 * @package   local_social_wall
 * @author    Manisha M
 * @paradiso
*/

defined('MOODLE_INTERNAL') || die;

// Global vars definition.
global $CFG, $PAGE;
require_once("{$CFG->libdir}/formslib.php");

class userbioform extends moodleform {

    public function definition() {
        global $PAGE,$DB,$USER;
        $choices = get_string_manager()->get_list_of_countries();
        $choices = array('' => get_string('selectacountry') . '...') + $choices;
        $userdetails = $DB->get_record('user', ['id' => $USER->id]);
        $userprefrences = $DB->get_record('user_preferences', ['name' => 'user_background_color','userid'=>$USER->id]);

        $color0 = get_config('local_social_wall', 'backgroundcolor');
        $color1 = get_config('local_social_wall', 'backgroundcolor1');
        $color2 = get_config('local_social_wall', 'backgroundcolor2');
        $color3 = get_config('local_social_wall', 'backgroundcolor3');
        $color4 = get_config('local_social_wall', 'backgroundcolor4');

        if($userprefrences->value==$color0){
            $color0selected = 'selected';
        }else if($userprefrences->value==$color1){
            $color1selected = 'selected';
        }else if($userprefrences->value==$color2){
            $color2selected = 'selected';
        }else if($userprefrences->value==$color3){
            $color3selected = 'selected';
        }else if($userprefrences->value==$color4){
            $color4selected = 'selected';
        }
        // echo "<pre>";
        // print_r($userdetails);
        //$fields = local_social_wall::get_user_fields_all();

        $mform = $this->_form;

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'container', 'id' => '')));
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row', 'id' => '')));
             
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-4 col-sm-4 col-lg-4']));
                $mform->addElement('html', html_writer::tag('label','Country' ,['class' => 'form-label']));
            $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-8 col-sm-8 col-lg-8']));
                $mform->addElement('html', html_writer::start_tag('select', ['class' => 'form-control country_social','name'=>'user_country','required'=>'true']));
                    foreach ($choices as $choice => $name) {
                       if($choice === $userdetails->country){
                           $mform->addElement('html',html_writer::tag('option', $name, array('class' => 'country_social','value' => $choice,'selected'=>'selected'))); 
                       }else{
                            $mform->addElement('html',html_writer::tag('option', $name, array('class' => 'country_social','value' => $choice)));
                       }
                        
                    }
                $mform->addElement('html', html_writer::end_tag('select'));
                $mform->addRule('user_country', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
            $mform->addElement('html', html_writer::end_tag('div'));
        
        $mform->addElement('html', html_writer::end_tag('div'));
        //row2
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row', 'id' => ''))); 
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-4 col-sm-4 col-lg-4']));
                $mform->addElement('html', html_writer::tag('label','City' ,['class' => 'form-label']));
            $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-8 col-sm-8 col-lg-8']));
                $mform->addElement('html', html_writer::start_tag('input', ['class' => 'form-control country_social','name'=>'user_city','value'=>$userdetails->city,'required'=>'true']));
                $mform->addElement('html', html_writer::end_tag('input'));
                $mform->addRule('user_city', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));
        //row3
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row', 'id' => '')));  
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-4 col-sm-4 col-lg-4']));
                $mform->addElement('html', html_writer::tag('label','Bio' ,['class' => 'form-label']));
            $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-8 col-sm-8 col-lg-8']));
                $mform->addElement('html', html_writer::tag('textarea',$userdetails->description,['class' => 'form-control user_bio_social','name'=>'user_bio','value'=>$userdetails->description,'maxlength'=>'250']));
               // $mform->addElement('html', html_writer::end_tag('textarea'));
      $desc_error = get_string('validation_error', 'local_social_wall');

            $mform->addElement('html', html_writer::start_tag('span',array('class' => 'area_error')));
            $mform->addElement('html', $desc_error);

            $mform->addElement('html', html_writer::end_tag('span'));

            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));
        //row4
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'form-group row', 'id' => ''))); 
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-4 col-sm-4 col-lg-4']));
                $mform->addElement('html', html_writer::tag('label','Background' ,['class' => 'form-label']));
            $mform->addElement('html', html_writer::end_tag('div'));
            $mform->addElement('html', html_writer::start_tag('div', ['class' => 'col-md-8 col-sm-8 col-lg-8']));
                $mform->addElement('html', html_writer::start_tag('table', ['style' => 'width:100%;']));
                    $mform->addElement('html', html_writer::start_tag('tbody', ['class' => '']));
                        $mform->addElement('html', html_writer::start_tag('tr', ['class' => '']));
                            // td1
                            $mform->addElement('html', html_writer::start_tag('td', ['class' => 'td-width']));
                                $mform->addElement('html', html_writer::tag('div','&nbsp;',['class' => 'div-color','style'=>'background-color:'.$color0.';','bgcolor'=>$color0,$color0selected]));
                            $mform->addElement('html', html_writer::end_tag('td'));
                            //td2
                            $mform->addElement('html', html_writer::start_tag('td', ['class' => 'td-width']));
                                $mform->addElement('html', html_writer::tag('div','&nbsp;',['class' => 'div-color','style'=>'background-color:'.$color1.';','bgcolor'=>$color1,$color1selected]));
                            $mform->addElement('html', html_writer::end_tag('td'));
                            //td3
                            $mform->addElement('html', html_writer::start_tag('td', ['class' => 'td-width']));
                                $mform->addElement('html', html_writer::tag('div','&nbsp;',['class' => 'div-color','style'=>'background-color:'.$color2.';','bgcolor'=>$color2,$color2selected]));
                            $mform->addElement('html', html_writer::end_tag('td'));
                            //td4
                            $mform->addElement('html', html_writer::start_tag('td', ['class' => 'td-width']));
                                $mform->addElement('html', html_writer::tag('div','&nbsp;',['class' => 'div-color','style'=>'background-color:'.$color3.';','bgcolor'=>$color3,$color3selected]));
                            $mform->addElement('html', html_writer::end_tag('td'));
                            //td5
                            $mform->addElement('html', html_writer::start_tag('td', ['class' => 'td-width']));
                                $mform->addElement('html', html_writer::tag('div','&nbsp;',['class' => 'div-color','style'=>'background-color:'.$color4.';','bgcolor'=>$color4,$color4selected]));
                                $mform->addElement('hidden', 'user_background', '',array('id'=>'user_background_colour'));
                                $mform->addElement('hidden', 'user_id', $USER->id,array('id'=>'user_id'));
                            $mform->addElement('html', html_writer::end_tag('td'));

                        $mform->addElement('html', html_writer::end_tag('tr'));
                    $mform->addElement('html', html_writer::end_tag('tbody'));
                $mform->addElement('html', html_writer::end_tag('table'));
            $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addRule('message', get_string('required_field', 'local_social_wall'), 'required', null, 'client');
    }
}