<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
global $SB_CONNECTION;
define('SB_PATH', getcwd());
if (!file_exists('config.php')) {
    $raw = str_replace(['[url]', '[name]', '[user]', '[password]', '[host]', '[port]'], '', file_get_contents('resources/config-source.php'));
    $file = fopen('config.php', 'w');
    fwrite($file, $raw);
    fclose($file);
}
require('config.php');
require('include/functions.php');
require('include/components.php');
$connection_check = sb_db_check_connection();

echo $itstheuser = $_POST["u"];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#279EFF" />
    <title>SupportOnSite</title>
    <link rel="shortcut icon" type="image/ico" href="media/favicon.ico" />
    <script src="js/min/jquery.min.js"></script>
    <script src="js/main.js?v=<?php echo SB_VERSION ?>"></script>
    <script src="js/admin.js?v=<?php echo SB_VERSION ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/min/admin.min.css?v=<?php echo SB_VERSION ?>" media="all" />
    <style>
    .sb-admin>.sb-header {
    background: #ffffff !important;
    box-shadow: none !important;
    }
    .sb-menu-wide li.sb-active, .sb-tab>.sb-nav li.sb-active, .sb-v-tab>.sb-nav li.sb-active {
    border-bottom: 2px solid #279EFF !important;
    }
    .sb-menu-wide li.sb-active, .sb-menu-wide li:hover, .sb-tab>.sb-nav li.sb-active, .sb-tab>.sb-nav li:hover, .sb-v-tab>.sb-nav li.sb-active, .sb-v-tab>.sb-nav li:hover {
    color: #279EFF !important;
    }
    .sb-btn, a.sb-btn, a.sb-rich-btn.sb-btn {
    background-color: #279EFF !important;
    }
    .sb-setting input:focus, .sb-setting select:focus, .sb-setting textarea:focus, .sb-input-setting input:focus, .sb-input-setting select:focus, .sb-input-setting textarea:focus {
    border: 1px solid #279EFF !important;
    box-shadow: none !important;
    }
    .sb-setting input[type=checkbox]:checked:before, .sb-input-setting input[type=checkbox]:checked:before {
    color: #279EFF !important;
    }
    .sb-admin>.sb-header>.sb-admin-nav>div>a:hover, .sb-admin>.sb-header>.sb-admin-nav>div>a.sb-active {
    color: #279EFF !important;
    }
    .sb-admin>.sb-header>.sb-admin-nav>img {
    height: 50px !important;
    margin: 18px 7px !important;
    }
    div ul.sb-menu li:hover, .sb-select ul li:hover {
    background-color: #279EFF !important;
    color: #ffffff !important;
    }
    .sb-search-btn>input {
    border: 1px solid #279EFF !important;
    box-shadow: none !important;
    }
    </style>
    <?php
    if ($connection_check === true) {
        sb_js_global();
        sb_js_admin();
    }
    ?>
</head>
<body>
    <?php
    if ($connection_check !== true) {
        sb_installation_box($connection_check);
        die();
    }
    sb_component_admin();
    ?>
</body>
</html>