<?php

require_once( '../../config.php');

global $DB, $USER, $CFG;
require_login();

$action = required_param('action', PARAM_TEXT);

switch ($action) {
    case 'updateticketstatus':

        $id = optional_param('id', 0, PARAM_INT);
        $status = optional_param('status', 0, PARAM_TEXT);

        $update = "UPDATE {ticketing} SET status = '$status' WHERE id = $id";
        if ($DB->execute($update)) {
            echo $id;
        }

        break;
}
