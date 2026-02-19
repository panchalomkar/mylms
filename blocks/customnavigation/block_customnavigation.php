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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.


require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.

class block_customnavigation extends block_base {

    /**
     *
     * @var string The name of the block
     */
    public $blockname = null;

    /**
     *
     * @var bool A switch to indicate whether content has been generated or not.
     */
    protected $contentgenerated = false;

    /**
     * Set the initial properties for the block
     */
    function init() {

        $this->title = get_string('pluginname', 'block_customnavigation');
    }

    /**
     *
     * @return bool Returns false
     */
    function instance_allow_multiple() {
        return false;
    }
    
    function has_config() {
        return true;
    }


    /**
     * Set the applicable formats for this block to all
     *
     * @return array
     */
    function applicable_formats() {
        return array (
                'all' => true
        );
    }

    /**
     * Allow the user to configure a block instance
     *
     * @return bool Returns true
     */
    function instance_allow_config() {
        return true;
    }

    /**
     * The navigation block cannot be hidden by default as it is integral to
     * the navigation of Moodle.
     *
     * @return false
     */
    function instance_can_be_hidden() {
        return true;
    }

    /**
     * Find out if an instance can be docked.
     *
     * @return bool true or false depending on whether the instance can be docked or not.
     */
    function instance_can_be_docked() {
        return false;
    }
   
    /**
     * Gets the content for this block by grabbing it from $this->page
     *
     * @return object $this->content
     */
    function get_content() {
        global $CFG, $USER, $PAGE,$DB;
        
        $activeLink ='';
        require_once(dirname(__FILE__).'/locallib.php');

        if ($this->contentgenerated === true) {
            return $this->content;
        }
        $PAGE->requires->jquery();
        $links = customnavigation_build_main_menu_array();
        $this->content = new stdClass ();
        $this->content->text = '';
        $addM = get_string('addmenus', 'block_customnavigation');
        $this->content->text .= $this->build_menu($links, false);
        
        $this->content->text .= '<script>' . PHP_EOL;
         $this->content->text .= "
            function customnavigation_set_active(index){
                $('.block_customnavigation ul.list-group > li a').each(function(i, obj) {
                
                   if(i == index){
                      $(obj).addClass('active');
                   }
                });
            }
            var active_link = '$activeLink';
            if(active_link){
                customnavigation_set_active(active_link);
            }


            $('.block_customnavigation .dropdown li').hover(function() {
                var self = $(this);
                found = self.find('ul').size();
                if(!found){
                    self.css('background-image', 'none');
                }
            });

            $('.dropdown .sub-links li:last').css('border-bottom', '0px');
            
        ";
        $this->content->text .= '</script>' . PHP_EOL;
        $this->contentgenerated = true;
        return $this->content;
    }




    function build_menu(array $menu, $is_sub=false, $parent = 0){
       
        global $COURSE, $USER, $CFG, $DB, $OUTPUT, $PAGE, $SESSION;
        
        $sub = "";
      
        $context = context_course::instance($COURSE->id);
        $role_sw = 0;
        if(has_capability('moodle/role:switchroles', $context) || is_role_switched($COURSE->id)){
            if($PAGE->pagelayout == 'frontpage' && $_SESSION['SESSION']->sr > 0){
                $role_sw = $_SESSION['SESSION']->sr;
            }else if(is_role_switched($COURSE->id) && $USER->access['rsw'][$context->path] > 0){
                $role_sw = $USER->access['rsw'][$context->path];
            }
        }

        $courses = enrol_get_all_users_courses($USER->id, true );
        $rolerecord = $DB->get_records('role_assignments', array('userid'=>$USER->id));

        $z = array();
        $i=0;
        foreach($courses as $course){
            $z[$i] = array(
                'label' => $course->fullname,
                'href' => $CFG->wwwroot.'/course/view.php?id='.$course->id,
                'type' => '',
                'module' => ''
            );
            $i++;
        }

/*
* If the supplied array is part of a sub-menu, add the
* sub-links class instead of the dropdown-vertical class
*/
    if(count($menu) > 0){
        if(!$is_sub){
            $attr = 'id="sidebar"';
            $ul = "<div class='wrapper w-navigation'><nav class='sidemenu-closed sidemenu-container slimScrollDiv' $attr>\n"; // Open the menu container
            $ul .= "<ul class = 'list-unstyled components'>";
        }
    }
    
    if($is_sub){
        $attr = ' class="list-unstyled collapse" id="subitem'.$parent.'"';
        $ul = "<ul $attr>\n"; // Open the menu container
    }    

/*
* Loop through the array to extract element values
*/
    foreach($menu as $item)
    {
       $attr_items = (!$is_sub) ? ' class="menu-title"' : ' class="menu-title"';
       $sub = "";
       $alt = '';
       $item = (array)$item;

        if(get_string_manager()->string_exists($item['label'], 'block_customnavigation')){
            $label = get_string(strtoupper($item['label']), 'block_customnavigation'); 
        }
        
        $identifier = str_replace(' ','-',$item['label']);
        if(get_string_manager()->string_exists(strtoupper($identifier), 'block_customnavigation')){
            $label = get_string(strtoupper($identifier), 'block_customnavigation'); 
        } else {
            $label = $item['label'];
        }

       $tooltip = $label;
       $label= '<span ' . $attr_items . '>'.ucfirst($label).'</span>';
        //=================================================

       $href = $item ['href'];

           if(preg_match('#^https://.*#s', $href)){

           }else if(!preg_match('#^http://.*#s', $href) && trim($href) != ''){
               $href = $CFG->wwwroot . $href;
        }
   

       $pages = (isset($item ['pages']) && is_array($item ['pages']) && count($item ['pages'])) ? $item ['pages'] : false;
       $icon = isset($item ['icon']) ? $item ['icon'] : null;

       $roleid = '';
       $rolechk ='';
        if(isset($item['roleid'])){
            $assignrole = explode(',',$item['roleid']);
            if(count($rolerecord) == 0){
                $rolechk = in_array("0", $assignrole);
            } else {
                foreach($rolerecord as $rolerecords){
                    if (!$rolechk){ //line added by Dani Otelch at 14-10-2015
                        $roleid = $rolerecords->roleid;
                        $rolechk = in_array($roleid, $assignrole);
                    } //--------
                }
            }
            
            if($role_sw){
                $rolechk = in_array(strval($role_sw), $assignrole);
            }

           
            if(!$role_sw){
              
                $displaytoadmin = in_array("-1", $assignrole);
                if($displaytoadmin == null or $displaytoadmin == false){
                    if($displaytoadmin == null or $displaytoadmin == false){
                        $displaytoadmin = in_array(12, $assignrole);
                        $displaytoadmin = in_array(11, $assignrole);
                    }
                 }
            }

        }

     
        if(!$role_sw and !isset($item['roleid'])){
            $displaytoadmin = true;
        }
       


        $instanseid='';
        if(isset($item['inst_id'])){
            $instanseid = $item['inst_id'];
        }
        $type = isset($item ['type']) ? $item ['type'] : '';
        $module =isset($item ['module']) ? $item ['module'] : '';

    if((!is_siteadmin() or ($role_sw)) or $displaytoadmin == true){
    /*
    * Because each pages element is another array, we
    * need to loop again. This time
    */
        if($pages && $module != 'my_courses')
        {
            $ul .= '<li class="dropdown nav-item" data-parent="'.$parent.'">';
            $sub .= $this->build_menu($pages, true, $item['id']);
            
        }else if($module == 'my_courses'){
            $item ['pages'] =$z;
            $pages = $item ['pages'];
        }
        else
        {
            $sub = null;
        }

       if($icon){
           $icon_url = customnavigation_get_icon_url($icon);
           if($icon){
                   $icon = "<span class=\"media-left\" title=\"" . ucfirst(strtolower($tooltip)) ."\"><i class=\"fa " . $icon . "\"></i></span>\n\t";
           } else {
                   $icon = ""; /*"<span class=\"icon\" title=\"" . ucfirst(strtolower($tooltip)) ."\"><img src=\"{$icon_url}\"><span class='vertical-align'></span></span>\n\t";*/
           }
       }

   
        $type = isset($item ['type']) ? $item ['type'] : '';
        if($type =='module'){
            if($item ['module'] == 'explore_courses'){
                $href = $CFG->wwwroot."/course/explore_courses.php";
            }
            if($item ['module'] == 'my_blogs'){
                $href = $CFG->wwwroot."/blog/index.php?userid=$USER->id";
            }
            if($item ['module'] == 'calendar'){
                $href = $CFG->wwwroot ."/calendar/view.php?view=month&time=" . time ();
            }
            if($item ['module'] == 'current_course'){
                $href = $CFG->wwwroot."/blocks/customnavigation/my_courses.php";
            }
            if($item ['module'] == 'my_courses'){
                $href = $CFG->wwwroot."/blocks/customnavigation/my_courses.php";
            }
        }

        if(isset($item['target'])){
            $target = "target='" . $item['target'] . "'";
        } else {
            $target = '';
        }

        $asignuserid = '';
       


       
        
            if(isset($item ['asignuserid'])){
                $asignuserid= $item ['asignuserid'];
            }
            
            $tag = "";
            $active = '';
            
            $active = ($this->check_if_active($href))?'tab_active':'';
            
            if((is_siteadmin() && (!$role_sw)) or !isset($item['roleid'])){
                 if(!isset($sub)) $sub='';
                if($pages && $module != 'my_courses'){
                   
                    if($sub && $item['parent_id']==0){
                        $tag .= "\n\t<a class='accordion-toggle collapsed list-group-item list-group-item-action' data-toggle=\"collapse\" data-target=\"#subitem".$item['id']."\"  aria-haspopup=\"true\" aria-expanded=\"false\" $target>{$icon}{$label}";
                    } elseif($sub && $item['parent_id']<>0) {
                        
                         $tag = "\n\t<a class='accordion-toggle collapsed list-group-item list-group-item-action' data-toggle=\"collapse\" data-target=\"#subitem".$item['id']."\"  aria-haspopup=\"true\" aria-expanded=\"false\" $target>{$icon}{$label}";    
                        
                    }
                    else
                    {
                        $tag = "\n\t<a class='list-group-item list-group-item-action' $target href=\"{$href}\">{$icon}{$label}";   
                    }
                    
                } else {
                          if($item['parent_id']<>0)
                          {
                            $tag = "<li class='sidemenu-closed menu-exp $active'>";
                            $tag .= "\n\t<a class='dropdown-item' $target href=\"{$href}\">{$icon}{$label}";
                            $tag .= "</li>";
                          }else
                          {
                            $tag = "<li class='sidemenu-closed menu-exp $active'>";
                            $tag .= "\n\t<a class='list-group-item list-group-item-action' $target href=\"{$href}\">{$icon}{$label}";
                            $tag .= "</li>";
                          }
                }
            } else {
                if($USER->id == $asignuserid || $rolechk){

                                    if($pages && $module != 'my_courses'){
                        $tag = "\n\t<a class='accordion-toggle collapsed list-group-item list-group-item-action' data-toggle=\"collapse\" data-target=\"#subitem".$item['id']."\"  aria-haspopup=\"true\" aria-expanded=\"false\" $target>{$icon}{$label}";
                    } else {
                       
                        if($item['parent_id']==0)
                        {
                        $tag = "<li class='sidemenu-closed menu-exp $active'>";
                        $tag .= "\n\t<a class='list-group-item list-group-item-action' $target title=\"{$alt}\" href=\"{$href}\">{$icon}{$label}";
                        $tag .= "</li>";

                        }else
                        {
                            $tag = "<li class='sidemenu-closed menu-exp $active'>";
                            $tag .= "\n\t<a class='list-group-item list-group-item-action' $target title=\"{$alt}\" href=\"{$href}\">{$icon}{$label}";
                            $tag .= "</li>";
                        }
                    }
                }
            }
        if(!$sub){
            $ul .= $tag."</a>\n";
        }else{
            $ul .= $tag."</a>\n".$sub;
        }
            
       
        }
        


        unset($href, $label, $sub);
    }

        if(count($menu) > 0){
            if(!$is_sub){
                return $ul . "</ul></nav></div>\n";
            }else{
                return $ul . "</ul></li>\n";
            }
        } else {
            return "";
        }
    }




    /*
     * {@link block_tree::html_attributes()} is used to get the default arguments
     * and then we check whether the user has enabled hover expansion and add the
     * appropriate hover class if it has.
     *
     * @return array An array of HTML attributes
     */
    public function html_attributes() {
        $attributes = parent::html_attributes ();

        if (! empty ( $this->config->enablehoverexpansion ) && $this->config->enablehoverexpansion == 'yes') {
            $attributes ['class'] .= ' block_js_expansion';
        }

        return $attributes;
    }


    /**
     * Returns the role that best describes the navigation block...
     * 'navigation'
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }

    /**
     * Checks if this node is the active node.
     *
     * This is determined by comparing the action for the node against the
     * defined URL for the page. A match will see this node marked as active.
     *
     * @param int $strength One of URL_MATCH_EXACT, URL_MATCH_PARAMS, or URL_MATCH_BASE
     * @return bool
     */
    public function check_if_active(string $action) {
        global $FULLME , $CFG, $PAGE;
        $selfurl = new moodle_url($FULLME);
        
        if (substr($action, -1) !== '/' && $PAGE->bodyid != 'page-my-index') {
            $parts = explode('?', $action, 2);
            $action = $CFG->wwwroot.$parts[0].'/'.(isset($parts[1]) ? '?'.$parts[1] : '');
        }
       
        $action = new moodle_url($action);
        
        if ($action->compare($selfurl, URL_MATCH_EXACT)) {
            return true;
        } else if( $PAGE->pagetype == 'local-iomad-dashboard-index' && $action->compare($selfurl, URL_MATCH_BASE)){ 
            /*On company selection tenent menu should be selected*/
             return true;
        } 
        return false;
    }
}
