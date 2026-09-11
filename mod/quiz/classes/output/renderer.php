<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_quiz\output;

use cm_info;
use coding_exception;
use context;
use context_module;
use html_table;
use html_table_cell;
use html_writer;
use mod_quiz\access_manager;
use mod_quiz\form\preflight_check_form;
use mod_quiz\output\grades\grade_out_of;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use moodle_url;
use plugin_renderer_base;
use popup_action;
use question_display_options;
use mod_quiz\quiz_settings;
use renderable;
use single_button;
use stdClass;

/**
 * The main renderer for the quiz module.
 *
 * @package   mod_quiz
 * @category  output
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Builds the review page
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param array $slots of slots to be displayed.
     * @param int $page the current page number
     * @param bool $showall whether to show entire attempt on one page.
     * @param bool $lastpage if true the current page is the last page.
     * @param display_options $displayoptions instance of display_options.
     * @param attempt_summary_information|array $summarydata summary information about the attempt.
     *      Passing an array is deprecated.
     * @return string HTML to display.
     */
public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall,
        $lastpage, display_options $displayoptions, $summarydata) {

    if (is_array($summarydata)) {
        debugging('Since Moodle 4.4, $summarydata passed to review_page should be a attempt_summary_information.',
            DEBUG_DEVELOPER);
        $summarydata = $this->filter_review_summary_table($summarydata, $page);
        $summarydata = attempt_summary_information::create_from_legacy_array($summarydata);
    }
 
    // ── Attempt data ─────────────────────────────────────────────────────
    $attempt   = $attemptobj->get_attempt();
    $quiz      = $attemptobj->get_quiz();
    
    $sumgrades = (float)($attempt->sumgrades ?? 0);
    $maxgrades = (float)($quiz->sumgrades ?? 0);
    // $converted = ($maxgrades > 0) ? round(($sumgrades / $maxgrades) * 35, 1) : 0;
    $score     = $maxgrades > 0 ? round(($sumgrades / $maxgrades) * 100, 1) : 0;
    $duration  = ($attempt->timefinish > 0) ? ($attempt->timefinish - $attempt->timestart) : 0;
    $timetaken = $duration > 0 ? format_time($duration) : '—';
    $quizname  = format_string($attemptobj->get_quiz_name());
    $viewurl   = $attemptobj->view_url()->out(false);
 
    // ── Per-question stats ────────────────────────────────────────────────
    $allslots   = $attemptobj->get_slots();
    $statCorrect = $statIncorrect = $statPartial = $statUnanswered = $statFlagged = 0;
 
    // Build slot meta for palette
    $slotMeta = [];
    foreach ($allslots as $s) {
        if (!$attemptobj->is_real_question($s)) { continue; }
        $sc      = $attemptobj->get_question_state($s)->get_state_class(true);
        $flagged = $attemptobj->is_question_flagged($s);
        $chipCls = in_array($sc, ['answersaved','complete']) ? 'correct' : $sc;
        if (!in_array($chipCls, ['correct','incorrect','partial'])) { $chipCls = 'unanswered'; }
 
        if ($chipCls === 'correct')   { $statCorrect++;   }
        elseif ($chipCls === 'incorrect') { $statIncorrect++; }
        elseif ($chipCls === 'partial')   { $statPartial++;   }
        else                              { $statUnanswered++; }
        if ($flagged) { $statFlagged++; }
 
        $slotMeta[$s] = [
            'cls'     => $chipCls,
            'flagged' => $flagged,
            'num'     => $attemptobj->get_question_number($s),
            'url' => "#qs{$s}",
        ];
    }
 
    // ── Score ring ────────────────────────────────────────────────────────
    if ($score >= 75)     { $gc = '#16a34a'; $gb = '#dcfce7'; $gl = 'Excellent'; }
    elseif ($score >= 50) { $gc = '#d97706'; $gb = '#fef3c7'; $gl = 'Average';   }
    else                  { $gc = '#dc2626'; $gb = '#fee2e2'; $gl = 'Needs Work'; }
 
    $circ      = round(2 * M_PI * 52, 2);
    $dashoff   = round($circ * (1 - $score / 100), 2);
    $marksBar  = $maxgrades > 0 ? round(($sumgrades / $maxgrades) * 100) : 0;
    $marksDisp = number_format($sumgrades, 1) . ' / ' . number_format($maxgrades, 1);
 
    // ── Build output ──────────────────────────────────────────────────────
    $output = '';
    $output .= $this->header();
 
    /* ── Fonts & master CSS ─────────────────────────────────────────────── */
    $output .= <<<'CSS'
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,500,1,0&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.ms-icon{
  font-family: 'Material Symbols Rounded' !important;
  font-weight: normal;
  font-style: normal;
  font-size: 20px;
  display: inline-block;
  line-height: 1;

  /* REQUIRED for icons to render */
  font-variation-settings:
    'FILL' 1,
    'wght' 500,
    'GRAD' 0,
    'opsz' 24;
}
#page-mod-quiz-review{overflow: hidden;}
.path-mod-quiz #page {
    margin-right: 10px;
}
/* ── TOP SUMMARY CARDS ───────────────── */
.qr-top-cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:20px;
}
.qr-top-card{
    background:#fff;
    border-radius:14px;
    padding:16px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}
.qr-top-title{
    font-size:12px;
    color:#64748b;
    font-weight:600;
}
.qr-top-value{
    font-size:22px;
    font-weight:800;
    color:#0f172a;
    margin-top:5px;
}
.qr-top-icon{
    width:40px;height:40px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:8px;
    color:#fff;
}
.qr-i-blue{background:#1e3a8a;}
.qr-i-green{background:#16a34a;}
.qr-i-red{background:#dc2626;}
.qr-i-orange{background:#f97316;}
/* ── scope ── */
#qrw,#qrw *{box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;}
 
/* ── full-height shell: header stays, body fills, no outer scroll ── */
#qrw{
    display:flex;flex-direction:column;
    height:100vh; /* adjust 60px to your theme header height */
    background:#f1f5f9;
    overflow:hidden;
}
 
/* ── top hero bar (fixed height) ─────────────────────────── */
.qr-hero{
    flex-shrink:0;
    background:linear-gradient(135deg,#003152 0%,#005288 100%);
    padding:2px 28px;
    display:flex;align-items:center;gap:14px;
    position:fixed;overflow:hidden;width: 100%;
}
.qr-hero::before{
    content:'';position:absolute;inset:0;
    background:url("data:image/svg+xml,%3Csvg width='52' height='52' viewBox='0 0 52 52' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='26' cy='26' r='4'/%3E%3C/g%3E%3C/svg%3E");
}
.qr-back{
    position:relative;z-index:1;
    display:inline-flex;align-items:center;gap:5px;
    background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);
    color:#fff;border-radius:10px;padding:7px 14px;
    font-size:13px;font-weight:600;text-decoration:none;transition:.2s;
    white-space:nowrap;
}
.qr-back:hover{background:rgba(255,255,255,.22);color:#fff;}
.qr-hero-text{position:relative;z-index:1;flex:1;}
.qr-hero-title{color:#fff;font-size:17px;font-weight:800;line-height:1.2;}
.qr-hero-sub{color:rgba(255,255,255,.6);font-size:12px;margin-top:2px;}
 
/* ── two-column body: left fixed, right scrolls ────────────── */
.qr-body{
    flex:1;overflow:hidden;
    display:grid;
    grid-template-columns:290px 1fr;
    gap:0;
    
    min-height:0;
    
}
 
/* ── left sidebar (sticky, no scroll — internal scroll only) ── */
.qr-sidebar{
    overflow-y:auto;
    padding:18px 14px;
    background:#f8fafc;
    border-right:1px solid #e2e8f0;
    display:flex;flex-direction:column;gap:14px;
}
.qr-sidebar::-webkit-scrollbar{width:4px;}
.qr-sidebar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px;}
 
/* ── right content area (scrolls independently) ─────────────── */
.qr-content{
    overflow-y:auto;
    padding:20px 22px 40px;
}
.qr-content::-webkit-scrollbar{width:5px;}
.qr-content::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px;}
 
/* ── shared card ─────────────────────────────────────────────── */
.qr-card{
    background:#fff;border-radius:16px;
    box-shadow:0 2px 12px rgba(0,49,82,.07);
    overflow:hidden;
}
 
/* ── score ring card ─────────────────────────────────────────── */
.qr-score-pad{padding:20px 14px;  position: relative;z-index: 1001;overflow: auto;    height: 100%;}
.qr-ring-label{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;text-align:center;margin-bottom:10px;}
.qr-ring-wrap{display:flex;justify-content:center;margin-bottom:10px;}
.qr-ring-track{fill:none;stroke:#e2e8f0;stroke-width:9;}
.qr-ring-fill{fill:none;stroke-width:9;stroke-linecap:round;transform:rotate(-90deg);transform-origin:center;transition:stroke-dashoffset 1.1s cubic-bezier(.4,0,.2,1);}
.qr-ring-pct{font-size:22px;font-weight:800;dominant-baseline:middle;text-anchor:middle;}
.qr-ring-lbl{font-size:10px;font-weight:600;fill:#94a3b8;dominant-baseline:middle;text-anchor:middle;}
.qr-badge-wrap{display:flex;justify-content:center;margin-bottom:14px;}
.qr-badge{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:50px;font-size:11px;font-weight:700;}
 
/* stats 2x2 */
.qr-stats{display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:12px;}
.qr-stat{background:#f8fafc;border-radius:10px;padding:5px 3px;text-align:center;}
.qr-stat-v{font-size:18px;font-weight:800;color:#0f172a;}
.qr-stat-l{font-size:9px;font-weight:600;color:#94a3b8;margin-top:1px;}
 
/* marks bar */
.qr-mbar-lbl{display:flex;justify-content:space-between;font-size:11px;font-weight:600;color:#64748b;margin-bottom:5px;}
.qr-mbar-out{height:7px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:14px;}
.qr-mbar-in{height:100%;border-radius:99px;background:linear-gradient(90deg,#003152,#0077b6);transition:width 1s ease;}
 
/* action btns */
.qr-actions{display:flex;flex-direction:column;gap:8px;}
.qr-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:.2s;}
.qr-btn-primary{background:linear-gradient(135deg,#003152,#005288);color:#fff;}
.qr-btn-primary:hover{opacity:.88;color:#fff;}
.qr-btn-outline{background:#fff;color:#003152;border:2px solid #003152;}
.qr-btn-outline:hover{background:#003152;color:#fff;}
 
/* ── nav palette card ─────────────────────────────────────────── */
.qr-nav-pad{padding:16px 14px;}
.qr-sec-title{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;display:flex;align-items:center;gap:6px;margin-bottom:12px;}
.qr-palette{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;}
 
/* chip: base */
.qr-chip{
    width:34px;height:34px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:700;text-decoration:none;
    transition:transform .15s,box-shadow .15s;
    position:relative;
}
.qr-chip:hover{transform:scale(1.1);box-shadow:0 4px 10px rgba(0,0,0,.15);}
.qr-chip.correct  {background:#dcfce7;color:#15803d;}
.qr-chip.incorrect{background:#fee2e2;color:#dc2626;}
.qr-chip.partial  {background:#fef9c3;color:#a16207;}
.qr-chip.unanswered{background:#f1f5f9;color:#64748b;}
/* flagged pin */
.qr-chip.flagged::after{
    content:'';position:absolute;top:-3px;right:-3px;
    width:8px;height:8px;border-radius:50%;
    background:#f97316;border:1.5px solid #fff;
}
 
/* legend */
.qr-legend{display:flex;flex-wrap:wrap;gap:8px;}
.qr-leg{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#64748b;}
.qr-leg-dot{width:9px;height:9px;border-radius:2px;}
.qr-leg-dot.correct  {background:#16a34a;}
.qr-leg-dot.incorrect{background:#dc2626;}
.qr-leg-dot.partial  {background:#ca8a04;}
.qr-leg-dot.unanswered{background:#cbd5e1;}
.qr-leg-dot.flagged  {background:#f97316;border-radius:50%;}
 
/* ── question blocks (right side) ─────────────────────────────── */
.qr-q-block{
    border-radius:14px;
    background:#fff;
    margin-bottom:18px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,49,82,.06);
    border-left:4px solid #e2e8f0;
    transition:box-shadow .2s;
}
.qr-q-block:hover{box-shadow:0 4px 18px rgba(0,49,82,.1);}
.qr-q-block.correct  {border-left-color:#16a34a;}
.qr-q-block.incorrect{border-left-color:#dc2626;}
.qr-q-block.partial  {border-left-color:#ca8a04;}
.qr-q-block.unanswered{border-left-color:#94a3b8;}
 
/* question header */
.qr-q-hdr{
    display:flex;align-items:center;gap:10px;
    padding:12px 16px;
    border-bottom:1px solid #f1f5f9;
    background:#fafafa;
}
.qr-q-hdr-num{font-size:14px;font-weight:800;color:#0f172a;}
.qr-q-hdr-mark{margin-left:auto;font-size:13px;font-weight:700;}
.qr-q-hdr-mark.correct  {color:#16a34a;}
.qr-q-hdr-mark.incorrect{color:#dc2626;}
.qr-q-hdr-mark.partial  {color:#ca8a04;}
.qr-q-hdr-mark.unanswered{color:#94a3b8;}
 
/* flag icon on header */
.qr-flag-icon{
    display:inline-flex;align-items:center;
    color:#f97316;margin-left:6px;
}
 
/* question body — the Moodle rendered content lives here */
.qr-q-body{padding:14px 16px;}
 
/* ── override Moodle default question styles inside our blocks ── */
 
/* TRUE / FALSE pill buttons */
.qr-q-body .answer .r0,
.qr-q-body .answer .r1{
    display:inline-block;width:calc(50% - 6px);
}
/* We re-skin via a wrapper added in JS below */
 
/* ── Material Symbols fix (no text leaking) ── */
.ms-icon{
    font-family:'Material Symbols Rounded';
    font-weight:normal;font-style:normal;
    font-size:inherit;line-height:1;
    letter-spacing:normal;text-transform:none;
    display:inline-block;white-space:nowrap;
    word-wrap:normal;direction:ltr;
    font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24;
    vertical-align:middle;
}
 
/* status badge (header) */
.qr-status-badge{
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 10px;border-radius:50px;
    font-size:11px;font-weight:700;
}
.qr-status-badge.correct  {background:#dcfce7;color:#15803d;}
.qr-status-badge.incorrect{background:#fee2e2;color:#dc2626;}
.qr-status-badge.partial  {background:#fef9c3;color:#a16207;}
.qr-status-badge.unanswered{background:#f1f5f9;color:#64748b;}
 
/* ── custom True/False pill UI ─────────────────────────────────── */
.tf-wrap{
    display:grid;grid-template-columns:1fr 1fr;
    gap:10px;margin:10px 0;
}
.tf-btn{
    display:flex;align-items:center;justify-content:center;gap:8px;
    padding:14px 10px;
    border-radius:50px;
    font-size:14px;font-weight:600;
    border:2px solid #e2e8f0;
    background:#fff;color:#374151;
    cursor:default;
    transition:.15s;
}
/* selected + correct */
.tf-btn.selected-correct{
    border-color:#16a34a;color:#16a34a;
    background:#f0fdf4;
}
/* selected + wrong */
.tf-btn.selected-wrong{
    border-color:#dc2626;color:#dc2626;
    background:#fef2f2;
}
/* correct answer (not selected) */
.tf-btn.correct-answer{
    border-color:#16a34a;color:#16a34a;
    background:#f0fdf4;
}
.qr-q-body .outcome,
.qr-q-body .specificfeedback,
.qr-q-body .generalfeedback {
    display: none !important;
}
/* feedback bar */
.qr-feedback{
    display:flex;align-items:center;gap:8px;
    padding:10px 14px;
    border-radius:10px;
    font-size:13px;font-weight:500;
    margin-top:10px;
}
.qr-feedback.correct  {background:#f0fdf4;color:#15803d;}
.qr-feedback.incorrect{background:#fef2f2;color:#dc2626;}
.qr-feedback .ms-icon{font-size:18px;}
 
/* ── nav buttons (prev/next) ─────────────────────────────────── */
/* .qr-nav-btns{
    display:flex;align-items:center;justify-content:center;
    gap:12px;padding:16px 22px;
    background:#f8fafc;border-top:1px solid #e2e8f0;
    flex-shrink:0;
} */
    
.qr-nav-btns {
    width: 80%;
    right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: -3px;
    padding: 16px 22px;
    background: #fff;
    position: fixed;
    bottom: 0;
    z-index: 1;}

.qr-nav-btns a,.qr-nav-btns button{
    display:inline-flex;align-items:center;gap:6px;
    padding:9px 20px;border-radius:10px;font-size:13px;font-weight:700;
    text-decoration:none;border:none;cursor:pointer;transition:.2s;
}
.qr-nav-btns .prev-btn{background:#fff;color:#003152;border:2px solid #003152;}
.qr-nav-btns .prev-btn:hover{background:#003152;color:#fff;}
.qr-nav-btns .next-btn,.qr-nav-btns .finish-btn{background:linear-gradient(135deg,#003152,#005288);color:#fff;}
.qr-nav-btns .next-btn:hover,.qr-nav-btns .finish-btn:hover{opacity:.88;color:#fff;}
 
/* ── hide original Moodle question chrome we don't need ─────── */
.qr-q-body .que > .info,
.qr-q-body .que .formulation .qtext + br{display:none!important;}
/* keep formulation readable */
.qr-q-body .que .formulation{
    background:#f8fafc!important;
    border:1px solid #e2e8f0!important;
    border-radius:10px!important;
    padding:14px!important;
    font-size:14px!important;
    color:#1e293b!important;
    margin-bottom:12px!important;
}
/* hide moodle's own correct/incorrect outcome (we show our own) */
.qr-q-body .outcome{display:none!important;}
/* hide moodle's radio buttons / labels for tf — we replace in JS */
/* .qr-q-body .answer{display:none!important;} */
/* but show answer for non-tf question types */
.qr-q-body.non-tf .answer{display:block!important;}
</style>
CSS;
 
    /* ── HERO ───────────────────────────────────────────────────────────── */
    $output .= <<<HTML
<div id="qrw" >
<div class="qr-hero" style="z-index:1002;">
  <a href="{$viewurl}" class="qr-back">
    <span class="ms-icon" style="font-size:18px;">arrow_back</span> Back
  </a>
  <div class="qr-hero-text">
    <div class="qr-hero-title">{$quizname}</div>
    <div class="qr-hero-sub">Attempt Review</div>
  </div>
</div>
HTML;
 
    /* ── BODY ───────────────────────────────────────────────────────────── */
    $output .= '<div class="qr-body">';
 
    /* ════════════════════════════════
/* ── Score card + Navigator (MERGED) ───────────────────────────────────── */
$reattempturl = $attemptobj->start_attempt_url()->out(false);

$output .= <<<HTML
<div class="qr-card ">
<div class="qr-score-pad">

  <div class="qr-ring-label mt-5" >Your Score</div>

  <div class="qr-ring-wrap ">
    <svg width="90" height="90" viewBox="0 0 120 120" >
      <circle class="qr-ring-track" cx="60" cy="60" r="52"/>
      <circle class="qr-ring-fill" cx="60" cy="60" r="52"
        stroke="{$gc}"
        stroke-dasharray="{$circ}"
        stroke-dashoffset="{$dashoff}"
        id="qr-ring"/>
      <text class="qr-ring-pct" x="60" y="57" fill="{$gc}">{$score}%</text>
      <text class="qr-ring-lbl" x="60" y="72">{$gl}</text>
    </svg>
  </div>

  <!-- Badge -->
  <div class="qr-badge-wrap ">
    <span class="qr-badge" style="background:{$gb};color:{$gc};">
      <span class="ms-icon" style="font-size:15px;">emoji_events</span>{$gl}
    </span>
  </div>

  <!-- 🔥 NAVIGATOR MOVED HERE -->
  <div class="qr-nav-pad" style="margin-top:16px;">
    <div class="qr-sec-title">
      <span class="ms-icon" style="font-size:14px;">grid_view</span> Navigator
    </div>

    <div class="qr-palette">
HTML;

foreach ($slotMeta as $sm) {
    $flagCls = $sm['flagged'] ? ' flagged' : '';
    $output .= "<a href=\"{$sm['url']}\" class=\"qr-chip {$sm['cls']}{$flagCls}\" title=\"Q{$sm['num']}\">{$sm['num']}</a>";
}

$output .= <<<HTML
    </div>

    <!-- Legend -->
   <!-- 🔥 MERGED STATS (Legend + Stats) -->
<div class="qr-stats qr-stats-lg d-flex ">

  <div class="qr-stat">
    <div class="qr-stat-v" style="color:#16a34a;">{$statCorrect}</div>
    <div class="qr-stat-l">✔ Correct</div>
  </div>

  <div class="qr-stat">
    <div class="qr-stat-v" style="color:#dc2626;">{$statIncorrect}</div>
    <div class="qr-stat-l">✖ Incorrect</div>
  </div>



  <div class="qr-stat">
    <div class="qr-stat-v" style="color:#64748b;">{$statUnanswered}</div>
    <div class="qr-stat-l">○ Skipped</div>
  </div>

  <div class="qr-stat">
    <div class="qr-stat-v" style="color:#f97316;">{$statFlagged}</div>
    <div class="qr-stat-l">⚑ Flagged</div>
  </div>

</div>
  </div>



  <!-- Marks -->
  <div class="qr-mbar-lbl ">
    <span>Marks</span><span style="font-weight:800;color:#0f172a;">{$marksDisp}</span>
  </div>
  <div class="qr-mbar-out ">
    <div class="qr-mbar-in" style="width:{$marksBar}%;"></div>
  </div>

  <!-- Actions -->
  <div class="qr-actions">
    <a href="{$reattempturl}" class="qr-btn qr-btn-outline">
      <span class="ms-icon" style="font-size:16px;">refresh</span> Re-attempt
    </a>
  </div>

</div>
</div>
HTML;

/* ── REMOVE THIS OLD BLOCK COMPLETELY ───────────────── */

    /* ════════════════════════════════
       RIGHT CONTENT
    ════════════════════════════════ */
    $output .= '<div class="qr-content">';
 $totalquestions = count($allslots);

$output .= <<<HTML
<div class="qr-top-cards mt-5">

  <div class="qr-top-card">
    <div class="qr-top-icon qr-i-blue">
      <span class="ms-icon">help</span>
    </div>
    <div class="qr-top-title">Total Questions</div>
    <div class="qr-top-value">{$totalquestions}</div>
  </div>
  <div class="qr-top-card">
    <div class="qr-top-icon qr-i-green">
      <span class="ms-icon">emoji_events</span>
    </div>
    <div class="qr-top-title">Your Score</div>
    <div class="qr-top-value">
      {$sumgrades}/{$maxgrades}
      <br><span style="font-size:14px;color:#64748b;">{$score}%</span>
    </div>
  </div>

  <div class="qr-top-card">
    <div class="qr-top-icon qr-i-orange">
      <span class="ms-icon">refresh</span>
    </div>
    <div class="qr-top-title">Attempts</div>
    <div class="qr-top-value">Unlimited</div>
  </div>

  <div class="qr-top-card">
    <div class="qr-top-icon qr-i-red">
      <span class="ms-icon">schedule</span>
    </div>
    <div class="qr-top-title">Time Taken</div>
    <div class="qr-top-value">{$timetaken}</div>
  </div>

</div>
HTML;
    // Build slot lookup for the current page slots
    $slotArr = [];
    foreach ($slots as $s) {
        $slotArr[] = $s;
    }
 
    foreach ($slotArr as $slot) {
        if (!$attemptobj->is_real_question($slot)) { continue; }
 
        $meta    = $slotMeta[$slot] ?? null;
        $cls     = $meta ? $meta['cls'] : 'unanswered';
        $flagged = $meta ? $meta['flagged'] : false;
        $qnum    = $attemptobj->get_question_number($slot);
 
        // Status badge text & icon
        $statusMap = [
            'correct'    => ['check_circle',  'Correct'],
            'incorrect'  => ['cancel',         'Incorrect'],
            'partial'    => ['adjust',          'Partial'],
            'unanswered' => ['help',            'Not Answered'],
        ];
        [$sIcon, $sLabel] = $statusMap[$cls] ?? ['help','Unknown'];
 
        // Mark display
        $markVal  = $attemptobj->get_question_mark($slot);
        $markMax  = $quiz->sumgrades > 0
                        ? $attemptobj->get_question_mark($slot)   // fallback
                        : 0;
        // Get proper max per question via qa
        try {
            $qa      = $attemptobj->get_question_attempt($slot);
            $markMax = $qa->get_max_mark();
            $markVal = $qa->get_mark();
        } catch (\Throwable $e) { }
 
        $markValFmt = $markVal !== null ? number_format((float)$markVal, 2) : '—';
        $markMaxFmt = $markMax !== null ? number_format((float)$markMax, 2) : '—';
 
        $flagHtml = $flagged
            ? '<span class="qr-flag-icon"><span class="ms-icon" style="font-size:16px;color:#f97316;">flag</span></span>'
            : '';
 
        $output .= <<<HTML
<div class="qr-q-block {$cls}" id="qs{$slot}">
  <div class="qr-q-hdr">
    <span class="qr-q-hdr-num">Question {$qnum}{$flagHtml}</span>
    <span class="qr-status-badge {$cls}">
      <span class="ms-icon" style="font-size:13px;">{$sIcon}</span>{$sLabel}
    </span>
    <span class="qr-q-hdr-mark {$cls}" style="margin-left:auto;">
      Mark {$markValFmt} out of {$markMaxFmt}
    </span>
  </div>
  <div class="qr-q-body" data-slot="{$slot}" data-cls="{$cls}">
HTML;
        $output .= $attemptobj->render_question($slot, true, $this,
                $attemptobj->review_url($slot, $page, $showall));
        $output .= '</div></div>';
    }
 
       /* ── Bottom nav buttons ─────────────────────────────────────────────── */
    $output .= '<div class="qr-nav-btns">';
    if ($page > 0) {
        $prevUrl = $attemptobj->review_url(null, $page - 1, $showall)->out(false);
        $output .= "<a href=\"{$prevUrl}\" class=\"prev-btn\"><span class=\"ms-icon\" style=\"font-size:16px;\">chevron_left</span>Previous</a>";
    }
    if ($lastpage) {
        $finUrl  = $attemptobj->view_url()->out(false);
        $output .= "<a href=\"{$finUrl}\" class=\"finish-btn\">Finish Review <span class=\"ms-icon\" style=\"font-size:16px;\">check_circle</span></a>";
    } else {
        $nextUrl = $attemptobj->review_url(null, $page + 1, $showall)->out(false);
        $output .= "<a href=\"{$nextUrl}\" class=\"next-btn\">Next <span class=\"ms-icon\" style=\"font-size:16px;\">chevron_right</span></a>";
    }
    $output .= '</div>';
    $output .= '</div>'; // qr-content
    $output .= '</div>'; // qr-body
 
 
 
    $output .= '</div>'; // #qrw
 
    /* ── JS: ring animation + rebuild True/False UI ─────────────────────── */
$output .= <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function(){

    /* ── Ring animation ── */
    var ring = document.getElementById('qr-ring');
    if(ring){
        var final = ring.getAttribute('stroke-dashoffset');
        ring.style.strokeDashoffset = ring.getAttribute('stroke-dasharray');
        setTimeout(function(){ ring.style.strokeDashoffset = final; }, 150);
    }

    /* ══════════════════════════════════════════════════════════════════
       SHARED HELPER: detect selected & correct row index from Moodle HTML
       
       From actual Moodle HTML we can see:
       - Each option is a div like <div class="r0"> or <div class="r1">
         (these alternate 0/1 for odd/even styling — NOT the option index)
       - SELECTED = the row whose <input> has checked="checked" attribute
       - CORRECT  = the row that has class "correct" on the div
    ══════════════════════════════════════════════════════════════════ */
    function getAnswerInfo(answerDiv) {
        var rows = answerDiv.querySelectorAll('.r0, .r1');
        var selectedRow = null;
        var correctRow  = null;

        rows.forEach(function(row){
            var radio = row.querySelector('input[type="radio"]');
            // Check HTML attribute (server-rendered) AND JS property
            if(radio && (radio.checked || radio.getAttribute('checked') !== null)){
                selectedRow = row;
            }
            if(row.classList.contains('correct')){
                correctRow = row;
            }
        });

        return { rows: rows, selectedRow: selectedRow, correctRow: correctRow };
    }

    /* ══════════════════════════════════════════════════════════════════
       PROCESS EACH QUESTION BODY
    ══════════════════════════════════════════════════════════════════ */
 /* ══════════════════════════════════════════════════════════════════
   SHARED HELPER
══════════════════════════════════════════════════════════════════ */
function getAnswerInfo(answerDiv) {
    var rows = answerDiv.querySelectorAll('.r0, .r1');
    var selectedRow = null;
    var correctRow  = null;

    rows.forEach(function(row){
        var radio = row.querySelector('input[type="radio"]');
        if(radio && (radio.checked || radio.getAttribute('checked') !== null)){
            selectedRow = row;
        }
        if(row.classList.contains('correct')){
            correctRow = row;
        }
    });

    return { rows: rows, selectedRow: selectedRow, correctRow: correctRow };
}

/* ══════════════════════════════════════════════════════════════════
   FEEDBACK BAR HELPER
══════════════════════════════════════════════════════════════════ */
function buildFeedbackBar(wasAnswered, selectedRow, correctRow, correctText){
    var fb = document.createElement('div');

    if(!wasAnswered){
        fb.className = 'qr-feedback incorrect';
        fb.innerHTML = '<span class="ms-icon">help</span> <strong>Not answered.</strong>'
                     + (correctText ? ' The correct answer is: <strong>' + correctText + '</strong>' : '');
    } else if(selectedRow && correctRow && selectedRow === correctRow){
        fb.className = 'qr-feedback correct';
        fb.innerHTML = '<span class="ms-icon">check_circle</span> Your answer is correct.';
    } else if(correctRow){
        fb.className = 'qr-feedback incorrect';
        fb.innerHTML = '<span class="ms-icon">cancel</span>'
                     + ' <strong>Incorrect.</strong> The correct answer is: <strong>' + correctText + '</strong>';
    } else {
        fb.className = 'qr-feedback incorrect';
        fb.innerHTML = '<span class="ms-icon">cancel</span> Your answer is incorrect.';
    }

    return fb;
}

/* ══════════════════════════════════════════════════════════════════
   LOOP 1 — MCQ / TRUE-FALSE
   Runs first. Marks body with data-qr-processed so Loop 2 skips it.
══════════════════════════════════════════════════════════════════ */
document.querySelectorAll('.qr-q-body').forEach(function(body){
    var blockCls    = body.getAttribute('data-cls');
    var wasAnswered = (blockCls !== 'unanswered');

    var answerDiv = body.querySelector('.answer');
    if(!answerDiv) return;

    // Only handle if .r0/.r1 rows exist — otherwise it's a DD question, skip to Loop 2
    var info = getAnswerInfo(answerDiv);
    if(info.rows.length === 0) return;

    // Mark as processed — Loop 2 will skip this body
    body.setAttribute('data-qr-processed', '1');

    // Hide Moodle default feedback
    body.querySelectorAll('.outcome, .specificfeedback, .generalfeedback').forEach(function(el){
        el.style.display = 'none';
    });

    // Detect True/False
    var allTexts = Array.from(info.rows).map(function(row){
        var lbl = row.querySelector('[data-region="answer-label"], label');
        return lbl ? lbl.textContent.trim() : '';
    });

    var isTF = (info.rows.length === 2) && allTexts.every(function(t){
        return /^(true|false)$/i.test(t);
    });

    /* ── TRUE / FALSE ── */
    if(isTF){
        answerDiv.style.display = 'none';

        var wrap = document.createElement('div');
        wrap.className = 'tf-wrap';

        info.rows.forEach(function(row, i){
            var btn        = document.createElement('div');
            btn.className  = 'tf-btn';
            var text       = allTexts[i];
            var iconHtml   = '';
            var isSelected = (row === info.selectedRow);
            var isCorrect  = (row === info.correctRow);

            if(wasAnswered && isSelected && isCorrect){
                btn.classList.add('selected-correct');
                iconHtml = '<span class="ms-icon" style="font-size:18px;">check_circle</span>';
            } else if(wasAnswered && isSelected && !isCorrect){
                btn.classList.add('selected-wrong');
                iconHtml = '<span class="ms-icon" style="font-size:18px;">cancel</span>';
            } else if(isCorrect){
                btn.classList.add('correct-answer');
                iconHtml = '<span class="ms-icon" style="font-size:18px;">check_circle</span>';
            }

            btn.innerHTML = text + (iconHtml ? ' ' + iconHtml : '');
            wrap.appendChild(btn);
        });

        var formulation = body.querySelector('.formulation');
        if(formulation) formulation.after(wrap);
        else body.appendChild(wrap);

        var correctLbl = info.correctRow
            ? info.correctRow.querySelector('[data-region="answer-label"], label')
            : null;
        var fb = buildFeedbackBar(
            wasAnswered, info.selectedRow, info.correctRow,
            correctLbl ? correctLbl.textContent.trim() : ''
        );
        wrap.after(fb);

    /* ── MCQ ── */
    } else {
        info.rows.forEach(function(row){
            row.style.cssText = [
                'display:flex',
                'align-items:center',
                'padding:12px 16px',
                'border-radius:50px',
                'border:2px solid #e2e8f0',
                'margin-bottom:8px',
                'font-size:14px',
                'font-weight:500',
                'background:#fff',
                'color:#374151',
                'cursor:default',
                'transition:.15s'
            ].join(';');

            var isSelected = (row === info.selectedRow);
            var isCorrect  = (row === info.correctRow);

            var moodleIcon = row.querySelector('i.icon, .fa');
            if(moodleIcon){
                if(moodleIcon.parentElement && moodleIcon.parentElement.tagName === 'SPAN'){
                    moodleIcon.parentElement.remove();
                } else {
                    moodleIcon.remove();
                }
            }

            var icon = document.createElement('span');
            icon.className = 'ms-icon';
            icon.style.cssText = 'font-size:18px;margin-left:auto;flex-shrink:0;';

            if(wasAnswered && isSelected && isCorrect){
                row.style.borderColor = '#16a34a';
                row.style.background  = '#f0fdf4';
                row.style.color       = '#15803d';
                icon.textContent      = 'check_circle';
                row.appendChild(icon);
            } else if(wasAnswered && isSelected && !isCorrect){
                row.style.borderColor = '#dc2626';
                row.style.background  = '#fef2f2';
                row.style.color       = '#dc2626';
                icon.textContent      = 'cancel';
                row.appendChild(icon);
            } else if(isCorrect){
                row.style.borderColor = '#16a34a';
                row.style.background  = '#f0fdf4';
                row.style.color       = '#15803d';
                icon.textContent      = 'check_circle';
                row.appendChild(icon);
            }
        });

        answerDiv.style.display = '';

        var correctText = info.correctRow
            ? (info.correctRow.querySelector('[data-region="answer-label"], label') || {textContent:''}).textContent.trim()
            : '';
        var fb = buildFeedbackBar(wasAnswered, info.selectedRow, info.correctRow, correctText);
        var formulation = body.querySelector('.formulation');
        if(formulation) formulation.after(fb);
        else answerDiv.after(fb);
    }
});

/* ══════════════════════════════════════════════════════════════════
   LOOP 2 — DRAG AND DROP (ddwtos / ddimageortext / ddmatch)
   Runs after Loop 1. Skips anything already marked processed.
══════════════════════════════════════════════════════════════════ */
document.querySelectorAll('.qr-q-body').forEach(function(body){
    var blockCls = body.getAttribute('data-cls');

    // Skip if MCQ/TF loop already handled this
    if(body.getAttribute('data-qr-processed') === '1') return;

    // Only match genuine DD containers
    var isDDWTOS  = !!body.querySelector('.ddwtos, .fposwrapper');
    var isDDImage = !!body.querySelector('.ddimageortext, .dropbackground, .ddarea');
    var isDDMatch = !!body.querySelector('.ddintoimage');

    if(!isDDWTOS && !isDDImage && !isDDMatch) return;

    body.setAttribute('data-qr-processed', '1');

    var wasAnswered = (blockCls !== 'unanswered');

    body.querySelectorAll('.outcome, .specificfeedback, .generalfeedback').forEach(function(el){
        el.style.display = 'none';
    });

    var fb = document.createElement('div');
    fb.className = 'qr-feedback';

    if(!wasAnswered){
        fb.classList.add('incorrect');
        fb.innerHTML = '<span class="ms-icon">help</span>'
                     + ' <strong>Not answered.</strong> This question was not attempted.';
    } else if(blockCls === 'correct'){
        fb.classList.add('correct');
        fb.innerHTML = '<span class="ms-icon">check_circle</span>'
                     + ' Your answer is correct.';
    } else if(blockCls === 'partial'){
        fb.classList.add('partial');
        fb.style.background = '#fef9c3';
        fb.style.color      = '#a16207';
        fb.innerHTML = '<span class="ms-icon">adjust</span>'
                     + ' <strong>Partially correct.</strong>'
                     + ' Some placements were wrong — review the highlighted answers above.';
    } else {
        fb.classList.add('incorrect');
        fb.innerHTML = '<span class="ms-icon">cancel</span>'
                     + ' <strong>Incorrect.</strong>'
                     + ' Review the correct answer shown above.';
    }

    var insertAfter = body.querySelector(
        '.ddwtos, .fposwrapper, .ddimageortext, .dropbackground, .ddarea, .ddintoimage, .formulation'
    );
    if(insertAfter) insertAfter.after(fb);
    else body.appendChild(fb);
});

    /* ── Build feedback bar (shared between TF and MCQ) ─────────────── */
    function buildFeedbackBar(wasAnswered, selectedRow, correctRow, correctText){
        var fb = document.createElement('div');

        if(!wasAnswered){
            fb.className = 'qr-feedback incorrect';
            fb.innerHTML = '<span class="ms-icon">help</span> <strong>Not answered.</strong>'
                         + (correctText
                            ? ' The correct answer is: <strong>' + correctText + '</strong>'
                            : '');

        } else if(selectedRow && correctRow && selectedRow === correctRow){
            fb.className = 'qr-feedback correct';
            fb.innerHTML = '<span class="ms-icon">check_circle</span> Your answer is correct.';

        } else if(correctRow){
            fb.className = 'qr-feedback incorrect';
            fb.innerHTML = '<span class="ms-icon">cancel</span>'
                         + ' <strong>Incorrect.</strong> The correct answer is: <strong>'
                         + correctText + '</strong>';
        } else {
            fb.className = 'qr-feedback incorrect';
            fb.innerHTML = '<span class="ms-icon">cancel</span> Your answer is incorrect.';
        }

        return fb;
    }

});
</script>
JS;
 
    $output .= $this->footer();
    return $output;
}

    /**
     * Renders the review question pop-up.
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param int $slot which question to display.
     * @param int $seq which step of the question attempt to show. null = latest.
     * @param display_options $displayoptions instance of display_options.
     * @param attempt_summary_information|array $summarydata summary information about the attempt.
     *      Passing an array is deprecated.
     * @return string HTML to display.
     */
    public function review_question_page(quiz_attempt $attemptobj, $slot, $seq,
            display_options $displayoptions, $summarydata) {
        if (is_array($summarydata)) {
            debugging('Since Moodle 4.4, $summarydata passed to review_question_page should be a attempt_summary_information.',
                DEBUG_DEVELOPER);
            $summarydata = attempt_summary_information::create_from_legacy_array($summarydata);
        }

        $output = '';
        $output .= $this->header();
        $output .= html_writer::div($this->render($summarydata), 'mb-3');

        if (!is_null($seq)) {
            $output .= $attemptobj->render_question_at_step($slot, $seq, true, $this);
        } else {
            $output .= $attemptobj->render_question($slot, true, $this);
        }

        $output .= $this->close_window_button();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Renders the review question pop-up.
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param string $message Why the review is not allowed.
     * @return string html to output.
     */
    public function review_question_not_allowed(quiz_attempt $attemptobj, $message) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(format_string($attemptobj->get_quiz_name(), true,
                ["context" => $attemptobj->get_quizobj()->get_context()]));
        $output .= $this->notification($message);
        $output .= $this->close_window_button();
        $output .= $this->footer();
        return $output;
    }

    /**
     * A chance to filter the information before display.
     *
     * Moodle core uses this to display less infomrmation on pages after the first.
     * This is a separate method, becaus it is a useful hook where themes can overrid things.
     *
     * @param attempt_summary_information $summarydata the data that will be displayed. Modify as desired.
     * @param int $page contains the current page number
     */
    public function filter_review_attempt_summary(
        attempt_summary_information $summarydata,
        int $page
    ): void {
        if ($page > 0) {
            $summarydata->filter_keeping_only(['user', 'attemptlist']);
        }
    }

    /**
     * Outputs the overall summary of the attempt at the top of the review page.
     *
     * @param attempt_summary_information $summarydata contains row data for table.
     * @param int $page contains the current page number
     * @return string HTML to display.
     */
    public function review_attempt_summary(
        attempt_summary_information $summarydata,
        int $page
    ): string {
        $this->filter_review_attempt_summary($summarydata, $page);
        return html_writer::div($this->render($summarydata), 'mb-3');
    }

    /**
     * Filters the summarydata array.
     *
     * @param array $summarydata contains row data for table
     * @param int $page the current page number
     * @return array updated version of the $summarydata array.
     * @deprecated since Moodle 4.4. Replaced by filter_review_attempt_summary.
     */
    protected function filter_review_summary_table($summarydata, $page) {
        debugging('filter_review_summary_table() is deprecated. Replaced by filter_review_attempt_summary().', DEBUG_DEVELOPER);
        if ($page == 0) {
            return $summarydata;
        }

        // Only show some of summary table on subsequent pages.
        foreach ($summarydata as $key => $rowdata) {
            if (!in_array($key, ['user', 'attemptlist'])) {
                unset($summarydata[$key]);
            }
        }

        return $summarydata;
    }

    /**
     * Outputs the table containing data from summary data array
     *
     * @param array $summarydata contains row data for table
     * @param int $page contains the current page number
     * @return string HTML to display.
     * @deprecated since Moodle 4.4. Replaced by review_attempt_summary.
     */
    public function review_summary_table($summarydata, $page) {
        debugging('review_summary_table() is deprecated. Please use review_attempt_summary() instead.', DEBUG_DEVELOPER);
        $summarydata = $this->filter_review_summary_table($summarydata, $page);
        $this->render(attempt_summary_information::create_from_legacy_array($summarydata));
    }

    /**
     * Renders each question
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param bool $reviewing
     * @param array $slots array of integers relating to questions
     * @param int $page current page number
     * @param bool $showall if true shows attempt on single page
     * @param display_options $displayoptions instance of display_options
     */
    public function questions(quiz_attempt $attemptobj, $reviewing, $slots, $page, $showall,
            display_options $displayoptions) {
        $output = '';
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, $reviewing, $this,
                    $attemptobj->review_url($slot, $page, $showall));
        }
        return $output;
    }

    /**
     * Renders the main bit of the review page.
     *
     * @param int $page current page number
     * @param bool $showall if true display attempt on one page
     * @param display_options $displayoptions instance of display_options
     * @param string $content the rendered display of each question
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @return string HTML to display.
     */
    public function review_form($page, $showall, $displayoptions, $content, $attemptobj) {
        if ($displayoptions->flags != question_display_options::EDITABLE) {
            return $content;
        }

        $this->page->requires->js_init_call('M.mod_quiz.init_review_form', null, false,
                quiz_get_js_module());

        $output = '';
        $output .= html_writer::start_tag('form', ['action' => $attemptobj->review_url(null,
                $page, $showall), 'method' => 'post', 'class' => 'questionflagsaveform']);
        $output .= html_writer::start_tag('div');
        $output .= $content;
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
                'value' => sesskey()]);
        $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
        $output .= html_writer::empty_tag('input', ['type' => 'submit',
                'class' => 'questionflagsavebutton btn btn-secondary', 'name' => 'savingflags',
                'value' => get_string('saveflags', 'question')]);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Returns either a link or button.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     */
    public function finish_review_link(quiz_attempt $attemptobj) {
        $url = $attemptobj->view_url();

        if ($attemptobj->get_access_manager(time())->attempt_must_be_in_popup()) {
            $this->page->requires->js_init_call('M.mod_quiz.secure_window.init_close_button',
                    [$url->out(false)], false, quiz_get_js_module());
            return html_writer::empty_tag('input', ['type' => 'button',
                    'value' => get_string('finishreview', 'quiz'),
                    'id' => 'secureclosebutton',
                    'class' => 'mod_quiz-next-nav btn btn-primary']);

        } else {
            return html_writer::link($url, get_string('finishreview', 'quiz'),
                    ['class' => 'mod_quiz-next-nav']);
        }
    }

    /**
     * Creates the navigation links/buttons at the bottom of the review attempt page.
     *
     * Note, the name of this function is no longer accurate, but when the design
     * changed, it was decided to keep the old name for backwards compatibility.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param int $page the current page
     * @param bool $lastpage if true current page is the last page
     * @param bool|null $showall if true, the URL will be to review the entire attempt on one page,
     *      and $page will be ignored. If null, a sensible default will be chosen.
     *
     * @return string HTML fragment.
     */
    public function review_next_navigation(quiz_attempt $attemptobj, $page, $lastpage, $showall = null) {
        $nav = '';
        if ($page > 0) {
            $nav .= link_arrow_left(get_string('navigateprevious', 'quiz'),
                    $attemptobj->review_url(null, $page - 1, $showall), false, 'mod_quiz-prev-nav');
        }
        if ($lastpage) {
            $nav .= $this->finish_review_link($attemptobj);
        } else {
            $nav .= link_arrow_right(get_string('navigatenext', 'quiz'),
                    $attemptobj->review_url(null, $page + 1, $showall), false, 'mod_quiz-next-nav');
        }
        return html_writer::tag('div', $nav, ['class' => 'submitbtns']);
    }

    /**
     * Return the HTML of the quiz timer.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param int $timenow timestamp to use as 'now'.
     * @return string HTML content.
     */
    public function countdown_timer(quiz_attempt $attemptobj, $timenow) {

        $timeleft = $attemptobj->get_time_left_display($timenow);
        if ($timeleft !== false) {
            $ispreview = $attemptobj->is_preview();
            $timerstartvalue = $timeleft;
            if (!$ispreview) {
                // Make sure the timer starts just above zero. If $timeleft was <= 0, then
                // this will just have the effect of causing the quiz to be submitted immediately.
                $timerstartvalue = max($timerstartvalue, 1);
            }
            $this->initialise_timer($timerstartvalue, $ispreview);
        }

        return $this->output->render_from_template('mod_quiz/timer', (object) []);
    }

    /**
     * Create a preview link
     *
     * @param moodle_url $url URL to restart the attempt.
     */
    public function restart_preview_button($url) {
        return $this->single_button($url, get_string('startnewpreview', 'quiz'));
    }

    /**
     * Outputs the navigation block panel
     *
     * @param navigation_panel_base $panel
     */
public function navigation_panel(navigation_panel_base $panel) {
    global $PAGE;

    // Icons
    $PAGE->requires->css(new moodle_url(
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@400;600&display=swap'
    ));

    // REQUIRED quiz JS
    $this->page->requires->js_init_call(
        'M.mod_quiz.nav.init',
        null,
        false,
        quiz_get_js_module()
    );

    // Card wrapper
    $output = html_writer::start_div(
        'w-full bg-white shadow-md rounded-2xl p-3 mt-4 quiz-nav-card border border-gray-100'
    );

    // 🔑 MUST KEEP (hidden autosave + form metadata)
    $output .= html_writer::div(
        $panel->render_before_button_bits($this),
        'othernav'
    );

    // Title
    $output .= html_writer::tag(
        'div',
        '<span class="font-semibold text-lg text-gray-800">Quiz Navigation</span>',
        ['class' => 'mb-4']
    );

    // Question buttons grid
    $output .= html_writer::start_div('flex gap-1 mb-4 flex-wrap justify-content-center');

    $buttons = $panel->get_question_buttons();
    foreach ($buttons as $button) {
        $output .= $this->render($button);
    }

    $output .= html_writer::end_div();

    // Stats
    $answered = $unanswered = $flagged = 0;
    foreach ($buttons as $b) {
        if (!empty($b->flagged)) {
            $flagged++;
        }
        if (in_array($b->stateclass, ['answersaved','complete','correct','incorrect','partial'])) {
            $answered++;
        } else {
            $unanswered++;
        }
    }

    $output .= "
        <div class='space-y-2 text-sm mt-3 text-gray-700'>
            <div class='flex items-center gap-2'>
                <span class='w-3 h-3 rounded-sm bg-green-500 inline-block'></span>
                <span>Answered: {$answered}</span>
            </div>
            <div class='flex items-center gap-2'>
                <span class='w-3 h-3 rounded-sm bg-gray-300 inline-block'></span>
                <span>Unanswered: {$unanswered}</span>
            </div>
            <div class='flex items-center gap-2'>
                <i class='fas fa-flag text-orange-500'></i>
                <span>Flagged: {$flagged}</span>
            </div>
        </div>
    ";

    /* ==============================
       Finish Attempt — SAME UI
       BUT SAFE
    =============================== */

$finishhtml = $panel->render_end_bits($this);

// Replace the class of finish link safely
$finishhtml = str_replace(
    'endtestlink aalink',
    'endtestlink aalink px-4 py-2 bg-[#003152] text-white rounded-full font-semibold flex items-center justify-center gap-2 hover:brightness-110 transition-all',
    $finishhtml
);

// Add icon before text
$finishhtml = str_replace(
    'Finish attempt',
    '<span class="material-symbols-rounded mr-1">check_circle</span>Finish attempt',
    $finishhtml
);
$finishhtml = preg_replace(
    '/<div class="singlebutton">.*?<\/div>/s',
    '',
    $finishhtml
);
$output .= html_writer::div($finishhtml, 'mt-5 custom-finish-wrapper');



    // Hide original markup but keep it in DOM (autosave safety)
    $output .= html_writer::div(
        $finishhtml,
        'hidden'
    );

    // Show your clean button
    $output .= html_writer::div($finishbutton, 'mt-5');

    $output .= html_writer::end_div();

    return $output;
}

/* --------------------------
   INDIVIDUAL QUESTION BUTTON (Number Palette)
-----------------------------*/
protected function render_navigation_question_button(navigation_question_button $button) {

    $bgcolor = '#e5e7eb'; // gray-200
    $textcolor = '#374151'; // gray-700
    $border = '';

    if (isset($button->stateclass) &&
        in_array($button->stateclass, ['answersaved', 'complete', 'correct', 'incorrect', 'partial'])) {
        $bgcolor = '#22c55e'; // green-500
        $textcolor = '#ffffff';
    }

    if ($button->flagged) {
        $bgcolor = '#f97316'; // orange-500
        $textcolor = '#ffffff';
    }

    if ($button->currentpage) {
        $bgcolor = '#003152';
        $textcolor = '#ffffff';
        $border = 'border: 3px double #003152;';
    }

    // REQUIRED Moodle classes (JS depends on these)
    $classes = implode(' ', [
        'qnbutton',
        $button->stateclass,
        $button->navmethod,
        'btn',
        'thispageholder-fix'
    ]);

    // 🔥 FORCE UI INLINE (beats Moodle CSS)
    $style = "
        width:40px !important;
        height:40px !important;
        border-radius:12px !important;
        background:$bgcolor !important;
        color:$textcolor !important;
        display:flex !important;
        align-items:center !important;
        justify-content:center !important;
        font-weight:600 !important;
        box-shadow:0 1px 3px rgba(0,0,0,.15) !important;
        transition:transform .15s ease !important;
        $border
    ";

    $content = html_writer::tag('span', s($button->number));

    $attrs = [
        'id' => $button->id, // 🔴 must keep
        'class' => $classes,
        'data-quiz-page' => $button->page,
        'title' => $button->statestring,
        'style' => $style,
        'onmouseover' => "this.style.transform='scale(1.08)'",
        'onmouseout'  => "this.style.transform='scale(1)'"
    ];

    if ($button->url) {
        return html_writer::link($button->url, $content, $attrs);
    }

    return html_writer::tag('span', $content, $attrs);
}


    /**
     * Display a quiz navigation heading.
     *
     * @param navigation_section_heading $heading the heading.
     * @return string HTML fragment.
     */
    protected function render_navigation_section_heading(navigation_section_heading $heading) {
        if (empty($heading->heading)) {
            $headingtext = get_string('sectionnoname', 'quiz');
            $class = ' dimmed_text';
        } else {
            $headingtext = $heading->heading;
            $class = '';
        }
        return $this->heading($headingtext, 3, 'mod_quiz-section-heading' . $class);
    }

    /**
     * Renders a list of links the other attempts.
     *
     * @param links_to_other_attempts $links
     * @return string HTML fragment.
     */
    protected function render_links_to_other_attempts(
            links_to_other_attempts $links) {
        $attemptlinks = [];
        foreach ($links->links as $attempt => $url) {
            if (!$url) {
                $attemptlinks[] = html_writer::tag('strong', $attempt);
            } else {
                if ($url instanceof renderable) {
                    $attemptlinks[] = $this->render($url);
                } else {
                    $attemptlinks[] = html_writer::link($url, $attempt);
                }
            }
        }
        return implode(', ', $attemptlinks);
    }

    /**
     * Render a {@see grade_out_of}.
     *
     * Most of the logic is in methods of the grade_out_of class. However,
     * having this renderer method allows themes to override the default rendering.
     *
     * @param grade_out_of $grade
     * @return string HTML to output.
     */
    protected function render_grade_out_of(grade_out_of $grade): string {
        return get_string($grade->get_string_key(), 'quiz',
                $grade->style_formatted_values($grade->get_formatted_values()));
    }

    /**
     * Render the 'start attempt' page.
     *
     * The student gets here if their interaction with the preflight check
     * from fails in some way (e.g. they typed the wrong password).
     *
     * @param \mod_quiz\quiz_settings $quizobj
     * @param preflight_check_form $mform
     * @return string
     */
    public function start_attempt_page(quiz_settings $quizobj, preflight_check_form $mform) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($quizobj->view_url());
        $output .= $this->heading(format_string($quizobj->get_quiz_name(), true,
                ["context" => $quizobj->get_context()]));
        $output .= $this->quiz_intro($quizobj->get_quiz(), $quizobj->get_cm());
        $output .= $mform->render();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param access_manager $accessmanager Instance of access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     * @return string HTML to output.
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id,
            $nextpage) {
        $output = '';
        $output .= $this->header();
        // added by omkar
        $output .= $this->during_attempt_progress($attemptobj, $page);
        // $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->quiz_notices($messages);
        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Render the tertiary navigation for pages during the attempt.
     *
     * @param string|moodle_url $quizviewurl url of the view.php page for this quiz.
     * @return string HTML to output.
     */
    public function during_attempt_tertiary_nav($quizviewurl): string {
        $output = '';
        $output .= html_writer::start_div('tertiary-navigation');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('navitem');
        $output .= html_writer::link($quizviewurl, get_string('back'),
                ['class' => 'btn btn-secondary']);
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        return $output;
    }
public function during_attempt_progress($attemptobj, $page): string {
    global $DB;

    $output = '';

    // All question slots
    $slots = $attemptobj->get_slots();
    $total = count($slots);

    // Count answered questions
    $answered = 0;
    foreach ($slots as $slot) {
        $state = $attemptobj->get_question_state($slot);
       $stateclass = $state->get_state_class(true);

   if (in_array($stateclass, [
    'answersaved', 'complete', 'correct', 'incorrect', 'partial'
    ])) {
    $answered++;
   }

    }

    // Calculate percentage
    $percent = $total > 0 ? round(($answered / $total) * 100) : 0;

    // Get quiz name
    $quiz = $DB->get_record('quiz', ['id' => $attemptobj->get_quizid()], 'id,name');
    $quizname = $quiz ? $quiz->name : 'Quiz';

    // Current question number
    $curr = $page + 1;

    // ---------------- WRAPPER ----------------
    $output .= html_writer::start_div('quiz-progress-b', [
        'style' => 'padding:12px 20px;margin-bottom:15px;'
    ]);

    // ------------- TOP ROW -------------
    $output .= html_writer::start_div('quiz-progress-row', [
        'style' => 'display:flex;align-items:center;gap:15px;margin-bottom:10px;'
    ]);

    // Back button
    $output .= html_writer::start_div('btn p-2 btn-secondary', [
        'style' => 'display:flex;align-items:center;gap:6px;'
    ]);

    $output .= html_writer::link(
        $attemptobj->view_url(),
        html_writer::tag('span', 'undo', [
            'class' => 'material-symbols-outlined',
            'style' => 'font-size:20px;color:#374151;'
        ]) . ' ' . get_string('back'),
        [
            'style' => 'display:flex;align-items:center;font-size:14px;color:#374151;text-decoration:none;',
            'class' => 'hover:underline'
        ]
    );

    $output .= html_writer::end_div();

    // Middle: Quiz name & question number
    $output .= html_writer::tag(
        'div',
        "<strong style='font-size: 20px;'>$quizname</strong>
         <strong class='text-muted'> Question $curr</strong> of 
         <strong class='text-muted'>$total</strong>",
        ['style' => 'font-size:14px;font-weight:500;color:#111827;']
    );

    $output .= html_writer::end_div(); // end top row

    // ------------- PROGRESS BAR -------------
    $output .= html_writer::start_div('progress-inner', [
        'style' => 'display:flex;align-items: center; margin-top:5px;'
    ]);

    // Outer bar
    $output .= html_writer::start_div('progress-outer', [
        'style' =>
            'width:100%;height:10px;background:#e5e7eb;
             border-radius:50px;overflow:hidden;'
    ]);

    // Inner bar width = ANSWERED %
    $output .= html_writer::tag('div', '', [
        'style' =>
            "height:100%;width:{$percent}%;
             background:#3b82f6;transition:width .4s ease;
             border-radius:50px;"
    ]);

    $output .= html_writer::end_div(); // progress outer

    // Percent Right
    $output .= html_writer::tag('span', "$percent", [
        'style' => 'font-size:14px;font-weight:600;color:#2563eb;margin-left:8px;'
    ]);

    $output .= html_writer::tag('span', 'percent', [
        'class' => 'material-symbols-outlined',
        'style' => 'font-size:18px;color:#2563eb;'
    ]);

    $output .= html_writer::end_div(); // progress inner

    $output .= html_writer::end_div(); // wrapper

    return $output;
}

    /**
     * Returns any notices.
     *
     * @param array $messages
     */
    public function quiz_notices($messages) {
        if (!$messages) {
            return '';
        }
        return $this->notification(
                html_writer::tag('p', get_string('accessnoticesheader', 'quiz')) . $this->access_messages($messages),
                'warning',
                false
        );
    }

    /**
     * Outputs the form for making an attempt
     *
     * @param quiz_attempt $attemptobj
     * @param int $page Current page number
     * @param array $slots Array of integers relating to questions
     * @param int $id ID of the attempt
     * @param int $nextpage Next page number
     */
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        $output = '';

        // Start the form.
        $output .= html_writer::start_tag('form',
                ['action' => new moodle_url($attemptobj->processattempt_url(),
                        ['cmid' => $attemptobj->get_cmid()]), 'method' => 'post',
                        'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                        'id' => 'responseform']);
        $output .= html_writer::start_tag('div');

        // Print all the questions.
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, false, $this,
                    $attemptobj->attempt_url($slot, $page));
        }

        $navmethod = $attemptobj->get_quiz()->navmethod;
        $output .= $this->attempt_navigation_buttons($page, $attemptobj->is_last_page($page), $navmethod);

        // Some hidden fields to track what is going on.
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
                'value' => $attemptobj->get_attemptid()]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
                'value' => $page, 'id' => 'followingpage']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'nextpage',
                'value' => $nextpage]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
                'value' => '0', 'id' => 'timeup']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
                'value' => sesskey()]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
                'value' => '', 'id' => 'mdlscrollto']);

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
                'value' => implode(',', $attemptobj->get_active_slots($page))]);

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
    }

    /**
     * Display the prev/next buttons that go at the bottom of each page of the attempt.
     *
     * @param int $page the page number. Starts at 0 for the first page.
     * @param bool $lastpage is this the last page in the quiz?
     * @param string $navmethod Optional quiz attribute, 'free' (default) or 'sequential'
     * @return string HTML fragment.
     */
    protected function attempt_navigation_buttons($page, $lastpage, $navmethod = 'free') {
        $output = '';

        $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
        if ($page > 0 && $navmethod == 'free') {
            $output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'previous',
                    'value' => get_string('navigateprevious', 'quiz'), 'class' => 'mod_quiz-prev-nav btn btn-secondary',
                    'id' => 'mod_quiz-prev-nav']);
            $this->page->requires->js_call_amd('core_form/submit', 'init', ['mod_quiz-prev-nav']);
        }
        if ($lastpage) {
            $nextlabel = get_string('endtest', 'quiz');
        } else {
            $nextlabel = get_string('navigatenext', 'quiz');
        }
        $output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'next',
                'value' => $nextlabel, 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'mod_quiz-next-nav']);
        $output .= html_writer::end_tag('div');
        $this->page->requires->js_call_amd('core_form/submit', 'init', ['mod_quiz-next-nav']);

        return $output;
    }

    /**
     * Render a button which allows students to redo a question in the attempt.
     *
     * @param int $slot the number of the slot to generate the button for.
     * @param bool $disabled if true, output the button disabled.
     * @return string HTML fragment.
     */
    public function redo_question_button($slot, $disabled) {
        $attributes = ['type' => 'submit', 'name' => 'redoslot' . $slot,
                'value' => get_string('redoquestion', 'quiz'),
                'class' => 'mod_quiz-redo_question_button btn btn-secondary',
                'id' => 'redoslot' . $slot . '-submit',
                'data-savescrollposition' => 'true',
            ];
        if ($disabled) {
            $attributes['disabled'] = 'disabled';
        } else {
            $this->page->requires->js_call_amd('core_question/question_engine', 'initSubmitButton', [$attributes['id']]);
        }
        return html_writer::div(html_writer::empty_tag('input', $attributes));
    }

    /**
     * Initialise the JavaScript required to initialise the countdown timer.
     *
     * @param int $timerstartvalue time remaining, in seconds.
     * @param bool $ispreview true if this is a preview attempt.
     */
    public function initialise_timer($timerstartvalue, $ispreview) {
        $options = [$timerstartvalue, (bool) $ispreview];
        $this->page->requires->js_init_call('M.mod_quiz.timer.init', $options, false, quiz_get_js_module());
    }

    /**
     * Output a page with an optional message, and JavaScript code to close the
     * current window and redirect the parent window to a new URL.
     *
     * @param moodle_url $url the URL to redirect the parent window to.
     * @param string $message message to display before closing the window. (optional)
     * @return string HTML to output.
     */
    public function close_attempt_popup($url, $message = '') {
        $output = '';
        $output .= $this->header();
        $output .= $this->box_start();

        if ($message) {
            $output .= html_writer::tag('p', $message);
            $output .= html_writer::tag('p', get_string('windowclosing', 'quiz'));
            $delay = 5;
        } else {
            $output .= html_writer::tag('p', get_string('pleaseclose', 'quiz'));
            $delay = 0;
        }
        $this->page->requires->js_init_call('M.mod_quiz.secure_window.close',
            [$url->out(false), $delay], false, quiz_get_js_module());

        $output .= $this->box_end();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Print each message in an array, surrounded by &lt;p>, &lt;/p> tags.
     *
     * @param array $messages the array of message strings.
     * @return string HTML to output.
     */
    public function access_messages($messages) {
        $output = '';
        foreach ($messages as $message) {
            $output .= html_writer::tag('p', $message, ['class' => 'text-start']);
        }
        return $output;
    }

    /*
     * Summary Page
     */
    /**
     * Create the summary page
     *
     * @param quiz_attempt $attemptobj
     * @param display_options $displayoptions
     */
    // public function summary_page($attemptobj, $displayoptions) {
    //     $output = '';
    //     $output .= $this->header();
    //     $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
    //     $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
    //     $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
    //     $output .= $this->summary_table($attemptobj, $displayoptions);
    //     $output .= $this->summary_page_controls($attemptobj);
    //     $output .= $this->footer();
    //     return $output;
    // }
public function summary_page($attemptobj, $displayoptions) {
    global $DB;

    // Prepare summary table HTML (reuse existing logic)
    $summarytable = $this->summary_table($attemptobj, $displayoptions);

    // Controls (submit + return)
    $returnurl = $attemptobj->attempt_url(null, $attemptobj->get_currentpage());

$returnbtn = '';
if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
    $returnbtn = html_writer::link(
        $attemptobj->attempt_url(null, $attemptobj->get_currentpage()),
        'Return to Attempt',
        ['class' => 'btn-return']
    );
}

// Submit button (IMPORTANT: must POST)
$options = [
    'attempt' => $attemptobj->get_attemptid(),
    'finishattempt' => 1,
    'timeup' => 0,
    'slots' => '',
    'cmid' => $attemptobj->get_cmid(),
    'sesskey' => sesskey(),
];

$submitbutton = new single_button(
    new moodle_url($attemptobj->processattempt_url(), $options),
    'Submit All and Finish'
);

$submitbutton->class = 'btn-submit';
$submitbutton->type = \single_button::BUTTON_PRIMARY;

$submitbtn = $this->render($submitbutton);

    // Quiz info
    $quizname = format_string($attemptobj->get_quiz_name());
   $attempt = $attemptobj->get_attempt();

// Pre-calculate result (before submission)
$marks = $attempt->sumgrades ?? 0;
$totalmarks = $attemptobj->get_quiz()->sumgrades ?? 0;

$score = $totalmarks > 0 ? round(($marks / $totalmarks) * 100, 2) : 0;
if (!empty($attempt->timefinish)) {
    $duration = $attempt->timefinish - $attempt->timestart;
} else {
    $duration = time() - $attempt->timestart;
}
$reviewurl = (new moodle_url('/mod/quiz/review.php', [
    'attempt' => $attemptobj->get_attemptid(),
    'cmid' => $attemptobj->get_cmid()
]))->out(false);

$reattemptbtn = $this->render(new single_button(
    $attemptobj->start_attempt_url(),
    'Re-attempt Quiz'
));

$timetaken = format_time($duration);
    $totalquestions = count($attemptobj->get_slots());

    // Calculate answered
    $answered = 0;
    $questions = [];
$unanswered = 0;
$flagged = 0;

foreach ($attemptobj->get_slots() as $slot) {

    if (!$attemptobj->is_real_question($slot)) {
        continue;
    }

    $state = $attemptobj->get_question_state($slot);
    $stateclass = $state->get_state_class(true);

    $status = $attemptobj->get_question_status($slot, $displayoptions->correctness);

    // Count stats
    if (in_array($stateclass, ['answersaved','complete','correct','incorrect','partial'])) {
        $answered++;
    } else {
        $unanswered++;
    }

    if ($attemptobj->is_question_flagged($slot)) {
        $flagged++;
    }

    $questions[] = [
        'slot' => $slot,
        'number' => $attemptobj->get_question_number($slot),
        'status' => $status,
        'url' => $attemptobj->attempt_url($slot)->out(false)
    ];
}

    $percent = $totalquestions > 0 ? round(($answered / $totalquestions) * 100) : 0;

    $data = [
    'quizname' => $quizname,
    'summarytitle' => get_string('summaryofattempt', 'quiz'),
    'timetaken' => $timetaken,

    'answered' => $answered,
    'unanswered' => $unanswered,
    'flagged' => $flagged,

    'questions' => $questions,
     'returnbtn' => $returnbtn,
    'submitbtn' => $submitbtn,
     'score' => $score,
    'marks' => $marks,
    'totalmarks' => $totalmarks,
    'timetaken' => $timetaken,
    'reviewurl' => $reviewurl,
    'reattemptbtn' => $reattemptbtn
];

    $output = '';
    $output .= $this->header();
    $output .= $this->render_from_template('theme_remui/quiz_summary', $data);
    $output .= $this->footer();

    return $output;
}
    /**
     * Generates the table of summarydata
     *
     * @param quiz_attempt $attemptobj
     * @param display_options $displayoptions
     */
    public function summary_table($attemptobj, $displayoptions) {
        // Prepare the summary table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head = [get_string('question', 'quiz'), get_string('status', 'quiz')];
        $table->align = ['left', 'left'];
        $table->size = ['', ''];
        $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
        if ($markscolumn) {
            $table->head[] = get_string('marks', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }
        $tablewidth = count($table->align);
        $table->data = [];

        // Get the summary info for each question.
        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            // Add a section headings if we need one here.
            $heading = $attemptobj->get_heading_before_slot($slot);
            if ($heading !== null) {
                // There is a heading here.
                $rowclasses = 'quizsummaryheading';
                if ($heading) {
                    $heading = format_string($heading);
                } else {
                    if (count($attemptobj->get_quizobj()->get_sections()) > 1) {
                        // If this is the start of an unnamed section, and the quiz has more
                        // than one section, then add a default heading.
                        $heading = get_string('sectionnoname', 'quiz');
                        $rowclasses .= ' dimmed_text';
                    }
                }
                $cell = new html_table_cell(format_string($heading));
                $cell->header = true;
                $cell->colspan = $tablewidth;
                $table->data[] = [$cell];
                $table->rowclasses[] = $rowclasses;
            }

            // Don't display information items.
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            // Real question, show it.
            $flag = '';
            if ($attemptobj->is_question_flagged($slot)) {
                // Quiz has custom JS manipulating these image tags - so we can't use the pix_icon method here.
                $flag = html_writer::empty_tag('img', ['src' => $this->image_url('i/flagged'),
                    'alt' => get_string('flagged', 'question'), 'class' => 'questionflag icon ms-2']);
            }
            if ($attemptobj->can_navigate_to($slot)) {
                $row = [html_writer::link($attemptobj->attempt_url($slot),
                        $attemptobj->get_question_number($slot) . $flag),
                        $attemptobj->get_question_status($slot, $displayoptions->correctness)];
            } else {
                $row = [$attemptobj->get_question_number($slot) . $flag,
                        $attemptobj->get_question_status($slot, $displayoptions->correctness)];
            }
            if ($markscolumn) {
                $row[] = $attemptobj->get_question_mark($slot);
            }
            $table->data[] = $row;
            $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $attemptobj->get_question_state_class(
                            $slot, $displayoptions->correctness);
        }

        // Print the summary table.
        return html_writer::table($table);
    }

    /**
     * Creates any controls the page should have.
     *
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        $output = '';

        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                    new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                    get_string('returnattempt', 'quiz'));
            $output .= $this->container($this->container($this->render($button),
                    'controls'), 'submitbtns mdl-align');
        }

        // Finish attempt button.
        $options = [
                'attempt' => $attemptobj->get_attemptid(),
                'finishattempt' => 1,
                'timeup' => 0,
                'slots' => '',
                'cmid' => $attemptobj->get_cmid(),
                'sesskey' => sesskey(),
        ];

        $button = new single_button(
                new moodle_url($attemptobj->processattempt_url(), $options),
                get_string('submitallandfinish', 'quiz'));
        $button->class = 'btn-finishattempt';
        $button->formid = 'frm-finishattempt';
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $totalunanswered = 0;
            if ($attemptobj->get_quiz()->navmethod == 'free') {
                // Only count the unanswered question if the navigation method is set to free.
                $totalunanswered = $attemptobj->get_number_of_unanswered_questions();
            }
            $this->page->requires->js_call_amd('mod_quiz/submission_confirmation', 'init', [$totalunanswered]);
        }
        $button->type = \single_button::BUTTON_PRIMARY;

        $duedate = $attemptobj->get_due_date();
        $message = '';
        if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
            $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));

        } else {
            if ($duedate) {
                $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
            }
        }

        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->container($message . $this->container(
                        $this->render($button), 'controls'), 'submitbtns mdl-align');

        return $output;
    }

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param view_page $viewobj
     * @return string HTML to display
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        $output = '';

        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->render($viewobj->attemptslist);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }

    /**
     * Render the tertiary navigation for the view page.
     *
     * @param view_page $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_tertiary_nav(view_page $viewobj): string {
        $content = '';

        if ($viewobj->buttontext) {
            $attemptbtn = $this->start_attempt_button($viewobj->buttontext,
                    $viewobj->startattempturl, $viewobj->preflightcheckform,
                    $viewobj->popuprequired, $viewobj->popupoptions);
            $content .= html_writer::div($attemptbtn, 'navitem');
        }

        if ($viewobj->canedit && !$viewobj->quizhasquestions) {
            $addquestionbutton = html_writer::link($viewobj->editurl, get_string('addquestion', 'quiz'),
                    ['class' => 'btn btn-secondary']);
            $content .= html_writer::div($addquestionbutton, 'navitem');
        }

        if ($content) {
            return html_writer::div(html_writer::div($content, 'd-flex'), 'tertiary-navigation');
        } else {
            return '';
        }
    }

    /**
     * Work out, and render, whatever buttons, and surrounding info, should appear
     * at the end of the review page.
     *
     * @param view_page $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_buttons(view_page $viewobj) {
        $output = '';

        if (!$viewobj->quizhasquestions) {
            $output .= html_writer::div(
                    $this->notification(get_string('noquestions', 'quiz'), 'warning', false),
                    'text-start mb-3');
        }
        $output .= $this->access_messages($viewobj->preventmessages);

        if ($viewobj->showbacktocourse) {
            $output .= $this->single_button($viewobj->backtocourseurl,
                    get_string('backtocourse', 'quiz'), 'get',
                    ['class' => 'continuebutton']);
        }

        return $output;
    }

    /**
     * Generates the view attempt button
     *
     * @param string $buttontext the label to display on the button.
     * @param moodle_url $url The URL to POST to in order to start the attempt.
     * @param preflight_check_form|null $preflightcheckform deprecated.
     * @param bool $popuprequired whether the attempt needs to be opened in a pop-up.
     * @param array $popupoptions the options to use if we are opening a popup.
     * @return string HTML fragment.
     */
    public function start_attempt_button($buttontext, moodle_url $url,
            ?preflight_check_form $preflightcheckform = null,
            $popuprequired = false, $popupoptions = null) {

        $button = new single_button($url, $buttontext, 'post', single_button::BUTTON_PRIMARY);
        $button->class .= ' quizstartbuttondiv';
        if ($popuprequired) {
            $button->class .= ' quizsecuremoderequired';
        }

        $popupjsoptions = null;
        if ($popuprequired && $popupoptions) {
            $action = new popup_action('click', $url, 'popup', $popupoptions);
            $popupjsoptions = $action->get_js_options();
        }

        $this->page->requires->js_call_amd('mod_quiz/preflightcheck', 'init',
                ['.quizstartbuttondiv [type=submit]', get_string('startattempt', 'quiz'),
                        '#mod_quiz_preflight_form', $popupjsoptions]);

        return $this->render($button) . ($preflightcheckform ? $preflightcheckform->render() : '');
    }

    /**
     * Generate a message saying that this quiz has no questions, with a button to
     * go to the edit page, if the user has the right capability.
     *
     * @param bool $canedit can the current user edit the quiz?
     * @param moodle_url $editurl URL of the edit quiz page.
     * @return string HTML to output.
     *
     * @deprecated since Moodle 4.0 MDL-71915 - please do not use this function any more.
     */
    public function no_questions_message($canedit, $editurl) {
        debugging('no_questions_message() is deprecated, please use generate_no_questions_message() instead.', DEBUG_DEVELOPER);

        $output = html_writer::start_tag('div', ['class' => 'card text-center mb-3']);
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);

        $output .= $this->notification(get_string('noquestions', 'quiz'), 'warning', false);
        if ($canedit) {
            $output .= $this->single_button($editurl, get_string('editquiz', 'quiz'), 'get');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Outputs an error message for any guests accessing the quiz
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param array $messages Array containing any messages
     * @param view_page $viewobj
     */
    public function view_page_guest($course, $quiz, $cm, $context, $messages, $viewobj) {
        $output = '';
        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $messages);
        $guestno = html_writer::tag('p', get_string('guestsno', 'quiz'));
        $liketologin = html_writer::tag('p', get_string('liketologin'));
        $referer = get_local_referer(false);
        $output .= $this->confirm($guestno . "\n\n" . $liketologin . "\n", get_login_url(), $referer);
        return $output;
    }

    /**
     * Outputs and error message for anyone who is not enrolled on the course.
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param array $messages Array containing any messages
     * @param view_page $viewobj
     */
    public function view_page_notenrolled($course, $quiz, $cm, $context, $messages, $viewobj) {
        global $CFG;
        $output = '';
        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $messages);
        $youneedtoenrol = html_writer::tag('p', get_string('youneedtoenrol', 'quiz'));
        $button = html_writer::tag('p',
                $this->continue_button($CFG->wwwroot . '/course/view.php?id=' . $course->id));
        $output .= $this->box($youneedtoenrol . "\n\n" . $button . "\n", 'generalbox', 'notice');
        return $output;
    }

    /**
     * Output the page information
     *
     * @param stdClass $quiz the quiz settings.
     * @param cm_info|stdClass $cm the course_module object.
     * @param context $context the quiz context.
     * @param array $messages any access messages that should be described.
     * @param bool $quizhasquestions does quiz has questions added.
     * @return string HTML to output.
     */
    public function view_information($quiz, $cm, $context, $messages, bool $quizhasquestions = false) {
        $output = '';

        // Output any access messages.
        if ($messages) {
            $output .= $this->box($this->access_messages($messages), 'quizinfo');
        }

        // Show number of attempts summary to those who can view reports.
        if (has_capability('mod/quiz:viewreports', $context)) {
            if ($strattemptnum = $this->quiz_attempt_summary_link_to_reports($quiz, $cm,
                    $context)) {
                $output .= html_writer::tag('div', $strattemptnum,
                        ['class' => 'quizattemptcounts']);
            }
        }

        if (has_any_capability(['mod/quiz:manageoverrides', 'mod/quiz:viewoverrides'], $context)) {
            if ($overrideinfo = $this->quiz_override_summary_links($quiz, $cm)) {
                $output .= html_writer::tag('div', $overrideinfo, ['class' => 'quizattemptcounts']);
            }
        }

        return $output;
    }

    /**
     * Output the quiz intro.
     *
     * @param stdClass $quiz the quiz settings.
     * @param stdClass $cm the course_module object.
     * @return string HTML to output.
     */
    public function quiz_intro($quiz, $cm) {
        if (html_is_blank($quiz->intro)) {
            return '';
        }

        return $this->box(format_module_intro('quiz', $quiz, $cm->id), 'generalbox', 'intro');
    }

    /**
     * Generates the table heading.
     */
    public function view_table_heading() {
        return $this->heading(get_string('summaryofattempts', 'quiz'), 3);
    }

    /**
     * Generates the table of data
     *
     * @param stdClass $quiz the quiz settings.
     * @param context_module $context the quiz context.
     * @param view_page $viewobj
     * @deprecated Since 4.4 please use the {@see list_of_attempts} renderable instead.
     */
    public function view_table($quiz, $context, $viewobj) {
        debugging('view_table has been deprecated since 4.4 please use the list_of_attempts renderable instead.');
        if (!$viewobj->attempts) {
            return '';
        }

        // Prepare table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizattemptsummary';
        $table->caption = get_string('summaryofattempts', 'quiz');
        $table->captionhide = true;
        $table->head = [];
        $table->align = [];
        $table->size = [];
        if ($viewobj->attemptcolumn) {
            $table->head[] = get_string('attemptnumber', 'quiz');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        $table->head[] = get_string('attemptstate', 'quiz');
        $table->align[] = 'left';
        $table->size[] = '';
        if ($viewobj->markcolumn) {
            $table->head[] = get_string('marks', 'quiz') . ' / ' .
                    quiz_format_grade($quiz, $quiz->sumgrades);
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->gradecolumn) {
            $table->head[] = get_string('gradenoun') . ' / ' .
                    quiz_format_grade($quiz, $quiz->grade);
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->canreviewmine) {
            $table->head[] = get_string('review', 'quiz');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->feedbackcolumn) {
            $table->head[] = get_string('feedback', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }

        // One row for each attempt.
        foreach ($viewobj->attemptobjs as $attemptobj) {
            $attemptoptions = $attemptobj->get_display_options(true);
            $row = [];

            // Add the attempt number.
            if ($viewobj->attemptcolumn) {
                if ($attemptobj->is_preview()) {
                    $row[] = get_string('preview', 'quiz');
                } else {
                    $row[] = $attemptobj->get_attempt_number();
                }
            }

            $row[] = $this->attempt_state($attemptobj);

            if ($viewobj->markcolumn) {
                if ($attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                        $attemptobj->is_finished()) {
                    $row[] = quiz_format_grade($quiz, $attemptobj->get_sum_marks());
                } else {
                    $row[] = '';
                }
            }

            // Outside the if because we may be showing feedback but not grades.
            $attemptgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $quiz, false);

            if ($viewobj->gradecolumn) {
                if ($attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                        $attemptobj->is_finished()) {

                    // Highlight the highest grade if appropriate.
                    if ($viewobj->overallstats && !$attemptobj->is_preview()
                            && $viewobj->numattempts > 1 && !is_null($viewobj->mygrade)
                            && $attemptobj->get_state() == quiz_attempt::FINISHED
                            && $attemptgrade == $viewobj->mygrade
                            && $quiz->grademethod == QUIZ_GRADEHIGHEST) {
                        $table->rowclasses[$attemptobj->get_attempt_number()] = 'bestrow';
                    }

                    $row[] = quiz_format_grade($quiz, $attemptgrade);
                } else {
                    $row[] = '';
                }
            }

            if ($viewobj->canreviewmine) {
                $row[] = $viewobj->accessmanager->make_review_link($attemptobj->get_attempt(),
                        $attemptoptions, $this);
            }

            if ($viewobj->feedbackcolumn && $attemptobj->is_finished()) {
                if ($attemptoptions->overallfeedback) {
                    $row[] = quiz_feedback_for_grade($attemptgrade, $quiz, $context);
                } else {
                    $row[] = '';
                }
            }

            if ($attemptobj->is_preview()) {
                $table->data['preview'] = $row;
            } else {
                $table->data[$attemptobj->get_attempt_number()] = $row;
            }
        } // End of loop over attempts.

        $output = '';
        $output .= $this->view_table_heading();
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Generate a brief textual description of the current state of an attempt.
     *
     * @param quiz_attempt $attemptobj the attempt
     * @return string the appropriate lang string to describe the state.
     */
    public function attempt_state($attemptobj) {
        switch ($attemptobj->get_state()) {
            case quiz_attempt::IN_PROGRESS:
                return get_string('stateinprogress', 'quiz');

            case quiz_attempt::OVERDUE:
                return get_string('stateoverdue', 'quiz') . html_writer::tag('span',
                                get_string('stateoverduedetails', 'quiz',
                                        userdate($attemptobj->get_due_date())),
                                ['class' => 'statedetails']);

            case quiz_attempt::FINISHED:
                return get_string('statefinished', 'quiz') . html_writer::tag('span',
                                get_string('statefinisheddetails', 'quiz',
                                        userdate($attemptobj->get_submitted_date())),
                                ['class' => 'statedetails']);

            case quiz_attempt::ABANDONED:
                return get_string('stateabandoned', 'quiz');

            default:
                throw new coding_exception('Unexpected attempt state');
        }
    }

    /**
     * Generates data pertaining to quiz results
     *
     * @param stdClass $quiz Array containing quiz data
     * @param context_module $context The quiz context.
     * @param stdClass|cm_info $cm The course module information.
     * @param view_page $viewobj
     * @return string HTML to display.
     */
    public function view_result_info($quiz, $context, $cm, $viewobj) {
        $output = '';
        if (!$viewobj->numattempts && !$viewobj->gradecolumn && is_null($viewobj->mygrade)) {
            return $output;
        }
        $resultinfo = '';

        if ($viewobj->overallstats) {
            if ($viewobj->moreattempts) {
                $a = new stdClass();
                $a->method = quiz_get_grading_option_name($quiz->grademethod);
                $a->mygrade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->quizgrade = quiz_format_grade($quiz, $quiz->grade);
                $resultinfo .= $this->heading(get_string('gradesofar', 'quiz', $a), 3);
            } else {
                $a = new stdClass();
                $a->grade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                $a = get_string('outofshort', 'quiz', $a);
                $resultinfo .= $this->heading(get_string('yourfinalgradeis', 'quiz', $a), 3);
            }
        }

        if ($viewobj->mygradeoverridden) {

            $resultinfo .= html_writer::tag('p', get_string('overriddennotice', 'grades'),
                            ['class' => 'overriddennotice']) . "\n";
        }
        if ($viewobj->gradebookfeedback) {
            $resultinfo .= $this->heading(get_string('comment', 'quiz'), 3);
            $resultinfo .= html_writer::div($viewobj->gradebookfeedback, 'quizteacherfeedback') . "\n";
        }
        if ($viewobj->feedbackcolumn) {
            $resultinfo .= $this->heading(get_string('overallfeedback', 'quiz'), 3);
            $resultinfo .= html_writer::div(
                            quiz_feedback_for_grade($viewobj->mygrade, $quiz, $context),
                            'quizgradefeedback') . "\n";
        }

        if ($resultinfo) {
            $output .= $this->box($resultinfo, 'generalbox', 'feedback');
        }
        return $output;
    }

    /**
     * Output either a link to the review page for an attempt, or a button to
     * open the review in a popup window.
     *
     * @param moodle_url $url of the target page.
     * @param bool $reviewinpopup whether a pop-up is required.
     * @param array $popupoptions options to pass to the popup_action constructor.
     * @return string HTML to output.
     */
    public function review_link($url, $reviewinpopup, $popupoptions) {
        if ($reviewinpopup) {
            $button = new single_button($url, get_string('review', 'quiz'));
            $button->add_action(new popup_action('click', $url, 'quizpopup', $popupoptions));
            return $this->render($button);

        } else {
            return html_writer::link($url, get_string('review', 'quiz'),
                    ['title' => get_string('reviewthisattempt', 'quiz')]);
        }
    }

    /**
     * Displayed where there might normally be a review link, to explain why the
     * review is not available at this time.
     *
     * @param string $message optional message explaining why the review is not possible.
     * @return string HTML to output.
     */
    public function no_review_message($message) {
        return html_writer::nonempty_tag('span', $message,
                ['class' => 'noreviewmessage']);
    }

    /**
     * Returns the same as {@see quiz_num_attempt_summary()} but wrapped in a link to the quiz reports.
     *
     * @param stdClass $quiz the quiz object. Only $quiz->id is used at the moment.
     * @param stdClass $cm the cm object. Only $cm->course, $cm->groupmode and $cm->groupingid
     * fields are used at the moment.
     * @param context $context the quiz context.
     * @param bool $returnzero if false (default), when no attempts have been made '' is returned
     *      instead of 'Attempts: 0'.
     * @param int $currentgroup if there is a concept of current group where this method is being
     *      called (e.g. a report) pass it in here. Default 0 which means no current group.
     * @return string HTML fragment for the link.
     */
    public function quiz_attempt_summary_link_to_reports($quiz, $cm, $context,
            $returnzero = false, $currentgroup = 0) {
        global $CFG;
        $summary = quiz_num_attempt_summary($quiz, $cm, $returnzero, $currentgroup);
        if (!$summary) {
            return '';
        }

        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        $url = new moodle_url('/mod/quiz/report.php', [
                'id' => $cm->id, 'mode' => quiz_report_default_report($context)]);
        return html_writer::link($url, $summary);
    }

    /**
     * Render a summary of the number of group and user overrides, with corresponding links.
     *
     * @param stdClass $quiz the quiz settings.
     * @param cm_info|stdClass $cm the cm object.
     * @param int $currentgroup currently selected group, if there is one.
     * @return string HTML fragment for the link.
     */
    public function quiz_override_summary_links(stdClass $quiz, cm_info|stdClass $cm, $currentgroup = 0): string {

        $baseurl = new moodle_url('/mod/quiz/overrides.php', ['cmid' => $cm->id]);
        $counts = quiz_override_summary($quiz, $cm, $currentgroup);

        $links = [];
        if ($counts['group']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'group']),
                    get_string('overridessummarygroup', 'quiz', $counts['group']));
        }
        if ($counts['user']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'user']),
                    get_string('overridessummaryuser', 'quiz', $counts['user']));
        }

        if (!$links) {
            return '';
        }

        $links = implode(', ', $links);
        switch ($counts['mode']) {
            case 'onegroup':
                return get_string('overridessummarythisgroup', 'quiz', $links);

            case 'somegroups':
                return get_string('overridessummaryyourgroups', 'quiz', $links);

            case 'allgroups':
                return get_string('overridessummary', 'quiz', $links);

            default:
                throw new coding_exception('Unexpected mode ' . $counts['mode']);
        }
    }

    /**
     * Outputs a chart.
     *
     * @param \core\chart_base $chart The chart.
     * @param string $title The title to display above the graph.
     * @param array $attrs extra container html attributes.
     * @return string HTML of the graph.
     */
    public function chart(\core\chart_base $chart, $title, $attrs = []) {
        return $this->heading($title, 3) . html_writer::tag('div',
                        $this->render($chart), array_merge(['class' => 'graph'], $attrs));
    }

    /**
     * Output a graph, or a message saying that GD is required.
     *
     * @param moodle_url $url the URL of the graph.
     * @param string $title the title to display above the graph.
     * @return string HTML of the graph.
     */
    public function graph(moodle_url $url, $title) {
        $graph = html_writer::empty_tag('img', ['src' => $url, 'alt' => $title]);

        return $this->heading($title, 3) . html_writer::tag('div', $graph, ['class' => 'graph']);
    }

    /**
     * Output the connection warning messages, which are initially hidden, and
     * only revealed by JavaScript if necessary.
     */
    public function connection_warning() {
        $options = ['filter' => false, 'newlines' => false];
        $warning = format_text(get_string('connectionerror', 'quiz'), FORMAT_MARKDOWN, $options);
        $ok = format_text(get_string('connectionok', 'quiz'), FORMAT_MARKDOWN, $options);
        return html_writer::tag('div', $warning,
                        ['id' => 'connection-error', 'style' => 'display: none;', 'role' => 'alert']) .
                html_writer::tag('div', $ok, ['id' => 'connection-ok', 'style' => 'display: none;', 'role' => 'alert']);
    }

    /**
     * Deprecated version of render_links_to_other_attempts.
     *
     * @param links_to_other_attempts $links
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_mod_quiz_links_to_other_attempts(links_to_other_attempts $links) {
        return $this->render_links_to_other_attempts($links);
    }

    /**
     * Deprecated version of render_navigation_question_button.
     *
     * @param navigation_question_button $button
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_quiz_nav_question_button(navigation_question_button $button) {
        return $this->render_navigation_question_button($button);
    }

    /**
     * Deprecated version of render_navigation_section_heading.
     *
     * @param navigation_section_heading $heading the heading.
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_quiz_nav_section_heading(navigation_section_heading $heading) {
        return $this->render_navigation_section_heading($heading);
    }
}
