<?php

require_once($CFG->libdir . '/uploadlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/moodlelib.php');

// we need to use a unique namespace to store global variables without overwriting existing values
$GLOBALS[__FILE__] = array();


function customnavigation_get_rand_image()
{
   return mt_rand(1, 4) . '.jpg';
}


function customnavigation_update_structure()
{
    global $DB;
    
    if( isset($_POST['structure']) )
    {
        $structure = $_POST['structure'];
        $data = @json_decode($structure, true);
        $ids = array();

        if($data)
        {
            $sort = 1;
            foreach ($data as $key => $item)
            {
                $ids[] = (int)$item['id'];

                $obj = new stdClass();
                $obj->id = (int)$item['id'];
                $obj->parent_id = (int)$item['parent_id'];
                $obj->sort = $sort;
                
                $DB->update_record('customnavigation', $obj);

                $sort++;
            }

            if($ids)
            {
                $imploded = implode(',', $ids);

                $sql = "DELETE FROM {customnavigation} WHERE `id` NOT IN($imploded)";

                $res = $DB->execute($sql);
                if($res){
                    $response['message'] = get_string('item_modified', 'block_customnavigation');
                    $response['type'] = 'status';
                    return json_encode($response);
                } else {
                    $response['message'] = get_string('item_modified_error', 'block_customnavigation');
                    $response['type'] = 'error';
                    return json_encode($response);
                }
            }
        }
    }
    else{
        $response['message'] = get_string('item_modified_error', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    }
}

function customnavigation_add_item()
{
    global $DB, $CFG,$USER;

    $label      = trim($_POST['label']);
    $type       = $_POST['type'];
    $href       = $_POST['href'];
    $target     = $_POST['target'];
    $visible    = $_POST['visible'];
    $roles      = implode(',', $_POST['roles']);
    if($roles == null){
        $roles = '';
    }
    $module = trim($_POST['module']);

    if('container' == $type) {
        $href = 'javascript:;';
        $target = '';
        $module = '';
    }
    elseif('module' == $type) {
        $href = '';
        $target = '';
    } 
    else {
        $module = '';
    }


    if( empty($label) ) {
        $response['message'] = get_string('providelabelfield', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    }

    if( 'module' == $type && empty($module)) {
        $response['message'] = get_string('select_a_module', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    }

    if ( $_FILES['icon']['error'] > 0 && $_FILES['icon']['error'] != 4 ) {
        $response['message'] = get_string('erroruploadingicon', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    }
    else
    {

        $iconId = null;

        if($_POST['icon-type'] == 'font'){
            $iconId = $_POST['fa-type'];
        } else {
            if(0 == $_FILES['icon']['error'])
            {

                $allowedExts = array('gif', 'jpeg', 'jpg', 'png');
                $temp = explode('.', $_FILES['icon']['name']);
                $extension = strtolower(end($temp));
                
                $imageAllowd = 'image/gif' == $_FILES['icon']['type'];
                $imageAllowd = $imageAllowd || 'image/jpeg' == $_FILES['icon']['type'];
                $imageAllowd = $imageAllowd || 'image/jpg' == $_FILES['icon']['type'];
                $imageAllowd = $imageAllowd || 'image/pjpeg' == $_FILES['icon']['type'];
                $imageAllowd = $imageAllowd || 'image/x-png' == $_FILES['icon']['type'];
                $imageAllowd = $imageAllowd || 'image/png' == $_FILES['icon']['type'];

                if($imageAllowd && in_array($extension, $allowedExts))
                {
                    $context = context_system::instance();

                    $id = mt_rand(1000, 900000);
                    $fileinfo = array(
                        'component' => 'block_customnavigation',
                        'filearea' => 'attachment', // usually = table name
                        'itemid' => $id, // usually = ID of row in table
                        'contextid' => $context->id, // ID of context
                        'filepath' => '/icons/',          // any path beginning and ending in /
                        'filename' => $_FILES['icon']['name'] // any filename
                    );

                    $fs = get_file_storage();
                    $fs->create_file_from_pathname($fileinfo, $_FILES['icon']['tmp_name']);

                    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],  $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                    
                    // crop the image
                    if ($imagefile = $file->copy_content_to_temp())
                    {
                         require_once($CFG->libdir. '/gdlib.php');

                         if (!empty($CFG->gdversion)) {
                            $iconId = process_new_icon_cn($context, 'block_customnavigation', 'attachment', $id, $imagefile);
                            $file = $fs->get_file_by_id($iconId);
                            @unlink($imagefile);
                           
                            $fs->delete_area_files($context->id, 'block_customnavigation', 'draft');
                        }
                    }
                }
                else
                {
                    $response['message'] = get_string('invalidimagetype', 'block_customnavigation');
                    $response['type'] = 'error';
                    return json_encode($response);
                }
            }
        }
    
  
        if( is_siteadmin() ) {
            $tazuser = 'admin';
        } else {
            $tazuser =$USER->id;
        }
        if( $visible == 'on' ) {
            $roleid = '11,12';
        }else {
            $roleid = '3,5,11,12';
        }
        $roleid = $roles;

        $mid = $report = $_GET['id'];
        

        $obj = new stdClass();
        $obj->parent_id = 0;
        $obj->type = $type;
        $obj->module = $module;
        $obj->label = $label;
        $obj->href = $href; 
        $obj->target = $target;
        $obj->icon = $iconId;
        $obj->sort = 1;
 
        $insertid = $DB->insert_record('customnavigation', $obj);
        $DB->set_field('customnavigation','asignuserid',$tazuser, array('id' => $insertid));
        $DB->set_field('customnavigation','roleid',$roleid, array('id' => $insertid));
        $DB->set_field('customnavigation','inst_id',$mid, array('id' => $insertid));
    

        if ( $_POST['id_item'] != 0 ) 
        {
			$obj2 = new stdClass();
            $obj2->id = $insertid;
			$obj2->parent_id = $_POST['h_parentid'];
			$obj2->roleid = $roleid; //$_POST['h_roleid'];
			$obj2->sort = $_POST['h_sort'];

            if ($obj->icon == '') {
                if($_POST['icon-type'] == 'font') {
                    $obj2->icon = $_POST['fa-type'];
                } else {
                    $obj2->icon = $_POST['h_icon'];
                }
            } else {
                if($_POST['icon-type'] == 'font'){
                    $obj2->icon = $_POST['fa-type'];
                } else {
            		$obj2->icon = $obj->icon;
                }
            };

            $DB->update_record('customnavigation', $obj2);
            $sql = "DELETE FROM {customnavigation} WHERE id = ".$_POST['id_item'];
            $DB->execute($sql);
			
            $response['message'] = get_string('item_modified', 'block_customnavigation');
            $response['type'] = 'status';
            return json_encode($response); 	
		}
		$response['message'] = get_string('item_added', 'block_customnavigation');
        $response['type'] = 'status';
        return json_encode($response);
    }
}


function customnavigation_edit_item()
{
    global $DB, $CFG,$USER;
    
    $label = trim($_POST['label']);
    $type = $_POST['type'];
    $href = $_POST['href'];
    $target = $_POST['target'];
    $visible = $_POST['visible'];
    $roles = implode(',',$_POST['roles']);
    if($roles == null){
        $roles = '';
    }
    $module = trim($_POST['module']);

    if('container' == $type){
        $href = 'javascript:;';
        $target = '';
        $module = '';
    } elseif('module' == $type) {
        $href = '';
        $target = '';
    } else {
        $module = '';
    }

    if(empty($label)){
        $response['message'] = get_string('providelabelfield', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    }

    if('module' == $type){
        if(empty($module)){
            $response['message'] = get_string('select_a_module', 'block_customnavigation');
            $response['type'] = 'error';
            return json_encode($response);
        }
    }

    if ($_FILES['icon']['error'] > 0 && $_FILES['icon']['error'] != 4){
        $response['message'] = get_string('erroruploadingicon', 'block_customnavigation');
        $response['type'] = 'error';
        return json_encode($response);
    } else {

        $iconId = null;

      if($_POST['fa-type']) {
        $iconId = $_POST['fa-type']; 
      }
       

        if(is_siteadmin()){
            $tazuser = 'admin';
        } else {
            $tazuser =$USER->id;
        }

        $roleid = $roles;

        $mid = $report = $_GET['id'];
        if ($_POST['id_item'] != 0){
            $obj = new stdClass();
            $obj->id = $_POST['id_item'];
            $obj->roleid = $roleid;
            $obj->sort = $_POST['h_sort'];
            $obj->label = $_POST['label'];
           
            $obj->target = $_POST['target'];

            $obj->href = $_POST['href'];


            if ($obj->icon == ''){
                    if($_POST['icon-type'] == 'font'){
                        $obj->icon = $_POST['fa-type'];
                    } else {
                        $obj->icon = $_POST['h_icon'];
                    }

            } else {

                if($_POST['icon-type'] == 'font'){
                        $obj->icon = $_POST['fa-type'];
                } else {
                    $obj->icon = $obj->icon;
                }

            }
          
            
            $obj->icon = $iconId;
           
            $roleIDs = explode(',', trim($obj->roleid));
            $DB->update_record('customnavigation', $obj);
            CheckElementsParentChild($obj->id, true , $roleIDs);
            CheckElementsParentChild($obj->id, false , $roleIDs);
            //End Alejandro changes

            $response['message'] = get_string('item_modified', 'block_customnavigation');
            $response['type'] = 'status';
            return json_encode($response);
        }
        $response['message'] = get_string('item_added', 'block_customnavigation');
        $response['type'] = 'status';
        return json_encode($response);
    }
}

function customnavigation_get_icon_url($iconId)
{
    $url = false;
    if($iconId)
    {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($iconId);
        if($file)
        {
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $pathinfo = pathinfo($url);
            $url = "{$pathinfo['dirname']}/f2.{$pathinfo['extension']}";
            #echo "<img src='$url'>";
        }
    }

    return $url;
}


function customnavigation_write_response($message, $type)
{
    $response['message'] = $message;
    $response['type'] = $type;
    ?>
    <script>
    var obj = <?php echo json_encode($response); ?>;
    parent.customnavigation_response_from_post(obj);
    </script>
    <?php
}

function customnavigation_get_modules()
{
    global $CFG;

    if(isset($GLOBALS[__FILE__]['modules']))
    {
        $modules = $GLOBALS[__FILE__]['modules'];
    }
    else
    {
        $str = $CFG->dirroot . '/blocks/customnavigation/modules/*.php';

        $modules = array();
        foreach(glob($str) as $item)
        {
            include_once($item);
            $pathinfo = pathinfo($item);

            $class_name = "customnavigation_{$pathinfo['filename']}";
            $module = new $class_name();

            $modules[$pathinfo['filename']] = array(
                'object' => $module,
                'name' => $module->name,
                'code' => $pathinfo['filename'],
            );
        }

        $GLOBALS[__FILE__]['modules'] = $modules;
    }

    
    return $modules;
}


function customnavigation_get_menu_from_database($is_sub=false, $pages = array())
{
    global $CFG, $DB, $USER;
	
    $menu = array();
    $arr = array();
    
    if(!$is_sub)
    {
      
        if(is_siteadmin())
        {
            if(@end(explode('/',$_SERVER['SCRIPT_NAME'])) != 'structure.php')
            {
               
                $arr = $DB->get_records('customnavigation', array('parent_id' => 0,'asignuserid'=>'admin'), 'sort ASC');
            }
            else
            {
                $arr = $DB->get_records('customnavigation', array('parent_id' => 0,'asignuserid'=>'admin'), 'sort ASC');
            }
    
        }
        else
        {
            if(end(explode('/',$_SERVER['SCRIPT_NAME'])) != 'structure.php')
            {
                $arr = $DB->get_records('customnavigation', array('parent_id' => 0), 'sort ASC');
            }
            else
            {
                $arr = $DB->get_records('customnavigation', array('parent_id' => 0,'roleid'=>7,'inst_id'=>$instid), 'sort ASC');
            }
        }
       
    }
    else
    {
        $arr = $pages;
    }
    
    foreach($arr as $key => $item)
    {
        if(is_siteadmin())
        {
            $pages = $DB->get_records('customnavigation', array('parent_id' => $item->id, 'asignuserid'=>'admin'), 'sort ASC');
        }
        else
        {
            $pages = $DB->get_records('customnavigation', array('parent_id' => $item->id), 'sort ASC');
        }
    
       
    
        if($pages)
        {
            $item->pages = customnavigation_get_menu_from_database(true, $pages);
        }
        else
        {
            $item->pages = array();
        }
    
        $menu[$key] = $item;
    }
    return $menu;
}


function customnavigation_array_to_tree(array $menu, $is_sub=false)
{
    global $CFG;

    $attr = (!$is_sub) ? ' class="sortable ui-sortable mjs-nestedSortable-branch mjs-nestedSortable-expanded"' : ' class=""';
	if(is_siteadmin()){
	$attr = $attr;
	}
	else{
	$attr = '';
	}
    $ol = "<ol{$attr}>"; 

    foreach($menu as $item)
    {
        $item = (array)$item;
        $pages = (isset($item['pages']) && is_array($item['pages']) && count($item['pages'])) ? $item['pages'] : false;
        $icon = isset($item['icon']) ? $item['icon'] : false;

        if($pages)
        {
            $sub = customnavigation_array_to_tree($pages, true);
        }
        else
        {
            $sub = null;
        }

        if($icon)
        {
            $icon = "<span class=\"icon\"><img src=\"{$CFG->wwwroot}/blocks/customnavigation/pix/icons/{$icon}\"></span>\n\t";
        }
        /**
        * Add the icon variable to display the menu icon
        * @author Hugo S.
        * @since June 25 of 2018
        * @rlms
        * @ticket 53
        */
        $itemObject = array(
            'type' => $item['type']
            ,'label' => $item['label']
            ,'href'=> $item['href']
            ,'target' => $item['target']
            ,'module' => $item['module']
            ,'parent_id' => $item['parent_id']
            ,'icon' => $item['icon']
        );

        $template = customnavigation_get_menu_item_template();
        $template = str_replace('{item_id}', "menuItem_{$item['id']}", $template);
        $template = str_replace('{item_label}', $item['label'], $template);
		$template = str_replace('{myitem_id}', "{$item['id']}", $template);
        $template = str_replace('{item_object}', json_encode($itemObject), $template);
        $template = str_replace('{sub}', $sub, $template);
        $template = str_replace('{icon}', "{$item['icon']}", $template);
        
        $ol .= $template;
    }

   
    return $ol . "</ol>\n";
}


function customnavigation_build_main_menu_array()
{
    $links = customnavigation_get_menu_from_database();
    $modules = customnavigation_get_modules();
    $moduleVisible = true;
    $isModule = false;

    foreach ($links as $key => $item)
    {
        $isModule = false;

        if('module' == $item->type)
        {
            $isModule = true;
            if(isset($modules[$item->module]))
            {
                $module = $modules[$item->module]['object'];
                $moduleVisible = $module->isVisible();
                if($moduleVisible)
                {
                    $item->href = $module->get_link();
                    $item->pages = array_merge($item->pages, $module->get_child());
                }
            }
        }
        
        if($isModule)
        {
            if($moduleVisible)
            {
                $links[$key] = $item;
            }
            else
            {
                unset($links[$key]);
            }
        }
    }

    return $links;
}



function customnavigation_make_link(array $item)
{
    $item = (array)$item;

    $label = $item['label'];
    $href = $item['href'];
    $target = $item['target'];

    $link = '<a target="'.$target.'" href='.$href.'>'.$label.'</a>';
    return $link;
}

function customnavigation_get_menu_item_template($includeSub = true)
{
    $sub = '';
    if($includeSub)
        $sub = '{sub}';

if(is_siteadmin()) {

$asignbutton = ''; 
} else {
$asignbutton = '';
}
    return <<<xxx
    <li class="mjs-nestedSortable-branch mjs-nestedSortable-expanded" id="{item_id}">
        <div class="menuDiv">
            <i class="fa {icon}"></i>
            <span class="itemTitle">{item_label}</span>
            <span title="Click to delete item." class="deleteMenu ui-icon ui-icon-closethick">
                <span></span>
            </span>
            <span title="Click to Assign Role" class="assignrole">$asignbutton</span>
            <span title="Click to edit item." class="editMenu ui-icon ui-icon-pencil">
                <span></span>
            </span>
            <span class="itemObject">{item_object}</span>
        </div>
        $sub
    </li>
xxx;
}

/* Copy of lib/gblib.php process_new_icon, for avoid crop the images */
function process_new_icon_cn($context, $component, $filearea, $itemid, $originalfile) {
    global $CFG;

    if (!is_file($originalfile)) {
        return false;
    }

    $imageinfo = getimagesize($originalfile);

    if (empty($imageinfo)) {
        return false;
    }

    $image = new stdClass();
    $image->width  = $imageinfo[0];
    $image->height = $imageinfo[1];
    $image->type   = $imageinfo[2];

    $t = null;
    switch ($image->type) {
        case IMAGETYPE_GIF:
            if (function_exists('imagecreatefromgif')) {
                $im = imagecreatefromgif($originalfile);
            } else {
                debugging('GIF not supported on this server');
                return false;
            }
            // Guess transparent colour from GIF.
            $transparent = imagecolortransparent($im);
            if ($transparent != -1) {
                $t = imagecolorsforindex($im, $transparent);
            }
            break;
        case IMAGETYPE_JPEG:
            if (function_exists('imagecreatefromjpeg')) {
                $im = imagecreatefromjpeg($originalfile);
            } else {
                debugging('JPEG not supported on this server');
                return false;
            }
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagecreatefrompng')) {
                $im = imagecreatefrompng($originalfile);
            } else {
                debugging('PNG not supported on this server');
                return false;
            }
            break;
        default:
            return false;
    }

    if (function_exists('imagepng')) {
        $imagefnc = 'imagepng';
        $imageext = '.png';
        $filters = PNG_NO_FILTER;
        $quality = 1;
    } else if (function_exists('imagejpeg')) {
        $imagefnc = 'imagejpeg';
        $imageext = '.jpg';
        $filters = null; // not used
        $quality = 90;
    } else {
        debugging('Jpeg and png not supported on this server, please fix server configuration');
        return false;
    }

    if (function_exists('imagecreatetruecolor')) {
        $im1 = imagecreatetruecolor(100, 100);
        $im2 = imagecreatetruecolor(35, 35);
        $im3 = imagecreatetruecolor(512, 512);
        if ($image->type != IMAGETYPE_JPEG and $imagefnc === 'imagepng') {
            if ($t) {
                // Transparent GIF hacking...
                $transparentcolour = imagecolorallocate($im1 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im1 , $transparentcolour);
                $transparentcolour = imagecolorallocate($im2 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im2 , $transparentcolour);
                $transparentcolour = imagecolorallocate($im3 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im3 , $transparentcolour);
            }

            imagealphablending($im1, false);
            $color = imagecolorallocatealpha($im1, 0, 0,  0, 127);
            imagefill($im1, 0, 0,  $color);
            imagesavealpha($im1, true);

            imagealphablending($im2, false);
            $color = imagecolorallocatealpha($im2, 0, 0,  0, 127);
            imagefill($im2, 0, 0,  $color);
            imagesavealpha($im2, true);

            imagealphablending($im3, false);
            $color = imagecolorallocatealpha($im3, 0, 0,  0, 127);
            imagefill($im3, 0, 0,  $color);
            imagesavealpha($im3, true);
        }
    } else {
        $im1 = imagecreate(100, 100);
        $im2 = imagecreate(35, 35);
        $im3 = imagecreate(512, 512);
    }

    $cx = $image->width / 2;
    $cy = $image->height / 2;

    if ($image->width < $image->height) {
        $half = floor($image->width / 2.0);
    } else {
        $half = floor($image->height / 2.0);
    }
    
    imagecopybicubic($im1, $im, 0, 0, $cx - $half, $cy - $half, 100, 100, $half * 2, $half * 2);
    imagecopybicubic($im2, $im, 0, 0, $cx - $half, $cy - $half, 35, 35, 35, 35);
    imagecopybicubic($im3, $im, 0, 0, $cx - $half, $cy - $half, 512, 512, $half * 2, $half * 2);

    $fs = get_file_storage();

    $icon = array('contextid'=>$context->id, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>'/');

    ob_start();
    if (!$imagefnc($im1, NULL, $quality, $filters)) {
        // keep old icons
        ob_end_clean();
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im1);
    $icon['filename'] = 'f1'.$imageext;
    $fs->delete_area_files($context->id, $component, $filearea, $itemid);
    $file1 = $fs->create_file_from_string($icon, $data);

    ob_start();
    if (!$imagefnc($im2, NULL, $quality, $filters)) {
        ob_end_clean();
        $fs->delete_area_files($context->id, $component, $filearea, $itemid);
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im2);
    $icon['filename'] = 'f2'.$imageext;
    $fs->create_file_from_string($icon, $data);

    ob_start();
    if (!$imagefnc($im3, NULL, $quality, $filters)) {
        ob_end_clean();
        $fs->delete_area_files($context->id, $component, $filearea, $itemid);
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im3);
    $icon['filename'] = 'f3'.$imageext;
    $fs->create_file_from_string($icon, $data);

    return $file1->get_id();
}



function CheckElementsParentChild ($elementid,$parent = true , $roleIDs) 
{
    global $DB;
    $allelements = ElementTotals($elementid);
    if($allelements)
    {
        foreach ($allelements as $key => $element) {
            
            if($parent){
                $ElementRoleIDs = explode(',', trim($element->roleid ,',' ) );
                foreach ($roleIDs as $key => $role) {                        
                    if(!in_array($role, $ElementRoleIDs))
                    {
                       array_push($ElementRoleIDs,$role);
                    }
                }
                $element->roleid = implode(',', $ElementRoleIDs);
                $DB->update_record('customnavigation', $element); 
            }else
            {
                $ElementRoleIDs = explode(',', trim($element->roleid ,',' ) );
                foreach ($ElementRoleIDs as $key => $role) {                       
                    if(!in_array($role, $roleIDs))
                    {
                        //remove from child parent not present role
                        $key = array_search($role, $ElementRoleIDs);
                        unset($ElementRoleIDs[$key]);
                        array_push($ElementRoleIDs);
                    }
                }
                
                $element->roleid = implode(',', $ElementRoleIDs);
                $DB->update_record('customnavigation', $element); 
            }
        }
    }
}


function ElementTotals($elementid)
{
    global $DB ;
    
    $childs = $DB->get_records('customnavigation', array('parent_id'=>$elementid));

    if($childs){  
        foreach ($childs as $key => $child) {
                $rechild = ElementTotals($child->id);
                if($rechild){ 
                    $childs = array_merge($childs,$rechild);
                }
        }
    }

    return $childs ;
}

function GetRoles()
{
    global $DB;
    $roles = $DB->get_records('role');
    $rl = [];
    $rl[-1] .= get_string('site_admin', 'block_customnavigation');
    $rl[0] .=get_string('no_roles', 'block_customnavigation');

    foreach ($roles as $role) {
        $rl[$role->id] .= $role->shortname;
    }
    $title = get_string('assign_roles', 'block_customnavigation') . get_string('clearall', 'block_customnavigation');
    $select = \theme_remui\widget::select2( $title ,
    $rl,
    'roles',
    '-2',
    'roles[]',
    false,
    ['class' => 'roleselection'],
    true
    );
    
    return $select;
}