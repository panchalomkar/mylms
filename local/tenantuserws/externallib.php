<?php
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

/**
 * External Web Service Template
 * @author     VaibhavGhadage
 * @package    Tenant User API
 * @since      7 Nov 2019
 *                  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
//require_once('../../config.php');

class local_tenantws_external extends external_api {    
    
    /**
     * TENANT USER API
     */
    public static function get_tenant_users_parameters() {
        $params = array(
            'tenant' => new external_multiple_structure(new external_single_structure(array(
                'username' => new external_value(PARAM_TEXT, 'username', VALUE_OPTIONAL),
            ))) 
        );
        
        return new external_function_parameters($params);
    }

    
    public static function get_tenant_users($tenant = array()) {
        global $DB, $CFG,$PAGE;
        //Parameter validation
        $params = self::validate_parameters(self::get_tenant_users_parameters(),
                array('tenant' => $tenant));
        $params = array_shift($params['tenant']); 
        $transaction = $DB->start_delegated_transaction();
        
        $username = $params['username'];

        //get userid from user email
        $user = $DB->get_record('user',array("username" => $username));
        $userpicture = new user_picture($user);
        $userpicture->size = 1; // Size f1.
        $profileimageurl = $userpicture->get_url($PAGE);
        $returndata = array();
        if(!empty($user->id))
        {
            //get company from userid
            $sql = "SELECT c.*,c.id as companyid, cu.* 
                 FROM {company} c 
                    JOIN {company_users} cu 
                    ON c.id = cu.companyid
                    JOIN {course_categories} cc 
                    ON c.category = cc.id 
                    WHERE cu.userid = ?    
                    ";
            $tenant_user = $DB->get_records_sql($sql,array($user->id));
        }else{
                $returndata[]['companyid'] = '';
                $returndata[]['classname'] = '';
                $returndata[]['userpic'] = '';
                $returndata[]['companyname'] = '';
                $returndata[]['shortname'] = '';
                $returndata[]['tenant_logo'] = '';
                $returndata[]['tenant_compact_logo'] = '';
                $returndata[]['tenant_brandprimary'] = '';
                $returndata[]['tenant_bodycolorotherpages'] = '';
                $returndata[]['tenant_bodybackground'] = '';
                $returndata[]['tenant_favicon'] = '';
                $returndata[]['auth_type'] = '';
                $returndata[]['url'] = '';
        }
        if(!empty($tenant_user))
        {

            foreach($tenant_user as $tenant)
            {
                if ($userdepartments = $DB->get_records_sql("SELECT d.* FROM {department} d
                JOIN {company_users} cu ON (d.company = cu.companyid AND d.id = cu.departmentid)
                WHERE cu.userid = :userid
                AND cu.companyid = :companyid
                ORDER BY  d.name",
                array('userid' => $user->id, 'companyid' => $tenant->companyid))) {
               
                } 
                foreach ($userdepartments as $key => $department) {
                  $departname = $department->name;
                }
                $returndata[]['companyid'] = $tenant->companyid;
                $returndata[]['classname'] = $departname;
		$returndata[]['userpic'] = $profileimageurl->out(false);
		$returndata[]['userfullname'] = fullname($user);

                //tenant name
                $returndata[]['companyname'] = $tenant->name;

                //tenant short name
                $returndata[]['shortname'] = $tenant->shortname;


                //tenant logo
                $tenant_logo = "tenant_logo_$tenant->companyid";

                        $route = $DB->get_record_sql('SELECT * FROM {files} WHERE filearea = ? AND component = ? AND source <> ?',[$tenant_logo, 'theme_remui', 'NULL']);
                        $firstdir = substr($route->contenthash, 0, 2);
                        $seconddir = substr($route->contenthash, 2, 2);
                        $uploaddir = $CFG->dataroot.'/filedir/'.$firstdir.'/'.$seconddir.'/';
                        $path = $uploaddir.$route->contenthash;
                        if(empty($path))
                            $path = $CFG->wwwroot.'/local/multitenant/pix/company_logo.png';
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        $returndata[]['tenant_logo'] =  $base64;
                        
                //tenant compact logo
                $tenant_compact_logo = "tenant_logo_compact_$tenant->companyid";
                        $routeC = $DB->get_record_sql('SELECT * FROM {files} WHERE filearea = ? AND component = ? AND source <> ?',[$tenant_compact_logo, 'theme_remui', 'NULL']);
                        $firstdirC = substr($routeC->contenthash, 0, 2);
                        $seconddirC = substr($routeC->contenthash, 2, 2);
                        $uploaddirC = $CFG->dataroot.'/filedir/'.$firstdirC.'/'.$seconddirC.'/';
                        $pathC = $uploaddirC.$routeC->contenthash;
                        if(empty($pathC))
                            $pathC = $CFG->wwwroot.'/local/multitenant/pix/company_logo.png';
                        $typeC = pathinfo($pathC, PATHINFO_EXTENSION);
                        $dataC = file_get_contents($pathC);
                        $base64C = 'data:image/' . $typeC . ';base64,' . base64_encode($dataC);
                            $returndata[]['tenant_compact_logo'] =  $base64C;

                //tenant brand primary
                $tenant_brandprimary = $DB->get_field('config_plugins','value',array("name" => "brandprimary_$tenant->companyid"));  
                !empty($tenant_brandprimary)?$returndata[]['tenant_brandprimary'] = $tenant_brandprimary:$returndata[]['tenant_brandprimary'] = '';

                //tenant body color other pages
                $tenant_bodycolorotherpages = $DB->get_field('config_plugins','value',array("name" => "bodycolorotherpages_$tenant->companyid"));  
                !empty($tenant_bodycolorotherpages)?$returndata[]['tenant_bodycolorotherpages'] = $tenant_bodycolorotherpages:$returndata[]['tenant_bodycolorotherpages'] = '';

                //tenant body background
                $tenant_bodybackground = $DB->get_field('config_plugins','value',array("name" => "bodybackground_$tenant->companyid"));  
                !empty($tenant_bodybackground)?$returndata[]['tenant_bodybackground'] = $tenant_bodybackground:$returndata[]['tenant_bodybackground'] = '';

                //tenant favicon
                $tenant_favicon = $DB->get_field('config_plugins','value',array("name" => "tenant_favicon_$tenant->companyid")); 
                !empty($tenant_favicon)?$returndata[]['tenant_favicon'] = $tenant_favicon:$returndata[]['tenant_favicon'] = '';

                //tenant auth type
                $returndata[]['auth_type'] = '';

                //tenant SSO URL
		$returndata[]['url'] = '';
		$returndata[]['appversion'] = '1.0.1';
            }
        }
//	$returndata[]['appversion'] = '1.0.1';
        $transaction->allow_commit();
        
        //return data
        return array(
            'tenant' =>  $returndata,
        );
    }

    public static function get_tenant_users_returns() {
        return new external_function_parameters(
            array(
                'tenant' => new external_multiple_structure(
                    new external_single_structure (
                        array(
                            'companyid' => 
                                new external_value(PARAM_TEXT, 'users company id', VALUE_OPTIONAL),
                                'classname' => 
                                new external_value(PARAM_TEXT, 'users company id', VALUE_OPTIONAL),
                                'userpic' => 
				new external_value(PARAM_TEXT, 'users company id', VALUE_OPTIONAL),
				 'userfullname' =>
				 new external_value(PARAM_TEXT, 'users company id', VALUE_OPTIONAL),
				  'appversion' =>
                                 new external_value(PARAM_TEXT, 'app version', VALUE_OPTIONAL),
                            'companyname' => 
                                new external_value(PARAM_TEXT, 'users company', VALUE_OPTIONAL),
                             'shortname' => 
                                new external_value(PARAM_TEXT, 'users company shortname', VALUE_OPTIONAL),
                            'tenant_logo' => 
                                new external_value(PARAM_TEXT, 'tenant logo', VALUE_OPTIONAL),    
                            'tenant_compact_logo' => 
                                new external_value(PARAM_TEXT, 'tenant compact logo', VALUE_OPTIONAL),    
                            'tenant_brandprimary' => 
                                new external_value(PARAM_TEXT, 'tenant brandprimary', VALUE_OPTIONAL),    
                            'tenant_bodycolorotherpages' => 
                                new external_value(PARAM_TEXT, 'tenant body color other pages', VALUE_OPTIONAL),    
                            'tenant_bodybackground' => 
                                new external_value(PARAM_TEXT, 'tenant body bodybackground', VALUE_OPTIONAL),    
                            'tenant_favicon' => 
                                new external_value(PARAM_TEXT, 'tenant favicon', VALUE_OPTIONAL),    
                            'auth_type' => 
                                new external_value(PARAM_TEXT, 'auth type', VALUE_OPTIONAL),   
                            'url' => 
                                new external_value(PARAM_TEXT, 'SSO url', VALUE_OPTIONAL),    
                        )                          
                    )
                )
            )
        );
    }





    /**
     * TENANT USER COURSE CATEGORIES
     */
    public static function get_tenant_cat_parameters() {
        $params = array(
            'tenant_cat' => new external_multiple_structure(new external_single_structure(array(
                'username' => new external_value(PARAM_TEXT, 'username', VALUE_OPTIONAL),
                'tenantid' => new external_value(PARAM_TEXT, 'tenantid', VALUE_OPTIONAL),
            ))) 
        );
        
        return new external_function_parameters($params);
    }

    
    public static function get_tenant_cat($tenant_cat = array()) {
        global $DB, $CFG;
        //echo "dd";die;
        //Parameter validation
        $params = self::validate_parameters(self::get_tenant_cat_parameters(),
               array('tenant_cat' => $tenant_cat));
        $params = array_shift($params['tenant_cat']); 
        $transaction = $DB->start_delegated_transaction();
        
        $username = $params['username'];
        $tenantid = $params['tenantid'];
        require_once($CFG->dirroot . "/theme/remui/classes/utility.php");
        require_once($CFG->libdir. '/coursecatlib.php');
        $categories = \coursecat::make_categories_list();
        //$company = $DB->get_record('company', array('id' => $SESSION->currenteditingcompany));
        $categories= \theme_remui\utility::user_course_categories($categories,$tenantid);
        
        if(!empty($categories))
        {
            //tenant category
            foreach($categories as $id => $cat)
            {
                $catobj = \coursecat::get($id);
                $parents = $catobj->get_parents();

                $category = new stdClass();

                $category->id = $catobj->id;
                $category->name = $catobj->name;              
                $category->description = $catobj->description;
                $category->descriptionformat = $catobj->descriptionformat;
                $category->parent = $catobj->parent;
                $category->sortorder = $catobj->sortorder;
                $category->coursecount = $catobj->coursecount;
                $category->depth = $catobj->depth;
                $category->path = $catobj->path;

                $allcategories[] = $category;
            }
                foreach ($allcategories as $k => $cat) {
                    $returndata[$k]['id'] = !empty($cat->id)?$cat->id:'';
                    $returndata[$k]['name'] = !empty($cat->name)?$cat->name:'';
                    $returndata[$k]['description'] = !empty($cat->description)?strip_tags($cat->description):'';
                    $returndata[$k]['descriptionformat'] = !empty($cat->descriptionformat)?strip_tags($cat->descriptionformat):'';
                    $returndata[$k]['parent'] = !empty($cat->parent)?$cat->parent:'';
                    $returndata[$k]['sortorder'] = !empty($cat->sortorder)?$cat->sortorder:'';
                    $returndata[$k]['coursecount'] = !empty($cat->coursecount)?$cat->coursecount:'';
                    $returndata[$k]['depth'] = !empty($cat->depth)?$cat->depth:'';
                    $returndata[$k]['path'] = !empty($cat->path)?$cat->path:'';
                }
        }
        else
        {
            $returndata[0]['id'] = '';
            $returndata[0]['name'] = '';
            $returndata[0]['description'] = '';
            $returndata[0]['descriptionformat'] = '';
            $returndata[0]['parent'] = '';
            $returndata[0]['sortorder'] = '';
            $returndata[0]['coursecount'] = '';
            $returndata[0]['depth'] = '';
            $returndata[0]['path'] = '';
        }
        $transaction->allow_commit();

        //return data
        return array(
            'tenant' =>  $returndata,
        );
    }

    public static function get_tenant_cat_returns() {
        return new external_function_parameters(
            array(
                'tenant' => new external_multiple_structure(
                    new external_single_structure (
                        array(
                            'id' => 
                                new external_value(PARAM_TEXT, 'id', VALUE_OPTIONAL),
                            'name' => 
                                new external_value(PARAM_TEXT, 'name', VALUE_OPTIONAL),
                            'description' => 
                                new external_value(PARAM_TEXT, 'description', VALUE_OPTIONAL),
                            'descriptionformat' => 
                                new external_value(PARAM_TEXT, 'descriptionformat', VALUE_OPTIONAL),
                            'parent' => 
                                new external_value(PARAM_TEXT, 'parent', VALUE_OPTIONAL),
                            'sortorder' => 
                                new external_value(PARAM_TEXT, 'sortorder', VALUE_OPTIONAL),
                            'coursecount' => 
                                new external_value(PARAM_TEXT, 'coursecount', VALUE_OPTIONAL),
                            'depth' => 
                                new external_value(PARAM_TEXT, 'depth', VALUE_OPTIONAL),
                            'path' => 
                                new external_value(PARAM_TEXT, 'path', VALUE_OPTIONAL),                            
                        )                          
                    )
                )
            )
        );
    }
    



    /**
     * TENANT USER COURSE CATEGORIES
     */
    public static function get_tenant_course_parameters() {
        $params = array(
            'tenant_course' => new external_multiple_structure(new external_single_structure(array(
                'categoryid' => new external_value(PARAM_TEXT, 'category id', VALUE_OPTIONAL),
            ))) 
        );
        
        return new external_function_parameters($params);
    }

    
    public static function get_tenant_course($tenant_course = array()) {
        global $DB, $CFG;
        
        //Parameter validation
        $params = self::validate_parameters(self::get_tenant_course_parameters(),
                array('tenant_course' => $tenant_course));
        $params = array_shift($params['tenant_course']); 
        $transaction = $DB->start_delegated_transaction();
        
        $categoryid = $params['categoryid'];
        require_once($CFG->dirroot . "/theme/remui/classes/utility.php");
        require_once($CFG->libdir. '/coursecatlib.php');
        
        $courses = \theme_remui\utility::get_courses($totalcount = false,
                                                        $search = null,
                                                        $category = $categoryid,
                                                        $limitfrom = 0,
                                                        $limitto = 0,
                                                        $mycourses = null,
                                                        $filters = null,
                                                          [],
                                                          true);
        if(!empty($courses))
        {
            //tenant category courses
            foreach($courses as $id => $cat)
            {
                $course = new stdClass();

                $course->courseid = $cat['courseid'];
                $course->coursename = $cat['coursename'];              
                $course->categoryname = $cat['categoryname'];
                $course->visible = $cat['visible'];
                $course->courseurl = $cat['courseurl'];
                $course->enrollusers = $cat['enrollusers'];
                $course->editcourse = $cat['editcourse'];
                $course->grader = $cat['grader'];
                $course->activity = $cat['activity'];
                $course->coursesummary = $cat['coursesummary'];
                $course->coursestartdate = $cat['coursestartdate'];
                $course->enrollmenticons = $cat['enrollmenticons'];
                $course->courseimage = $cat['courseimage'];

                $allcourses[] = $course;
            }
            
            foreach($allcourses as $k => $catobj)
            {
                $returndata[$k]['courseid'] = strip_tags($catobj->courseid);
                $returndata[$k]['coursename'] = strip_tags($catobj->coursename);              
                $returndata[$k]['categoryname'] = strip_tags($catobj->categoryname);
                $returndata[$k]['visible'] = strip_tags($catobj->visible);
                $returndata[$k]['courseurl'] = strip_tags($catobj->courseurl);
                $returndata[$k]['enrollusers'] = strip_tags($catobj->enrollusers);
                $returndata[$k]['editcourse'] = strip_tags($catobj->editcourse);
                $returndata[$k]['grader'] =strip_tags( $catobj->grader);
                $returndata[$k]['activity'] = strip_tags($catobj->activity);
                $returndata[$k]['coursesummary'] = strip_tags($catobj->coursesummary);
                $returndata[$k]['coursestartdate'] = strip_tags($catobj->coursestartdate);
                $returndata[$k]['enrollmenticons'] = strip_tags($catobj->enrollmenticons);
                $returndata[$k]['courseimage'] = strip_tags($catobj->courseimage);
            }                
        }
        else
        {
            $returndata[0]['courseid'] = '';
            $returndata[0]['coursename'] = '';
            $returndata[0]['categoryname'] = '';
            $returndata[0]['visible'] = '';
            $returndata[0]['courseurl'] = '';
            $returndata[0]['enrollusers'] = '';
            $returndata[0]['editcourse'] = '';
            $returndata[0]['grader'] = '';
            $returndata[0]['activity'] = '';
            $returndata[0]['coursesummary'] = '';
            $returndata[0]['coursestartdate'] = '';
            $returndata[0]['enrollmenticons'] = '';
            $returndata[0]['courseimage'] = '';

        }
        $transaction->allow_commit();

        //return data
        return array(
            'tenant' =>  $returndata,
        );
    }

    public static function get_tenant_course_returns() {
        return new external_function_parameters(
            array(
                'tenant' => new external_multiple_structure(
                    new external_single_structure (
                        array(
                            'courseid' => 
                                new external_value(PARAM_TEXT, 'course id', VALUE_OPTIONAL),
                            'coursename' => 
                                new external_value(PARAM_TEXT, 'course name', VALUE_OPTIONAL),
                            'categoryname' => 
                                new external_value(PARAM_TEXT, 'category name', VALUE_OPTIONAL),
                            'visible' => 
                                new external_value(PARAM_TEXT, 'visible', VALUE_OPTIONAL),
                            'courseurl' => 
                                new external_value(PARAM_TEXT, 'courseurl', VALUE_OPTIONAL),
                            'enrollusers' => 
                                new external_value(PARAM_TEXT, 'enrollusers', VALUE_OPTIONAL),
                            'editcourse' => 
                                new external_value(PARAM_TEXT, 'editcourse', VALUE_OPTIONAL),
                            'grader' => 
                                new external_value(PARAM_TEXT, 'grader', VALUE_OPTIONAL),
                            'activity' => 
                                new external_value(PARAM_TEXT, 'activity', VALUE_OPTIONAL),
                            'coursesummary' => 
                                new external_value(PARAM_TEXT, 'coursesummary', VALUE_OPTIONAL),
                            'coursestartdate' => 
                                new external_value(PARAM_TEXT, 'coursestartdate', VALUE_OPTIONAL),
                            'enrollmenticons' => 
                                new external_value(PARAM_TEXT, 'enrollmenticons', VALUE_OPTIONAL),
                            'courseimage' => 
                                new external_value(PARAM_TEXT, 'courseimage', VALUE_OPTIONAL),
                        )                          
                    )
                )
            )
        );
    }

}
