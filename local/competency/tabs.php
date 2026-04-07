<?php
global $CFG, $DB, $OUTPUT, $PAGE, $USER;
$tabid=''; $limit =10;
$id =  optional_param('id', 0, PARAM_INT);
$selectPageNo = optional_param('selectPageNo', 1, PARAM_INT);
$selectPageNo1 = optional_param('selectPageNo1', 1, PARAM_INT);
$selectPageNo2 = optional_param('selectPageNo2', 1, PARAM_INT);
$selectPageNo3 = optional_param('selectPageNo3', 1, PARAM_INT);
$pagination=''; $pagination1='';
$pagination2=''; $pagination3='';
$tabid='';$errormessage1='';
$rows='';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap');
ul li { text-decoration: none; list-style-type: none; }
.comp-module-banner {
    background:#003152;
    border-radius: 14px; padding: 28px 36px; margin-bottom: 24px;
    position: relative; overflow: hidden;
    box-shadow: 0 8px 32px rgba(13,27,42,0.18);
}
.comp-module-banner::before {
    content:''; position:absolute; top:-80px; right:-80px;
    width:320px; height:320px;
    background:radial-gradient(circle, rgba(0,180,216,0.1) 0%, transparent 70%);
    pointer-events:none;
}
.comp-module-banner::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:#ec9707;
}
.comp-module-banner .banner-label {
    display:inline-flex; align-items:center; gap:6px;
    background:#ec9707;
    color:#fff; padding:4px 14px; border-radius:100px;
    font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase;
    margin-bottom:10px; font-family:'Inter',sans-serif;
}
.comp-module-banner h2 {
    font-family:'DM Sans',sans-serif; font-size:24px; font-weight:700;
    color:#FFFFFF; margin:0 0 4px 0; letter-spacing:-0.3px;
}
.comp-module-banner p { color:rgba(255,255,255,0.5); font-size:13px; margin:0; font-family:'Inter',sans-serif; }
.path-local-competency .nav-tabs {
    border:none !important; background:#FFFFFF;
    border-radius:12px 12px 0 0; padding:8px 12px 0;
    border-bottom:2px solid #E2E8F0 !important;
    gap:2px; flex-wrap:wrap;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);
}
.path-local-competency .nav-tabs .nav-item { list-style:none; margin:0; }
.path-local-competency .nav-tabs .nav-link {
    font-family:'Inter',sans-serif; font-size:12.5px; font-weight:500;
    color:#64748B; padding:9px 16px; border:none !important;
    border-radius:8px 8px 0 0; transition:all 0.2s ease;
    background:transparent; white-space:nowrap; position:relative; text-decoration:none;
}
.path-local-competency .nav-tabs .nav-link:hover { color:#0096C7; background:rgba(0,180,216,0.07); text-decoration:none; }
.path-local-competency .nav-tabs .nav-link.active {
    color:#0096C7 !important; background:#F7F9FC !important;
    font-weight:600; 
    /* border-bottom:2px solid #ec9707 !important; */
    margin-bottom:-2px; text-decoration:none;
}
.path-local-competency .tab-content {
    background:#F7F9FC; border-radius:0 0 12px 12px; padding:24px;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
    border:1px solid #E2E8F0; border-top:none;
}
.competencytable table, .path-local-competency table.generaltable {
    border-collapse:separate !important; border-spacing:0 !important;
    border-radius:10px; overflow:hidden;
    box-shadow:0 2px 12px rgba(0,0,0,0.06);
    font-family:'Inter',sans-serif; font-size:13.5px;
}
.competencytable td, .path-local-competency table.generaltable td {
    padding:12px 16px !important; vertical-align:middle !important;
    border-bottom:1px solid #F1F5F9 !important; color:#1E293B;
}
.competencytable th, .path-local-competency table.generaltable th {
    text-align:left; padding:13px 16px !important;
    background:#003152 !important;
    color:rgba(255,255,255,0.88) !important; font-size:11.5px !important;
    font-weight:600 !important; letter-spacing:0.6px !important;
    text-transform:uppercase !important; border:none !important;
    font-family:'DM Sans',sans-serif !important;
}
.competencytable .competency_title {
    background:#003152 !important;
    color:#fff !important; font-weight:700 !important;
    font-size:11.5px !important; letter-spacing:0.5px !important; text-transform:uppercase;
}
.competencytable .userlist { background:#003152; color:#fff !important; font-size:12px; }
.competencytable .subcompcolor { background:rgba(0,180,216,0.06) !important; font-weight:500; color:#243B55; }
.path-local-competency table.generaltable tbody tr:hover td { background:rgba(0,180,216,0.04) !important; }
.circle_green { height:12px; width:12px; border-radius:50%; background:linear-gradient(135deg,#10B981,#059669); display:inline-block; box-shadow:0 0 6px rgba(5,150,105,0.4); vertical-align:middle; }
.circle_red { height:12px; width:12px; border-radius:50%; background:linear-gradient(135deg,#EF4444,#DC2626); display:inline-block; box-shadow:0 0 6px rgba(220,38,38,0.4); vertical-align:middle; }
.circle_yellow { height:12px; width:12px; border-radius:50%; background:linear-gradient(135deg,#FBBF24,#D97706); display:inline-block; box-shadow:0 0 6px rgba(217,119,6,0.4); vertical-align:middle; }
.path-local-competency tr:hover td.sticky-col { position:sticky; position:-webkit-sticky; background:rgba(0,180,216,0.06) !important; }
.sticky-col { border-right:2px solid #ec9707 !important; }
.dot { height:7px; width:7px; background-color:#94A3B8; border-radius:50%; display:inline-block; margin-right:4px; }
.table { margin-bottom:0 !important; }
.table-scroll, #table-scroll { overflow-x:auto; border-radius:10px; border:1px solid #E2E8F0; }
.table-scroll::-webkit-scrollbar { height:5px; }
.table-scroll::-webkit-scrollbar-track { background:#F7F9FC; border-radius:3px; }
.table-scroll::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:3px; }
.table-scroll::-webkit-scrollbar-thumb:hover { background:#00B4D8; }
.successmessgae, .path-local-competency .alert-success {
    background:rgba(5,150,105,0.08) !important; color:#059669 !important;
    border:none !important; border-left:3px solid #ec9707 !important;
    border-radius:8px !important; font-weight:500; font-family:'Inter',sans-serif;
}
.path-local-competency .alert-danger {
    background:rgba(220,38,38,0.08) !important; color:#DC2626 !important;
    border:none !important; border-left:3px solid #DC2626 !important; border-radius:8px !important;
}
.path-local-competency .modal-content { border:none !important; border-radius:14px !important; box-shadow:0 20px 60px rgba(0,0,0,0.15) !important; overflow:hidden; font-family:'Inter',sans-serif; }
.path-local-competency .modal-header { background:#003152 !important; border-bottom:none !important; padding:10px 15px !important; }
.path-local-competency .modal-header .modal-title, .path-local-competency .modal-header h5 { color:#FFFFFF !important; font-family:'DM Sans',sans-serif !important; font-weight:600 !important; font-size:16px !important; }
.path-local-competency .modal-header .close { color:rgba(255,255,255,0.7) !important; opacity:1 !important; }
.path-local-competency .modal-header .close:hover { color:#FFFFFF !important; }
.path-local-competency .modal-body { padding:24px !important; background:#FFFFFF !important; }
.path-local-competency .modal-footer { background:#F7F9FC !important; border-top:1px solid #E2E8F0 !important; padding:16px 24px !important; }
.path-local-competency .btn-primary { background:#ec9707 !important; border-color:#ec9707 !important; font-weight:600 !important; font-size:13px !important; padding:9px 22px !important; border-radius:6px !important; box-shadow:0 3px 10px rgba(236, 151, 7, 0.25) !important; transition:all 0.2s ease !important; font-family:'Inter',sans-serif; }
.path-local-competency .btn-primary:hover { transform:translateY(-1px) !important; box-shadow:0 6px 20px rgba(236, 151, 7, 0.35) !important; }
.path-local-competency .btn-secondary { background:#FFFFFF !important; border-color:#CBD5E1 !important; color:#475569 !important; font-size:13px !important; padding:9px 22px !important; border-radius:6px !important; font-family:'Inter',sans-serif; }
.path-local-competency .btn-secondary:hover { border-color:#ec9707 !important; color:#ec9707 !important; }
.path-local-competency .btn-danger { background:linear-gradient(135deg,#DC2626,#EF4444) !important; border-color:#DC2626 !important; font-weight:600 !important; font-size:13px !important; border-radius:6px !important; }
.path-local-competency .form-control, .path-local-competency select.form-control, .path-local-competency input.form-control { border:1.5px solid #E2E8F0 !important; border-radius:6px !important; padding:8px 13px !important; font-size:13.5px !important; color:#1E293B !important; transition:all 0.2s ease !important; font-family:'Inter',sans-serif; }
.path-local-competency .form-control:focus { border-color:#00B4D8 !important; box-shadow:0 0 0 3px rgba(0,180,216,0.15) !important; outline:none !important; }
.path-local-competency ul.pagination li.active a, .path-local-competency ul.pagination li.active span { background:#00B4D8 !important; border-color:#00B4D8 !important; color:white !important; }
.path-local-competency ul.pagination li a { border-radius:6px !important; border:1.5px solid #E2E8F0 !important; color:#475569 !important; font-size:13px !important; padding:6px 12px !important; transition:all 0.18s ease !important; }
.path-local-competency ul.pagination li a:hover { background:rgba(0,180,216,0.08) !important; border-color:#00B4D8 !important; color:#0096C7 !important; }
.custom-dropdown-wrapper { position:relative; width:100%; max-width:400px; }
.custom-dropdown-wrapper .dropdown-input { padding:9px 13px; border:1.5px solid #E2E8F0; cursor:pointer; background:#fff; border-radius:6px; font-size:13.5px; font-family:'Inter',sans-serif; transition:all 0.2s ease; width:100%; }
.custom-dropdown-wrapper .dropdown-input:hover, .custom-dropdown-wrapper .dropdown-input:focus { border-color:#00B4D8; box-shadow:0 0 0 3px rgba(0,180,216,0.12); }
.custom-dropdown-wrapper .dropdown-menu { display:none; position:absolute; width:100%; background:white; border:1.5px solid #E2E8F0; border-radius:10px; max-height:260px; overflow-y:auto; z-index:1050; box-shadow:0 10px 40px rgba(0,0,0,0.1); top:calc(100% + 4px); }
.custom-dropdown-wrapper .dropdown-menu.active { display:block; }
.custom-dropdown-wrapper .dropdown-search { width:100%; padding:10px 13px; font-size:13px; box-sizing:border-box; border:none; border-bottom:1px solid #E2E8F0; background:#F7F9FC; font-family:'Inter',sans-serif; }
.custom-dropdown-wrapper #dropdownList { list-style:none; margin:0; padding:4px; }
.custom-dropdown-wrapper #dropdownList li { padding:9px 12px; cursor:pointer; font-size:13px; border-radius:6px; transition:background 0.15s ease; color:#1E293B; font-family:'Inter',sans-serif; }
.custom-dropdown-wrapper #dropdownList li:hover { background:rgba(0,180,216,0.08); color:#0096C7; }
.comp-zone-legend { display:flex; flex-wrap:wrap; gap:14px; align-items:center; padding:12px 18px; background:#F7F9FC; border-radius:8px; border:1px solid #E2E8F0; margin:16px 0; font-family:'Inter',sans-serif; font-size:12.5px; color:#475569; }
.comp-zone-legend .legend-title { font-weight:700; text-transform:uppercase; letter-spacing:0.5px; font-size:11px; color:#94A3B8; margin-right:6px; }
.comp-zone-legend .legend-item { display:flex; align-items:center; gap:6px; font-weight:500; }
.comp-score-display { display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:50%; font-weight:700; font-size:14px; border:2px solid; line-height:1; font-family:'Courier New',monospace; }
.comp-score-display.green { background:rgba(5,150,105,0.1); border-color:#059669; color:#059669; }
.comp-score-display.yellow { background:rgba(217,119,6,0.1); border-color:#D97706; color:#D97706; }
.comp-score-display.red { background:rgba(220,38,38,0.1); border-color:#DC2626; color:#DC2626; }
.zone-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:100px; font-size:11.5px; font-weight:700; letter-spacing:0.2px; font-family:'Inter',sans-serif; }
.zone-green { background:rgba(5,150,105,0.1); color:#059669; border:1px solid rgba(5,150,105,0.3); }
.zone-yellow { background:rgba(217,119,6,0.1); color:#D97706; border:1px solid rgba(217,119,6,0.3); }
.zone-red { background:rgba(220,38,38,0.1); color:#DC2626; border:1px solid rgba(220,38,38,0.3); }
.comp-section-title { font-family:'DM Sans',sans-serif; font-size:15px; font-weight:700; color:#1E293B; margin-bottom:14px; padding-bottom:11px; border-bottom:1px solid #E2E8F0; display:flex; align-items:center; gap:8px; }
.comp-section-title i { color:#00B4D8; }
.comp-stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:24px; }
.comp-stat-card { background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; padding:16px 18px; box-shadow:0 1px 4px rgba(0,0,0,0.06); display:flex; flex-direction:column; gap:5px; position:relative; overflow:hidden; transition:all 0.2s ease; }
.comp-stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#00B4D8,#0096C7); }
.comp-stat-card.green::before { background:linear-gradient(90deg,#10B981,#059669); }
.comp-stat-card.yellow::before { background:linear-gradient(90deg,#FBBF24,#D97706); }
.comp-stat-card.red::before { background:linear-gradient(90deg,#EF4444,#DC2626); }
.comp-stat-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); transform:translateY(-2px); }
.comp-stat-card .stat-value { font-family:'DM Sans',sans-serif; font-size:26px; font-weight:700; color:#1E293B; line-height:1; letter-spacing:-0.5px; }
.comp-stat-card .stat-label { font-size:11.5px; color:#94A3B8; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; font-family:'Inter',sans-serif; }
.comp-filter-bar { background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; padding:18px 22px; margin-bottom:22px; box-shadow:0 1px 4px rgba(0,0,0,0.05); display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; }
.comp-filter-bar .comp-form-group { flex:1; min-width:150px; margin-bottom:0; }
.comp-form-label { display:block; font-size:11.5px; font-weight:700; color:#64748B; letter-spacing:0.4px; text-transform:uppercase; margin-bottom:5px; font-family:'Inter',sans-serif; }
.comp-spider-container { background:#FFFFFF; border:1px solid #E2E8F0; border-radius:14px; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.06); margin-top:24px; }
.comp-spider-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:14px; }
.comp-spider-title { font-family:'DM Sans',sans-serif; font-size:17px; font-weight:700; color:#1E293B; display:flex; align-items:center; gap:10px; }
.comp-spider-title::before { content:''; display:block; width:4px; height:18px; background:linear-gradient(180deg,#00B4D8,#0096C7); border-radius:2px; }
.comp-spider-legend { display:flex; flex-wrap:wrap; gap:14px; justify-content:center; margin-top:18px; padding:14px; background:#F7F9FC; border-radius:8px; border:1px solid #E2E8F0; font-family:'Inter',sans-serif; }
.comp-spider-legend .legend-item { display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:500; color:#475569; }
.comp-spider-legend .legend-dot { width:11px; height:11px; border-radius:50%; flex-shrink:0; }
.comp-table-wrapper { overflow-x:auto; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border:1px solid #E2E8F0; }
.comp-table { font-size:13.5px; border-collapse:separate; border-spacing:0; width:100%; background:#FFFFFF; border-radius:10px; overflow:hidden; }
.comp-table thead th { background:linear-gradient(135deg,#0D1B2A,#1A2E44) !important; color:rgba(255,255,255,0.88) !important; font-family:'DM Sans',sans-serif !important; font-weight:600 !important; font-size:11.5px !important; letter-spacing:0.6px !important; text-transform:uppercase !important; padding:13px 16px !important; border:none !important; }
.comp-table tbody tr { border-bottom:1px solid #F1F5F9; }
.comp-table tbody tr:hover { background:rgba(0,180,216,0.04); }
.comp-table tbody tr:last-child { border-bottom:none; }
.comp-table tbody td { padding:12px 16px; vertical-align:middle; border:none; color:#1E293B; font-family:'Inter',sans-serif; }
.comp-empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:60px 20px; text-align:center; color:#94A3B8; gap:12px; font-family:'Inter',sans-serif; }
.comp-empty-state p { font-size:14px; max-width:300px; }
@media(max-width:768px) {
    .comp-module-banner { padding:18px; }
    .comp-module-banner h2 { font-size:19px; }
    .path-local-competency .nav-tabs .nav-link { font-size:11.5px; padding:7px 11px; }
    .comp-stats-row { grid-template-columns:repeat(2,1fr); }
    .comp-filter-bar { flex-direction:column; }
}
</style>

<!-- Module Banner -->
<div class="comp-module-banner">
    <div class="banner-label">
        <i class="fa fa-layer-group fa-fw" style="font-size:10px;"></i>
        Competency Management
    </div>
    <h2>On-The-Job Competency Rating</h2>
    <p class="text-white text-light" >Manage, rate and track employee competencies across departments and roles</p>
</div>

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <?php $context = context_system::instance(); ?>
            <?php if (has_capability('local/competency:managemainheading', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='mainheading') echo 'active'; ?>" href="mainheading.php">
                    <i class="fa fa-sitemap fa-fw" style="font-size:11px;margin-right:4px;"></i>Main Competency
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:uploadcompetency', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='uploadcsv') echo 'active'; ?>" href="uploadcompetency.php">
                    <i class="fa fa-upload fa-fw" style="font-size:11px;margin-right:4px;"></i>Upload CSV
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:managesubcompetency', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='subcompetency') echo 'active'; ?>" href="subcompetency.php">
                    <i class="fa fa-project-diagram fa-fw" style="font-size:11px;margin-right:4px;"></i>Sub Competency
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:managesubsubcompetency', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='subsubcompetency') echo 'active'; ?>" href="subsubcompetency.php">
                    <i class="fa fa-code-branch fa-fw" style="font-size:11px;margin-right:4px;"></i>Sub-Sub Competency
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:viewcompetency', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='viewcompetency') echo 'active'; ?>" href="viewcompetency.php">
                    <i class="fa fa-eye fa-fw" style="font-size:11px;margin-right:4px;"></i>View Performance
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:competencyapproval', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='approval') echo 'active'; ?>" href="approval.php">
                    <i class="fa fa-user-check fa-fw" style="font-size:11px;margin-right:4px;"></i>Assign Competency
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:managerrating', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='managerrating') echo 'active'; ?>" href="managerrating.php">
                    <i class="fa fa-star-half-alt fa-fw" style="font-size:11px;margin-right:4px;"></i>Manager Rating
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:userselfrating', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='usersrating') echo 'active'; ?>" href="userselfrating.php">
                    <i class="fa fa-star fa-fw" style="font-size:11px;margin-right:4px;"></i>Self Rating
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:landdrating', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='landdrating') echo 'active'; ?>" href="landdrating.php">
                    <i class="fa fa-award fa-fw" style="font-size:11px;margin-right:4px;"></i>L&amp;D Rating
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_capability('local/competency:landdreport', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='userwisereport') echo 'active'; ?>" href="userwisereport.php">
                    <i class="fa fa-chart-bar fa-fw" style="font-size:11px;margin-right:4px;"></i>L&amp;D Report
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='userreport') echo 'active'; ?>" href="userreport.php">
                    <i class="fa fa-file-alt fa-fw" style="font-size:11px;margin-right:4px;"></i>Self Report
                </a>
            </li>
            <?php if (has_capability('local/competency:maangerreport', $context)): ?>
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='managerwisereport') echo 'active'; ?>" href="managerwisereport.php">
                    <i class="fa fa-chart-line fa-fw" style="font-size:11px;margin-right:4px;"></i>Manager Report
                </a>
            </li>
            <?php endif; ?>
            <!-- Spider Diagram - new feature -->
            <li class="nav-item">
                <a class="nav-link <?php if($activepage=='spiderdiagram') echo 'active'; ?>" href="spiderdiagram.php"
                   style="display:flex;align-items:center;gap:5px;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"></polygon><line x1="12" y1="2" x2="12" y2="22"></line><line x1="2" y1="8.5" x2="22" y2="8.5"></line><line x1="2" y1="15.5" x2="22" y2="15.5"></line></svg>
                    Competency Spider
                </a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade" id="tabapprovalform" role="tabpanel" aria-labelledby="approval-tab"></div>
        </div>
    </div>
</div>
