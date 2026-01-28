<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


require_once ('../../config.php');

require_once 'lib.php';
global $DB, $CFG, $USER;

require_login();

// redirect to report page if siteadmin
if (is_siteadmin()) {
    redirect('report.php');
}
$PAGE->requires->css(new moodle_url("/local/mydashboard/style.css"));
$PAGE->set_url('/local/mydashboard/index.php');
$PAGE->set_title('My Dashboard');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('title', 'local_mydashboard'));

echo $OUTPUT->header();

$userid = $USER->id;

$user = $DB->get_record('user', array('id' => $userid));

$output['fullname'] = $user->firstname . ' ' . $user->lastname;
$output['email'] = $user->email;
$output['department'] = $user->department;
$output['designation'] = $user->institution;
$output['empcode'] = $user->username;
$output['phone'] = $user->phone1;
$usercontext = context_user::instance($user->id);
$output['profileimage'] = $src = $CFG->wwwroot . "/pluginfile.php/$usercontext->id/user/icon/f1";

set_my_rank($userid);
$output['my_rank'] = get_my_rank($userid);

$nextlevel = get_next_level($userid);
$output['points_needed'] = $nextlevel[0];
$output['grade_needed'] = $nextlevel[1];
$output['nextlevel'] = $nextlevel[2];
$output['lifetime_points'] = get_lifetime_points($userid);
$output['available_points'] = get_available_points($userid);
$output['total_points'] = get_total_points($userid);
$output['login_points'] = $l = get_points($userid, 'login');
$output['quiz_points'] = $q = get_points($userid, 'quiz');
$output['spinwheel_points'] = $s = get_points($userid, 'spinwheel');
$output['rewards_received_points'] = $r = get_rewards_points_received($userid);
$output['admin_points'] = $r = get_admin_points_received($userid);
//get the single activity daily quiz course
$course = $DB->get_record('course', array('shortname' => 'dailyquiz'));
$output['dailyquiz_url'] = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
$output['pollurl'] = $CFG->wwwroot . '/local/mydashboard/poll.php';

//spin allowed
$output['spinbutton'] = get_spinwheel_button($userid);
//leaderboard
$output['leaderboard'] = get_leaderboard();
$output['leaderboard_top'] = get_leaderboard_top();
//spin wheel chart
$spinsgraph = get_lastfive_spin($userid);
$logingraph = get_lastfive_login($userid);
$quizgraph = get_lastfive_quiz($userid);
$mypointsgraph = "";

$cards = get_my_scratchcard($userid);
//print_object($cards);
$output['scratchcards'] = $cards[0];
$output['scratchcounter'] = get_scratch_counter($userid);

list($scratch1, $scratch2, $scratch3) = $cards[1];
list($n1, $n2, $n3) = $cards[2];

$context_data = [
    'user_stats' => $output,

    // Chart values
    'chartdata' => [
        'login'   => (int) $output['login_points'],
        'spin'    => (int) $output['spinwheel_points'],
        'rank'    => (int) $output['my_rank'],
        'quiz'    => (int) $output['quiz_points'],
        'rewards' => (int) $output['rewards_received_points'],

        // Weekly bar chart (dynamic last 7 days)
        'weekgraph' => [
            get_points_last7($userid, 'mon'),
            get_points_last7($userid, 'tue'),
            get_points_last7($userid, 'wed'),
            get_points_last7($userid, 'thu'),
            get_points_last7($userid, 'fri'),
            get_points_last7($userid, 'sat'),
            get_points_last7($userid, 'sun'),
        ]
    ]
];

// print_r($context_data);


echo $OUTPUT->render_from_template('local_mydashboard/landing_page', $context_data);
?>

<!--SUNIL-->
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">-->
<!--<link rel="stylesheet" href="external/all.css"
      integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />-->

<!--****custom css****-->
<link rel="stylesheet" href="sunil/style.css">
<!-- Google Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
<!-- DATATABLES WITH BUTTONS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script> -->

<!--SUNIL END-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<!--<link rel="stylesheet" href="main.css" type="text/css" />
 Theme style 
<link rel="stylesheet" href="external/dist/css/adminlte.min.css">

<script src="external/plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="external/plugins/chart.js/Chart.min.js"></script>
<script src="external/plugins/sparklines/sparkline.js"></script>
 AdminLTE App 
<script src="external/dist/js/adminlte.min.js"></script>
 AdminLTE for demo purposes 
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>-->
<!--<script src="external/dist/js/demo.js"></script>-->
<script type="text/javascript" src="external/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="external/wScratchPad.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script src="external/Winwheel.js"></script>
<script src="external/TweenMax.min.js"></script>


<style>

 #region-main-box #region-main div[role="main"] {
     background: transparent;
    box-shadow: none !important;
}

#page-local-mydashboard-index #page-header {display: none;}
.text-game {
background: linear-gradient(90deg, #003152, #ec9707);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
    /* MAIN CARD STYLING */
.leader-wrapper {
    background: #ffffff;
    border-radius: 18px;
    padding: 35px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.05);
}

/* TOP 3 USER CARDS */
.top-card {
    background: #fff;
    border-radius: 18px;
    padding: 30px 25px;
    text-align: center;
    border: 3px solid transparent;
    transition: .3s;
}

.top-card.gold { border-color: #f4cb24; }
.top-card.silver { border-color: #d9d9d9; }
.top-card.bronze { border-color: #ffb879; }

.top-card:hover { transform: translateY(-5px); }

/* PROFILE IMAGE */
.top-user-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f4cb24;
    animation: zoomPulse 2.5s ease-in-out infinite;
}

/* ZOOM ANIMATION */
@keyframes zoomPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}


/* TOP 3 LAYOUT */
.top-three-container {
    display: flex;
    gap: 20px;
    margin-bottom: 35px;
}

.top-three-item {
    flex: 1;
}

    .scratchpad {
        width: 15%;
        height: 160px;
        border: solid 5px;
        display: inline-block;
    }
    .legend-2col {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    row-gap: 4px;
    column-gap: 10px;
    margin-top: 10px;
    font-size: 14px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(.9); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn { animation: fadeIn .25s ease-out; }
/* Fast smooth spin */
.animate-spin-fast {
    animation: spin 0.4s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.shadow-inner {
    box-shadow: inset 0 0 15px rgba(255, 200, 0, 0.4);
}
/* MAIN CARD STYLING */
.leader-wrapper {
    background: #ffffff;
    border-radius: 18px;
    padding: 35px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.05);
}

/* TOP 3 USER CARDS */
.top-card {
    background: #fff;
    border-radius: 18px;
    padding: 30px 25px;
    text-align: center;
    border: 3px solid transparent;
    transition: .3s;
}

.top-card.gold { border-color: #f4cb24; }
.top-card.silver { border-color: #d9d9d9; }
.top-card.bronze { border-color: #ffb879; }

.top-card:hover { transform: translateY(-5px); }

/* PROFILE IMAGE */
.top-user-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f4cb24;
    animation: zoomPulse 2.5s ease-in-out infinite;
}

/* ZOOM ANIMATION */
@keyframes zoomPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

/* TABLE HEADER */
.leader_table_d thead {
    background: #05245c;
    color: #fff;
    border-radius: 12px;
}

.leader_table_d th {
    padding: 14px 10px;
    font-weight: 600;
    text-transform: none;
}

/* TABLE ROW */
.leader_table tbody tr {
    background: #f7f9fc;
    border-radius: 14px;
    margin-bottom: 10px;
}

.leader_table tbody tr td {
    padding: 18px 10px;
    vertical-align: middle;
    font-size: 15px;
    color: #333;
}

/* BADGE STYLE */
.badge-count {
    background: #f4cb24;
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: bold;
    color: #000;
}

/* RANK NUMBER CIRCLE */
.rank-circle {
    width: 36px;
    height: 36px;
    background: #e8edf5;
    color: #555;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: 600;
}

/* TOP 3 LAYOUT */
.top-three-container {
    display: flex;
    gap: 20px;
    margin-bottom: 35px;
}

.top-three-item {
    flex: 1;
}
#demo1, #demo2, #demo3 {
    border-radius: 10px;
    border: solid 2px gold;
    box-shadow: 0 4px 8px rgba(244, 201, 8, 0.75);
    overflow: hidden;
}
</style>
<script>

const donutCtx = document.getElementById('donutChart').getContext('2d');

// Plugin: Show text inside the center of donut
const centerText = {
    id: 'centerText',
    afterDraw(chart, args, options) {
        const {ctx, chartArea} = chart;

        // Get actual center of doughnut
        const centerX = chart.getDatasetMeta(0).data[0].x;
        const centerY = chart.getDatasetMeta(0).data[0].y;

        ctx.save();
        ctx.font = "600 10px sans-serif";  // Smaller text
        ctx.fillStyle = "#003152";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";

        ctx.fillText(options.text, centerX, centerY);
        ctx.restore();
    }
};


// DATA
const labels = ['Login points', 'Speen Wheel', 'Leadership', 'Quiz points', 'Rewards'];
const values = [
    <?php echo $context_data['chartdata']['login']; ?>,
    <?php echo $context_data['chartdata']['spin']; ?>, <?php echo $context_data['chartdata']['rank']; ?>, <?php echo $context_data['chartdata']['quiz']; ?>, <?php echo $context_data['chartdata']['rewards']; ?>
];

const colors = ['#003152', 'rgb(36, 71, 143)', 'hsl(220 85% 25%)', 'hsl(48 96% 50%)', 'rgb(251, 212, 55)'];

// CREATE CHART
let donutChart = new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{
            label: 'Points',
            data: values,
            backgroundColor: colors,
            hoverOffset: 15,        // 🔥 More zoom effect
            borderWidth: 2
        }]
    },
   options: {
    responsive: true,
    cutout: '60%',

    layout: {
        padding: {
            top: 25,
            bottom: 25,
            left: 25,
            right: 25
        }
    },

    interaction: {
        mode: 'nearest',
        intersect: true
    },

    plugins: {
        legend: { display: false },

        tooltip: {
            enabled: false
        },

        centerText: { text: "" }
    },

    onHover: function(evt, items) {
        if (items.length > 0) {
            const index = items[0].index;
            donutChart.options.plugins.centerText.text =
                labels[index] + ": " + values[index];
            donutChart.update();
        }
    }
},
    plugins: [centerText]
});

// Reset center text on mouse leave
document.getElementById("donutChart").addEventListener("mouseleave", function () {
    donutChart.options.plugins.centerText.text = "";
    donutChart.update();
});

// 🔥 Custom 2-column legend
function renderCustomLegend() {
    const legendContainer = document.getElementById("customLegend");
    legendContainer.innerHTML = "";

    labels.forEach((label, i) => {
        const div = document.createElement("div");
        div.classList.add("legend-item");

        div.innerHTML = `
            <div class="legend-dot" style="background:${colors[i]}"></div>
            <span style="font-size: 10px;" >${label} (${values[i]})</span>
        `;

        legendContainer.appendChild(div);
    });
}

renderCustomLegend();


const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            label: 'Points Earned',
            borderRadius: 8,  
            data: [<?php echo implode(', ', $context_data['chartdata']['weekgraph']); ?>],
            backgroundColor: '#003152'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});



// Open modal and load content
function openPointsModal() {
    const modal = document.getElementById("pointsModal");
    const box = document.getElementById("pointsModalBox");

    // Load PHP table content
    $("#pointsModalContent").load("mypoint.php", function () {
        // Initialize DataTable after table is loaded
        const table = $("#pointsTable").DataTable({
            responsive: true,
            lengthChange: true,
            pageLength: 8, // show 8 entries per page
            autoWidth: false,
            dom: 'Bfrtip', // Buttons, filter (search bar), table, info
            buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"]
        });

        // Move buttons above table (Tailwind-friendly wrapper)
        table.buttons().container().prependTo('#pointsModalContent');
    });

    // Show modal with fade & scale animation
    modal.classList.remove("hidden");
    setTimeout(() => {
        modal.classList.add("opacity-100");
        box.classList.remove("scale-95", "opacity-0");
        box.classList.add("scale-100", "opacity-100");
    }, 10);
}

// Close modal
function closePointsModal() {
    const modal = document.getElementById("pointsModal");
    const box = document.getElementById("pointsModalBox");

    // Reverse animation
    modal.classList.remove("opacity-100");
    box.classList.remove("scale-100", "opacity-100");
    box.classList.add("scale-95", "opacity-0");

    // Clear content after animation
    setTimeout(() => {
        modal.classList.add("hidden");
        $("#pointsModalContent").html("");
    }, 300);
}

// Event listeners
document.getElementById("closePointsModal").addEventListener("click", closePointsModal);

// Close modal on background click
document.getElementById("pointsModal").addEventListener("click", function (e) {
    if (e.target.id === "pointsModal") {
        closePointsModal();
    }
});

// new code above here 
var cnt1 = 0, cnt2 = 0, cnt3 = 0;

// ---------------------- HELPER FUNCTION: SHOW REWARD POPUP -------------------------
function showScratchReward(points) {
    $("#scratchRevealModal").removeClass("hidden").addClass("flex");
    $("#rewardPoints").text(points + " Points");
    $("#wonPoints1").text(points);

    if (points <= 10) {
        $('#popupimage').html('<img src="./images/pop/1-10.gif" width="200">');
    } else if (points <= 20) {
        $('#popupimage').html('<img src="./images/pop/10-20.gif" width="200">');
    } else {
        $('#popupimage').html('<img src="./images/pop/21-50.gif" width="200">');
    }

    startConfetti();
}


// ---------------------- CONFETTI ANIMATION -------------------------
function startConfetti() {
    const duration = 2000;
    const end = Date.now() + duration;

    (function frame() {
        confetti({ particleCount: 5, spread: 80, origin: { y: 0.2 } });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
}

// ---------------------- CLOSE POPUP -------------------------
$("#closeScratchModal").on("click", function () {
    $("#scratchRevealModal").addClass("hidden").removeClass("flex");
    location.reload(); // reset scratch cards
});

// ---------------------- SCRATCH CARD INIT -------------------------
var scratchCards = [ 
    { id: "demo1", fg: '<?php echo $n1 ?>', bg: '<?php echo $scratch1 ?>', cnt: 0 },
    { id: "demo2", fg: '<?php echo $n2 ?>', bg: '<?php echo $scratch2 ?>', cnt: 0 },
    { id: "demo3", fg: '<?php echo $n3 ?>', bg: '<?php echo $scratch3 ?>', cnt: 0 }
];

// ---------------------- HELPER FUNCTION: SHOW WIN POPUP -------------------------


// ---------------------- HELPER FUNCTION: SHOW BETTER LUCK POPUP -------------------------
function showBetterLuck() {
    $("#betterLuckModal").removeClass("hidden").addClass("flex");
}

// ---------------------- CONFETTI -------------------------
function startConfetti() {
    const duration = 3000;
    const end = Date.now() + duration;

    (function frame() {
        confetti({ particleCount: 5, spread: 80, origin: { y: 0.2 } });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
}

// ---------------------- CLOSE POPUPS -------------------------
$(".closeModal").on("click", function () {
    $(this).closest(".modal").addClass("hidden").removeClass("flex");
    location.reload();
});

// ---------------------- SCRATCH CARD INIT -------------------------
scratchCards.forEach(function(card, index) {
    let clicked = false;

    $('#' + card.id).wScratchPad({
        fg: card.fg,
        bg: card.bg,
        size: 45,
        scratchMove: function (e, percent) {
            if (percent > 50 && !clicked) {
                clicked = true;
                this.clear();

                const spoint = parseInt($('#' + card.id).attr('point'));
                const scid   = $('#' + card.id).attr('scid');

                $.ajax({
                    url: "ajax.php",
                    type: "post",
                    data: { action: "SCRATCHCARD", spoint: spoint, scid: scid },
                    success: function(res) {
                        if (parseInt(res) > 0) {
                            $('#spinpoint').html(spoint);
                            showScratchReward(spoint); // show win popup
                        } else {
                            showBetterLuck(); // show better luck popup
                        }
                    }
                });
            }
        }
    });
});



let spinning = false;

// Utility: get today key
function getTodayKey() {
    const d = new Date();
    return `spin_${d.getFullYear()}_${d.getMonth() + 1}_${d.getDate()}`;
}

// Replace spin button with message
function replaceSpinButton() {
    const btn = document.getElementById("spinBtn");
    if (!btn) return;

    btn.outerHTML = `
        <div class=" italic bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-2 rounded" style="font-size:12px;">
            You have won today's luck on wheel, Try next day
        </div>
    `;
}

// On page load – if already spun today
document.addEventListener("DOMContentLoaded", () => {
    const todayKey = getTodayKey();
    if (localStorage.getItem(todayKey)) {
        replaceSpinButton();
    }
});

function startSpin() {
    if (spinning) return;

    const todayKey = getTodayKey();
    // if (localStorage.getItem(todayKey)) {
    //     replaceSpinButton();
    //     return;
    // }

    spinning = true;

    const icon = document.querySelector(".spin-refresh-icon");
    icon.classList.add("animate-spin-fast");

    // 🎁 Reward logic (unchanged)
    const pointsList = [0, 5, 7, 9, 13, 17, 21, 30, 50];
    const reward = pointsList[Math.floor(Math.random() * pointsList.length)];

    setTimeout(() => {
        icon.classList.remove("animate-spin-fast");
        spinning = false;

        // Save spin for today
        // localStorage.setItem(todayKey, "1");

        // Replace button immediately after spin
        replaceSpinButton();

        // Show modal
        document.getElementById("wonPoints").innerHTML = reward;
        document.getElementById("spinSuccessModal").classList.remove("hidden");

        if (reward > 0) startConfetti();

        // Update points UI
        if (reward > 0) {
            const sp = document.querySelector(".spin-point");
            const ap = document.querySelector(".available-points");

            if (sp) sp.innerHTML = parseInt(sp.innerHTML) + reward;
            if (ap) ap.innerHTML = parseInt(ap.innerHTML) + reward;
        }

        // AJAX save
        $.ajax({
            url: "ajax.php",
            type: "POST",
            data: {
                point: reward,
                action: "SPINWHEELPOINT"
            }
        });

    }, 1500);
}

function closeSpinPopup() {
    document.getElementById("spinSuccessModal").classList.add("hidden");
    window.location.reload();
}


    //AJAX

$('body').on('click', '#gift-reward', function () {

    let av_poiints = parseInt($('.available-points').text()) || 0;

    if (av_poiints <= 0) {
        $('#searchtext, #searchuser, #sharepoints').hide();
        $('#getmatch').html('<div class="text-center p-3">You have no points</div>');
        return;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        dataType: 'json', // ✅ MUST BE JSON
        data: {
            action: 'SEARCHUSERS',
            av_poiints: av_poiints
        },
        success: function (res) {
            if (res.status === 1) {
                $('#getmatch').html(res.html); // ✅ render HTML only
            } else {
                $('#getmatch').html('<div class="text-center p-3">No match found</div>');
            }

            $('#exampleModal').modal('show'); // ✅ show popup
        }
    });
});


    $('body').on('click', '#redeem-point', function () {
        var av_poiints = $('.available-points').html();
        if (av_poiints > 0) {
            $.ajax({
                url: 'ajax.php',
                type: 'post',
                dataType: 'json',
                data: { action: 'GETREDEEMPOINTS', av_poiints: av_poiints },
                success: function (res) {
                      $('#modalAvailablePoints').text(res.total);
            $('#lifetimePoints').text(res.lifetime);
            $('#burnoutPoints').text(res.burnout);
            $('#totalPoints').text(res.total);
            $('#redeemablePoints').text(res.redeemable);

            $('#idredeem-points').val(res.redeemable);

            $('#redeemModal').modal('show');
                }
            });
        } else {
            $('#redeemable').html('You have no points');
        }

    });
    function loadRedeemPopup(avPoints) {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'GETREDEEMPOINTS',
            av_poiints: avPoints
        },
        success: function (res) {

            $('#lifetimePoints').text(res.lifetime);
            $('#burnoutPoints').text(res.burnout);
            $('#totalPoints').text(res.total);
            $('#redeemablePoints').text(res.redeemable);
            $('#idredeem-points').val(res.redeemable);

            // Enable / Disable redeem button
            if (res.redeemable === 5000) {
                $('#redeemnow').prop('disabled', false)
                    .css('opacity', '1');
            } else {
                $('#redeemnow').prop('disabled', true)
                    .css('opacity', '0.5');
            }

            $('#redeemModal').modal('show');
        }
    });
}


  $('#searchuser').on('click', function () {

    let search = $('#searchtext').val().trim();
    let av_poiints = parseInt($('.available-points').text()) || 0;

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        dataType: 'json', // ✅ JSON
        data: {
            action: 'SEARCHUSERS',
            search: search,
            av_poiints: av_poiints
        },
        success: function (res) {
            if (res.status === 1) {
                $('#getmatch').html(res.html);
            } else {
                $('#getmatch').html('<div class="text-center p-3 text-muted">No match found</div>');
            }
        }
    });
});


$('body').on('click', '#redeemnow', function (e) {
    e.preventDefault();

    var point = parseInt($('#idredeem-points').val());

    if (point !== 5000) {
        alert('Minimum 5000 points required to redeem');
        return;
    }

    if (confirm("Are you sure you want to redeem the points?\nThe action cannot be undone.")) {

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: {
                action: 'REDEEMNOW',
                point: 5000
            },
            success: function (response) {
                if (response == 1) {
                    window.location.reload();
                } else {
                    alert('The redeemable amount is invalid');
                }
            }
        });
    }
});



    $('body').on('click', '#sharepoints', function (e) {
        e.preventDefault();

        var points = $('#sharepointsform').serialize();
        if (confirm("Are you sure you want to share the points. \n The action cannot be undone.")) {
            $.ajax({
                url: 'ajax.php?action=SHAREPOINTS',
                type: 'post',
                data: points,
                success: function (response) {
                    if (response == 1) {
                        window.location.reload();
                    } else if (response == 2) {
                        alert('You exceed the daily sharing points limit');
                    } else {
                        alert('You exceed the sharing point from you available points');
                    }
                }
            });
        }
    });

    //SPIN CHART
    google.charts.load('current', { packages: ['corechart', 'line',] });
    google.charts.setOnLoadCallback(drawBasic);

    function drawBasic() {
        var data = new google.visualization.DataTable();
        data.addColumn('number', 'X');
        data.addColumn('number', 'Spin Points');
        data.addRows([
            <?php echo $spinsgraph; ?>
            //            [0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
        ]);
        var options = {
            hAxis: {
                title: 'Days'
            },
            vAxis: {
                title: 'Points'
            }
        };
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    }

    // LOAD CHARTS
google.charts.setOnLoadCallback(drawLogin);
google.charts.setOnLoadCallback(drawQuiz);

// ===== LOGIN CHART =====
function drawLogin() {
    var data = new google.visualization.DataTable();
    data.addColumn('number', 'X');
    data.addColumn('number', 'Login Points');
    data.addRows([
        <?php echo $logingraph; ?>
    ]);

    var options = {
        hAxis: { 
            baselineColor: '#003152', 
            textStyle: { color: '#004c8c', fontSize: 12 },
            gridlines: { color: 'transparent' } // hide horizontal gridlines
        },
        vAxis: { 
            baselineColor: '#003152', 
            textStyle: { color: '#004c8c', fontSize: 12 },
            gridlines: { color: 'transparent' } // hide vertical gridlines
        },
        legend: 'none',
        backgroundColor: 'transparent',
        colors: ['#F5B700'],
        curveType: 'function', // smooth curve
        lineWidth: 2,
        pointSize: 6,
        chartArea: { left: 40, top: 10, width: '90%', height: '75%' }
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_login'));
    chart.draw(data, options);
}


// ===== QUIZ CHART =====
function drawQuiz() {
    var data = new google.visualization.DataTable();
    data.addColumn('number', 'X');
    data.addColumn('number', 'Quiz Points');
    data.addRows([
        <?php echo $quizgraph; ?>
    ]);

    var options = {
        backgroundColor: 'transparent',
        legend: 'none',

        colors: ['#0b2a5b'], // dark blue like image

        curveType: 'function',   // smooth curve
        lineWidth: 2,
        pointSize: 7,            // solid visible dots
        pointShape: 'circle',

        hAxis: {
            ticks: [0, 1, 2, 3, 4,5],
            baselineColor: '#6b7280',      // visible axis
            textStyle: {
                color: '#6b7280',
                fontSize: 12
            },
            gridlines: {
                color: 'transparent',          // light dashed grid
                count: 5
            },
            minorGridlines: {
                color: 'transparent'
            }
        },

        vAxis: {
            baselineColor: 'transparent',
            textStyle: {
                color: '#6b7280',
                fontSize: 12
            },
            gridlines: {
                color: '#e5e7eb'
            },
            minorGridlines: {
                color: 'transparent'
            }
        },

        chartArea: {
            left: 40,
            top: 15,
            width: '92%',
            height: '70%'
        }
    };

    var chart = new google.visualization.LineChart(
        document.getElementById('chart_quiz')
    );
    chart.draw(data, options);
}





    var a = parseInt($('.count-container').html());
    if (a <= 0) {
        $('.count-container').css('display', 'none');
    }

    $(document).ready(function () {
        var show_btn = $('.sendmsg');
        var show_btn = $('.sendmsg');
        //$("#testmodal").modal('show');

        show_btn.click(function () {
            $("#testmodal").modal('show');
        })
    });

    $(function () {
        $('.sendmsg').on('click', function (e) {
            var userid = $(this).attr("value");
            $('#submitdata').on('click', function (e) {
                var textareavalue = $('#exampleFormControlTextarea1').val();
                // var userid = $('.sendmsg').attr('value');
                $.ajax({
                    url: 'sendmsg.php',
                    type: 'post',
                    data: { userid: userid, textareavalue: textareavalue },
                    success: function (response) {
                        window.location.reload();
                    }
                });
            });

            e.preventDefault();
        });
    });

</script>

<style>
    #page-local-mydashboard-index .has-blocks {
        display: none;
    }
</style>
<?php
echo $OUTPUT->footer();
