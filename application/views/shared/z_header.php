<?php 
//Attempt to fetch session variables:
$udata = $this->session->userdata('user');
$website = $this->config->item('website');
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<link rel="icon" type="image/png" href="/img/bp_16.png">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title><?= ( isset($title) ? $title : $website['name'] ) ?></title>
	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

	<!-- Fonts and icons -->
	<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Lato|Roboto:300,400,500,700|Roboto+Slab:400,700|Material+Icons|Titillium+Web:700" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" />

	<!-- CSS Addons -->
    <link href="/css/lib/bootstrap.min.css" rel="stylesheet" />
    <link href="/css/lib/animate.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link href="/css/challenges/material-kit.css?v=<?= version_salt() ?>" rel="stylesheet"/>
    <link href="/css/challenges/styles.css?v=<?= version_salt() ?>" rel="stylesheet"/>
    
    <!-- Core JS Files -->
	<script src="/js/lib/jquery.min.js" type="text/javascript"></script>
	<script src="/js/lib/bootstrap.min.js" type="text/javascript"></script>
	<script src="/js/lib/material.min.js"></script>
	
	<!--    Plugin for Date Time Picker and Full Calendar Plugin   -->
	<script src="/js/lib/moment.min.js"></script>

	<!--	Plugin for the Datepicker, full documentation here: https://github.com/Eonasdan/bootstrap-datetimepicker   -->
	<script src="/js/lib/bootstrap-datetimepicker.js" type="text/javascript"></script>

	<!--	Plugin for Fileupload, full documentation here: http://www.jasny.net/bootstrap/javascript/#fileinput   -->
	<script src="/js/lib/jasny-bootstrap.min.js"></script>
	
	<!--	Plugin for Text Flasher http://morphext.fyianlai.com/ -->
	<script src="/js/lib/morphext.min.js"></script>

	<!--    Control Center for Material Kit: activating the ripples, parallax effects, scripts from the example pages etc    
	<script src="/js/lib/material-kit.js?v=1.1.0" type="text/javascript"></script>-->
	
	<!-- Custom JS file -->
	<script src="/js/challenges/global.js?v=<?= version_salt() ?>" type="text/javascript"></script>
</head>

<body class="landing-page">
    <nav class="navbar navbar-warning navbar-fixed-top navbar-color-on-scroll <?= ( isset($landing_page) ? 'navbar-transparent': 'no-adj') ?>">
    	<div class="container">
        	<!-- Brand and toggle get grouped for better mobile display -->
        	<div class="navbar-header">
        		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navigation-example">
            		<span class="sr-only">Toggle navigation</span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
        		</button>
        		<a class="navbar-brand" href="/"><img src="/img/bp_48.png" /><span>mench</span></a>
        	</div>

        	<div class="collapse navbar-collapse">
        		<ul class="nav navbar-nav navbar-right">
    				<li><a href="/challenges"><i class="fa fa-search"></i> Browse</a></li>
    				<li><a href="/launch"><i class="fa fa-rocket"></i> Launch</a></li>
    				<?php
    				if(isset($udata['id'])){
    					echo '<li class="dropdown" id="isloggedin">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<i class="fa fa-user-circle-o"></i> '.$udata['first_name'].'
								<b class="caret"></b>
							<div class="ripple-container"></div></a>
							<ul class="dropdown-menu dropdown-with-icons">
								<li><a href="/account"><i class="fa fa-user-circle-o"></i> My Account</a></li>
								'.( $udata['status']>=1 ? '<li><a href="/dashboard"><i class="fa fa-tachometer"></i> Seller Dashboard</a></li>' : '' ).'
								'.( $udata['status']>=1 ? '<li><a href="/users"><i class="fa fa-users"></i> Browse Users</a></li>' : '' ).'
								'.( $udata['status']>=1 ? '<li><a href="/challenges/start"><i class="fa fa-plus-circle"></i> New Challenge</a></li>' : '' ).'
								<li><a href="#" id="logoutbutton"><i class="fa fa-sign-out"></i> Logout</a></li>
							</ul>
						</li>';
    				} else {
    					echo '<li><a href="#" data-toggle="modal" data-target="#loginModal"><i class="fa fa-sign-in"></i> Sign In / Sign Up</a></li>';
    				}
    				?>
        		</ul>
        	</div>
    	</div>
    </nav>
    
<?php
//Any landing pages?
if(isset($landing_page)){
	//Yes, load the page:
	$this->load->view($landing_page);
	
	//Load landing page containers:
	echo '<div class="main main-raised">';
	echo '<div class="container">';
} else {
	//Regular content page:
	echo '<div class="main main-raised main-plain">';
	echo '<div class="container">';
}
?>