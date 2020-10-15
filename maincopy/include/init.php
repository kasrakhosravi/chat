<?php

header('Access-Control-Allow-Origin: *');

if (!file_exists('../config.php')) {
    die();
}
if (!defined('SB_PATH')) define('SB_PATH', dirname(dirname(__FILE__)));
require('../config.php');
require('functions.php');
sb_init_translations();
require('components.php');
sb_component_chat();

die();

?>