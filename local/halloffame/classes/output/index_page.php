<?php
namespace local_halloffame\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use local_halloffame\manager;
use local_halloffame\iomad_helper;
use local_halloffame\department_helper;

/**
 * Renderable for the main Hall of Fame index page.
 * v2: injects IOMAD companyid context and dynamic department filter list.
 */
class index_page implements renderable, templatable {

    private string $tab;
    private array  $filters;

    public function __construct(string $tab = 'awards', array $filters = []) {
        $this->tab     = $tab;
        $this->filters = $filters;
    }

    public function export_for_template(renderer_base $output): array {
        $context    = \context_system::instance();
        $canmanage  = has_capability('local/halloffame:manageawards', $context);
        $canapprove = has_capability('local/halloffame:approve',      $context);
        $cansubmit  = has_capability('local/halloffame:submit',       $context);
        $pending    = $canapprove ? count(manager::get_submissions('pending')) : 0;

        // ── IOMAD context ────────────────────────────────────────────────────
        $companyid   = iomad_helper::get_current_companyid();
        $companyname = iomad_helper::get_company_name($companyid);
        $issiteadmin = is_siteadmin();

        // ── Filter data — departments now dynamic from profile fields ─────────
        $months  = [];
        foreach (manager::months_list() as $num => $name) {
            $months[] = ['value' => $num, 'label' => $name];
        }
        $years = [];
        foreach (range((int) date('Y'), (int) date('Y') - 5) as $y) {
            $years[] = ['value' => $y, 'label' => $y];
        }

        // Dynamic departments: reads user profile field, scoped to company.
        $depts = department_helper::get_departments_for_filter($companyid);

        $cats  = array_map(fn($c) => ['value' => $c->name, 'label' => $c->name],
                           manager::get_categories());
        $quarters = [
            ['value' => 1, 'label' => 'Q1 (Jan–Mar)'],
            ['value' => 2, 'label' => 'Q2 (Apr–Jun)'],
            ['value' => 3, 'label' => 'Q3 (Jul–Sep)'],
            ['value' => 4, 'label' => 'Q4 (Oct–Dec)'],
        ];

        $data = [
            'tab_awards'       => $this->tab === 'awards',
            'tab_achievements' => $this->tab === 'achievements',
            'canmanage'        => $canmanage,
            'canapprove'       => $canapprove,
            'cansubmit'        => $cansubmit,
            'pendingcount'     => $pending,
            'haspending'       => $pending > 0,
            // IOMAD context badge.
            'companyname'      => $companyname,
            'hascompany'       => $companyname !== '',
            'issiteadmin'      => $issiteadmin,
            // URLs.
            'adminurl'         => (new \moodle_url('/local/halloffame/pages/admin.php'))->out(false),
            'reviewurl'        => (new \moodle_url('/local/halloffame/pages/review.php'))->out(false),
            'submiturl'        => (new \moodle_url('/local/halloffame/pages/submit.php'))->out(false),
            'mysuburl'         => (new \moodle_url('/local/halloffame/pages/my_submissions.php'))->out(false),
            'caturl'           => (new \moodle_url('/local/halloffame/pages/manage_categories.php'))->out(false),
            'depturl'          => (new \moodle_url('/local/halloffame/pages/manage_departments.php'))->out(false),
            'awardsurl'        => (new \moodle_url('/local/halloffame/pages/index.php', ['tab' => 'awards']))->out(false),
            'achievementsurl'  => (new \moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']))->out(false),
            'filters'          => [
                'months'      => $months,
                'years'       => $years,
                'categories'  => $cats,
                'departments' => $depts,
                'quarters'    => $quarters,
            ],
        ];

        // ── Content records ───────────────────────────────────────────────────
        if ($this->tab === 'awards') {
            $awards = [];
            foreach (manager::get_awards($this->filters) as $a) {
                $user = \core_user::get_user($a->userid);
                // FIX: Use $output->user_picture() with proper size for circular avatar.
                $awards[] = [
                    'id'          => $a->id,
                    'fullname'    => $a->fullname,
                    // PROFILE IMAGE FIX: pass full user object to user_picture().
                    'userpicture' => $output->user_picture($user, [
                        'size'   => 80,
                        'class'  => 'hof-avatar',
                        'link'   => false,
                        'alttext'=> false,
                    ]),
                    'title'       => $a->title,
                    'department'  => $a->department ?? '',
                    'monthname'   => $a->monthname  ?? '',
                    'year'        => $a->year,
                    'message'     => $a->message    ?? '',
                    'image'       => $a->image       ?? '',
                    'likecount'   => $a->likecount,
                    'userliked'   => (bool) $a->userliked,
                    'gradient'    => $a->gradient,
                    'canmanage'   => $canmanage,
                    'deleteurl'   => (new \moodle_url('/local/halloffame/pages/delete_award.php', [
                        'id' => $a->id, 'sesskey' => sesskey(),
                    ]))->out(false),
                ];
            }
            $data['awards']    = $awards;
            $data['hasawards'] = !empty($awards);
        } else {
            $achs = [];
            foreach (manager::get_achievements($this->filters) as $a) {
                $user = \core_user::get_user($a->userid);
                $achs[] = [
                    'id'                 => $a->id,
                    'fullname'           => $a->fullname,
                    // PROFILE IMAGE FIX: consistent $output->user_picture().
                    'userpicture'        => $output->user_picture($user, [
                        'size'   => 52,
                        'class'  => 'hof-ach-avatar',
                        'link'   => false,
                        'alttext'=> false,
                    ]),
                    'title'              => $a->title,
                    'issuer'             => $a->issuer             ?? '',
                    'issuedateformatted' => $a->issuedateformatted ?? '',
                    'type'               => $a->type               ?? '',
                    'department'         => $a->department         ?? '',
                    'fileurl'            => $a->fileurl            ?? '',
                    'fileisimage'        => $a->fileisimage        ?? false,
                    'notes'              => $a->notes              ?? '',
                    'likecount'          => $a->likecount,
                    'userliked'          => (bool) $a->userliked,
                ];
            }
            $data['achievements']    = $achs;
            $data['hasachievements'] = !empty($achs);
        }

        return $data;
    }
}
