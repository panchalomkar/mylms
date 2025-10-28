<?php
function local_questionreport_before_http_headers() {
    global $PAGE;
    $PAGE->requires->js_call_amd('local_questionreport/chartbuilder', 'init');
}
