<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
redirect(new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']));
