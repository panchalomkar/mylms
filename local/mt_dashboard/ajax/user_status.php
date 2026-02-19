<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

// ✅ Correct context (NO namespace)
$context = context_system::instance();

// Capability checks
require_capability('moodle/user:update', $context);

// Read JSON body
$data = json_decode(file_get_contents('php://input'));

if (empty($data->sesskey) || !confirm_sesskey($data->sesskey)) {
    throw new moodle_exception('invalidsesskey');
}

$userid = clean_param($data->userid ?? 0, PARAM_INT);
$action = clean_param($data->action ?? '', PARAM_ALPHA);

if (!$userid || !$action) {
    throw new moodle_exception('missingparam');
}

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

switch ($action) {

    case 'suspend':
        $user->suspended = 1;
        $DB->update_record('user', $user);
        break;

    case 'activate':
        $user->suspended = 0;
        $DB->update_record('user', $user);
        break;

    case 'delete':
        require_capability('moodle/user:delete', $context);
        delete_user($user);
        break;

    default:
        throw new moodle_exception('invalidaction');
}

echo json_encode([
    'success' => true
]);
exit;
