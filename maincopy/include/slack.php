<?php

if (file_exists('../apps/slack/functions.php')) {
    require_once('functions.php');
    require_once('../apps/slack/functions.php');
    $response = json_decode(file_get_contents('php://input'), true);
    sb_slack_listener($response);
}

?>