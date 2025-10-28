<?php
global $CFG, $DB, $OUTPUT, $PAGE, $USER;
$tabid = '';
$limit = 10;
$id = optional_param('id', 0, PARAM_INT);
$selectPageNo = optional_param('selectPageNo', 1, PARAM_INT);
$selectPageNo1 = optional_param('selectPageNo1', 1, PARAM_INT);
$selectPageNo2 = optional_param('selectPageNo2', 1, PARAM_INT);
$selectPageNo3 = optional_param('selectPageNo3', 1, PARAM_INT);
$pagination = '';
$pagination1 = '';
$pagination2 = '';
$pagination3 = '';
$tabid = '';
$errormessage1 = '';
$rows = '';
?>
<style type="text/css">
	ul li {
		text-decoration: none;
		list-style-type: none;
	}

	.competencytable table {
		padding: 20px;
	}

	.competencytable td {
		padding: 10px !important;
	}

	.competencytable th {
		text-align: center;
		padding: 10px !important;
		color:#fff;
		background-color: #003152;
	}

	.usersrow {
		text-align: center;
	}

	.competency_title {
		color: red;
		background-color: #ccc;
	}

	.userlist {
		color: red;
		background-color: #ccc;
	}

	.dot {
		height: 8px;
		width: 8px;
		background-color: #787878;
		border-radius: 50%;
		display: inline-block;
	}

	/* Tabs wrapper layout */
	.nav-tabs.styled-tabs {
		display: flex;
		flex-wrap: wrap;
		justify-content: flex-start;
		border-bottom: none;
		margin-bottom: -1px;
	}
	#page-local-competency-landdrating .table-wrap.wrapper1,#page-local-competency-managersrating .table-wrap.wrapper1{border-radius: 10px !important;
    overflow: hidden;
    text-align: center;
    box-shadow: 0px 5px 0px 0px #003152;
}
.path-local-competency table.competencytable{border-collapse: collapse !important;}
#page-local-competency-userwisereport {
    .table-scroll th {
        color: white !important;
        background: #003152;
    }
}
#page-local-competency-approval .competencytable .card-body div.text-end{display: flex; justify-content: center;}
#page-local-competency-userwisereport {
    .wrapper {
        /* padding: 5px; */
        border: none !important;
        border-radius: 10px !important;
        /* overflow: hidden; */
        text-align: center;
        box-shadow: 0px 5px 0px 0px #003152;
    }
}
#page-local-competency-approval{
th.sticky-col.first-col, th.userlist {
    z-index: 99;
}
.table-bordered thead th, .table-bordered thead td {
    z-index: 9;
}}

#page-local-competency-managerwisereport .table thead th {
    vertical-align: bottom;
    border-bottom: none;
    background: #003152;
    color: #fff !important;
}

#page-local-competency-managerwisereport .table-scroll .table-wrap.wrapper {
    position: relative;
    overflow: auto;
    /* border: 1px solid #dee2e6; */
    border: 1px solid #4e4e54f0;
    border: none !important;
    border-radius: 10px !important;
    /* overflow: hidden; */
    text-align: center;
    box-shadow: 0px 5px 0px 0px #003152;
}
	/* Each tab equal width (6 per row = 12 tabs in 2 rows) */
	.nav-tabs.styled-tabs .nav-item {
		flex: 0 0 calc(100% / 6);
		text-align: center;
		margin-right: 0;
	}

	/* Tab link style */
	.nav-tabs.styled-tabs .nav-link {
		border: 1px solid #ccc;
		border-bottom: none;
		background: #f1f1f1;
		color: #555;
		padding: 8px 10px;
		margin-right: -1px;
		font-weight: 500;
		border-top-left-radius: 12px;
		border-top-right-radius: 12px;
		position: relative;
		z-index: 1;
		transition: all 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		white-space: nowrap;
	}

	/* Icon spacing in tab */
	.nav-tabs.styled-tabs .nav-link i {
		margin-right: 4px;
		font-size: 12px;
		color: #6c757d;
	}

	/* Hover effect */
	.nav-tabs.styled-tabs .nav-link:hover {
		background-color: #eee;
		color: #ec9707;
	}

	/* Active tab design (like your image) */
	.nav-tabs.styled-tabs .nav-link.active {
		background: linear-gradient(to bottom, #c53100 0%, #ec9707 100%);
		color: #fff;
		z-index: 3;
		font-weight: 600;
		border-color: #ec9707 #ec9707 #fff;
	}

	/* Lift effect for active tab */
	.nav-tabs.styled-tabs .nav-link.active::after {
		content: "";
		position: absolute;
		bottom: -1px;
		left: 0;
		right: 0;
		height: 2px;
		background: white;
		z-index: 2;
	}

	/* Active icon color */
	.nav-tabs.styled-tabs .nav-link.active i {
		color: #fff;
	}

	/* Optional: curved ends for first/last tab */
	.nav-tabs.styled-tabs .nav-item:first-child .nav-link {
		border-top-left-radius: 20px;
	}

	.nav-tabs.styled-tabs .nav-item:last-child .nav-link {
		border-top-right-radius: 20px;
	}

	/* Tab content container */
	.tab-content-container {
		border: 1px solid #ccc;
		background-color: white;
		padding: 20px;
		border-top: none;
		border-bottom-left-radius: 12px;
		border-bottom-right-radius: 12px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
		z-index: 1;
		position: relative;
		margin-top: -1px;
	}

	/* Responsive (optional): stack tabs in smaller screens */
	@media (max-width: 768px) {
		.nav-tabs.styled-tabs .nav-item {
			flex: 0 0 50%;
		}
	}
	.path-local-competency #page-wrapper #page {
		padding-left: 18em;

		div[role="main"] {
			padding: 10px 20px 0px 0px;
		}
	}
	.path-local-competency .collapsed #page-wrapper #page,
	.path-local-competency #page-wrapper #page {
		padding-right: 0;
	}

	/* #competencytabs {
		position: fixed;
		background: #fff;
		z-index: 999;
		padding: 8px;
		padding-top: 5px;
    top: 90px;
		margin-right: 20px;
		box-shadow: 1px 5px 12px -5px;
	} */
	#competencytabs {
	position: fixed;
    background: #fff;
    z-index: 999;
    padding: 8px;
    padding-top: 49px;
    border-radius: 0px 0px 10px 10px;
    top: 50px;
    margin-right: 20px;
    box-shadow: 1px 5px 12px -5px;
	}
	.path-local-competency #page-header {
		/* font-size: 12px !important; */
		margin-bottom: 0px !important;
		margin-top: 65px;
	.header-heading{display: none;}
	}

	#page-local-competency-mainheading #topofscroll {
		margin-bottom: 10px !important
	}

	#page-local-competency-mainheading .table-responsive{
		display: flex !important;
		justify-content: center;
		width: 100%;

		table.generaltable {
			width: 60%;
			border-radius: 10px !important;
			overflow: hidden;
			text-align: center;
			box-shadow: 0px 5px 0px 0px #003152;

			td {
				padding: 5px;
			}
		}
	}


	#page-local-competency-subcompetency .table-responsive,#page-local-competency-subsubcompetency .table-responsive{
		display: flex !important;
		justify-content: center;
		width: 100%;
padding: 30px;
padding-top:0px;
		table.generaltable {
			width: 100%;
			border-radius: 10px !important;
			overflow: hidden;
			text-align: center;
			box-shadow: 0px 5px 0px 0px #003152;

			td {
				padding: 5px;
			}
		}
	}

	#page-local-competency-uploadcompetency div[role="main"] form.mform{
background: #fff;
    padding: 20px;
}

#page-local-competency-viewcompetency .accordion>.card>.card-header
 {
    border-radius: 10px;
    margin: 10px;
    margin-bottom: -1px;
    background: #003152 !important;

	h5{ color:#fff}
}
#page-local-competency-viewcompetency .accordion-blocks div.collapse[role="tabpanel"]{
padding:20px;
.table-responsive{
	overflow: hidden;
    border-radius: 10px;
    border: solid 1px darkseagreen;
	thead{
		background:#003152;
	 th {
    color: #fff;
}}

}


</style>