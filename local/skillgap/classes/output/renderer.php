<?php
namespace local_skillgap\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Main renderer for local_skillgap.
 * All UI output goes through render_from_template() using Mustache.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the admin dashboard.
     */
    public function render_admin_dashboard(array $data): string {
        return $this->render_from_template('local_skillgap/admin_dashboard', $data);
    }

    /**
     * Render the user competency dashboard.
     */
    public function render_user_dashboard(array $data): string {
        return $this->render_from_template('local_skillgap/user_dashboard', $data);
    }

    /**
     * Render the manager team analytics dashboard.
     */
    public function render_manager_dashboard(array $data): string {
        return $this->render_from_template('local_skillgap/manager_dashboard', $data);
    }

    /**
     * Render the departments management page.
     */
    public function render_departments(array $data): string {
        return $this->render_from_template('local_skillgap/departments', $data);
    }

    /**
     * Render the designations management page.
     */
    public function render_designations(array $data): string {
        return $this->render_from_template('local_skillgap/designations', $data);
    }

    /**
     * Render the competencies management page.
     */
    public function render_competencies(array $data): string {
        return $this->render_from_template('local_skillgap/competencies', $data);
    }

    /**
     * Render the questions management page.
     */
    public function render_questions(array $data): string {
        return $this->render_from_template('local_skillgap/questions', $data);
    }

    /**
     * Render the assessments management page.
     */
    public function render_assessments(array $data): string {
        return $this->render_from_template('local_skillgap/assessments', $data);
    }

    /**
     * Render the assessment-taking page.
     */
    public function render_take_assessment(array $data): string {
        return $this->render_from_template('local_skillgap/take_assessment', $data);
    }

    /**
     * Render the clusters page.
     */
    public function render_clusters(array $data): string {
        return $this->render_from_template('local_skillgap/clusters', $data);
    }

    /**
     * Render the promotion panel.
     */
    public function render_promotion(array $data): string {
        return $this->render_from_template('local_skillgap/promotion', $data);
    }

    /**
     * Render the weak zone analysis page.
     */
    public function render_weakzones(array $data): string {
        return $this->render_from_template('local_skillgap/weakzones', $data);
    }

    /**
     * Render the assessment competency report page.
     */
    public function render_assessment_report(array $data): string {
        return $this->render_from_template('local_skillgap/assessment_report', $data);
    }
}
