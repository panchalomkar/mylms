<?php
require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $COURSE, $USER, $CFG, $DB, $OUTPUT, $PAGE;

require_login();

// profile_load_data($USER);
$context = get_context_instance(CONTEXT_SYSTEM);

$courses = enrol_get_my_courses();

$PAGE->set_url('/blocks/customnavigation/my_courses.php');
$PAGE->set_context($context);

// base, standard, course, mydashboard
$PAGE->set_pagelayout('base');
$PAGE->set_title('My Courses');
$PAGE->set_heading(' ');
$PAGE->requires->css ( new moodle_url ( '/blocks/customnavigation/my_courses.css' ) );
$PAGE->navbar->add(get_string('mycourses'));

echo $OUTPUT->header();
?>
<div class="custom_page">
	<div>
		<h2><?php echo get_string('mycourses'); ?></h2>
	</div>
	
	<div class="custom_page_content">
   	<!--
    <div style="padding:0px 64px">
   	   <h2 class="my_courses_title"><?php echo get_string('welcome', 'block_customnavigation'); ?></h2>
         <?php echo get_string('my_courses_description', 'block_customnavigation'); ?>
   	</div>
   	-->
   	<div class="course_list">
   	   <?php $counter = 1;?>
   	   
   	   <?php foreach($courses as $course) : ?>
  	      <?php $open_course_image = (0 == ($counter % 2)) ? 2 : 1 ?>
   	      <?php //echo '<pre>'; print_r($course);?>
   	      
      	   <div class="course_item">
      	      <div class="course_number"><?php echo $counter ?></div>
      	      <div class="course_detail">
      	         <div class="course_image">
					<?php 

		require_once($CFG->libdir. '/coursecatlib.php');
		$course = get_course($course->id);
		$course = new course_in_list($course);					  
		
               if(count($course->get_course_overviewfiles()) == 0){
                  echo html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/theme/rlmslms/pix/test1.jpg'));
                }

							 foreach ($course->get_course_overviewfiles() as $file) {
								$isimage = $file->is_valid_image();
								$url = file_encode_url("$CFG->wwwroot/pluginfile.php",
										'/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
										$file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
								if ($isimage) {
									echo html_writer::tag('div',
											html_writer::empty_tag('img', array('src' => $url)),
											array('class' => 'courseimage'));
								} else {
									$image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
									$filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
											html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
									echo  html_writer::tag('span',
											html_writer::link($url, $filename),
											array('class' => 'coursefile fp-filename-icon'));
								}
							}
							//=========================================================
				  ?>				 
				 </div>
    	         <a href="<?php echo $CFG->wwwroot?>/course/view.php?id=<?php echo $course->id ?>">
                     <div class="content">
                         <div class="course_action<?php echo $open_course_image?>">
                            <div class="course_action_icon"></div>
                            <!--<div class="course_action_text">Open<br>Course</div>-->
                         </div>
      	                 <div class="course_description"><?php echo $course->summary; ?></div>
     	             </div>
     	         </a>
      	      </div>
              <div class="course_name">
                <a href="<?php echo $CFG->wwwroot?>/course/view.php?id=<?php echo $course->id ?>">
                  <?php echo $course->shortname?>
                </a>
              </div>
      	   </div>
      	   
      	   <?php $counter++; ?>
         <?php endforeach; ?>
         
   	</div>
	</div>
</div>

<script type="text/javascript">
<!--
jQuery(function() {
	var $pageContent = $("#page-content");
	var $regionMain = $("#region-main");
	
	$pageContent.addClass("page-content-custom");
	
   customnavigation_set_active(1);
});

//-->
        $('.course_item')
        .mouseenter(function(event){
            $(this).children('.course_detail').children('a').children('.content').animate({'opacity' : 0.9}, 800)
        })
        .mouseleave(function(event){
            $(this).children('.course_detail').children('a').children('.content').animate({'opacity' : 0}, 300)
        })

</script>
<?php

echo $OUTPUT->footer();
