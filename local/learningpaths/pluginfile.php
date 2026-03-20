<?php
require_once("../../config.php");

// Require learningpaths library.
global $CFG;
require_once("{$CFG->dirroot}/local/learningpaths/lib.php");
require_once("{$CFG->dirroot}/local/learningpaths/classes/objects/LearningPath.php");

$learningpathimage = LearningPath::get_learningpath_image_object(required_param('learningpathid', PARAM_INT));
get_learningpath_image($learningpathimage);
