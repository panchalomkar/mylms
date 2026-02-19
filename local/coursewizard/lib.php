<?php
function get_recent_courses_images():string{
    global $CFG, $DB, $OUTPUT, $PAGE;
    require_once($CFG->dirroot . '/local/coursewizard/classes/recent_files.php');
   if ($repo = $DB->get_record('repository', ['type' => 'recent'])) {
        $files = new repository_recent_cw($repo->type);
        $recent_files = $files->print_login();
      if(count($recent_files['list']) > 0){
            $templatedata['has_data'] = true;
            foreach ($recent_files['list'] as $data) {  //print_r($data); 
                if(array_key_exists('realicon', $data)){
                    $url = preg_replace('/\?.*/', '', $data['realicon']);
                    $datatest['url'] = $url;
                    $datatest['contextid'] = $data['finfo']['contextid'];
                    $datatest['itemid'] = $data['finfo']['itemid'];
                    $datatest['filearea'] = $data['finfo']['filearea'];
                    $datatest['component'] = $data['finfo']['component'];
                    $datatest['filepath'] = $data['finfo']['filepath'];
                    $datatest['filename'] = $data['finfo']['filename'];
                    $temp[] = $datatest;
                }
            }
        }
    }
    $templatedata['finfo'] = $temp;
    //return $OUTPUT->render_from_template('local_coursewizard/recentcourseimages', $templatedata);
    $renderable = new \local_coursewizard\output\main($templatedata);
    $renderer = $PAGE->get_renderer('local_coursewizard');
    return $renderer->render_get_recent_courses_images($renderable);
}