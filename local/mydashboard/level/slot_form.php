<?php
  global $USER, $CFG, $DB, $OUTPUT, $SESSION;
  if (!empty($SESSION->currenteditingcompany)) {
      $selectedcompany = $SESSION->currenteditingcompany;
  } else if (!empty($USER->profile->company)) {
      $usercompany = company::by_userid($USER->id);
      $selectedcompany = $usercompany->id;
  } else {
      $selectedcompany = "";
  }
$records = $DB->get_records('custom_level', array('companyid' => $selectedcompany));
$j = 1;
foreach ($records as $rec) {
    $array['id'][$j] = $rec->id;
    $array['level'][$j] = $rec->level;
    $array['point'][$j] = $rec->point;
    $array['grade'][$j] = $rec->grade;
    $array['icon'][$j] = $rec->icon;
    $j++;
}
?>

<div class="gam-admin-wrap">

  <style>
      #page-local-mydashboard-level-index #page-wrapper #page #topofscroll .container #page-content #region-main-box #region-main div[role="main"] {
    padding: 0px !important;
    background: transparent !important;
    box-shadow: none !important;
    }
 
    .gam-admin-wrap {
      --gam-primary: #003152;
      --gam-primary-light: #0a4a78;
      --gam-accent: #ec9707;
      --gam-accent-light: #ffb545;
      --gam-border: #e2e8f0;
      --gam-bg: #f7f9fb;
      --gam-text: #1f2d3d;
      --gam-text-muted: #6b7a8d;
      --gam-radius: 10px;

      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: var(--gam-text);
      max-width: 1100px;
      margin: 0 auto;
      padding: 4px;
    }

    .gam-admin-wrap * { box-sizing: border-box; }

    /* ---------- HEADER ---------- */
    .gam-admin-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      background: linear-gradient(135deg, var(--gam-primary) 0%, var(--gam-primary-light) 100%);
      padding: 20px 24px;
      border-radius: var(--gam-radius);
      margin-bottom: 20px;
      box-shadow: 0 4px 14px rgba(0, 49, 82, 0.18);
    }

    .gam-admin-header h2 {
      color: #ffffff;
      font-size: 1.3rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .gam-admin-header h2::before {
      content: "";
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--gam-accent);
      box-shadow: 0 0 0 4px rgba(236, 151, 7, 0.25);
    }

    .gam-admin-header p {
      color: rgba(255, 255, 255, 0.75);
      font-size: 0.82rem;
      margin: 4px 0 0 20px;
    }

    /* ---------- CARD ---------- */
    .gam-admin-card {
      background: #ffffff;
      border: 1px solid var(--gam-border);
      border-radius: var(--gam-radius);
      padding: 22px 24px;
      margin-bottom: 22px;
      box-shadow: 0 2px 8px rgba(0, 49, 82, 0.06);
    }

    /* ---------- TABLE HEAD ROW (desktop) ---------- */
    .gam-table-head {
      display: grid;
      grid-template-columns: 60px 1.6fr 1fr 1fr 1.8fr;
      gap: 14px;
      padding: 12px 16px;
      background: var(--gam-primary);
      border-radius: 8px;
      color: #ffffff;
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 10px;
    }

    /* ---------- ROW ---------- */
    .gam-level-row {
      display: grid;
      grid-template-columns: 60px 1.6fr 1fr 1fr 1.8fr;
      gap: 14px;
      align-items: center;
      padding: 12px 16px;
      border: 1px solid var(--gam-border);
      border-radius: 8px;
      margin-bottom: 10px;
      background: #fbfcfd;
      transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .gam-level-row:hover {
      border-color: var(--gam-accent);
      box-shadow: 0 2px 10px rgba(236, 151, 7, 0.1);
    }

    .gam-level-num {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 34px;
      height: 34px;
      border-radius: 8px;
      background: rgba(0, 49, 82, 0.08);
      color: var(--gam-primary);
      font-weight: 700;
      font-size: 0.85rem;
    }

    .gam-input {
      width: 100%;
      padding: 10px 12px;
      border: 1.5px solid var(--gam-border);
      border-radius: 8px;
      font-size: 0.88rem;
      color: var(--gam-text);
      background: #ffffff;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .gam-input:hover { border-color: #c9d4e0; }

    .gam-input:focus {
      outline: none;
      border-color: var(--gam-accent);
      box-shadow: 0 0 0 3px rgba(236, 151, 7, 0.15);
    }

    /* ---------- ICON / UPLOAD CELL ---------- */
    .gam-icon-cell {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .gam-icon-preview-wrap {
      flex: none;
      width: 46px;
      height: 46px;
      border-radius: 8px;
      overflow: hidden;
      border: 1.5px solid var(--gam-border);
      background: var(--gam-bg);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .gam-icon-preview-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .gam-upload-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--gam-primary);
      color: #ffffff;
      border: none;
      padding: 9px 14px;
      border-radius: 7px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .gam-upload-btn:hover {
      background: var(--gam-primary-light);
    }

    .gam-file-name {
      font-size: 0.72rem;
      color: var(--gam-text-muted);
      max-width: 90px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* ---------- ACTION BUTTON ---------- */
    .actionbutton {
      display: flex;
      justify-content: flex-end;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 2px solid var(--gam-bg);
    }

    .actionbutton .btn-primary,
    #formsubmit {
      background: var(--gam-accent) !important;
      border: none !important;
      color: #ffffff !important;
      padding: 11px 28px !important;
      border-radius: 8px !important;
      font-size: 0.92rem !important;
      font-weight: 600 !important;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(236, 151, 7, 0.35);
      transition: all 0.2s ease;
    }

    #formsubmit:hover {
      background: var(--gam-accent-light) !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(236, 151, 7, 0.4);
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 900px) {
      .gam-table-head { display: none; }

      .gam-level-row {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 14px;
      }

      .gam-level-row > div::before {
        content: attr(data-label);
        display: block;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--gam-accent);
        margin-bottom: 4px;
        letter-spacing: 0.4px;
      }

      .gam-level-num { width: auto; height: auto; justify-content: flex-start; background: none; padding: 0; }
      .gam-level-num::before { content: "Level "; color: var(--gam-text-muted); font-weight: 600; }
    }

    @media (max-width: 480px) {
      .gam-admin-card { padding: 14px; }
      .actionbutton { justify-content: stretch; }
      #formsubmit { width: 100%; }
    }
  </style>

  <!-- HEADER -->


  <div class="gam-admin-card">
    <form action="" method="POST" id="level_form1" enctype="multipart/form-data" onsubmit="return validateForm();">
      <div id="slotform" style="border:unset;">

        <div class="gam-table-head">
          <div>Level</div>
          <div>Rank</div>
          <div>Points</div>
          <div>Course Grade %</div>
          <div style="    display: flex;
    justify-content: center;">Icon</div>
        </div>

        <?php for ($i = 1; $i <= 10; $i++) { ?>
          <div class="gam-level-row formrow" id="1">
            <input type="hidden" name="id[]" value="<?php echo $array['id'][$i]; ?>">

            <div class="gam-level-num" data-label="Level"><?php echo $i; ?></div>

            <div data-label="Rank">
              <input type="text" name="level[]" class="gam-input" placeholder="Level Name" value="<?php echo $array['level'][$i]; ?>" required>
            </div>

            <div data-label="Points">
              <input type="number" name="point[]" id="st1" tagid="1" class="gam-input" placeholder="Point" value="<?php echo $array['point'][$i]; ?>" required>
            </div>

            <div data-label="Course Grade %">
              <input type="number" name="grade[]" id="et1" tagid="1" class="gam-input" placeholder="Grade" value="<?php echo $array['grade'][$i]; ?>" required>
            </div>

            <div class="gam-icon-cell" data-label="Icon">
              <div class="gam-icon-preview-wrap">
                <img
                  id="preview_<?php echo $i; ?>"
                  src="<?php echo !empty($array['icon'][$i]) ? $CFG->wwwroot.'/local/mydashboard/images/'.$array['icon'][$i] : $CFG->wwwroot.'/local/mydashboard/images/default-icon.png'; ?>"
                  width="50"
                  class="si-img"
                  onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><rect width=%2224%22 height=%2224%22 rx=%224%22 fill=%22%23003152%22/><path d=%22M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.4 5.7 21l2.3-7.2-6-4.6h7.6z%22 fill=%22%23ec9707%22/></svg>';"
                  alt="icon"/>
              </div>

              <input type="file" min="1" id="prof_<?php echo $i; ?>" name="icon[]" class="form-control" style="display:none" onchange="previewIcon(this, <?php echo $i; ?>)">
              <label for="prof_<?php echo $i; ?>" class="gam-upload-btn">Choose File</label>
              <span class="gam-file-name" id="filename_<?php echo $i; ?>"></span>
            </div>

          </div>
        <?php } ?>

        <div class="actionbutton">
          <input type="submit" name="submitbutton" id="formsubmit" class="btn btn-primary" value="Save">
        </div>
      </div>
    </form>
  </div>

</div>

<script>
    function validateForm() {
        var isValid = true;
        var inputs = document.querySelectorAll('#level_form1 input[required]');

        inputs.forEach(function(input) {
            if (input.value.trim() === '') {
                isValid = false;
                alert('Please fill in all required fields.');
            }
        });

        return isValid;
    }

    // Live preview of the chosen icon file + safe fallback (never a broken image).
    function previewIcon(input, i) {
        var preview = document.getElementById('preview_' + i);
        var fileNameEl = document.getElementById('filename_' + i);

        if (input.files && input.files[0]) {
            var file = input.files[0];
            fileNameEl.textContent = file.name;

            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            fileNameEl.textContent = '';
        }
    }
</script>