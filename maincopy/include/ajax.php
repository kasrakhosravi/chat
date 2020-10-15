<?php

header("Access-Control-Allow-Origin: *");


if (!isset($_POST['function'])) {
    die();
}
global $SB_LANGUAGE;
$SB_LANGUAGE = sb_post('language');
require_once('functions.php');

switch ($_POST['function']) {
    case 'emoji':
        die(file_get_contents(SB_PATH . '/resources/json/emoji.json'));
    case 'saved-replies':
        die(sb_ajax_response(sb_get_setting('saved-replies')));
    case 'save-settings':
        die(sb_ajax_response(sb_save_settings($_POST['settings'], $_POST['external_settings']) ));
    case 'get-settings':
        die(sb_ajax_response(sb_get_settings()));
    case 'get-all-settings':
        die(sb_ajax_response(sb_get_all_settings()));
    case 'get-front-settings':
        die(sb_ajax_response(sb_get_front_settings()));
    case 'get-block-setting':
        die(sb_ajax_response(sb_get_block_setting($_POST['value'])));
    case 'add-user':
        die(sb_ajax_response(sb_add_user($_POST['settings'], sb_post('settings_extra', null))));
    case 'add-user-and-login':
        die(sb_ajax_response(sb_add_user_and_login(sb_post('settings', null), sb_post('settings_extra', null), sb_post('login_app'))));
    case 'get-user':
        die(sb_ajax_response(sb_get_user($_POST['user_id'], sb_post('extra'))));
    case 'get-users':
        die(sb_ajax_response(sb_get_users(sb_post('exclude_id', -1), sb_post('sorting', ['creation_time', 'DESC']), sb_post('user_types', []), sb_post('search', ''))));
    case 'get-new-users':
        die(sb_ajax_response(sb_get_new_users($_POST['datetime'])));
    case 'get-user-extra':
        die(sb_ajax_response(sb_get_user_extra($_POST['user_id'])));
    case 'get-online-users':
        die(sb_ajax_response(sb_get_online_users(sb_post('exclude_id', -1))));
    case 'search-users':
        die(sb_ajax_response(sb_search_users($_POST['search'])));
    case 'get-active-user':
        die(sb_ajax_response(sb_get_active_user(sb_post('storage', null), sb_post('db'), sb_post('login_app'))));
    case 'delete-user':
        die(sb_ajax_response(sb_delete_user($_POST['user_id'])));
    case 'delete-users':
        die(sb_ajax_response(sb_delete_users($_POST['user_ids'])));
    case 'update-user':
        die(sb_ajax_response(sb_update_user($_POST['user_id'], $_POST['settings'], $_POST['settings_extra'])));
    case 'update-user-to-lead':
        die(sb_ajax_response(sb_update_user_to_lead($_POST['user_id'])));
    case 'get-conversations':
        die(sb_ajax_response(sb_get_conversations($_POST['pagination'], sb_post('status', 0))));
    case 'get-new-conversations':
        die(sb_ajax_response(sb_get_new_conversations($_POST['datetime'], sb_post('exclude_id', -1))));
    case 'get-conversation':
        die(sb_ajax_response(sb_get_conversation($_POST['user_id'], $_POST['conversation_id'])));
    case 'search-conversations':
        die(sb_ajax_response(sb_search_conversations($_POST['search'])));
    case 'get-new-messages':
        die(sb_ajax_response(sb_get_new_messages($_POST['user_id'], $_POST['conversation_id'], $_POST['datetime'])));
    case 'new-conversation':
        die(sb_ajax_response(sb_new_conversation($_POST['user_id'], sb_post('status_code', 0), sb_post('title', ''))));
    case 'get-user-conversations':
        die(sb_ajax_response(sb_get_user_conversations($_POST['user_id'], sb_post('exclude_id', -1))));
    case 'get-new-user-conversations':
        die(sb_ajax_response(sb_get_new_user_conversations($_POST['user_id'], $_POST['datetime'])));
    case 'update-conversation-status':
        die(sb_ajax_response(sb_update_conversation_status($_POST['conversation_id'], $_POST['status'])));
    case 'update-users-last-activity':
        die(sb_ajax_response(sb_update_users_last_activity($_POST['user_id'], sb_post('return_user_id', -1), sb_post('check_slack'))));
    case 'is-typing':
        die(sb_ajax_response(sb_is_typing($_POST['user_id'], $_POST['conversation_id'])));
    case 'set-typing':
        die(sb_ajax_response(sb_set_typing($_POST['user_id'], $_POST['conversation_id'])));
    case 'login':
        die(sb_ajax_response(sb_login(sb_post('email', ''), sb_post('password', ''), sb_post('user_id', ''), sb_post('token', ''))));
    case 'logout':
        die(sb_ajax_response(sb_logout()));
    case 'update-login':
        die(sb_ajax_response(sb_update_login(sb_post('profile_image', ''), sb_post('first_name', ''), sb_post('last_name', ''), sb_post('email', ''))));
    case 'send-message':
        die(sb_ajax_response(sb_send_message($_POST['user_id'], $_POST['conversation_id'], sb_post('message', ''), sb_post('attachments', []), $_POST['conversation_status'], sb_post('payload'))));
    case 'send-bot-message':
        die(sb_ajax_response(sb_send_bot_message($_POST['conversation_id'], $_POST['message'], $_POST['token'], sb_post('language', ''))));
    case 'send-slack-message':
        die(sb_ajax_response(sb_send_slack_message($_POST['user_id'], $_POST['full_name'], sb_post('profile_image'), sb_post('message', ''), sb_post('attachments', []), sb_post('channel', -1))));
    case 'update-message':
        die(sb_ajax_response(sb_update_message($_POST['user_id'], $_POST['message_id'], sb_post('message'), sb_post('attachments'), sb_post('payload'))));
    case 'delete-message':
        die(sb_ajax_response(sb_delete_message($_POST['user_id'], $_POST['message_id'])));
    case 'csv-users':
        die(sb_ajax_response(sb_csv_users()));
    case 'csv-conversations':
        die(sb_ajax_response(sb_csv_conversations(sb_post('conversation_id', -1))));
    case 'update-user-and-message':
        die(sb_ajax_response(sb_update_user_and_message($_POST['user_id'], sb_post('settings', []), sb_post('settings_extra', []), sb_post('message_id', ''), sb_post('message', ''), sb_post('payload'))));
    case 'get-rich-message':
        die(sb_ajax_response(sb_get_rich_message($_POST['name'])));
    case 'create-email':
        die(sb_ajax_response(sb_email_create($_POST['recipient_id'], $_POST['sender_name'], $_POST['sender_profile_image'], $_POST['message'], sb_post('attachments', []))));
    case 'send-test-email':
        die(sb_ajax_response(sb_email_send_test($_POST['to'], $_POST['email_type'])));
    case 'slack-users':
        die(sb_ajax_response(sb_slack_users()));
    case 'archive-slack-channels':
        die(sb_ajax_response(sb_archive_slack_channels()));
    case 'clean-data':
        die(sb_ajax_response(sb_clean_data()));
    case 'user-autodata':
        die(sb_ajax_response(sb_user_autodata($_POST['user_id'], $_POST['data'])));
    case 'current-url':
        die(sb_ajax_response(sb_current_url(sb_post('url'))));
    case 'get-translations':
        die(sb_ajax_response(sb_get_translations()));
    case 'save-translations':
        die(sb_ajax_response(sb_save_translations($_POST['translations'])));
    case 'dialogflow-intent':
        die(sb_ajax_response(sb_dialogflow_intent($_POST['expressions'], $_POST['response'], sb_post('agent_language', ''))));
    case 'set-rating':
        die(sb_ajax_response(sb_set_rating($_POST['settings'], $_POST['payload'], $_POST['message_id'], $_POST['message'], $_POST['user_id'])));
    case 'get-rating':
        die(sb_ajax_response(sb_get_rating($_POST['user_id'])));
    case 'save-articles':
        die(sb_ajax_response(sb_save_articles($_POST['articles'])));
    case 'get-articles':
        die(sb_ajax_response(sb_get_articles(sb_post('id', -1), false, sb_post('full'))));
    case 'search-articles':
        die(sb_ajax_response(sb_search_articles($_POST['search'])));
    case 'installation':
        die(sb_ajax_response(sb_installation($_POST['details'])));
    case 'get-versions':
        die(sb_ajax_response(sb_get_versions()));
    case 'update':
        die(sb_ajax_response(sb_update()));
   case 'app-activation':
       die(sb_ajax_response(sb_app_activation($_POST['app_name'], $_POST['key'])));
   case 'app-get-key':
       die(sb_ajax_response(sb_app_get_key($_POST['app_name'])));
   case 'wp-synch':
       die(sb_ajax_response(sb_wp_synch()));
   default:
        die('["error", "Support Board Error [ajax.php]: No functions found with name: ' . $_POST['function'] . '."]');
}

function sb_ajax_response($result) {
    if (sb_is_error($result)) {
        return '["error", "' . $result->message() . '"]';
    } else {
        return json_encode(['success', $result]);
    }
}

function sb_post($key, $default = false) {
    global $_POST;
    return isset($_POST[$key]) ? ($_POST[$key] == 'false' ? false : $_POST[$key]) : $default;
}

function sb_security() {
    global $_POST;
    $security = [
        'admin' => ['save-settings', 'get-settings', 'get-all-settings', 'add-user', 'delete-user', 'delete-users', 'app-get-key', 'app-activation', 'wp-synch'],
        'agent' => ['get-users', 'get-new-users', 'get-online-users', 'search-users', 'get-conversations', 'get-new-conversations', 'search-conversations', 'csv-users', 'csv-conversations', 'send-test-email', 'slack-users', 'clean-data', 'save-translations', 'dialogflow-intent', 'get-rating', 'save-articles', 'update', 'archive-slack-channels'],
        'user' => ['update-login', 'update-user', 'get-user', 'get-user-extra', 'update-user-to-lead', 'new-conversation', 'get-user-conversations', 'get-new-user-conversations', 'send-slack-message', 'slack-unarchive', 'update-message', 'delete-message', 'update-user-and-message', 'get-conversation', 'get-new-messages'],
        'login' => ['update-conversation-status', 'update-users-last-activity', 'is-typing', 'send-message', 'set-typing', 'create-email', 'user-autodata', 'set-rating', 'get-articles', 'search-articles', 'emoji', 'saved-replies', 'get-translations']
    ];
    $function = $_POST['function'];
    $user_id = sb_post('user_id', -1);
    $user = sb_get_active_user();

    $no_check = true;
    foreach ($security as $key => $value) {
        if (in_array($function, $security[$key])) {
            $no_check = false;
            break;
        }
    }
    if ($no_check) {
        return true;
    }

    if (in_array($function, $security['login']) && $user != false) {
        return true;
    }
    if ($user != false && isset($user['user_type'])) {
        $user_type = $user['user_type'];
        $current_user_id = sb_isset($user, 'id', -2);

        if (in_array($function, $security['user']) && (sb_is_agent($user_type) || $user_id == $current_user_id)) {
            return true;
        }

        if (in_array($function, $security['agent']) && sb_is_agent($user_type)) {
            return true;
        }

        if (in_array($function, $security['admin']) && $user_type == 'admin') {
            return true;
        }

        return false;
    }
    return false;
}

?>