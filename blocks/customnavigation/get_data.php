<?php
require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

global $DB, $USER, $OUTPUT, $PAGE, $CFG;

$nav_id = optional_param( 'item', 0, PARAM_INT );

$item = $DB->get_record('customnavigation', array('id'=> $nav_id ));
$m['sort']          = $item->sort;
$m['type']          = $item->type;
$m['module']        = $item->module;
$m['label']         = $item->label;
$m['href']          = $item->href;
$m['target']        = $item->target;
$m['icon']          = $item->icon;
$m['asignuserid']   = $item->asignuserid;
$m['roleid']        = explode(',',$item->roleid);
$m['parent']        = $item->parent_id;
$m['inst_id']       = $item->inst_id;
$m['languages']     = array();


$files = scandir("lang");
for($i=0; $i<count($files); $i++) {

    $fileSelected = '';
    if($files[$i] != '.' && $files[$i] != '..') {
        $fileToWrite = "lang/{$files[$i]}/block_customnavigation.php";
        $labelLocal = strtoupper(str_replace(' ', '-', $m['label']));
        if( !(strpos("'{$labelLocal}'",file_get_contents($fileToWrite)) == false) && !(strpos("'{$m['label']}'",file_get_contents($fileToWrite) ) == false) ) {
            $strLabelValue = '';
        } else {
            $strLabelValue = get_string_manager()->get_string(strtoupper(str_replace(' ', '-', $m['label'])), 'block_customnavigation', null, $files[$i]);
        }
        if($strLabelValue && $strLabelValue != '') {
            $m['languages'][$files[$i]] = $strLabelValue;
        }
    }
   
}

echo json_encode($m);