<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Changed the class name from "iomad_admin_menu" to "mt_admin_menu" 
 * to prevent the fetal errors on pages
 * 
 * @author Baikare Sandeep
 * @since 27/12/2018
 * @author Paradiso
 * 
*/
if( !function_exists('mt_admin_menu') ):
    class mt_admin_menu {
        public static function getmenu() {
            global $CFG, $SESSION, $USER;
            $edittitle = '';
            if (empty($SESSION->currenteditingcompany) && empty($USER->company)) {
                $edittitle = get_string('createcompany', 'block_iomad_company_admin');
            } else {
                $edittitle = get_string('editcompany', 'block_iomad_company_admin');
            }
            $returnarray = array(
                'editdepartments' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('editdepartment', 'block_iomad_company_admin'),
                    'url' => 'company_departments.php',
                    'cap' => 'block/iomad_company_admin:edit_departments',
                    'icondefault' => 'managedepartment',
                    'style' => 'department',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa-gear'
                ),
                'editcompany' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('editcompany', 'block_iomad_company_admin'),
                    'url' => 'company_edit_form.php',
                    'cap' => 'block/iomad_company_admin:company_edit',
                    //'icondefault' => 'editcompany',
                    'style' => 'company',
                    'icon' => 'fa-building',
                    'iconsmall' => 'fa fa-edit'
                ),
                'assignmanagers' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('assignmanagers', 'block_iomad_company_admin'),
                    'url' => 'company_managers_form.php',
                    'cap' => 'block/iomad_company_admin:company_manager',
                    'icondefault' => 'assigndepartmentusers',
                    'style' => 'department',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa fa-user'
                ),
                'userprofiles' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('userprofiles', 'block_iomad_company_admin'),
                    'url' => 'company_user_profiles.php',
                    'cap' => 'block/iomad_company_admin:company_user_profiles',
                    'icondefault' => 'optionalprofiles',
                    'style' => 'user',
                    'icon' => 'fa-user',
                    'iconsmall' => 'fa-plus-square'
                ),
                'assignusers' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('assignusers', 'block_iomad_company_admin'),
                    'url' => 'company_users_form.php',
                    'cap' => 'block/iomad_company_admin:company_user',
                    'icondefault' => 'assignusers',
                    'style' => 'user',
                    'icon' => 'fa-user',
                    'iconsmall' => 'fa fa-users'
                ),
                'assigncourses' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('assigncourses', 'block_iomad_company_admin'),
                    'url' => 'company_courses_form.php',
                    'cap' => 'block/iomad_company_admin:company_course',
                    'icondefault' => 'assigncourses',
                    'style' => 'course',
                    'icon' => 'fa-file-text',
                    'iconsmall' => 'fa-chevron-circle-right'
                ),
                'assigncourses' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 1,
                    'name' => get_string('restrictcapabilities', 'block_iomad_company_admin'),
                    'url' => 'company_capabilities.php',
                    'cap' => 'block/iomad_company_admin:restrict_capabilities',
                    'icondefault' => 'useredit',
                    'style' => 'user',
                    'icon' => 'fa-user',
                    'iconsmall' => 'fa-gear'
                ),
                'createuser' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('createuser', 'block_iomad_company_admin'),
                    'url' => 'company_user_create_form.php',
                    'cap' => 'block/iomad_company_admin:user_create',
                    'icondefault' => 'usernew',
                    'style' => 'user',
                    'icon' => 'fa-user',
                    'iconsmall' => 'fa-user-plus',
                ),
                'edituser' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('edituser', 'block_iomad_company_admin'),
                    'url' => 'editusers.php',
                    'cap' => 'block/iomad_company_admin:user_create',
                    'icondefault' => 'useredit',
                    'style' => 'user',
                    'icon' => 'fa-user',
                    'iconsmall' => 'fa-users',
                ),
                'assigntocompany' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('assigntocompany', 'block_iomad_company_admin'),
                    'url' => 'company_users_form.php',
                    'cap' => 'block/iomad_company_admin:company_user',
                    'icondefault' => '',
                    'style' => 'user',
                    'icon' => 'fa-building',
                    'iconsmall' => 'fa-chevron-circle-left',
                ),
                'enroluser' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('enroluser', 'block_iomad_company_admin'),
                    'url' => 'company_course_users_form.php',
                    'cap' => 'block/iomad_company_admin:company_course_users',
                    'icondefault' => '',
                    'style' => 'user',
                    'icon' => 'fa-file-text',
                    'iconsmal' => 'fa-user',
                ),
                'uploadfromfile' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('user_upload_title', 'block_iomad_company_admin'),
                    'url' => 'uploaduser.php',
                    'cap' => 'block/iomad_company_admin:user_upload',
                    'icondefault' => 'up',
                    'style' => 'user',
                    'icon' => 'fa-file',
                    'iconsmall' => 'fa-upload',

                ),
                'downloadusers' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('users_download', 'block_iomad_company_admin'),
                    'url' => 'user_bulk_download.php',
                    'cap' => 'block/iomad_company_admin:user_upload',
                    'icondefault' => 'down',
                    'style' => 'user',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa-download',
                ),
                'bulkusers' => array(
                    'category' => 'UserAdmin',
                    'tab' => 2,
                    'name' => get_string('users_bulk', 'block_iomad_company_admin'),
                    'url' => '/admin/user/user_bulk.php',
                    'cap' => 'block/iomad_company_admin:company_add',
                    'icondefault' => 'users',
                    'style' => 'user',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa-reply-all'
                ),
                'CompanyCohorts' => array(
                    'category' => 'CompanyAdmin',
                    'tab' => 2,
                    'name' => get_string('companyCohorts', 'local_mt_dashboard'),
                    'url' => '/local/mt_dashboard/cohort/index.php',
                    'cap' => 'local/mt_dashboard:companycohort_view',
                    'icondefault' => 'managecompany',
                    'style' => 'company',
                    'icon' => 'fa-globe',
                    'iconsmall' => 'fa-globe'
                ),
                'createcourse' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('createcourse', 'block_iomad_company_admin'),
                    'url' => 'company_course_create_form.php',
                    'cap' => 'block/iomad_company_admin:createcourse',
                    'icondefault' => 'createcourse',
                    'style' => 'course',
                    'icon' => 'fa-file-text',
                    'iconsmall' => 'fa-plus-square',
                ),
                'assigntocompany' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('assigntocompany', 'block_iomad_company_admin'),
                    'url' => 'company_courses_form.php',
                    'cap' => 'block/iomad_company_admin:company_course',
                    'icondefault' => 'assigntocompany',
                    'style' => 'course',
                    'icon' => 'fa-building',
                    'iconsmall' => 'fa-chevron-circle-left'
                ),
                'managecourses' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('iomad_courses_title', 'block_iomad_company_admin'),
                    'url' => 'iomad_courses_form.php',
                    'cap' => 'block/iomad_company_admin:managecourses',
                    'icondefault' => 'managecoursesettings',
                    'style' => 'course',
                    'icon' => 'fa-file-text',
                    'iconsmall' => 'fa-gear',
                ),
                'enroluser' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('enroluser', 'block_iomad_company_admin'),
                    'url' => 'company_course_users_form.php',
                    'cap' => 'block/iomad_company_admin:company_course_users',
                    'icondefault' => 'userenrolements',
                    'style' => 'course',
                    'icon' => 'fa-file-text',
                    'iconsmall' => 'fa-user',
                ),
                'managegroups' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('managegroups', 'block_iomad_company_admin'),
                    'url' => 'company_groups_create_form.php',
                    'cap' => 'block/iomad_company_admin:edit_groups',
                    'icondefault' => 'groupsedit',
                    'style' => 'group',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa-gear',
                ),
                'assigngroups' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('assigncoursegroups', 'block_iomad_company_admin'),
                    'url' => 'company_groups_users_form.php',
                    'cap' => 'block/iomad_company_admin:assign_groups',
                    'icondefault' => 'groupsassign',
                    'style' => 'group',
                    'icon' => 'fa-group',
                    'iconsmall' => 'fa-plus-square',
                ),
                'classrooms' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('classrooms', 'block_iomad_company_admin'),
                    'url' => 'classroom_list.php',
                    'cap' => 'block/iomad_company_admin:classrooms',
                    'icondefault' => 'teachinglocations',
                    'style' => 'company',
                    'icon' => 'fa-map-marker',
                    'iconsmall' => 'fa-gear',
                ),
                'cohort_sync' => array(
                    'category' => 'CourseAdmin',
                    'tab' => 3,
                    'name' => get_string('cohort_sync', 'local_mt_dashboard'),
                    'url' => '/local/mt_dashboard/cohort_sync/index.php',
                    'cap' => 'local/mt_dashboard:viewcohortsync',
                    'icondefault' => 'learningpath',
                    'style' => 'company',
                    'icon' => 'fa-map-signs',
                    'iconsmall' => 'fa-gear',
                ),
            );
            $returnarray['manageiomadlicenses'] = array(
                    'category' => 'LicenseAdmin',
                    'tab' => 4,
                    'name' => get_string('managelicenses', 'block_iomad_company_admin'),
                    'url' => 'company_license_list.php',
                    'cap' => 'block/iomad_company_admin:edit_my_licenses',
                    'icondefault' => 'licensemanagement',
                    'style' => 'license',
                    'icon' => 'fa-legal',
                    'iconsmall' => 'fa-gear',
                );
            $returnarray['licenseusers'] = array(
                    'category' => 'LicenseAdmin',
                    'tab' => 4,
                    'name' => get_string('licenseusers', 'block_iomad_company_admin'),
                    'url' => 'company_license_users_form.php',
                    'cap' => 'block/iomad_company_admin:allocate_licenses',
                    'icondefault' => 'userlicenseallocations',
                    'style' => 'license',
                    'icon' => 'fa-legal',
                    'iconsmall' => 'fa-user'
                );
            $returnarray['EmailTemplates'] = array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('blocktitle', 'local_email'),
                'url' => '/local/email/template_list.php',
                'cap' => 'local/email:list',
                'icondefault' => 'emailtemplates',
                'style' => 'company',
                'icon' => 'fa-inbox',
                'iconsmall' => 'fa-envelope'
            );
            $returnarray['CompanyApperance'] = array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('companyapperance', 'local_mt_dashboard'),
                'url' => '/admin/settings.php?section=tenantsettingremui',
                'cap' => 'local/mt_dashboard:companyapperance_view',
                'icondefault' => 'emailtemplates',
                'style' => 'company',
                'icon' => 'fa-inbox',
                'iconsmall' => 'fa-desktop'
            );
            $returnarray['ShopSettings_list'] = array(
                'category' => 'ECommerceAdmin',
                'tab' => 6,
                'name' => get_string('courses', 'block_iomad_commerce'),
                'url' => '/blocks/iomad_commerce/courselist.php',
                'cap' => 'block/iomad_commerce:admin_view',
                'icondefault' => 'courses',
                'style' => 'ecomm',
                'icon' => 'fa-file-text',
                'iconsmall' => 'fa-money'
            );
            $returnarray['Orders'] = array(
                'category' => 'ECommerceAdmin',
                'tab' => 6,
                'name' => get_string('orders', 'block_iomad_commerce'),
                'url' => '/blocks/iomad_commerce/orderlist.php',
                'cap' => 'block/iomad_commerce:admin_view',
                'icondefault' => 'orders',
                'style' => 'ecomm',
                'icon' => 'fa-truck',
                'iconsmall' => 'fa-eye'
            );
            $returnarray['companyframeworks'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('companyframeworks', 'block_iomad_company_admin'),
                'url' => '/blocks/iomad_company_admin/company_competency_frameworks_form.php',
                'cap' => 'block/iomad_company_admin:company_framework',
                'icondefault' => 'assigntocompany',
                'style' => 'competency',
                'icon' => 'fa-list',
                'iconsmall' => 'fa-chevron-circle-right'
            );
            $returnarray['iomadframeworksettings'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('frameworksettings', 'block_iomad_company_admin'),
                'url' => '/blocks/iomad_company_admin/iomad_frameworks_form.php',
                'cap' => 'block/iomad_company_admin:manageframeworks',
                'icondefault' => 'managecoursesettings',
                'style' => 'competency',
                'icon' => 'fa-list',
                'iconsmall' => 'fa-cog'
            );
            $returnarray['editframeworks'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('competencyframeworks', 'tool_lp'),
                'url' => '/admin/tool/lp/competencyframeworks.php?pagecontextid=1',
                'cap' => 'block/iomad_company_admin:competencyview',
                'icondefault' => 'courses',
                'style' => 'competency',
                'icon' => 'fa-list',
                'iconsmall' => 'fa-eye'
            );
            $returnarray['companytemplates'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('companytemplates', 'block_iomad_company_admin'),
                'url' => '/blocks/iomad_company_admin/company_competency_templates_form.php',
                'cap' => 'block/iomad_company_admin:company_template',
                'icondefault' => 'assigntocompany',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-chevron-circle-right'
            );
            $returnarray['iomadtemplatesettings'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('templatesettings', 'block_iomad_company_admin'),
                'url' => '/blocks/iomad_company_admin/iomad_templates_form.php',
                'cap' => 'block/iomad_company_admin:managetemplates',
                'icondefault' => 'managecoursesettings',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-cog'
            );
            $returnarray['edittemplates'] = array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('templates', 'tool_lp'),
                'url' => '/admin/tool/lp/learningplans.php?pagecontextid=1',
                'cap' => 'block/iomad_company_admin:templateview',
                'icondefault' => 'userenrolements',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-eye'
            );
            // Reports Menus
            $returnarray['reportattendence'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_attendance'),
                'url' => '/local/report_attendance/index.php',
                'cap' => 'local/report_attendance:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-gear'
            );
            $returnarray['reportcompletion'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_completion'),
                'url' => '/local/report_completion/index.php',
                'cap' => 'local/report_completion:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-gear'
            );
            $returnarray['reportlicenseusage'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_license_usage'),
                'url' => '/local/report_license_usage/index.php',
                'cap' => 'local/report_license_usage:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-gear'
            );
            $returnarray['reportlicense'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_license'),
                'url' => '/local/report_license/index.php',
                'cap' => 'local/report_license:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-gear'
            );
            $returnarray['reportuserlicense'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_user_licenses'),
                'url' => '/local/report_user_licenses/index.php',
                'cap' => 'local/report_user_licenses:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-gear'
            );
            $returnarray['reportscormoverview'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_scorm_overview'),
                'url' => '/local/report_scorm_overview/index.php',
                'cap' => 'local/report_scorm_overview:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-sitemap'
            );
            $returnarray['reportuserlicenseallocations'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_user_license_allocations'),
                'url' => '/local/report_user_license_allocations/index.php',
                'cap' => 'local/report_user_license_allocations:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-cog'
            );
            $returnarray['reportuserlogins'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_user_logins'),
                'url' => '/local/report_user_logins/index.php',
                'cap' => 'local/report_user_logins:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-sign-in'
            );
            $returnarray['reportusers'] = array(
                'category' => 'ReportsAdmin',
                'tab' => 7,
                'name' => get_string('pluginname', 'local_report_users'),
                'url' => '/local/report_users/index.php',
                'cap' => 'local/report_users:view',
                'icondefault' => 'userenrolements',
                'style' => 'reports',
                'icon' => 'fa-user',
                'iconsmall' => 'fa-user'
            );
            return $returnarray;
        }
    }
endif;
