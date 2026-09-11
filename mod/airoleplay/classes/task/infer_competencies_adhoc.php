<?php
// This file is part of mod_airoleplay — optional competency inference (no local plugin).

namespace mod_airoleplay\task;

defined('MOODLE_INTERNAL') || die();

class infer_competencies_adhoc extends \core\task\adhoc_task {

    public function execute(): void {
        // Competency plugin integration is optional; no-op when not installed.
        if (class_exists('\local_airoleplay_competency\competency_engine')) {
            $data = $this->get_custom_data();
            if (!empty($data->sessionid)) {
                try {
                    \local_airoleplay_competency\competency_engine::infer_from_session((int) $data->sessionid);
                } catch (\Throwable $e) {
                    mtrace('competency infer skipped: ' . $e->getMessage());
                }
            }
        }
    }
}
