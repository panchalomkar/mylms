<?php


function local_customnavigation_extend_navigation(global_navigation $navigation) {

    //print_object($nav);
    global $PAGE, $CFG, $USER;

    
    require_once($CFG->dirroot.'/blocks/customnavigation/locallib.php');
    // $context = context_system::instance();
    $menus = customnavigation_build_main_menu_array();
   // $roles = get_user_roles($context);
  //  print_object($menus);die;
    $i = 0;
    if (isloggedin()) {
    foreach ($menus as $menu) {
        
        if (get_string_manager()->string_exists($menu->label, 'block_customnavigation')) {
                $label = get_string(strtoupper($menu->label), 'block_customnavigation');
            }

            $identifier = str_replace(' ', '-', $menu->label);
           
            if (get_string_manager()->string_exists(strtoupper($identifier), 'block_customnavigation')) {
                $label = get_string(strtoupper($identifier), 'block_customnavigation');
            } else {
                $label = $menu->label;
            }
            
            
        $allowed = false;
        $allowedroles = explode(',', $menu->roleid);
	foreach($allowedroles as $role) {
            if(!is_siteadmin() && user_has_role_assignment($USER->id, $role)) {
                $allowed = true;   
            }
           /* if(in_array($role->roleid, $allowedroles)) {
                $allowed = true;
            }*/
        }
        if(is_siteadmin() && in_array('-1', $allowedroles)) {
            $allowed = true;
        }
        if(!is_siteadmin() && in_array('7', $allowedroles)) {
            $allowed = true;
        }
        if(!$allowed) {
            continue;
        }
        if ($menu->type == 'container') {
        //$mynode = $navigation->find('home', navigation_node::TYPE_ROOTNODE);
        $main_node = $navigation->add($label, null, $navigation::TYPE_ROOTNODE, $menu->icon, 'custommenu', new pix_icon($menu->icon, ''));
        $main_node->add_class('has-sub');
        $main_node->isexpandable = true; 
        $main_node->forceopen = true; 
        
        $main_node->showinflatnavigation = true;
        if($menu->pages) {
            foreach ($menu->pages as $page) {
                if (get_string_manager()->string_exists($page->label, 'block_customnavigation')) {
                    $label = get_string(strtoupper($page->label), 'block_customnavigation');
                }
    
                $identifier1 = str_replace(' ', '-', $page->label);
               
                if (get_string_manager()->string_exists(strtoupper($identifier1), 'block_customnavigation')) {
                    $pagelabel = get_string(strtoupper($identifier1), 'block_customnavigation');
                } else {
                    $pagelabel = $page->label;
                }
                $pagenode = $main_node->add($pagelabel, $page->href, $navigation::NODETYPE_BRANCH, $page->icon, 'customsubmenu');
                $pagenode->isexpandable = false;
                $pagenode->set_parent($main_node);
                $pagenode->showinflatnavigation = true;
            }
        }
        } else {
           // $mynode = $navigation->find('home', navigation_node::TYPE_ROOTNODE);
            $main_node = $navigation->add($label, $menu->href, $navigation::TYPE_ROOTNODE, $menu->icon, 'custommenu');
            $main_node->add_class('has-sub');
            $main_node->isexpandable = false; 
            $main_node->forceopen = false; 
            $main_node->showinflatnavigation = true;
            if($menu->pages) {
                foreach ($menu->pages as $page) {
                    $pagenode = $main_node->add($label, $page->href, $navigation::NODETYPE_BRANCH, $page->icon, 'customsubmenu');
                    $pagenode->set_parent($main_node);
                    $pagenode->isexpandable = false;
                    $pagenode->showinflatnavigation = true;
                }
            }
        }
        $i++;
    }
    //$flatnav = new flat_navigation($PAGE);
    //print_object($flatnav);
    //die;
} 
    /*if (isloggedin()) {
        $mynode = $navigation->find('home', navigation_node::TYPE_ROOTNODE);
        $main_node = $mynode->add(get_string('mycourses1'), null, $navigation::TYPE_ROOTNODE, null, 'mycourses1', new pix_icon('i/course', ''));
       
       // $main_node->collapse = true;
        $main_node->isexpandable = false; 
        $main_node->forceopen = true; 
        $main_node->showinflatnavigation = true;
        //$main_node->hideicon = true;
        # add child node
        $parent = $navigation->find('mycourses1', navigation_node::TYPE_ROOTNODE);
       // $coursenode = $parent->add('ddd', null, $navigation::TYPE_COURSE, 'add', 1, new pix_icon('i/course', ''));
        //$coursenode->showinflatnavigation = true;
        $cgpareportnode = $parent->add(get_string('singlereportmenu', 'local_cgpa'), new moodle_url('/local/cgpa/students.php'), $navigation::NODETYPE_BRANCH);
        //$cgpareportnode->set_parent($main_node);
        //$cgpareportnode->isexpandable = true; 
        $cgpareportnode->nodetype = $navigation::NODETYPE_BRANCH;
        $cgpareportnode->isexpandable = true;
        $cgpareportnode->display = false;
        $cgpareportnode->showinflatnavigation = false;
       

        
        # add further child node for admin
       /* if (is_siteadmin()) {
        $consolidatedreportnode = $main_node->add(get_string('consolidatedreport', 'local_cgpa'), new moodle_url('/local/cgpa/consolidated_report.php'), $navigation::NODETYPE_BRANCH); 
        $consolidatedreportnode->set_parent($main_node);
        $consolidatedreportnode->showinflatnavigation = true;
        }
    }*/
   /* $flatnav = new flat_navigation($PAGE);
    print_object($flatnav);
   die;*/
}    