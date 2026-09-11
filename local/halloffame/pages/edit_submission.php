<?php
// local/halloffame/pages/edit_submission.php
// Edit a *pending* submission. Owner only.

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/halloffame/lib.php');

require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$id      = required_param('id', PARAM_INT);
$context = context_system::instance();

require_capability('local/halloffame:submit', $context);

// Fetch submission
$sub = $DB->get_record('halloffame_submissions', ['id' => $id], '*', MUST_EXIST);

// Security checks
if ((int)$sub->userid !== (int)$USER->id) {
    throw new moodle_exception('accessdenied', 'local_halloffame');
}

if ($sub->status !== 'pending') {
    throw new moodle_exception(
        'cannoteditnonpending',
        'local_halloffame',
        '',
        null,
        'Only pending submissions can be edited.'
    );
}

// Page setup
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/halloffame/pages/edit_submission.php', ['id' => $id]));
$PAGE->set_title(get_string('editsubmission', 'local_halloffame'));
// $PAGE->set_heading(get_string('halloffame', 'local_halloffame'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/halloffame/styles.css'));

$PAGE->navbar->add(
    get_string('halloffame', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/index.php')
);

$PAGE->navbar->add(
    get_string('mysubmissions', 'local_halloffame'),
    new moodle_url('/local/halloffame/pages/my_submissions.php')
);

$PAGE->navbar->add(get_string('editsubmission', 'local_halloffame'));

/**
 * ===============================
 * HANDLE FORM SUBMISSION
 * ===============================
 */
if (data_submitted() && confirm_sesskey()) {

    $data = [
        'title'     => required_param('title', PARAM_TEXT),
        'issuer'    => optional_param('issuer', '', PARAM_TEXT),
        'type'      => optional_param('type', '', PARAM_TEXT),
        'notes'     => optional_param('notes', '', PARAM_TEXT),
        'issuedate' => optional_param('issuedate', 0, PARAM_INT),
    ];

    // ===============================
    // FILE UPLOAD HANDLING
    // ===============================
    if (!empty($_FILES['certificate']['name'])) {

        $file = $_FILES['certificate'];

        $filename = time() . '_' . clean_param($file['name'], PARAM_FILE);
       $uploaddir = $CFG->dirroot . '/local/halloffame/uploads/';

if (!file_exists($uploaddir)) {
    mkdir($uploaddir, 0777, true);
}

$filename = time() . '_' . clean_param($file['name'], PARAM_FILE);
$uploadpath = $uploaddir . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadpath)) {
    $data['fileurl'] = $CFG->wwwroot . '/local/halloffame/uploads/' . $filename;
}
    }

    // ===============================
    // UPDATE DB
    // ===============================
    $result = \local_halloffame\manager::update_submission($id, $data);

    if ($result) {
        redirect(
            new moodle_url('/local/halloffame/pages/my_submissions.php'),
            get_string('changessaved', 'core'),
            0
        );
    } else {
        throw new moodle_exception('updatefailed', 'local_halloffame');
    }
}

/**
 * ===============================
 * OUTPUT PAGE
 * ===============================
 */
echo $OUTPUT->header();



$action = $PAGE->url->out(false);
$sk     = sesskey();

$title  = s($sub->title);
$issuer = s($sub->issuer ?? '');
$notes  = s($sub->notes ?? '');

$issuedate = (int)($sub->issuedate ?? 0);
$datestr = $issuedate ? date('Y-m-d', $issuedate) : '';

/**
 * ===============================
 * TYPE DROPDOWN
 * ===============================
 */
$types = $DB->get_records_sql("
    SELECT DISTINCT type
    FROM {halloffame_submissions}
    WHERE type IS NOT NULL AND type <> ''
    ORDER BY type ASC
");

$options = '<option value="">Select Type</option>';
$currenttype = $sub->type ?? '';

foreach ($types as $t) {
    $val = s($t->type);
    $sel = ($currenttype === $t->type) ? 'selected' : '';
    $options .= "<option value=\"{$val}\" {$sel}>{$val}</option>";
}

/**
 * ===============================
 * FILE PREVIEW
 * ===============================
 */
$fileurl = $sub->fileurl ?? '';
$filepreview = '';

if (!empty($fileurl)) {

    $ext = strtolower(pathinfo($fileurl, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {

        $filepreview = '
        <div class="hof-file-preview" style="    display: flex;
    position: relative;
    left: 95px;
    gap: 10px;">
            <p><strong>Current Certificate:</strong></p>
            <img src="' . s($fileurl) . '" style="max-width:200px;border-radius:8px;">
        </div>';

    } else {

        $filepreview = '
        <div class="hof-file-preview"style="    display: flex;
    position: relative;
    left: 95px;
    gap: 10px;">
            <p><strong>Current Certificate:</strong></p>
            <a href="' . s($fileurl) . '" target="_blank" class="btn btn-secondary">
                View Certificate
            </a>
        </div>';
    }
}

/**
 * ===============================
 * FORM UI
 * ===============================
 */
echo <<<HTML
<div class="hof-form-wrap">
  <div class="hof-form-card">

    <h2 class="hof-form-title">Edit Submission</h2>

    <form method="post" enctype="multipart/form-data" action="{$action}" class="hof-form">

      <input type="hidden" name="sesskey" value="{$sk}"/>

      <div class="hof-form-row">
        <label class="hof-label">Certificate Title</label>
        <input type="text" name="title" value="{$title}" required class="hof-input"/>
      </div>

      <div class="hof-form-row">
        <label class="hof-label">Issuing Organisation</label>
        <input type="text" name="issuer" value="{$issuer}" class="hof-input"/>
      </div>

      <div class="hof-form-row">
        <label class="hof-label">Date Of Issue</label>
        <input type="date"
               name="issuedate_human"
               value="{$datestr}"
               class="hof-input"
               onchange="this.form.issuedate.value = this.value ? Math.floor(new Date(this.value).getTime()/1000) : 0;"/>
        <input type="hidden" name="issuedate" value="{$issuedate}"/>
      </div>

      <div class="hof-form-row">
        <label class="hof-label">Certificate Type</label>
        <select name="type" class="hof-input">
          {$options}
        </select>
      </div>

      <div class="hof-form-row">
        <label class="hof-label">Additional Notes</label>
        <textarea name="notes" rows="4" class="hof-input hof-textarea">{$notes}</textarea>
      </div>

      <div class="hof-form-row">
        {$filepreview}
      </div>

      <div class="hof-form-row">
        <label class="hof-label">Replace Certificate</label>

        <div class="hof-dropzone" id="hofDropzone">
          <input type="file" name="certificate" class="hof-file-input" accept=".pdf,.jpg,.jpeg,.png"/>

          <div class="hof-dropzone-inner">
            <span class="hof-upload-icon">☁</span>
            <span class="hof-dz-label">Upload a File</span>
            <small>Drag and drop or click to browse</small>
          </div>
        </div>

      </div>

      <div class="hof-form-actions" style="margin-top:18px">
        <button type="submit" class="btn hof-btn-primary">Save changes</button>
        <a href="my_submissions.php" style="background:#003152;" class="btn hof-btn-secondary">Cancel</a>
      </div>

    </form>

  </div>
</div>
HTML;
?>
<script>
  document.querySelector('.hof-file-input').addEventListener('change', function() {
    if (this.files.length > 0) {
        this.closest('.hof-dropzone').querySelector('.hof-dz-label').innerText = this.files[0].name;
    }
});
</script>
<?php
echo $OUTPUT->footer();