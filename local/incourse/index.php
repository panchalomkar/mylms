<?php 
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid);
require_once(__DIR__ . '/lib.php');

$course = get_course($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/incourse/index.php', ['id' => $courseid]));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
?>
 
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<style>
    /* Block YouTube Share + Watch Later + Title */
.sv-yt-blocker {
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;   /* covers Share + Watch later */
    height: 80px;   /* covers title bar */
    z-index: 15;
    background: transparent;
    pointer-events: all;
}

    .course-action {
    display: block;
    padding: 8px 14px;
    cursor: pointer;
}
.course-action:hover {
    background: #f3f4f6;
}

   .courseindex-active {
    background: #2e3740 !important;
    border-radius: 8px;
        border: solid 1px hsl(45deg 93% 47% / 30%);
}
#page-local-incourse-index.drawer-open-left {
    overflow: auto;
}
#page-local-incourse-index{    width: 100%;
    position: fixed;}
#page{margin:0px !important; padding:0px !important; }
#page-local-incourse-index #mdb-navbar{ display: none !important;}
#page-local-incourse-index .drawer-left {
    display: none !important;
}

.accordion-header-active{ background:rgb(30 64 175)!important;}
.courseindex-active span,
.courseindex-active div,
.courseindex-active a {
    color: #fff !important;
}
#iltModalBody a[title="Go back"],#iltModalBody .fdescription,#iltModalBody #id_cancel,#iltModalBody #fgroup_id_buttonar .col-form-label {display:none;}
#iltModalBody #fgroup_id_buttonar .felement,#iltModalBody #fgroup_id_buttonar fieldset{display: flex;
    justify-content: center;
    width: 100%;}
    </style>
<div id="full-leftright" class="flex h-screen bg-gray-100 dark:bg-gray-900">

    <!-- SIDEBAR -->
    <aside class="w-80 text-white flex flex-col rounded-r-2xl" style="background:#003152">
        <div class="relative">
            <?php
            global $CFG;
            $courseimage = '';
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $context->id,
                'course',
                'overviewfiles',
                0,
                'itemid, filepath, filename',
                false
            );

            if (!empty($files)) {
                $file = reset($files);
                $courseimage = file_encode_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() .
                    '/' . $file->get_component() .
                    '/' . $file->get_filearea() .
                    $file->get_filepath() .
                    $file->get_filename()
                );
            }

            if (empty($courseimage)) {
                $courseimage = 'https://img.icons8.com/stickers/100/education.png';
            }

            require_once($CFG->libdir . '/completionlib.php');
            $completion = new \completion_info($course);
            $progress = (int) round(\core_completion\progress::get_course_progress_percentage($course, $USER->id));
            $progresscolor = '#ec9707';
            ?>
            <img src="<?php echo $courseimage; ?>" class="w-full h-48 object-cover rounded-tr-2xl" alt="Course banner" style="max-height: 145px;">
            <div class="p-3 m-2 rounded border-1 mt-2">
                <h2 class="text-light mb-3" style="font-size:16px">Course Progress</h2>
                <div class="flex justify-between">
                    <span class="text-sm text-light font-semibold">Overall Progress</span>
                    <span class="text-xs"><?php echo $progress; ?>%</span>
                </div>
                <div class="bg-light w-70 h-2 rounded mt-1">
                    <div class="h-2 rounded" style="width: <?php echo $progress; ?>%; background-color: <?php echo $progresscolor; ?>;"></div>
                </div>
            </div>
        </div>

        <div class="p-2 flex-grow overflow-y-auto" style="scrollbar-width: none;">
            <h3 class="text-lg text-light font-semibold mb-4">Course Content</h3>
            <?php echo local_incourse_render_course_index($course); ?>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <main id="half-content" class="flex-1 flex flex-col">
        <!-- Course Header -->
        <div class="p-8 bg-light rounded-b-lg shadow-sm">
      
            <h1 class="text-3xl font-bold text-text-light dark:text-text-dark d-flex gap-3" style="align-items: center;">
                     <a href="<?php echo $CFG->wwwroot; ?>/my" 
       class="flex items-center justify-center w-5 h-5 p-3 rounded-full bg-[#003152] text-white hover:bg-[#00253d] transition"
       title="Back to Dashboard">
        <span class="material-icons">undo</span>
    </a>   <?php echo format_string($course->fullname); ?>
            </h1>

            <?php if (!empty(trim(strip_tags($course->summary)))): ?>
                <p class="mt-2 text-subtext-light dark:text-subtext-dark">
                    <?php echo format_text($course->summary, FORMAT_HTML); ?>
                </p>
            <?php else: ?>
                <p class="mt-2 text-subtext-light dark:text-subtext-dark">No course summary available.</p>
            <?php endif; ?>

            <div class="flex items-center mt-4  text-sm text-subtext-light dark:text-subtext-dark space-x-4">
                <div class="flex items-center">
                   <?php
require_once($CFG->dirroot.'/blocks/edwiserratingreview/classes/dbhandler.php');
$dbh = new \block_edwiserratingreview\dbhandler();
$ratingdata = $dbh->get_avg_rating_stat_data($courseid);
?>

<?php
$avg = round($ratingdata['averagerating'], 1);
$fullstars = floor($avg);
$emptystars = 5 - $fullstars;
?>

<div class="flex items-center text-sm space-x-2">

    <!-- Stars -->
    <div class="flex">
        <?php for ($i = 0; $i < $fullstars; $i++): ?>
            <span class="material-icons text-yellow-500 text-base">star</span>
        <?php endfor; ?>

        <?php for ($i = 0; $i < $emptystars; $i++): ?>
            <span class="material-icons text-gray-300 text-base">star</span>
        <?php endfor; ?>
    </div>

    <!-- Rating Text -->
    <div class="flex items-center space-x-1">
        <span class="font-semibold"><?= $avg ?></span>
        <span class="font-semibold">(<?= $ratingdata['ratingcount']; ?> Reviews)</span>
    </div>
<button id="openReviewModal"
    class="ml-4 bg-[#003152] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#00253d] transition d-none">
    Write a Review
</button>
</div>

                </div>
                <span class="d-none"><?php echo rand(5,20) . "h total"; ?></span>
                <span class="d-none">Updated <?php echo rand(1,7); ?>d ago</span>
                <span class="d-none"><?php echo rand(3,10); ?>+ languages</span>
            </div>

            <div class="mt-4 flex items-center space-x-2">
                <button id="announcementBtn" class="flex items-center bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm" style="border-color:#ec9707;">
                    <span class="material-icons text-accent-light mr-2" style="color:#ec9707;">campaign</span>
                    Announcements
                </button>
               <div class="relative">
    <button id="courseActionsBtn"
        style="border-color:#ec9707;"
        class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-2 text-sm">
        <span class="material-icons" style="color:#ec9707;">expand_more</span>
    </button>

    <!-- Dropdown -->
    <div id="courseActionsDropdown"
        class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hidden z-50">

        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
            <li><a class="course-action" data-url="user/index.php?id=<?= $courseid ?>">Participants</a></li>
            <li><a class="course-action" data-url="grade/report/user/index.php?id=<?= $courseid ?>">Grades</a></li>
            <li><a class="course-action" data-url="admin/tool/lp/coursecompetencies.php?courseid=<?= $courseid ?>">Competencies</a></li>
        </ul>
    </div>
</div>

            </div>
        </div>

        <!-- Dynamic Content Area -->
           <div id="content-area1" >
            
        </div>
        <div id="content-area" class="flex-grow flex flex-col items-center justify-center text-center p-8">
            <span class="material-icons text-6xl text-gray-400 mb-4">play_circle</span>
            <h2 class="text-2xl font-semibold text-text-light dark:text-text-dark">Select a lesson to begin</h2>
            <p class="mt-2 max-w-md text-subtext-light dark:text-subtext-dark">
                Choose a lesson from the sidebar to view its content here.
            </p>
        </div>
    </main>
</div>

<!-- Review Modal -->
<div id="reviewModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-6 relative">

        <!-- Close Button -->
        <button id="closeReviewModal"
                class="absolute top-3 right-3 text-gray-500 hover:text-black">
            <span class="material-icons">close</span>
        </button>

        <h3 class="font-semibold mb-4 text-lg">Rate this course</h3>

        <div id="rating-stars" class="flex space-x-2 text-3xl cursor-pointer mb-3">
            <span data-star="1" class="material-icons">star_border</span>
            <span data-star="2" class="material-icons">star_border</span>
            <span data-star="3" class="material-icons">star_border</span>
            <span data-star="4" class="material-icons">star_border</span>
            <span data-star="5" class="material-icons">star_border</span>
        </div>

        <textarea id="reviewtext"
            class="w-full border rounded p-3 mb-3"
            rows="4"
            placeholder="Write your review"></textarea>

        <button id="submitrating"
            class="w-full bg-[#003152] text-white py-2 rounded-lg hover:bg-[#00253d] transition">
            Submit Review
        </button>

    </div>
</div>
<!-- Success Popup -->
<div id="successPopup"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 text-center relative">

        <div class="flex justify-center mb-3">
            <span class="material-icons text-green-500 text-5xl">check_circle</span>
        </div>

        <h3 class="text-lg font-semibold mb-2">Thank You for Your Review!</h3>

        <p class="text-gray-600 text-sm mb-4">
            Your feedback helps us improve our courses and provide a better learning experience.
        </p>

        <button id="closeSuccessPopup"
            class="bg-[#003152] text-white px-6 py-2 rounded-lg hover:bg-[#00253d] transition">
            Continue Learning
        </button>
    </div>
</div>
<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<?php
$PAGE->requires->js(new moodle_url('/local/incourse/js/main.js'));?>
<script>

var coursecontextid = <?= $context->id ?>;

document.addEventListener('DOMContentLoaded', function () {

    let selectedStar = 0;

    const modal = document.getElementById('reviewModal');
    const openBtn = document.getElementById('openReviewModal');
    const closeBtn = document.getElementById('closeReviewModal');
    const stars = document.querySelectorAll('#rating-stars span');
    const submitBtn = document.getElementById('submitrating');

    // ✅ Open Modal
    openBtn.addEventListener('click', function () {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    // ✅ Close Modal
    closeBtn.addEventListener('click', function () {
        modal.classList.add('hidden');
    });

    // ✅ Close when clicking outside
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // ⭐ Star selection
    stars.forEach(function (star, index) {
        star.addEventListener('click', function () {
            selectedStar = parseInt(this.dataset.star);

            stars.forEach(function (s) {
                s.innerHTML = 'star_border';
            });

            for (let i = 0; i < selectedStar; i++) {
                stars[i].innerHTML = 'star';
            }
        });
    });

    // 🚀 Submit Rating
    submitBtn.addEventListener('click', function () {

        const review = document.getElementById('reviewtext').value;

        if (selectedStar === 0) {
            alert('Please select rating');
            return;
        }

        fetch(M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' + M.cfg.sesskey, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify([{
                index: 0,
                methodname: 'block_edwiserratingreview_store_userfeedback',
                args: {
                    starcount: selectedStar,
                    contextid: coursecontextid,
                    feedbackreview: review,
                    fortype: 'course'
                }
            }])
        })
        .then(response => response.json())
        .then(data => {

            if (data[0].data.status) {

    // Close rating modal
    modal.classList.add('hidden');

    // Reset form
    selectedStar = 0;
    document.getElementById('reviewtext').value = '';
    stars.forEach(s => s.innerHTML = 'star_border');

    // Show success popup
    const successPopup = document.getElementById('successPopup');
    successPopup.classList.remove('hidden');
    successPopup.classList.add('flex');

}
 else {
                alert('Something went wrong');
            }

        })
        .catch(error => {
            console.error('Error:', error);
        });

    });

    const successPopup = document.getElementById('successPopup');
    const closeSuccessBtn = document.getElementById('closeSuccessPopup');

    closeSuccessBtn.addEventListener('click', function () {
    successPopup.classList.add('hidden');
    window.location.reload();
});

});
// ----  REQUIRED FOR ARROWS TO WORK ----
let activityOrder = [];
let activityIndexMap = {};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".activity-link").forEach((a, index) => {
        let cmid = a.dataset.cmid;
        activityOrder.push({
            cmid: cmid,
            modname: a.dataset.modname,
            href: a.href
        });
        activityIndexMap[cmid] = index;
    });
});

// Build Previous/Next Navigation HTML
function getNavigationHTML(currentCmid) {
    const index = activityIndexMap[currentCmid];
    const prev = index > 0 ? activityOrder[index - 1] : null;
    const next = index < activityOrder.length - 1 ? activityOrder[index + 1] : null;

    return `
        <div class="flex justify-between items-center w-full px-4 py-3 border-b mb-4 bg-white rounded-md shadow-sm">
            <button 
                ${prev ? '' : 'disabled'}
                data-nav="${prev ? prev.cmid : ''}"
                class="nav-btn flex items-center gap-2 text-[#003152] disabled:opacity-30 font-medium">
                <span class="material-icons">arrow_back</span> Previous
            </button>

            <button 
                ${next ? '' : 'disabled'}
                data-nav="${next ? next.cmid : ''}"
                class="nav-btn flex items-center gap-2 text-[#003152] disabled:opacity-30 font-medium">
                Next <span class="material-icons">arrow_forward</span>
            </button>
        </div>
    `;
}

// ----  WORKING CLICK HANDLER FOR NEXT/PREV ----
document.addEventListener("click", function(e) {
    const btn = e.target.closest(".nav-btn");
    if (!btn) return;

    const target = btn.dataset.nav;
    if (!target) return;

    const nextLink = document.querySelector(`.activity-link[data-cmid="${target}"]`);
    if (nextLink) nextLink.click();
});


// -----------------------------------------

document.addEventListener('DOMContentLoaded', () => {

    const btn = document.getElementById('courseActionsBtn');
    const dropdown = document.getElementById('courseActionsDropdown');
    const area = document.getElementById('content-area');

    // Toggle dropdown
    btn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });

    // Close when clicking outside
    document.addEventListener('click', () => {
        dropdown.classList.add('hidden');
    });

    // Handle menu click
    document.querySelectorAll('.course-action').forEach(item => {
        item.addEventListener('click', async () => {
            dropdown.classList.add('hidden');

            const base = (typeof M !== "undefined" && M.cfg)
                ? M.cfg.wwwroot
                : window.location.origin;

            const url = base + '/' + item.dataset.url;

            area.innerHTML = `<div class="text-gray-400 p-8">Loading...</div>`;

            try {
                const html = await fetch(url).then(r => r.text());
                const doc = new DOMParser().parseFromString(html, 'text/html');

                const main =
                    doc.querySelector('#region-main') ||
                    doc.querySelector('.region-main-content') ||
                    doc.body;

                area.innerHTML = main.innerHTML;

            } catch (err) {
                console.error(err);
                area.innerHTML = `<div class="text-red-500 p-8">Failed to load content</div>`;
            }
        });
    });

});
document.addEventListener('DOMContentLoaded', () => {

    // 📜 Inline module content loader
    document.querySelectorAll('.activity-link').forEach(link => {
        
        link.addEventListener('click', async e => {
            e.preventDefault();
            const area = document.getElementById('content-area');
            const area1 = document.getElementById('content-area1');
            const modname = link.dataset.modname;
            const cmid = link.dataset.cmid;

            area.innerHTML = ` <div class="text-gray-400 p-8">Loading Content...</div> `;
             area1.innerHTML = ` ${getNavigationHTML(cmid)} `;
            try {

                if (modname === 'customcert' || modname === 'iomadcertificate') {
                    const baseUrl = link.href.split('?')[0];
                    const params = new URLSearchParams(link.href.split('?')[1]);
                    const id = params.get('id');
                    const pdfUrl =
                        modname === 'iomadcertificate'
                            ? `${baseUrl}?id=${id}&action=get`
                            : `${baseUrl}?id=${id}&downloadown=1`;

                    const pdfData = await fetch(pdfUrl).then(r => r.arrayBuffer());
                    const pdf = await pdfjsLib.getDocument({ data: pdfData }).promise;
                    const page = await pdf.getPage(1);
                    const viewport = page.getViewport({ scale: 1.5 });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    await page.render({ canvasContext: context, viewport }).promise;
                    const imgUrl = canvas.toDataURL('image/png');

                    area.innerHTML = `
                        <div class="relative w-full rounded-lg overflow-hidden flex flex-col items-center justify-center"   style="padding:60px 0;background:#fff;">
                            <div class="absolute top-3 right-3 z-10">
                                <a href="${pdfUrl}" class="flex items-center gap-1 px-4 py-2 bg-[#ec9707] text-white rounded-md hover:bg-[#d38305]" target="_blank" download>
                                    <span class="material-icons text-sm">download</span>Download PDF
                                </a>
                            </div>
                            <svg style="color:#ec9707;" xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-award w-32 h-32 mb-6">
                                 <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path>
                                 <circle cx="12" cy="8" r="6"></circle>
                            </svg>
                            <div style="background:#fff;width:60%;border-radius:12px;padding:0;">
                                <img src="${imgUrl}" alt="Certificate" style="width:100%;border-radius:8px;"/>
                            </div>
                        </div>`;
                    return;
                }
if (modname === 'resource') {

    try {
        const response = await fetch(link.href);
        const html = await response.text();

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // ✅ Extract ONLY real resource content
        const resourceBlock = doc.querySelector('.resourcecontent');

        if (!resourceBlock) {
            area.innerHTML = `
                <div class="text-center text-gray-500 p-10">
                    Resource content not found.
                </div>`;
            return;
        }

        const iframe = resourceBlock.querySelector('iframe');

        if (iframe && iframe.src) {

            // 🔥 IMPORTANT: add ?embed=1 to remove Moodle header/footer
            let cleanSrc = iframe.src;
            if (!cleanSrc.includes('embed=1')) {
                cleanSrc += (cleanSrc.includes('?') ? '&embed=1' : '?embed=1');
            }

            area.innerHTML = `
                <div class="w-full">
                    <iframe 
                        src="${cleanSrc}" 
                        class="w-full border-0"
                        style="height:85vh;">
                    </iframe>
                </div>
            `;
        } else {
            area.innerHTML = `
                <div class="w-full p-6">
                    ${resourceBlock.innerHTML}
                </div>
            `;
        }

        window.scrollTo({ top: 0 });

    } catch (error) {
        console.error(error);
        area.innerHTML = `
            <div class="text-center text-red-500 p-10">
                Failed to load resource.
            </div>
        `;
    }

    return; // 🚀 VERY IMPORTANT (prevents fallback loader)
}


// Handle PDFJSFolder inline view (mod_pdfjsfolder)
if (modname === 'pdfjsfolder') {
    const baseUrl = link.href.split('?')[0];
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');
    const viewUrl = `${baseUrl}?id=${cmid}`;

    const areaHtml = await fetch(viewUrl).then(r => r.text());
    const parser = new DOMParser();
    const doc = parser.parseFromString(areaHtml, 'text/html');
    const pdfLinks = [...doc.querySelectorAll('a[href*="pdf.js"]')];

    // No PDFs case
    if (pdfLinks.length === 0) {
        area.innerHTML = `
            <div class="text-center text-gray-500 p-8">
                <p>No PDF files found in this folder.</p>
            </div>`;
        return;
    }

    // Render PDF list cards
    const renderPdfList = () => {
        area.innerHTML = `
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Available PDFs</h2>
            </div>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 d-flex">
                ${pdfLinks.map(a => {
                    const title = a.innerText.trim();
                    const icon = a.querySelector('img')?.src || '';
                    const href = a.href;
                    return `
                        <div class="flex flex-col items-center justify-center p-6 bg-gray-100 rounded-xl shadow hover:shadow-md transition-all pdf-open cursor-pointer" data-pdf="${href}">
                            <div class="bg-white rounded-full p-4 mb-4 shadow-inner">
                                <img src="${icon}" class="w-10 h-10">
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2" style="width: 220px;height: 30px; overflow: hidden;">${title}</h3>
                            <p class="text-sm text-gray-500 mb-4">Ready to read this PDF document</p>
                            <button class="flex items-center gap-2 bg-[#003152] text-white px-4 py-2 rounded-md hover:bg-[#00263d] transition">
                                <span class="material-icons text-sm">picture_as_pdf</span> Open PDF
                            </button>
                        </div>`;
                }).join('')}
            </div>
        `;

        // Click event for each PDF card
        area.querySelectorAll('.pdf-open').forEach(card => {
            card.addEventListener('click', e => openPdf(card.dataset.pdf));
        });
      };

    // Open PDF in preview mode
    const openPdf = async (pdfUrl) => {
        area.innerHTML = `
            <div class="flex items-center gap-3 mb-6">
                <button id="backToFolder" class="flex items-center text-[#003152] hover:text-[#ec9707] font-medium transition">
                    <span class="material-icons mr-1">arrow_back</span>Back to PDFs
                </button>
            </div>
            <div id="pdfContainer" class="flex flex-col items-center justify-center w-100 p-0 " style="left: 90px;    overflow: hidden;>
                <span class="text-gray-500">Loading PDF...</span>
            </div>
        `;

        // Back button → return to list
        const backBtn = document.getElementById('backToFolder');
        backBtn.addEventListener('click', renderPdfList);

        try {
            // ✅ Improved link extraction (fixes “Failed to open PDF” issue)
            let realPdf = null;

            // Extract full pluginfile URL from ?files= param (including all &params)
            const filesMatch = pdfUrl.match(/files=([^"]+)/);
            if (filesMatch) {
                realPdf = decodeURIComponent(filesMatch[1]);
            } else {
                // Fallback: direct pluginfile.php detection
                const altMatch = pdfUrl.match(/(https?:\/\/[^"']*pluginfile\.php[^"']+)/);
                if (altMatch) realPdf = altMatch[1];
            }

            if (!realPdf) throw new Error('Invalid file link');

            // Fetch and render preview (first page)
            const pdfData = await fetch(realPdf).then(r => r.arrayBuffer());
            const pdf = await pdfjsLib.getDocument({ data: pdfData }).promise;
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale: 1.2 });

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            await page.render({ canvasContext: context, viewport }).promise;

            const filename = decodeURIComponent(realPdf.split('/').pop());

            document.getElementById('pdfContainer').innerHTML = `
                <div class="text-center" style="position: relative;
    right: 170px;
    top: -50px;
    overflow: hidden;">
                    <div class="bg-white rounded-lg p-8 shadow-md border border-gray-200">
                        <img src="${canvas.toDataURL('image/png')}" alt="PDF Preview" class="rounded-lg border border-gray-300 mb-6 max-w-full"/>
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">${filename}</h2>
                        <p class="text-gray-600 mb-6">Ready to read this PDF document</p>
                        <a href="${pdfUrl}" target="_blank"
                            class="flex items-center justify-center gap-2 bg-[#003152] hover:bg-[#00263d] text-white px-6 py-3 rounded-md font-medium">
                            <span class="material-icons text-sm">picture_as_pdf</span> Open PDF
                        </a>
                    </div>
                </div>
            `;

        } catch (err) {
            console.error('PDF open error:', err);

            // If preview fails, fallback to iframe full viewer
            document.getElementById('pdfContainer').innerHTML = `
                <div class="text-center w-full" style="right: 170px;
    top: -50px;
    overflow: hidden;" >
                    <iframe src="${pdfUrl}" style="left: 100px;"  class="w-full h-[80vh] rounded-lg border" allowfullscreen></iframe>
                </div>
               
            `;
        }
    };

      // Initial render
        renderPdfList();
        return;
}

// Handle Google Meet inline view
if (modname === 'googlemeet') {
    const baseUrl = link.href.split('?')[0];
    const params = new URLSearchParams(link.href.split('?')[1]);
    const id = params.get('id');

    area.innerHTML = '<div class="text-gray-400 p-8 text-center">Loading Google Meet...</div>';

    try {
        // Fetch Moodle view page (to extract actual Google Meet link)
        const html = await fetch(link.href).then(r => r.text());
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // 🎥 Extract actual Google Meet link
        const meetLink =
            doc.querySelector('a[href*="https://meet.google.com"]')?.href ||
            doc.querySelector('a[href*="meet.google.com"]')?.href || '';

        // 📘 Extract title
        const title =
            doc.querySelector('.page-header-headings h1')?.innerText?.trim() ||
            'Google Meet Session';

        // 🕒 Extract Event Time info from #googlemeet_upcoming_events
        const eventBlock = doc.querySelector('#googlemeet_upcoming_events');
        let startTime = '', endTime = '', durationText = 'Not available', readableDates = '';

        if (eventBlock) {
            const spans = [...eventBlock.querySelectorAll('span')].map(s => s.innerText.trim());
            readableDates = spans.slice(0, spans.length - 1).join(', ');
            
            const timeText = spans.find(t => t.includes('from')) || '';
            const timeMatch = timeText.match(/from\s*(\d{1,2}:\d{2})\s*to\s*(\d{1,2}:\d{2})/i);
            
            if (timeMatch) {
                startTime = timeMatch[1];
                endTime = timeMatch[2];

                // ⏱ Calculate duration
                const [sh, sm] = startTime.split(':').map(Number);
                const [eh, em] = endTime.split(':').map(Number);
                let startMins = sh * 60 + sm;
                let endMins = eh * 60 + em;
                if (endMins < startMins) endMins += 24 * 60; // handle overnight

                const diff = endMins - startMins;
                const hours = Math.floor(diff / 60);
                const mins = diff % 60;

                durationText =
                    (hours ? `${hours} hour${hours > 1 ? 's' : ''}` : '') +
                    (hours && mins ? ' ' : '') +
                    (mins ? `${mins} minute${mins > 1 ? 's' : ''}` : '');
            }
        }

        // 🧭 Build output UI  
        if (meetLink) {
            area.innerHTML = `
                <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
                    <div class="bg-gray-200 rounded-full p-5 mb-5">
                        <span class="material-icons text-gray-700 text-4xl">videocam</span>
                    </div>
                    <div class="px-4 py-1 rounded-full bg-blue-100 text-dark-blue-700 text-sm font-medium mb-4">
                        Google Meet
                    </div>
                    <h2 class=" text-gray-900 mb-2" style="font-size: 1.5rem !important; line-height: 2rem;font-weight: 600 !important;">${title}</h2>
                    <p class="text-gray-500 mb-1">${readableDates || ''}</p>
                    <p class="text-gray-500 mb-1">${startTime && endTime ? `From ${startTime} to ${endTime}` : ''}</p>
                    <p class="text-gray-500 mb-8">Duration: ${durationText}</p>
                    <a href="${meetLink}" target="_blank"
                        class="inline-flex items-center gap-2 bg-[#003152] hover:bg-[#ec9707] text-white px-5 py-2 rounded-md font-medium transition">
                        <span class="material-icons text-white text-base">video_call</span>
                       Open Google Meet
                    </a>
                </div>
            `;
        } else {
            area.innerHTML = `
                <div class="text-center text-gray-500 p-8">
                    <p>Could not find Google Meet link.</p>
                </div>`;
        }
    } catch (err) {
        console.error('Google Meet load error:', err);
        area.innerHTML = `
            <div class="text-center text-red-500 p-8">
                <p>Failed to load Google Meet details.</p>
            </div>`;
    }

    return;
}
  // Handle SCORM inline view
if (modname === 'scorm') {
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');
    area.innerHTML = `<div class="text-gray-400 p-8 text-center">Loading SCORM details...</div>`;

    try {
        const response = await fetch(`<?= $CFG->wwwroot ?>/local/incourse/fetch_scorm.php?id=${cmid}`);
        const data = await response.json();

        if (data.status === 'success' && data.launchurl) {
            // 🎨 Always show the first "intro" screen (Google Meet style)
            area.innerHTML = `
                <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
                    <div class="bg-gray-200 rounded-full p-4 mb-4">
    <span class="material-icons text-gray-700 text-4xl">
        inventory_2
    </span>
</div>

                    <div class="px-4 py-1 rounded-full bg-blue-100 text-sm font-medium mb-4">
                        SCORM Activity
                    </div>
                    <h2 class="text-gray-900 mb-2"
                        style="font-size: 1.5rem !important; line-height: 2rem;font-weight: 600 !important;">
                        ${data.scormname || 'Interactive Learning Module'}
                    </h2>
                    <p class="text-gray-500 mb-1">This activity contains interactive course content.</p>
                    <p class="text-gray-500 mb-6">Your progress will be tracked automatically.</p>
                    <button id="startScorm"
                        class="inline-flex items-center gap-2 bg-[#003152] hover:bg-[#ec9707] text-white px-5 py-2 rounded-md font-medium transition">
                        <span class="material-icons text-white text-base">play_arrow</span>
                        Start SCORM Activity
                    </button>
                </div>
            `;

       // ▶️ Start button – ALWAYS open in new tab
document.getElementById('startScorm').addEventListener('click', () => {
    window.open(data.launchurl, '_blank', 'noopener,noreferrer');

    area.innerHTML = `
        <div class="text-center text-gray-500 p-8">
            <h2 class="text-lg font-semibold mb-2">
                SCORM Activity Opened in New Tab
            </h2>
            <p>
                Please check your browser tabs to continue the activity.
            </p>
        </div>
    `;
});


        } else {
            area.innerHTML = `
                <div class="text-center text-gray-500 p-8">
                    <h2 class="text-lg font-semibold mb-2">SCORM Launch Error</h2>
                    <p>${data.message || 'Unable to load SCORM package.'}</p>
                </div>
            `;
        }

    } catch (err) {
        console.error('SCORM load error:', err);
        area.innerHTML = `
            <div class="text-center text-red-500 p-8">
                <p>Failed to load SCORM package.</p>
            </div>
        `;
    }

    return;
}
// Handle Zoom inline view
if (modname === 'zoom') {
    area.innerHTML = '<div class="text-gray-400 p-8 text-center">Loading Zoom Meeting...</div>';

    try {
        const html = await fetch(link.href).then(r => r.text());
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Title
        const title =
            doc.querySelector('.page-header-headings h1')?.innerText.trim() ||
            'Zoom Meeting';

        // Start Time
        const startTime =
            doc.querySelector('#zoom_schedule-meetingtime .cell.c1')?.innerText.trim() || '';

        // Duration
        const duration =
            doc.querySelector('#zoom_schedule-duration .cell.c1')?.innerText.trim() || '';

        // Host
        const host =
            doc.querySelector('#zoom_schedule-host .cell.c1')?.innerText.trim() || '';

        // Status
        const status =
            doc.querySelector('#zoom_schedule-status .cell.c1')?.innerText.trim() || '';

        // Extract join link
        let zoomJoinLink = '';
        const showMoreBody = doc.querySelector('#show-more-body');

        if (showMoreBody) {
            const text = showMoreBody.innerText;
            const match = text.match(/https:\/\/[\w./?=&-]+/);
            if (match) zoomJoinLink = match[0];
        }

        // Build UI
        area.innerHTML = `
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">

                <!-- Main Icon -->
                <div class="bg-gray-200 rounded-full p-5 mb-5">
                    <span class="material-icons text-gray-700 text-4xl">videocam</span>
                </div>

                <div class="px-4 py-1 rounded-full bg-blue-100 text-[#003152] text-sm font-medium mb-4">
                    Zoom Meeting
                </div>

                <h2 class="text-gray-900 mb-2"
                    style="font-size: 1.5rem !important; line-height: 2rem; font-weight: 600 !important;">
                    ${title}
                </h2>

                ${startTime ? `<p class="text-gray-500 mb-6">${startTime}</p>` : ''}

                <!-- 🔥 Three Boxes Layout -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 w-full max-w-3xl">

                    <!-- Duration Box -->
                    <div class="bg-white shadow-sm rounded-md flex flex-col items-center px-6 py-4">
                        <span class="material-icons text-green-600 text-4xl mb-2">schedule</span>
                        <p class="font-medium">Duration</p>
                        <p class="text-gray-500 text-sm">${duration || '—'}</p>
                    </div>

                    <!-- Status Box -->
                    <div class="bg-white shadow-sm rounded-md flex flex-col items-center px-6 py-4">
                        <span class="material-icons text-yellow-600 text-4xl mb-2">event_available</span>
                        <p class="font-medium">Status</p>
                        <p class="text-gray-500 text-sm">${status || '—'}</p>
                    </div>

                    <!-- Host Box -->
                    <div class="bg-white shadow-sm rounded-md flex flex-col items-center px-6 py-4">
                        <span class="material-icons text-blue-600 text-4xl mb-2">person</span>
                        <p class="font-medium">Host</p>
                        <p class="text-gray-500 text-sm">${host || '—'}</p>
                    </div>

                </div>

                <!-- Join Button -->
                ${zoomJoinLink ? `
                    <a href="${zoomJoinLink}" target="_blank"
                        class="inline-flex items-center gap-2 bg-[#003152] hover:bg-[#ec9707] text-white px-5 py-2 rounded-md font-medium transition mb-4">
                        <span class="material-icons text-white text-base">videocam</span>
                        Join Zoom Meeting
                    </a>
                ` : `
                    <p class="text-gray-500">Meeting has not started yet</p>
                `}
            </div>
        `;

    } catch (err) {
        console.error('Zoom load error:', err);
        area.innerHTML = `
            <div class="text-center text-red-500 p-8">
                <p>Failed to load Zoom meeting details.</p>
            </div>`;
    }

    return;
}


if (modname === 'h5pactivity') {
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');

    if (!cmid) {
        console.error("H5P CMID missing");
        return;
    }

    area.innerHTML = `
        <div class="text-gray-400 p-8 text-center animate-pulse">
            Loading H5P activity...
        </div>
    `;

    try {
        const base = (typeof M !== "undefined" && M.cfg) ? M.cfg.wwwroot : window.location.origin;
        const response = await fetch(`${base}/local/incourse/fetch_h5p.php?id=${cmid}`);
        const data = await response.json();

        if (data.status === 'success' && data.embedurl) {

            // ✅ Direct inline H5P view
            area.innerHTML = `
                <div class="flex items-center gap-3 mb-2 mt-2">
                    <button id="backToCourse"
                        class="flex items-center text-[#003152] hover:text-[#ec9707] font-medium transition d-none">
                        <span class="material-icons mr-1 text-lg">arrow_back</span>
                        Back to Course
                    </button>
                    <h2 class="text-lg font-semibold text-[#003152]">${data.h5pname}</h2>
                </div>

                <div id="h5pContainer"
                    class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-md " style="width:93%;">
                    <iframe
                        src="${data.embedurl}"
                        class="w-full h-[88vh] border-0 bg-white"
                        allowfullscreen
                        allow="fullscreen; autoplay; encrypted-media">
                    </iframe>
                </div>
            `;
  // ✅ Trigger Moodle completion (ONLY ONCE)
            if (!window.h5pCompletionTriggered) {
                window.h5pCompletionTriggered = true;

                const completionIframe = document.createElement('iframe');
                completionIframe.src = `${base}/mod/h5pactivity/view.php?id=${cmid}`;
                completionIframe.style.cssText =
                    'width:1px;height:1px;opacity:0;position:absolute;left:-9999px;';
                document.body.appendChild(completionIframe);
            }
            // ✅ Back to course
            document.getElementById('backToCourse').addEventListener('click', () => {
                window.location.reload();
            });

        } else {
            area.innerHTML = `
                <div class="text-center text-gray-500 p-8">
                    <h2 class="text-lg font-semibold mb-2">H5P Launch Error</h2>
                    <p>${data.message || 'Unable to load H5P package.'}</p>
                </div>
            `;
        }
    } catch (err) {
        console.error('H5P load error:', err);
        area.innerHTML = `
            <div class="text-center text-red-500 p-8">
                <p>Failed to load H5P activity.</p>
            </div>
        `;
    }

    return;
}





                // 🧩 Other modules (load HTML content dynamically)
                const response = await fetch(link.href);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const main = doc.querySelector('#region-main') || doc.body;
                area.innerHTML = main.innerHTML;

// ✅ Helpers for saving progress
function saveWatchTime(cmid, seconds) {
    localStorage.setItem("video_time_" + cmid, seconds);
}
function getWatchTime(cmid) {
    return parseFloat(localStorage.getItem("video_time_" + cmid)) || 0;
}
function markVideoCompleted(cmid) {
    localStorage.setItem("video_completed_" + cmid, "1");
}
function isVideoCompleted(cmid) {
    return localStorage.getItem("video_completed_" + cmid) === "1";
}
// 🎬 SUPERVIDEO (CORRECT + STABLE)
if (modname === 'supervideo') {

    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');
    if (!cmid) return;

    const base = (typeof M !== "undefined" && M.cfg && M.cfg.wwwroot)
        ? M.cfg.wwwroot
        : window.location.origin;

    // Clear area immediately
   area.innerHTML = `
    <div class="relative w-full h-[85vh] bg-black rounded-2xl overflow-hidden">

        <!-- Loader -->
        <div id="sv-loader"
             class="absolute inset-0 flex flex-col items-center justify-center text-white bg-black/70 z-20">
            <span class="material-symbols-outlined text-5xl mb-2 animate-pulse">
                smart_display
            </span>
            <p class="text-sm opacity-80">Loading Super Video…</p>
        </div>

        <!-- 🔒 UI BLOCKER (THIS IS THE FIX) -->
        <div class="sv-yt-blocker"></div>

        <!-- IFRAME -->
        <iframe
            src="${base}/mod/supervideo/view.php?id=${cmid}"
            class="w-full h-full border-0 bg-white relative z-10"
            allowfullscreen
            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups">
        </iframe>
    </div>
`;


    // Remove loader after short delay (iframe onload is unreliable)
    setTimeout(() => {
        const loader = document.getElementById('sv-loader');
        if (loader) loader.remove();
    }, 1500);

    return;
}

if (modname === 'videotime') {

    let playerWrap = area.querySelector('#videoWrap');
    if (!playerWrap) {
        playerWrap = document.createElement('div');
        playerWrap.id = 'videoWrap';
        area.innerHTML = '';
        area.appendChild(playerWrap);
    }
    playerWrap.innerHTML = '';

    try {
        const cmid = link.dataset.cmid;

        const html = await fetch(link.href).then(r => r.text());
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const title =
            doc.querySelector('.page-header-headings h1')?.innerText?.trim() ||
            doc.querySelector('.activityinstance .instancename')?.innerText?.trim() ||
            'Video Session';

        const fetchUrl = `<?= $CFG->wwwroot ?>/local/incourse/fetch_videotime.php?cmid=${cmid}`;
        const response = await fetch(fetchUrl);
        const data = await response.json();

        if (!data.videourl) {
            area.innerHTML = '<div class="text-red-400 p-8">Video not found.</div>';
            return;
        }

        const videoUrl = data.videourl;
        const videoContainer = document.createElement('div');
        videoContainer.className = 'rounded-xl overflow-hidden bg-black mb-6';
        playerWrap.appendChild(videoContainer);

        let videoEl = null;
        let ytPlayer = null;

        const infoDiv = document.createElement('div');
        infoDiv.className = 'mt-6';
        infoDiv.innerHTML = `
            <div class="flex flex-col gap-2">
                <span class="text-sm text-blue-700 font-medium">Video</span>
                <h2 class="text-2xl font-semibold text-gray-900">${title}</h2>
                <p id="videoDuration" class="text-gray-500 text-sm">Duration: calculating...</p>
            </div>

            <button id="continueBtn" 
                class="mt-4 w-full bg-[#001F5B] hover:bg-[#003152] text-white font-medium py-3 rounded-lg flex items-center justify-center gap-2 transition">
                <svg id="playIcon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                <span id="btnText">Continue Watching</span>
            </button>
        `;
        playerWrap.appendChild(infoDiv);

        const durationEl = infoDiv.querySelector('#videoDuration');
        const continueBtn = infoDiv.querySelector('#continueBtn');
        const playIcon = infoDiv.querySelector('#playIcon');
        const btnText = infoDiv.querySelector('#btnText');

        // ✅ YOUTUBE VIDEO
        if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {

            const ytDiv = document.createElement('div');
            ytDiv.id = 'ytPlayer_' + cmid;
            videoContainer.appendChild(ytDiv);

            function extractVideoId(url) {
                const u = new URL(url);
                if (u.hostname.includes('youtu.be')) return u.pathname.slice(1);
                return u.searchParams.get('v');
            }

            function initYTPlayer() {
                ytPlayer = new YT.Player(ytDiv.id, {
                    videoId: extractVideoId(videoUrl),
                    playerVars: { autoplay: 0, controls: 1, rel: 0, modestbranding: 1 },
                    events: {
                        onReady: (event) => {
                            const d = event.target.getDuration();
                            durationEl.textContent = `Duration: ${Math.floor(d/60)}m ${(d%60).toFixed(0)}s`;

                            // ✅ Click inside player always start from 0
                            const iframe = ytDiv.querySelector('iframe');
                            iframe?.contentWindow?.postMessage(JSON.stringify({
                                event: "command",
                                func: "seekTo",
                                args: [0]
                            }), "*");

                            setInterval(() => {
                                const t = ytPlayer.getCurrentTime();
                                if (!isNaN(t)) saveWatchTime(cmid, t);
                                if (t >= d - 3) markVideoCompleted(cmid);
                            }, 5000);
                        }
                    }
                });
            }

            if (!window.YT) {
                const tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                document.body.appendChild(tag);
                window.onYouTubeIframeAPIReady = initYTPlayer;
            } else initYTPlayer();


        } else {
            // ✅ LOCAL MP4 PLAYER
            videoEl = document.createElement('video');
            videoEl.src = videoUrl;
            videoEl.controls = true;
            videoEl.style.width = '100%';
            videoEl.style.maxHeight = '600px';
            videoContainer.appendChild(videoEl);

            videoEl.addEventListener('loadedmetadata', () => {
                const mins = Math.floor(videoEl.duration / 60);
                const secs = Math.floor(videoEl.duration % 60).toString().padStart(2, '0');
                durationEl.textContent = `Duration: ${mins}m ${secs}s`;
            });

            // ✅ If user clicks video Play button → always reset to 0
            videoEl.addEventListener("play", () => {
                if (videoEl.currentTime > 2) videoEl.currentTime = 0;
            });

            videoEl.addEventListener("timeupdate", () => {
                saveWatchTime(cmid, videoEl.currentTime);
                if (videoEl.currentTime >= videoEl.duration - 3) markVideoCompleted(cmid);
            });
        }

        // ✅ Continue Button → Resume from saved time
        continueBtn.addEventListener('click', () => {
            const last = getWatchTime(cmid) || 0;

            const pauseUI = () => {
                playIcon.innerHTML = `<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>`;
                btnText.textContent = "Pause Video";
            };
            const playUI = () => {
                playIcon.innerHTML = `<path d="M8 5v14l11-7z"/>`;
                btnText.textContent = "Continue Watching";
            };

            if (videoEl) {
                if (videoEl.paused) {
                    videoEl.currentTime = last;
                    videoEl.play();
                    pauseUI();
                } else {
                    videoEl.pause();
                    playUI();
                }
            } else if (ytPlayer) {
                if (ytPlayer.getPlayerState() !== 1) {
                    ytPlayer.seekTo(last, true);
                    ytPlayer.playVideo();
                    pauseUI();
                } else {
                    ytPlayer.pauseVideo();
                    playUI();
                }
            }
        });

    } catch (err) {
        console.error(err);
        area.innerHTML = '<div class="text-red-400 p-8">Failed to load video.</div>';
    }
}
if (modname === 'quiz') {
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');
    const base = (typeof M !== "undefined" && M.cfg) ? M.cfg.wwwroot : window.location.origin;

    // small helper to create element from HTML
    const elFrom = (html) => {
        const div = document.createElement('div');
        div.innerHTML = html.trim();
        return div.firstElementChild;
    };

    // show loading skeleton
    area.innerHTML = `
        <div class="text-gray-400 p-8 text-center animate-pulse">
            Loading quiz details...
        </div>
    `;

    try {
        const response = await fetch(`${base}/local/incourse/fetch_quiz.php?cmid=${cmid}`);
        const data = await response.json();

        // compute dynamic values
        const minutes = data.timelimit ? Math.round(data.timelimit / 60) + " mins" : "No Limit";
        const remaining = data.attempts_remaining === "Unlimited"
            ? "Unlimited"
            : `${data.attempts_remaining} remaining`;

        // SEB / Proctor
        const isSEB = data.seb_enabled == 1;
        const isProctor = data.proctoring_enabled == 1;

        // choose start URL depending on mode
        let startURL = `${base}/mod/quiz/view.php?id=${cmid}`;
        if (isProctor) startURL = `${base}/local/proctor/start.php?cmid=${cmid}`;
        else if (isSEB) startURL = `${base}/mod/quiz/view.php?id=${cmid}`;

        // build attempts HTML (table with marks+grade)
        let attemptsHTML = "";
        if (!data.attempts_list || data.attempts_list.length === 0) {
            attemptsHTML = `<p class="text-gray-500 text-center py-4">No previous attempts found.</p>`;
        } else {
            attemptsHTML = `
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Attempt</th>
                            <th class="py-2">State</th>
                            <th class="py-2">Marks</th>
                            <th class="py-2">Grade</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr]:border-b [&>tr]:border-gray-200" style="border:none;">
            `;

            data.attempts_list.forEach(a => {
                attemptsHTML += `
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium">
                            Attempt ${a.attemptnum}
                            <div class="text-gray-400 text-xs mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size:12px;">calendar_today</span>
                                ${a.completed}
                            </div>
                        </td>

                        <td class="py-3">
                            <div class="inline-flex items-center gap-2">
                                <span class="inline-flex items-center text-white bg-[#2ac48e] px-2 py-0.5 rounded-full text-sm">
                                    <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>
                                    Finished
                                </span>
                            </div>
                        </td>

                        <td class="py-3">${a.marks}</td>
                        <td class="py-3">${a.grade} / 100</td>

                        <td class="py-3 text-right">
                            <a href="${a.reviewurl}" class="px-4 py-1 rounded-lg border text-sm hover:bg-gray-100 inline-block">
                                Review
                            </a>
                        </td>
                    </tr>
                `;
            });

            attemptsHTML += `
                    </tbody>
                </table>

                <div class="mt-4 bg-gray-50 p-3 rounded-lg text-sm flex border-2 gap-2 items-center">
                    <span class="text-gray-600">Highest Grade:</span>
                    <span class="font-bold text-gray-800">${data.highest_grade} / 100.00</span>
                </div>
            `;
        }

        // FINAL UI markup (cards + attempts + CTA). Note the start button has id="openStartModalBtn"
        area.innerHTML = `
        <div class="p-6 md:p-10">

            <!-- TOP STAT CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">

                <!-- Time Limit -->
                <div class="bg-[#f0f8ff] p-4 rounded-xl shadow flex items-center gap-3 border">
                    <div class="w-12 h-12 rounded-xl bg-[#003152] flex items-center justify-center">
                      <span class="material-symbols-outlined text-white text-3xl">schedule</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Time Limit</p>
                        <p class="text-xl font-bold">${minutes}</p>
                    </div>
                </div>

                <!-- Grading Method -->
                <div class="bg-[#ebfaf4] p-4 rounded-xl shadow flex items-center gap-3 border">
                    <div class="w-12 h-12 rounded-xl bg-[#2ac48e] flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-3xl">trending_up</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Grading Method</p>
                        <p class="text-xl font-bold">${data.grademethod}</p>
                    </div>
                </div>

                <!-- Grade to Pass -->
                <div class="bg-[#fff5e8] p-4 rounded-xl shadow flex items-center gap-3 border">
                    <div class="w-12 h-12 rounded-xl bg-[#fd9602] flex items-center justify-center">
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-target w-8 h-8 mx-auto text-white" data-lov-id="src/components/CourseLanding.tsx:318:22" data-lov-name="Target" data-component-path="src/components/CourseLanding.tsx" data-component-line="318" data-component-file="CourseLanding.tsx" data-component-name="Target" data-component-content="%7B%22className%22%3A%22w-8%20h-8%20mx-auto%20mb-2%20text-orange-600%22%7D"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>

                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Grade to Pass</p>
                        <p class="text-xl font-bold">${data.grade_to_pass} / 100</p>
                    </div>
                </div>

                <!-- Attempts -->
                <div class="bg-[#fff8f4] p-4 rounded-xl shadow flex items-center gap-3 border">
                    <div class="w-12 h-12 rounded-xl bg-[#fcb684] flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-3xl">autorenew</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Attempts Allowed</p>
                        <p class="text-xl font-bold">${remaining}</p>
                    </div>
                </div>

            </div>

            <!-- SUMMARY OF PREVIOUS ATTEMPTS -->
            <div class="bg-white p-6 rounded-xl shadow mb-10 border">
                <h2 class="text-lg text-left font-bold mb-4">Summary of Previous Attempts</h2>
                ${attemptsHTML}
            </div>

            <!-- READY TO TRY AGAIN SECTION -->
            <div class="bg-white p-6 rounded-xl shadow border text-center">
                <h2 class="text-2xl font-bold mb-2">Ready to Try Again?</h2>
                <p class="text-gray-500 mb-6">Click the button below to start a new attempt.</p>

                <button id="openStartModalBtn"
                    class="inline-flex items-center gap-2 bg-[#003152] hover:bg-[#0b2f49] text-white px-6 py-3 rounded-lg text-lg font-semibold transition">
                    <span class="material-symbols-outlined">sync</span>
                    ${data.attempts_remaining != 0 ? "Start / Re-attempt Quiz" : "Review Attempts Only"}
                </button>
                
            </div>

        </div>
        `;

        // ---------- Modal creation function ----------
        function showStartModal({ title, timelabel, timelimitText, description, startUrl }) {

            // if modal exists remove it first
            const existing = document.getElementById('quizStartModal');
            if (existing) existing.remove();

            const modalHtml = `
            <div id="quizStartModal" class="fixed inset-0 z-50 flex items-center justify-center p-6">
                <div class="absolute inset-0 bg-black opacity-60"></div>

                <div role="dialog" aria-modal="true" aria-labelledby="quizStartTitle" class="relative bg-white w-full max-w-md rounded-xl shadow-2xl p-3 md:p-10">
                    <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700">
                        <span class="material-symbols-outlined" style="font-size:24px;">close</span>
                    </button>

                    <div class="flex flex-col items-center text-center">
                        <!-- circular icon -->
                        <div class="w-12 h-12 rounded-full bg-[#003152] flex items-center justify-center mb-2 shadow-lg">
                            <span class="material-symbols-outlined text-white text-4xl">schedule</span>
                        </div>

                        <h2 id="quizStartTitle" class="text-2xl md:text-2xl font-bold text-[#0b2f49] mb-2">${title}</h2>

                        <!-- time box -->
                        <div class="mb-4">
                            <div class="inline-block rounded-xl border border-[#f3d3b0] bg-[#fff5e8] px-2 py-1">
                                <div class="d-flex flex-row gap-1"><div class="text-2xl font-extrabold text-[#fd9602]">${timelabel}</div><span class="d-flex" style="    align-items: anchor-center;">Minutes</span></div>
                                <div class="text-sm text-gray-500">Time Limit</div>
                            </div>
                        </div>

                        <div class="mb-4 text-left">
                            <div class="border-l-4 border-[#fde8c9] bg-[#fff8f0] p-2 rounded-lg text-sm text-gray-700">
                                ${description}
                            </div>
                        </div>

                        <div class="w-full flex items-center justify-between gap-4 mt-2">
                            <button id="modalCancelBtn" class="flex-1 px-2 py-2 border rounded-lg text-white bg-red-500 hover:bg-red-600">Cancel</button>
                            <button id="modalStartBtn" class="flex-1 px-2 py-2 bg-[#003152] text-white rounded-lg hover:bg-[#0b2f49]">Start Attempt</button>
                        </div>
                    </div>
                </div>
            </div>
            `;

            const modalEl = elFrom(modalHtml);
            document.body.appendChild(modalEl);

            // focus management
            const startBtn = modalEl.querySelector('#modalStartBtn');
            const cancelBtn = modalEl.querySelector('#modalCancelBtn');
            const closeBtn = modalEl.querySelector('#closeModalBtn');

            // handlers
            cancelBtn.addEventListener('click', () => modalEl.remove());
            closeBtn.addEventListener('click', () => modalEl.remove());
            modalEl.addEventListener('click', (e) => {
                if (e.target === modalEl) modalEl.remove();
            });

            // keyboard: Esc to close
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    modalEl.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);

            // Start action — navigate to real start url
startBtn.addEventListener("click", () => {

    const base = M.cfg.wwwroot;

    // 1️⃣ If user has an IN-PROGRESS attempt → resume
    if (data.inprogress_attemptid && data.inprogress_attemptid > 0) {

        let url = `${base}/mod/quiz/attempt.php?attempt=${data.inprogress_attemptid}&cmid=${cmid}`;
        window.location.href = url;
        return;
    }

    // 2️⃣ If this is a NEW attempt → use startattempt.php (IMPORTANT!)
    let url = "";

     if (data.proctoring_enabled == 1) {
        // Important: view.php uses 'id' (cmid) param, not cmid param name
         window.location.href = `${base}/mod/quiz/accessrule/seb/start.php?cmid=${cmid}`;
        // window.location.href = `${base}/mod/quiz/view.php?id=${cmid}`;
        return;
    }else if (data.seb_enabled == 1) {

        url = `${base}/mod/quiz/accessrule/seb/start.php?cmid=${cmid}`;

    } else {

        // ⭐ Correct Moodle flow for fresh attempt
        url = `${base}/mod/quiz/startattempt.php?cmid=${cmid}&sesskey=${M.cfg.sesskey}`;
    }

    window.location.href = url;
});



            // autofocus start button
            startBtn.focus();
        }

        // open modal with dynamic data when Start clicked
        document.getElementById('openStartModalBtn').addEventListener('click', () => {

    const desc = `Your quiz attempt will begin immediately upon confirmation. The timer will run continuously.`;

    // If NO previous attempts → Show special First-Time Popup
    if (data.first_attemptid == 0) {

        showStartModal({
            title: 'Start Your First Quiz Attempt',
            timelabel: (data.timelimit ? Math.round(data.timelimit / 60) : 'No Limit'),
            timelimitText: minutes,
            description: `This is your first time attempting this quiz. Once you begin, the timer cannot be paused.`,
            startUrl: startURL + `&attempt=${data.next_attempt}` // first attempt = attempt 1
        });

    } else {

        // Normal re-attempt modal
        showStartModal({
            title: 'Start Your Quiz Attempt',
            timelabel: (data.timelimit ? Math.round(data.timelimit / 60) : 'No Limit'),
            timelimitText: minutes,
            description: desc,
            startUrl: startURL + `&attempt=${data.next_attempt}`
        });
    }
});


    } catch (error) {
        console.error("Quiz load error:", error);
        area.innerHTML = `
            <div class="text-red-500 p-6 text-center">
                Failed to load quiz info.
            </div>
        `;
    }

    return;
}



// 🎯 GOONE Activity (same UI as H5P)
// 🎯 GOONE – behave EXACTLY like SCORM
if (modname === 'goone') {
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');

    area.innerHTML = `
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <div class="bg-gray-200 rounded-full p-4 mb-4">
                <span class="material-icons text-gray-700 text-4xl">school</span>
            </div>
            <div class="px-4 py-1 rounded-full bg-blue-100 text-sm font-medium mb-4">
                Go1 Activity
            </div>
            <h2 class="text-gray-900 mb-2 text-xl font-semibold">
                ${link.textContent}
            </h2>
            <p class="text-gray-500 mb-6">
                Click start to launch the activity inline. Progress will be tracked automatically.
            </p>
            <button id="startGoOne" class="inline-flex items-center gap-2 bg-[#003152] hover:bg-[#ec9707] text-white px-5 py-2 rounded-md font-medium transition">
                <span class="material-icons">play_arrow</span>
                Start Go1 Activity
            </button>
        </div>
    `;

    const btn = document.getElementById('startGoOne');
    btn.addEventListener('click', async () => {
        area.innerHTML = `<div class="text-center py-20">Loading Go1 activity...</div>`;
        try {
            const response = await fetch(`${M.cfg.wwwroot}/local/incourse/get_token.php?cmid=${cmid}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.message || 'Failed to get Go1 token');

            area.innerHTML = `
                <div role="main">
                    <div class="container" style="height: 680px;">
                        <iframe
                            src="${data.url}"
                            allowfullscreen
                            loading="eager"
                            allow="autoplay *; camera *; display-capture *; fullscreen *; microphone *"
                            style="width: 100%; height: 100%; border: 0;">
                        </iframe>
                    </div>
                </div>
            `;
        } catch (err) {
            area.innerHTML = `<div class="text-center text-red-600 py-20">Error loading Go1 activity: ${err.message}</div>`;
        }
    });
}



// 🧑‍🏫 Handle ILT (Instructor-Led Training) inline view
if (modname === 'ilt') {
    const params = new URLSearchParams(link.href.split('?')[1]);
    const cmid = params.get('id');
    if (!cmid) return console.error("ILT CMID missing");

    area.innerHTML = `
        <div class="text-gray-400 p-8 text-center animate-pulse">
            <span class="material-icons text-4xl mb-2 text-[#003152]">event</span>
            <p>Loading Instructor-Led Training sessions...</p>
        </div>
    `;

   try {
    const base = (typeof M !== "undefined" && M.cfg && M.cfg.wwwroot)
        ? M.cfg.wwwroot
        : window.location.origin;

    const response = await fetch(`${base}/mod/ilt/view.php?id=${cmid}`);
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, "text/html");
    const mainBox = doc.querySelector('div[role="main"]');
    if (!mainBox) throw new Error("No ILT session details found.");

    const tables = mainBox.querySelectorAll("table");
    const titles = mainBox.querySelectorAll("h2.prev_session");

    const extractSessions = (table) => {
        if (!table) return [];
        return [...table.querySelectorAll("tbody tr")].map(row => {
            const cells = row.querySelectorAll("td");
            const signupHref = cells[7]?.querySelector("a.dropdown-item")?.getAttribute("href") || "";
            const urlParams = new URLSearchParams(signupHref.split('?')[1] || '');
            const sessionId = urlParams.get('s');
            return {
                name: cells[0]?.innerText.trim(),
                instructor: cells[1]?.innerText.trim() || "— — —",
                date: cells[3]?.innerText.trim(),
                time: cells[4]?.innerText.trim(),
                seats: cells[5]?.innerText.trim(),
                status: cells[6]?.innerText.trim(),
                signup: sessionId ? `${base}/mod/ilt/signup.php?s=${sessionId}&backtoallsessions=2` : ""
            };
        });
    };

    const upcoming = extractSessions(tables[0]);
    const past = extractSessions(tables[1]);

    area.innerHTML = `
        <div class="p-3 bg-white rounded-2xl shadow-lg border border-gray-100 w-100">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-icons text-[#003152] text-3xl">event_available</span>
                <h2 class="text-xl font-semibold text-[#003152]">Instructor-Led Training Sessions</h2>
            </div>

            <!-- Upcoming Sessions -->
            <section class="mb-10">
                <h3 class="text-lg font-semibold text-[#003152] mb-4 flex items-center gap-2">
                    <span class="material-icons text-[#ec9707]">upcoming</span> 
                    ${titles[0]?.innerText || 'Upcoming Sessions'}
                </h3>
                ${upcoming.length ? `
                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                        ${upcoming.map(s => `
                            <div class="flex flex-col items-start justify-between gap-4 border rounded-2xl p-4 bg-white shadow-sm hover:shadow-md transition hover:-translate-y-1">
                                <div class="flex items-center gap-4 flex-1">
                                    <div>
                                        <h4 class="text-lg gap-2 flex font-semibold text-[#003152] mb-3 items-center">
                                            <span class="bg-[#003152]/10 p-2 rounded-full material-icons text-[#003152]">school</span> 
                                            ${s.name}
                                        </h4>
                                        <p class="text-sm text-gray-600 flex items-center gap-1">
                                            <span class="material-icons text-sm text-[#ec9707]">person</span> ${s.instructor}
                                        </p>
                                        <p class="text-sm text-gray-600 flex items-center gap-1">
                                            <span class="material-icons text-sm text-[#ec9707]">calendar_month</span> ${s.date}
                                        </p>
                                        <p class="text-sm text-gray-600 flex items-center gap-1">
                                            <span class="material-icons text-sm text-[#ec9707]">schedule</span> ${s.time}
                                        </p>
                                        <p class="text-sm text-gray-700 flex items-center gap-1">
                                            <span class="material-icons text-sm text-[#ec9707]">event_seat</span>
                                            <span class="font-medium">${s.seats || 'N/A'}</span> seats available
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 w-full">
                                    <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full self-start">${s.status}</span>
                                    ${s.signup ? `
                                        <button 
                                            data-url="${s.signup}"
                                            class="signupBtn  flex items-center justify-center gap-1 bg-[#003152] hover:bg-[#ec9707] text-white px-4 py-2 rounded-md text-sm font-medium transition duration-300 w-full">
                                            <span class="material-icons text-sm">how_to_reg</span> Sign Up
                                        </button>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : `<p class="text-gray-400 italic">No upcoming sessions.</p>`}
            </section>

            <!-- Past Sessions -->
            <section>
                <h3 class="text-lg font-semibold text-[#003152] mb-4 flex items-center gap-2">
                    <span class="material-icons text-[#ec9707]">history</span> 
                    ${titles[1]?.innerText || 'Past Sessions'}
                </h3>
                ${past.length ? `
                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                        ${past.map(s => `
                            <div class="flex flex-col justify-between border rounded-2xl p-4 bg-gray-50 shadow-sm">
                                <div>
                                    <h4 class="text-gray-700 mb-3 gap-2 flex font-semibold items-center">
                                        <span class="bg-[#003152]/10 p-2 rounded-full material-icons text-[#003152]">school</span> 
                                        ${s.name}
                                    </h4>
                                    <p class="text-sm text-gray-600 flex items-center gap-1">
                                        <span class="material-icons text-sm text-[#ec9707]">calendar_month</span> ${s.date}
                                    </p>
                                    <p class="text-sm text-gray-600 flex items-center gap-1">
                                        <span class="material-icons text-sm text-[#ec9707]">schedule</span> ${s.time}
                                    </p>
                                </div>
                                <span class="text-xs bg-gray-200 text-gray-600 px-3 py-1 rounded-full mt-3 self-start">${s.status}</span>
                            </div>
                        `).join('')}
                    </div>
                ` : `<p class="text-gray-400 italic">No past sessions.</p>`}
            </section>
        </div>

        <!-- Modal -->
        <div id="iltModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden transition-opacity duration-300">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-auto max-h-[90vh] transform transition-transform scale-95">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-[#003152] flex items-center gap-2">
                        <span class="material-icons">how_to_reg</span> Sign Up for Session
                    </h3>
                    <button id="closeIltModal" class="material-icons text-gray-500 hover:text-red-500">close</button>
                </div>
                <div id="iltModalBody" class="p-4 text-center text-gray-500">
                    <span class="material-icons animate-spin text-3xl text-[#003152] mb-2">sync</span>
                    <p>Loading sign-up form...</p>
                </div>
            </div>
        </div>
    `;

    // 🎬 Modal logic
    const modal = document.getElementById("iltModal");
    const modalBody = document.getElementById("iltModalBody");
    const modalBox = modal.querySelector("div.bg-white");
    document.getElementById("closeIltModal").addEventListener("click", () => {
        modal.classList.add("hidden");
        modalBox.classList.add("scale-95");
    });

 document.querySelectorAll(".signupBtn").forEach(btn => {
    btn.addEventListener("click", async () => {
        modal.classList.remove("hidden");
        modalBox.classList.remove("scale-95");
        modalBody.innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <span class="material-icons animate-spin text-3xl text-[#003152] mb-3">sync</span>
                <p>Loading sign-up form...</p>
            </div>
        `;

        try {
            const formRes = await fetch(btn.dataset.url);
            if (!formRes.ok) throw new Error("Failed to fetch sign-up form");

            const html = await formRes.text();
            const doc = new DOMParser().parseFromString(html, "text/html");
            const main = doc.querySelector('div[role="main"]');
            modalBody.innerHTML = main ? main.innerHTML : `<p class='text-red-500'>Failed to load form.</p>`;

            const form = modalBody.querySelector("form");
            if (form) {
                form.addEventListener("submit", async (e) => {
                    e.preventDefault();

                    const formData = new FormData(form);
                    modalBody.innerHTML = `
                        <div class="text-center py-10 text-gray-500">
                            <span class="material-icons animate-spin text-3xl text-[#003152] mb-3">sync</span>
                            <p>Booking your seat...</p>
                        </div>
                    `;

                    try {
                        const response = await fetch(form.action, {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin",
                            redirect: "manual" // stop browser from following 303 automatically
                        });

                        // ✅ Detect Moodle 303 redirect = success
                        if (response.status === 303 || response.type === "opaqueredirect") {
                            modalBody.innerHTML = `
                                <div class="text-center p-8 text-green-600 animate-fade-in">
                                    <div class="flex items-center justify-center mb-4">
                                        <div class="w-20 h-20 rounded-full border-4 border-green-500 flex items-center justify-center bg-green-50 animate-bounce">
                                            <span class="material-icons text-5xl text-green-600">check</span>
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-2">Congratulations!</h3>
                                    <p class="text-green-700 text-base">Your seat has been successfully booked.</p>
                                </div>
                            `;

                            // Change button to "Booked"
                            btn.outerHTML = `
                                <span class="flex items-center justify-center gap-1 bg-green-100 text-green-700 px-4 py-2 rounded-md text-sm font-semibold w-full">
                                    <span class="material-icons text-base">event_available</span> Booked
                                </span>
                            `;

                            // Auto close modal
                            setTimeout(() => {
                                modal.classList.add("hidden");
                                modalBox.classList.add("scale-95");
                            }, 1800);

                        } else {
                            // fallback: check HTML for success text
                            const text = await response.text();
                            if (/success|booked|enrolled|signed\s*up|registered/i.test(text)) {
                                modalBody.innerHTML = `
                                    <div class="text-center p-8 text-green-600 animate-fade-in">
                                        <div class="flex items-center justify-center mb-4">
                                            <div class="w-20 h-20 rounded-full border-4 border-green-500 flex items-center justify-center bg-green-50 animate-bounce">
                                                <span class="material-icons text-5xl text-green-600">check</span>
                                            </div>
                                        </div>
                                        <h3 class="text-2xl font-semibold mb-2">Congratulations!</h3>
                                        <p class="text-green-700 text-base">Your seat has been successfully booked.</p>
                                    </div>
                                `;

                                btn.outerHTML = `
                                    <span class="flex items-center justify-center gap-1 bg-green-100 text-green-700 px-4 py-2 rounded-md text-sm font-semibold w-full">
                                        <span class="material-icons text-base">event_available</span> Booked
                                    </span>
                                `;

                                setTimeout(() => {
                                    modal.classList.add("hidden");
                                    modalBox.classList.add("scale-95");
                                }, 1800);
                            } else {
                                throw new Error("Unexpected response");
                            }
                        }
                    } catch (err) {
                        console.error(err);
                        modalBody.innerHTML = `
                            <div class="text-center p-8 text-red-600">
                                <span class="material-icons text-6xl mb-3">error_outline</span>
                                <h3 class="text-xl font-semibold mb-2">Submission Failed</h3>
                                <p>Unable to complete booking. Please try again later.</p>
                            </div>
                        `;
                    }
                });
            }

        } catch (e) {
            console.error(e);
            modalBody.innerHTML = `
                <div class="text-center text-red-500 p-6">
                    <span class="material-icons text-4xl mb-2">error_outline</span>
                    <p>Failed to load sign-up form.</p>
                </div>
            `;
        }
    });
});


} catch (err) {
    console.error(err);
    area.innerHTML = `<p class="text-red-500 text-center p-6">Error loading ILT sessions.</p>`;
}

}


            } catch (err) {
                console.error(err);
                area.innerHTML = '<div class="text-red-400 p-8">Failed to load content.</div>';
            }
        });
    });

    // 📢 Dynamic Announcements (Moodle Forum Integration)
    const announcementBtn = document.getElementById('announcementBtn');
    const contentArea = document.getElementById('content-area');


 if (announcementBtn) {
    announcementBtn.addEventListener('click', async () => {
        contentArea.innerHTML = `<div class="text-gray-400 p-8 text-center">Loading Announcements...</div>`;

        try {
            const response = await fetch('<?= $CFG->wwwroot ?>/local/incourse/fetch_announcements.php?id=<?= $courseid ?>');
            const data = await response.json();

            if (!data.forums || data.forums.length === 0) {
                contentArea.innerHTML = `<div class="text-gray-500 p-8 text-center">No announcements found.</div>`;
                return;
            }

            // Pagination setup
            let currentPage = 1;
            const perPage = 4;
            let filteredData = data.forums;

            // Render announcements list
            const renderAnnouncements = () => {
                const start = (currentPage - 1) * perPage;
                const end = start + perPage;
                const pageData = filteredData.slice(start, end);

                let html = `
                <div class="max-w-6xl mx-auto py-8 font-display">
                    <header class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                        <div class="flex items-center gap-3 text-gray-800 dark:text-white">
                            <span class="material-symbols-outlined text-primary" style="font-size:32px;">campaign</span>
                            <h1 class="text-2xl font-bold tracking-tight">Announcements</h1>
                        </div>
                    </header>

                    <!-- Search + Filter -->
                    <div class="flex flex-col md:flex-row gap-4 mb-6">
                        <div class="flex-1">
                            <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg shadow-sm h-12">
                                <span class="material-symbols-outlined text-gray-400 dark:text-gray-500 pl-4">search</span>
                                <input id="announcementSearch"
                                    class="flex-1 bg-transparent border-none focus:ring-0 px-2 text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500"
                                    placeholder="Search announcements..."/>
                            </div>
                        </div>
                        <div>
                            <select id="dateFilter"
                                class="h-12 rounded-lg bg-white dark:bg-gray-800 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm">
                                <option value="all">All Dates</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                        <div class="hidden md:grid grid-cols-[3fr_1fr_1fr_1fr] gap-4 p-3"style="background:#003152">
                            <div class="text-sm text-left font-semibold text-white px-4">Topic</div>
                            <div class="text-sm font-semibold text-white text-center">Started by</div>
                            <div class="text-sm font-semibold text-white text-center">Replies</div>
                            <div class="text-sm font-semibold text-white text-center">Last Post</div>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">`;

                pageData.forEach(topic => {
                    const now = new Date();
                    const lastPostDate = new Date(topic.lastposttimestamp * 1000);
                    const diffDays = Math.floor((now - lastPostDate) / (1000 * 60 * 60 * 24));

                    let icon = "check_circle", colorClass = "text-green-500";
                    if (diffDays > 10) { icon = "radio_button_unchecked"; colorClass = "text-gray-400"; }
                    else if (diffDays >= 4 && diffDays <= 10) { icon = "error"; colorClass = "text-red-500"; }

                    html += `
                        <div class="grid grid-cols-1 md:grid-cols-[3fr_1fr_1fr_1fr] items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer topic-item"
                             data-discussionid="${topic.discussionid}">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined ${colorClass} text-2xl">${icon}</span>
                                <div class="flex-1 text-left">
                                    <p class="font-bold text-gray-900 dark:text-white">${topic.name}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">${topic.author}</p>
                                </div>
                            </div>
                            <div class="text-center text-sm text-gray-600 dark:text-gray-300">${topic.author}</div>
                            <div class="text-center text-sm font-medium text-gray-800 dark:text-gray-100">${topic.replies}</div>
                            <div class="text-center text-sm text-gray-600 dark:text-gray-300">
                                <p>${topic.lastpostauthor}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">${topic.created}</p>
                            </div>
                        </div>`;
                });

                html += `
                        </div>
                    </div>
                    <div class="flex justify-center mt-6 gap-2">`;

                const totalPages = Math.ceil(filteredData.length / perPage);
                for (let i = 1; i <= totalPages; i++) {
                    html += `<button class="page-btn px-3 py-1 rounded-md text-sm font-medium ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'}">${i}</button>`;
                }

                html += `
                    </div>
                </div>

                <!-- Discussion Modal -->
                <div id="discussion-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 opacity-0 pointer-events-none transition-all duration-300">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl transform scale-95 transition-transform duration-300 overflow-hidden">
                        <div id="discussion-content" class="p-6 max-h-[80vh] overflow-y-auto text-gray-800 dark:text-gray-200">
                            <div class="text-center text-gray-400">Loading discussion...</div>
                        </div>
                        <div class="flex justify-end gap-3 p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <button class="h-10 px-4 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors" onclick="closeDiscussionModal()">Close</button>
                        </div>
                    </div>
                </div>`;

                contentArea.innerHTML = html;
    // ✅ Add these lines RIGHT HERE
    const searchInput = document.getElementById('announcementSearch');
    const dateSelect = document.getElementById('dateFilter');
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (dateSelect) dateSelect.addEventListener('change', applyFilters);
                // Pagination buttons
                document.querySelectorAll('.page-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        currentPage = parseInt(btn.textContent);
                        renderAnnouncements();
                        
                    });
                });

                // Topic click -> load discussion inside modal
                document.querySelectorAll('.topic-item').forEach(item => {
                    item.addEventListener('click', async () => {
                        const id = item.dataset.discussionid;
                        openDiscussionModal();

                        const discussionContainer = document.getElementById('discussion-content');
                        discussionContainer.innerHTML = `<div class="text-gray-400 text-center py-8">Loading discussion...</div>`;

                        try {
                            const res = await fetch('<?= $CFG->wwwroot ?>/local/incourse/fetch_discussion.php?id=' + id);
                            const discussion = await res.text();
                            discussionContainer.innerHTML = discussion;

                            // ✅ Keep Reply button logic working inside modal
                            discussionContainer.querySelectorAll('.reply-btn').forEach(btn => {
                                btn.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    const postId = btn.dataset.postid;
                                    let postDiv = btn.closest('div.mb-4');
                                    if (postDiv.querySelector('.reply-box')) return;

                                    const replyBox = document.createElement('div');
                                    replyBox.className = 'reply-box mt-3';
                                    replyBox.innerHTML = `
                                        <textarea class="form-control mb-2 w-full p-2 border rounded" rows="3" placeholder="Write your reply..."></textarea>
                                        <div class="flex gap-2 mt-2">
                                            <button style="background:#003152;" class=" text-white px-3 py-1 rounded submit-reply">Post to forum</button>
                                            <button class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded cancel-reply">Cancel</button>
                                        </div>
                                    `;
                                    postDiv.appendChild(replyBox);

                                    replyBox.querySelector('.cancel-reply').addEventListener('click', () => replyBox.remove());

                                    replyBox.querySelector('.submit-reply').addEventListener('click', async () => {
                                        const message = replyBox.querySelector('textarea').value.trim();
                                        if (!message) return alert('Please enter a reply.');

                                        const formData = new FormData();
                                        formData.append('postid', postId);
                                        formData.append('message', message);

                                        const res = await fetch('<?= $CFG->wwwroot ?>/local/incourse/submit_reply.php', {
                                            method: 'POST',
                                            body: formData
                                        });
                                        const result = await res.json();

                                        if (result.status === 'success') {
                                            const newReply = document.createElement('div');
                                            newReply.className = 'mt-4 ms-5 ps-4 border-l-2 border-gray-300';
                                            newReply.innerHTML = `
                                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg shadow-sm">
                                                    <p class="font-semibold text-primary mb-1">Re: Reply</p>
                                                    <p class="text-xs text-gray-400 mb-2">by You — just now</p>
                                                    <div class="text-gray-800 dark:text-gray-200 text-sm">${message}</div>
                                                </div>`;
                                            postDiv.appendChild(newReply);
                                            replyBox.remove();
                                        } else {
                                            alert('Failed to submit reply.');
                                        }
                                    });
                                });
                            });

                        } catch (err) {
                            discussionContainer.innerHTML = `<div class="text-red-400 text-center py-8">Error loading discussion.</div>`;
                        }
                    });
                });
  
            };

            // Apply filters
            const applyFilters = () => {
                const search = document.getElementById('announcementSearch').value.toLowerCase();
                const dateFilter = document.getElementById('dateFilter').value;
                const now = new Date();

                filteredData = data.forums.filter(f => {
                    const titleMatch = f.name.toLowerCase().includes(search) || f.author.toLowerCase().includes(search);
                    const postDate = new Date(f.lastposttimestamp * 1000);
                    const diffDays = (now - postDate) / (1000 * 60 * 60 * 24);

                    let dateMatch = true;
                    if (dateFilter === 'today') dateMatch = diffDays < 1;
                    else if (dateFilter === 'week') dateMatch = diffDays <= 7;
                    else if (dateFilter === 'month') dateMatch = diffDays <= 30;
                    

                    return titleMatch && dateMatch;
                });

                currentPage = 1;
                renderAnnouncements();
                
            };

        

            // Modal open/close
            window.openDiscussionModal = function () {
                const modal = document.getElementById('discussion-modal');
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.querySelector('div').classList.remove('scale-95');
            };
            window.closeDiscussionModal = function () {
                const modal = document.getElementById('discussion-modal');
                modal.classList.add('opacity-0');
                modal.querySelector('div').classList.add('scale-95');
                setTimeout(() => modal.classList.add('pointer-events-none'), 300);
            };

            // Initial render
            renderAnnouncements();

        } catch (err) {
            console.error(err);
            contentArea.innerHTML = `<div class="text-red-400 p-8 text-center">Failed to load announcements.</div>`;
        }
    });
}
  
});
// ✅ Highlight current clicked activity + accordion + auto-scroll (safe)
document.addEventListener("DOMContentLoaded", () => {

    const links = document.querySelectorAll(".activity-link");

    links.forEach(link => {
        link.addEventListener("click", () => {

            // Remove previous highlight
            document.querySelectorAll(".courseindex-active")
                .forEach(el => el.classList.remove("courseindex-active"));

            // Add highlight to clicked item
            link.classList.add("courseindex-active");

            //  Auto-open its accordion section
            let section = link.closest(".accordion-content");
            if (section && section.classList.contains("hidden")) {
                section.classList.remove("hidden");

                let icon = section.previousElementSibling.querySelector(".material-icons");
                if (icon) icon.style.transform = "rotate(90deg)";
            }

            //  Highlight accordion header
            let header = section ? section.previousElementSibling : null;
            if (header) {
                document.querySelectorAll(".accordion-header-active")
                    .forEach(h => h.classList.remove("accordion-header-active"));

                header.classList.add("accordion-header-active");
            }

            //  SAFE AUTO SCROLL (NO FOOTER SPACE)
            setTimeout(() => safeScroll(link), 80);

        });
    });


    //  Auto highlight when loading activity page
    const currentUrl = window.location.href;
    links.forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add("courseindex-active");

            let section = link.closest(".accordion-content");
            if (section && section.classList.contains("hidden")) {
                section.classList.remove("hidden");

                let icon = section.previousElementSibling.querySelector(".material-icons");
                if (icon) icon.style.transform = "rotate(90deg)";
            }

            let header = section ? section.previousElementSibling : null;
            if (header) {
                document.querySelectorAll(".accordion-header-active")
                    .forEach(h => h.classList.remove("accordion-header-active"));
                header.classList.add("accordion-header-active");
            }

            //  SAFE SCROLL ON LOAD
            setTimeout(() => safeScroll(link), 120);
        }
    });

});


// --------------------------------------------
//  SAFE SCROLL FUNCTION — NO BOTTOM GAP
// --------------------------------------------
function safeScroll(element) {
    const container = document.querySelector(".your-left-panel-container-selector");

    // If not in scroll container → normal safe scroll
    if (!container) {
        element.scrollIntoView({ behavior: "smooth", block: "nearest" });
        return;
    }

    // Safe scroll inside fixed-height panel
    const offsetTop = element.offsetTop - container.offsetHeight / 3;

    container.scrollTo({
        top: offsetTop,
        behavior: "smooth"
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const cmid = params.get('cmid');

    if (!cmid) return;

    // Wait until activity links are registered
    const tryOpen = () => {
        const link = document.querySelector(`.activity-link[data-cmid="${cmid}"]`);
        if (link) {
            link.click(); //  reuse your entire inline system
        } else {
            setTimeout(tryOpen, 300);
        }
    };

    tryOpen();
});

</script>


<?php echo $OUTPUT->footer(); ?>
