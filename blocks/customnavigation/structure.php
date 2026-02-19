<?php

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

global $DB, $USER, $OUTPUT, $PAGE, $CFG, $THEME;

require_login();
$context = context_system::instance();
$data = new stdClass();
$data->result = array();

if( !has_capability('block/customnavigation:managecustomnavigation', $context) )
{
    print_error('badpermissions');
}

if (isset($_GET['enablecustomnavigation'])) {
    if ($_GET['enablecustomnavigation'] == 'enable') {
        set_config('customnavigation', TRUE, 'theme_remui');
    } else {
        set_config('customnavigation', FALSE, 'theme_remui');
    }
}
$isPost = ('POST' == $_SERVER['REQUEST_METHOD']);
if ($isPost) {
    if (isset($_POST['action'])) {
    
        if((isset($_POST['select_language']) && $_POST['select_language'] != '') && (isset($_POST['select_index_language']) && $_POST['select_index_language'] != '')) {
            $fileToWrite = "blocks/customnavigation/lang/{$_POST['select_language']}/block_customnavigation.php";
            
            if(isset($_POST['label']) && $_POST['label'] != '') {
                $resultstring   = '';
                $labelLocal = strtoupper(str_replace(' ', '-', $_POST['label']));
              
                $selectedLanguage = optional_param('select_index_language', 'en', PARAM_TEXT);
                $filetoRead = $CFG->dirroot."/".$fileToWrite;
                $readFile = fopen($filetoRead, 'r');
                $fileContents = fread($readFile, filesize($filetoRead));
              
                fclose($readFile);
     
                   if( strpos($fileContents,$labelLocal) == false) {
                      
                    $labelWrite = "\$string['{$labelLocal}'] = \"{$selectedLanguage}\"; \n";
                                         
                    $file = $CFG->dirroot."/".$fileToWrite;
                    $handle = fopen($file, 'a');
                    if ($handle = fopen($file, 'a')) {
         
                        if (fwrite($handle, $labelWrite) === FALSE) {
                         
                           redirect($PAGE->url);
                         }
                    }else{
                         redirect($PAGE->url);
                    }
                    fclose($handle);
                       // Write $somecontent to our opened file.
                       
                   
                }else{
                 
                    $fileAppend = $CFG->dirroot."/".$fileToWrite;
                    $fcontent = file($fileAppend);
                    $newLine = "\$string['{$labelLocal}'] = \"{$selectedLanguage}\"; \n";
           
                    $newData = array_map(function ($line) use ($labelLocal, $newLine) {
                        return stristr($line, "\$string['{$labelLocal}']") ? $newLine : $line;
                    }, $fcontent);
            
                    if ($updateHandle = fopen($fileAppend, 'w')) {
                        if (fwrite($updateHandle, implode('', $newData)) === FALSE) {
                           redirect($PAGE->url);
                         }
                    }else{
           
                       redirect($PAGE->url);
                    }
                 
                    fclose($updateHandle);
                    //file_put_contents($fileToWrite, implode('', $newData));
                }
            }
        }

        $_POST['href'] = trim($_POST['href']);
        $href = substr($_POST['href'], 0, 1);
        if ( !preg_match("/^([\D]{1,})[https\:|http\:]/i",$_POST['href'],$la_result) && $href != '/' ) { 
            $_POST['href'] = '/'.$_POST['href'];
        }        
        if ('add_item' == $_POST['action']) {
            $data->result = json_decode(customnavigation_add_item());
        } else if ('edit_item' == $_POST['action']) {
            $data->result = json_decode(customnavigation_edit_item());
        } elseif ('sort_items' == $_POST['action']) {
            $data->result = json_decode(customnavigation_update_structure());
        }
    }
    $isPost = false;
    purge_all_caches();
}

$menu = customnavigation_get_menu_from_database();
$pageurl = new moodle_url('blocks/customnavigation/structure.php?id=' . $repor);
$PAGE->set_context($context);
$PAGE->set_title('Navigation structure');
$PAGE->set_heading('Navigation structure');

$PAGE->requires->css(new moodle_url('/blocks/customnavigation/css/structure.css'));
$PAGE->requires->css(new moodle_url('/blocks/customnavigation/css/jquery-ui.css'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/customnavigation/js/json_encode.js'));
$PAGE->requires->js(new moodle_url('/blocks/customnavigation/js/json_decode.js'));
$PAGE->requires->js_call_amd('block_customnavigation/nested', 'init');
$urlIcons = new moodle_url('/blocks/customnavigation/pix');

$PAGE->navbar->add(get_string('settings', 'block_customnavigation'));

global $SESSION;
if(isset($SESSION->currentrole) && $SESSION->currentrole->current != 0){
    redirect($CFG->wwwroot . '/');
} 

$data->roles = GetRoles();
echo $OUTPUT->header();
// Commented because wrong meessage (Sandeep B)
//$data->result->color = 'status' ==  $data->result->type ? 'green' : 'red';
$data->result->class = ( 'status' == trim($data->result->type) ) ? 'alert alert-success' : 'alert alert-danger';
$data->action = new moodle_url($CFG->wwwroot."/blocks/customnavigation/structure.php");
$data->files = scandir("lang");
$data->modules = array_values(customnavigation_get_modules());
$data->tree = customnavigation_array_to_tree($menu);
$data->menu = array_values($menu);
$data->icons = array_values(array_flip((array)json_decode(file_get_contents($CFG->wwwroot . '/theme/remui/json/font-awesome-data.json'))));
$fileselectedindex = 'en';
$data->select_index_language = '';
$data->root_url = $CFG->wwwroot;
if(isset($_POST['select_language'])) {
    $fileselectedindex = addslashes($_POST['select_language']);
}
/**
* Display the selected language and ignore the dots
* @author Hugo S.
* @since June 25 of 2018
* @rlms
* @ticket 53
*/
$langs = get_string_manager()->get_list_of_translations();
$data->files = $langs ;
array_walk($data->files, function(&$v, $key, $i) use (&$files_){
    if ($v != '.' && $v != '..'){
        $files_[] = [
            "key" => $key,
            "value" => $v,
            "selected" => $i === $v
        ];
    }
}, $fileselectedindex);

$data->files = $files_; 
echo $OUTPUT->render_from_template('block_customnavigation/customnavigation', $data);

echo $OUTPUT->footer();