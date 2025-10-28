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
];

echo $OUTPUT->render_from_template('local_mydashboard/landing_page', $context_data);
?>

<!--SUNIL-->
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">-->
<!--<link rel="stylesheet" href="external/all.css"
      integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />-->

<!--****custom css****-->
<link rel="stylesheet" href="sunil/style.css">
<script src="external/jquery.min.js"
    integrity="sha512-3P8rXCuGJdNZOnUx/03c1jOTnMn3rP63nBip5gOP2qmUh5YAdVAvFZ1E+QLZZbC1rtMrQb+mah3AfYW11RUrWA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script> -->

<!--SUNIL END-->

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
<script src="external/Winwheel.js"></script>
<script src="external/TweenMax.min.js"></script>
<style>
    .scratchpad {
        width: 15%;
        height: 160px;
        border: solid 5px;
        display: inline-block;
    }
</style>
<script>

    var cnt1 = 0;
    var cnt2 = 0;
    var cnt3 = 0;
    $('#demo1').wScratchPad({
        fg: '<?php echo $n1 ?>',
        bg: '<?php echo $scratch1 ?>',

        scratchMove: function (e, percent) {

            if (percent > 50) {

                this.clear();
                var spoint = $('#demo1').attr('point');
                var scid = $('#demo1').attr('scid');

                if (cnt1 == 0) {
                    cnt1++;
                    $.ajax({
                        url: 'ajax.php',
                        type: 'post',
                        dataType: 'html',
                        data: { action: 'SCRATCHCARD', spoint: spoint, scid: scid },
                        success: function (res) {
                            //                                alert('You got ' + spoint + ' points');
                            if (parseInt(res) > 0) {
                                $('#spinpoint').html(spoint);
                                if (parseInt(spoint) <= 10) {
                                    $('#popupimage').html('<img src="./images/pop/1-10.gif" width="200">');
                                } else if (parseInt(spoint) > 10 && parseInt(spoint) <= 20) {
                                    $('#popupimage').html('<img src="./images/pop/10-20.gif" width="200">');
                                } else if (parseInt(spoint) > 20) {
                                    $('#popupimage').html('<img src="./images/pop/21-50.gif" width="200">');
                                }
                                $('#spindWheel').modal('show');//now its working
                            } else {
                                alert('Better luck next time');
                                window.location.reload();
                            }
                        }
                    });
                } else {
                    cnt1++;

                }
            }
        }
    });
    $('#demo2').wScratchPad({
        fg: '<?php echo $n2 ?>',
        bg: '<?php echo $scratch2 ?>',
        scratchMove: function (e, percent) {
            console.log(percent);

            if (percent > 50) {
                this.clear();
                var spoint = $('#demo2').attr('point');
                var scid = $('#demo2').attr('scid');

                if (cnt2 == 0) {
                    cnt2++;
                    $.ajax({
                        url: 'ajax.php',
                        type: 'post',
                        dataType: 'html',
                        data: { action: 'SCRATCHCARD', spoint: spoint, scid: scid },
                        success: function (res) {
                            //                                alert('You got ' + spoint + ' points');

                            if (res > 0) {
                                $('#spinpoint').html(spoint);
                                if (parseInt(spoint) <= 10) {
                                    $('#popupimage').html('<img src="./images/pop/1-10.gif" width="200">');
                                } else if (parseInt(spoint) > 10 && parseInt(spoint) <= 20) {
                                    $('#popupimage').html('<img src="./images/pop/10-20.gif" width="200">');
                                } else if (parseInt(spoint) > 20) {
                                    $('#popupimage').html('<img src="./images/pop/21-50.gif" width="200">');
                                }
                                $('#spindWheel').modal('show');//now its working
                            } else {
                                alert('Better luck next time');
                            }
                        }
                    });
                } else {
                    cnt2++;

                }
            }
        }
    });
    $('#demo3').wScratchPad({
        fg: '<?php echo $n3 ?>',
        bg: '<?php echo $scratch3 ?>',
        scratchMove: function (e, percent) {
            console.log(percent);

            if (percent > 50) {
                this.clear();
                var spoint = $('#demo3').attr('point');
                var scid = $('#demo3').attr('scid');

                if (cnt3 == 0) {
                    cnt3++;
                    $.ajax({
                        url: 'ajax.php',
                        type: 'post',
                        dataType: 'html',
                        data: { action: 'SCRATCHCARD', spoint: spoint, scid: scid },
                        success: function (res) {
                            if (res > 0) {
                                $('#spinpoint').html(spoint);
                                if (parseInt(spoint) <= 10) {
                                    $('#popupimage').html('<img src="./images/pop/1-10.gif" width="200">');
                                } else if (parseInt(spoint) > 10 && parseInt(spoint) <= 20) {
                                    $('#popupimage').html('<img src="./images/pop/10-20.gif" width="200">');
                                } else if (parseInt(spoint) > 20) {
                                    $('#popupimage').html('<img src="./images/pop/21-50.gif" width="200">');
                                }
                                $('#spindWheel').modal('show');//now its working
                            } else {
                                alert('Better luck next time');
                            }
                        }
                    });
                } else {
                    cnt3++;

                }
            }
        }
    });


    // Create new wheel object specifying the parameters at creation time.
    var theWheel = new Winwheel({
        'numSegments': 12, // Specify number of segments.
        'outerRadius': 120, // Set outer radius so wheel fits inside the background.
        'textFontSize': 20, // Set font size as desired.
        'segments': // Define segments including colour and text.
            [
                { 'fillStyle': '#eae56f', 'text': '00' },
                { 'fillStyle': '#89f26e', 'text': '5' },
                { 'fillStyle': '#7de6ef', 'text': '7' },
                { 'fillStyle': '#a87b32', 'text': '5' },
                { 'fillStyle': '#eae56f', 'text': '9' },
                { 'fillStyle': '#89f26e', 'text': '13' },
                { 'fillStyle': '#7de6ef', 'text': '5' },
                { 'fillStyle': '#e7706f', 'text': '17' },
                { 'fillStyle': '#89f26e', 'text': '00' },
                { 'fillStyle': '#e7706f', 'text': '21' },
                { 'fillStyle': '#eae56f', 'text': '30' },
                { 'fillStyle': '#a83255', 'text': '50' }
            ],
        'animation': // Specify the animation to use.
        {
            'type': 'spinToStop',
            'duration': 5, // Duration in seconds.
            'spins': 8, // Number of complete spins.
            'callbackFinished': alertPrize
        }
    });

    // Vars used by the code in this page to do power controls.
    var wheelPower = 0;
    var wheelSpinning = false;

    // -------------------------------------------------------
    // Function to handle the onClick on the power buttons.
    // -------------------------------------------------------
    function powerSelected(powerLevel) {
        // Ensure that power can't be changed while wheel is spinning.
        if (wheelSpinning == false) {
            // Reset all to grey incase this is not the first time the user has selected the power.
            document.getElementById('pw1').className = "";
            document.getElementById('pw2').className = "";
            document.getElementById('pw3').className = "";

            // Now light up all cells below-and-including the one selected by changing the class.
            if (powerLevel >= 1) {
                document.getElementById('pw1').className = "pw1";
            }

            if (powerLevel >= 2) {
                document.getElementById('pw2').className = "pw2";
            }

            if (powerLevel >= 3) {
                document.getElementById('pw3').className = "pw3";
            }

            // Set wheelPower var used when spin button is clicked.
            wheelPower = powerLevel;

            // Light up the spin button by changing it's source image and adding a clickable class to it.
            document.getElementById('spin_button').src = "spin_on.png";
            document.getElementById('spin_button').className = "clickable";
        }
    }

    // -------------------------------------------------------
    // Click handler for spin button.
    // -------------------------------------------------------
    function startSpin() {
        // Ensure that spinning can't be clicked again while already running.
        if (wheelSpinning == false) {
            // Based on the power level selected adjust the number of spins for the wheel, the more times is has
            // to rotate with the duration of the animation the quicker the wheel spins.
            if (wheelPower == 1) {
                theWheel.animation.spins = 3;
            } else if (wheelPower == 2) {
                theWheel.animation.spins = 8;
            } else if (wheelPower == 3) {
                theWheel.animation.spins = 15;
            }

            // Disable the spin button so can't click again while wheel is spinning.
            document.getElementById('spin_button').src = "spin_off.png";
            document.getElementById('spin_button').className = "";

            // Begin the spin animation by calling startAnimation on the wheel object.
            theWheel.startAnimation();

            // Set to true so that power can't be changed and spin button re-enabled during
            // the current animation. The user will have to reset before spinning again.
            wheelSpinning = true;
        }
    }

    // -------------------------------------------------------
    // Function for reset button.
    // -------------------------------------------------------
    function resetWheel() {
        theWheel.stopAnimation(false);  // Stop the animation, false as param so does not call callback function.
        theWheel.rotationAngle = 0;     // Re-set the wheel angle to 0 degrees.
        theWheel.draw();                // Call draw to render changes to the wheel.

        document.getElementById('pw1').className = "";  // Remove all colours from the power level indicators.
        document.getElementById('pw2').className = "";
        document.getElementById('pw3').className = "";

        wheelSpinning = false;          // Reset to false to power buttons and spin can be clicked again.
    }

    // -------------------------------------------------------
    // Called when the spin animation has finished by the callback feature of the wheel because I specified callback in the parameters
    // note the indicated segment is passed in as a parmeter as 99% of the time you will want to know this to inform the user of their prize.
    // -------------------------------------------------------
    function alertPrize(indicatedSegment) {
        // Do basic alert of the segment text. You would probably want to do something more interesting with this information.

        //            alert("You have won " + indicatedSegment.text);
        $.ajax({
            url: 'ajax.php',
            type: 'post',
            dataType: 'html',
            data: { point: indicatedSegment.text, action: 'SPINWHEELPOINT' },
            success: function (res) {
                if (parseInt(res) > 0) {
                    $('#spinpoint').html(res);
                    if (parseInt(res) <= 10) {
                        $('#popupimage').html('<img src="./images/pop/1-10.gif" width="200">');
                    } else if (parseInt(res) > 10 && parseInt(res) <= 20) {
                        $('#popupimage').html('<img src="./images/pop/10-20.gif" width="200">');
                    } else if (parseInt(res) > 20) {
                        $('#popupimage').html('<img src="./images/pop/21-50.gif" width="200">');
                    }
                    $('#spindWheel').modal('show');//now its working
                    //hide spin button 
                    $('#spin_button').hide();
                    //updated spin wheel points
                    var swp = $('.spin-point').html();
                    $('.spin-point').html(parseInt(res) + parseInt(swp));

                    //Update available points
                    var av = $('.available-points').html();
                    $('.available-points').html(parseInt(res) + parseInt(av));
                } else {
                    alert('Better luck next time.');
                }
            }
        });




    }

    //AJAX

    $('body').on('click', '#gift-reward', function () {
        var av_poiints = $('.available-points').html();
        if (av_poiints > 0) {
            $.ajax({
                url: 'ajax.php',
                type: 'post',
                dataType: 'html',
                data: { action: 'SEARCHUSERS', av_poiints: av_poiints },
                success: function (res) {
                    $('#getmatch').html(res);
                }
            });
        } else {
            $('#searchtext').hide();
            $('#searchuser').hide();
            $('#sharepoints').hide();
            $('#getmatch').html('You have no points');
        }

    });

    $('body').on('click', '#redeem-point', function () {
        var av_poiints = $('.available-points').html();
        if (av_poiints > 0) {
            $.ajax({
                url: 'ajax.php',
                type: 'post',
                dataType: 'html',
                data: { action: 'GETREDEEMPOINTS', av_poiints: av_poiints },
                success: function (res) {
                    $('#redeemable').html(res);
                }
            });
        } else {
            $('#redeemable').html('You have no points');
        }

    });


    $('body').on('click', '#searchuser', function () {
        var av_poiints = $('.available-points').html();
        var search = $('#searchtext').val();
        if (search != '') {
            $.ajax({
                url: 'ajax.php',
                type: 'post',
                dataType: 'html',
                data: { search: search, action: 'SEARCHUSERS', av_poiints: av_poiints },
                success: function (res) {
                    $('#getmatch').html(res);
                }
            });
        }
    });


    $('body').on('click', '#redeemnow', function (e) {
        e.preventDefault();
        var point = parseInt($('#idredeem-points').val());
        if (point > 0) {
            if (confirm("Are you sure you want to redeem the points. \n The action cannot be undone.")) {
                $.ajax({
                    url: 'ajax.php',
                    type: 'post',
                    data: { point: point, action: 'REDEEMNOW' },
                    success: function (response) {
                        if (response == 1) {
                            window.location.reload();
                        } else {
                            alert('The redeemable amount is invalid');
                        }
                    }
                });
            }
        } else {
            alert('The redeemable amount is invalid');
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

    //LOGIN CHART
    google.charts.setOnLoadCallback(drawLogin);

    function drawLogin() {
        var data = new google.visualization.DataTable();
        data.addColumn('number', 'X');
        data.addColumn('number', 'Login Points');
        data.addRows([
            <?php echo $logingraph; ?>
            //            [0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
        ]);
        var options = {
            hAxis: {
                title: 'Login',
                textStyle: {
                    color: '#004c8c', // Change the axis text color
                    fontName: 'Arial', // Change the font
                    fontSize: 14 // Change the font size
                },
                titleTextStyle: {
                    color: '#004c8c', // Change the axis title color
                    fontName: 'Arial', // Change the title font
                    fontSize: 16, // Change the title font size
                    bold: true // Make the title bold
                }
            },
            vAxis: {
                title: 'Points',
                textStyle: {
                    color: '#004c8c', // Change the axis text color
                    fontName: 'Arial', // Change the font
                    fontSize: 14 // Change the font size
                },
                titleTextStyle: {
                    color: '#004c8c', // Change the axis title color
                    fontName: 'Arial', // Change the title font
                    fontSize: 16, // Change the title font size
                    bold: true // Make the title bold
                }
            },
            backgroundColor: '#f9f9f9', // Change background color of the chart
            colors: ['#004c8c'], // Change line color to blue
            legend: {
                textStyle: {
                    color: '#004c8c' // Change text color of the legend
                }
            },
            titleTextStyle: {
                color: '#004c8c' // Change title text color if you have a title
            }
        };
        var chart = new google.visualization.LineChart(document.getElementById('chart_login'));
        chart.draw(data, options);
    }



    //QUIZ CHART
    google.charts.setOnLoadCallback(drawQuiz);

    function drawQuiz() {
        var data = new google.visualization.DataTable();
        data.addColumn('number', 'X');
        data.addColumn('number', 'Quiz Points');
        data.addRows([
            <?php echo $quizgraph; ?>
            // [0, 0], [1, 10], [2, 23], [3, 17], [4, 18], [5, 9],
        ]);

        var options = {
            backgroundColor: {
                fill: '#f9f9f9',
                stroke: '#cccccc',
            },
            hAxis: {
                title: 'Quiz',
                textStyle: {
                    color: '#004c8c', // Change the axis text color
                    fontName: 'Arial', // Change the font
                    fontSize: 14 // Change the font size
                },
                titleTextStyle: {
                    color: '#004c8c', // Change the axis title color
                    fontName: 'Arial', // Change the title font
                    fontSize: 16, // Change the title font size
                    bold: true // Make the title bold
                }
            },
            vAxis: {
                title: 'Points',
                textStyle: {
                    color: '#004c8c', // Change the axis text color
                    fontName: 'Arial', // Change the font
                    fontSize: 14 // Change the font size
                },
                titleTextStyle: {
                    color: '#004c8c', // Change the axis title color
                    fontName: 'Arial', // Change the title font
                    fontSize: 16, // Change the title font size
                    bold: true // Make the title bold
                }
            },
            // Optional: Change the chart title style
            titleTextStyle: {
                color: '#004c8c', // Change the title text color
                fontName: 'Arial', // Change the font
                fontSize: 18, // Change the font size
                bold: true // Make the title bold
            }
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_quiz'));
        chart.draw(data, options);
    }



    //MY POINT CHART
    google.charts.load('current', { packages: ['corechart', 'bar'] });
    google.charts.setOnLoadCallback(drawPoints);

    function drawPoints() {
        var data = google.visualization.arrayToDataTable([
            ['Points', 'My Points'],
            ['LOGIN', <?php echo $l; ?>],
            ['QUIZ', <?php echo $q; ?>],
            ['SPIN', <?php echo $s; ?>],
            ['REWARD', <?php echo $r; ?>],
        ]);

        var options = {
            title: '',
            chartArea: { width: '50%' },
            isStacked: true,
            hAxis: {
                title: 'Points',
                minValue: 0,
                titleTextStyle: {
                    color: '#004c8c',
                    fontSize: 14,
                    bold: true
                },
                textStyle: {
                    color: '#004c8c',
                    fontSize: 12
                },
                gridlines: {
                    color: '#d9e8f5'
                },
                baselineColor: '#004c8c',
                // ticks: [0, 10, 20, 30, 40, 50] // Example ticks, customize as needed
            },
            vAxis: {
                title: 'Category',
                titleTextStyle: {
                    color: '#004c8c',
                    fontSize: 14,
                    bold: true
                },
                textStyle: {
                    color: '#004c8c',
                    fontSize: 12
                },
                gridlines: {
                    color: '#d9e8f5'
                },
                baselineColor: '#004c8c'
            },
            colors: ['#1a43c3', '#00c851', '#ffbb33', '#ff4444'],
            backgroundColor: {
                fill: '#f9f9f9',
                stroke: '#cccccc',
                strokeWidth: 1
            },

            tooltip: {
                isHtml: true,
                textStyle: {
                    color: '#004c8c'
                }
            }
        };
        var chart = new google.visualization.BarChart(document.getElementById('chart_mypoints'));
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
