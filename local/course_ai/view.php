<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/course_ai/lib.php');
require_login();
global $PAGE;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$title = get_string('viewcourse', 'local_course_ai');

$PAGE->set_url(new moodle_url('/local/course_ai/view.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js(new moodle_url('https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js'));
// Local styles
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'));

$PAGE->requires->css(new moodle_url('/local/course_ai/css/styles.css'));
$PAGE->requires->js_call_amd('local_course_ai/main', 'init');
$PAGE->requires->jquery();


echo $OUTPUT->header();
?>
<style>
    .path-local-course_ai div[role="main"] {
        padding: 0px !important;
    }

    article.course-view-card {
        position: relative;
        bottom: 100px;
    }

    .summary-2-line p,
    .truncate-2-lines {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .summary-2-line div {
        display: none;
    }

    .summary-2-line p[dir="ltr"] {
        display: -webkit-box !important;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #page-local-course_ai-view #page-wrapper #page .content .card p {
        padding: 0px;
        margin-bottom: 0px;
        font-size: 12px;
        opacity: 0.8;
    }

    #page-local-course_ai-view #page-wrapper #page {
        .course-name-text {
            font-size: 13px;
        }
    }

    title[emailForm] {}
</style>

<body>
    <div id="aicourseforamt">
        <div class="modal1">
            <article class="modal-container course-view-card">
                <header class="modal-container-header">
                    <div class="header-c">
                        <h1 class="modal-container-title">
                            <a class="icon-button1" href="#" data-bs-toggle="modal" data-bs-target="#exampleModal"> <svg
                                    viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                    data-testid="DiscoIcon.arrow-stem-left"
                                    class="root-0-4-376 root-d97-0-4-2490 root-0-4-374 root-d97-0-4-2489">
                                    <path d="M9.57 5.93 3.5 12l6.07 6.07M20.5 12H3.67" stroke="currentColor"
                                        stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                        stroke-linejoin="round"></path>
                                </svg>
                            </a>
                            <span id="modalTitle1"></span>

                        </h1>
                        <p id="modalDescription1">
                        </p>
                    </div>
                    <button class="icon-button icon-button-close">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 4 4 20M4 4l16 16" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </header>
                <section class="modal-container-body rtf" id="modalBodyContent1">

                </section>
                <footer class="modal-container-footer" style="height: 50px;">
                    <div class="buttons-0-4-823 buttons-d64-0-4-8174" data-testid="CreatePathwayModal.modal.buttons"
                        id="modalFooterButtons1">
                    </div>
                </footer>
            </article>
        </div>

        <div class="modal2" id="dynamicModal">
            <article class="modal-container course-view-card pt-2">
                <header class="modal-container-header">
                    <div class="header-c">
                        <h1 class="modal-container-title" id="modalTitle">Default
                            Title</h1>
                        <p id="modalDescription">Default Description</p>
                    </div>
                    <button class="icon-button" onclick="closeModal()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 4 4 20M4 4l16 16" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </header>
                <section class="modal-container-body rtf" id="modalBodyContent">
                    <p>Default Content</p>
                </section>
                <footer class="modal-container-footer" style="height: 50px;">
                    <div class="buttons-0-4-823 buttons-d64-0-4-8174" id="modalFooterButtons">
                        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button class="btn btn-primary">Next</button>
                    </div>
                </footer>
            </article>
        </div>

        <div class="d-flex" style="">
            <div class="sidebar334 course-view-card">
                <div class="d-flex justify-content-between align-items-center  headerContainer-0-4-521 ">
                    <div class="container-0-4-525"></div>
                    <div class="iconContainer-0-4-526">
                        <svg class="root-d9-0-4-578" fill="#003152" viewBox="0 0 32 32" version="1.1"
                            xmlns="http://www.w3.org/2000/svg" stroke="#003152">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <title>book</title>
                                <path
                                    d="M0 23.008v-17.984q0-1.024 0.48-1.888t1.28-1.44q1.024-0.672 2.24-0.672 0.736 0 1.472 0.256l10.016 4q0.064 0.032 0.512 0.32 0.448-0.288 0.512-0.32l10.016-4q0.736-0.256 1.472-0.256 1.248 0 2.24 0.672 0.832 0.576 1.312 1.44t0.448 1.888v17.984q0 1.248-0.672 2.24t-1.856 1.472l-9.984 4q-0.736 0.288-1.472 0.288-1.024 0-2.016-0.576-0.992 0.576-1.984 0.576-0.768 0-1.504-0.288l-9.984-4q-1.152-0.448-1.824-1.472t-0.704-2.24zM4 23.008l10.016 4v-17.984l-10.016-4v17.984zM6.016 21.824v-2.016l5.984 2.4v2.016zM6.016 17.824v-2.016l5.984 2.4v2.016zM6.016 13.824v-2.016l5.984 2.4v2.016zM6.016 9.824v-2.016l5.984 2.4v2.016zM18.016 27.008l9.984-4v-17.984l-9.984 4v17.984zM20 24.224v-2.016l6.016-2.4v2.016zM20 20.224v-2.016l6.016-2.4v2.016zM20 16.224v-2.016l6.016-2.4v2.016zM20 12.224v-2.016l6.016-2.4v2.016z">
                                </path>
                            </g>
                        </svg>
                    </div>

                    <h5 class="body-md-500-0-4-276 ">Learning</h5>
                    <div class role="group">
                        <?php if (is_siteadmin()) { ?>
                            <button type="button" style class="btn btn-sm btn-primary" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                +
                            </button>
                            <?php
                        } ?>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exampleModal"
                                    href="#">Add
                                    Course</a></li>
                            <li><a class="dropdown-item open-modal2" href="#">Add
                                    Pathway</a></li>
                            <li><a class="dropdown-item open-modal1" id="addSection" href="#">Add Section</a></li>
                        </ul>
                    </div>
                </div>

                <!-- popup for add courses -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content container-d527-0-4-23131">
                            <div class="modal-header border-0">
                                <h1 class="modal-title fs-5" id="exampleModalLabel">New Course</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Build a thriving learning Community by
                                    creating unique Course. Start fresh, or
                                    use one of our templates to get a quick
                                    start.</p>
                                <!-- open modal blanck -->
                                <div class="col-md-12 d-flex pop-gap">
                                    <div class="col-6">
                                        <div class="card popc-card openModalblanck w-100">
                                            <div class="heading d-flex align-items-center gap-3"><svg width="40"
                                                    height="40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect width="40" height="40" rx="20" fill="#41D9F1"></rect>
                                                    <path
                                                        d="M11.268 12.876a268.378 268.378 0 0 0 .085 17.035c.018.49.433.915.924.943a138 138 0 0 0 12.761.123.97.97 0 0 0 .914-.937 399.23 399.23 0 0 0 .059-17.258.956.956 0 0 0-.918-.928c-4.3-.117-8.6-.086-12.9.092a.977.977 0 0 0-.925.93Z"
                                                        fill="url(#blank_svg__a)" fill-opacity="0.6"></path>
                                                    <path
                                                        d="M14.056 10.02a251.991 251.991 0 0 0 0 17.547.894.894 0 0 0 .821.843c1.762.103 3.524.164 5.286.183a2.312 2.312 0 0 0 1.683-.709 2.489 2.489 0 0 0 .708-1.727c.004-.993.008-1.985.01-2.978a.819.819 0 0 1 .813-.816 316.2 316.2 0 0 0 2.97-.033 2.516 2.516 0 0 0 1.725-.731 2.4 2.4 0 0 0 .726-1.695c.015-3.294-.036-6.589-.15-9.883a.893.893 0 0 0-.822-.842 110.5 110.5 0 0 0-12.949 0 .894.894 0 0 0-.821.842Z"
                                                        fill="url(#blank_svg__b)"></path>
                                                    <path
                                                        d="M27.21 21.783c-.671-.032-3.98-.663-3.982-.668-1.088-.187-1.996.767-1.859 1.866.001 0 .385 3.318.431 3.993.033.459-.106.89-.334 1.259a2.49 2.49 0 0 0 1.137-2.076c.005-.993.008-1.985.01-2.978a.819.819 0 0 1 .813-.816c.99-.006 1.98-.017 2.97-.033a2.514 2.514 0 0 0 1.698-.706 1.992 1.992 0 0 1-.883.159Z"
                                                        fill="#fff" fill-opacity="0.2"></path>
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="m21.47 28.207-.01.014.023-.022-.013.008Zm.684-.678c.263-.393.42-.868.423-1.378.004-.992.008-1.985.01-2.977a.819.819 0 0 1 .812-.816c.99-.007 1.98-.018 2.97-.033a2.51 2.51 0 0 0 1.635-.647l-5.85 5.85Z"
                                                        fill="#fff"></path>
                                                    <defs>
                                                        <lineargradient id="blank_svg__a" x1="3.631" y1="36.442"
                                                            x2="24.106" y2="15.968" gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#fff"></stop>
                                                            <stop offset="1" stop-color="#fff"></stop>
                                                        </lineargradient>
                                                        <lineargradient id="blank_svg__b" x1="22.491" y1="28.015"
                                                            x2="14.326" y2="8.989" gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#fff" stop-opacity="0.2"></stop>
                                                            <stop offset="1" stop-color="#fff"></stop>
                                                        </lineargradient>
                                                    </defs>
                                                </svg>
                                                <strong class="card-title">Doc Based Course Generator</strong>
                                            </div>
                                            <p class="card-text body-xs-0-4-291 mb-3">Build
                                                a
                                                product from scratch using
                                                Disco Apps.</p>
                                        </div>
                                    </div>
                                    <!-- openModalbadges -->
                                    <div class="col-6">
                                        <a href="#">
                                            <div class="card popc-card openModalbadges w-100">
                                                <div class="heading d-flex align-items-center gap-3"><svg width="40"
                                                        height="40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect width="40" height="40" rx="20" fill="#966BD8"></rect>
                                                        <rect x="16.941" y="29.155" width="6.112" height="1.667"
                                                            rx="0.387" fill="#fff"></rect>
                                                        <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M17.032 18.439a.557.557 0 0 1 .12-.608l3.284-3.283a.557.557 0 1 1 .788.788l-2.332 2.332h3.365a.557.557 0 0 1 .394.951l-3.284 3.284a.557.557 0 1 1-.788-.788l2.332-2.332h-3.364a.557.557 0 0 1-.515-.344Z"
                                                            fill="#fff"></path>
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M23.61 26.752a9.17 9.17 0 0 0-3.612-17.596 9.167 9.167 0 0 0-3.611 17.596v1.77c0 .044.034.078.077.078h7.068a.078.078 0 0 0 .077-.077v-1.771Zm-6.578-8.311a.558.558 0 0 1 .121-.607l3.284-3.284a.557.557 0 1 1 .788.788l-2.332 2.332h3.364a.557.557 0 0 1 .394.952l-3.284 3.283a.557.557 0 1 1-.788-.788l2.333-2.332h-3.365a.557.557 0 0 1-.515-.344Z"
                                                            fill="url(#cohort-based-course_svg__a)"></path>
                                                        <defs>
                                                            <lineargradient id="cohort-based-course_svg__a" x1="19.998"
                                                                y1="9.156" x2="19.999" y2="35.401"
                                                                gradientUnits="userSpaceOnUse">
                                                                <stop stop-color="#fff"></stop>
                                                                <stop offset="1" stop-color="#fff" stop-opacity="0">
                                                                </stop>
                                                            </lineargradient>
                                                        </defs>
                                                    </svg>
                                                    <strong class="card-title">AI Course Generator </strong>
                                                </div>
                                                <p class="card-text body-xs-0-4-291">A
                                                    social
                                                    and collaborative
                                                    learning
                                                    experience that takes
                                                    place
                                                    over a period of days or
                                                    weeks.</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4 d-flex pop-gap justify-content-center">
                                    <div class="col-6">
                                        <div class="card popc-card openModalevent w-100">
                                            <div class="heading d-flex align-items-center gap-3">
                                                <svg width="40" height="40" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <rect width="40" height="40" rx="20" fill="#F8C354"></rect>
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M13.908 28.88a.037.037 0 0 0-.037.037v1.036c0 .02.016.037.037.037H26.61c.02 0 .037-.016.037-.037v-1.037a.037.037 0 0 0-.037-.037h-3.148a.149.149 0 0 1-.149-.148v-4.259a.037.037 0 0 0-.037-.037H17.24a.037.037 0 0 0-.037.037v4.259a.149.149 0 0 1-.148.148h-3.147Z"
                                                        fill="url(#self-paced-course_svg__a)"></path>
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M10.724 11.657a.743.743 0 0 0-.743.744v12.957c0 .41.333.743.743.743h18.513c.41 0 .744-.333.744-.743V12.4a.743.743 0 0 0-.744-.744H10.724Zm3.702 3.927c0-.02.016-.037.037-.037h11.036c.02 0 .038.017.038.037v1.037c0 .02-.017.037-.038.037H14.463a.037.037 0 0 1-.037-.037v-1.037Zm0 3.89c0-.021.016-.038.037-.038h7.147c.021 0 .038.017.038.037v1.037c0 .02-.017.037-.038.037h-7.147a.037.037 0 0 1-.037-.037v-1.037Z"
                                                        fill="url(#self-paced-course_svg__b)"></path>
                                                    <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M14.463 15.547a.037.037 0 0 0-.038.037v1.037c0 .02.017.037.037.037H25.5c.02 0 .037-.016.037-.037v-1.036a.037.037 0 0 0-.037-.038H14.463Zm0 3.89a.037.037 0 0 0-.038.036v1.037c0 .02.017.037.037.037h7.148c.02 0 .037-.016.037-.037v-1.037a.037.037 0 0 0-.037-.037h-7.147Z"
                                                        fill="#fff"></path>
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M10.055 11.657a.074.074 0 0 0-.074.075v13.184c0 .04.033.074.074.074h13.767a.075.075 0 0 0 .068-.043l6.042-13.184a.074.074 0 0 0-.067-.106h-19.81Zm4.37 3.927c0-.02.017-.037.038-.037h11.036c.02 0 .038.017.038.037v1.037c0 .02-.017.037-.038.037H14.463a.037.037 0 0 1-.037-.037v-1.037Zm0 3.89c0-.021.017-.038.038-.038h7.147c.021 0 .038.017.038.037v1.037c0 .02-.017.037-.038.037h-7.147a.037.037 0 0 1-.037-.037v-1.037Z"
                                                        fill="url(#self-paced-course_svg__c)"></path>
                                                    <defs>
                                                        <lineargradient id="self-paced-course_svg__a" x1="23.037"
                                                            y1="29.99" x2="23.037" y2="24.713"
                                                            gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#fff"></stop>
                                                            <stop offset="1" stop-color="#fff" stop-opacity="0"></stop>
                                                        </lineargradient>
                                                        <lineargradient id="self-paced-course_svg__b" x1="9.981"
                                                            y1="11.657" x2="34.147" y2="26.101"
                                                            gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#fff"></stop>
                                                            <stop offset="1" stop-color="#fff" stop-opacity="0.5">
                                                            </stop>
                                                        </lineargradient>
                                                        <lineargradient id="self-paced-course_svg__c" x1="24.284"
                                                            y1="24.712" x2="10.376" y2="11.249"
                                                            gradientUnits="userSpaceOnUse">
                                                            <stop stop-color="#fff"></stop>
                                                            <stop offset="1" stop-color="#fff" stop-opacity="0"></stop>
                                                        </lineargradient>
                                                    </defs>
                                                </svg>
                                                <strong class="card-title">Manual Course creator</strong>
                                            </div>
                                            <p class="card-text body-xs-0-4-291">A
                                                series of
                                                events that focus on a
                                                specific topic and occur
                                                with a regular cadence.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end popup -->
                <div class="scrollContainer-0-4-522">
                    <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist"
                        aria-orientation="vertical">
                        <button class="nav-link mt-3 explore-btn active d-flex justify-content-between"
                            id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-home" type="button"
                            role="tab" aria-controls="v-pills-home" aria-selected="true">
                            <div class="header-left">
                                <svg viewBox="0 0 24 24" fill="#fff" stroke="#003152" xmlns="http://www.w3.org/2000/svg"
                                    data-testid="DiscoIcon.map.active" class="root-d9-0-4-578">
                                    <path
                                        d="M7.63 3.57c.178-.098.37.052.37.257v13.556c0 .223-.153.412-.35.516a1.448 1.448 0 0 0-.02.01l-2.35 1.34c-1.64.94-2.99.16-2.99-1.74V7.78c0-.63.45-1.41 1.01-1.73l4.33-2.48ZM14.722 6.103A.5.5 0 0 1 15 6.55v13.153a.5.5 0 0 1-.717.45l-4.25-2.047a.5.5 0 0 1-.283-.45V4.447a.5.5 0 0 1 .722-.449l4.25 2.105ZM22 6.49v9.73c0 .63-.45 1.41-1.01 1.73l-3.491 2.001a.5.5 0 0 1-.749-.434V6.33a.5.5 0 0 1 .252-.434L19.01 4.75C20.65 3.81 22 4.59 22 6.49Z">
                                    </path>
                                </svg>
                                <h5 class="body-md-500-0-4-276 ms-md-2 ms-sm-1">Explore</h5>
                            </div>
                            <a type="button" class="btn more-btn-1 border-0" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <span class="MuiIconButton-label-132"><svg viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="moreIcon-0-4-618">
                                        <path
                                            d="M4 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z"
                                            fill="currentColor" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">
                                        <div class="content-0-4-1531 content-d4-0-4-1548">
                                            <div class="lhs-0-4-1534"><span class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                        viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        data-testid="DiscoIcon.eye-off"
                                                        class="root-0-4-376 root-d56-0-4-1553 root-0-4-374 root-d56-0-4-1552">
                                                        <path d="m14.53 9.47-5.06 5.06a3.576 3.576 0 1 1 5.06-5.06Z"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path
                                                            d="M17.82 5.77C16.07 4.45 14.07 3.73 12 3.73c-3.53 0-6.82 2.08-9.11 5.68-.9 1.41-.9 3.78 0 5.19.79 1.24 1.71 2.31 2.71 3.17M8.42 19.53c1.14.48 2.35.74 3.58.74 3.53 0 6.82-2.08 9.11-5.68.9-1.41.9-3.78 0-5.19-.33-.52-.69-1.01-1.06-1.47"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path
                                                            d="M15.51 12.7a3.565 3.565 0 0 1-2.82 2.82M9.47 14.53 2 22M22 2l-7.47 7.47"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg></span>
                                                <div class="textContainer-0-4-1537">
                                                    <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                        data-testid="DiscoDropdownItem.title">Hide
                                                        For All
                                                        Members</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a></li>

                                <li class="dropdown-submenu"><a class=" dropdown-item mt-2" href="#">
                                        <div class="content-0-4-1531 content-d7-0-4-1560">
                                            <div class="lhs-0-4-1534"><span class="icon-0-4-1533 icon-d8-0-4-1561"><svg
                                                        viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg" data-testid="DiscoIcon.sort"
                                                        class="root-0-4-376 root-d57-0-4-1565 root-0-4-374 root-d57-0-4-1564">
                                                        <path
                                                            d="M10.45 6.72 6.73 3 3.01 6.72M6.73 21V3M13.55 17.28 17.27 21l3.72-3.72M17.27 3v18"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg></span>
                                                <div class="textContainer-0-4-1537"><span
                                                        class="MuiTypography-root-75 root-0-4-241 root-d52-0-4-1566 root-0-4-299 root-d52-0-4-1567 body-sm-0-4-283 MuiTypography-body2-76"
                                                        data-testid="ProductListOverflow.sort.button.title">Sort
                                                        by</span></div>
                                            </div>
                                            <div class="rhs-0-4-1535"><span
                                                    class="icon-0-4-1533 rotateIcon-0-4-616 icon-d8-0-4-1561"><svg
                                                        viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        data-testid="DiscoIcon.chevron"
                                                        class="root-0-4-376 root-d58-0-4-1571 root-0-4-374 root-d58-0-4-1570">
                                                        <path
                                                            d="M19.92 15.05 13.4 8.53c-.77-.77-2.03-.77-2.8 0l-6.52 6.52"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-miterlimit="10" stroke-linecap="round"
                                                            stroke-linejoin="round"></path>
                                                    </svg></span></div>
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">Order
                                                in Left Nav</a></li>
                                        <li><a class="dropdown-item" href="#">Alphabetical</a></li>
                                        <li><a class="dropdown-item" href="#">Date
                                                publishded</a></li>
                                    </ul>
                                </li>

                            </ul>
                        </button>
                        <hr class="hr-0-4-536 hr-d2-0-4-646" data-testid="DiscoDivider">
                        <!-- ✅ HTML: Search Input + Button -->
                        <div class="input-group mb-3">
                            <input type="text" id="courseSearch" class="form-control" placeholder="Search courses...">
                            <button class="btn btn-outline-secondary" type="button" id="searchButton">Search</button>
                        </div>


                    </div>
                </div>
            </div>

            <div class="content course-view-card">

                <div class="tab-content course-view-card" id="v-pills-tabContent">
                    <div class="tab-pane fade show active" id="v-pills-home" role="tabpanel"
                        aria-labelledby="v-pills-home-tab">
                        <div class="container">
                            <div
                                class="d-flex justify-content-between align-items-center mb-2 headerContainer-0-4-521 ">
                                <div class="header-left">
                                    <svg viewBox="0 0 24 24" fill="#003152" stroke="#003152"
                                        xmlns="http://www.w3.org/2000/svg" data-testid="DiscoIcon.map.active"
                                        class="root-d9-0-4-578">
                                        <path
                                            d="M7.63 3.57c.178-.098.37.052.37.257v13.556c0 .223-.153.412-.35.516a1.448 1.448 0 0 0-.02.01l-2.35 1.34c-1.64.94-2.99.16-2.99-1.74V7.78c0-.63.45-1.41 1.01-1.73l4.33-2.48ZM14.722 6.103A.5.5 0 0 1 15 6.55v13.153a.5.5 0 0 1-.717.45l-4.25-2.047a.5.5 0 0 1-.283-.45V4.447a.5.5 0 0 1 .722-.449l4.25 2.105ZM22 6.49v9.73c0 .63-.45 1.41-1.01 1.73l-3.491 2.001a.5.5 0 0 1-.749-.434V6.33a.5.5 0 0 1 .252-.434L19.01 4.75C20.65 3.81 22 4.59 22 6.49Z">
                                        </path>
                                    </svg>
                                    <h5 class="body-md-500-0-4-276 ms-md-2 ms-sm-1">Explore</h5>
                                </div>
                                <div class="btn-group d-none" style="gap:2px;" role="group"
                                    aria-label="Button group with nested dropdown">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary " data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <span class="MuiIconButton-label-132" class><svg viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg"
                                                    class="moreIcon-0-4-618 rotateIcon-0-4-616">
                                                    <path
                                                        d="M4 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z"
                                                        fill="currentColor" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg></span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">
                                                    <div class="content-0-4-1531 content-d4-0-4-1548">
                                                        <div class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.eye-off"
                                                                    class="root-0-4-376 root-d56-0-4-1553 root-0-4-374 root-d56-0-4-1552">
                                                                    <path
                                                                        d="m14.53 9.47-5.06 5.06a3.576 3.576 0 1 1 5.06-5.06Z"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                    <path
                                                                        d="M17.82 5.77C16.07 4.45 14.07 3.73 12 3.73c-3.53 0-6.82 2.08-9.11 5.68-.9 1.41-.9 3.78 0 5.19.79 1.24 1.71 2.31 2.71 3.17M8.42 19.53c1.14.48 2.35.74 3.58.74 3.53 0 6.82-2.08 9.11-5.68.9-1.41.9-3.78 0-5.19-.33-.52-.69-1.01-1.06-1.47"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                    <path
                                                                        d="M15.51 12.7a3.565 3.565 0 0 1-2.82 2.82M9.47 14.53 2 22M22 2l-7.47 7.47"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                </svg></span>
                                                            <div class="textContainer-0-4-1537">
                                                                <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="DiscoDropdownItem.title">Hide
                                                                    For All
                                                                    Members</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a></li>

                                            <li class="dropdown-submenu"><a class=" dropdown-item mt-2" href="#">
                                                    <div class="content-0-4-1531 content-d7-0-4-1560">
                                                        <div class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d8-0-4-1561"><svg
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.sort"
                                                                    class="root-0-4-376 root-d57-0-4-1565 root-0-4-374 root-d57-0-4-1564">
                                                                    <path
                                                                        d="M10.45 6.72 6.73 3 3.01 6.72M6.73 21V3M13.55 17.28 17.27 21l3.72-3.72M17.27 3v18"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                </svg></span>
                                                            <div class="textContainer-0-4-1537"><span
                                                                    class="MuiTypography-root-75 root-0-4-241 root-d52-0-4-1566 root-0-4-299 root-d52-0-4-1567 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="ProductListOverflow.sort.button.title">Sort
                                                                    by</span></div>
                                                        </div>
                                                        <div class="rhs-0-4-1535"><span
                                                                class="icon-0-4-1533 rotateIcon-0-4-616 icon-d8-0-4-1561"><svg
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.chevron"
                                                                    class="root-0-4-376 root-d58-0-4-1571 root-0-4-374 root-d58-0-4-1570">
                                                                    <path
                                                                        d="M19.92 15.05 13.4 8.53c-.77-.77-2.03-.77-2.8 0l-6.52 6.52"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-miterlimit="10" stroke-linecap="round"
                                                                        stroke-linejoin="round"></path>
                                                                </svg></span></div>
                                                    </div>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#">Order
                                                            in Left Nav</a></li>
                                                    <li><a class="dropdown-item" href="#">Alphabetical</a></li>
                                                    <li><a class="dropdown-item" href="#">Date
                                                            publishded</a></li>
                                                </ul>
                                            </li>

                                        </ul>
                                    </div>
                                    <button type="button" class="btn btn-primary">Add
                                        Product</button>
                                    <div class="btn-group" role="group">
                                        <button type="button" style="border-radius: 0px 5px 5px 0px;"
                                            class="btn btn-primary" data-bs-toggle="dropdown" aria-expanded="false">
                                            <div
                                                class="iconBase-0-4-929 iconBase-d253-0-4-8681 leftIcon-0-4-930 leftIcon-d254-0-4-8682 rotateIcon-180 ">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                                    data-testid="DiscoIcon.chevron"
                                                    class="root-0-4-376 root-d548-0-4-8685 root-0-4-374 root-d548-0-4-8684">
                                                    <path d="M19.92 15.05 13.4 8.53c-.77-.77-2.03-.77-2.8 0l-6.52 6.52"
                                                        stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"
                                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg>
                                            </div>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Pathway</a></li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item " role="presentation">

                                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile" type="button" role="tab" aria-controls="profile"
                                        aria-selected="ture" style="font-weight: bold;"> <svg width="40px"
                                            viewBox="0 0 2050 2050" data-name="Layer 2" id="Layer_2"
                                            xmlns="http://www.w3.org/2000/svg" fill="#003152">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <defs>
                                                    <style>
                                                        .cls-1 {
                                                            fill: #003152;
                                                        }

                                                        .cls-2 {
                                                            fill: #003152;
                                                        }

                                                        .cls-3 {
                                                            fill: #003152;
                                                        }

                                                        .cls-4 {
                                                            fill: #003152;
                                                        }

                                                        .cls-5 {
                                                            fill: #f4c23f;
                                                        }

                                                        .cls-6 {
                                                            fill: #f4a93f;
                                                        }

                                                        .cls-7 {
                                                            fill: #de3226;
                                                        }

                                                        .cls-8 {
                                                            fill: #b11a31;
                                                        }

                                                        .cls-9 {
                                                            fill: #f8881b;
                                                        }

                                                        .cls-10 {
                                                            fill: #f08000;
                                                        }

                                                        .cls-11 {
                                                            fill: #fad564;
                                                        }

                                                        .cls-12 {
                                                            fill: #f44533;
                                                        }
                                                    </style>
                                                </defs>
                                                <title></title>
                                                <path class="cls-1"
                                                    d="M978.4,193.8,170.6,511.4c-18.8,7.5-18.8,34.1,0,41.6l854.9,336.1a21.9,21.9,0,0,0,16.3,0L1896.7,553c18.9-7.5,18.9-34.1,0-41.6L1088.9,193.8A150.6,150.6,0,0,0,978.4,193.8Z">
                                                </path>
                                                <polygon class="cls-2"
                                                    points="741 492.6 473.1 671.9 736.7 775.6 741 492.6"></polygon>
                                                <polygon class="cls-2"
                                                    points="1326.3 492.6 1594.2 671.9 1330.7 775.6 1326.3 492.6">
                                                </polygon>
                                                <path class="cls-1"
                                                    d="M1434.8,1108.8H632.5L706.8,530A44.3,44.3,0,0,1,741,492.6q319.8-71.2,586.9-.9a43.7,43.7,0,0,1,32.4,36.9Z">
                                                </path>
                                                <path class="cls-2"
                                                    d="M833.3,620.9a44.2,44.2,0,0,1,34.2-37.4q267-59.4,497.3-20.3l-4.5-34.6a43.7,43.7,0,0,0-32.4-36.9q-267-70.3-586.9.9A44.3,44.3,0,0,0,706.8,530l-74.3,578.8H770.7Z">
                                                </path>
                                                <ellipse class="cls-2" cx="1033.7" cy="1108.8" rx="401.1" ry="49.3">
                                                </ellipse>
                                                <path class="cls-2"
                                                    d="M1631.4,1016.8a19.9,19.9,0,0,1-20-20V429a20,20,0,0,1,40,0V996.8A20,20,0,0,1,1631.4,1016.8Z">
                                                </path>
                                                <path class="cls-1"
                                                    d="M1628.6,1213.3a69.4,69.4,0,0,1-69.3-69.3V1032.8a69.3,69.3,0,0,1,138.5,0V1144A69.4,69.4,0,0,1,1628.6,1213.3Z">
                                                </path>
                                                <path class="cls-2"
                                                    d="M1594.2,1182V1070.8a69.2,69.2,0,0,1,99.1-62.5,69.2,69.2,0,0,0-134,24.5V1144a69.4,69.4,0,0,0,39.4,62.5A68.8,68.8,0,0,1,1594.2,1182Z">
                                                </path>
                                                <path class="cls-1"
                                                    d="M1827.5,1285.4a67.5,67.5,0,0,0-54.3-27.3H240.8a67.6,67.6,0,0,0-54.3,27.3q-94.6,127.6,0,255.2a67.6,67.6,0,0,0,54.3,27.3H1773.2a67.5,67.5,0,0,0,54.3-27.3Q1922.3,1413,1827.5,1285.4Z">
                                                </path>
                                                <path class="cls-3"
                                                    d="M1867.2,1457.2H334.8a67.5,67.5,0,0,1-54.3-27.3q-63.7-85.8-41.7-171.8a68.2,68.2,0,0,0-52.3,27.3q-94.6,127.6,0,255.2a67.6,67.6,0,0,0,54.3,27.3H1773.2a67.5,67.5,0,0,0,54.3-27.3q31.1-41.7,41.7-83.4Z">
                                                </path>
                                                <rect class="cls-2" height="309.82" width="124.9" x="433.6" y="1258.1">
                                                </rect>
                                                <rect class="cls-2" height="309.82" width="124.9" x="1455.5" y="1258.1">
                                                </rect>
                                                <rect class="cls-4" height="379.76" rx="60.3" ry="60.3"
                                                    transform="translate(-104.3 198.6) rotate(-8.2)" width="120.7"
                                                    x="1272.3" y="636.3"></rect>
                                                <rect class="cls-4" height="379.76" rx="60.3" ry="60.3"
                                                    transform="translate(536.5 1470) rotate(-70.1)" width="120.7"
                                                    x="1256.6" y="162.3"></rect>
                                                <rect class="cls-4" height="114.13" rx="28.4" ry="28.4" width="56.8"
                                                    x="1639.5" y="1044"></rect>
                                                <path class="cls-5"
                                                    d="M1283.9,1533.8c0,27.9-40.4,49.1-48.6,74.2s11.5,66.9-4.3,88.6-61,15.4-82.9,31.4-29.4,60.7-55.5,69.2-57.7-23.4-85.6-23.4-60.4,31.6-85.5,23.4-33.8-53.4-55.5-69.2-67-9.5-82.9-31.4,4.1-62.5-4.3-88.6-48.6-46.3-48.6-74.2,40.4-49,48.6-74.1-11.5-66.9,4.3-88.6,61-15.5,82.9-31.4,29.4-60.8,55.5-69.2,57.7,23.4,85.5,23.4,60.5-31.6,85.6-23.4,33.8,53.4,55.5,69.2,67,9.5,82.9,31.4-4.2,62.5,4.3,88.6S1283.9,1506,1283.9,1533.8Z">
                                                </path>
                                                <path class="cls-6"
                                                    d="M1000.6,1732.2A181.2,181.2,0,0,1,872.5,1423a182.8,182.8,0,0,1,57.6-38.8,181.1,181.1,0,0,1,237.3,237.4,181.1,181.1,0,0,1-166.8,110.6Z">
                                                </path>
                                                <path class="cls-6"
                                                    d="M1115.4,1590.5a15.1,15.1,0,0,0-19.9-7.6l-111.2,50a18.1,18.1,0,0,0-4.2,2.8,18.1,18.1,0,0,0-4.2-2.8l-111.2-50a15.1,15.1,0,0,0-19.9,7.6l-51.6,114.9c20.1,11.6,54.5,9.3,72.8,22.6,21,15.3,28.9,57.8,53,68.3l61.1-135.9,54.2,120.5c20.3,8.8,41.1,21.9,58.3,16.3,26.1-8.5,33.8-53.4,55.5-69.2,6.6-4.8,15.3-7.6,24.7-9.7Z">
                                                </path>
                                                <path class="cls-7"
                                                    d="M891.4,1859.1l-26.3-46.6a15,15,0,0,0-16.3-7.3l-52.3,11.2a15.1,15.1,0,0,1-16.9-20.9l92.2-205a15.1,15.1,0,0,1,19.9-7.6l111.2,50a15.2,15.2,0,0,1,7.5,19.9l-92.2,205A15,15,0,0,1,891.4,1859.1Z">
                                                </path>
                                                <path class="cls-8"
                                                    d="M904,1628.1a31.1,31.1,0,0,0-15.2-41.3l-8.9-4.1a14.7,14.7,0,0,0-8.1,7.8l-92.2,205a15.1,15.1,0,0,0,16.9,20.9l22.6-4.9Z">
                                                </path>
                                                <path class="cls-7"
                                                    d="M1122.7,1859.1l26.3-46.6a15,15,0,0,1,16.3-7.3l52.3,11.2a15.1,15.1,0,0,0,16.9-20.9l-92.2-205a15.1,15.1,0,0,0-19.9-7.6l-111.2,50a15.1,15.1,0,0,0-7.5,19.9l92.1,205A15.1,15.1,0,0,0,1122.7,1859.1Z">
                                                </path>
                                                <path class="cls-8"
                                                    d="M1040.1,1643.8l-33.7,15,89.4,199a15.1,15.1,0,0,0,26.9,1.3l7.5-13.3Z">
                                                </path>
                                                <path class="cls-8"
                                                    d="M1002.9,1632.9l-111.2-50a15.1,15.1,0,0,0-19.9,7.6l-25.2,55.9a181.7,181.7,0,0,0,128.9,84.1l34.9-77.7A15.2,15.2,0,0,0,1002.9,1632.9Z">
                                                </path>
                                                <path class="cls-8"
                                                    d="M1161.8,1633.7l-19.5-43.2a15.1,15.1,0,0,0-19.9-7.6l-111.2,50a15.1,15.1,0,0,0-7.5,19.9l33.9,75.6a180.7,180.7,0,0,0,33.5-10.5,182.6,182.6,0,0,0,90.7-84.2Z">
                                                </path>
                                                <circle class="cls-9" cx="1007" cy="1529.4" r="169.5"></circle>
                                                <circle class="cls-10" cx="1007" cy="1529.4" r="149.7"></circle>
                                                <circle class="cls-5" cx="1007" cy="1529.4" r="132.9"></circle>
                                                <path class="cls-11"
                                                    d="M1125.4,1333.5h0a31.4,31.4,0,0,0,9.5-8.6c-13.1-19.8-22.2-47.9-42.3-54.4-6.9-2.3-14.3-1.5-22.1.8a30.3,30.3,0,0,0,.7,29.2l12.6,21.9A30.5,30.5,0,0,0,1125.4,1333.5Z">
                                                </path>
                                                <path class="cls-11"
                                                    d="M1007.7,1303.9a30.6,30.6,0,0,0,4.3-10.3,41.9,41.9,0,0,1-5,.3c-24.4,0-52.5-24.4-76-24.8a30.4,30.4,0,0,0,13.4,29.7l21.2,13.9a30.5,30.5,0,0,0,42.1-8.8Z">
                                                </path>
                                                <path class="cls-11"
                                                    d="M1235.3,1459.7c-8.5-26.1,11.5-66.9-4.3-88.6-9.9-13.7-31.3-16.3-51.2-20.2a48.4,48.4,0,0,0-6.7,29.9l3.9,40.8a49.3,49.3,0,0,0,53.8,44.4h0a49.3,49.3,0,0,0,6.7-1.1A38.5,38.5,0,0,1,1235.3,1459.7Z">
                                                </path>
                                                <rect class="cls-12" height="85.82" rx="22.5" ry="22.5"
                                                    transform="translate(833.7 -224.2) rotate(24.8)" width="45"
                                                    x="903.9" y="1739.5"></rect>
                                                <rect class="cls-12" height="85.82" rx="22.5" ry="22.5"
                                                    transform="translate(-600.1 629) rotate(-24)" width="45" x="1156"
                                                    y="1682.4"></rect>
                                                <rect class="cls-4" height="89.05" rx="44.5" ry="44.5" width="181.2"
                                                    x="1594.2" y="1258.1"></rect>
                                                <rect class="cls-4" height="89.05" rx="44.5" ry="44.5" width="181.2"
                                                    x="1242.1" y="1258.1"></rect>
                                                <rect class="cls-4" height="89.05" rx="44.5" ry="44.5" width="139.5"
                                                    x="267.3" y="1258.1"></rect>
                                            </g>
                                        </svg> Courses</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="profile" role="tabpanel"
                                    aria-labelledby="profile-tab" style="overflow: auto;">
                                    <div class="row gap-2 justify-content-center mt-2" id="course-containerall" style="padding-bottom: 174px;">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-profile" role="tabpanel"
                        aria-labelledby="v-pills-profile-tab">
                        <div class="container">
                            <div
                                class="d-flex justify-content-between align-items-center mb-2 headerContainer-0-4-521 ">
                                <div class="header-left">
                                    <svg viewBox="0 0 24 24" fill="#003152" stroke="#003152"
                                        xmlns="http://www.w3.org/2000/svg" data-testid="DiscoIcon.map.active"
                                        class="root-d9-0-4-578">
                                        <path
                                            d="M7.63 3.57c.178-.098.37.052.37.257v13.556c0 .223-.153.412-.35.516a1.448 1.448 0 0 0-.02.01l-2.35 1.34c-1.64.94-2.99.16-2.99-1.74V7.78c0-.63.45-1.41 1.01-1.73l4.33-2.48ZM14.722 6.103A.5.5 0 0 1 15 6.55v13.153a.5.5 0 0 1-.717.45l-4.25-2.047a.5.5 0 0 1-.283-.45V4.447a.5.5 0 0 1 .722-.449l4.25 2.105ZM22 6.49v9.73c0 .63-.45 1.41-1.01 1.73l-3.491 2.001a.5.5 0 0 1-.749-.434V6.33a.5.5 0 0 1 .252-.434L19.01 4.75C20.65 3.81 22 4.59 22 6.49Z">
                                        </path>
                                    </svg>
                                    <h5 class="body-md-500-0-4-276 ms-md-2 ms-sm-1">Explore</h5>
                                </div>
                                <div class="btn-group d-none" style="gap:2px;" role="group"
                                    aria-label="Button group with nested dropdown">

                                    <button type="button" class="btn btn-primary rounded-2 ">Add</button>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary rounded-2"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="MuiIconButton-label-132" class><svg viewBox="0 0 24 24"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg"
                                                    class="moreIcon-0-4-618 rotateIcon-0-4-616">
                                                    <path
                                                        d="M4 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z"
                                                        fill="currentColor" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg></span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">
                                                    <div class="content-0-4-1531 content-d4-0-4-1548">
                                                        <div class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.user"
                                                                    class="root-0-4-376 root-d394-0-4-10152 root-0-4-374 root-d394-0-4-10151">
                                                                    <path
                                                                        d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                </svg></span>
                                                            <div class="textContainer-0-4-1537">
                                                                <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="DiscoDropdownItem.title">
                                                                    Members</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a></li>
                                            <li><a class="dropdown-item" href="#">
                                                    <div class="content-0-4-1531 content-d4-0-4-1548">
                                                        <div class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.user-add"
                                                                    class="root-0-4-376 root-d395-0-4-10163 root-0-4-374 root-d395-0-4-10162">
                                                                    <path
                                                                        d="M17.12 20.6c0-3.405-3.39-6.16-7.56-6.16C5.388 14.44 2 17.195 2 20.6M19.22 9.485v2.78m0 0v2.779m0-2.78H22m-2.78 0h-2.778M13.96 7.4a4.4 4.4 0 1 1-8.8 0 4.4 4.4 0 0 1 8.8 0Z"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round"></path>
                                                                </svg></span>
                                                            <div class="textContainer-0-4-1537">
                                                                <p class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="DiscoDropdownItem.title">
                                                                    Invite
                                                                    Members</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a></li>
                                        </ul>
                                    </div>

                                </div>

                            </div>
                            <!-- tab place ul element -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-messages" role="tabpanel"
                        aria-labelledby="v-pills-messages-tab">
                        c</div>
                    <div class="tab-pane fade" id="v-pills-settings" role="tabpanel"
                        aria-labelledby="v-pills-settings-tab">
                        d</div>
                </div>
            </div>
        </div>
        <div id="courseModal" class="modal5">
            <div class="modal-content2">
                <span class="close">&times;</span>
                <strong>Add Module</strong>

                <form id="emailForm" class="mt-5">
                    <p>Title</p>
                    <div class="input-group">
                        <input class="c-input p-2" id="courseName" type="text" name="name" required>
                    </div>
                    <p class="mt-2">Description</p> <br>
                    <div class="input-group input-group-icon">
                        <textarea name="message" id="courseDescription"
                            placeholder="A condensed description for this Pathway" required></textarea>
                    </div>
                </form>
                <footer class="modal-container-footer" style="height: 50px;">
                    <div class="buttons-0-4-823 buttons-d64-0-4-8174" data-testid="CreatePathwayModal.modal.buttons"
                        id="modalFooterButtons1"> <button class="root-d136-0-4-8247">Cancel</button> <button
                            class="btn btn-primary" id="saveCourse">Create
                            Module</button>
                    </div>
                </footer>

            </div>
        </div>
        <div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="addActivityLabel">Add
                            Content</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="contentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="new-content-tab" data-bs-toggle="tab"
                                    data-bs-target="#newContent" type="button" role="tab">New Content</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="existing-content-tab" data-bs-toggle="tab"
                                    data-bs-target="#existingContent" type="button" role="tab">From Existing
                                    Content</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="generate-tab" data-bs-toggle="tab"
                                    data-bs-target="#generate" type="button" role="tab">Generate</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="newContent" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-start text-center mb-2 gap-3"
                                                    style=" align-items: center;">
                                                    <div class=" p-3 ">
                                                        <svg width="41" height="40" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <rect x="0.333" width="40" height="40" rx="8"
                                                                fill="#FF855F">
                                                            </rect>
                                                            <path
                                                                d="M28.232 31.66H13.066c-.65 0-1.084-.433-1.084-1.083v-19.5c0-.65.434-1.083 1.084-1.083h9.75l6.5 6.5v14.083c0 .65-.434 1.083-1.084 1.083ZM23.9 26.244c0-.65-.433-1.084-1.083-1.084h-6.5c-.65 0-1.084.434-1.084 1.084 0 .65.434 1.083 1.084 1.083h6.5c.65 0 1.083-.433 1.083-1.083Zm2.167-5.417c0-.65-.434-1.083-1.084-1.083h-8.666c-.65 0-1.084.433-1.084 1.083 0 .65.434 1.084 1.084 1.084h8.666c.65 0 1.084-.434 1.084-1.084Zm0-5.416c0-.65-.434-1.084-1.084-1.084h-8.666c-.65 0-1.084.434-1.084 1.084 0 .65.434 1.083 1.084 1.083h8.666c.65 0 1.084-.433 1.084-1.083Z"
                                                                fill="url(#text-content_svg__a)"></path>
                                                            <path
                                                                d="M23.899 16.494h5.416l-6.5-6.5v5.417c0 .65.434 1.083 1.084 1.083Z"
                                                                fill="#fff"></path>
                                                            <defs>
                                                                <lineargradient id="text-content_svg__a" x1="20.649"
                                                                    y1="9.994" x2="20.649" y2="31.66"
                                                                    gradientUnits="userSpaceOnUse">
                                                                    <stop stop-color="#fff" stop-opacity="0.8"></stop>
                                                                    <stop offset="1" stop-color="#fff"
                                                                        stop-opacity="0.3">
                                                                    </stop>
                                                                </lineargradient>
                                                            </defs>
                                                        </svg>
                                                    </div>
                                                    <h6 class="fw-bold">Text</h6>
                                                </div>

                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-start text-center mb-2 gap-3"
                                                    style=" align-items: center;">
                                                    <div class=" p-3 ">
                                                        <svg width="40" height="40" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <rect width="40" height="40" rx="8" fill="#4467F6"></rect>
                                                            <path
                                                                d="M27.6 18.448c0-1.5-1.212-2.716-2.708-2.716H12.705a2.712 2.712 0 0 0-2.708 2.716v8.145c0 1.5 1.213 2.716 2.708 2.716h12.187a2.712 2.712 0 0 0 2.709-2.716v-8.145Z"
                                                                fill="#fff" fill-opacity="0.8" opacity="0.3"></path>
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M28.955 11.66a2.712 2.712 0 0 1 2.708 2.715v8.146c0 1.5-1.212 2.715-2.708 2.715H16.768a2.712 2.712 0 0 1-2.708-2.715v-8.146c0-1.5 1.212-2.715 2.708-2.715h12.187Zm-3.665 6.223a.71.71 0 0 1 0 1.13 13.485 13.485 0 0 1-3.73 2.02l-.245.086a.78.78 0 0 1-1.029-.63 15.701 15.701 0 0 1 0-4.082.78.78 0 0 1 1.03-.63l.245.087a13.485 13.485 0 0 1 3.729 2.019Z"
                                                                fill="#fff" fill-opacity="0.8"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Video</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">AI</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Assignment</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Surey</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Quiz</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Completed
                                                    Profile</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                    <div class="col-4"><button class="btn w-100">
                                            <div class="card text-center p-3 shadow-sm border-0"
                                                style="border-radius: 10px;">
                                                <div class="d-flex justify-content-center mb-2">
                                                    <div class="bg-danger p-3 rounded-circle">
                                                        <img src="https://cdn-icons-png.flaticon.com/512/1250/1250680.png"
                                                            width="30" height="30" alt="Text Icon">
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold">Event RSVP</h6>
                                                <p class="text-muted mb-0">Create
                                                    text-based content with
                                                    links and images.</p>
                                            </div>
                                        </button></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="existingContent" role="tabpanel">
                                <p>Load existing content from your repository.</p>
                            </div>
                            <div class="tab-pane fade" id="generate" role="tabpanel">
                                <p>Use AI to generate content suggestions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Button trigger -->

    </div>
    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        onclick="stopVideo()"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe id="videoFrame" src="" title="YouTube video" allowfullscreen allow="autoplay"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>

        function fetchCourses() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajax.php?action=fetch_courses', true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var courses = JSON.parse(xhr.responseText);
                    // Sort so newest courses (largest id or latest date) appear first
    courses.sort(function(a, b) {
        return b.id - a.id; // or b.timecreated - a.timecreated
    });
                    var container = document.getElementById('course-containerall');
                    container.innerHTML = ''; // Clear existing cards if needed
                    courses.forEach(function (course) {
                        addCourseCard(course);
                    });

                    for (var i = 0; i < courses.length; i++) {
                        var course = courses[i];
                        addCourseToList1(course.fullname, course.id);
                    }
                }
            };
            xhr.send();
        }
        window.onload = function () {
            fetchCourses();
        };
        function addCourseCard(course) {
            var container = document.getElementById('course-containerall');
            var cardHTML = `
    <div class="card col-md-3 p-0" style="width: 13rem; min-height: 320px; max-height: 320px;">
        <div class="card h-100 d-flex flex-column p-0">
            <a href="#" class="flex-grow-1 d-flex flex-column">
                <img src="${course.image}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${course.fullname}">
                <div class="card-body summary-2-line flex-grow-1" style="padding: 0.50rem !important;">
                    <h5 class="card-title course-name-text ellipsis ellipsis-2">${course.fullname}</h5>
                    <p>${course.summary.trim()}</p>
                </div>
            </a>
            <div class="card-footer c-footer mt-auto">
                <a href="#" class="btn ai-course-button c-btn-primary w-100" data-courseid="${course.id}" data-coursename="${course.fullname}">
                    View Course
                </a>
            </div>
        </div>
    </div>`;
            container.insertAdjacentHTML('beforeend', cardHTML);
        }

        document.addEventListener("DOMContentLoaded", function () {
            let modalBody1 = document.querySelector(".modal-container-body");
            let modalfooter = document.querySelector(".modal-container-footer");
            modalBody1.addEventListener("scroll", function () {
                if (modalBody1.scrollHeight - modalBody1.scrollTop <= modalBody1.clientHeight + 1) {
                    // When scrolled to the bottom
                    modalfooter.classList.remove("footer-scrollp");
                } else {
                    // Remove class when not at the bottom
                    modalfooter.classList.add("footer-scrollp");
                }
            });
            let modalBody = document.querySelector(".modal-container-body");
            let modalFooter = document.querySelector(".modal-container-header");

            modalBody.addEventListener("scroll", function () {
                if (modalBody.scrollTop > 0) {
                    // When user starts scrolling
                    modalFooter.classList.add("header-scrollp");
                } else {
                    // Remove class when at the top
                    modalFooter.classList.remove("header-scrollp");
                }
            });
        });
        function openModal(data) {
            // Update title and description
            document.getElementById("modalTitle").innerText = data.title || "Default Title";
            document.getElementById("modalDescription").innerText = data.description || "Default Description";

            // Update modal body content
            document.getElementById("modalBodyContent").innerHTML = data.content || "<p>Default Content</p>";

            // Update modal footer buttons
            let footerButtons = document.getElementById("modalFooterButtons");
            footerButtons.innerHTML = ""; // Clear existing buttons

            data.buttons.forEach(button => {
                let btn = document.createElement("button");
                btn.innerText = button.text;
                btn.className = `btn ${button.class}`;
                btn.onclick = button.onClick;
                footerButtons.appendChild(btn);
            });

            // Show the modal
            document.getElementById("dynamicModal").style.display = "flex";

        }

        function closeModal() {
            document.getElementById("dynamicModal").style.display = "none";
        }

        // Example Usage:
        document.querySelector(".open-modal1").addEventListener("click", function () {
            openModal({
                title: "New Section Setup",
                description: "Sections are used to group relevant navigation items..",
                content: `
            <form id="dynamicForm">
                <label>Section Title:</label>
                <input type="text" placeholder="e.g. Resources">
            </form>
            
        `,
                buttons: [
                    { text: "Complete Setup", class: "btn-primary", onClick: () => alert("Saved!") }
                ]
            });
        });
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.play-video-btn');
        if (!btn) return;

        e.preventDefault();

        // read cmid
        const cmid = parseInt(btn.getAttribute('data-cmid') || '0', 10);
        if (!cmid || cmid <= 0) {
            console.warn('play-video-btn clicked but no valid data-cmid present.');
            return;
        }

        // disable button immediately to avoid duplicate clicks
        // if (!btn.dataset.inflight) {
        //     btn.dataset.inflight = '1';
        //     btn.disabled = true;
        // } else {
        //     // request already in flight
        //     return;
        // }

        // get sesskey (prefer M.cfg.sesskey)
        const sesskey = (typeof M !== 'undefined' && M.cfg && M.cfg.sesskey) ? M.cfg.sesskey : <?php echo json_encode(sesskey()); ?>;

        const body = new URLSearchParams();
        body.append('sesskey', sesskey);
        body.append('cmid', cmid);

        fetch(M.cfg.wwwroot + '/local/course_ai/complete_video.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        })
        .then(response => {
            // try parse JSON even on non-200
            return response.json().catch(() => ({ success: false, error: 'Invalid JSON response' }));
        })
        .then(json => {
            if (json && json.success) {
                // success UI
              //  btn.classList.remove('btn-light');
               // btn.classList.add('btn-success');
                // btn.innerHTML = '✅ Completed';
                // btn.disabled = true;
            } else {
                // failure UI + console
                // btn.disabled = false;
                console.warn('Mark complete failed:', json && json.error ? json.error : 'Unknown error');
                // optional small UI feedback:
                // const old = btn.innerHTML;
                // btn.innerHTML = '⚠️ Try again';
                // setTimeout(() => { btn.innerHTML = old; }, 2000);
            }
        })
        .catch(err => {
            // delete btn.dataset.inflight;
            // btn.disabled = false;
            // console.error('Network or fetch error marking complete:', err);
            // // optional small UI feedback:
            // const old = btn.innerHTML;
            // btn.innerHTML = '⚠️ Error';
            // setTimeout(() => { btn.innerHTML = old; }, 2000);
        });
    });
});


        document.addEventListener("DOMContentLoaded", function () {
            // Select modal and buttons
            const modal = document.querySelector(".modal1");
            const openModalButtons = document.querySelectorAll(".open-modal");
            const openModalButtons2 = document.querySelectorAll(".open-modal2");
            const openModalbadges = document.querySelectorAll(".openModalbadges");
            const openModalblanck = document.querySelectorAll(".openModalblanck");
            const openModalevent = document.querySelectorAll(".openModalevent");
            const closeModalButtons = document.querySelectorAll(".icon-button, .icon-button1");
            // Function to open modal with dynamic content
            function openModal1(data) {
                document.getElementById("modalTitle1").innerText = data.title;
                document.getElementById("modalDescription1").innerText = data.description;
                document.getElementById("modalBodyContent1").innerHTML = data.content;

                // Update footer buttons
                let footerButtons = document.getElementById("modalFooterButtons1");
                footerButtons.innerHTML = ""; // Clear existing buttons

                data.buttons.forEach(button => {
                    let btn = document.createElement("button");
                    btn.className = `MuiButtonBase-root-124 root-0-4-5757 ${button.class}`;
                    btn.setAttribute("tabindex", "0");
                    btn.setAttribute("type", "button");
                    btn.setAttribute("data-testid", `CreatePathwayModal.${button.text.toLowerCase()}.button`);

                    // Create the inner <span> with text
                    let span = document.createElement("span");
                    span.setAttribute("data-testid", `CreatePathwayModal.${button.text.toLowerCase()}.button.text`);
                    span.innerText = button.text;

                    // Append <span> inside <button>
                    btn.appendChild(span);

                    // Add event listener for click event
                    btn.addEventListener("click", button.onClick || closeModal);

                    // Append button to modal footer
                    footerButtons.appendChild(btn);
                });

                modal.style.display = "flex"; // Show modal
            }

            // Function to close modal
            function closeModal() {
                modal.style.display = "none";
                document.querySelectorAll('.modal-container-title').forEach(element => {
                    element.classList.remove('justify-content-center', 'text-center');
                });

                document.querySelectorAll('.modal-container').forEach(element => {
                    element.classList.remove('justify-content-center', 'text-center', 'modal-container-small');
                });
            }

            const createcontent = `<form id="emailForm">
                        <strong>Course Name</strong> <br>
                        <div class="input-group input-group-icon mt-3">
                            <input class="c-input" id="courseNameInput" type="text" name="name" required>
                            <div class="input-icon container-d21-0-4-4509">
                                <div class="input-icon1"></div>
                            </div>
                        </div>
                        <strong class="mt-2">Description</strong> <br>
                        <p class="p-font"> Add a short description about your Course.</p>
                        <div class="input-group input-group-icon">
                            <textarea name="message"  required></textarea>
                        </div> 
    </div>
                    </form>`;
            const createcontentdoc = `<form id="emailForm">
                        <strong>Course Name</strong> <br>
                        <div class="input-group input-group-icon mt-3">
                            <input class="c-input" id="courseNameInput" type="text" name="name" required>
                            <div class="input-icon container-d21-0-4-4509">
                                <div class="input-icon1"></div>
                            </div>
                        </div>
                  
                        <div class="input-group input-group-icon d-none">
                            <textarea name="message"  required></textarea>
                        </div> 
                        <strong class="mt-2">Upload a Word Document</strong> <br>
    <p class="p-font">You can upload a .docx file to generate the course content.</p>
    <div class="input-group input-group-icon">
        <input type="file" name="course_doc" accept=".docx">
    </div>
                    </form>`;
            document.querySelector('.modal-container-title')?.classList.remove('justify-content-center', 'text-center');
            document.querySelector('.modal-container')?.classList.remove('justify-content-center', 'text-center', 'modal-container-small');

            // Attach event listeners for open buttons
            openModalButtons2.forEach(button => {

                button.addEventListener("click", function () {


                    let modalData = {
                        title: 'Create Pathway',
                        description: 'Pathways allow you to group multiple Products into a sequential program to achieve specific learning goals. Easily chart Member progress and guide them through each Product in a simple, structured interface.',
                        content: createcontent,
                        buttons: [
                            { text: "Cancel", class: "root-d136-0-4-8247", onClick: closeModal },
                            { text: "Next", class: "root-d140-0-4-8251 btn btn-primary add-course" }
                        ]
                    };
                    document.querySelectorAll(".icon-button1").forEach(btn => {
                        btn.style.display = "none";
                    });

                    openModal1(modalData);
                });

            });

            document.querySelectorAll(".icon-button, .icon-button1").forEach(btn => {
                btn.style.display = "block";
            });

            let courseName = ""; // global within this scope

            openModalbadges.forEach(button => {
                button.addEventListener("click", function () {
                    document.querySelectorAll(".icon-button1").forEach(btn => {
                        btn.style.display = "block";
                    });

                    let modalData = {
                        title: 'AI Course Generator',
                        description: '',
                        content: createcontent,
                        buttons: [
                            { text: "Cancel", class: "root-d136-0-4-8247", onClick: closeModal },
                            {
                                text: "Generate Course",
                                class: "root-d140-0-4-8251 btn btn-primary add-course",
                                onClick: function () {
                                    let input = document.getElementById("courseNameInput");
                                    let courseName = input?.value.trim();
                                    let courseSummary = document.querySelector("textarea[name='message']")?.value.trim();

                                    if (!courseName) {
                                        alert("Please enter a Course Name before saving.");
                                        return;
                                    }

                                    fetch(M.cfg.wwwroot + '/local/course_ai/save_draft_course.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: `coursename=${encodeURIComponent(courseName)}&summary=${encodeURIComponent(courseSummary)}&sesskey=${M.cfg.sesskey}`
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                        if (data.success && data.courseid) {                                       
    const courseid = data.courseid;
    addCourseToList1(courseName, courseid);

    document.querySelectorAll(".icon-button, .icon-button1").forEach(btn => {
        btn.style.display = "none";
    });

    // Create loader overlay
    const loaderOverlay = document.createElement("div");
    loaderOverlay.style.position = "fixed";
    loaderOverlay.style.top = "0";
    loaderOverlay.style.left = "0";
    loaderOverlay.style.width = "100%";
    loaderOverlay.style.height = "100%";
    loaderOverlay.style.backgroundColor = "rgba(255, 255, 255, 0.95)";
    loaderOverlay.style.zIndex = "9999";
    loaderOverlay.innerHTML = `
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
            <div class="loader" style="
                border: 8px solid #f3f3f3; 
                border-top: 8px solid #ec9707; 
                border-radius: 50%; 
                width: 60px; 
                height: 60px; 
                animation: spin 1s linear infinite; 
                margin: 0 auto;
            "></div>
            <h2 id="loader-status" class="d-flex justify-content-center" style="font-family: Arial; color: #333; margin-top: 20px;">
                AI generating course...
            </h2>
        </div>
    `;
    document.body.appendChild(loaderOverlay);

    // Spinner keyframes
    const style = document.createElement("style");
    style.innerHTML = `
        @keyframes spin { 
            from { transform: rotate(0deg); } 
            to { transform: rotate(360deg); } 
        }
    `;
    document.head.appendChild(style);

    // Loader steps
    const steps = [
        "AI generating course...",
        "📄 Preparing course draft...",
        "📝 Generating section outlines...",
        "📚 Writing course content...",
        "🎥 Adding videos...",
        "❓ Creating quizzes...",
        "✅ Finalizing course..."
    ];

    let stepIndex = 0;
    const loaderStatus = document.getElementById("loader-status");

    const stepInterval = setInterval(() => {
        if (stepIndex < steps.length - 1) {
            loaderStatus.textContent = steps[stepIndex];
            stepIndex++;
        } else {
            loaderStatus.textContent = steps[steps.length - 1];
            clearInterval(stepInterval);
        }
    }, 5000); // 5 seconds per step

    // Continue with your existing course generation
    setTimeout(() => {
        fetch(M.cfg.wwwroot + `/local/course_ai/generate_course.php?prompt=${encodeURIComponent(courseName)}&courseid=${data.courseid}`)
            .then(res => res.json())
            .then(gen => {
                if (gen.success && data.courseid) {
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('courseid', courseid);
                    history.pushState(null, '', newUrl);

                    fetch(`course_template.php?courseid=${courseid}`)
                        .then(response => response.text())
                        .then(html => {
                            html = html
                                .replace(/\${courseName}/g, courseName)
                                .replace(/\${courseId}/g, courseid);

                            const mainContent = document.getElementById('aicourseforamt');
                            if (mainContent) {
                                mainContent.innerHTML = html;
                            }

                            document.body.removeChild(loaderOverlay); // remove loader after success
                        })
                        .catch(error => {
                            console.error('Error loading course template:', error);
                            const mainContent = document.getElementById('aicourseforamt');
                            if (mainContent) {
                                mainContent.innerHTML = `<p>Error loading content.</p>`;
                            }
                            document.body.removeChild(loaderOverlay);
                        });
                } else {
                    alert("Failed to generate course content.");
                    document.body.removeChild(loaderOverlay);
                }
            });
    }, 500);
} else {
    alert("Failed to save course draft: " + (data.error || "Unknown error."));
}

                                        })

                                        .catch(err => {
                                            console.error("Error saving draft course:", err);
                                            alert("Error saving course.");
                                        });
                                }

                            }
                        ]
                    };

                    openModal1(modalData);
                });
            });

            function postToGenerateCourse(prompt, courseid) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/local/course_ai/generate_course.php';

                const promptInput = document.createElement('input');
                promptInput.type = 'hidden';
                promptInput.name = 'prompt';
                promptInput.value = prompt;

                const courseIdInput = document.createElement('input');
                courseIdInput.type = 'hidden';
                courseIdInput.name = 'courseid';
                courseIdInput.value = courseid;

                form.appendChild(promptInput);
                form.appendChild(courseIdInput);
                document.body.appendChild(form);
                form.submit();
            }


            openModalblanck.forEach(button => {
                button.addEventListener("click", function () {
                    document.querySelectorAll(".icon-button1").forEach(btn => {
                        btn.style.display = "block";
                    }); //hide back arrow
                    let modalData = {                        
                        title: 'Doc based course generator',
                        description: '',
                        content: createcontentdoc,
                        buttons: [
                            { text: "Cancel", class: "root-d136-0-4-8247", onClick: closeModal },
                            {
                                text: "Generate Course",
                                class: "root-d140-0-4-8251 btn btn-primary add-course",
                                onClick: function () {
                                    const form = document.getElementById("emailForm");
                                    const input = document.getElementById("courseNameInput");
                                    const courseName = input?.value.trim();
                                    const courseSummary = form.querySelector("textarea[name='message']")?.value.trim();
                                    const fileInput = form.querySelector("input[type='file'][name='course_doc']");
                                    const file = fileInput?.files[0];

                                    if (!courseName) {
                                        alert("Please enter a Course Name before saving.");
                                        return;
                                    }

                                    const formData = new FormData();
                                    formData.append("coursename", courseName);
                                    formData.append("summary", courseSummary);
                                    formData.append("sesskey", M.cfg.sesskey);
                                    if (file) {
                                        formData.append("course_doc", file);
                                    }

                                    // === 1. SHOW FIRST LOADER: Word Document Uploading ===
                                    const wordLoader = document.createElement("div");
                                    wordLoader.style.position = "fixed";
                                    wordLoader.style.top = "0";
                                    wordLoader.style.left = "0";
                                    wordLoader.style.width = "100%";
                                    wordLoader.style.height = "100%";
                                    wordLoader.style.backgroundColor = "rgba(255, 255, 255, 0.95)";
                                    wordLoader.style.zIndex = "9999";
                                    wordLoader.innerHTML = `
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                    <div class="loader" style="border: 8px solid #f3f3f3; border-top: 8px solid #9b59b6; border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                    <h2 class="d-flex justify-content-center" style="font-family: Arial; color: #333; margin-top: 20px;">Word document uploading...</h2>
                                </div>
                            `;
                                    document.body.appendChild(wordLoader);

                                    const style = document.createElement("style");
                                    style.innerHTML = `@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }`;
                                    document.head.appendChild(style);
                                    fetch(M.cfg.wwwroot + '/local/course_ai/save_draft_course.php', {
                                        method: 'POST',
                                        body: formData // No need to set Content-Type manually; browser sets it for FormData
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success && data.courseid) {
                                                const courseid = data.courseid;
                                                addCourseToList1(courseName, courseid);

                                                // Hide buttons
                                                document.querySelectorAll(".icon-button, .icon-button1").forEach(btn => {
                                                    btn.style.display = "none";
                                                });
                                                // Wait 4 seconds, then replace with AI loader
                                                setTimeout(() => {
                                                    document.body.removeChild(wordLoader);

                                                    // === 2. SHOW SECOND LOADER: AI Generating Course ===
                                                    const aiLoader = document.createElement("div");
                                                    aiLoader.style.position = "fixed";
                                                    aiLoader.style.top = "0";
                                                    aiLoader.style.left = "0";
                                                    aiLoader.style.width = "100%";
                                                    aiLoader.style.height = "100%";
                                                    aiLoader.style.backgroundColor = "rgba(255, 255, 255, 0.95)";
                                                    aiLoader.style.zIndex = "9999";
                                                    aiLoader.innerHTML = `
                                              <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                                  <div class="loader" style="border: 8px solid #f3f3f3; border-top: 8px solid #3498db; border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                                  <h2 class="d-flex justify-content-center" style="font-family: Arial; color: #333; margin-top: 20px;">AI generating course...</h2>
                                              </div>
                                              `;
                                                    document.body.appendChild(aiLoader);

                                                    // Finally, redirect
                                                    if (data.redirect) {
                                                        setTimeout(() => {
                                                            window.location.href = data.redirect;
                                                        }, 500); // small delay for smooth UX
                                                    }
                                                }, 1000); // 1-second delay before AI loader
                                            }

                                            else {
                                                alert("Failed to save course draft: " + (data.error || "Unknown error."));
                                            }
                                        })
                                        .catch(err => {
                                            console.error("Error saving draft course:", err);
                                            alert("Error saving course.");
                                        });
                                }

                            }
                        ]
                    };
                    openModal1(modalData);
                });
            });

            openModalevent.forEach(button => {
                button.addEventListener("click", function () {
                    document.querySelectorAll(".icon-button1").forEach(btn => {
                        btn.style.display = "block";
                    }); //hide back arrow
                    let modalData = {
                        title: 'Create Event Series',
                        description: '',
                        content: createcontent,
                        buttons: [
                            { text: "Cancel", class: "root-d136-0-4-8247", onClick: closeModal },
                            {
                                text: "Save as Draft",
                                class: "root-d140-0-4-8251 btn btn-primary add-course",
                            }
                        ]
                    };
                    openModal1(modalData);
                });
            });

            // Attach event listeners for close buttons
            closeModalButtons.forEach(button => {
                button.addEventListener("click", closeModal);
            });

            // Close modal when clicking outside
            modal.addEventListener("click", (event) => {
                if (event.target === modal) closeModal();
            });

            // Close modal on ESC key press
            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") closeModal();
            });

            // Hide Bootstrap modal before opening the custom modal
            $(".open-modal").on("click", function () {
                $("#exampleModal").modal("hide");
            });
            $(".openModalbadges").on("click", function () {
                $("#exampleModal").modal("hide");
                $(".btn-close").trigger("click");
            });
            $(".openModalblanck").on("click", function () {
                $(".btn-close").trigger("click");
            });
            $(".openModalevent").on("click", function () {
                $(".btn-close").trigger("click");
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('course-containerall').addEventListener('click', function (event) {
                const button = event.target.closest('.ai-course-button');
                if (button) {
                    event.preventDefault();

                    const courseid = button.getAttribute('data-courseid');
                    const courseName = button.getAttribute('data-coursename');

                    // Update the URL with the courseid
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('courseid', courseid);
                    history.pushState(null, '', newUrl);

                    // Fetch course content
                    fetch(`course_template.php?courseid=${courseid}`)
                        .then(response => response.text())
                        .then(html => {
                            // Replace placeholders
                            html = html
                                .replace(/\${courseName}/g, courseName)
                                .replace(/\${courseId}/g, courseid);

                            // Replace the entire content inside div[role="main"]
                            const mainContent = document.getElementById('aicourseforamt');
                            if (mainContent) {
                                mainContent.innerHTML = html;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading course template:', error);
                            const mainContent = document.getElementById('aicourseforamt');
                            if (mainContent) {
                                mainContent.innerHTML = `<p>Error loading content.</p>`;
                            }
                        });
                }
            });
        });

        function addCourseToList1(courseName, courseid) {

            let courseList1 = document.getElementById('v-pills-tab');
            let tabContent = document.getElementById('v-pills-tabContent');

            let courseId = 'course_' + courseName.replace(/\s+/g, '_'); // Unique ID
            let tabPaneId = 'v-pills-' + courseid.replace(/\s+/g, '-').toLowerCase(); // Unique tab pane ID

            // Create new tab button
            let listItem = document.createElement('button');
            listItem.className = 'nav-link draggable d-flex align-items-center';
            listItem.id = courseId;
            listItem.setAttribute('data-bs-toggle', 'pill');
            listItem.setAttribute('data-bs-target', `#${tabPaneId}`);
            listItem.setAttribute('type', 'button');
            listItem.setAttribute('role', 'tab');
            listItem.setAttribute('aria-controls', tabPaneId);
            listItem.setAttribute('aria-selected', 'false');
            listItem.draggable = true;

            // Create badge container (before text)
            let badgeContainerBefore = document.createElement('div');
            badgeContainerBefore.className = 'badgeContainer-0-4-239 wh badgeContainer-d30-0-4-2926';
            // Create the image element
            let imgElement = document.createElement("img");
            imgElement.setAttribute("src", "https://img.icons8.com/external-sbts2018-solid-sbts2018/58/FFFFFF/external-notebook-stationery-items-sbts2018-solid-sbts2018-3.png");
            imgElement.setAttribute("alt", "external-file-explorer-online-education-flaticons-flat-flat-icons");
            imgElement.setAttribute("width", "20");

            // Append the image to the div
            badgeContainerBefore.appendChild(imgElement);

            // Create text span for course name
            let textSpan = document.createElement('span');
            textSpan.innerText = courseName;
            textSpan.className = 'mx-2 text-truncate course-text'; // Adds spacing between icons and text

            // Create badge container (after text)
            let badgeContainerAfter = document.createElement('div');
            badgeContainerAfter.innerText = 'Draft';
            badgeContainerAfter.className = 'small-draft';

            // Append elements in order: Before Icon -> Text -> After Icon
            listItem.appendChild(badgeContainerBefore);
            listItem.appendChild(textSpan);
            listItem.appendChild(badgeContainerAfter);
            // Create tab pane content
            let tabPane = document.createElement('div');
            tabPane.className = 'tab-pane fade';
            tabPane.id = tabPaneId;
            tabPane.setAttribute('role', 'tabpanel');
            tabPane.innerHTML = `
        <h4>loading content for course ${courseName}</h4>
    `;

            // Create container for additional buttons
            let buttonContainer = document.createElement('div');
            buttonContainer.className = 'card mb-2 justify-content-center p-2';
            buttonContainer.style.display = 'none'; // Initially hidden

            let button1 = document.createElement('button');
            button1.className = 'nav-link';
            button1.id = `v-pills-${courseName}-item1`;
            button1.setAttribute('type', 'button');
            button1.innerText = 'Curriculum';

            let button2 = document.createElement('button');
            button2.className = 'nav-link ';
            button2.id = `v-pills-${courseName}-item2`;
            button2.setAttribute('type', 'button');
            button2.innerText = 'Members';

            // Append buttons to the container
            buttonContainer.appendChild(button1);
            buttonContainer.appendChild(button2);

            listItem.addEventListener('click', function () {
                let allTabPanes = tabContent.querySelectorAll('.tab-pane');
                allTabPanes.forEach(pane => pane.classList.remove('show', 'active'));

                document.getElementById(tabPaneId).classList.add('show', 'active');
                document.querySelectorAll('.card.mb-2.justify-content-center.p-2').forEach(card => {
                    card.style.display = 'none';
                });
                buttonContainer.style.display = 'flex';
                // Update the URL with the courseid
                var newUrl = new URL(window.location);
                newUrl.searchParams.set('courseid', courseid);
                history.pushState(null, '', newUrl);

                fetch(`course_template.php?courseid=${courseid}`)
                    .then(response => response.text())
                    .then(html => {
                        // Replace placeholders (if needed)
                        html = html
                            .replace(/\${courseName}/g, courseName)
                            .replace(/\${courseId}/g, courseId)
                        tabPane.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading course template:', error);
                        tabPane.innerHTML = `<p>Error loading content.</p>`;
                    });
            });


            // fetchSections(courseid, courseName, tabPane)
            // Function to handle item button clicks
            function handleItemClick(button, content) {
                // Remove 'active' class from all buttons inside buttonContainer
                buttonContainer.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));

                // Add 'active' class to clicked button
                button.classList.add('active');

                // Update tab content
                tabPane.innerHTML = `
        <div
                            class="container">
                            <div
                                class="d-flex justify-content-between align-items-center mb-2 headerContainer-0-4-521 ">
                                <div
                                    class="header-left-inner"> <div class="badgeContainer-0-4-239 badgeContainer-d30-0-4-2926"></div>
                                    <h5
                                        class="body-md-500-0-4-276 ms-md-2 ms-sm-1">${courseName}</h5>
                                </div>
                                <div class="btn-group d-none" style="gap:2px;"
                                    role="group"
                                    aria-label="Button group with nested dropdown">

                                    <button type="button"
                                        class="btn btn-primary rounded-2 showalert">+ Add Module</button>
                                    <div class="btn-group" role="group">
                                        <button type="button"
                                            class="btn btn-primary rounded-2"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <span
                                                class="MuiIconButton-label-132"
                                                class><svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="moreIcon-0-4-618 rotateIcon-0-4-616"><path
                                                        d="M4 12a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Zm7 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z"
                                                        fill="currentColor"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"></path></svg></span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item"
                                                    href="#"><div
                                                        class="content-0-4-1531 content-d4-0-4-1548"><div
                                                            class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.user"
                                                                    class="root-0-4-376 root-d394-0-4-10152 root-0-4-374 root-d394-0-4-10151"><path
                                                                        d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"
                                                                        stroke="currentColor"
                                                                        stroke-width="1.5"
                                                                        stroke-linecap="round"
                                                                        stroke-linejoin="round"></path></svg></span><div
                                                                class="textContainer-0-4-1537"><p
                                                                    class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="DiscoDropdownItem.title">
                                                                    Members</p></div></div></div></a></li>
                                            <li><a class="dropdown-item"
                                                    href="#"><div
                                                        class="content-0-4-1531 content-d4-0-4-1548"><div
                                                            class="lhs-0-4-1534"><span
                                                                class="icon-0-4-1533 icon-d5-0-4-1549"><svg
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    data-testid="DiscoIcon.user-add"
                                                                    class="root-0-4-376 root-d395-0-4-10163 root-0-4-374 root-d395-0-4-10162"><path
                                                                        d="M17.12 20.6c0-3.405-3.39-6.16-7.56-6.16C5.388 14.44 2 17.195 2 20.6M19.22 9.485v2.78m0 0v2.779m0-2.78H22m-2.78 0h-2.778M13.96 7.4a4.4 4.4 0 1 1-8.8 0 4.4 4.4 0 0 1 8.8 0Z"
                                                                        stroke="currentColor"
                                                                        stroke-width="1.5"
                                                                        stroke-linecap="round"></path></svg></span><div
                                                                class="textContainer-0-4-1537"><p
                                                                    class="MuiTypography-root-75 root-0-4-241 root-d51-0-4-1554 root-0-4-299 root-d51-0-4-1555 body-sm-0-4-283 MuiTypography-body2-76"
                                                                    data-testid="DiscoDropdownItem.title">
                                                                    Invite
                                                                    Members</p></div></div></div></a></li>
                                        </ul>
                                    </div>

                                </div>

                            </div>
                            <div class="course-view" id="course-view"></div>
        `;
            }


            $(document).ready(function () {
                // Open modal when "Add" button is clicked
                $(document).on("click", ".showalert", function () {
                    $("#courseModal").show();
                });

                // Close modal when "X" is clicked
                $(".close").click(function () {
                    $("#courseModal").hide();
                });

                $("#saveCourse").click(function () {
                    let courseName = $("#courseName").val().trim();
                    let courseDesc = $("#courseDescription").val().trim();

                    // if (courseName === "" || courseDesc === "") {
                    //     alert("Please enter both Course Name and Description!");
                    //     return;
                    // }

                    // Generate unique ID for this accordion
                    let uniqueId = new Date().getTime();
                    let today = new Date();

                    // Format it as YYYY-MM-DD
                    let formattedDate = today.toISOString().split('T')[0];

                    let cardHtml = `
        <div id="collapse${uniqueId}" class="collapse show" aria-labelledby="heading${uniqueId}" data-parent="#outer-accordion" >
            <div class="card-body">
                <div id="inner-accordion-${uniqueId}">
                    <div class="course-view-card1 mt-3 chaptertab">
                        <div class="card-header h_border chaptertab" id="innerHeading${uniqueId}" style="display: flex;justify-content: space-between;">
                            <h5 class="mb-0" >
                                <button class="btn chapterbtn d-flex align-content-center w-100 text-decoration-none accordion-button collapsed"
                                    data-toggle="collapse" data-target="#innerCollapse${uniqueId}" aria-expanded="true" aria-controls="innerCollapse${uniqueId}">
                                    <h5 class="d-flex justify-content-center">
                                        <span class="MuiIconButton-label-132">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="collapsibleCaret-0-4-1378">
                                                <path d="M17.919 8.18H6.079c-.96 0-1.44 1.16-.76 1.84l5.18 5.18c.83.83 2.18.83 3.01 0l1.97-1.97 3.21-3.21c.67-.68.19-1.84-.77-1.84Z" fill="currentColor"></path>
                                            </svg>
                                        </span>
                                        ${courseName}
                                    </h5>
                                    <div class=".release-date"><span>Release date:${formattedDate}</span></div>
                                </button>
                            </h5>
                            <button id="addActivity" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal" style="    border-radius: 50%;padding: 0px;margin: 12px;width: 30px;height: 30px;">+</button>
                        </div>
                        <div id="innerCollapse${uniqueId}" class="collapse" aria-labelledby="innerHeading${uniqueId}" data-parent="#inner-accordion-${uniqueId}">
                            <div class="card-body">
                                <!-- MCQ 1 -->
                                <div class="course-view-card1 mt-3 chaptertab">
                                    <div class="card-header h_border chaptertab">
                                        <h5 class="mb-0">
                                            <button class="btn chapterbtn btn-light d-flex justify-content-between align-content-center w-100 text-decoration-none collapsed">
                                                MCQ
                                            </button>
                                        </h5>
                                    </div>
                                </div>
                                <!-- MCQ 2 -->
                                <div class="course-view-card1 mt-3 chaptertab">
                                    <div class="card-header h_border chaptertab">
                                        <h5 class="mb-0">
                                            <button class="btn chapterbtn btn-light d-flex justify-content-between align-content-center w-100 text-decoration-none collapsed">
                                                MCQ
                                            </button>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Add more inner accordion items as needed -->
                </div>
            </div>
        </div>
    `;

                    $("#course-view").append(cardHtml);
                    $("#course-view1").append(cardHtml);
                    // Close the modal and clear inputs
                    $("#courseModal").hide();
                    $("#courseName").val("");
                    $("#courseDescription").val("");
                });



                // Remove card when "Remove" button is clicked
                $(document).on("click", ".remove-card", function () {
                    $(this).closest(".card").remove();
                });
            });

            // Event for Item1 to update tabPane content
            button1.addEventListener('click', function () {
                handleItemClick(button1, 'Item1');
            });

            // Event for Item2 to update tabPane content
            button2.addEventListener('click', function () {
                handleItemClick(button2, 'Item2');
            });

            // Drag and drop functionality
            listItem.addEventListener('dragstart', function (event) {
                event.dataTransfer.setData("text/plain", event.target.id);
                event.target.classList.add("dragging");
            });

            listItem.addEventListener('dragend', function (event) {
                event.target.classList.remove("dragging");
            });

            // Append elements to UI
            courseList1.appendChild(listItem);
            courseList1.appendChild(buttonContainer); // Append below course button
            tabContent.appendChild(tabPane);

        }
        document.getElementById('v-pills-home-tab').addEventListener('click', function () {
            const profile = document.getElementById('profile');
            profile.classList.add('show');
            profile.style.display = 'block';
            // Hide all additional button containers when clicking "Home" tab
            document.querySelectorAll('.card.mb-2.justify-content-center.p-2').forEach(card => {
                card.style.display = 'none';
            });

            // Remove 'active' class from all item buttons
            document.querySelectorAll('.card.mb-2 .nav-link').forEach(btn => {
                btn.classList.remove('active');
            });
        });

        // Allow drop functionality
        document.getElementById("v-pills-tab").addEventListener("dragover", function (event) {
            event.preventDefault();
        });

        document.getElementById("v-pills-tab").addEventListener("drop", function (event) {
            event.preventDefault();

            let draggedElementId = event.dataTransfer.getData("text/plain");
            let draggedElement = document.getElementById(draggedElementId);

            let dropTarget = event.target.closest(".draggable");

            if (dropTarget && draggedElement !== dropTarget) {
                let list = document.getElementById("v-pills-tab");
                let children = Array.from(list.children);
                let draggedIndex = children.indexOf(draggedElement);
                let targetIndex = children.indexOf(dropTarget);

                if (draggedIndex > targetIndex) {
                    list.insertBefore(draggedElement, dropTarget);
                } else {
                    list.insertBefore(draggedElement, dropTarget.nextSibling);
                }
            }
        });
        $(document).ready(function () {
            // Initialize Bootstrap collapse component
            $('.collapse').collapse();
        });

        document.querySelectorAll('.dropdown-submenu > a').forEach(element => {
            element.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                let submenu = this.nextElementSibling;
                submenu.classList.toggle('show');
            });
        });
        // for video activity
        const videoModal = document.getElementById('videoModal');
        const videoFrame = document.getElementById('videoFrame');
        const videoModalLabel = document.getElementById('videoModalLabel');

        videoModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const videoUrl = button.getAttribute('data-video-url');
            const videoHeading = button.getAttribute('data-course-name');
            videoModalLabel.innerHTML = videoHeading;
            videoFrame.src = videoUrl + "?autoplay=1";

        });

        function stopVideo() {
            videoFrame.src = "";
        }
        function toggleVideoEdit(button) {
            const container = button.closest('.video-edit-container');
            const videoUrl = container.getAttribute('data-video-url') || '';

            const input = container.querySelector('.video-url-input');
            const saveBtn = container.querySelector('.save-video-btn');
            const editBtn = container.querySelector('.edit-video-btn');

            // Show input + save button, hide edit
            input.classList.remove('d-none');
            saveBtn.classList.remove('d-none');
            editBtn.classList.add('d-none');

            input.value = videoUrl;
            input.focus();
        }

        function convertToEmbedUrl(url) {
            const youtubeRegex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w\-]+)/;
            const match = url.match(youtubeRegex);
            return match ? `https://www.youtube.com/embed/${match[1]}` : url;
        }

        function saveVideoUrl(button) {
            const container = button.closest('.video-edit-container');
            const courseId = button.getAttribute('data-courseid');
            const sectionId = button.getAttribute('data-sectionid');
            const input = container.querySelector('.video-url-input');
            const editBtn = container.querySelector('.edit-video-btn');
            const msgBox = container.querySelector('.message-box');

            const videoUrl = input.value.trim();
            const sesskey = M.cfg.sesskey;

            button.disabled = true;

            fetch('update_video.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    courseid: courseId,
                    sectionid: sectionId,
                    videourl: videoUrl,
                    sesskey: sesskey
                })
            })
                .then(res => res.json())
                .then(data => {
                    button.disabled = false;

                    // Show message
                    msgBox.innerHTML = `<i class="alert-${data.status === 'success' ? 'success' : 'danger'}" style='font-size: 14px;text-align: center;' >${data.message}</i>`;
                    setTimeout(() => { msgBox.innerHTML = ''; }, 3000);

                    if (data.status === 'success') {
                        container.setAttribute('data-video-url', videoUrl);

                        // Hide input and save, show edit
                        input.classList.add('d-none');
                        button.classList.add('d-none');
                        editBtn.classList.remove('d-none');

                        // ✅ Update Play Video button's data-video-url immediately
                        const embedUrl = convertToEmbedUrl(videoUrl);
                        const playBtn = document.querySelector(`.play-video-btn[data-sectionid="${sectionId}"]`);
                        if (playBtn) {
                            playBtn.setAttribute('data-video-url', embedUrl);
                        }
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    msgBox.innerHTML = `<div class="alert alert-danger">❌ Error: ${err.message}</div>`;
                    setTimeout(() => { msgBox.innerHTML = ''; }, 3000);
                });
        }

        function regenerateSection(courseid, sectionid, button) {
            const msgBox = button.closest('.card-body').querySelector('.message-box');
            const sesskey = M.cfg.sesskey;

            button.disabled = true;
            button.innerHTML = '⏳ Regenerating...';

            fetch(M.cfg.wwwroot + '/local/course_ai/regenerate_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    courseid,
                    sectionid,
                    sesskey
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error ${res.status}`);
                    return res.json();
                })
                .then(data => {
                    button.disabled = false;
                    button.innerHTML = '🔄 Regenerate';

                    msgBox.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'}">${data.message}</div>`;
                    setTimeout(() => msgBox.innerHTML = '', 3000);

                    if (data.success) {
                        // Optionally refresh section content via AJAX or show updated DOM
                    }
                })
                .catch(err => {
                    console.error('Fetch failed:', err);
                    button.disabled = false;
                    button.innerHTML = '🔄 Regenerate';
                    msgBox.innerHTML = `<div class="alert alert-danger">❌ Request failed: ${err.message}</div>`;
                    setTimeout(() => msgBox.innerHTML = '', 3000);
                });
        }

        // Optional: Stop video also when modal closes via backdrop or Esc
        videoModal.addEventListener('hidden.bs.modal', stopVideo);

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('courseSearch');
            const searchButton = document.getElementById('searchButton');
            const container = document.querySelector('#v-pills-tab'); // adjust to your course tab container

            function filterCourses() {
                const query = searchInput.value.toLowerCase().trim();
                console.log("Search triggered with query:", query);

                const courseTabs = Array.from(document.querySelectorAll('button.nav-link.draggable.d-flex.align-items-center'));
                console.log("Total course buttons found:", courseTabs.length);

                const matchedCourses = [];
                const unmatchedCourses = [];

                courseTabs.forEach(tab => {
                    const idText = tab.id.toLowerCase();
                    const courseText = tab.querySelector('.course-text')?.innerText.toLowerCase() || '';
                    const match = idText.includes(query) || courseText.includes(query);

                    const nextCard = tab.nextElementSibling;
                    if (nextCard && nextCard.classList.contains('card')) {
                        nextCard.style.display = match ? '' : 'none';
                    }

                    if (match) {
                        tab.style.display = 'flex';
                        matchedCourses.push({ tab, nextCard });
                    } else {
                        tab.style.display = 'none';
                        if (nextCard) nextCard.style.display = 'none';
                        unmatchedCourses.push({ tab, nextCard });
                    }
                });

                // Append matched courses at the top
                matchedCourses.forEach(({ tab, nextCard }) => {
                    container.appendChild(tab);
                    if (nextCard && nextCard.classList.contains('card')) {
                        container.appendChild(nextCard);
                    }
                });

                // Optionally re-append unmatched (hidden anyway)
                unmatchedCourses.forEach(({ tab, nextCard }) => {
                    container.appendChild(tab);
                    if (nextCard && nextCard.classList.contains('card')) {
                        container.appendChild(nextCard);
                    }
                });

                if (matchedCourses.length > 0) {
                    console.log('Matched courses:', matchedCourses.map(m => m.tab.id));
                } else {
                    console.log('No courses matched your search.');
                }
            }

            if (searchButton) {
                searchButton.addEventListener('click', filterCourses);
            }

            if (searchInput) {
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') filterCourses();
                });
            }
        });

    </script>
</body>
<?php

echo $OUTPUT->footer();
?>