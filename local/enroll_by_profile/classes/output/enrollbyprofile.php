<?php
/**
 * Class containing data for enroll_by_profile.
 *
 * @package    local_enroll_by_profile
 * @copyright  2021 Ajinkya D
 * @license    Paradiso
 */

namespace local_enroll_by_profile\output;

defined('MOODLE_INTERNAL') || die();


use renderable;
use renderer_base;
use templatable;

require_once($CFG->dirroot . '/local/enroll_by_profile/lib.php');

/**
 * Class containing data for Automation hub.
 *
 * @copyright  2021 Ajinkya D
 * @license    Paradiso
 */
class enrollbyprofile implements renderable, templatable {

    /**
     * Constructor.
     *
     * @param object $config An object containing the configuration information for the current instance of this block.
     */
    public function __construct() { }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG,$DB,$USER;
		
        $rules = get_rule_renderable(null, null, null);
        $templatedata['rules'] = $rules->rules;
        $templatedata['is_tag'] = $rules->is_tags;
        $templatedata['rules_form'] = RowConditionContent(1);
        $templatedata['notsearch'] = 1;
        $templatedata['is_index'] = 1;
        if($rules->totalpages > 1){
            $templatedata['pagination'] = ['pages'=>$rules->pages,'previous'=>$rules->previous,'next'=>$rules->next];
        }
        
        return $templatedata;
    }
}