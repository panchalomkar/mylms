<?php

/**
 * Get all the cohorts defined anywhere in system.
 *
 * The function assumes that user capability to view/manage cohorts on system level
 * has already been verified. This function only checks if such capabilities have been
 * revoked in child (categories) contexts.
 *
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function getcompanyid(){
    global $USER,$SESSION;
    $companyid ='';
    if(is_siteadmin($USER->id) && !empty ($SESSION->currenteditingcompany)){
        $companyid =$SESSION->currenteditingcompany;
    }else if(\iomad::is_company_admin()){
        $companyid =\iomad::is_company_admin();
    }
    return $companyid;
}

/**
 * Get all the cohorts defined anywhere in system.
 *
 * The function assumes that user capability to view/manage cohorts on system level
 * has already been verified. This function only checks if such capabilities have been
 * revoked in child (categories) contexts.
 *
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function mt_dashboard_cohort_get_all_cohorts($page = 0, $perpage = 25, $search = '') {
    global $DB;
    $companyid = getcompanyid();
    $fields = "SELECT c.*, ".context_helper::get_preload_record_columns_sql('ctx');
    $countfields = "SELECT COUNT(*)";
    $sql = " FROM {cohort} c left join {company_cohorts}  cc ON c.id = cc.cohortid
             JOIN {context} ctx ON ctx.id = c.contextid ";
    $params = array();
    $wheresql = "WHERE companyid = $companyid";

    if ($excludedcontexts = mt_dashboard_cohort_get_invisible_contexts()) {
        list($excludedsql, $excludedparams) = $DB->get_in_or_equal($excludedcontexts, SQL_PARAMS_NAMED, 'excl', false);
        $wheresql = ' AND c.contextid '.$excludedsql;
        $params = array_merge($params, $excludedparams);
    }

    $totalcohorts = $allcohorts = $DB->count_records_sql($countfields . $sql . $wheresql, $params);

    if (!empty($search)) {
        list($searchcondition, $searchparams) = mt_dashboard_cohort_get_search_query($search, 'c');
        $wheresql .= ($wheresql ? ' AND ' : ' WHERE ') . $searchcondition;
        $params = array_merge($params, $searchparams);
        $totalcohorts = $DB->count_records_sql($countfields . $sql . $wheresql, $params);
    }

    $order = " ORDER BY c.name ASC, c.idnumber ASC";
    $cohorts = $DB->get_records_sql($fields . $sql . $wheresql . $order, $params, $page*$perpage, $perpage);

    // Preload used contexts, they will be used to check view/manage/assign capabilities and display categories names.
    foreach (array_keys($cohorts) as $key) {
        context_helper::preload_from_record($cohorts[$key]);
    }
    return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts' => $allcohorts);
}

/**
 * Returns list of contexts where cohorts are present but current user does not have capability to view/manage them.
 *
 * This function is called from {@link cohort_get_all_cohorts()} to ensure correct pagination in rare cases when user
 * is revoked capability in child contexts. It assumes that user's capability to view/manage cohorts on system
 * level has already been verified.
 *
 * @access private
 *
 * @return array array of context ids
*/
function mt_dashboard_cohort_get_invisible_contexts() {
    global $DB;
    if (is_siteadmin()) {
        // Shortcut, admin can do anything and can not be prohibited from any context.
        return array();
    }
    $records = $DB->get_recordset_sql("SELECT DISTINCT ctx.id, ".context_helper::get_preload_record_columns_sql('ctx')." ".
        "FROM {context} ctx JOIN {cohort} c ON ctx.id = c.contextid ");
    $excludedcontexts = array();
    foreach ($records as $ctx) {
        context_helper::preload_from_record($ctx);
        if (context::instance_by_id($ctx->id) == context_system::instance()) {
            continue; // System context cohorts should be available and permissions already checked.
        }
        if (!has_capability('local/mt_dashboard:companycohort_view', context::instance_by_id($ctx->id))) {
            $excludedcontexts[] = $ctx->id;
        }
    }
    $records->close();
    return $excludedcontexts;
}

/**
 * Get all the cohorts defined in given context.
 *
 * The function does not check user capability to view/manage cohorts in the given context
 * assuming that it has been already verified.
 *
 * @param int $contextid
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function mt_dashboard_cohort_get_cohorts($contextid, $page = 0, $perpage = 25, $search = '') {
    global $DB;

    $companyid = getcompanyid();
    $fields = "SELECT *";
    $countfields = "SELECT COUNT(1)";
    $sql = " FROM {cohort} c left join {company_cohorts}  cc ON c.id = cc.cohortid
             WHERE  companyid = $companyid";
    $params = array();
    $order = " ORDER BY name ASC, idnumber ASC";

    $totalcohorts = $allcohorts = $DB->count_records_sql($countfields . $sql, $params);
    
    if (!empty($search)) {
        list($searchcondition, $searchparams) = mt_dashboard_cohort_get_search_query($search);
        $sql .= ' AND ' . $searchcondition;
        $params = array_merge($params, $searchparams);
    }

    if (!empty($search)) {
        $totalcohorts = $DB->count_records_sql($countfields . $sql, $params);
    }
    $cohorts = $DB->get_records_sql($fields . $sql . $order, $params, $page*$perpage, $perpage);

    return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts' => $allcohorts);
}

/**
 * Returns navigation controls (tabtree) to be displayed on cohort management pages
 *
 * @param context $context system or category context where cohorts controls are about to be displayed
 * @param moodle_url $currenturl
 * @return null|renderable
 */
function mt_dashboard_cohort_edit_controls(context $context, moodle_url $currenturl) {
    $tabs = array();
    $currenttab = 'view';
    $viewurl = new moodle_url('/local/mt_dashboard/cohort/index.php', array('contextid' => $context->id));
 
    if (($searchquery = $currenturl->get_param('search'))) {
        $viewurl->param('search', $searchquery);
    }
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        // $tabs[] = new tabobject('view', new moodle_url($viewurl, array('showall' => 0)), get_string('systemcohorts', 'cohort'));
        $tabs[] = new tabobject('viewall', new moodle_url($viewurl), get_string('allcohorts', 'cohort'));
        $currenttab = 'viewall';
    } else {
        $tabs[] = new tabobject('view', $viewurl, get_string('cohorts', 'cohort'));
    }
    if (has_capability('local/mt_dashboard:companycohort_view', $context)) {
        $addurl = new moodle_url('/local/mt_dashboard/cohort/edit.php', array('contextid' => $context->id));
        $tabs[] = new tabobject('addcohort', $addurl, get_string('addcohort', 'cohort'));
        if ($currenturl->get_path() === $addurl->get_path() && !$currenturl->param('id')) {
            $currenttab = 'addcohort';
        }
 
    }
    if (count($tabs) >= 1) {
        return new tabtree($tabs, $currenttab);
    }
    return null;
}

/**
 * Add new cohort.
 *
 * @param  stdClass $cohort
 * @return int new cohort id
 */
function mt_dashboard_cohort_add_cohort($cohort) {
    global $DB, $CFG, $USER, $SESSION;
    require_once($CFG->dirroot.'/local/iomad/lib/iomad.php');    
 
    if (!isset($cohort->name)) {
        throw new coding_exception('Missing cohort name in cohort_add_cohort().');
    }
    if (!isset($cohort->idnumber)) {
        $cohort->idnumber = NULL;
    }
    if (!isset($cohort->description)) {
        $cohort->description = '';
    }
    if (!isset($cohort->descriptionformat)) {
        $cohort->descriptionformat = FORMAT_HTML;
    }
    if (!isset($cohort->visible)) {
        $cohort->visible = 1;
    }
    if (empty($cohort->component)) {
        $cohort->component = '';
    }
    if (empty($CFG->allowcohortthemes) && isset($cohort->theme)) {
        unset($cohort->theme);
    }
    if (empty($cohort->theme) || empty($CFG->allowcohortthemes)) {
        $cohort->theme = '';
    }
    if (!isset($cohort->timecreated)) {
        $cohort->timecreated = time();
    }
    if (!isset($cohort->timemodified)) {
        $cohort->timemodified = $cohort->timecreated;
    }

    $cohort->id = $DB->insert_record('cohort', $cohort);

    $event = \core\event\cohort_created::create(array(
        'context' => context::instance_by_id($cohort->contextid),
        'objectid' => $cohort->id,
    ));
    $event->add_record_snapshot('cohort', $cohort);
    $event->trigger();

    $companyid = getcompanyid();

    if($cohort->id){
        $company_cohort             = new stdClass();
        $company_cohort->cohortid   = $cohort->id;
        $company_cohort->companyid  = $companyid;
        $company_cohort->userid     = $USER->id;
        $DB->insert_record('company_cohorts', $company_cohort);
    }
    return $cohort->id;
}

/**
 * Update existing cohort.
 * @param  stdClass $cohort
 * @return void
 */
function mt_dashboard_cohort_update_cohort($cohort) {
    global $DB, $CFG;
    if (property_exists($cohort, 'component') and empty($cohort->component)) {
        // prevent NULLs
        $cohort->component = '';
    }
    // Only unset the cohort theme if allowcohortthemes is enabled to prevent the value from being overwritten.
    if (empty($CFG->allowcohortthemes) && isset($cohort->theme)) {
        unset($cohort->theme);
    }
    $cohort->timemodified = time();
    $DB->update_record('cohort', $cohort);

    $event = \core\event\cohort_updated::create(array(
        'context' => context::instance_by_id($cohort->contextid),
        'objectid' => $cohort->id,
    ));
    $event->trigger();
}

/**
 * Add cohort member
 * @param  int $cohortid
 * @param  int $userid
 * @return void
 */
function mt_dashboard_cohort_add_member($cohortid, $userid) {
    global $DB;
    if ($DB->record_exists('cohort_members', array('cohortid'=>$cohortid, 'userid'=>$userid))) {
        // No duplicates!
        return;
    }
    $record = new stdClass();
    $record->cohortid  = $cohortid;
    $record->userid    = $userid;
    $record->timeadded = time();
    $DB->insert_record('cohort_members', $record);

    $cohort = $DB->get_record('cohort', array('id' => $cohortid), '*', MUST_EXIST);

    $event = \core\event\cohort_member_added::create(array(
        'context' => context::instance_by_id($cohort->contextid),
        'objectid' => $cohortid,
        'relateduserid' => $userid,
    ));
    $event->add_record_snapshot('cohort', $cohort);
    $event->trigger();
}

/**
 * Remove cohort member
 * @param  int $cohortid
 * @param  int $userid
 * @return void
 */
function mt_dashboard_cohort_remove_member($cohortid, $userid) {
    global $DB;
    $DB->delete_records('cohort_members', array('cohortid'=>$cohortid, 'userid'=>$userid));

    $cohort = $DB->get_record('cohort', array('id' => $cohortid), '*', MUST_EXIST);

    $event = \core\event\cohort_member_removed::create(array(
        'context' => context::instance_by_id($cohort->contextid),
        'objectid' => $cohortid,
        'relateduserid' => $userid,
    ));
    $event->add_record_snapshot('cohort', $cohort);
    $event->trigger();
}

/**
 * Delete cohort.
 * @param  stdClass $cohort
 * @return void
 */
function mt_dashboard_cohort_delete_cohort($cohort) {
    global $DB;

    if ($cohort->component) {
        // TODO: add component delete callback
    }

    $DB->delete_records('cohort_members', array('cohortid'=>$cohort->id));
    $DB->delete_records('cohort', array('id'=>$cohort->id));

    // Notify the competency subsystem.
    \core_competency\api::hook_cohort_deleted($cohort);

    $event = \core\event\cohort_deleted::create(array(
        'context' => context::instance_by_id($cohort->contextid),
        'objectid' => $cohort->id,
    ));
    $event->add_record_snapshot('cohort', $cohort);
    $event->trigger();
}

/**
 * Produces a part of SQL query to filter cohorts by the search string
 *
 * Called from {@link cohort_get_cohorts()}, {@link cohort_get_all_cohorts()} and {@link cohort_get_available_cohorts()}
 *
 * @access private
 *
 * @param string $search search string
 * @param string $tablealias alias of cohort table in the SQL query (highly recommended if other tables are used in query)
 * @return array of two elements - SQL condition and array of named parameters
 */
function mt_dashboard_cohort_get_search_query($search, $tablealias = '') {
    global $DB;
    $params = array();
    if (empty($search)) {
        // This function should not be called if there is no search string, just in case return dummy query.
        return array('1=1', $params);
    }
    if ($tablealias && substr($tablealias, -1) !== '.') {
        $tablealias .= '.';
    }
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';
    $conditions = array();
    $fields = array('name', 'idnumber', 'description');
    $cnt = 0;
    foreach ($fields as $field) {
        $conditions[] = $DB->sql_like($tablealias . $field, ':csearch' . $cnt, false);
        $params['csearch' . $cnt] = $searchparam;
        $cnt++;
    }
    $sql = '(' . implode(' OR ', $conditions) . ')';
    return array($sql, $params);
}

/**
 * Returns a list of valid themes which can be displayed in a selector.
 *
 * @return array as (string)themename => (string)get_string_theme
*/
function mt_dashboard_cohort_get_list_of_themes() {
    $themes = array();
    $allthemes = get_list_of_themes();
    foreach ($allthemes as $key => $theme) {
        if (empty($theme->hidefromselector)) {
            $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
        }
    }
    return $themes;
}

/**
 * Returns a short name of company
 *
 * @return string
*/
function mt_dashboard_get_company_shortname($companyid) {
   global $DB;
   $companyid = getcompanyid();
   $record = $DB->get_record('company', array('id' => $companyid), '*', MUST_EXIST);
   
   return $record->shortname; 
}

/**
 * Returns a company_category
 *
 * @return int
*/
function mt_dashboard_get_company_category($companyid) {
   global $DB;
   $companyid = getcompanyid();
   $record = $DB->get_record('company', array('id' => $companyid), '*', MUST_EXIST);
   
   return $record->category; 
}

