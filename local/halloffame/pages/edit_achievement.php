<?php
// local/halloffame/pages/edit_achievement.php
// Admin: edit an approved achievement.

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

require_login();

global $DB, $OUTPUT, $PAGE;

$id      = required_param('id', PARAM_INT);
$context = context_system::instance();
require_capability('local/halloffame:approve', $context);

$ach = $DB->get_record('halloffame_achievements', ['id' => $id], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/edit_achievement.php', ['id' => $id]));
$PAGE->set_title(get_string('editachievement', 'local_halloffame'));
$PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php'));
$PAGE->navbar->add(get_string('achievements', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']));
$PAGE->navbar->add(get_string('editachievement', 'local_halloffame'));

if (data_submitted() && confirm_sesskey()) {
    $data = [
        'title'     => required_param('title',  PARAM_TEXT),
        'issuer'    => optional_param('issuer', '', PARAM_TEXT),
        'type'      => optional_param('type',   '', PARAM_TEXT),
        'notes'     => optional_param('notes',  '', PARAM_TEXT),
        'issuedate' => optional_param('issuedate', 0, PARAM_INT),
    ];
    \local_halloffame\manager::update_achievement($id, $data);
    redirect(
        new moodle_url('/local/halloffame/pages/index.php', ['tab' => 'achievements']),
        get_string('achievementupdated', 'local_halloffame'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('editachievement', 'local_halloffame'));

$action = $PAGE->url->out(false);
$sk     = sesskey();
$title  = s($ach->title);
$issuer = s($ach->issuer ?? '');
$type   = s($ach->type   ?? '');
$notes  = s($ach->notes  ?? '');
$issuedate = (int)($ach->issuedate ?? 0);
$datestr = $issuedate ? date('Y-m-d', $issuedate) : '';

echo <<<HTML
<form method="post" action="{$action}" class="hof-form" style="max-width:680px">
  <input type="hidden" name="sesskey" value="{$sk}"/>

  <label class="hof-label">Certificate Title
    <input type="text" name="title" value="{$title}" required class="hof-input"/>
  </label>

  <label class="hof-label">Issuing Organisation
    <input type="text" name="issuer" value="{$issuer}" class="hof-input"/>
  </label>

  <label class="hof-label">Certificate Type
    <input type="text" name="type" value="{$type}" class="hof-input"/>
  </label>

  <label class="hof-label">Date Of Issue
    <input type="date" name="issuedate_human" value="{$datestr}" class="hof-input"
           onchange="this.form.issuedate.value = this.value ? Math.floor(new Date(this.value).getTime()/1000) : 0;"/>
    <input type="hidden" name="issuedate" value="{$issuedate}"/>
  </label>

  <label class="hof-label">Additional Notes
    <textarea name="notes" rows="4" class="hof-textarea">{$notes}</textarea>
  </label>

  <div class="hof-form-actions" style="margin-top:18px">
    <button type="submit" class="btn hof-btn-primary">Save changes</button>
    <a href="index.php?tab=achievements" class="btn hof-btn-secondary">Cancel</a>
  </div>
</form>
HTML;

echo $OUTPUT->footer();
