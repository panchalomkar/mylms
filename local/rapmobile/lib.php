<?php

/**
 * Hook called after every webservice response
 */
function local_rapmobile_after_execute_webservice($function, $params, $response) {
    global $CFG;

    if ($function === 'core_enrol_get_users_courses') {
        $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

        if (is_array($response)) {
            foreach ($response as &$course) {
                if (!empty($course['courseimage'])) {
                    $course['courseimage'] = str_replace(
                        $CFG->wwwroot . '/pluginfile.php',
                        $CFG->wwwroot . '/webservice/pluginfile.php',
                        $course['courseimage']
                    ) . '?token=' . $wstoken;
                }
            }
        }
    }

    return $response;
}
function local_rapmobile_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_USER) {
        return false;
    }

    if ($filearea !== 'submission') {
        return false;
    }

    require_login();

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_rapmobile', 'submission', $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Only the owner or an admin/manager should be able to view it.
    if ($context->instanceid != $USER->id && !is_siteadmin()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}