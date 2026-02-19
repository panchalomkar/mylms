<?php
require_once($CFG->dirroot .'/local/lms_reports/reportlib.php'); 

global $PAGE,$USER,$CFG;

//Librerias para traer los datos de los reportes para el dashboard, en la variable $charts quedan alojados los datos necesarios para hacer las graficas
require_once(dirname(__FILE__).'/locallib.php');

require_login();
$data = new report_overviewstats();
$cc = $data->get_total_cc();
$top_viewed         = $data->get_top_viewed();
$top_enrolled       = $data->get_top_enrolled();
$dataperday=array();
$top_viewc = array();

foreach($top_viewed as $p){
	$top_viewc[$p->course] = $p->views;
}

foreach($perday['perday'] as $p){
	$dataperday[]='{
			period: \''.$p['date'].'\',
			dl: '.$p['loggedin'].' 
			}';
}
?>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

var confirm_delete ='<?php echo get_string('confirm_delete','local_lms_reports') ;?>';

//function ready_local() {

	
</script>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	
<?php 

?>
	var dataperview = google.visualization.arrayToDataTable([
		["Course", "Viewed", { role: "style" } ],
		["<?php echo $top_viewed[0]->course;?>", <?php echo $top_viewed[0]->views;?>, "#edb879"],
		["<?php echo $top_viewed[1]->course;?>", <?php echo $top_viewed[1]->views;?>, "#1979a9"],
		["<?php echo $top_viewed[2]->course;?>", <?php echo $top_viewed[2]->views;?>, "#69bdd2"],
		["<?php echo $top_viewed[3]->course;?>", <?php echo $top_viewed[3]->views;?>, "#042f66"],
		["<?php echo $top_viewed[4]->course;?>", <?php echo $top_viewed[4]->views;?>, "#cce7e8"] 
	]);
	var perview = new google.visualization.DataView(dataperview);
	perview.setColumns([0, 1,
				   { calc: "stringify",
					 sourceColumn: 1,
					 type: "string",
					 role: "annotation" },
				   2]);

	var options = { 
		width: '%100',
		height: 215,
		bar: {groupWidth: "95%"},
		legend: { position: "none" },
	};
	var chartperview = new google.visualization.ColumnChart(document.getElementById("top_viewed"));
	chartperview.draw(perview, options);

<?php 
		
?>

 
	var dataperenroll = google.visualization.arrayToDataTable([
		["Course", "Enrolled", { role: "style" } ],
		["<?php echo $top_enrolled[0]->course;?>", <?php echo $top_enrolled[0]->enrolled;?>, "#edb879"],
		["<?php echo $top_enrolled[1]->course;?>", <?php echo $top_enrolled[1]->enrolled;?>, "#1979a9"],
		["<?php echo $top_enrolled[2]->course;?>", <?php echo $top_enrolled[2]->enrolled;?>, "#69bdd2"],
		["<?php echo $top_enrolled[3]->course;?>", <?php echo $top_enrolled[3]->enrolled;?>, "#042f66"],
		["<?php echo $top_enrolled[4]->course;?>", <?php echo $top_enrolled[4]->enrolled;?>, "#cce7e8"] 
	]);
	var perenroll = new google.visualization.DataView(dataperenroll);
	perenroll.setColumns([0, 1,
				   { calc: "stringify",
					 sourceColumn: 1,
					 type: "string",
					 role: "annotation" },
				   2]);
	var chartperenroll = new google.visualization.ColumnChart(document.getElementById("top_enrolled"));
	chartperenroll.draw(perenroll, options); 
	$( window ).resize(function() {
		chartperview.draw(perview, options);
		chartperenroll.draw(perenroll, options);
	}); 

<?php 
	
?>

}
</script>
<div class="reports-both-blocks">
	<div class="row">
	<div class="col-md-8">
	<h2 class="title-reports pl-3 pr-3"><?php echo (get_string('report_management_system','local_lms_reports')); ?>
		<a href="<?php echo $documentation ?>" class="help-video-modal float-right pr-3" target="_BLANK">
		</a>
	</h2>
		<p class="">
			<?php echo (get_string('report_management_desc','local_lms_reports')); ?></p>
	</div>
				<div class="col-md-4">
					<a href="<?php echo $CFG->wwwroot; ?>/blocks/configurable_reports/editreport.php?courseid=<?php echo $course->id; ?>&f=pr&iframe=true&wizard=1" class="btn btn-primary new-button" id="demo-bootbox-custom-h-content">
						<i class="fa fa-user pr-1"></i>
						<?php echo (get_string('addreport','local_lms_reports')); ?>
					</a>
				</div>
			</div>
		</div>
		<div class="row mt-3"><div class="col-12 ">
  <div class="row">
	<div class="col-xl-3 col-md-6 mob-mrg">
      <div class="card report-color-elements h-100 redbox">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
				
				<div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#fff;"><?php echo get_string('total_users','local_lms_reports'); ?>
				</div>
	            <div class="h5 mb-0 font-weight-bold" style="color:#fff;"><?php echo $totalusers; ?> </div>
	           </div>
	            <div class="col-auto">
	              <i class="fa fa-users fa-2x text-gray-300"></i>
	            </div>
	          </div>
	        </div>
	      </div>
	    </div>

	    <div class="col-xl-3 col-md-6 mob-mrg">
	      <div class="card report-color-elements h-100 card-box-b" style="color:#fff;">
	        <div class="card-body">
	          <div class="row no-gutters align-items-center">
	            <div class="col mr-2">
					
					<div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#fff;"><?php echo get_string('users_online','local_lms_reports') ; ?>
					</div>
		            <div class="h5 mb-0 font-weight-bold" style="color:#fff;"><?php echo $onlineusers; ?> </div>
		           </div>
		            <div class="col-auto text-online">
		              <i class="fa fa-street-view fa-2x text-online"></i>
		            </div>
		          </div>
		        </div>
	      	</div>
	    </div>
	    <div class="col-xl-3 col-md-6 mob-mrg">
	      <div class="card report-color-elements h-100 card-box-c" style="color:#fff;">
	        <div class="card-body">
	          <div class="row no-gutters align-items-center">
	            <div class="col mr-2">
					
					<div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#fff;"><?php echo get_string('totalcategories','local_lms_reports');?>
					</div>
		            <div class="h5 mb-0 font-weight-bold" style="color:#fff;"><?php echo $cc; ?> </div>
		           </div>
		            <div class="col-auto text-tcourse">
		              <i class="fa fa-cubes fa-2x text-tcourse"></i>
		            </div>
		          </div>
		        </div>
	      	</div>
	    </div>
	    <div class="col-xl-3 col-md-6 mob-mrg">
	      <div class="card report-color-elements card-box-d h-100"style="color:#fff;">
	        <div class="card-body">
	          <div class="row no-gutters align-items-center">
	            <div class="col mr-2">
					
					<div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#fff;"><?php echo get_string('registered_today','local_lms_reports') ; ?>
					</div>
		            <div class="h5 mb-0 font-weight-bold" style="color:#fff;"><?php echo $registeredtoday; ?> </div>
		           </div>
		            <div class="col-auto text-rtoday">
		              <i class="fa fa-user-plus fa-2x text-rtoday"></i>
		            </div>
		          </div>
		        </div>
	      	</div>
	    </div>
	</div>
	</div>
</div>
<div class="row dashbo graph-report">
  <div class="col-xl-4 col-md-6">
		<div id="report_accordion" class="panel-group"> 
			<p class="font-15 title-search"><?php echo (get_string('report_categories','local_lms_reports')); ?></p>
			<div class="col-md-12 searchbox p-0">
				<form action="<?php echo new moodle_url('/local/lms_reports/actions.php');?>" method="post" id="searchform">
					<div class="input-group custom-search-form">
								<span class="input-group-btn">
							<button type="button" id="searchbutton" class="text-muted">
								<i class="edw-icon edw-icon-Search"></i>
							</button>
						</span>
						<input type="text" name="txt" id="txt" placeholder="<?php echo get_string('search_report','local_lms_reports'); ?>" class="form-control  input-lg pl-2">
						<span class="input-group-btn">
						</span>
					</div>
				</form> 
			</div> 
			<?php echo $data->get_accordion_html($menu); ?> 
		</div>
	</div>
		<div class="col-xl-4 col-md-6" >
				<div class="panel panel-dark card-box">
					<div class="panel-body text-center">
						<p class="text-uppercase mb-3 text-sm pt0"><?php echo get_string('top_viewed','local_lms_reports') ; ?></p>
						<div id="top_viewed"></div>
					</div>
				</div>
			</div>
			<div class="col-xl-4 col-md-6" >
				<div class="panel panel-dark card-box">
					<div class="panel-body text-center">
						<p class="text-uppercase mb-3 text-sm"><?php echo get_string('top_enrolled','local_lms_reports') ; ?></p>
						<div id="top_enrolled" ></div>
					</div>
				</div>
			</div>
		</div>	


<div class="row">
	<div class="col-sm-3 report-left-block-acordeon">
		<div class="bord-all">
			

		</div>
	</div>
		
	<div class="col-sm-9 report-right-block-acordeon pl-0">
					
		<?php 
			$sitecontext 	= context_system::instance();
			$lc_show_board	= ( has_capability('local/lms_reports:show_report_dashboard_graph', $sitecontext) === true ) ? '' : 'display:none;';
		?>
					
		<div class="multi-blocks-reports" style="<?php echo $lc_show_board; ?>"> 
			
						
			<div class="row graph-report hidden">
				<?php
				$dashboardreports = json_decode(get_config('block_configurable_reports','repots_for_dashboard'));
				$enabletopcoursesviewedreport  = theme_rap_get_setting('enabletopcoursesviewedreport');
    			$enabletopcoursesenrolledreport  = theme_rap_get_setting('enabletopcoursesenrolledreport');			

				?>
			</div>
			
			<div class="row blocks-top graph-report">
				 
		</div>
	</div>
    </div>
 </div>
