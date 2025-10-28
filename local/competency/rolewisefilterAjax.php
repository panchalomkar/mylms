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

/**
 * competency local caps.
 *
 * @package    local_competency
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;
$context = context_system::instance();
$PAGE->set_context($context);
require_login();
$get_competencyheading = $DB->get_records('competency_title', array('isdeleted' => 0));
$i=0;
$role = required_param('role', PARAM_INT);

?>

<div class="accordion md-accordion accordion-local" id="accordionEx78" role="tablist" aria-multiselectable="false">
  <div class="card">
    <?php
      $show = 'show'; 
      foreach ($get_competencyheading as $key => $competencyheading) {
        if($i > 0){
          $show = '';
        }
        $sqlcompetency_category = "SELECT cp.id, cp.comptencyname, cc.name, r.shortname 
          FROM {competency_category} as cc 
          LEFT JOIN {competencies} as cp ON cc.id = cp.ccid 
          LEFT JOIN {role} as r ON cc.roleid = r.id 
          WHERE cc.isdeleted=0 and cc.ctid=? ";
         
        if($role!=0)
			$sqlcompetency_category .= "and cc.roleid = $role ORDER by cc.id";

        $competency_category = $DB->get_records_sql($sqlcompetency_category, array($competencyheading->id));
    ?>
    <!-- Card header -->
    <div class="card-header" role="tab" id="heading<?php echo $i; ?>">
      <!-- Heading -->
      <a data-toggle="collapse" data-parent="#accordionEx78" href="#collapse<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse<?php echo $i; ?>">
        <h5 class="mt-1 mb-0">
          <span><?php echo $competencyheading->title; ?></span>
        </h5>
      </a>
    </div>
    <!-- Card body -->
    <div id="collapse<?php echo $i; ?>" class="collapse <?php echo $show; ?>" role="tabpanel" aria-labelledby="heading<?php echo $i; ?>" data-parent="#accordionEx78">
      <div class="card-body">
        <!-- Table responsive wrapper -->
        <div class="table-responsive mx-3">
          <!--Table-->
          <table class="table table-hover mb-0">
            <!--Table head-->
            <thead>
              <tr>
                <th class="th-lg">Competency Name</th>
                <th class="th-lg">Sub-Competency Name </a></th>
                <th class="th-lg">Role</th>
                <th class="th-lg">View course</th>
              </tr>
            </thead>
            <!--Table head-->
            <!--Table body-->
            <tbody>
              <?php foreach($competency_category as $competency_categorys){
                  if(empty($competency_categorys->id)){
                      $id = 0;
                      $comptencyname = '-';
                  }
                  else{ 
                      $id = $competency_categorys->id;
                      $comptencyname = $competency_categorys->comptencyname;
                  }
              ?>
              <tr>
                <td><?php echo $competency_categorys->name; ?></td>
                <td><?php echo $comptencyname; ?></td>
                <td><?php echo $competency_categorys->shortname; ?></td>
                <td>
                  <a href="#" class="btn btn-primary" data-target='#AddModal' data-toggle='modal' onclick="getcourses(<?php echo $id; ?>)" > View Courses </a>
                </td>
              </tr>
              <?php } ?>
            </tbody>
            <!--Table body-->
          </table>
          <!--Table-->
        </div>
        <!-- Table responsive wrapper -->
      </div>
    </div>
    <?php 
        $i++;
  }

   ?>
  </div>