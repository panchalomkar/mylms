<?php
namespace local_skillgap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralised validation for all plugin operations.
 */
class data_validator {

    /**
     * Validate that a competency has exactly 5 questions before assessment inclusion.
     * Returns [valid => bool, message => string].
     */
    public static function competency_has_questions(int $compid): array {
        global $DB;
        $count = $DB->count_records('local_skillgap_question', ['compid' => $compid]);
        if ($count < 5) {
            $name = $DB->get_field('local_skillgap_comp', 'name', ['id' => $compid]);
            return [
                'valid'   => false,
                'message' => get_string('error_no_questions', 'local_skillgap', $name),
            ];
        }
        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate a CSV file upload — checks MIME type, size, column headers.
     * $expected_headers: array of lowercase column names that must be present.
     */
    public static function validate_csv_upload(array $file, array $expected_headers = []): array {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => get_string('error_csv_required', 'local_skillgap')];
        }

        $allowed_types = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        $finfo         = new \finfo(FILEINFO_MIME_TYPE);
        $mime          = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed_types)) {
            // Accept anyway if extension is .csv — some servers report wrong MIME.
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                return ['valid' => false, 'message' => get_string('error_csv_invalid', 'local_skillgap')];
            }
        }

        if (!empty($expected_headers)) {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $header_row = fgetcsv($handle);
                fclose($handle);
                if ($header_row) {
                    $normalised = array_map('strtolower', array_map('trim', $header_row));
                    foreach ($expected_headers as $h) {
                        if (!in_array(strtolower($h), $normalised)) {
                            return [
                                'valid'   => false,
                                'message' => get_string('error_csv_invalid', 'local_skillgap') .
                                             ' Missing column: ' . $h,
                            ];
                        }
                    }
                }
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate no duplicate competency name exists in the same dept+desig scope.
     */
    public static function no_duplicate_competency(string $name, int $deptid, int $desigid, int $excludeid = 0): bool {
        global $DB;
        $sql    = "SELECT id FROM {local_skillgap_comp}
                    WHERE name = :name AND deptid = :deptid AND desigid = :desigid";
        $params = ['name' => $name, 'deptid' => $deptid, 'desigid' => $desigid];
        if ($excludeid) {
            $sql .= " AND id != :excludeid";
            $params['excludeid'] = $excludeid;
        }
        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Validate no duplicate designation name exists in the hierarchy for a dept.
     * Designation names come from user profiles — uniqueness is enforced by the
     * UNIQUE index on (department, designation_name) in local_skillgap_desig_hierarchy.
     */
    public static function no_duplicate_designation(string $name, string $department, int $excludeid = 0): bool {
        global $DB;
        $sql    = "SELECT id FROM {local_skillgap_desig_hierarchy} WHERE designation_name = :name AND department = :dept";
        $params = ['name' => trim($name), 'dept' => $department];
        if ($excludeid) {
            $sql .= " AND id != :excludeid";
            $params['excludeid'] = $excludeid;
        }
        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Validate an assessment configuration before saving.
     * Returns array of error messages (empty = valid).
     * compids now supports hierarchical format: "main:compid", "sub:compid:subcompid", "subsub:compid:subcompid"
     * as well as legacy plain integer format.
     */
    public static function validate_assessment(array $data): array {
        global $DB;
        $errors = [];

        if (empty($data['deptid'])) {
            $errors[] = get_string('error_missing_dept', 'local_skillgap');
        }
        if (empty($data['desigid'])) {
            $errors[] = get_string('error_missing_desig', 'local_skillgap');
        }
        if (empty($data['compids'])) {
            $errors[] = get_string('error_missing_comps', 'local_skillgap');
        } else {
            foreach (array_filter(explode(',', $data['compids'])) as $token) {
                $token = trim($token);
                if (empty($token)) { continue; }

                // Format: "main.compid" or "sub.compid.subcompid" or legacy plain integer.
                $parts     = explode('.', $token);
                $comp_type = 'main';
                $compid    = 0;
                $subcompid = 0;

                if (count($parts) === 1 && is_numeric($parts[0])) {
                    $comp_type = 'main';
                    $compid    = (int)$parts[0];
                } elseif (count($parts) >= 2) {
                    $comp_type = $parts[0];
                    $compid    = (int)$parts[1];
                    $subcompid = isset($parts[2]) ? (int)$parts[2] : 0;
                }

                if ($compid <= 0) { continue; }

                // Count questions at the correct level.
                if ($comp_type === 'main' || $subcompid === 0) {
                    $count = (int)$DB->count_records_select('local_skillgap_question',
                        'compid = :cid AND (subcompid IS NULL OR subcompid = 0)',
                        ['cid' => $compid]);
                    $name  = $DB->get_field('local_skillgap_comp', 'name', ['id' => $compid]);
                    $name  = ($name !== false && $name !== '') ? $name : 'Competency #' . $compid;
                } else {
                    $count = (int)$DB->count_records('local_skillgap_question',
                        ['compid' => $compid, 'subcompid' => $subcompid]);
                    $name  = $DB->get_field('local_skillgap_subcomp', 'name', ['id' => $subcompid]);
                    $name  = ($name !== false && $name !== '') ? $name : 'Sub-Competency #' . $subcompid;
                }

                if ($count < 5) {
                    $errors[] = 'Competency "' . $name . '" does not have 5 questions yet. (Has ' . $count . '/5)';
                }
            }
        }

        return $errors;
    }
}
