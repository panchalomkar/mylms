<?php
defined('MOODLE_INTERNAL') || die();

class block_edwiser_dashboard extends block_base {

    public function init(): void {
        $this->title = 'Dashboard Overview';
    }

    public function applicable_formats(): array {
        return [
            'my'   => true,
            'site' => true
        ];
    }

    public function get_content(): stdClass {
        global $CFG, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        require_once($CFG->dirroot . '/blocks/edwiser_dashboard/classes/helper.php');

        $data = \block_edwiser_dashboard\helper::get_dashboard_data();

        // Encode arrays safely for JS
        $data['siteoverview']['trendActiveUsers'] =
            json_encode($data['siteoverview']['trendActiveUsers'], JSON_NUMERIC_CHECK);
        $data['siteoverview']['trendEnrollments'] =
            json_encode($data['siteoverview']['trendEnrollments'], JSON_NUMERIC_CHECK);
        $data['siteoverview']['trendCompletions'] =
            json_encode($data['siteoverview']['trendCompletions'], JSON_NUMERIC_CHECK);

        /* ================= ASSETS ================= */
        $this->content->text .= <<<HTML
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
HTML;

        /* ================= TEMPLATE ================= */
        $this->content->text .= $OUTPUT->render_from_template(
            'block_edwiser_dashboard/dashboard',
            $data
        );

        /* ================= EXTERNAL JS ================= */
        $PAGE->requires->js('/blocks/edwiser_dashboard/js/dashboard.js');

        return $this->content;
    }
}
