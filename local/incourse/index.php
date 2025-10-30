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
   .courseindex-active {
    background: #2e3740 !important;
    border-radius: 8px;
        border: solid 1px hsl(45deg 93% 47% / 30%);
}

.courseindex-active span,
.courseindex-active div,
.courseindex-active a {
    color: #fff !important;
}

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
            <img src="<?php echo $courseimage; ?>" class="w-full h-48 object-cover rounded-tr-2xl" alt="Course banner">
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
            <h1 class="text-3xl font-bold text-text-light dark:text-text-dark">
                <?php echo format_string($course->fullname); ?>
            </h1>

            <?php if (!empty(trim(strip_tags($course->summary)))): ?>
                <p class="mt-2 text-subtext-light dark:text-subtext-dark">
                    <?php echo format_text($course->summary, FORMAT_HTML); ?>
                </p>
            <?php else: ?>
                <p class="mt-2 text-subtext-light dark:text-subtext-dark">No course summary available.</p>
            <?php endif; ?>

            <div class="flex items-center mt-4 text-sm text-subtext-light dark:text-subtext-dark space-x-4">
                <div class="flex items-center">
                    <span class="material-icons text-accent-light mr-1" style="color:#ec9707;">star</span>
                    <span class="font-bold"><?php echo rand(4,5) . '.' . rand(0,9); ?></span>
                    <span>(<?php echo number_format(rand(200,10000)); ?> ratings)</span>
                </div>
                <span><?php echo rand(5,20) . "h total"; ?></span>
                <span>Updated <?php echo rand(1,7); ?>d ago</span>
                <span><?php echo rand(3,10); ?>+ languages</span>
            </div>

            <div class="mt-6 flex items-center space-x-2">
                <button id="announcementBtn" class="flex items-center bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm" style="border-color:#ec9707;">
                    <span class="material-icons text-accent-light mr-2" style="color:#ec9707;">campaign</span>
                    Announcements
                </button>
                <button style="border-color:#ec9707;" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-2 text-sm">
                    <span class="material-icons" style="color:#ec9707;">expand_more</span>
                </button>
            </div>
        </div>

        <!-- Dynamic Content Area -->
        <div id="content-area" class="flex-grow flex flex-col items-center justify-center text-center p-8">
            <span class="material-icons text-6xl text-gray-400 mb-4">play_circle</span>
            <h2 class="text-2xl font-semibold text-text-light dark:text-text-dark">Select a lesson to begin</h2>
            <p class="mt-2 max-w-md text-subtext-light dark:text-subtext-dark">
                Choose a lesson from the sidebar to view its content here.
            </p>
        </div>
    </main>
</div>

<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 📜 Inline module content loader (certificates, lessons, etc.)
    document.querySelectorAll('.activity-link').forEach(link => {
        link.addEventListener('click', async e => {
            e.preventDefault();
            const area = document.getElementById('content-area');
            const modname = link.dataset.modname;
            const cmid = link.dataset.cmid;
            
            area.innerHTML = '<div class="text-gray-400 p-8">Loading Content...</div>';

            try {
                // 🎓 Handle customcert or iomadcertificate (inline certificate viewer)
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

                    // ✅ Keep inline certificate + download icon
                    area.innerHTML = `
                        <div class="relative w-full rounded-lg overflow-hidden flex flex-col items-center justify-center"
                             style="padding:60px 0;background:#fff;border:2px solid #ec9707;">
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
                            <div style="background:#fff;width:60%;border-radius:12px;border:solid 4px #ec9707;padding:0;">
                                <img src="${imgUrl}" alt="Certificate" style="width:100%;border-radius:8px;"/>
                            </div>
                        </div>`;
                    return;
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
            <div id="pdfContainer" class="flex flex-col items-center justify-center w-100 p-0 " style="position: relative;left: 90px;    overflow: hidden;>
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
                <div class="text-center w-full" style="position: relative;
    right: 170px;
    top: -50px;
    overflow: hidden;" >
                    <iframe src="${pdfUrl}" style=" position: relative;left: 100px;"  class="w-full h-[80vh] rounded-lg border" allowfullscreen></iframe>
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
                    <div class="bg-gray-200 rounded-full p-5 mb-5">
                        <span class="material-icons text-gray-700 text-4xl">school</span>
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

            // ▶️ Start button action based on backend flag
            document.getElementById('startScorm').addEventListener('click', () => {
                if (data.openinnewtab) {
                    // Open in new tab if backend says so
                    window.open(data.launchurl, '_blank');
                    area.innerHTML = `
                        <div class="text-center text-gray-500 p-8">
                            <h2 class="text-lg font-semibold mb-2">SCORM Activity Opened in New Tab</h2>
                            <p>You can continue learning in the new tab that was opened.</p>
                        </div>
                    `;
                } else {
                    // Otherwise, open inline (iframe view)
                    area.innerHTML = `
                        <div class="flex items-center gap-3 mb-4">
                            <button id="backToCourse"
                                class="flex items-center text-[#003152] hover:text-[#ec9707] font-medium transition">
                                <span class="material-icons mr-1">arrow_back</span>Back to Course
                            </button>
                        </div>
                        <div id="scormContainer"
                            class="rounded-lg border border-gray-200 bg-white overflow-hidden"
                            style="width:100%">
                            <iframe
                                src="${data.launchurl}"
                                class="w-full h-[85vh] border-0 bg-white"
                                allowfullscreen
                                allow="fullscreen; autoplay; encrypted-media">
                            </iframe>
                        </div>
                    `;

                    document.getElementById('backToCourse').addEventListener('click', () => {
                        window.location.reload();
                    });
                }
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
// ✅ Highlight current clicked activity
document.addEventListener("DOMContentLoaded", () => {

    const links = document.querySelectorAll(".activity-link");

    links.forEach(link => {
        link.addEventListener("click", (e) => {

            // Remove previous highlight
            document.querySelectorAll(".courseindex-active").forEach(el => {
                el.classList.remove("courseindex-active");
            });

            // Add highlight to clicked item
            link.classList.add("courseindex-active");
        });
    });

    // ✅ Auto highlight when coming from activity page (URL match)
    const currentUrl = window.location.href;
    links.forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add("courseindex-active");

            // auto open section so user sees it
            let section = link.closest(".accordion-content");
            if (section && section.classList.contains("hidden")) {
                section.classList.remove("hidden");
                let icon = section.previousElementSibling.querySelector(".material-icons");
                if (icon) icon.style.transform = "rotate(90deg)";
            }
        }
    });

});


</script>


<?php echo $OUTPUT->footer(); ?>
