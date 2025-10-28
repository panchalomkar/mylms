<?php
require_once '../../../config.php';
require_once '../locallib.php';
global $DB, $CFG, $USER;
require_login();

$heading = get_string('pluginname', LANGFILE);
$context = context_system::instance();
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . BASEURL . '/gallery/index.php');
if (is_siteadmin($USER)) {
    $reporturl = 'Main';
    $PAGE->navbar->add($reporturl, new moodle_url('/local/content_structure/'));
}
$PAGE->navbar->add(get_string('pluginname', 'local_content_structure'), '/local/content_structure/index.php');
$PAGE->set_title($heading);
$contentid = optional_param('cid', 0, PARAM_INT);
$search = optional_param('s', '0', PARAM_TEXT);
$sval = '';
$condition = '';
$tcondition = '';
$userid = $USER->id;

$tenant = $DB->get_record('company_users', ['userid' => $userid], 'companyid');

if ($tenant) {
    $companyid = $tenant->companyid;

    // Step 1: Fetch assigned parent content IDs (folders)
    $assignedparents = $DB->get_fieldset_select('content_assign_company', 'contentid', 'companyid = ?', [$companyid]);

    if (empty($assignedparents)) {
        echo $OUTPUT->header();
        echo "<div class='alert alert-warning'>No content assigned to your company.</div>";
        echo $OUTPUT->footer();
        exit;
    }

    // Step 2: Expand to include child files under each parent
    list($in_sql, $in_params) = $DB->get_in_or_equal($assignedparents, SQL_PARAMS_NAMED, 'cid_');
    $children = $DB->get_fieldset_sql("SELECT id FROM {local_content_structure} WHERE parent $in_sql", $in_params);

    // Step 3: Merge both parent folders and child items into content access list
    $contentids = array_merge($assignedparents, $children);

    // Optional: make sure contentids is unique
    $contentids = array_unique($contentids);

} else {
    // No tenant — global user
    $contentids = []; // empty means unrestricted access for admin/global
}



if ($search != '0' && $search != '') {
    $condition = " AND name LIKE :search ";
}

$per_page = 12;
$page = isset($_GET['page']) ? $_GET['page'] - 1 : 0;
$offset = $page * $per_page;

$params = ['contentid' => $contentid];
if (!empty($condition)) {
    $params['search'] = "%$search%";
}
echo $OUTPUT->header();
$localObj = new local_content_structure();
$count_sql = "SELECT COUNT(id) FROM {local_content_structure} WHERE parent = :contentid";
if (!empty($contentids)) {
    list($in_sql, $in_params) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED, 'cid_');
    $count_sql .= " AND id $in_sql";
    $params = array_merge($params, $in_params);
}
if (!empty($condition)) {
    $count_sql .= $condition;
}
$total_images = $DB->count_records_sql($count_sql, $params);

$pages_total = ceil($total_images / $per_page);

// Base SQL
$base_sql = "SELECT id, image, '' AS path, name, 'container' AS type, link 
             FROM {local_content_structure} 
             WHERE parent = :contentid";

// Add condition for tenant users
if (!empty($contentids)) {
    list($in_sql, $in_params) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED, 'cid_');
    $base_sql .= " AND id $in_sql";
    $params = array_merge($params, $in_params);
}

// Add search if needed
if (!empty($condition)) {
    $base_sql .= $condition;
}

// Add pagination
$base_sql .= " LIMIT $offset, $per_page";

// Final query execution
$result = $DB->get_records_sql($base_sql, $params);


// Fetch playlist items for this user

$playlistparams = ['userid' => $userid];

$psql = "
    SELECT 
        cs.id AS itemid,
        cs.name,
        cs.image,
        cs.link,
        cs.parent,  
        p.type AS itemtype
    FROM {local_content_playlist} p
    JOIN {local_content_structure} cs ON p.itemid = cs.id
    WHERE p.userid = :userid
";

if (!empty($contentids)) {
    list($in_sql, $in_params) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED, 'pid_');
    $psql .= " AND cs.id $in_sql";
    $playlistparams = array_merge($playlistparams, $in_params);
}

$psql .= " ORDER BY p.timecreated DESC";

$playlistitems = $DB->get_records_sql($psql, $playlistparams);


// fetch files
$file_sql = "
    SELECT id AS fileid, name, image, parent, 'file' AS itemtype, link 
    FROM {local_content_structure}
    WHERE parent != 0
";

$fileparams = [];
if (!empty($contentids)) {
    list($in_sql, $in_params) = $DB->get_in_or_equal($contentids, SQL_PARAMS_NAMED, 'fid_');
    $file_sql .= " AND id $in_sql";
    $fileparams = array_merge($fileparams, $in_params);
}

$file_sql .= " ORDER BY timecreated DESC";

$allfiles = $DB->get_records_sql($file_sql, $fileparams);

?>


<!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<!--MODAL-->
<div class="modal fade" id="moduleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 650px;">
        <div class="modal-content">
            <div class="modal-header btn-primary-t">
                <h2 class="modal-title text-light d-flex justify-content-start align-items-center"
                    id="exampleModalLabel" style="text-align:center !important; width: 100%;">
                    <?php echo get_string('addmodule', LANGFILE); ?>
                </h2>
                <button type="button" class="close closemodal text-light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-1">

            </div>
        </div>
    </div>
</div>
<!--MODAL END-->
<div class="container pt-4 pb-3 mt-4 p-3" style="background: #fff;">
    <b class="mt-2 mb-4"><?php echo $heading ?></b>
    <?php if (!empty($contentid)) { ?>
        <div class="mb-2 mt-3">
            <?php echo '<a href="' . $CFG->wwwroot . '/local/content_structure/gallery/index.php">' . get_string('back', 'local_content_structure') . '</a> > ' . $localObj->get_gallerybreadcrumb($contentid, ''); ?>
        </div>
        <!-- Search -->
        <div class="d-flex flex-row">
            <div class="input-group col-md-6 mb-3">
                <input type="text" class="form-control" id="searchInput" style="height: 38px;" value="<?php echo $sval; ?>"
                    placeholder="<?php echo get_string('searchcontent', 'local_content_structure'); ?>">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary search-button" type="button" style="height: fit-content;">
                        <i class="fa fa-search" style="color:#003152;"></i>
                    </button>
                    <a href="<?php echo $CFG->wwwroot; ?>/local/content_structure/gallery/index.php"
                        class="btn btn-outline-danger" style="height: fit-content;">Clear</a>
                </div>
            </div>
            <div class="mb-3 col-md-6">
                <div class="mb-3">
                    <div class="btn-group mb-3" id="file-type-buttons" style="border: solid 1px;
    border-radius: 5px;">
                        <button class="btn btn-outline active" data-type="all">
                            <i class="fa fa-list"></i> All
                        </button>
                        <button class="btn btn-outline" data-type="pdf">
                            <i class="fa fa fa-file-pdf-o"></i> PDF
                        </button>
                        <button class="btn btn-outline" data-type="docx">
                            <i class="fa fa-file-word-o"></i> Word
                        </button>
                        <button class="btn btn-outline" data-type="pptx">
                            <i class="fa fa-file-powerpoint-o"></i> PPT
                        </button>
                        <button class="btn btn-outline" data-type="mp4">
                            <i class="fa fa-play"></i> Video
                        </button>
                        <button class="btn btn-outline" data-type="url">
                            <i class="fa fa-link"></i> URL
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Styled Module List -->
        <?php if (!empty($result)) { ?>
            <div class="row" id="file-list">
                <?php foreach ($result as $row) {
                    $name = pathinfo($row->name, PATHINFO_FILENAME);
                    $meta = get_file_display_metadata($row, $CFG);

                    // Access like this:
                    $fileurl = $meta['fileurl'];
                    $type = $meta['type'];
                    $icon = $meta['icon'];
                    $ext = $meta['ext'];
                    $viewtext = $meta['viewtext'];
                    $viewicon = $meta['viewicon'];

                    $typeLabel = ucfirst($ext);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4" data-filetype="<?php echo $ext; ?>">
                        <div class="p-3 rounded shadow-lg d-flex flex-column justify-content-between"
                            style="min-height: 120px; background:#ffffff ;">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?php echo $CFG->wwwroot . '/local/content_structure/images/media/icons/' . $icon . '.png'; ?>"
                                    alt="icon" style="width: 32px; height: 32px;" class="mr-2">
                                <div>
                                    <div class="fw-bold text-truncate"><?php echo $name; ?></div>
                                    <div class="small"><?php echo $typeLabel; ?></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">

                                <div class="d-flex align-items-center btn-primary-t p-0 m-0 btn btn-sm">
                                    <a href="#" data-url="<?php echo $fileurl; ?>" data-type="<?php echo $type; ?>"
                                        archtype="<?php echo $name; ?>" class="btn text-light btn-sm viewfile" data-toggle="modal"
                                        data-target="#moduleModal">
                                        <i class="<?php echo $viewicon; ?> mr-2"></i> <?php echo $viewtext; ?>
                                    </a>
                                    <?php if ($ext === 'url'): ?>
                                        <span class="mr-2">||</span>
                                        <button class="btn btn-sm text-light copy-link-btn"
                                            data-clipboard-text="<?php echo $fileurl; ?>" title="Copy link">
                                            <i class="fa fa-clipboard"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <a href="#" class="btn btn-sm text-white add-to-playlist" data-id="<?php echo $row->id; ?>"
                                    data-type="file" style="background: #ec9707;">
                                    <i class="fa fa-plus mr-2"></i> Add To Playlist
                                </a>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="alert alert-warning mt-3">No content found.</div>
        <?php } ?>



        <!-- Pagination -->
        <?php if ($pages_total > 1) { ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pages_total; $i++) {
                        $active = ($i == $page + 1) ? 'active' : '';
                        ?>
                        <li class="page-item <?php echo $active; ?>">
                            <a class="page-link"
                                href="index.php?cid=<?php echo $contentid; ?>&s=<?php echo $search; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>
    <?php } else { ?>
        <ul class="nav nav-tabs mt-4 mb-3" id="tabNav">
            <li class="nav-item">
                <a class="nav-link foldert active" data-toggle="tab" href="#tab-folders">📁 Folders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link filet" data-toggle="tab" href="#tab-files">📄 Files</a>
            </li>
            <li class="nav-item">
                <a class="nav-link filet" data-toggle="tab" href="#tab-playlist">⭐ My Playlist</a>
            </li>
        </ul>

        <div class="tab-content" id="tab-content">
            <!-- Folders Tab -->
            <div class="tab-pane fade show active" id="tab-folders">
                <div class="input-group col-md-6 mb-3">
                    <input type="text" class="form-control" id="searchInput" value="<?php echo $sval; ?>"
                        style="height: 38px; placeholder=" <?php echo get_string('searchcontent', 'local_content_structure'); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary search-button" type="button" style="height: fit-content;">
                            <i class="fa fa-search"></i>
                        </button>
                        <a href="<?php echo $CFG->wwwroot; ?>/local/content_structure/gallery/index.php"
                            class="btn btn-outline-danger" style="height: fit-content;">Clear</a>
                    </div>
                </div>
                <!-- Folder/File Cards -->
                <div class="row">
                    <?php
                    foreach ($result as $row) {
                        $isfolder = ($row->type === 'container');
                        $link = $isfolder ? "index.php?cid=$row->id" : "#";
                        $name = $isfolder ? $row->name : pathinfo($row->image, PATHINFO_FILENAME);
                        $type = $isfolder ? get_string('folder', 'local_content_structure') : get_file_type($row->image);
                        $defaultimage = $CFG->wwwroot . '/local/content_structure/images/folderimg.png';

                        $image = $isfolder
                            ? (!empty($row->image) ? $CFG->wwwroot . '/local/content_structure/images/media/' . $row->image : $defaultimage)
                            : get_icon($row->image);
                        ?>
                        <div class="col-md-3 p-3">
                            <div class="card h-100 shadow-sm clickable-card" data-href="<?php echo $link; ?>"
                                style="cursor:pointer;border-radius: 10px;">
                                <img src="<?php echo $image; ?>" class="card-img-top" style="height:150px; object-fit:cover;">
                                <div class="card-body p-4">
                                    <span class="badge badge-warning mb-1"><?php echo $type; ?></span><br>
                                    <h6 class="card-title "><?php echo $name; ?></h6>
                                    <!-- <p class="card-text text-muted small">Workpac Workpac Skills</p> -->

                                </div>
                                <div class="card-footer d-flex justify-content-center border-0" style="background:none;">

                                    <a href="#" class="btn btn-primary-t btn-sm add-to-playlist"
                                        data-id="<?php echo $row->id; ?>" data-type="<?php echo $row->type; ?>"><i
                                            class="fa fa-plus mr-2"></i>Add To Playlist</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <?php
                if ($pages_total > 1) {
                    echo '<nav><ul class="pagination justify-content-center">';
                    for ($i = 1; $i <= $pages_total; $i++) {
                        $active = ($i == $page + 1) ? 'active' : '';
                        echo "<li class='page-item mr-2 $active'><a class='page-link' href='index.php?cid=$contentid&s=$search&page=$i'>$i</a></li>";
                    }
                    echo '</ul></nav>';
                }
                ?>
            </div>
            <!-- File Tab -->
            <!-- Filter Buttons -->
            <div class="tab-pane fade" id="tab-files">
                <!-- Search -->
                <div class="row">
                    <div class="input-group col-md-6 mb-3">
                        <input type="text" class="form-control" style="height: 38px; id=" searchInput"
                            value="<?php echo $sval; ?>"
                            placeholder="<?php echo get_string('searchcontent', 'local_content_structure'); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary search-button" type="button"
                                style="height: fit-content;">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="<?php echo $CFG->wwwroot; ?>/local/content_structure/gallery/index.php"
                                class="btn btn-outline-danger" style="height: fit-content;">Clear</a>
                        </div>
                    </div>

                    <div class="mb-3 col-md-6">
                        <div class="mb-3">
                            <div class="btn-group mb-3" id="file-type-buttons" style="border: solid 1px;
    border-radius: 5px;">
                                <button class="btn btn-outline active" data-type="all">
                                    <i class="fa fa-list"></i> All
                                </button>
                                <button class="btn btn-outline" data-type="pdf">
                                    <i class="fa fa fa-file-pdf-o"></i> PDF
                                </button>
                                <button class="btn btn-outline" data-type="docx">
                                    <i class="fa fa-file-word-o"></i> Word
                                </button>
                                <button class="btn btn-outline" data-type="pptx">
                                    <i class="fa fa-file-powerpoint-o"></i> PPT
                                </button>
                                <button class="btn btn-outline" data-type="mp4,video,youtube">
                                    <i class="fa mr-1 fa-play-circle "></i>Videos
                                </button>
                                <button class="btn btn-outline" data-type="url">
                                    <i class="fa fa-link"></i> URL
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <?php if (!empty($allfiles)): ?>
                    <div class="row" id="file-list">
                        <?php foreach ($allfiles as $file): ?>
                            <?php
                            $name = pathinfo($file->name, PATHINFO_FILENAME);
                            $meta = get_file_display_metadata($file, $CFG);

                            $fileurl = $meta['fileurl'];
                            $type = $meta['type']; // check this
                            $icon = $meta['icon'];
                            $viewtext = $meta['viewtext'];
                            $viewicon = $meta['viewicon'];
                            $ext = $meta['ext'];
                            $typeLabel = ucfirst($ext);
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-filetype="<?php echo $ext; ?>">
                                <div class="p-3 rounded shadow-lg d-flex flex-column justify-content-between"
                                    style="min-height: 120px; background: #ffffff;">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?php echo $CFG->wwwroot . '/local/content_structure/images/media/icons/' . $icon . '.png'; ?>"
                                            alt="icon" style="width: 32px; height: 32px;" class="mr-2">
                                        <div>
                                            <div class="fw-bold text-truncate" style="max-width: 200px;"
                                                title="<?php echo $name; ?>">
                                                <?php echo $name; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo $typeLabel; ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">

                                        <div class="d-flex align-items-center btn-primary-t p-0 m-0 btn btn-sm">
                                            <a href="#" data-url="<?php echo $fileurl; ?>" data-type="<?php echo $type; ?>"
                                                archtype="<?php echo $name; ?>" class="btn text-light btn-sm viewfile"
                                                data-toggle="modal" data-target="#moduleModal">
                                                <i class="<?php echo $viewicon; ?> mr-2"></i> <?php echo $viewtext; ?>
                                            </a>
                                            <?php if ($ext === 'url'): ?>
                                                <span class="mr-2">||</span>
                                                <button class="btn btn-sm text-light copy-link-btn"
                                                    data-clipboard-text="<?php echo $fileurl; ?>" title="Copy link">
                                                    <i class="fa fa-clipboard"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <a href="#" class="btn btn-sm text-white add-to-playlist"
                                            data-id="<?php echo $file->fileid; ?>" data-type="file" style="background: #ec9707;">
                                            <i class="fa fa-plus mr-2"></i> Add To Playlist
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">No files found in subfolders.</div>
                <?php endif; ?>
            </div>



            <!-- Playlist Tab -->
            <div class="tab-pane fade" id="tab-playlist">
                <!-- Search -->
                <div class="row">
                    <div class="input-group col-md-6 mb-3">
                        <input type="text" class="form-control" style="height: 38px; id=" searchInput"
                            value="<?php echo $sval; ?>"
                            placeholder="<?php echo get_string('searchcontent', 'local_content_structure'); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary search-button" type="button"
                                style="height: fit-content;">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="<?php echo $CFG->wwwroot; ?>/local/content_structure/gallery/index.php"
                                class="btn btn-outline-danger" style="height: fit-content;">Clear</a>
                        </div>
                    </div>

                    <div class="mb-3 col-md-6">
                        <div class="mb-3">
                            <div class="btn-group mb-3" id="file-type-buttons" style="border: solid 1px;
    border-radius: 5px;">
                                <button class="btn btn-outline active" data-type="all">
                                    <i class="fa fa-list"></i> All
                                </button>
                                <button class="btn btn-outline" data-type="pdf">
                                    <i class="fa fa fa-file-pdf-o"></i> PDF
                                </button>
                                <button class="btn btn-outline" data-type="docx">
                                    <i class="fa fa-file-word-o"></i> Word
                                </button>
                                <button class="btn btn-outline" data-type="pptx">
                                    <i class="fa fa-file-powerpoint-o"></i> PPT
                                </button>
                                <button class="btn btn-outline" data-type="mp4,video,youtube">
                                    <i class="fa mr-1 fa-play-circle"></i>Videos
                                </button>
                                <button class="btn btn-outline" data-type="url">
                                    <i class="fa fa-link"></i> URL
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <?php if (!empty($playlistitems)): ?>
                    <div class="row" id="playlist-list">
                        <?php foreach ($playlistitems as $item): ?>
                            <?php
                            $name = pathinfo($item->name, PATHINFO_FILENAME);

                            // Use shared metadata function
                            $meta = get_file_display_metadata($item, $CFG);
                            $fileurl = $meta['fileurl'];
                            $type = $meta['type'];
                            $icon = $meta['icon'];
                            $ext = $meta['ext'];
                            $viewtext = $meta['viewtext'];
                            $viewicon = $meta['viewicon'];
                            $typeLabel = ucfirst($ext);
                            ?>
                            <div class="col-md-6 playlist col-lg-4 mb-4" data-id="<?php echo $item->itemid; ?>"
                                data-type="<?php echo $item->itemtype; ?>" data-filetype="<?php echo $ext; ?>">
                                <div class="p-3 rounded shadow-lg d-flex flex-column justify-content-between"
                                    style="min-height: 120px; background: #ffffff;">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?php echo $CFG->wwwroot . '/local/content_structure/images/media/icons/' . $icon . '.png'; ?>"
                                            alt="icon" style="width: 32px; height: 32px;" class="mr-2">
                                        <div>
                                            <div class="fw-bold text-truncate" style="max-width: 200px;"
                                                title="<?php echo $name; ?>">
                                                <?php echo $name; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo $typeLabel; ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">


                                        <div class="d-flex align-items-center btn-primary-t p-0 m-0 btn btn-sm">
                                            <?php if (strpos($fileurl, '/gallery/index.php') !== false): ?>
                                                <a href="<?php echo $fileurl; ?>" class="btn text-light btn-sm">
                                                    <i class="<?php echo $viewicon; ?> mr-2"></i> <?php echo $viewtext; ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" data-url="<?php echo $fileurl; ?>" data-type="<?php echo $type; ?>"
                                                    archtype="<?php echo $name; ?>" class="btn text-light btn-sm viewfile"
                                                    data-toggle="modal" data-target="#moduleModal">
                                                    <i class="<?php echo $viewicon; ?> mr-2"></i> <?php echo $viewtext; ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($ext === 'url'): ?>
                                                <span class="mr-2">||</span>
                                                <button class="btn btn-sm text-light copy-link-btn"
                                                    data-clipboard-text="<?php echo $fileurl; ?>" title="Copy link">
                                                    <i class="fa fa-clipboard"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <button type="button" class="btn btn-sm btn-danger remove-from-playlist" title=" Remove">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">Your playlist is empty.</div>
                <?php endif; ?>
            </div>





        </div> <!-- End of .tab-content -->
    <?php } ?>


</div> <!-- End of .container -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

    $(document).ready(function () {
        $('#moduleModal').on('hidden.bs.modal', function () {
            const $modalBody = $(this).find('.modal-body');

            $modalBody.find('video').each(function () {
                this.pause();
                this.currentTime = 0;
            });

            $modalBody.find('iframe').each(function () {
                const $iframe = $(this);
                $iframe.attr('src', $iframe.attr('src'));
            });

            $modalBody.html('');
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('#file-type-buttons button');

        const lists = ['file-list', 'playlist-list'];
        const originalItems = {};


        // Save original items
        lists.forEach(listId => {
            const list = document.getElementById(listId);
            if (!list) return;
            originalItems[listId] = Array.from(list.children);
        });

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const types = button.getAttribute('data-type').split(',');

                // Toggle active class
                buttons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                lists.forEach(listId => {
                    const list = document.getElementById(listId);
                    if (!list) return;

                    list.innerHTML = '';

                    originalItems[listId].forEach(item => {
                        const filetype = item.getAttribute('data-filetype');

                        const match = types.includes('all') || types.includes(filetype);
                        if (match) {
                            item.style.display = '';
                            list.appendChild(item);
                        }
                    });
                });
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        // Handle card clicks (except playlist button)
        document.querySelectorAll('.clickable-card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (!e.target.classList.contains('add-to-playlist')) {
                    window.location.href = this.dataset.href;
                }
                // const tabNav = document.getElementById('tabNav');
                // const tabContent = document.querySelector('.tab-content');

                // if (tabNav) tabNav.style.display = 'none';
                // if (tabContent) tabContent.style.display = 'none';
            });

        });


    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.add-to-playlist').forEach(function (button) {
            const id = button.dataset.id;
            const type = button.dataset.type;

            // ✅ Check if already in playlist on page load
            fetch('../ajax.php?action=checkplaylist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ id: id, type: type })
            })
                .then(response => response.text())
                .then(data => {
                    if (data === 'exists') {
                        button.innerHTML = '<i class="fa fa-check-circle text-light mr-2"></i>Added to Playlist';
                        button.classList.add('added');
                    }
                });

            // Add/Remove on click
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const btn = this;
                const isAdded = btn.classList.contains('added');
                const action = 'addtoplaylist';

                fetch(`../ajax.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({ id: id, type: type })
                })
                    .then(response => isAdded ? response.json() : response.text())
                    .then(data => {
                        if (action === 'addtoplaylist' && data === 'success') {
                            btn.innerHTML = '<i class="fa fa-check-circle text-light mr-2"></i>Added to Playlist';
                            btn.classList.add('added');
                        }
                    })
            });
        });

        // Remove from playlist handler
        document.addEventListener('click', function (e) {
            const target = e.target.closest('.remove-from-playlist');
            if (!target) return;

            e.preventDefault();
            const li = target.closest('div.playlist');
            const itemid = li.dataset.id;
            const itemtype = li.dataset.type;

            if (confirm("Remove this item from your playlist?")) {
                fetch('../ajax.php?action=removeitem', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${itemid}&type=${itemtype}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            li.remove();

                            const list = document.getElementById('playlist-list');
                            if (!list.querySelector('div.playlist')) {
                                list.insertAdjacentHTML('afterend', '<div class="alert alert-warning mt-3">Your playlist is empty.</div>');
                                list.remove();
                            }
                        } else {
                            alert("Failed to remove item.");
                        }
                    });
            }
        });
    });


    jQuery(document).ready(function ($) {

        // Helper: Extract YouTube video ID
        function getYouTubeID(url) {
            const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([\w-]{11})/);
            return match ? match[1] : null;
        }

        // Helper: Extract Google Drive file ID
        function getGoogleDriveID(url) {
            const match = url.match(/\/file\/d\/([^/]+)\//);
            return match ? match[1] : null;
        }

        $("body").on("click", ".viewfile", function () {
            const archtype = $(this).attr("archtype");
            const fileUrl = $(this).data("url");
            const fileType = $(this).data("type");
            const ext = fileUrl ? fileUrl.split('.').pop().toLowerCase() : "";

            const iconMap = {
                pdf: '<i class="fa fa-file-pdf-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                doc: '<i class="fa fa-file-word-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                docx: '<i class="fa fa-file-word-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                ppt: '<i class="fa fa-file-powerpoint-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                pptx: '<i class="fa fa-file-powerpoint-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                png: '<i class="fa fa-file-image-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                jpg: '<i class="fa fa-file-image-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                jpeg: '<i class="fa fa-file-image-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                gif: '<i class="fa fa-file-image-o mr-2 v" aria-hidden="true"></i>',
                mp4: '<i class="fa fa-file-video-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                mov: '<i class="fa fa-file-video-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                avi: '<i class="fa fa-file-video-o mr-2 border p-1 rounded" aria-hidden="true"></i>',
                url: '<i class="fa fa-link mr-2 border p-1 rounded" aria-hidden="true"></i>',
                video_link: '<i class="fa fa-video-camera mr-2 border p-1 rounded" aria-hidden="true"></i>',
            };

            const iconHtml = iconMap[fileType] || iconMap[ext] || '<i class="fa fa-file-o border p-1 rounded" aria-hidden="true"></i>';
            $("#exampleModalLabel").html(iconHtml + archtype);
            $(".modal-body").html("Loading...");
            $("#exampleModalLabel").show();

            if (!fileUrl) {
                $(".modal-body").html("File not found or unsupported.");
                return;
            }

            // Preview logic
            if (["pdf"].includes(ext)) {
                $(".modal-body").html(`<iframe src="${fileUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`);
            } else if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                $(".modal-body").html(`<img src="${fileUrl}" style="max-width:100%; height:auto;" />`);
            } else if (["mp4", "mov", "avi"].includes(ext)) {
                $(".modal-body").html(`
            <video controls style="width:100%; max-height: 350px;">
                <source src="${fileUrl}" type="video/${ext}">
                Your browser does not support the video tag.
            </video>
        `);
            } else if (["doc", "docx", "ppt", "pptx"].includes(ext)) {
                if (window.location.hostname !== "localhost") {
                    const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
                    $(".modal-body").html(`<iframe src="${officeViewerUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`);
                } else {
                    $(".modal-body").html(`<a href="${fileUrl}" target="_blank">Download and open with Microsoft Office or LibreOffice</a><br><small>Preview not available on localhost for Office documents.</small>`);
                }
            } else if (fileType === "url" || fileType === "video_link") {
                let embedUrl = fileUrl;

                // Convert YouTube to embed URL
                if (fileType === "video_link" && /youtube\.com|youtu\.be/.test(fileUrl)) {
                    const videoId = getYouTubeID(fileUrl);
                    if (videoId) embedUrl = `https://www.youtube.com/embed/${videoId}`;
                }

                // Convert Google Drive link
                if (fileType === "video_link" && /drive\.google\.com/.test(fileUrl)) {
                    const driveId = getGoogleDriveID(fileUrl);
                    if (driveId) embedUrl = `https://drive.google.com/file/d/${driveId}/preview`;
                }

                const loaderHtml = `
        <div id="iframeLoader" style="text-align:center; padding:50px;">
            <i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading content...
        </div>
        <iframe id="contentIframe" src="${embedUrl}" width="100%" height="450px" frameborder="0" style="display:none;" allowfullscreen></iframe>
    `;

                $(".modal-body").html(loaderHtml);

                // Show iframe after it loads
                $('#contentIframe').on('load', function () {
                    $('#iframeLoader').hide();
                    $(this).show();
                });
            }
            else {
                $(".modal-body").html(`<a href="${fileUrl}" target="_blank">Download File</a>`);
            }

        });

        // Search button
        $('.search-button').on('click', function () {
            var value = $.trim($('#searchInput').val()).toLowerCase();
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.set('s', value);
            window.location.href = "<?php echo $CFG->wwwroot; ?>/local/content_structure/gallery/index.php?" + urlParams.toString();
        });

        // Delete content
        $('body').on('click', '.delete-content', function (e) {
            e.preventDefault();
            var id = $(this).attr('id');
            var type = $(this).attr('type');
            if (confirm('Are you sure you want to delete the content?')) {
                $.ajax({
                    url: '../ajax.php?action=deletecontent',
                    dataType: 'html',
                    type: "POST",
                    data: { id: id, type: type },
                    success: function (data) {
                        window.location.href = data;
                    },
                    error: function () {
                        alert('Data saving error');
                    },
                });
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        // Get tab from URL param
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'tab-folders';

        // Remove all 'active' classes
        document.querySelectorAll('#tabNav .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });

        // Add 'active' to the correct tab
        const activeTabLink = document.querySelector('#tabNav .nav-link[href="#' + activeTab + '"]');
        if (activeTabLink) {
            activeTabLink.classList.add('active');
        }

        // Optional: show the correct content if using .tab-pane
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active', 'show');
        });
        const activePane = document.getElementById(activeTab);
        if (activePane) {
            activePane.classList.add('active', 'show');
        }

        // Handle click: force page reload
        document.querySelectorAll('#tabNav .nav-link').forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                const tabId = this.getAttribute('href').replace('#', '');
                window.location.href = window.location.pathname + '?tab=' + tabId;
            });
        });
    });


    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-link-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const link = this.getAttribute('data-clipboard-text');
                navigator.clipboard.writeText(link).then(() => {
                    this.innerHTML = '<i class="fa fa-check"></i>'; // show success icon
                    setTimeout(() => {
                        this.innerHTML = '<i class="fa fa-clipboard"></i>';
                    }, 1500);
                }).catch(err => {
                    console.error('Clipboard copy failed:', err);
                });
            });
        });
    });


</script>

<?php echo $OUTPUT->footer(); ?>