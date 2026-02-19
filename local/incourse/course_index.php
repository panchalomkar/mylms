<?php
defined('MOODLE_INTERNAL') || die();

function local_incourse_render_course_index($course) {
    global $OUTPUT;

    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();

    $html = html_writer::start_div('local-incourse-leftpanel');
    $html .= html_writer::tag('h4', get_string('courseindex', 'local_incourse'));
    $html .= html_writer::start_tag('ul', ['class' => 'list-unstyled local-incourse-sections']);

    foreach ($sections as $section) {
        $sectionname = trim(format_string($section->name ?: get_section_name($course, $section->section)));
        $html .= html_writer::start_tag('li', ['class' => 'mb-2 local-incourse-section']);
        $html .= html_writer::tag('div', $sectionname, ['class' => 'fw-bold small mb-1']);

        if (!empty($section->sequence)) {
            $html .= html_writer::start_tag('ul', ['class' => 'list-unstyled ms-2']);
            foreach (explode(',', $section->sequence) as $cmid) {
                $cmid = (int)$cmid;
                if (empty($cmid) || !isset($modinfo->cms[$cmid])) continue;
                $cm = $modinfo->cms[$cmid];
                $modurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
                $icon = $OUTPUT->pix_icon('icon', '', $cm->modname, ['class' => 'icon']);
                $linktext = format_string($cm->get_formatted_name());
                $html .= html_writer::tag('li',
                    html_writer::link($modurl, $icon . ' ' . $linktext, ['class' => 'd-block text-truncate activity-link']),
                    ['class' => 'mb-1 small']);
            }
            $html .= html_writer::end_tag('ul');
        } else {
            $html .= html_writer::tag('div', get_string('nosections', 'local_incourse'), ['class' => 'text-muted small']);
        }

        $html .= html_writer::end_tag('li');
    }

    $html .= html_writer::end_tag('ul');
    $html .= html_writer::end_div();

    return $html;
}
