<?php
//Attempt to fetch session variables:
$session_en = $this->session->userdata('user');
$url_part_1 = $this->uri->segment(1);
?><!doctype html>
<html lang="en">
<head>
    <!--

    WELCOME TO MENCH'S SOURCE CODE 😻​

    INTERESTED IN HELPING US BUILD THE FUTURE OF EDUCATION?

    YOU CAN WORK WITH US FROM ANYWHERE IN THE WORLD

    EMAIL YOUR RESUME TO SUPPORT@MENCH.COM

    -->
    <meta charset="utf-8"/>
    <link rel="icon" type="image/png" href="/img/bp_16.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title><?= (isset($title) ? $title . ' | ' : '') . 'Mench' ?></title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport'/>
    <?php $this->load->view('view_shared/global_js_css'); ?>
</head>

<body class="landing-page">

<nav class="navbar navbar-warning navbar-fixed-top navbar-color-on-scroll no-adj">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navigation-example">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/"><img src="/img/bp_128.png"/><span
                        style="text-transform: lowercase; color: #2f2739;">Mench</span></a>
        </div>

        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-right">

                <li><a href="/7436"><i class="fal fa-info-circle"></i> About Us</a></li>

                <?php
                if (isset($session_en['en_id'])) {

                    //Everyone has an Action Plan:
                    echo '<li id="isloggedin"><a href="/messenger/actionplan"><i class="fas fa-flag"></i> Action Plan</a></li>';

                    //Is this user a Miner?
                    if(isset($session_en['en__parents']) && filter_array($session_en['en__parents'], 'en_id', 1308)){
                        echo '<li id="isloggedin"><a href="/intents/' . (isset($in['in_id']) ? $in['in_id'] : $this->config->item('in_miner_start')) . '"><i class="fas fa-yin-yang fa-spin"></i> The Matrix</a></li>';
                    }

                } else {
                    echo '<li><a href="/login'. ( isset($in['in_id']) ? '?url=%2Fintents%2F'.$in['in_id'] : '' ) .'"><i class="fas fa-sign-in"></i> Sign In</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
</nav>


<div class="main main-raised main-plain">
    <div class="container body-container">

<?php
//Show possible flash message:
$hm = $this->session->flashdata('flash_message');
if ($hm) {
    echo $hm;
}

if (isset($message)) {
    echo $message;
}
?>