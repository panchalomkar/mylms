<?php

define('AJAX_SCRIPT', true);

require('../../config.php');
require('locallib.php');

$action = required_param('action', PARAM_RAW);
$context = context_system::instance();

// require_capability('local/content_structure:view', $context);

$localObj = new local_content_structure();
switch ($action) {
    // Display the question attempt with response
    case 'displaymoduleform':
        $id = required_param('id', PARAM_INT);
        $archtype = required_param('archtype', PARAM_TEXT);

        echo $localObj->display_module_form($id, $archtype);
        break;
    case 'addmodule':

        echo create_course_content((object) $_POST, $_FILES);
        break;
    case 'getlessons';
        $id = required_param('id', PARAM_INT);
        echo $localObj->get_lesson_options($id);
        break;
    case 'getlearning';
        $id = required_param('id', PARAM_INT);
        echo $localObj->get_learning_list($id);
        break;
        break;
    case 'deletecontent':
        $id = required_param('id', PARAM_INT);
        $type = required_param('type', PARAM_TEXT);
        delete_content($id, $type);
        break;
    case 'getmodinfo':
        $id = required_param('id', PARAM_INT);
        $archtype = required_param('archtype', PARAM_TEXT);
        $return = $localObj->get_mod_info($id, $archtype);

        break;
    case 'addtoplaylist':
        $itemid = required_param('id', PARAM_INT);
        $type = required_param('type', PARAM_TEXT);
        global $USER, $DB;

        // Avoid duplicates
        $exists = $DB->record_exists('local_content_playlist', [
            'userid' => $USER->id,
            'itemid' => $itemid,
            'type' => $type,
        ]);

        if (!$exists) {
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->itemid = $itemid;
            $record->type = $type;
            $record->timecreated = time();

            $DB->insert_record('local_content_playlist', $record);
            echo 'success';
        } else {
            echo 'already_added';
        }
        break;

    case 'removeitem':
        $id = required_param('id', PARAM_INT);
        $type = required_param('type', PARAM_ALPHA);

        global $DB, $USER;

        $DB->delete_records('local_content_playlist', [
            'userid' => $USER->id,
            'itemid' => $id,
            'type' => $type
        ]);

        echo json_encode(['success' => true]);
        break;
    case 'checkplaylist':
        $itemid = required_param('id', PARAM_INT);
        $type = required_param('type', PARAM_TEXT);

        global $USER, $DB;

        $exists = $DB->record_exists('local_content_playlist', [
            'userid' => $USER->id,
            'itemid' => $itemid,
            'type' => $type,
        ]);

        echo $exists ? 'exists' : 'not_exists';
        break;
    case 'getprogram':
        $id = required_param('id', PARAM_INT);

        $record = $DB->get_record('local_content_structure', ['id' => $id, 'parent' => 0], '*', MUST_EXIST);

        echo json_encode([
            'id' => $record->id,
            'name' => $record->name,
            'image' => $record->image,
        ]);
        break;
    default:
        throw new moodle_exception('unknowajaxaction');
}

die();
?>