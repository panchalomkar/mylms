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
$pagetitle = 'Salary Certificate Application';
$PAGE->set_context($context);
// $PAGE->set_url('/my/passport_withdrawal.php', $params);
$PAGE->set_url('/local/hrms/salary_certificate_application.php', $params);
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
        <a class="nav-link" href="salary_certificate_application_list.php">List</a>
    </li>
     
</ul>
<form name="frmSalaryCertificateApp" id="frmSalaryCertificateApp" method="post">
    <input type="hidden" name="requesttype" value="add">  
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="">Purpose Of The Application</label>
                <select name="purpose_of_application" id="purpose_of_application" class="form-control"> 
				    <option value="">--Select Purpose--</option>
                    <option value="Application for Opening of Bank Account">Application for Opening of Bank Account</option>
                    <option value="Application for Credit Card">Application for Credit Card</option>
                    <option value="Application for Bank Loan">Application for Bank Loan</option>
                    <option value="Application for Car Loan">Application for Car Loan</option>
                    <option value="Application for Tenancy Contract">Application for Tenancy Contract</option>
                    <option value="Application for Renewal of  Tenancy Contract">Application for Renewal of  Tenancy Contract</option>
                    <option value="Application for Family Visa">Application for Family Visa</option>
                    <option value="Application for Housing Loan">Application for Housing Loan</option>
                    <option value="Application forResidential Unit Loan">Application forResidential Unit Loan</option>
                    <option value="Application for Renewal of  Family Visa">Application for Renewal of  Family Visa</option>
                    <option value="Application for Visa - Conference">Application for Visa - Conference</option>
                    <option value="Others">Others</option>
                    <option value="Application for Visa - Tourism">Application for Visa - Tourism</option>
                    <option value="Application for Visa - Duty Travel">Application for Visa - Duty Travel</option>
                    <option value="Application for Family Visa - Tourism">Application for Family Visa - Tourism</option>
                    <option value="Application for Transferring Loan from Another Bank">Application for Transferring Loan from Another Bank</option>
                </select>
                
            </div>
        </div>
        <div class="col-md-12" id="other_purpose" style="display:none;">
            <div class="form-group">
                <label for="">Other</label>
                <input type="text" class="form-control" name="other_purpose_of_application" id="other_purpose_of_application" value="">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Company & Address</label>
                <textarea name="company_address" id="company_address" class="form-control"></textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Address</label>
                <textarea name="address" id="address" class="form-control"></textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">City</label>
                <input type="text" class="form-control" name="city" id="city" value="">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Country</label>
                <select name="country" id="country" class="form-control"> 
    				<option value="">--Select Country--</option> 
                    <option value="AFGHANISTAN">AFGHANISTAN</option>
                    <option value="ALBANIA">ALBANIA</option>
                    <option value="ALGERIA">ALGERIA</option>
                    <option value="AMERICAN SAMOA">AMERICAN SAMOA</option>
                    <option value="ANDORRA">ANDORRA</option>
                    <option value="ANGOLA">ANGOLA</option>
                    <option value="ANGUILLA">ANGUILLA</option>
                    <option value="ANTARCTICA">ANTARCTICA</option>
                    <option value="ANTIGUA AND BARBUDA">ANTIGUA AND BARBUDA</option>
                    <option value="ARGENTINA">ARGENTINA</option>
                    <option value="ARMENIA">ARMENIA</option>
                    <option value="ARUBA">ARUBA</option>
                    <option value="AUSTRALIA">AUSTRALIA</option>
                    <option value="AUSTRIA">AUSTRIA</option>
                    <option value="AZERBAIJAN">AZERBAIJAN</option>
                    <option value="BAHAMAS">BAHAMAS</option>
                    <option value="BAHRAIN">BAHRAIN</option>
                    <option value="BANGLADESH">BANGLADESH</option>
                    <option value="BARBADOS">BARBADOS</option>
                    <option value="BELARUS">BELARUS</option>
                    <option value="BELGIUM">BELGIUM</option>
                    <option value="BELIZE">BELIZE</option>
                    <option value="BENIN">BENIN</option>
                    <option value="BERMUDA">BERMUDA</option>
                    <option value="BHUTAN">BHUTAN</option>
                    <option value="BOLIVIA">BOLIVIA</option>
                    <option value="BOSNIA AND HERZEGOVINA">BOSNIA AND HERZEGOVINA</option>
                    <option value="BOTSWANA">BOTSWANA</option>
                    <option value="BOUVET ISLAND">BOUVET ISLAND</option>
                    <option value="BRAZIL">BRAZIL</option>
                    <option value="BRITISH ">BRITISH </option>
                    <option value="BRUNEI">BRUNEI</option>
                    <option value="BULGARIA">BULGARIA</option>
                    <option value="BURKINA FASO">BURKINA FASO</option>
                    <option value="BURUNDI">BURUNDI</option>
                    <option value="CAMBODIA">CAMBODIA</option>
                    <option value="CAMEROON">CAMEROON</option>
                    <option value="CANADA">CANADA</option>
                    <option value="CAPE VERDE">CAPE VERDE</option>
                    <option value="CAYMAN ISLANDS">CAYMAN ISLANDS</option>
                    <option value="CENTRAL AFRICAN REPUBLIC">CENTRAL AFRICAN REPUBLIC</option>
                    <option value="CHAD">CHAD</option>
                    <option value="CHILE">CHILE</option>
                    <option value="CHINA">CHINA</option>
                    <option value="CHRISTMAS ISLAND">CHRISTMAS ISLAND</option>
                    <option value="COCOS (KEELING) ISLANDS">COCOS (KEELING) ISLANDS</option>
                    <option value="COLOMBIA">COLOMBIA</option>
                    <option value="COMOROS">COMOROS</option>
                    <option value="CONGO">CONGO</option>
                    <option value="COOK ISLANDS">COOK ISLANDS</option>
                    <option value="COSTA RICA">COSTA RICA</option>
                    <option value="CÔTE D IVOIRE">CÔTE D IVOIRE</option>
                    <option value="CROATIA (HRVATSKA)">CROATIA (HRVATSKA)</option>
                    <option value="CUBA">CUBA</option>
                    <option value="CYPRUS">CYPRUS</option>
                    <option value="CZECH REPUBLIC">CZECH REPUBLIC</option>
                    <option value="CONGO (DRC)">CONGO (DRC)</option>
                    <option value="DENMARK">DENMARK</option>
                    <option value="DJIBOUTI">DJIBOUTI</option>
                    <option value="DOMINICA">DOMINICA</option>
                    <option value="DOMINICAN REPUBLIC">DOMINICAN REPUBLIC</option>
                    <option value="EAST TIMOR">EAST TIMOR</option>
                    <option value="ECUADOR">ECUADOR</option>
                    <option value="EGYPT">EGYPT</option>
                    <option value="EL SALVADOR">EL SALVADOR</option>
                    <option value="EQUATORIAL GUINEA">EQUATORIAL GUINEA</option>
                    <option value="ERITREA">ERITREA</option>
                    <option value="ESTONIA">ESTONIA</option>
                    <option value="ETHIOPIA">ETHIOPIA</option>
                    <option value="FALKLAND ISLANDS (ISLAS MALVINAS)">FALKLAND ISLANDS (ISLAS MALVINAS)</option>
                    <option value="FAROE ISLANDS">FAROE ISLANDS</option>
                    <option value="FIJI ISLANDS">FIJI ISLANDS</option>
                    <option value="FINLAND">FINLAND</option>
                    <option value="FRANCE">FRANCE</option>
                    <option value="FRENCH GUIANA">FRENCH GUIANA</option>
                    <option value="FRENCH POLYNESIA">FRENCH POLYNESIA</option>
                    <option value="FRENCH SOUTHERN AND ANTARCTIC LANDS">FRENCH SOUTHERN AND ANTARCTIC LANDS</option>
                    <option value="GABON">GABON</option>
                    <option value="GAMBIA">GAMBIA</option>
                    <option value="GEORGIA">GEORGIA</option>
                    <option value="GERMANY">GERMANY</option>
                    <option value="GHANA">GHANA</option>
                    <option value="GIBRALTAR">GIBRALTAR</option>
                    <option value="GREECE">GREECE</option>
                    <option value="GREENLAND">GREENLAND</option>
                    <option value="GRENADA">GRENADA</option>
                    <option value="GUADELOUPE">GUADELOUPE</option>
                    <option value="GUAM">GUAM</option>
                    <option value="GUATEMALA">GUATEMALA</option>
                    <option value="GUINEA">GUINEA</option>
                    <option value="GUINEABISSAU">GUINEABISSAU</option>
                    <option value="GUYANA">GUYANA</option>
                    <option value="HAITI">HAITI</option>
                    <option value="HEARD ISLAND AND MCDONALD ISLANDS">HEARD ISLAND AND MCDONALD ISLANDS</option>
                    <option value="HONDURAS">HONDURAS</option>
                    <option value="HONG KONG SAR">HONG KONG SAR</option>
                    <option value="HUNGARY">HUNGARY</option>
                    <option value="ICELAND">ICELAND</option>
                    <option value="INDIA">INDIA</option>
                    <option value="INDONESIA">INDONESIA</option>
                    <option value="IRAN">IRAN</option>
                    <option value="IRAQ">IRAQ</option>
                    <option value="IRELAND">IRELAND</option>
                    <option value="ISRAEL">ISRAEL</option>
                    <option value="ITALY">ITALY</option>
                    <option value="JAMAICA">JAMAICA</option>
                    <option value="JAPAN">JAPAN</option>
                    <option value="JORDAN">JORDAN</option>
                    <option value="KAZAKHSTAN">KAZAKHSTAN</option>
                    <option value="KENYA">KENYA</option>
                    <option value="KIRIBATI">KIRIBATI</option>
                    <option value="KOREA">KOREA</option>
                    <option value="KUWAIT">KUWAIT</option>
                    <option value="KYRGYZSTAN">KYRGYZSTAN</option>
                    <option value="LAOS">LAOS</option>
                    <option value="LATVIA">LATVIA</option>
                    <option value="LEBANON">LEBANON</option>
                    <option value="LESOTHO">LESOTHO</option>
                    <option value="LIBERIA">LIBERIA</option>
                    <option value="LIBYA">LIBYA</option>
                    <option value="LIECHTENSTEIN">LIECHTENSTEIN</option>
                    <option value="LITHUANIA">LITHUANIA</option>
                    <option value="LUXEMBOURG">LUXEMBOURG</option>
                    <option value="MACAU SAR">MACAU SAR</option>
                    <option value="MACEDONIA FORMER YUGOSLAV REPUBLIC OF">MACEDONIA FORMER YUGOSLAV REPUBLIC OF</option>
                    <option value="MADAGASCAR">MADAGASCAR</option>
                    <option value="MALAWI">MALAWI</option>
                    <option value="MALAYSIA">MALAYSIA</option>
                    <option value="MALDIVES">MALDIVES</option>
                    <option value="MALI">MALI</option>
                    <option value="MALTA">MALTA</option>
                    <option value="MARSHALL ISLANDS">MARSHALL ISLANDS</option>
                    <option value="MARTINIQUE">MARTINIQUE</option>
                    <option value="MAURITANIA">MAURITANIA</option>
                    <option value="MAURITIUS">MAURITIUS</option>
                    <option value="MAYOTTE">MAYOTTE</option>
                    <option value="MEXICO">MEXICO</option>
                    <option value="MICRONESIA">MICRONESIA</option>
                    <option value="MOLDOVA">MOLDOVA</option>
                    <option value="MONACO">MONACO</option>
                    <option value="MONGOLIA">MONGOLIA</option>
                    <option value="MONTSERRAT">MONTSERRAT</option>
                    <option value="MOROCCO">MOROCCO</option>
                    <option value="MOZAMBIQUE">MOZAMBIQUE</option>
                    <option value="MYANMAR">MYANMAR</option>
                    <option value="NAMIBIA">NAMIBIA</option>
                    <option value="NAURU">NAURU</option>
                    <option value="NEPAL">NEPAL</option>
                    <option value="NETHERLANDS">NETHERLANDS</option>
                    <option value="NETHERLANDS ANTILLES">NETHERLANDS ANTILLES</option>
                    <option value="NEW CALEDONIA">NEW CALEDONIA</option>
                    <option value="NEW ZEALAND">NEW ZEALAND</option>
                    <option value="NICARAGUA">NICARAGUA</option>
                    <option value="NIGER">NIGER</option>
                    <option value="NIGERIA">NIGERIA</option>
                    <option value="NIUE">NIUE</option>
                    <option value="NORFOLK ISLAND">NORFOLK ISLAND</option>
                    <option value="NORTH KOREA">NORTH KOREA</option>
                    <option value="NORTHERN MARIANA ISLANDS">NORTHERN MARIANA ISLANDS</option>
                    <option value="NORWAY">NORWAY</option>
                    <option value="OMAN">OMAN</option>
                    <option value="PAKISTAN">PAKISTAN</option>
                    <option value="PALAU">PALAU</option>
                    <option value="PANAMA">PANAMA</option>
                    <option value="PAPUA NEW GUINEA">PAPUA NEW GUINEA</option>
                    <option value="PARAGUAY">PARAGUAY</option>
                    <option value="PERU">PERU</option>
                    <option value="PHILIPPINES">PHILIPPINES</option>
                    <option value="PITCAIRN ISLANDS">PITCAIRN ISLANDS</option>
                    <option value="POLAND">POLAND</option>
                    <option value="PORTUGAL">PORTUGAL</option>
                    <option value="PUERTO RICO">PUERTO RICO</option>
                    <option value="QATAR">QATAR</option>
                    <option value="REUNION">REUNION</option>
                    <option value="ROMANIA">ROMANIA</option>
                    <option value="RUSSIA">RUSSIA</option>
                    <option value="RWANDA">RWANDA</option>
                    <option value="ST. KITTS AND NEVIS">ST. KITTS AND NEVIS</option>
                    <option value="ST. LUCIA">ST. LUCIA</option>
                    <option value="ST. VINCENT AND THE GRENADINES">ST. VINCENT AND THE GRENADINES</option>
                    <option value="SAMOA">SAMOA</option>
                    <option value="SAN MARINO">SAN MARINO</option>
                    <option value="SÃO TOMÉ AND PRÍNCIPE">SÃO TOMÉ AND PRÍNCIPE</option>
                    <option value="SAUDI ARABIA">SAUDI ARABIA</option>
                    <option value="SENEGAL">SENEGAL</option>
                    <option value="SEYCHELLES">SEYCHELLES</option>
                    <option value="SIERRA LEONE">SIERRA LEONE</option>
                    <option value="SINGAPORE">SINGAPORE</option>
                    <option value="SLOVAKIA">SLOVAKIA</option>
                    <option value="SLOVENIA">SLOVENIA</option>
                    <option value="SOLOMON ISLANDS">SOLOMON ISLANDS</option>
                    <option value="SOMALIA">SOMALIA</option>
                    <option value="SOUTH AFRICA">SOUTH AFRICA</option>
                    <option value="SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS">SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS</option>
                    <option value="SPAIN">SPAIN</option>
                    <option value="SRI LANKA">SRI LANKA</option>
                    <option value="ST. HELENA">ST. HELENA</option>
                    <option value="ST. PIERRE AND MIQUELON">ST. PIERRE AND MIQUELON</option>
                    <option value="SUDAN">SUDAN</option>
                    <option value="SURINAME">SURINAME</option>
                    <option value="SVALBARD AND JAN MAYEN">SVALBARD AND JAN MAYEN</option>
                    <option value="SWAZILAND">SWAZILAND</option>
                    <option value="SWEDEN">SWEDEN</option>
                    <option value="SWITZERLAND">SWITZERLAND</option>
                    <option value="SYRIA">SYRIA</option>
                    <option value="TAIWAN">TAIWAN</option>
                    <option value="TAJIKISTAN">TAJIKISTAN</option>
                    <option value="TANZANIA">TANZANIA</option>
                    <option value="THAILAND">THAILAND</option>
                    <option value="TOGO">TOGO</option>
                    <option value="TOKELAU">TOKELAU</option>
                    <option value="TONGA">TONGA</option>
                    <option value="TRINIDAD AND TOBAGO">TRINIDAD AND TOBAGO</option>
                    <option value="TUNISIA">TUNISIA</option>
                    <option value="TURKEY">TURKEY</option>
                    <option value="TURKMENISTAN">TURKMENISTAN</option>
                    <option value="TURKS AND CAICOS ISLANDS">TURKS AND CAICOS ISLANDS</option>
                    <option value="TUVALU">TUVALU</option>
                    <option value="UGANDA">UGANDA</option>
                    <option value="UKRAINE">UKRAINE</option>
                    <option value="UAE">UAE</option>
                    <option value="UNITED KINGDOM">UNITED KINGDOM</option>
                    <option value="UNITED STATES">UNITED STATES</option>
                    <option value="UNITED STATES MINOR OUTLYING ISLANDS">UNITED STATES MINOR OUTLYING ISLANDS</option>
                    <option value="URUGUAY">URUGUAY</option>
                    <option value="UZBEKISTAN">UZBEKISTAN</option>
                    <option value="VANUATU">VANUATU</option>
                    <option value="VATICAN CITY">VATICAN CITY</option>
                    <option value="VENEZUELA">VENEZUELA</option>
                    <option value="VIET NAM">VIET NAM</option>
                    <option value="VIRGIN ISLANDS (BRITISH)">VIRGIN ISLANDS (BRITISH)</option>
                    <option value="VIRGIN ISLANDS">VIRGIN ISLANDS</option>
                    <option value="WALLIS AND FUTUNA">WALLIS AND FUTUNA</option>
                    <option value="YEMEN">YEMEN</option>
                    <option value="YUGOSLAVIA">YUGOSLAVIA</option>
                    <option value="ZAMBIA">ZAMBIA</option>
                    <option value="ZIMBABWE">ZIMBABWE</option>
                    <option value="PALESTINE">PALESTINE</option>
                    <option value="UAE (BEDON)">UAE (BEDON)</option>
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div id="loaderdisp" style="display: none;"><div class="loader"></div></div> 
            <div id="btndisp"> 
                <button class="btn btn-success" id="btnsubmit">Submit</button>
                <button type="reset" class="btn btn-danger">Cancel</button>
            </div>
        </div>
    </div>

<form>

<script>
$(document).ready(function(){

    

    $("#purpose_of_application").change(function(){

        if($(this).val() == "Others") {
            $("#other_purpose").show();
        } else {
            $("#other_purpose").hide();
            $("#other_purpose_of_application").val("");
        }
    });

    $("#frmSalaryCertificateApp").validate({
        rules: {
            purpose_of_application: "required",  
            company_address: "required",  
            address: "required",  
            city: "required",
            country: "required",
            other_purpose_of_application: {
                required: function() {
                    if($("#purpose_of_application").val() == "Others"){
                        return true;
                    } else {
                        return false;
                    }
                }
            } 
        },
        submitHandler: function() {
            $("#loaderdisp").show();
            $("#btndisp").hide();
 
            let frmdata = $("#frmSalaryCertificateApp").serialize();
            $.ajax({
                method: "post",
                url: 'ajax_salary_certificate_application.php',
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