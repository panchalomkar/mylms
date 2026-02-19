<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_remui;

use user_picture;
use moodle_url;
use blog_listing;
use context_system;
use course_in_list;
use context_course;
use core_completion\progress;
use stdClass;
use html_writer;

class widget {

     public static function input(string $label, string $value = "", string $id = "", string $name = "", bool $required = false, array $params = array()): string {
        global $PAGE;
        
        $classes = ' ';
        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }
        if ($value) {
         $params['value'] = $value; 
        }
        if (!empty($id)) {
            $params['id'] = $id;
        }else{
            $params['id'] = 'input' . self::randHash(10);
        }
        $id = $params['id'];
        if (!empty($name)) {
            $params['name'] = $name;
        }

        $attributes = self::parse_data_attr($params);

        $input = <<<STRING
                    <div class="">
                        <input $attributes for="$id">
                    </div>
STRING;
        return $input;
    }

     public static function input_field(string $placeholder, string $value = "", string $id = "", string $name = "", $icon_class = '', bool $required = false, array $params = array(), string $addhtml = ''): string {
        global $PAGE;
        
        $classes = ' form-control';

        $params['type'] = 'text';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }

        $icon = '';
        if(!empty($icon_class)){
            $icon = html_writer::tag('i','',['class' => $icon_class,'aria-hidden' => true]);
        }
        $params['placeholder'] = $placeholder;
        if ($value) {
         $params['value'] = $value; 
        }
        if (!empty($id)) {
            $params['id'] = $id;
        }else{
            $params['id'] = 'input' . self::randHash(10);
        }
        $id = $params['id'];
        if (!empty($name)) {
            $params['name'] = $name;
        }

        $attributes = self::parse_data_attr($params);

        $input = <<<STRING
                    <fieldset class="input_field_rap">
                        $icon
                        <input $attributes>
                        $addhtml
                    </fieldset>
STRING;
        return $input;
    }
    
    public static function select2(string $label, array $options ,string $id = '', string $selected = '-1', string $name = "", bool $required = false, array $params = array(), bool $multiple = false, array $dataselect2 = array()): string {
        global $PAGE;
        $PAGE->requires->css(new moodle_url('/theme/remui/style/select2.min.css'));
        if (empty($id)) {
            $id = 'select2' . time();
        }

        if (empty($selected)) {
            $selected = '-1';
        }
        
        $classes = 'form-control ';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }
        
        if($multiple){
            $params['multiple'] = 'multiple';

            if(count($options) > 20){
                $dataselect2['minimumInputLength'] = -1;    
            }else{
                $dataselect2['minimumInputLength'] = 0;
            }

        }else{

            $dataselect2['minimumInputLength'] = 1;
        }
          
        if(!$required){
            if($options[-1] == ''){
                $dataselect2['placeholder'] = ['id' => '-1', 'text' => get_string('choose')];   
            }else{
                $dataselect2['placeholder'] = ['id' => '-2', 'text' => get_string('choose')];
            }

            $dataselect2['allowClear'] = true;
        }else{
            $params['required'] = 'required';
        }
         
        $params['id'] = $id;
        
        if (!empty($name)) {
            $params['name'] = $name;
        }
         
        $select_config_json = json_encode($dataselect2);
        
        $data_options = '';
        if($options[-1] == ''){
            $options[-1] = '';
        }
        foreach ($options as $key => $value){
            $attr = [
                'value' => $key
            ];
            if ($selected == $key) {
                $attr['selected'] = true;
            }
            $data_options .= html_writer::tag('option', $value, $attr);
        }
        
        $_html = html_writer::start_div('form-group row fitem');
            $_html .= html_writer::tag('label', $label, ['for' => $id]);
            $_html .= html_writer::end_tag('br');
            $_html .= html_writer::start_tag('select',$params);
                $_html .= $data_options;
            $_html .= html_writer::end_tag('select');
        $_html .= html_writer::end_div();
                
        $_js = <<<JS
        require(['jquery','theme_remui/select2'], function($, select2) {
            $(function () {
                $('#$id').select2($select_config_json);
            });
        });      
JS;
        
        $PAGE->requires->js_amd_inline($_js);
        return $_html;
    }

    public static function datepicker(string $label,  $value = 0, string $id = '', string $name = "", bool $is_datetime = false, $is_between = false, bool $required = false, array $params = array()): string {
        global $PAGE;
        $format = '%m/%d/%Y' . (($is_datetime) ? ' %I:%M %p' : '');

        if (empty($id)) {
            $id = 'datetimepicker' . time();
        }

        if ($value <= 0) {
//            $value = userdate(time(), $format);
            $value = "";
        }

        $classes = 'form-control ';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }

        $params['value'] = $value;
        $params['id'] = $id;
        $params['type'] = 'text';
        if (!empty($name)) {
            $params['name'] = $name;
        }


        $date_config = array();

        $date_config['icons'] = [
            'time' => 'fa fa-clock-o',
            'date' => 'fa fa-calendar',
            'up' => 'fa fa-chevron-up',
            'down' => 'fa fa-chevron-down',
            'previous' => 'fa fa-chevron-left',
            'next' => 'fa fa-chevron-right',
            'today' => 'fa fa-screenshot',
            'clear' => 'fa fa-trash',
            'close' => 'fa fa-remove',
        ];

        if (!$is_datetime) {
            $date_config['format'] = 'MM/DD/YYYY';
        }

        $date_config_json = json_encode($date_config);
        $attributes = self::parse_data_attr($params);

        $_js2 = '';
        $input2 = '';
        $size = '12';
        if ($is_between) {
            $size = '6';
            $id2 = ($params['id_2']) ? $params['id_2'] : $id . '_2';
            $date_config['useCurrent'] = false;
            $date_config2_json = json_encode($date_config);

            $params2 = [
                'value' => ($params['value_2']) ? $params['value_2'] : $value,
                'id' => $id2,
                'type' => 'text',
                'class' => $params['class'],
            ];

            if (!empty($params['name_2'])) {
                $params2['name'] = $params['name_2'];
            }

            $attributes2 = self::parse_data_attr($params2);

//                        <div class="input-group-addon">to</div>
            $input2 = <<<HTML
                    <div class="form-group is-filled input-group  col-md-12 col-lg-6 ">
                        <input $attributes2 >
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                      </div>
                    </div>
HTML;
            $_js2 = <<<JS
                $('#$id2').datetimepicker($date_config2_json);
                $('#$id2').closest('.form-group').find('div.input-group-prepend').click(function(){
                    $('#$id2').focus();
                });
                $("#$id").on("dp.change", function (e) {
                    $('#$id2').data("DateTimePicker").minDate(e.date);
                });
                $("#$id2").on("dp.change", function (e) {
                    $('#$id').data("DateTimePicker").maxDate(e.date);
                });
JS;
        }

        $datepicker = <<<HTML
            <label class="label-control bmd-label-static">$label</label>
            <div class="row">
                <div class="form-group input-group col-md-12 col-lg-$size">
                    <input $attributes >
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
                $input2
            </div>
HTML;

        $_js = <<<JS
        require(['jquery','theme_remui/bootstrap-datetimepicker'], function($, datetimepicker) {
            $(function () {
                $('#$id').datetimepicker($date_config_json);
                $('#$id').closest('.form-group').find('div.input-group-prepend').click(function(){
                    $('#$id').focus();
                });
                $_js2
            });
        });      
JS;
        $PAGE->requires->js_amd_inline($_js);
        return $datepicker;
    }

    public static function checkbox(string $label, bool $value, string $id = "", string $name = "", bool $required = false, array $params = array(),string $extraclasseslabel = ''): string {
        global $PAGE;
        
        $classes = ' ';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }

        if ($value) {
            $params['checked'] = 'checked';
        }

        $params['type'] = 'checkbox';

        if (!empty($id)) {
            $params['id'] = $id;
        }else{
            $params['id'] = 'checkbox' . self::randHash(10);
        }

        $id = $params['id'];

        if (!empty($name)) {
            $params['name'] = $name;
        }

        $attributes = self::parse_data_attr($params);
        if($label != ''){
            $hlabel = html_writer::tag('label',$label, array('class' => '$extraclasseslabel form-check-label', 'for' => $id));
        }else{
            $hlabel = '';
        }

        $checkbox = <<<STRING
                    <div class="checkbox">
                        <input $attributes >
                        $hlabel
                    </div> 
                 
STRING;
        return $checkbox;
    }

    public static function radio(string $label, bool $value, string $id = '', string $name = "", bool $required = false, array $params = array(), string $extraclasseslabel = ''): string {

        $classes = 'form-check-input ';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }

        if ($value) {
            $params['checked'] = 'checked';
        }

        $params['type'] = 'radio';

        if (!empty($id)) {
            $params['id'] = $id;
        }else{
            $params['id'] = 'radio_' . self::randHash(10);
        }

        $id = $params['id'];


        if (!empty($name)) {
            $params['name'] = $name;
        }

        $attributes = self::parse_data_attr($params);
        
        $radio = <<<STRING
              <div class="form-radio col-sm-12">
                        <input $attributes>
                        <label class="$extraclasseslabel form-check-label" for="$id">
                        $label
                    </label>
                </div> 
STRING;
        return $radio;
    }

   
     public static function select(array $options, string $label = '' , string $id = '', string $selected = '', string $firstoption = '' ,string $name = "", bool $required = false, array $params = array(), bool $multiple = false): string {
            global $PAGE;
           

        if(!$firstoption == ''){
            $options = array('' => $firstoption) + $options;
        }

        if (empty($id)) {
            $id = 'selectid_' . self::randHash(10);
        }
        
        $classes = 'form-control';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }
        
        if($multiple){
            $params['multiple'] = 'multiple';
        }
          
        if($required){
            $params['required'] = 'required';
        }
         
        $params['id'] = $id;
        
        if (!empty($name)) {
            $params['name'] = $name;
        }

        $data_options = '';
        $_select = '';
        
        foreach ($options as $key => $value){
            $attr = [
                'value' => $key
            ];
            if ($selected == $key) {
                $attr['selected'] = true;
            }
            $data_options .= html_writer::tag('option', $value, $attr);
        }   
            $_select .= html_writer::start_tag('select',$params);
                $_select .= $data_options;
            $_select .= html_writer::end_tag('select');

        $_label ='';
        if($label){
            $_label =  self::label($label, '','',true);
            $_html = $_label;
            $_html .= html_writer::start_tag('div', array('class' => 'col-sm-12 col-md-8'));
                $_html .= $_select;
            $_html .= html_writer::end_tag('div');
        }else{
            $_html = $_select;
        }
        
        return $_html;
    }

    public static function label(string $label , string $id = "", string $name = "", bool $required = false, array $params = array()): string {

        $_html = html_writer::start_tag('div', array('class' => 'col-sm-4'));
        $classes = 'form-label';

        if (array_key_exists('class', $params)) {
            $params['class'] = $params['class'] . $classes;
        } else {
            $params['class'] = $classes;
        }

        if (!empty($name)) {
            $params['name'] = $name;
        }

        $_html .= html_writer::tag('label',$label, $params);
        
        if($required){
            $_html .= html_writer::start_tag('div', array('class' => 'options d-inline float-right'));
                $_html .= html_writer::start_tag('abbr', array('class' => 'initialism text-danger', 'title' => 'Required'));
                    $_html .= html_writer::tag('i','', array('class' => 'icon fa fa-exclamation-circle text-danger fa-fw'));
                $_html .= html_writer::end_tag('abbr');
            $_html .= html_writer::end_tag('div');
        }

        $_html .= html_writer::end_tag('div');
        return $_html;

       }


    public static function parse_data_attr(array $params = []): string {
        $attributes = '';
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $attributes .= $key . '="' . $value . '" ';
            }
        }
        return $attributes;
    }


    public static function randHash($len=32){
        return substr(md5(openssl_random_pseudo_bytes(20)),-$len);
    }




}
