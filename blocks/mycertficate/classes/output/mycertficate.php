<?php
namespace block_mycertficate\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use moodle_url;

class mycertficate implements renderable, templatable {
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function export_for_template(renderer_base $output) {
        global $DB, $USER;

        $certificates = [];

        // --- Fetch Custom Certificates ---
        $customcerts = $DB->get_records('customcert_issues', ['userid' => $USER->id]);
        foreach ($customcerts as $issue) {
            $cert = $DB->get_record('customcert', ['id' => $issue->customcertid]);
            if (!$cert) continue;

            $cm = get_coursemodule_from_instance('customcert', $cert->id, $cert->course, false, MUST_EXIST);
            $url = new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]);

            $certificates[] = [
                'name' => $cert->name,
                'lastaccessed' => $this->time_ago($issue->timecreated),
                'url' => $url->out(),
                'type' => 'customcert'
            ];
        }

        // --- Fetch Iomad Certificates (if table exists) ---
        if ($DB->get_manager()->table_exists('iomadcertificate_issues')) {
            $iomadcerts = $DB->get_records('iomadcertificate_issues', ['userid' => $USER->id]);
            foreach ($iomadcerts as $issue) {
                $cert = $DB->get_record('iomadcertificate', ['id' => $issue->iomadcertificateid]);
                if (!$cert) continue;

                $cm = get_coursemodule_from_instance('iomadcertificate', $cert->id, $cert->course, false, MUST_EXIST);
                $url = new moodle_url('/mod/iomadcertificate/view.php', ['id' => $cm->id]);

                $certificates[] = [
                    'name' => $cert->name,
                    'lastaccessed' => $this->time_ago($issue->timecreated),
                    'url' => $url->out(),
                    'type' => 'iomad'
                ];
            }
        }

        // --- Limit to only 3 certificates ---
        $limitedcerts = array_slice($certificates, 0, 3);

        // --- Define View More URL ---
        $viewmoreurl = new moodle_url('/blocks/mycertficate/viewall.php');

        return [
            'certificates' => $limitedcerts,
            'hascertificates' => !empty($limitedcerts),
            'viewmoreurl' => $viewmoreurl->out()
        ];
    }

    private function time_ago($timestamp) {
        $diff = time() - $timestamp;
        $days = floor($diff / 86400);
        if ($days == 0) return "Today";
        if ($days == 1) return "1 day ago";
        return "$days days ago";
    }
}
