<?php
require_once('../../config.php');
require_once('lib.php');

global $USER, $DB, $CFG;
require_login();

if($USER->department != 'FACULTY')
{   
    echo $OUTPUT->header();
    echo "<h1>Invalid Access</h1>";
    echo $OUTPUT->footer();
    exit;
}
 
$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$params = array();
$pagetitle = 'Air Ticket Request';
$PAGE->set_context($context);
// $PAGE->set_url('/my/passport_withdrawal.php', $params);
$PAGE->set_url('/local/hrms/airticket-request.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);


echo $OUTPUT->header();

?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.error {
    color: red;
    font-size: 12px;
}

.loader {
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
 
</style>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" href="<?php echo $PAGE->url; ?>">Apply</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="airticket-request-list.php">List</a>
    </li>
     
</ul>
<form name="frmAirTicketRequest" id="frmAirTicketRequest" method="post">
    <input type="hidden" name="requesttype" value="add">  
    <div class="card">
        <div class="card-header text-bold text-center">ITINERARY - SELF</div>
        <div class="">
            <table class="table table-bordered table-striped">
                <thead class="" style="font-size: 11px;">
                    <tr> 
                        <th>NAME</th>
                        <th class="text-center">TRAVELLING / NOT TRAVELLING</th>	
                        <th>FROM/CITY</th>	
                        <th>TO/CITY</th>	
                        <th>DAY(FROM)/DAY(TO)</th>	
                        <th>DATE</th>	
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody style="font-size: 11px;">
                    <tr>
                        <td colspan="7">OUTBOUND</td>
                    </tr>
                    <tr> 
                        <td>
                            <input type="text" class="form-control" value="<?php echo $USER->firstname . " " . $USER->lastname; ?>" name="name_outbound" readonly>
                        </td>
                        <td>
                            <input type="checkbox" name="is_travelling_self_outbound" id="is_travelling_self_outbound" class="form-control is_travelling" style="height: 20px;">
                        </td>	
                        <td><input type="text" class="form-control" name="from_city_outbound" ></td>	
                        <td><input type="text" class="form-control" name="to_city_outbound" ></td>	
                        <td>
                            <select name="day_outbound" id="day_outbound" class="form-control" >
							    <option value=""></option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
					        </select>    

                        </td>	
                        <td><input type="date" class="form-control form-control-sm" name="date_outbound"></td>	
                        <td>
                            <select name="time_outbound" id="time_outbound" class="form-control">
								<option value="">0000</option>
								<option value="0100">0100</option>
								<option value="0105">0105</option>
								<option value="0110">0110</option>
								<option value="0115">0115</option>
								<option value="0120">0120</option>
								<option value="0125">0125</option>
								<option value="0130">0130</option>
								<option value="0135">0135</option>
								<option value="0140">0140</option>
								<option value="0145">0145</option>
								<option value="0150">0150</option>
								<option value="0155">0155</option>
								<option value="0200">0200</option>
								<option value="0205">0205</option>
								<option value="0210">0210</option>
								<option value="0215">0215</option>
								<option value="0220">0220</option>
								<option value="0225">0225</option>
								<option value="0230">0230</option>
								<option value="0235">0235</option>
								<option value="0240">0240</option>
								<option value="0245">0245</option>
								<option value="0250">0250</option>
								<option value="0255">0255</option>
								<option value="0300">0300</option>
								<option value="0305">0305</option>
								<option value="0310">0310</option>
								<option value="0315">0315</option>
								<option value="0320">0320</option>
								<option value="0325">0325</option>
								<option value="0330">0330</option>
								<option value="0335">0335</option>
								<option value="0340">0340</option>
								<option value="0345">0345</option>
								<option value="0350">0350</option>
								<option value="0355">0355</option>
								<option value="0400">0400</option>
								<option value="0405">0405</option>
								<option value="0410">0410</option>
								<option value="0415">0415</option>
								<option value="0420">0420</option>
								<option value="0425">0425</option>
								<option value="0430">0430</option>
								<option value="0435">0435</option>
								<option value="0440">0440</option>
								<option value="0445">0445</option>
								<option value="0450">0450</option>
								<option value="0455">0455</option>
								<option value="0500">0500</option>
								<option value="0505">0505</option>
								<option value="0510">0510</option>
								<option value="0515">0515</option>
								<option value="0520">0520</option>
								<option value="0525">0525</option>
								<option value="0530">0530</option>
								<option value="0535">0535</option>
								<option value="0540">0540</option>
								<option value="0545">0545</option>
								<option value="0550">0550</option>
								<option value="0555">0555</option>
								<option value="0600">0600</option>
								<option value="0605">0605</option>
								<option value="0610">0610</option>
								<option value="0615">0615</option>
								<option value="0620">0620</option>
								<option value="0625">0625</option>
								<option value="0630">0630</option>
								<option value="0635">0635</option>
								<option value="0640">0640</option>
								<option value="0645">0645</option>
								<option value="0650">0650</option>
								<option value="0655">0655</option>
								<option value="0700">0700</option>
								<option value="0705">0705</option>
								<option value="0710">0710</option>
								<option value="0715">0715</option>
								<option value="0720">0720</option>
								<option value="0725">0725</option>
								<option value="0730">0730</option>
								<option value="0735">0735</option>
								<option value="0740">0740</option>
								<option value="0745">0745</option>
								<option value="0750">0750</option>
								<option value="0755">0755</option>
								<option value="0800">0800</option>
								<option value="0805">0805</option>
								<option value="0810">0810</option>
								<option value="0815">0815</option>
								<option value="0820">0820</option>
								<option value="0825">0825</option>
								<option value="0830">0830</option>
								<option value="0835">0835</option>
								<option value="0840">0840</option>
								<option value="0845">0845</option>
								<option value="0850">0850</option>
								<option value="0855">0855</option>
								<option value="0900">0900</option>
								<option value="0905">0905</option>
								<option value="0910">0910</option>
								<option value="0915">0915</option>
								<option value="0920">0920</option>
								<option value="0925">0925</option>
								<option value="0930">0930</option>
								<option value="0935">0935</option>
								<option value="0940">0940</option>
								<option value="0945">0945</option>
								<option value="0950">0950</option>
								<option value="0955">0955</option>
								<option value="1000">1000</option>
								<option value="1005">1005</option>
								<option value="1010">1010</option>
								<option value="1015">1015</option>
								<option value="1020">1020</option>
								<option value="1025">1025</option>
								<option value="1030">1030</option>
								<option value="1035">1035</option>
								<option value="1040">1040</option>
								<option value="1045">1045</option>
								<option value="1050">1050</option>
								<option value="1055">1055</option>
								<option value="1100">1100</option>
								<option value="1105">1105</option>
								<option value="1110">1110</option>
								<option value="1115">1115</option>
								<option value="1120">1120</option>
								<option value="1125">1125</option>
								<option value="1130">1130</option>
								<option value="1135">1135</option>
								<option value="1140">1140</option>
								<option value="1145">1145</option>
								<option value="1150">1150</option>
								<option value="1155">1155</option>
								<option value="1200">1200</option>
								<option value="1205">1205</option>
								<option value="1210">1210</option>
								<option value="1215">1215</option>
								<option value="1220">1220</option>
								<option value="1225">1225</option>
								<option value="1230">1230</option>
								<option value="1235">1235</option>
								<option value="1240">1240</option>
								<option value="1245">1245</option>
								<option value="1250">1250</option>
								<option value="1255">1255</option>
								<option value="1300">1300</option>
								<option value="1305">1305</option>
								<option value="1310">1310</option>
								<option value="1315">1315</option>
								<option value="1320">1320</option>
								<option value="1325">1325</option>
								<option value="1330">1330</option>
								<option value="1335">1335</option>
								<option value="1340">1340</option>
								<option value="1345">1345</option>
								<option value="1350">1350</option>
								<option value="1355">1355</option>
								<option value="1400">1400</option>
								<option value="1405">1405</option>
								<option value="1410">1410</option>
								<option value="1415">1415</option>
								<option value="1420">1420</option>
								<option value="1425">1425</option>
								<option value="1430">1430</option>
								<option value="1435">1435</option>
								<option value="1440">1440</option>
								<option value="1445">1445</option>
								<option value="1450">1450</option>
								<option value="1455">1455</option>
								<option value="1500">1500</option>
								<option value="1505">1505</option>
								<option value="1510">1510</option>
								<option value="1515">1515</option>
								<option value="1520">1520</option>
								<option value="1525">1525</option>
								<option value="1530">1530</option>
								<option value="1535">1535</option>
								<option value="1540">1540</option>
								<option value="1545">1545</option>
								<option value="1550">1550</option>
								<option value="1555">1555</option>
								<option value="1600">1600</option>
								<option value="1605">1605</option>
								<option value="1610">1610</option>
								<option value="1615">1615</option>
								<option value="1620">1620</option>
								<option value="1625">1625</option>
								<option value="1630">1630</option>
								<option value="1635">1635</option>
								<option value="1640">1640</option>
								<option value="1645">1645</option>
								<option value="1650">1650</option>
								<option value="1655">1655</option>
								<option value="1700">1700</option>
								<option value="1705">1705</option>
								<option value="1710">1710</option>
								<option value="1715">1715</option>
								<option value="1720">1720</option>
								<option value="1725">1725</option>
								<option value="1730">1730</option>
								<option value="1735">1735</option>
								<option value="1740">1740</option>
								<option value="1745">1745</option>
								<option value="1750">1750</option>
								<option value="1755">1755</option>
								<option value="1800">1800</option>
								<option value="1805">1805</option>
								<option value="1810">1810</option>
								<option value="1815">1815</option>
								<option value="1820">1820</option>
								<option value="1825">1825</option>
								<option value="1830">1830</option>
								<option value="1835">1835</option>
								<option value="1840">1840</option>
								<option value="1845">1845</option>
								<option value="1850">1850</option>
								<option value="1855">1855</option>
								<option value="1900">1900</option>
								<option value="1905">1905</option>
								<option value="1910">1910</option>
								<option value="1915">1915</option>
								<option value="1920">1920</option>
								<option value="1925">1925</option>
								<option value="1930">1930</option>
								<option value="1935">1935</option>
								<option value="1940">1940</option>
								<option value="1945">1945</option>
								<option value="1950">1950</option>
								<option value="1955">1955</option>
								<option value="2000">2000</option>
								<option value="2005">2005</option>
								<option value="2010">2010</option>
								<option value="2015">2015</option>
								<option value="2020">2020</option>
								<option value="2025">2025</option>
								<option value="2030">2030</option>
								<option value="2035">2035</option>
								<option value="2040">2040</option>
								<option value="2045">2045</option>
								<option value="2050">2050</option>
								<option value="2055">2055</option>
								<option value="2100">2100</option>
								<option value="2105">2105</option>
								<option value="2110">2110</option>
								<option value="2115">2115</option>
								<option value="2120">2120</option>
								<option value="2125">2125</option>
								<option value="2130">2130</option>
								<option value="2135">2135</option>
								<option value="2140">2140</option>
								<option value="2145">2145</option>
								<option value="2150">2150</option>
								<option value="2155">2155</option>
								<option value="2200">2200</option>
								<option value="2205">2205</option>
								<option value="2210">2210</option>
								<option value="2215">2215</option>
								<option value="2220">2220</option>
								<option value="2225">2225</option>
								<option value="2230">2230</option>
								<option value="2235">2235</option>
								<option value="2240">2240</option>
								<option value="2245">2245</option>
								<option value="2250">2250</option>
								<option value="2255">2255</option>
								<option value="2300">2300</option>
								<option value="2305">2305</option>
								<option value="2310">2310</option>
								<option value="2315">2315</option>
								<option value="2320">2320</option>
								<option value="2325">2325</option>
								<option value="2330">2330</option>
								<option value="2335">2335</option>
								<option value="2340">2340</option>
								<option value="2345">2345</option>
								<option value="2350">2350</option>
								<option value="2355">2355</option>
							</select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7">INBOUND</td>
                    </tr>
                    <tr> 
                        <td>
                            <input type="text" class="form-control" value="<?php echo $USER->firstname . " " . $USER->lastname; ?>" name="name_inbound" readonly>
                        </td>
                        <td>
                            <input type="checkbox"  name="is_travelling_self_inbound" id="is_travelling_self_inbound" class="form-control is_travelling" style="height: 20px;">
                        </td>	
                        <td><input type="text" class="form-control" name="from_city_inbound" ></td>	
                        <td><input type="text" class="form-control" name="to_city_inbound" ></td>	
                        <td>
                            <select name="day_inbound" id="day_inbound" class="form-control" >
							    <option value=""></option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
					        </select>    

                        </td>	
                        <td><input type="date" class="form-control form-control-sm" name="date_inbound"></td>	
                        <td>
                            <select name="time_inbound" id="time_inbound" class="form-control">
								<option value="">0000</option>
								<option value="0100">0100</option>
								<option value="0105">0105</option>
								<option value="0110">0110</option>
								<option value="0115">0115</option>
								<option value="0120">0120</option>
								<option value="0125">0125</option>
								<option value="0130">0130</option>
								<option value="0135">0135</option>
								<option value="0140">0140</option>
								<option value="0145">0145</option>
								<option value="0150">0150</option>
								<option value="0155">0155</option>
								<option value="0200">0200</option>
								<option value="0205">0205</option>
								<option value="0210">0210</option>
								<option value="0215">0215</option>
								<option value="0220">0220</option>
								<option value="0225">0225</option>
								<option value="0230">0230</option>
								<option value="0235">0235</option>
								<option value="0240">0240</option>
								<option value="0245">0245</option>
								<option value="0250">0250</option>
								<option value="0255">0255</option>
								<option value="0300">0300</option>
								<option value="0305">0305</option>
								<option value="0310">0310</option>
								<option value="0315">0315</option>
								<option value="0320">0320</option>
								<option value="0325">0325</option>
								<option value="0330">0330</option>
								<option value="0335">0335</option>
								<option value="0340">0340</option>
								<option value="0345">0345</option>
								<option value="0350">0350</option>
								<option value="0355">0355</option>
								<option value="0400">0400</option>
								<option value="0405">0405</option>
								<option value="0410">0410</option>
								<option value="0415">0415</option>
								<option value="0420">0420</option>
								<option value="0425">0425</option>
								<option value="0430">0430</option>
								<option value="0435">0435</option>
								<option value="0440">0440</option>
								<option value="0445">0445</option>
								<option value="0450">0450</option>
								<option value="0455">0455</option>
								<option value="0500">0500</option>
								<option value="0505">0505</option>
								<option value="0510">0510</option>
								<option value="0515">0515</option>
								<option value="0520">0520</option>
								<option value="0525">0525</option>
								<option value="0530">0530</option>
								<option value="0535">0535</option>
								<option value="0540">0540</option>
								<option value="0545">0545</option>
								<option value="0550">0550</option>
								<option value="0555">0555</option>
								<option value="0600">0600</option>
								<option value="0605">0605</option>
								<option value="0610">0610</option>
								<option value="0615">0615</option>
								<option value="0620">0620</option>
								<option value="0625">0625</option>
								<option value="0630">0630</option>
								<option value="0635">0635</option>
								<option value="0640">0640</option>
								<option value="0645">0645</option>
								<option value="0650">0650</option>
								<option value="0655">0655</option>
								<option value="0700">0700</option>
								<option value="0705">0705</option>
								<option value="0710">0710</option>
								<option value="0715">0715</option>
								<option value="0720">0720</option>
								<option value="0725">0725</option>
								<option value="0730">0730</option>
								<option value="0735">0735</option>
								<option value="0740">0740</option>
								<option value="0745">0745</option>
								<option value="0750">0750</option>
								<option value="0755">0755</option>
								<option value="0800">0800</option>
								<option value="0805">0805</option>
								<option value="0810">0810</option>
								<option value="0815">0815</option>
								<option value="0820">0820</option>
								<option value="0825">0825</option>
								<option value="0830">0830</option>
								<option value="0835">0835</option>
								<option value="0840">0840</option>
								<option value="0845">0845</option>
								<option value="0850">0850</option>
								<option value="0855">0855</option>
								<option value="0900">0900</option>
								<option value="0905">0905</option>
								<option value="0910">0910</option>
								<option value="0915">0915</option>
								<option value="0920">0920</option>
								<option value="0925">0925</option>
								<option value="0930">0930</option>
								<option value="0935">0935</option>
								<option value="0940">0940</option>
								<option value="0945">0945</option>
								<option value="0950">0950</option>
								<option value="0955">0955</option>
								<option value="1000">1000</option>
								<option value="1005">1005</option>
								<option value="1010">1010</option>
								<option value="1015">1015</option>
								<option value="1020">1020</option>
								<option value="1025">1025</option>
								<option value="1030">1030</option>
								<option value="1035">1035</option>
								<option value="1040">1040</option>
								<option value="1045">1045</option>
								<option value="1050">1050</option>
								<option value="1055">1055</option>
								<option value="1100">1100</option>
								<option value="1105">1105</option>
								<option value="1110">1110</option>
								<option value="1115">1115</option>
								<option value="1120">1120</option>
								<option value="1125">1125</option>
								<option value="1130">1130</option>
								<option value="1135">1135</option>
								<option value="1140">1140</option>
								<option value="1145">1145</option>
								<option value="1150">1150</option>
								<option value="1155">1155</option>
								<option value="1200">1200</option>
								<option value="1205">1205</option>
								<option value="1210">1210</option>
								<option value="1215">1215</option>
								<option value="1220">1220</option>
								<option value="1225">1225</option>
								<option value="1230">1230</option>
								<option value="1235">1235</option>
								<option value="1240">1240</option>
								<option value="1245">1245</option>
								<option value="1250">1250</option>
								<option value="1255">1255</option>
								<option value="1300">1300</option>
								<option value="1305">1305</option>
								<option value="1310">1310</option>
								<option value="1315">1315</option>
								<option value="1320">1320</option>
								<option value="1325">1325</option>
								<option value="1330">1330</option>
								<option value="1335">1335</option>
								<option value="1340">1340</option>
								<option value="1345">1345</option>
								<option value="1350">1350</option>
								<option value="1355">1355</option>
								<option value="1400">1400</option>
								<option value="1405">1405</option>
								<option value="1410">1410</option>
								<option value="1415">1415</option>
								<option value="1420">1420</option>
								<option value="1425">1425</option>
								<option value="1430">1430</option>
								<option value="1435">1435</option>
								<option value="1440">1440</option>
								<option value="1445">1445</option>
								<option value="1450">1450</option>
								<option value="1455">1455</option>
								<option value="1500">1500</option>
								<option value="1505">1505</option>
								<option value="1510">1510</option>
								<option value="1515">1515</option>
								<option value="1520">1520</option>
								<option value="1525">1525</option>
								<option value="1530">1530</option>
								<option value="1535">1535</option>
								<option value="1540">1540</option>
								<option value="1545">1545</option>
								<option value="1550">1550</option>
								<option value="1555">1555</option>
								<option value="1600">1600</option>
								<option value="1605">1605</option>
								<option value="1610">1610</option>
								<option value="1615">1615</option>
								<option value="1620">1620</option>
								<option value="1625">1625</option>
								<option value="1630">1630</option>
								<option value="1635">1635</option>
								<option value="1640">1640</option>
								<option value="1645">1645</option>
								<option value="1650">1650</option>
								<option value="1655">1655</option>
								<option value="1700">1700</option>
								<option value="1705">1705</option>
								<option value="1710">1710</option>
								<option value="1715">1715</option>
								<option value="1720">1720</option>
								<option value="1725">1725</option>
								<option value="1730">1730</option>
								<option value="1735">1735</option>
								<option value="1740">1740</option>
								<option value="1745">1745</option>
								<option value="1750">1750</option>
								<option value="1755">1755</option>
								<option value="1800">1800</option>
								<option value="1805">1805</option>
								<option value="1810">1810</option>
								<option value="1815">1815</option>
								<option value="1820">1820</option>
								<option value="1825">1825</option>
								<option value="1830">1830</option>
								<option value="1835">1835</option>
								<option value="1840">1840</option>
								<option value="1845">1845</option>
								<option value="1850">1850</option>
								<option value="1855">1855</option>
								<option value="1900">1900</option>
								<option value="1905">1905</option>
								<option value="1910">1910</option>
								<option value="1915">1915</option>
								<option value="1920">1920</option>
								<option value="1925">1925</option>
								<option value="1930">1930</option>
								<option value="1935">1935</option>
								<option value="1940">1940</option>
								<option value="1945">1945</option>
								<option value="1950">1950</option>
								<option value="1955">1955</option>
								<option value="2000">2000</option>
								<option value="2005">2005</option>
								<option value="2010">2010</option>
								<option value="2015">2015</option>
								<option value="2020">2020</option>
								<option value="2025">2025</option>
								<option value="2030">2030</option>
								<option value="2035">2035</option>
								<option value="2040">2040</option>
								<option value="2045">2045</option>
								<option value="2050">2050</option>
								<option value="2055">2055</option>
								<option value="2100">2100</option>
								<option value="2105">2105</option>
								<option value="2110">2110</option>
								<option value="2115">2115</option>
								<option value="2120">2120</option>
								<option value="2125">2125</option>
								<option value="2130">2130</option>
								<option value="2135">2135</option>
								<option value="2140">2140</option>
								<option value="2145">2145</option>
								<option value="2150">2150</option>
								<option value="2155">2155</option>
								<option value="2200">2200</option>
								<option value="2205">2205</option>
								<option value="2210">2210</option>
								<option value="2215">2215</option>
								<option value="2220">2220</option>
								<option value="2225">2225</option>
								<option value="2230">2230</option>
								<option value="2235">2235</option>
								<option value="2240">2240</option>
								<option value="2245">2245</option>
								<option value="2250">2250</option>
								<option value="2255">2255</option>
								<option value="2300">2300</option>
								<option value="2305">2305</option>
								<option value="2310">2310</option>
								<option value="2315">2315</option>
								<option value="2320">2320</option>
								<option value="2325">2325</option>
								<option value="2330">2330</option>
								<option value="2335">2335</option>
								<option value="2340">2340</option>
								<option value="2345">2345</option>
								<option value="2350">2350</option>
								<option value="2355">2355</option>
							</select>
                        </td>
                    </tr>
                </tbody>
                
            </table>
        </div>
        
    </div>
	<div class="card">
		<div class="card-header text-bold text-center">FAMILY OUTBOUND ITINERARY [PASSENGERS(Family/Dependant Detail form, if applicable)]</div>
		<div class="">
			<table class="table table-bordered table-striped">
                <thead class="" style="font-size: 11px;">
                    <tr> 
                        <th>NAME</th>
                        <th class="text-center">TRAVELLING / NOT TRAVELLING</th>	
                        <th>FROM/CITY</th>	
                        <th>TO/CITY</th>	
                        <th>DAY(FROM)/DAY(TO)</th>	
                        <th>DATE</th>	
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody style="font-size: 11px;" id="family_outbound_tbody"> 
                    <tr id="family_outbound_tr"> 
                        <td>
                            <input type="text" class="form-control" value="" name="name_family_outbound[]">
                        </td>
                        <td>
                            <input type="checkbox" name="is_travelling_family_outbound[]" id="" class="form-control is_travelling" style="height: 20px;">
                        </td>	
                        <td><input type="text" class="form-control" name="from_city_family_outbound[]" ></td>	
                        <td><input type="text" class="form-control" name="to_city_family_outbound[]" ></td>	
                        <td>
                            <select name="day_family_outbound[]" class="form-control" >
							    <option value=""></option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
					        </select>    

                        </td>	
                        <td><input type="date" class="form-control form-control-sm" name="date_family_outbound[]"></td>	
                        <td>
                            <select name="time_family_outbound[]" class="form-control">
								<option value="">0000</option>
								<option value="0100">0100</option>
								<option value="0105">0105</option>
								<option value="0110">0110</option>
								<option value="0115">0115</option>
								<option value="0120">0120</option>
								<option value="0125">0125</option>
								<option value="0130">0130</option>
								<option value="0135">0135</option>
								<option value="0140">0140</option>
								<option value="0145">0145</option>
								<option value="0150">0150</option>
								<option value="0155">0155</option>
								<option value="0200">0200</option>
								<option value="0205">0205</option>
								<option value="0210">0210</option>
								<option value="0215">0215</option>
								<option value="0220">0220</option>
								<option value="0225">0225</option>
								<option value="0230">0230</option>
								<option value="0235">0235</option>
								<option value="0240">0240</option>
								<option value="0245">0245</option>
								<option value="0250">0250</option>
								<option value="0255">0255</option>
								<option value="0300">0300</option>
								<option value="0305">0305</option>
								<option value="0310">0310</option>
								<option value="0315">0315</option>
								<option value="0320">0320</option>
								<option value="0325">0325</option>
								<option value="0330">0330</option>
								<option value="0335">0335</option>
								<option value="0340">0340</option>
								<option value="0345">0345</option>
								<option value="0350">0350</option>
								<option value="0355">0355</option>
								<option value="0400">0400</option>
								<option value="0405">0405</option>
								<option value="0410">0410</option>
								<option value="0415">0415</option>
								<option value="0420">0420</option>
								<option value="0425">0425</option>
								<option value="0430">0430</option>
								<option value="0435">0435</option>
								<option value="0440">0440</option>
								<option value="0445">0445</option>
								<option value="0450">0450</option>
								<option value="0455">0455</option>
								<option value="0500">0500</option>
								<option value="0505">0505</option>
								<option value="0510">0510</option>
								<option value="0515">0515</option>
								<option value="0520">0520</option>
								<option value="0525">0525</option>
								<option value="0530">0530</option>
								<option value="0535">0535</option>
								<option value="0540">0540</option>
								<option value="0545">0545</option>
								<option value="0550">0550</option>
								<option value="0555">0555</option>
								<option value="0600">0600</option>
								<option value="0605">0605</option>
								<option value="0610">0610</option>
								<option value="0615">0615</option>
								<option value="0620">0620</option>
								<option value="0625">0625</option>
								<option value="0630">0630</option>
								<option value="0635">0635</option>
								<option value="0640">0640</option>
								<option value="0645">0645</option>
								<option value="0650">0650</option>
								<option value="0655">0655</option>
								<option value="0700">0700</option>
								<option value="0705">0705</option>
								<option value="0710">0710</option>
								<option value="0715">0715</option>
								<option value="0720">0720</option>
								<option value="0725">0725</option>
								<option value="0730">0730</option>
								<option value="0735">0735</option>
								<option value="0740">0740</option>
								<option value="0745">0745</option>
								<option value="0750">0750</option>
								<option value="0755">0755</option>
								<option value="0800">0800</option>
								<option value="0805">0805</option>
								<option value="0810">0810</option>
								<option value="0815">0815</option>
								<option value="0820">0820</option>
								<option value="0825">0825</option>
								<option value="0830">0830</option>
								<option value="0835">0835</option>
								<option value="0840">0840</option>
								<option value="0845">0845</option>
								<option value="0850">0850</option>
								<option value="0855">0855</option>
								<option value="0900">0900</option>
								<option value="0905">0905</option>
								<option value="0910">0910</option>
								<option value="0915">0915</option>
								<option value="0920">0920</option>
								<option value="0925">0925</option>
								<option value="0930">0930</option>
								<option value="0935">0935</option>
								<option value="0940">0940</option>
								<option value="0945">0945</option>
								<option value="0950">0950</option>
								<option value="0955">0955</option>
								<option value="1000">1000</option>
								<option value="1005">1005</option>
								<option value="1010">1010</option>
								<option value="1015">1015</option>
								<option value="1020">1020</option>
								<option value="1025">1025</option>
								<option value="1030">1030</option>
								<option value="1035">1035</option>
								<option value="1040">1040</option>
								<option value="1045">1045</option>
								<option value="1050">1050</option>
								<option value="1055">1055</option>
								<option value="1100">1100</option>
								<option value="1105">1105</option>
								<option value="1110">1110</option>
								<option value="1115">1115</option>
								<option value="1120">1120</option>
								<option value="1125">1125</option>
								<option value="1130">1130</option>
								<option value="1135">1135</option>
								<option value="1140">1140</option>
								<option value="1145">1145</option>
								<option value="1150">1150</option>
								<option value="1155">1155</option>
								<option value="1200">1200</option>
								<option value="1205">1205</option>
								<option value="1210">1210</option>
								<option value="1215">1215</option>
								<option value="1220">1220</option>
								<option value="1225">1225</option>
								<option value="1230">1230</option>
								<option value="1235">1235</option>
								<option value="1240">1240</option>
								<option value="1245">1245</option>
								<option value="1250">1250</option>
								<option value="1255">1255</option>
								<option value="1300">1300</option>
								<option value="1305">1305</option>
								<option value="1310">1310</option>
								<option value="1315">1315</option>
								<option value="1320">1320</option>
								<option value="1325">1325</option>
								<option value="1330">1330</option>
								<option value="1335">1335</option>
								<option value="1340">1340</option>
								<option value="1345">1345</option>
								<option value="1350">1350</option>
								<option value="1355">1355</option>
								<option value="1400">1400</option>
								<option value="1405">1405</option>
								<option value="1410">1410</option>
								<option value="1415">1415</option>
								<option value="1420">1420</option>
								<option value="1425">1425</option>
								<option value="1430">1430</option>
								<option value="1435">1435</option>
								<option value="1440">1440</option>
								<option value="1445">1445</option>
								<option value="1450">1450</option>
								<option value="1455">1455</option>
								<option value="1500">1500</option>
								<option value="1505">1505</option>
								<option value="1510">1510</option>
								<option value="1515">1515</option>
								<option value="1520">1520</option>
								<option value="1525">1525</option>
								<option value="1530">1530</option>
								<option value="1535">1535</option>
								<option value="1540">1540</option>
								<option value="1545">1545</option>
								<option value="1550">1550</option>
								<option value="1555">1555</option>
								<option value="1600">1600</option>
								<option value="1605">1605</option>
								<option value="1610">1610</option>
								<option value="1615">1615</option>
								<option value="1620">1620</option>
								<option value="1625">1625</option>
								<option value="1630">1630</option>
								<option value="1635">1635</option>
								<option value="1640">1640</option>
								<option value="1645">1645</option>
								<option value="1650">1650</option>
								<option value="1655">1655</option>
								<option value="1700">1700</option>
								<option value="1705">1705</option>
								<option value="1710">1710</option>
								<option value="1715">1715</option>
								<option value="1720">1720</option>
								<option value="1725">1725</option>
								<option value="1730">1730</option>
								<option value="1735">1735</option>
								<option value="1740">1740</option>
								<option value="1745">1745</option>
								<option value="1750">1750</option>
								<option value="1755">1755</option>
								<option value="1800">1800</option>
								<option value="1805">1805</option>
								<option value="1810">1810</option>
								<option value="1815">1815</option>
								<option value="1820">1820</option>
								<option value="1825">1825</option>
								<option value="1830">1830</option>
								<option value="1835">1835</option>
								<option value="1840">1840</option>
								<option value="1845">1845</option>
								<option value="1850">1850</option>
								<option value="1855">1855</option>
								<option value="1900">1900</option>
								<option value="1905">1905</option>
								<option value="1910">1910</option>
								<option value="1915">1915</option>
								<option value="1920">1920</option>
								<option value="1925">1925</option>
								<option value="1930">1930</option>
								<option value="1935">1935</option>
								<option value="1940">1940</option>
								<option value="1945">1945</option>
								<option value="1950">1950</option>
								<option value="1955">1955</option>
								<option value="2000">2000</option>
								<option value="2005">2005</option>
								<option value="2010">2010</option>
								<option value="2015">2015</option>
								<option value="2020">2020</option>
								<option value="2025">2025</option>
								<option value="2030">2030</option>
								<option value="2035">2035</option>
								<option value="2040">2040</option>
								<option value="2045">2045</option>
								<option value="2050">2050</option>
								<option value="2055">2055</option>
								<option value="2100">2100</option>
								<option value="2105">2105</option>
								<option value="2110">2110</option>
								<option value="2115">2115</option>
								<option value="2120">2120</option>
								<option value="2125">2125</option>
								<option value="2130">2130</option>
								<option value="2135">2135</option>
								<option value="2140">2140</option>
								<option value="2145">2145</option>
								<option value="2150">2150</option>
								<option value="2155">2155</option>
								<option value="2200">2200</option>
								<option value="2205">2205</option>
								<option value="2210">2210</option>
								<option value="2215">2215</option>
								<option value="2220">2220</option>
								<option value="2225">2225</option>
								<option value="2230">2230</option>
								<option value="2235">2235</option>
								<option value="2240">2240</option>
								<option value="2245">2245</option>
								<option value="2250">2250</option>
								<option value="2255">2255</option>
								<option value="2300">2300</option>
								<option value="2305">2305</option>
								<option value="2310">2310</option>
								<option value="2315">2315</option>
								<option value="2320">2320</option>
								<option value="2325">2325</option>
								<option value="2330">2330</option>
								<option value="2335">2335</option>
								<option value="2340">2340</option>
								<option value="2345">2345</option>
								<option value="2350">2350</option>
								<option value="2355">2355</option>
							</select>
                        </td>
                    </tr>
                    
                </tbody>
				<tfoot>
					<tr>
						<td colspan="7">
							<a href="#" class="btn btn-primary" id="family_outbound_btn">Add More</a>
						</td>
					</tr>
				</tfoot>
            </table>
		</div>
	</div>

	<div class="card">
		<div class="card-header text-bold text-center">FAMILY INBOUND ITINERARY</div>
		<div class="">
			<table class="table table-bordered table-striped">
                <thead class="" style="font-size: 11px;">
                    <tr> 
                        <th>NAME</th>
                        <th class="text-center">TRAVELLING / NOT TRAVELLING</th>	
                        <th>FROM/CITY</th>	
                        <th>TO/CITY</th>	
                        <th>DAY(FROM)/DAY(TO)</th>	
                        <th>DATE</th>	
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody style="font-size: 11px;" id="family_inbound_tbody"> 
                    <tr id="family_inbound_tr"> 
                        <td>
                            <input type="text" class="form-control" value="" name="name_family_inbound[]" class="">
                        </td>
                        <td>
                            <input type="checkbox" name="is_travelling_family_inbound[]" id="" class="form-control is_travelling" style="height: 20px;">
                        </td>	
                        <td><input type="text" class="form-control" name="from_city_family_inbound[]" ></td>	
                        <td><input type="text" class="form-control" name="to_city_family_inbound[]" ></td>	
                        <td>
                            <select name="day_family_inbound[]" class="form-control" >
							    <option value=""></option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
					        </select>    

                        </td>	
                        <td><input type="date" class="form-control form-control-sm" name="date_family_inbound[]"></td>	
                        <td>
                            <select name="time_family_inbound[]" class="form-control">
								<option value="">0000</option>
								<option value="0100">0100</option>
								<option value="0105">0105</option>
								<option value="0110">0110</option>
								<option value="0115">0115</option>
								<option value="0120">0120</option>
								<option value="0125">0125</option>
								<option value="0130">0130</option>
								<option value="0135">0135</option>
								<option value="0140">0140</option>
								<option value="0145">0145</option>
								<option value="0150">0150</option>
								<option value="0155">0155</option>
								<option value="0200">0200</option>
								<option value="0205">0205</option>
								<option value="0210">0210</option>
								<option value="0215">0215</option>
								<option value="0220">0220</option>
								<option value="0225">0225</option>
								<option value="0230">0230</option>
								<option value="0235">0235</option>
								<option value="0240">0240</option>
								<option value="0245">0245</option>
								<option value="0250">0250</option>
								<option value="0255">0255</option>
								<option value="0300">0300</option>
								<option value="0305">0305</option>
								<option value="0310">0310</option>
								<option value="0315">0315</option>
								<option value="0320">0320</option>
								<option value="0325">0325</option>
								<option value="0330">0330</option>
								<option value="0335">0335</option>
								<option value="0340">0340</option>
								<option value="0345">0345</option>
								<option value="0350">0350</option>
								<option value="0355">0355</option>
								<option value="0400">0400</option>
								<option value="0405">0405</option>
								<option value="0410">0410</option>
								<option value="0415">0415</option>
								<option value="0420">0420</option>
								<option value="0425">0425</option>
								<option value="0430">0430</option>
								<option value="0435">0435</option>
								<option value="0440">0440</option>
								<option value="0445">0445</option>
								<option value="0450">0450</option>
								<option value="0455">0455</option>
								<option value="0500">0500</option>
								<option value="0505">0505</option>
								<option value="0510">0510</option>
								<option value="0515">0515</option>
								<option value="0520">0520</option>
								<option value="0525">0525</option>
								<option value="0530">0530</option>
								<option value="0535">0535</option>
								<option value="0540">0540</option>
								<option value="0545">0545</option>
								<option value="0550">0550</option>
								<option value="0555">0555</option>
								<option value="0600">0600</option>
								<option value="0605">0605</option>
								<option value="0610">0610</option>
								<option value="0615">0615</option>
								<option value="0620">0620</option>
								<option value="0625">0625</option>
								<option value="0630">0630</option>
								<option value="0635">0635</option>
								<option value="0640">0640</option>
								<option value="0645">0645</option>
								<option value="0650">0650</option>
								<option value="0655">0655</option>
								<option value="0700">0700</option>
								<option value="0705">0705</option>
								<option value="0710">0710</option>
								<option value="0715">0715</option>
								<option value="0720">0720</option>
								<option value="0725">0725</option>
								<option value="0730">0730</option>
								<option value="0735">0735</option>
								<option value="0740">0740</option>
								<option value="0745">0745</option>
								<option value="0750">0750</option>
								<option value="0755">0755</option>
								<option value="0800">0800</option>
								<option value="0805">0805</option>
								<option value="0810">0810</option>
								<option value="0815">0815</option>
								<option value="0820">0820</option>
								<option value="0825">0825</option>
								<option value="0830">0830</option>
								<option value="0835">0835</option>
								<option value="0840">0840</option>
								<option value="0845">0845</option>
								<option value="0850">0850</option>
								<option value="0855">0855</option>
								<option value="0900">0900</option>
								<option value="0905">0905</option>
								<option value="0910">0910</option>
								<option value="0915">0915</option>
								<option value="0920">0920</option>
								<option value="0925">0925</option>
								<option value="0930">0930</option>
								<option value="0935">0935</option>
								<option value="0940">0940</option>
								<option value="0945">0945</option>
								<option value="0950">0950</option>
								<option value="0955">0955</option>
								<option value="1000">1000</option>
								<option value="1005">1005</option>
								<option value="1010">1010</option>
								<option value="1015">1015</option>
								<option value="1020">1020</option>
								<option value="1025">1025</option>
								<option value="1030">1030</option>
								<option value="1035">1035</option>
								<option value="1040">1040</option>
								<option value="1045">1045</option>
								<option value="1050">1050</option>
								<option value="1055">1055</option>
								<option value="1100">1100</option>
								<option value="1105">1105</option>
								<option value="1110">1110</option>
								<option value="1115">1115</option>
								<option value="1120">1120</option>
								<option value="1125">1125</option>
								<option value="1130">1130</option>
								<option value="1135">1135</option>
								<option value="1140">1140</option>
								<option value="1145">1145</option>
								<option value="1150">1150</option>
								<option value="1155">1155</option>
								<option value="1200">1200</option>
								<option value="1205">1205</option>
								<option value="1210">1210</option>
								<option value="1215">1215</option>
								<option value="1220">1220</option>
								<option value="1225">1225</option>
								<option value="1230">1230</option>
								<option value="1235">1235</option>
								<option value="1240">1240</option>
								<option value="1245">1245</option>
								<option value="1250">1250</option>
								<option value="1255">1255</option>
								<option value="1300">1300</option>
								<option value="1305">1305</option>
								<option value="1310">1310</option>
								<option value="1315">1315</option>
								<option value="1320">1320</option>
								<option value="1325">1325</option>
								<option value="1330">1330</option>
								<option value="1335">1335</option>
								<option value="1340">1340</option>
								<option value="1345">1345</option>
								<option value="1350">1350</option>
								<option value="1355">1355</option>
								<option value="1400">1400</option>
								<option value="1405">1405</option>
								<option value="1410">1410</option>
								<option value="1415">1415</option>
								<option value="1420">1420</option>
								<option value="1425">1425</option>
								<option value="1430">1430</option>
								<option value="1435">1435</option>
								<option value="1440">1440</option>
								<option value="1445">1445</option>
								<option value="1450">1450</option>
								<option value="1455">1455</option>
								<option value="1500">1500</option>
								<option value="1505">1505</option>
								<option value="1510">1510</option>
								<option value="1515">1515</option>
								<option value="1520">1520</option>
								<option value="1525">1525</option>
								<option value="1530">1530</option>
								<option value="1535">1535</option>
								<option value="1540">1540</option>
								<option value="1545">1545</option>
								<option value="1550">1550</option>
								<option value="1555">1555</option>
								<option value="1600">1600</option>
								<option value="1605">1605</option>
								<option value="1610">1610</option>
								<option value="1615">1615</option>
								<option value="1620">1620</option>
								<option value="1625">1625</option>
								<option value="1630">1630</option>
								<option value="1635">1635</option>
								<option value="1640">1640</option>
								<option value="1645">1645</option>
								<option value="1650">1650</option>
								<option value="1655">1655</option>
								<option value="1700">1700</option>
								<option value="1705">1705</option>
								<option value="1710">1710</option>
								<option value="1715">1715</option>
								<option value="1720">1720</option>
								<option value="1725">1725</option>
								<option value="1730">1730</option>
								<option value="1735">1735</option>
								<option value="1740">1740</option>
								<option value="1745">1745</option>
								<option value="1750">1750</option>
								<option value="1755">1755</option>
								<option value="1800">1800</option>
								<option value="1805">1805</option>
								<option value="1810">1810</option>
								<option value="1815">1815</option>
								<option value="1820">1820</option>
								<option value="1825">1825</option>
								<option value="1830">1830</option>
								<option value="1835">1835</option>
								<option value="1840">1840</option>
								<option value="1845">1845</option>
								<option value="1850">1850</option>
								<option value="1855">1855</option>
								<option value="1900">1900</option>
								<option value="1905">1905</option>
								<option value="1910">1910</option>
								<option value="1915">1915</option>
								<option value="1920">1920</option>
								<option value="1925">1925</option>
								<option value="1930">1930</option>
								<option value="1935">1935</option>
								<option value="1940">1940</option>
								<option value="1945">1945</option>
								<option value="1950">1950</option>
								<option value="1955">1955</option>
								<option value="2000">2000</option>
								<option value="2005">2005</option>
								<option value="2010">2010</option>
								<option value="2015">2015</option>
								<option value="2020">2020</option>
								<option value="2025">2025</option>
								<option value="2030">2030</option>
								<option value="2035">2035</option>
								<option value="2040">2040</option>
								<option value="2045">2045</option>
								<option value="2050">2050</option>
								<option value="2055">2055</option>
								<option value="2100">2100</option>
								<option value="2105">2105</option>
								<option value="2110">2110</option>
								<option value="2115">2115</option>
								<option value="2120">2120</option>
								<option value="2125">2125</option>
								<option value="2130">2130</option>
								<option value="2135">2135</option>
								<option value="2140">2140</option>
								<option value="2145">2145</option>
								<option value="2150">2150</option>
								<option value="2155">2155</option>
								<option value="2200">2200</option>
								<option value="2205">2205</option>
								<option value="2210">2210</option>
								<option value="2215">2215</option>
								<option value="2220">2220</option>
								<option value="2225">2225</option>
								<option value="2230">2230</option>
								<option value="2235">2235</option>
								<option value="2240">2240</option>
								<option value="2245">2245</option>
								<option value="2250">2250</option>
								<option value="2255">2255</option>
								<option value="2300">2300</option>
								<option value="2305">2305</option>
								<option value="2310">2310</option>
								<option value="2315">2315</option>
								<option value="2320">2320</option>
								<option value="2325">2325</option>
								<option value="2330">2330</option>
								<option value="2335">2335</option>
								<option value="2340">2340</option>
								<option value="2345">2345</option>
								<option value="2350">2350</option>
								<option value="2355">2355</option>
							</select>
                        </td>
                    </tr>
                    
                </tbody>
				<tfoot>
					<tr>
						<td colspan="7">
							<a href="#" class="btn btn-primary" id="family_inbound_btn">Add More</a>
						</td>
					</tr>
				</tfoot>
            </table>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<label for="">Remarks</label>
						<textarea name="remarks" id="remarks" class="form-control"></textarea>
					</div>
				</div>
			</div>	
			<div class="row">
				<div class="col-md-12">
					<input type="checkbox" name="terms_and_conditions" id="terms_and_conditions"> I agree the Terms & Conditions
				</div>
				<div class="col-md-12 mt-3">
					<div id="loaderdisp" style="display: none;"><div class="loader"></div></div> 
					<div id="btndisp"> 
						<button class="btn btn-success" id="btnsubmit">Submit</button>
						<button type="reset" class="btn btn-danger">Cancel</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</form> 
<script>
$(document).ready(function(){

	$("#family_outbound_btn").click(function(e){
		e.preventDefault();
		let family_outbound_tr = $("#family_outbound_tr").html();
		$("#family_outbound_tbody").append("<tr>" + family_outbound_tr + "</tr>");

	});

	$("#family_inbound_btn").click(function(e){
		e.preventDefault();
		let family_inbound_tr = $("#family_inbound_tr").html();
		$("#family_inbound_tbody").append("<tr>" + family_inbound_tr + "</tr>");

	});
 

	$("#frmAirTicketRequest").validate({
		rules: {
			terms_and_conditions: "required"
		},
		submitHandler: function() {
			if($(".is_travelling:checked").length == 0) {
				alert("Please select atleast one checkbox");
				return false;
			}


			$("#loaderdisp").show();
            $("#btndisp").hide();
 
            let frmdata = $("#frmAirTicketRequest").serialize();
            $.ajax({
                method: "post",
                url: 'ajax_airticket-request.php',
                data: frmdata,
                beforeSend() {

                },
                success: function(response) {
                    let parseResp = $.parseJSON(response);
                    console.log(parseResp);
                    if(parseResp.success == 1) {
                        Swal.fire({
                            title: 'Successfully Applied!',
                            text: parseResp.message,
                            icon: 'success'
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 5000);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: parseResp.message,
                            icon: 'error'
                        });

                        $("#loaderdisp").hide();
                        $("#btndisp").show();
                    }
                    
                }
            });
		}
	});
});
</script>
<?php
echo $OUTPUT->footer();