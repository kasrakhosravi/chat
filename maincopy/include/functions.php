<?php

define('SB_VERSION', '3.0.0');

if (!defined('SB_PATH')) {
    define('SB_PATH', dirname(__DIR__, 1));
}

require_once(SB_PATH . '/config.php');
global $SB_CONNECTION;
global $SB_SETTINGS;
global $SB_LOGIN;
global $SB_LANGUAGE;
global $SB_TRANSLATIONS;
const  SELECT_FROM_USERS = 'SELECT id, first_name, last_name, email, profile_image, user_type, creation_time, last_activity, token';

$sb_apps = ['dialogflow', 'slack', 'wordpress'];
for ($i = 0; $i < count($sb_apps); $i++) {
    $file = SB_PATH . '/apps/' . $sb_apps[$i] . '/functions.php';
    if (file_exists($file)) {
        require_once($file);
    }
}

function sb_db_connect() {
    global $SB_CONNECTION;
    if (SB_DB_NAME != '' && isset($SB_CONNECTION) && $SB_CONNECTION->ping()) {
        sb_db_init_settings();
        return true;
    }
    sb_db_init_settings();
    return true;
}

function sb_db_get($query, $single = true) {
    global $SB_CONNECTION;
    $status = sb_db_connect();
    $value = ($single ? '' : []);
    if ($status) {
        $result = $SB_CONNECTION->query($query);
        if ($result) {
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    if ($single) {
                        $value = $row;
                    } else {
                        array_push($value, $row);
                    }
                }
            }
        } else {
            return sb_db_error('sb_db_get');
        }
    } else {
        return $status;
    }
    return $value;
}

function sb_db_query($query, $return = false) {
    global $SB_CONNECTION;
    $status = sb_db_connect();
    if ($status) {
        $result = $SB_CONNECTION->query($query);
        if ($result) {
            if ($return) {
                if (isset($SB_CONNECTION->insert_id) && $SB_CONNECTION->insert_id > 0) {
                    return $SB_CONNECTION->insert_id;
                } else {
                    return sb_db_error('sb_db_query');
                }
            } else {
                return true;
            }
        } else {
            return sb_db_error('sb_db_query');
        }
    } else {
        return $status;
    }
}

function sb_db_encode($string) {
    global $SB_CONNECTION;
    sb_db_connect();
    return $SB_CONNECTION->real_escape_string($string);
}

function sb_db_sanatize($value) {
    return str_replace('"', '\"', $value);
}

function sb_json_enconde($array) {
    return sb_db_encode(str_replace(['"false"', '"true"'], ['false', 'true'], json_encode($array)));
}

function sb_db_error($function) {
    global $SB_CONNECTION;
    $message = new SBError($SB_CONNECTION->error, $function);
    return $message;
}

function sb_db_check_connection($name = false, $user = false, $password = false, $host = false, $port = false) {
    global $SB_CONNECTION;
    $response = true;
    if ($name === false && defined('SB_DB_NAME')) {
        $name = SB_DB_NAME;
        $user = SB_DB_USER;
        $password = SB_DB_PASSWORD;
        $host = SB_DB_HOST;
        $port = defined('SB_DB_PORT') && SB_DB_PORT != '' ? intval(SB_DB_PORT) : false;
    }
    if ($name === false || $name == '') {
        return 'installation';
    }
    try {
        set_error_handler(function() {}, E_ALL);
    	$SB_CONNECTION = new mysqli($host, $user, $password, $name, $port === false ? ini_get('mysqli.default_port') : intval($port));
        sb_db_init_settings();
    }
    catch (Exception $e) {
        $response = $e->getMessage();
    }
    if ($SB_CONNECTION->connect_error) {
        $response = $SB_CONNECTION->connect_error;
    }
    restore_error_handler();
    return $response;
}

function sb_db_init_settings() {
    global $SB_CONNECTION;
    $SB_CONNECTION->set_charset('utf8mb4');
    $SB_CONNECTION->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
}

class SBError {
    public $error;
    function __construct($message, $function = '') {
        $this->error = ['message' => $message, 'function' => $function];
    }

    function message() {
        return 'Support Board Error [' . $this->error['function'] . ']: ' . $this->error['message'];
    }

    public function __toString() {
        return $this->message();
    }
}

function sb_is_error($object) {
    return is_a($object, 'SBError');
}

function sb_is_agent($user = false) {
    if ($user === '') {
        return false;
    }
    $user = $user === false ? sb_get_active_user() : (is_string($user) ? ['user_type' => $user] : $user);
    if ($user == false) {
        return false;
    }
    return $user['user_type'] == 'agent' || $user['user_type'] == 'admin' || $user['user_type'] == 'bot';
}

function sb_login($email = '', $password = '', $user_id = '', $user_token = '') {
    global $SB_LOGIN;
    $valid_login = false;
    $result = null;
    if ($email != '' && $password != '') {

        $result = sb_db_get('SELECT id, profile_image, first_name, last_name, email, user_type, token, password FROM sb_users WHERE email = "' . $email . '" LIMIT 1');
        if (sb_is_error($result)) return $result;
        if (isset($result) && $result != '' && isset($result['password']) && isset($result['user_type']) && sb_password_verify($password, $result['password'])) {
            $valid_login = true;
        }
    } else if ($user_id != '' && $user_token != '') {

        $result = sb_db_get('SELECT id, profile_image, first_name, last_name, email, user_type, token FROM sb_users WHERE id = "' . $user_id . '" AND token = "' . $user_token . '" LIMIT 1');
        if (sb_is_error($result)) return $result;
        if (isset($result['user_type']) && isset($result['token'])) {
            $valid_login = true;
        }
    }
    if ($valid_login) {
        $settings =  ['id' => $result['id'], 'profile_image' => $result['profile_image'], 'first_name' => $result['first_name'], 'last_name' => $result['last_name'], 'email' => $result['email'], 'user_type' => $result['user_type'], 'token' => $result['token']];
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['sb-session'] = $settings;
        $SB_LOGIN = $settings;
        return [$settings, sb_encryption('encrypt', json_encode($settings))];
    }
    return false;
}

function sb_logout() {
    global $SB_LOGIN;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['sb-session']);
    $SB_LOGIN = null;
    return true;
}

function sb_get_active_user($storage = null, $db = false, $login_app = false) {
    global $SB_LOGIN;
    $return = false;
    if (isset($SB_LOGIN)) {
        $return = $SB_LOGIN;
    }
    if ($return === false && session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if ($return === false && isset($storage)) {
        $storage = json_decode(sb_encryption('decrypt', $storage), true);
        if ($storage !== false) {
            $return = $storage;
        }
    }
    if ($return === false && isset($_SESSION['sb-session']) && isset($_SESSION['sb-session']['id'])) {
        $return = $_SESSION['sb-session'];
    }
    if ($login_app !== false) {
        if ($return === false || !isset($return['email'])) {
            $return = sb_wp_get_active_user($login_app);
            if ($return !== false) {
                $return = $return[0];
            }
        } else {
            $user = json_decode(sb_encryption('decrypt', $login_app), true);
            if (isset($user['email']) && $user['email'] != $return['email']) {
                $return = sb_wp_get_active_user($login_app)[0];
            }
        }
    }
    if ($db && $return != false && isset($return['id'])) {
        if (intval(sb_db_get('SELECT COUNT(*) as count FROM sb_users WHERE id = "' . $return['id'] . '"')['count']) == 0) {
            $return = false;
        }
    }
    if ($return !== false) {
        $_SESSION['sb-session'] = $return;
        $SB_LOGIN = $return;
    }
    return $return;
}

function sb_password_verify($password, $hash) {
    $success = password_verify($password, $hash);
    if (!$success && defined('SB_WP')) {
        $wp_hasher = new SBPasswordHash(8, true);
        $success = $wp_hasher->CheckPassword($password, $hash);
    }
    return $success;
}

function sb_update_login($profile_image, $first_name, $last_name, $email) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['sb-session'])) {
        $settings = $_SESSION['sb-session'];
        $settings['profile_image'] = $profile_image;
        $settings['first_name'] = $first_name;
        $settings['last_name'] = $last_name;
        $settings['email'] = $email;
        $_SESSION['sb-session'] = $settings;
        return sb_encryption('encrypt', json_encode($settings));
    }
    return false;
}

function sb_js_global() {
    global $SB_LANGUAGE;
    $ajax_url = str_replace('//include', '/include', SB_URL . '/include/ajax.php');
    $code = '<script>';
    $code .= 'var SB_AJAX_URL = "' . $ajax_url . '";';
    $code .= 'var SB_URL = "' . SB_URL . '";';
    $code .= 'var SB_LANG = ' . ($SB_LANGUAGE == false ? 'false' : json_encode($SB_LANGUAGE)) . ';';
    $code .= '</script>';
    echo $code;
}

function sb_js_admin() {
    $code = '<script>';
    $code .= 'var SB_ADMIN_SETTINGS = { "desktop-notifications": "' . sb_get_setting('desktop-notifications') .'", "flash-notifications": "' . sb_get_setting('flash-notifications') .'", "auto-updates": "' . sb_get_setting('auto-updates') .'" };';
    if (isset($_SESSION['sb-session'])) {
        $code .= 'var SB_CURRENT_AGENT = {"id": "' . $_SESSION['sb-session']['id'] . '", "full_name": "' . $_SESSION['sb-session']['first_name'] . ' ' .  $_SESSION['sb-session']['last_name'] . '", "user_type": "' . $_SESSION['sb-session']['user_type'] . '", "profile_image": "' . $_SESSION['sb-session']['profile_image'] . '"};';
    } else {
        $code .= 'var SB_CURRENT_AGENT = {"id": "", "full_name": "", "user_type": "", "profile_image": "" };';
    }
    $code .= 'var SB_TRANSLATIONS = ' . json_encode(sb_get_current_translations()) . ';';
    $code .= 'var SB_VERSIONS = { sb: "' . SB_VERSION . '", dialogflow: "' . (defined('SB_DIALOGFLOW') ? SB_DIALOGFLOW : -1) . '", slack: "' . (defined('SB_SLACK') ? SB_SLACK : -1) . '"};';
    $code .= '</script>';
    echo $code;
}

function sb_add_user($settings = null, $settings_extra = null, $login_app = false) {
    $profile_image = sb_isset($settings, 'profile_image', [''])[0];
    $first_name = sb_isset($settings, 'first_name', [''])[0];
    $last_name = sb_isset($settings, 'last_name', [''])[0];
    $email = sb_isset($settings, 'email', [''])[0];
    $user_type = sb_isset($settings, 'user_type', [''])[0];
    $password = sb_isset($settings, 'password', [''])[0];

    if ($email != '' && intval(sb_db_get('SELECT COUNT(*) as count FROM sb_users WHERE email = "' . $email . '"')['count']) > 0) {
        return 'duplicate-email';
    }

    if ($profile_image == '') {
        $profile_image = SB_URL . '/media/user.svg';
    }
    if ($first_name == '') {
        $first_name = 'User';
        $last_name = '#' . rand(0, 99999);
    }
    if ($user_type == '') {
        $user_type = $email != '' ? 'user' : 'visitor';
    }
    if ($password != '') {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    if (defined('SB_WP') && sb_get_setting('wp-users-system') == 'wp' && $login_app !== false) {
        $settings = sb_wp_get_user($login_app);
        if ($settings !== false) {
            $first_name = $settings['first_name'];
            $last_name = $settings['last_name'];
            $password = $settings['user_pass'];
            $email = $settings['user_email'];
            $user_type = 'user';
        }
    }

    $now = gmdate('Y-m-d H:i:s');
    $token = bin2hex(openssl_random_pseudo_bytes(20));
    $query = 'INSERT INTO sb_users(first_name, last_name, password, email, profile_image, user_type, creation_time, token, last_activity) VALUES ("' . sb_db_sanatize($first_name) . '", "' . sb_db_sanatize($last_name) . '", "' . sb_db_sanatize($password) . '", ' . ($email == '' ? 'NULL' : '"' . $email . '"') . ', "' . sb_db_sanatize($profile_image) . '", "' . $user_type . '", "' . $now . '", "' . $token . '", "' . $now . '")';
    $result = sb_db_query($query, true);

    if (!sb_is_error($result) && is_int($result) && $result > 0 && isset($settings_extra)) {
        sb_add_new_user_extra($result, $settings_extra);
    }
    if (!sb_is_error($result) && $user_type == 'user' && !sb_is_agent()) {
        sb_user_autodata($result);
    }
    return $result;
}

function sb_add_new_user_extra($user_id, $settings) {
    $query = '';
    foreach ($settings as $key => $setting) {
        if (is_array($setting) && $setting[0] != '') {
            $query .= '("' . $user_id . '", "' . $key . '", "' . sb_db_sanatize($setting[1]) . '", "' . sb_db_sanatize($setting[0]) . '"),';
        }
    }
    if ($query != '') {
        $query = 'INSERT INTO sb_users_data(user_id, slug, name, value) VALUES ' . substr($query, 0, -1);
        return sb_db_query($query);
    }
    return false;
}

function sb_add_user_and_login($settings, $settings_extra, $login_app = false) {
    $response = sb_add_user($settings, $settings_extra, $login_app);
    if (is_int($response)) {
        $token = sb_db_get('SELECT token FROM sb_users WHERE id = "' . $response . '"');
        return sb_login('', '', $response, $token['token']);
    }
    return $response;
}

function sb_delete_user($user_id) {
    return sb_db_query('DELETE FROM sb_users WHERE id = "' . $user_id . '"');
}

function sb_delete_users($user_ids) {
    $query = '';
    for ($i = 0; $i < count($user_ids); $i++) {
        $query .= $user_ids[$i] . ',';
    }
    return sb_db_query('DELETE FROM sb_users WHERE id IN (' . substr($query, 0, -1) . ')');
}

function sb_update_user($user_id, $settings, $settings_extra = []) {
    $query = '';
    $profile_image = sb_isset($settings, 'profile_image', [''])[0];
    $first_name = sb_isset($settings, 'first_name', [''])[0];
    $last_name = sb_isset($settings, 'last_name', [''])[0];
    $email = sb_isset($settings, 'email', [''])[0];
    $user_type = sb_isset($settings, 'user_type', [''])[0];
    $password = isset($settings['password']) && $settings['password'][0] != '********' ? $settings['password'][0] : '';

    if ($email != '' && intval(sb_db_get('SELECT COUNT(*) as count FROM sb_users WHERE email = "' . $email . '" AND id <> ' . $user_id)['count']) > 0) {
        return 'duplicate-email';
    }

    if ($profile_image == '') {
        $profile_image = SB_URL . '/media/user.svg';
    }
    if ($first_name != '') {
        $query .= ', first_name = "' . sb_db_sanatize($first_name) . '"';
    }
    if ($last_name != '') {
        $query .= ', last_name = "' . sb_db_sanatize($last_name) . '"';
    }
    if ($password != '') {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $query .= ', password = "' . sb_db_sanatize($password) . '"';
    }
    if (!sb_is_agent($user_type)) {
        $user_type = $email != '' ? 'user' : (intval(sb_db_get('SELECT COUNT(*) as count FROM sb_users WHERE id = "' . $user_id . '"')['count']) > 0 ? 'lead' : 'visitor');
    }

    $query_final = 'UPDATE sb_users SET profile_image = "' . sb_db_sanatize($profile_image) . '", user_type = "' . $user_type . '", email = ' . (strlen($email) == 0 ? 'NULL' : '"' . sb_db_sanatize($email) . '"') . $query . ' WHERE id = "' . $user_id . '"';
    $result = sb_db_query($query_final);

    foreach ($settings_extra as $key => $setting) {
        if (is_array($setting)) {
            sb_db_query('REPLACE INTO sb_users_data SET name = "' . sb_db_sanatize($setting[1]) . '", value = "' . sb_db_sanatize($setting[0]) . '", slug = "' . $key . '", user_id = "' . $user_id . '"');
        }
    }
    sb_db_query('DELETE FROM sb_users_data WHERE user_id = "' . $user_id . '" AND value = "";');

    if (defined('SB_SLACK') && $first_name != '' && $last_name != '' && sb_get_setting('slack-active')) {
        sb_slack_rename_channel($user_id, $first_name . ' ' . $last_name);
    }
    return $result;
}

function sb_update_user_to_lead($user_id) {
    sb_user_autodata($user_id);
    return sb_db_query('UPDATE sb_users SET user_type = "lead" WHERE id = "' . $user_id . '"');
}

function sb_update_user_and_message($user_id, $settings, $settings_extra = [], $message_id = '', $message = '', $payload = false) {
    $result = sb_update_user($user_id, $settings, $settings_extra);
    if ($result === 'duplicate-email') {
        return 'duplicate-email';
    }
    if ($message_id != '' && $message != '') {
        $result = sb_update_message($user_id, $message_id, $message, false, $payload);
    }
    if ($result) {
        $user = sb_get_active_user();
        sb_login('', '', $user['id'], $user['token']);
        return true;
    }
    return $result;
}

function sb_get_users($exclude_id = -1, $sorting = ['creation_time' , 'DESC'], $user_types = [], $search = '', $pagination = false) {
    $query = '';
    $query_search = '';
    $count = count($user_types);
    if ($count) {
        for ($i = 0; $i < $count; $i++) {
            $query .= 'user_type = "' . $user_types[$i] . '" OR ';
        }
        $query = '(' . substr($query, 0, strlen($query) - 4) . ')';
    }
    if ($search != '') {
        $searched_users = sb_search_users($search);
        $count_search = count($searched_users);
        if ($count_search > 0) {
            for ($i = 0; $i < $count_search; $i++) {
                $query_search .= $searched_users[$i]['id'] . ',';
            }
            $query .= ($query != '' ? ' AND ' : '') . 'id IN (' . substr($query_search, 0, -1) . ')';
        }
    }
    if ($query != '') {
        $query = ' WHERE user_type <> "bot" AND ' . $query;
    } else {
        $query = ' WHERE user_type <> "bot"';
    }
    if ($pagination != false) {
        $pagination = ' LIMIT ' . $pagination[0] . ',' . $pagination[1];
    } else {
        $pagination = '';
    }
    $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users ' . $query . ' ORDER BY ' . $sorting[0] . ' ' . $sorting[1] . $pagination, false);
    if (isset($users) && is_array($users)) {
        return $users;
    } else {
        return new SBError('Users array not of type array or not set.', 'sb_get_users');
    }
}

function sb_search_users($search) {
    $search = trim(sb_db_sanatize($search));
    $query = '';
    if (strpos($search, ' ') > 0) {
        $search = explode(' ', $search);
    } else {
        $search = [$search];
    }
    for ($i = 0; $i < count($search); $i++) {
    	$query .= 'sb_users.first_name LIKE "%' . $search[$i] . '%" OR sb_users.last_name LIKE "%' . $search[$i] . '%" OR ';
    }

    $result = sb_db_get('SELECT sb_users.* FROM sb_users, sb_users_data WHERE sb_users.user_type <> "bot" AND ' . $query . ' sb_users.email LIKE "%' . $search[0] . '%" OR (sb_users.id = sb_users_data.user_id AND sb_users_data.value LIKE "%' . $search[0] . '%") GROUP BY sb_users.id;', false);
    if (isset($result) && is_array($result)) {
        return $result;
    } else {
        return new SBError('Conversation array not of type array or not set.', 'sb_search_users');
    }
}

function sb_get_online_users($exclude_id = -1, $sorting = 'creation_time') {
    $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users WHERE user_type <> "bot" AND last_activity > "' . gmdate('Y-m-d H:i:s', time() - 15) . '"' . ($exclude_id > 0 ? ' AND id <> "' . $exclude_id . '"' : '') . ' ORDER BY ' . $sorting . ' DESC', false);
    if (isset($users) && is_array($users)) {
        return $users;
    } else {
        return new SBError('Users array not of type array or not set.', 'sb_get_online_users');
    }
}

function sb_get_new_users($datetime) {
    $users = sb_db_get(SELECT_FROM_USERS . ' FROM sb_users WHERE  user_type <> "bot" AND creation_time > "' . $datetime . '" ORDER BY creation_time DESC', false);
    if (isset($users) && is_array($users)) {
        return $users;
    } else {
        return new SBError('Users array not of type array or not set.', 'sb_update_users');
    }
}

function sb_get_user($user_id, $extra = false) {
    $user = sb_db_get(SELECT_FROM_USERS . ', password FROM sb_users WHERE id = "' . $user_id . '"');
    if (isset($user) && is_array($user)) {
        if ($extra) {
            $user['details'] = sb_get_user_extra($user_id);
        }
        return $user;
    } else {
        return false;
    }
}

function sb_get_user_extra($user_id) {
    $details = sb_db_get('SELECT slug, name, value FROM sb_users_data WHERE user_id = "' . $user_id . '"', false);
    if (isset($details) && is_array($details)) {
        return $details;
    } else {
        return new SBError('User details array not of type array or not set.', 'sb_get_user_extra');
    }
}

function sb_get_conversations_users($conversations) {
    if (count($conversations) > 0) {
        $code = '(';
        for ($i = 0; $i < count($conversations); $i++) {
            $code .= $conversations[$i]['conversation_id'] . ',';
        }
        $code = substr($code, 0, -1) . ')';
        $result = sb_db_get('SELECT sb_users.id, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.id as conversation_id, sb_conversations.status_code FROM sb_users, sb_conversations WHERE sb_users.id = sb_conversations.user_id AND sb_conversations.id IN ' . $code, false);
        for ($i = 0; $i < count($conversations); $i++) {
            $conversation_id = $conversations[$i]['conversation_id'];
            for ($j = 0; $j < count($result); $j++) {
                if ($conversation_id == $result[$j]['conversation_id']) {
                    $conversations[$i]['first_name'] = $result[$j]['first_name'];
                    $conversations[$i]['last_name'] = $result[$j]['last_name'];
                    $conversations[$i]['profile_image'] = $result[$j]['profile_image'];
                    $conversations[$i]['user_id'] = $result[$j]['id'];
                    $conversations[$i]['conversation_status_code'] = $result[$j]['status_code'];
                    $conversations[$i]['user_type'] = $result[$j]['user_type'];
                    break;
                }
            }
        }
    }
    return $conversations;
}

function sb_get_conversations($pagination = 0, $status = 0) {
    $i = ($pagination == 0 ? 0 : 1);
    $status = $status == 0 ? 'AND sb_conversations.status_code <> 3 AND sb_conversations.status_code <> 4' : 'AND sb_conversations.status_code = ' . $status;
    $result = sb_db_get('SELECT sb_messages.*, sb_users.user_type as message_user_type FROM sb_messages, sb_users, sb_conversations WHERE sb_users.id = sb_messages.user_id ' . $status . ' AND sb_conversations.id = sb_messages.conversation_id AND sb_messages.creation_time IN (SELECT max(creation_time) latest_creation_time FROM sb_messages GROUP BY conversation_id) GROUP BY conversation_id ORDER BY sb_messages.creation_time DESC', false); // . LIMIT ' . ($pagination * 30 + $i) . ',' . ($pagination * 30 + 30)
    if (isset($result) && is_array($result)) {
        return sb_get_conversations_users($result);
    } else {
        return new SBError($result, 'sb_get_conversations');
    }
}

function sb_get_new_conversations($datetime) {
    $result = sb_db_get('SELECT m.*, u.user_type as message_user_type FROM sb_messages m, sb_users u, sb_conversations c WHERE m.creation_time IN (SELECT max(creation_time) latest_creation_time FROM sb_messages WHERE creation_time > "' . $datetime . '" GROUP BY conversation_id) AND u.id = m.user_id AND c.id = m.conversation_id AND c.status_code <> 3 AND c.status_code <> 4 ORDER BY m.creation_time DESC', false);
    if (isset($result) && is_array($result)) {
        if (count($result) > 0) {
            return sb_get_conversations_users($result);
        } else {
            return [];
        }
    } else {
        return new SBError('Messages array not of type array or not set.', 'sb_get_new_conversations');
    }
}

function sb_get_conversation($user_id, $id) {
    $messages = sb_db_get('SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type FROM sb_messages, sb_users, sb_conversations WHERE sb_messages.conversation_id = "' . $id . '" AND sb_conversations.user_id = "' . $user_id . '" AND sb_messages.conversation_id = sb_conversations.id AND sb_users.id = sb_messages.user_id ORDER BY sb_messages.creation_time ASC', false);
    if (isset($messages) && is_array($messages)) {
        $details = sb_db_get('SELECT sb_users.id as user_id, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.id, sb_conversations.title, sb_conversations.creation_time as conversation_time, sb_conversations.status_code as conversation_status_code FROM sb_users, sb_conversations WHERE sb_conversations.id = "' . $id . '" AND sb_users.id = "' . $user_id . '" AND sb_users.id = sb_conversations.user_id LIMIT 1');
        return ['messages' => $messages, 'details' => $details];
    } else {
        return new SBError('Conversation array not of type array or not set.', 'sb_get_conversation');
    }
}

function sb_get_new_messages($user_id, $conversation_id, $datetime) {
    $result = sb_db_get('SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type FROM sb_messages, sb_users, sb_conversations WHERE sb_messages.creation_time > "' . $datetime . '" AND sb_messages.conversation_id = "' . $conversation_id . '" AND sb_users.id = sb_messages.user_id AND sb_conversations.user_id = "' . $user_id . '" AND sb_messages.conversation_id = sb_conversations.id ORDER BY sb_messages.creation_time ASC', false);
    if (isset($result) && is_array($result)) {
        return $result;
    } else {
        return new SBError('Messages array not of type array or not set.', 'sb_get_new_messages');
    }
}

function sb_search_conversations($search) {
    $search = sb_db_sanatize($search);
    $result = sb_db_get('SELECT sb_messages.*, sb_users.user_type as message_user_type FROM sb_messages, sb_users WHERE sb_users.id = sb_messages.user_id AND (sb_messages.message LIKE "%' . $search . '%" OR sb_messages.attachments LIKE "%' . $search . '%" OR sb_users.first_name LIKE "%' . $search . '%" OR sb_users.last_name LIKE "%' . $search . '%" OR sb_users.email LIKE "%' . $search . '%") GROUP BY sb_messages.conversation_id ORDER BY sb_messages.creation_time DESC', false);
    if (isset($result) && is_array($result)) {
        return sb_get_conversations_users($result);
    } else {
        return new SBError('Conversation array not of type array or not set.', 'sb_search_conversations');
    }
}

function sb_new_conversation($user_id, $status_code = 0, $title = '') {
    $conversation_id = sb_db_query('INSERT INTO sb_conversations(user_id, title, status_code, creation_time) VALUES ("' . $user_id . '", "' . $title . '", ' . $status_code . ', "' . gmdate('Y-m-d H:i:s') . '")', true);
    if (is_int($conversation_id)) {
        return sb_get_conversation($user_id, $conversation_id);
    } else if (sb_is_error($conversation_id) && sb_db_get('SELECT count(*) as count FROM sb_users WHERE id = "' . $user_id . '"')['count'] == 0) {
        return 'user-not-found';
    }
    return $conversation_id;
}

function sb_get_user_conversations($user_id, $exclude_id = -1) {
    $exclude = $exclude_id != -1 ? ' AND sb_messages.conversation_id <> "' . $exclude_id . '"' : '';
    return sb_db_get('SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type, sb_conversations.status_code AS conversation_status_code FROM sb_messages, sb_users, sb_conversations WHERE sb_users.id = sb_messages.user_id AND sb_messages.conversation_id = sb_conversations.id AND sb_messages.creation_time IN (SELECT max(sb_messages.creation_time) AS latest_creation_time FROM sb_messages, sb_conversations WHERE sb_messages.conversation_id = sb_conversations.id AND sb_conversations.user_id = "' . $user_id . '"' . $exclude . ' GROUP BY conversation_id) GROUP BY conversation_id ORDER BY creation_time DESC', false);
}

function sb_get_new_user_conversations($user_id, $datetime) {
    return sb_db_get('SELECT sb_messages.*, sb_users.first_name, sb_users.last_name, sb_users.profile_image, sb_users.user_type FROM sb_messages, sb_users WHERE sb_users.id = sb_messages.user_id AND sb_messages.creation_time IN (SELECT max(sb_messages.creation_time) AS latest_creation_time FROM sb_messages, sb_conversations WHERE sb_messages.creation_time > "' . $datetime . '" AND sb_messages.conversation_id = sb_conversations.id AND sb_conversations.user_id = "' . $user_id . '" GROUP BY conversation_id) GROUP BY conversation_id ORDER BY creation_time DESC', false);
}

function sb_update_conversation_status($conversation_id, $status) {
    if (in_array($status, [0, 1, 2, 3, 4])) {
        return sb_db_query('UPDATE sb_conversations SET status_code = ' . $status . ' WHERE id = '. $conversation_id);
    } else {
        if ($status == 5 && sb_is_agent()) {
            return sb_db_query('DELETE FROM sb_conversations WHERE status_code = 4');
        } else {
            return new SBError('Invalid status code "' . $status . '"', 'sb_update_conversation_status');
        }
    }
}

function sb_send_message($user_id = -1, $conversation_id, $message = '', $attachments = [], $conversation_status = -1, $payload = false) {
    if ($user_id == -1) {
        $active_user = sb_get_active_user();
        if ($active_user != false && isset($active_user['id'])) {
            $user_id = $active_user['id'];
        }
    }
    if ($user_id != -1) {
        $attachments_json = '';
        $security = "true";
        if (count($attachments) > 0) {
            $attachments_json = '[';
            for ($i = 0; $i < count($attachments); $i++) {
            	$attachments_json .= '[\"' . sb_db_sanatize($attachments[$i][0]) . '\", \"' . sb_db_sanatize($attachments[$i][1]) . '\"],';
            }
            $attachments_json = substr($attachments_json, 0, -1) . ']';
        }
        if ($security == "true") {
            $check_id = sb_db_get('SELECT user_id FROM sb_conversations WHERE id = "' . $conversation_id . '" LIMIT 1');
            if (isset($check_id['user_id']) && $check_id['user_id'] == sb_get_active_user()['id']) {
                $security = true;
            }
        }
        if ($security == "true") {
            $response = sb_db_query('INSERT INTO sb_messages(user_id, message, status_code, creation_time, attachments, conversation_id, payload) VALUES ("' . $user_id . '", "' . sb_db_sanatize(htmlentities($message, ENT_NOQUOTES)) . '", 0, "' . gmdate('Y-m-d H:i:s') . '", "' . $attachments_json . '", "' . $conversation_id . '", "' . ($payload != false ? sb_json_enconde($payload) : '') . '")');
            if ($conversation_status != -1) {
                sb_db_query('UPDATE sb_conversations SET status_code = ' . $conversation_status . ' WHERE id = "' . $conversation_id . '" LIMIT 1');
            }
            return $response;
        }
        return new SBError('Security Error.', 'sb_send_message');
    } else {
        return new SBError('Active user not found', 'sb_send_message');
    }
}

function sb_update_message($user_id, $message_id, $message = false, $attachments = false, $payload = false) {
    if ($message === false && $payload === false) return false;
    $security = "true";
    if ($security == "true") {
        $check_id = sb_db_get('SELECT user_id FROM sb_conversations WHERE id = (SELECT conversation_id FROM sb_messages WHERE id = "' . $message_id . '" LIMIT 1) LIMIT 1');
        if (isset($check_id['user_id']) && $check_id['user_id'] == sb_get_active_user()['id']) {
            $security = true;
        }
    }
    if ($security == "true") {
        return sb_db_query('UPDATE sb_messages SET ' . ($message !== false ? 'message = "' . sb_db_sanatize($message) . '",' : '') . ' creation_time = "' . gmdate('Y-m-d H:i:s') . '"' . ($payload !== false ? ', payload = "' . sb_json_enconde($payload) . '"' : '') . ($attachments !== false ? ', attachments = "' . sb_json_enconde($attachments) . '"' : '') . ' WHERE id = "' . $message_id . '" LIMIT 1');
    }
    return new SBError('Security Error.', 'sb_update_message');
}

function sb_delete_message($user_id, $message_id) {
    return sb_db_query('DELETE FROM sb_messages WHERE id = "' . $message_id . '" AND conversation_id IN (SELECT id FROM sb_conversations WHERE user_id = "' . $user_id . '") LIMIT 1');
}

function sb_update_users_last_activity($user_id = -1, $return_user_id = -1, $check_slack = false) {
    $result = $user_id != -1 ? sb_db_query('UPDATE sb_users SET last_activity = "' . gmdate('Y-m-d H:i:s') . '" WHERE id = "' . $user_id . '"') : false;
    if ($return_user_id != -1) {
        $last_activity = sb_db_get('SELECT last_activity FROM sb_users WHERE id = "' . $return_user_id . '" LIMIT 1');
        if (!isset($last_activity['last_activity'])) {
            return 'offline';
        }
        if (strtotime($last_activity['last_activity']) > strtotime(gmdate('Y-m-d H:i:s', time() - 15))) {
            return 'online';
        } else {
            return defined('SB_SLACK') && $check_slack ? sb_slack_agent_online($return_user_id) : 'offline';
        }
    }
    return $result;
}

function sb_is_typing($user_id, $conversation_id) {
    $typing = sb_db_get('SELECT COUNT(*) as typing FROM sb_users WHERE id = "' . $user_id . '" AND typing = "' . $conversation_id . '"');
    return $typing['typing'] != 0;
}

function sb_set_typing($user_id, $conversation_id) {
    return sb_db_query('UPDATE sb_users SET typing = ' . $conversation_id . ' WHERE id = "' . $user_id . '"');
}

function sb_($string) {
    global $SB_TRANSLATIONS;
    if (!isset($SB_TRANSLATIONS)) {
        sb_init_translations();
    }
    if ($SB_TRANSLATIONS != false && isset($SB_TRANSLATIONS[$string]) && $SB_TRANSLATIONS[$string] != '') {
        return $SB_TRANSLATIONS[$string];
    }
    return $string;
}

function sb_e($string) {
    echo sb_($string);
}

function sb_init_translations() {
    global $SB_TRANSLATIONS;
    global $SB_LANGUAGE;

    if (isset($SB_LANGUAGE) && $SB_LANGUAGE != false) {
        $path = SB_PATH . '/resources/languages/' . $SB_LANGUAGE[1] . '/' . $SB_LANGUAGE[0] . '.json';
        if (file_exists($path)) {
            $SB_TRANSLATIONS = json_decode(file_get_contents($path), true);
        }  else {
            $SB_TRANSLATIONS = false;
        }
    } else if (!isset($SB_LANGUAGE)) {
        $language = isset($_GET['lang']) ? $_GET['lang'] : strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        if ($language == '' || $language == 'en') {
            $SB_TRANSLATIONS = false;
        } else {
            $active_user = sb_get_active_user();
            $area = 'front';
            if ($active_user == false) {
                if ((isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'supportboard/admin'))) {
                    $area = 'admin';
                }
            } else if (sb_is_agent($active_user['user_type'])) {
                $area = 'admin';
            }
            if ($area == 'admin' && !sb_get_setting('admin-auto-translations')) {
                $SB_TRANSLATIONS = false;
            } else if ($area == 'front' && !isset($_GET['lang']) && !sb_get_setting('front-auto-translations')) {
                $SB_TRANSLATIONS = false;
            } else {
                $path = SB_PATH . '/resources/languages/' . $area . '/' . $language . '.json';
                if (file_exists($path)) {
                    $SB_TRANSLATIONS = json_decode(file_get_contents($path), true);
                    $SB_LANGUAGE = [$language, $area];
                }  else {
                    $SB_TRANSLATIONS = false;
                }
            }
        }
        if ($SB_TRANSLATIONS == false) {
            $SB_LANGUAGE = false;
        }
    } else {
        $SB_TRANSLATIONS = false;
    }
}

function sb_get_current_translations() {
    global $SB_TRANSLATIONS;
    if (!isset($SB_TRANSLATIONS)) {
        sb_init_translations();
    }
    return $SB_TRANSLATIONS;
}

function sb_get_translations($is_user = false) {
    $translations = [];
    if ($is_user && !file_exists(SB_PATH . '/uploads/languages')) {
        return [];
    }
    $path = $is_user ? '/uploads' : '/resources';
    $language_codes = json_decode(file_get_contents(SB_PATH . '/resources/languages/language-codes.json'), true);
    $front_files = scandir(SB_PATH . $path . '/languages/front');
    $admin_files = scandir(SB_PATH . $path . '/languages/admin');
    for ($i = 0; $i < count($front_files); $i++)  {
        $code = substr($front_files[$i], 0, -5);
        if (isset($language_codes[$code])) {
            $translations[$code] = ['name' => $language_codes[$code], 'front' => json_decode(file_get_contents(SB_PATH . $path . '/languages/front/' . $front_files[$i]), true)];
        }
    }
    for ($i = 0; $i < count($admin_files); $i++)  {
        $code = substr($admin_files[$i], 0, -5);
        if (isset($translations[$code])) {
            $translations[$code]['admin'] = json_decode(file_get_contents(SB_PATH . $path . '/languages/admin/' . $admin_files[$i]), true);
        }
    }
    return $translations;
}

function sb_save_translations($translations) {
    if (!file_exists(SB_PATH . '/uploads/languages')) {
        mkdir(SB_PATH . '/uploads/languages', 0777, true);
        mkdir(SB_PATH . '/uploads/languages/front', 0777, true);
        mkdir(SB_PATH . '/uploads/languages/admin', 0777, true);
    }
    foreach ($translations as $key => $translation) {
        foreach ($translation as $key_area => $translations_list) {
            $json = json_encode($translations_list);
            if ($json != false) {
                $paths = ['resources', 'uploads'];
                for ($i = 0; $i < 2; $i++)  {
                    sb_file(SB_PATH . '/' . $paths[$i] . '/languages/' . $key_area . '/' . $key . '.json', $json);
                }
            }
        }
    }
}

function sb_restore_user_translations() {
    $translations_all = sb_get_translations();
    $translations_user = sb_get_translations(true);
    foreach ($translations_user as $key => $translations) {
        $paths = ['front', 'admin'];
        for ($i = 0; $i < 2; $i++)  {
            if (isset($translations_all[$key]) && isset($translations_all[$key][$paths[$i]])) {
                foreach ($translations_all[$key][$paths[$i]] as $key_two => $translation) {
                    if (!isset($translations[$paths[$i]][$key_two])) {
                        $translations[$paths[$i]][$key_two] = $translations_all[$key][$paths[$i]][$key_two];
                    }
                }
            }
            sb_file(SB_PATH . '/resources/languages/' . $paths[$i] . '/'. $key . '.json', json_encode($translations[$paths[$i]]));
        }
    }
}

function sb_populate_settings($category, $settings) {
    if (!isset($settings) && file_exists(SB_PATH . '/resources/json/settings.json')) {
        $settings = json_decode(file_get_contents(SB_PATH . '/resources/json/settings.json'), true);
    }
    $settings = $settings[$category];
    $code = '';
    for ($i = 0; $i < count($settings); $i++) {
        $code .= sb_get_setting_code($settings[$i]);
    }
    echo $code;
}

function sb_populate_app_settings($app_name) {
    $file = SB_PATH . '/apps/' . $app_name . '/settings.json';
    $settings = [$app_name => []];
    if (file_exists($file)) {
        $settings[$app_name] = json_decode(file_get_contents($file), true);
    }
    sb_populate_settings($app_name, $settings);
}

function sb_get_setting_code($array) {
    if (isset($array)) {
        $id = $array['id'];
        $type = $array['type'];
        $content = '<div id="' . $id . '" data-type="' . $type . '"' . (isset($array['setting']) ? ' data-setting="' . $array['setting'] . '"' : '') .' class="sb-setting sb-type-' . $type . '"><div class="content"><h2>' . sb_($array["title"]) . '</h2><p>' . sb_($array["content"]) . '</p></div><div class="input">';
        switch ($type) {
            case 'color':
                $content .= '<input type="text"><i class="sb-close sb-icon-close"></i>';
                break;
            case 'text':
                $content .= '<input type="text">';
                break;
            case 'password':
                $content .= '<input type="password">';
                break;
            case 'textarea':
                $content .= '<textarea></textarea>';
                break;
            case 'select':
                $content .= '<select>';
                for ($i = 0; $i < count($array['value']); $i++) {
                    $content .= '<option value="' . $array['value'][$i][0] . '">' . $array['value'][$i][1] . '</option>';
                }
                $content .= '</select>';
                break;
            case 'checkbox':
                $content .= '<input type="checkbox">';
                break;
            case 'radio':
                for ($i = 0; $i < count($array['value']); $i++) {
                    $content .= '<div><input type="radio" name="' . $id . '" value="' . strtolower(str_replace(' ', '-', $array['value'][$i])) . '"><label>' . $array["value"][$i] . '</label></div>';
                }
                break;
            case 'number':
                $content .= '<input type="number">' . (key_exists('unit', $array) ? '<label>' . $array['unit'] . '</label>' : '');
                break;
            case 'upload':
                $content .= '<input type="url"><button type="button">' . sb_('Choose file') . '</button>';
                break;
            case 'upload-image':
                $content .= '<div class="image"' . (isset($array['background-size']) ? ' style="background-size: ' . $array['background-size'] . '"' : '')  . '><i class="sb-icon-close"></i></div>';
                break;
            case 'input-button':
                $content .= '<input type="text"><a class="sb-btn">' . sb_($array['button-text']) . '</a>';
                break;
            case 'button':
                $content .= '<a class="sb-btn" target="_blank" href="' . sb_($array['button-url']) . '">' . sb_($array['button-text']) . '</a>';
                break;
            case 'multi-input':
                for ($i = 0; $i < count($array['value']); $i++) {
                    $type = $array['value'][$i]['type'];
                    $content .= '<div id="' . $array['value'][$i]['id'] . '" data-type="' . $type . '" class="multi-input-' . $array['value'][$i]['type'] . '"><label>' . $array['value'][$i]['title'] . '</label>';
                    switch ($type) {
                        case 'text':
                            $content .= '<input type="text">';
                            break;
                        case 'password':
                            $content .= '<input type="password">';
                            break;
                        case 'number':
                            $content .= '<input type="number">';
                            break;
                        case 'textarea':
                            $content .= '<textarea></textarea>';
                            break;
                        case 'upload':
                            $content .= '<input type="url"><button type="button">' . sb_('Choose file') . '</button>';
                            break;
                        case 'upload-image':
                            $content .= '<div class="image"><i class="sb-icon-close"></i></div>';
                            break;
                        case 'checkbox':
                            $content .= '<input type="checkbox">';
                            break;
                    }
                    $content .= '</div>';
                }
                break;
            case 'range':
                $range = (key_exists('range', $array) ? $array['range'] : array(0, 100));
                $unit = (key_exists('unit', $array) ? '<label>' . $array['unit'] . '</label>' : '');
                $content .= '<label class="range-value">' . $range[0] . '</label><input type="range" min="' . $range[0] .'" max="' . $range[1] .'" value="' . $range[0] . '" />' . $unit;
                break;
            case 'select-image':
                $html = '<div class="thumbs" data-columns="' . (key_exists('columns', $array) ? $array['columns'] : '3') . '">';
                for ($i = 0; $i < count($array['value']); $i++) {
                    $name = $array['value'][$i][0];
                    $html .= '<div data-id="' . strtolower(str_replace(' ', '-', $name)) . '"><span><img src="' . $array['value'][$i][1] . '"></span><p>' . $name . '</p></div>';
                }
                $content .= $html . '</div>';
                break;
            case 'repeater':
                $content .= '<div class="sb-repeater"><div class="repeater-item">';
                for ($i = 0; $i < count($array['items']); $i++) {
                    $item = $array['items'][$i];
                    $content .= '<div><label>' . sb_($item['name']) . '</label>';
                    switch ($item['type']) {
                        case 'text':
                            $content .= '<input data-id="' . $item['id'] . '" type="text">';
                            break;
                        case 'textarea':
                            $content .= '<textarea data-id="' . $item['id'] . '"></textarea>';
                            break;
                    }
                    $content .= '</div>';
                }
                $content .= '<i class="sb-icon-close"></i></div></div><a class="sb-btn sb-repeater-add">' . sb_('Add new item') . '</a>';
                break;
            case 'timetable':
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $hours = [['', ''], ['00:00', '12:00 am'], ['00:30', '12:30 am'], ['01:00', '1:00 am'], ['01:30', '1:30 am'], ['02:00', '2:00 am'], ['02:30', '2:30 am'], ['03:00', '3:00 am'], ['03:30', '3:30 am'], ['04:00', '4:00 am'], ['04:30', '4:30 am'], ['05:00', '5:00 am'], ['05:30', '5:30 am'], ['06:00', '6:00 am'], ['06:30', '6:30 am'], ['07:00', '7:00 am'], ['07:30', '7:30 am'], ['08:00', '8:00 am'], ['08:30', '8:30 am'], ['09:00', '9:00 am'], ['09:30', '9:30 am'], ['10:00', '10:00 am'], ['10:30', '10:30 am'], ['11:00', '11:00 am'], ['11:30', '11:30 am'], ['12:00', '12:00 pm'], ['12:30', '12:30 pm'], ['13:00', '1:00 pm'], ['13:30', '1:30 pm'], ['14:00', '2:00 pm'], ['14:30', '2:30 pm'], ['15:00', '3:00 pm'], ['15:30', '3:30 pm'], ['16:00', '4:00 pm'], ['16:30', '4:30 pm'], ['17:00', '5:00 pm'], ['17:30', '5:30 pm'], ['18:00', '6:00 pm'], ['18:30', '6:30 pm'], ['19:00', '7:00 pm'], ['19:30', '7:30 pm'], ['20:00', '8:00 pm'], ['20:30', '8:30 pm'], ['21:00', '9:00 pm'], ['21:30', '9:30 pm'], ['22:00', '10:00 pm'], ['22:30', '10:30 pm'], ['23:00', '11:00 pm'], ['23:30', '11:30 pm'], ['closed', sb_('Closed')]];
                $select = '<div class="sb-custom-select">';
                for ($i = 0; $i < count($hours); $i++) {
                    $select .= '<span data-value="' . $hours[$i][0] . '">' . $hours[$i][1] . '</span>';
                }
                $content .= '<div class="sb-timetable">';
                for ($i = 0; $i < 7; $i++) {
                    $content .= '<div data-day="' . strtolower($days[$i]) . '"><label>' . sb_($days[$i]) . '</label><div><div></div><span>' . sb_('To') . '</span><div></div><span>' . sb_('And') . '</span><div></div><span>' . sb_('To') . '</span><div></div></div></div>';
                }
                $content .= $select . '</div></div>';
                break;
        }
        return $content . '</div></div>';
    }
    return '';
}

function sb_save_settings($settings, $external_settings = []) {
    if (isset($settings)) {
        global $SB_SETTINGS;
        $settings_encoded = sb_json_enconde($settings);
        if (isset($settings_encoded) && is_string($settings_encoded)) {

            // Save main settings
            $query = 'INSERT INTO sb_settings(name, value) VALUES (\'settings\', \'' . $settings_encoded . '\') ON DUPLICATE KEY UPDATE value = \'' . $settings_encoded . '\'';
            $result = sb_db_query($query);
            if (sb_is_error($result)) {
                return $result;
            }

            // Save external settings
            foreach ($external_settings as $key => $value) {
                sb_save_external_setting($key, $value);
            }

            // Update bot
            sb_update_bot($settings['bot-name'][0], $settings['bot-image'][0]);

            $SB_SETTINGS = $settings;
            return true;
        } else {
            return new SBError('json_encode or real_escape_string error.', 'sb_save_settings');
        }
    } else {
        return new SBError('$_POST[settings] not found.', 'sb_save_settings');
    }
}

function sb_save_external_setting($name, $value) {
    $settings_encoded = sb_json_enconde($value);
    sb_db_query('INSERT INTO sb_settings(name, value) VALUES (\'' . $name . '\', \'' . $settings_encoded . '\') ON DUPLICATE KEY UPDATE value = \'' . $settings_encoded . '\'');
}

function sb_get_settings() {
    global $SB_SETTINGS;
    if (isset($SB_SETTINGS)) {
        return $SB_SETTINGS;
        
    } else {
        $value = sb_db_get('SELECT value FROM sb_settings WHERE name="settings" LIMIT 1');
        if ($value != '' && !is_a($value, 'SBError') && is_string($value['value'])) {
            $value = json_decode($value['value'], true);
            $SB_SETTINGS = $value;
            return $value;
        }
        return [];
    }
}

function sb_get_all_settings() {
    return array_merge(sb_get_settings(), sb_get_external_settings());
}

function sb_get_setting($id) {
    $settings = sb_get_settings();
    if (!sb_is_error($settings)) {
        if (isset($settings[$id])) {
            $setting = $settings[$id][0];
            if (is_array($setting) && !isset($setting[0])) {
                $settings_result = [];
                foreach ($setting as $key => $value) {
                    $settings_result[$key] = $value[0];
                }
                return $settings_result;
            } else {
                return $setting;
            }
        } else {
            return false;
        }
    } else {
        return $settings;
    }
}

function sb_get_external_settings($name = '') {
    if ($name == '') {
        $name = 'name="emails" || name="rich-messages"';
    } else {
        $name = 'name="' . $name . '"';
    }
    $result = sb_db_get('SELECT value FROM sb_settings WHERE ' . $name, false);
    $settings = [];
    if (!is_array($result)) {
        return $result;
    }
    if (count($result) == 1) {
        return json_decode($result[0]['value'], true);
    }
    for ($i = 0; $i < count($result); $i++) {
        $settings = array_merge($settings, json_decode($result[$i]['value'], true));
    }
    return $settings;
}

function sb_get_front_settings() {
    global $SB_LANGUAGE;
    $settings = sb_get_settings();
    $bot_id = sb_isset(sb_db_get('SELECT id FROM sb_users WHERE user_type = "bot" LIMIT 1'), 'id');
    if ($bot_id == false) {
        $bot_id = sb_update_bot();
    }
    return [
        'registration-required' => sb_isset($settings, 'registration-required', [false])[0],
        'visitors-registration' => sb_isset($settings, 'visitors-registration', [false])[0],
        'privacy' => sb_isset($settings, 'privacy', [['privacy-active' => [false]]])[0]['privacy-active'][0],
        'popup' => sb_isset($settings, 'popup-message', [['popup-active' => [false]]])[0]['popup-active'][0],
        'welcome' => sb_isset($settings, 'welcome-message', [['welcome-active' => [false]]])[0]['welcome-active'][0],
        'follow' => sb_isset($settings, 'follow-message', [['follow-active' => [false]]])[0]['follow-active'][0],
        'chat-manual-init' => sb_isset($settings, 'chat-manual-init', [false])[0],
        'chat-login-init' => sb_isset($settings, 'chat-login-init', [false])[0],
        'chat-sound' => sb_isset($settings, 'chat-sound', [false])[0],
        'header-name' => sb_isset($settings, 'header-name', [''])[0],
        'desktop-notifications' => sb_isset($settings, 'desktop-notifications', [false])[0],
        'flash-notifications' => sb_isset($settings, 'flash-notifications', [false])[0],
        'bot-id' => $bot_id,
        'bot-name' => sb_isset($settings, 'bot-name', [''])[0],
        'bot-image' => sb_isset($settings, 'bot-image', [''])[0],
        'bot-delay' => sb_isset($settings, 'dialogflow-bot-delay', [2000])[0] == '' ? 2000 : sb_isset($settings, 'dialogflow-bot-delay'),
        'bot-office-hours'=> sb_isset($settings, 'dialogflow-timetable', [false])[0],
        'dialogflow-active' => defined('SB_DIALOGFLOW') ? sb_isset($settings, 'dialogflow-active', [false])[0] : false,
        'slack-active' => defined('SB_SLACK') ? sb_isset($settings, 'slack-active', [false])[0] : false,
        'rich-messages' => sb_get_rich_messages_ids(),
        'display-users-thumb' => sb_isset($settings, 'display-users-thumb', [false])[0],
        'hide-agents-thumb' => sb_isset($settings, 'hide-agents-thumb', [false])[0],
        'notify-user-email' => sb_isset($settings, 'notify-user-email', [false])[0],
        'notify-agent-email' => sb_isset($settings, 'notify-agent-email', [false])[0],
        'translations' => sb_get_current_translations(),
        'auto-open' => sb_isset($settings, 'auto-open', [false])[0],
        'office-hours' => sb_office_hours(),
        'disable-office-hours' => sb_isset($settings, 'chat-timetable-disable', [false])[0],
        'timetable' => sb_isset($settings, 'chat-timetable', [['chat-timetable-active' => [false]]])[0]['chat-timetable-active'][0],
        'articles' => sb_isset($settings, 'articles-active', [false])[0],
        'articles-title' => sb_isset($settings, 'articles-title', [''])[0],
        'init-dashboard' => sb_isset($settings, 'init-dashboard', [false])[0],
        'wp' => defined('SB_WP'),
        'wp-users-system' => sb_isset($settings, 'wp-users-system', ['sb'])[0],
        'wp-show-ids' => defined('SB_WP') ? json_decode('[' . str_replace(' ', '', sb_isset($settings, 'wp-show-ids', [''])[0] . ']'), true) : [],
    ];
}

function sb_get_block_setting($value) {
    switch ($value)  {
    	case 'privacy':
            $setting = sb_get_setting('privacy');
            return ['title' => $setting['privacy-title'], 'message' => $setting['privacy-msg'], 'decline' => $setting['privacy-msg-decline'], 'link' => $setting['privacy-link'], 'btn-approve' => $setting['privacy-btn-approve'], 'btn-decline' => $setting['privacy-btn-decline']];
        case 'popup':
            $setting = sb_get_setting('popup-message');
            return ['title' => $setting['popup-title'], 'message' => $setting['popup-msg'], 'image' => $setting['popup-image']];
        case 'welcome':
            $setting = sb_get_setting('welcome-message');
            return ['message' => $setting['welcome-msg'], 'open' => $setting['welcome-open'], 'sound' => $setting['welcome-sound']];
        case 'follow':
            $setting = sb_get_setting('follow-message');
            return ['title' => $setting['follow-title'], 'message' => $setting['follow-msg'], 'success' => $setting['follow-success'], 'placeholder' => $setting['follow-placeholder']];
    }
    return false;
}

function sb_isset($array, $key, $default = false) {
    if (sb_is_error($array)) return $array;
    return isset($array[$key]) && $array[$key] !== '' ? $array[$key] : $default;
}

function sb_office_hours() {
    $settings = sb_get_settings();
    $timetable = sb_isset($settings, 'timetable', [[]])[0];
    $today = strtolower(gmdate('l'));
    if (isset($timetable[$today]) && $timetable[$today][0][0] != '') {
        $now_hours = gmdate('H');
        $now_minutes = gmdate('i');
        $status = false;
        $offset = sb_get_setting('timetable-utc');
        if ($offset === false) {
            $offset = 0;
        } else {
            $offset = intval($offset);
        }
        for ($i = 0; $i < 3; $i+=2){
            if ($timetable[$today][$i][0] != '' && $timetable[$today][$i][0] != 'closed' && $timetable[$today][$i + 1][0] != '') {
                $hours_start = intval(explode(':', $timetable[$today][$i][0])[0]) - $offset;
                $minutes_start = intval(explode(':',$timetable[$today][$i][0])[1]);
                $hours_end = intval(explode(':', $timetable[$today][$i + 1][0])[0]) - $offset;
                $minutes_end = intval(explode(':', $timetable[$today][$i + 1][0])[1]);
                if (($now_hours > $hours_start || ($now_hours == $hours_start && $now_minutes >= $minutes_start)) && ($now_hours < $hours_end || ($now_hours == $hours_end && $now_minutes <= $minutes_end))) {
                    $status = true;
                    break;
                }
            }
        }
        return $status;
    }
    return true;
}

function sb_encryption($action, $string) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'supportboard';
    $secret_iv = 'supportboard_iv';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt'){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

/*
 * -----------------------------------------------------------
 * # STRING SLUG
 * -----------------------------------------------------------
 *
 * Convert a string to slug and inverse
 *
 */

function sb_string_slug($string, $operation = 'slug') {
    $string = trim($string);
    if ($operation == 'slug') {
        return strtolower(str_replace(' ', '-', $string));
    }
    if ($operation == 'string') {
        return ucfirst(strtolower(str_replace('-', ' ', $string)));
    }
    return $string;
}

function sb_curl($url, $post_fields, $header) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_USERAGENT => 'Support Board Agent',
           CURLOPT_POST => true,
           CURLOPT_POSTFIELDS => is_string($post_fields) ? $post_fields : http_build_query($post_fields),
           CURLOPT_CONNECTTIMEOUT => 5,
           CURLOPT_HTTPHEADER => $header
    ));
    $response = curl_exec($ch);
    if (curl_errno($ch) > 0) {
        curl_close($ch);
        return $response;
    }
    curl_close($ch);
    return json_decode($response, true);
}

function sb_download($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Support Board Agent');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function sb_get_rich_messages_ids() {
    $result = sb_get_external_settings('rich-messages');
    $ids = ['email' , 'registration' , 'login', 'timetable', 'articles'];
    if (is_array($result) && isset($result['rich-messages'])) {
        for ($i = 0; $i < count($result['rich-messages'][0]); $i++) {
            array_push($ids, $result['rich-messages'][0][$i]['rich-message-name']);
        }
        return $ids;
    }
    return [];
}

function sb_get_rich_message($name) {
    if (in_array($name, ['email' , 'registration' , 'login', 'timetable', 'articles'])) {
        $title = '';
        $message = '';
        $success = '';
        switch ($name) {
            case 'email' :
                $active_user = sb_get_active_user();
                $user = $active_user == false || $active_user['user_type'] != 'user' ? ['first_name' => '', 'last_name' => '', 'email' => ''] : $active_user;
                $settings = sb_get_setting('follow-message');
                $title = sb_(sb_isset($settings, 'follow-title', ''));
                $message = sb_(sb_isset($settings, 'follow-msg', ''));
                $placeholder = sb_(sb_isset($settings, 'follow-placeholder', ''));
                $success = sb_(sb_isset($settings, 'follow-success', 'We will contact you soon!'));
                $name = $settings['follow-name'] ? '<div id="first_name" data-type="text" class="sb-input sb-input-text"><span>' . sb_('First name') . '</span><input value="' . $user['first_name'] . '" autocomplete="false" type="text" required></div><div id="last_name" data-type="text" class="sb-input sb-input-text"><span>' . sb_('Last name') . '</span><input value="' . $user['last_name'] . '" autocomplete="false" type="text" required></div>' : '';
                $code = $name . '<div id="email" data-type="email" class="sb-input sb-input-btn"><span>' . $placeholder . '</span><input value="' . $user['email'] . '" autocomplete="off" type="email" required><div class="sb-submit sb-icon-arrow-right"></div></div>';
                break;
            case 'registration':
                $active_user = sb_get_active_user();
                $user = $active_user != false && !sb_is_agent($active_user['user_type']) ? sb_get_user($active_user['id'], true) : ['profile_image' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'password' => '', 'user_type' => 'visitor', 'details' => []];
                $visitor = $user['user_type'] == 'visitor' || $user['user_type'] == 'lead';
                $settings = sb_get_setting('registration');
                $registration_fields = sb_get_setting('registration-fields');
                $title = sb_(sb_isset($settings, 'registration-title', 'Create new account'));
                $message = sb_(sb_isset($settings, 'registration-msg', 'Please create a new account before start the chat.'));
                $success = sb_(sb_isset($settings, 'registration-success', 'Login successful.'));
                $profile_image = sb_get_setting('registration-profile-img') ? '<div id="profile_image" data-type="image" class="sb-input sb-input-image sb-profile-image"><span>' . sb_('Profile image') . '</span><div' . ($user['profile_image'] != '' && strpos($user['profile_image'], 'media/user.svg') == false ? ' data-url="' . $user['profile_image'] . '" style="background-image:url(' . $user['profile_image'] . ')"' : '') . ' class="image">' . ($user['profile_image'] != '' && strpos($user['profile_image'], 'media/user.svg') == false ? '<i class="sb-icon-close"></i>' : '') . '</div></div>' : '';
                $password = sb_get_setting('registration-password') || sb_get_setting('wp-users-system') == 'wp' ? '<div id="password" data-type="text" class="sb-input sb-input-password"><span>' . sb_('Password') . '</span><input value="' . ($user['password'] != '' ? '********' : '') . '" autocomplete="false" type="password" required></div><div id="password-check" data-type="text" class="sb-input sb-input-password"><span>' . sb_('Repeat password') . '</span><input value="' . ($user['password'] != '' ? '********' : '') . '" autocomplete="false" type="password" required></div>' : '';
                $link = $settings['registration-terms-link'] != '' ? '<div class="sb-link-area">' . sb_('By clicking the button below you agree to our') . ' <a target="_blank" href="' . $settings['registration-terms-link'] . '">' . sb_('Terms of service') . '</a>' . ($settings['registration-privacy-link'] != '' ? ' ' . sb_('and') . ' <a target="_blank" href="' . $settings['registration-privacy-link'] . '">' . sb_('Privacy Policy') . '</a>' : '') . '.</div>' : '';
                $code = '<div class="sb-form-main sb-form">' . $profile_image . '<div id="first_name" data-type="text" class="sb-input sb-input-text"><span>' . sb_('First name') . '</span><input value="' . ($visitor ? '' : $user['first_name']) . '" autocomplete="false" type="text" required></div><div id="last_name" data-type="text" class="sb-input sb-input-text"><span>' . sb_('Last name') . '</span><input value="' . ($visitor ? '' : $user['last_name'])  . '" autocomplete="false" type="text" required></div><div id="email" data-type="text" class="sb-input sb-input-text"><span>' . sb_('Email') . '</span><input value="' . $user['email'] . '" autocomplete="off" type="email" required></div>' . $password . '</div><div class="sb-form-extra sb-form">';

                $extra = [];
                if (isset($user['details'])) {
                    for ($i = 0; $i < count($user['details']); $i++) {
                        $extra[$user['details'][$i]['slug']] = $user['details'][$i]['value'];
                    }
                }

                foreach ($registration_fields as $key => $setting) {
                    if ($registration_fields[$key][0]) {
                        $name = str_replace(['reg-', '-'], ['', ' '], $key);
                        $filled = (isset($extra[$name]) ? ' value="' . $extra[$name] . '"': '');
                        $code .= '<div id="' . str_replace('reg-', '', $key) . '" data-type="text" class="sb-input sb-input-text"><span>' . ucfirst(sb_($name)) . '</span><input' . $filled . ' autocomplete="false" type="text"></div>';
                    }
                }

                if (sb_get_setting('registration-extra')) {
                    $additional_fields = sb_get_setting('user-additional-fields');
                    if ($additional_fields != false) {
                        for ($i = 0; $i < count($additional_fields); $i++) {
                            $value = $additional_fields[$i];
                            $name = $value['extra-field-name'];
                            $filled = isset($extra[$value['extra-field-slug']]) ? ' value="' . $extra[$value['extra-field-slug']] . '"' : '';
                            if ($name != '') {
                                $code .= '<div id="' . $value['extra-field-slug'] . '" data-type="text" class="sb-input sb-input-text"><span>' . sb_($name) . '</span><input' . $filled . ' autocomplete="false" type="text"></div>';
                            }
                        }
                    }
                }

                $code .= '</div>' . $link . '<div class="sb-buttons"><div class="sb-btn sb-submit">' . sb_($visitor ? 'Create account' : 'Update account') . '</div>' . ($password  != '' ? '<div class="sb-btn-text sb-login-area">' . sb_('Sign in instead') . '</div>': '') . '</div>';
                break;
            case 'login':
                $settings = sb_get_setting('login');
                $title = sb_(sb_isset($settings, 'login-title', 'Login'));
                $message = sb_($settings['login-msg']);
                $code = '<div class="sb-form"><div id="email" class="sb-input"><span>' . sb_('Email') . '</span><input autocomplete="false" type="email"></div><div id="password" class="sb-input"><span>' . sb_('Password') . '</span><input autocomplete="false" type="password"></div></div><div class="sb-buttons"><div class="sb-btn sb-submit-login">' . sb_('Sign in') . '</div><div class="sb-btn-text sb-registration-area">' . sb_('Create new account') . '</div></div>';
                break;
            case 'timetable':
                $settings = sb_get_settings();
                $timetable = sb_isset($settings, 'timetable', [false])[0];
                $title = $settings['chat-timetable'][0]['chat-timetable-title'][0];
                $message = $settings['chat-timetable'][0]['chat-timetable-msg'][0];
                $title = sb_($title == '' ? 'Office hours' : $title);
                $message = sb_($message == '' ? 'Sorry, we are not available now, we will reply as soon as possible.' : $message);
                $code = '<div class="sb-timetable">';
                if ($timetable != false) {
                    foreach ($timetable as $day => $hours) {
                        if ($hours[0][0] != '') {
                            $code .= '<div><div>' . sb_(ucfirst($day)) . '</div><div data-time="' . $hours[0][0] . '|' . $hours[1][0] . '|' . $hours[2][0] . '|' . $hours[3][0] . '"></div></div>';
                        }
                    }
                }
                $code .= '<span></span></div>';
                break;
            case 'articles':
                $code = '<div class="sb-dashboard-articles"><div class="sb-title">' . sb_('Find an answer yourself') . '</div><div class="sb-input sb-input-btn"><input placeholder="' . sb_('Search for articles ...') . '" autocomplete="off"><div class="sb-submit sb-icon-arrow-right"></div></div><div class="sb-articles">';
                $articles = sb_get_articles(-1, 2);
                for ($i = 0; $i < count($articles); $i++) {
                    $code .= '<div data-id="' . $articles[$i]['id'] . '"><div>' . $articles[$i]['title'] . '</div><span>' . $articles[$i]['content'] . '</span></div>';
                }
                $code .= '</div><div class="sb-btn sb-btn-all-articles">' .sb_('All articles') . '</div></div>';
                break;

        }
        return ($title == '' ? '' : '<div class="sb-top">' . $title . '</div>') . ($message == '' ? '' : '<div class="sb-text">' . $message . '</div>') . $code  .  '<div data-success="' . $success . '" class="sb-info"></div>';
    } else {
        $result = sb_get_external_settings('rich-messages');
        if (is_array($result)) {
            for ($i = 0; $i < count($result['rich-messages'][0]); $i++) {
                $item = $result['rich-messages'][0][$i];
                if ($item['rich-message-name'] == $name) {
                    return $item['rich-message-content'];
                }
            }
        }
    }
    return false;
}

function sb_set_rating($settings, $payload, $message_id, $message, $user_id) {
    if (isset($settings['rating'])) {
        $ratings = sb_get_external_settings('ratings');
        if (!isset($ratings)) {
            $ratings = [];
        }
        $ratings[$settings['conversation_id']] = $settings;
        $json = sb_json_enconde($ratings);
        sb_db_query('INSERT INTO sb_settings(name, value) VALUES (\'ratings\', \'' . $json . '\') ON DUPLICATE KEY UPDATE value = \'' . $json . '\'');
        sb_update_message($user_id, $message_id, $message, false, $payload);
        return true;
    }
    return false;
}

function sb_get_rating($agent_id) {
    $ratings = sb_get_external_settings('ratings');
    $positive = 0;
    $negative = 0;
    if (isset($ratings)) {
        foreach ($ratings as $rating) {
            if ($rating['agent_id'] == $agent_id){
                if ($rating['rating'] == 1) {
                    $positive++;
                } else {
                    $negative++;
                }
            }
        }
    }
    return [$positive, $negative];
}

function sb_save_articles($articles) {
    $json = sb_json_enconde($articles);
    return sb_db_query('INSERT INTO sb_settings(name, value) VALUES (\'articles\', \'' . $json . '\') ON DUPLICATE KEY UPDATE value = \'' . $json . '\'');
}

function sb_get_articles($id = -1, $count = false, $full = false) {
    $articles = sb_get_external_settings('articles');
    $return = [];
    if ($articles != false) {
        $articles_count = count($articles);
        if ($id == -1) {
            $count = $count === false ? $articles_count : ($articles_count < $count ? $articles_count : $count);
            for ($i = 0; $i < $count; $i++) {
                if (!$full) {
                    $articles[$i]['content'] = substr($articles[$i]['content'], 0, 100);
                }
                array_push($return, $articles[$i]);
            }
        } else {
            for ($i = 0; $i < $articles_count; $i++) {
                if ($articles[$i]['id'] == $id) {
                    return $articles[$i];
                }
            }
            return false;
        }
    }
    return $return;
}

function sb_search_articles($search) {
    $articles = sb_get_external_settings('articles');
    $return = [];
    $search = strtolower($search);
    if ($articles != false) {
        for ($i = 0; $i < count($articles); $i++) {
            if (strpos(strtolower($articles[$i]['title']), $search) !== false || strpos(strtolower($articles[$i]['content']), $search)) {
                array_push($return, $articles[$i]);
            }
        }
    }
    return $return;
}

function sb_csv($items, $header, $filename) {
    $file = fopen(SB_PATH . '/uploads/' . $filename . '.csv', 'w');
    if (isset($header)) {
        fputcsv($file, $header);
    }
    for ($i = 0; $i < count($items); $i++) {
    	fputcsv($file, $items[$i]);
    }
    fclose($file);
    return SB_URL . '/uploads/' . $filename . '.csv';
}

function sb_csv_users() {
    $custom_fields = sb_get_setting('user-additional-fields');
    $header = ['Birthdate', 'City', 'Company', 'Country', 'Facebook', 'Language', 'LinkedIn', 'Phone', 'Twitter', 'Website'];
    $users = sb_db_get('SELECT id, first_name, last_name, email, profile_image, user_type, creation_time FROM sb_users WHERE user_type <> "bot"', false);
    $users_extra = sb_db_get('SELECT * FROM sb_users_data ORDER BY name', false);
    if (isset($custom_fields) && is_array($custom_fields)) {
        for ($i = 0; $i < count($custom_fields); $i++) {
            array_push($header, $custom_fields[$i]['extra-field-name']);
        }
    }
    for ($i = 0; $i < count($users); $i++) {
        $user = $users[$i];
        $user_extra = [];
        for ($j = 0; $j < count($users_extra); $j++) {
            if ($users_extra[$j] != false && $users_extra[$j]['user_id'] == $user['id']) {
                $user_extra[$users_extra[$j]['name']] = $users_extra[$j]['value'];
                $users_extra[$j] = false;
            }
        }
        for ($j = 0; $j < count($header); $j++) {
            $key = $header[$j];
            if (isset($user_extra[$key])) {
                $user[$key] = $user_extra[$key];
            } else {
                $user[$key] = '';
            }
        }
        $users[$i] = $user;
    }
    return sb_csv($users, array_merge(['ID', 'First Name', 'Last Name', 'Email', 'Profile Image', 'Type', 'Creation Time'], $header), 'users');
}

function sb_csv_conversations($conversation_id) {
    if ($conversation_id != -1) {
        $conversation = sb_db_get('SELECT * FROM sb_messages WHERE conversation_id="' . $conversation_id . '"', false);
        return sb_csv($conversation, ['ID', 'User ID', 'Title', 'Status Code', 'Creation date'], 'conversation-' . $conversation_id);
    }
    return false;
}

function sb_css() {
    $css = '';
    $color_1 = sb_get_setting('color-1');
    $color_2 = sb_get_setting('color-2');
    $color_3 = sb_get_setting('color-3');
    if ($color_1 != '') {
        $css .= '.sb-chat-btn, .sb-chat>div>.sb-header,.sb-chat .sb-dashboard>div>.sb-btn:hover,.sb-chat .sb-scroll-area .sb-header,.sb-input.sb-input-btn>div,div ul.sb-menu li:hover,
                 .sb-select ul li:hover,.sb-popup.sb-emoji .sb-emoji-bar>div.sb-active, .sb-popup.sb-emoji .sb-emoji-bar>div:hover,.sb-btn { background-color: ' . $color_1 . '; }';
        $css .= '.sb-chat .sb-dashboard>div>.sb-btn,.sb-search-btn>input,.sb-input>input:focus, .sb-input>select:focus, .sb-input>textarea:focus,.sb-input.sb-input-image .image:hover { border-color: ' . $color_1 . '; }';
        $css .= '.sb-chat .sb-dashboard>div>.sb-btn,.sb-editor .sb-bar-icons>div:hover:before,.sb-articles>div:hover>div,.sb-chat .sb-btn-text:hover, .sb-list .sb-btn-text:hover,.sb-editor .sb-submit,
                 .sb-select p:hover,div ul.sb-menu li.sb-active, .sb-select ul li.sb-active,.sb-search-btn>i:hover,.sb-search-btn.sb-active i,.sb-rich-message .sb-input>span.sb-active:not(.sb-filled),
                 .sb-input.sb-input-image .image:hover:before { color: ' . $color_1 . '; }';
        $css .= '.sb-search-btn>input:focus,.sb-input>input:focus, .sb-input>select:focus, .sb-input>textarea:focus,.sb-input.sb-input-image .image:hover { box-shadow: 0 0 5px rgba(104, 104, 104, 0.2); }';
        $css .= '.sb-list>div.sb-rich-cnt { border-top-color: ' . $color_1 . '; }';
    }
    if ($color_2 != '') {
        $css .= '.sb-chat-btn:hover,.sb-input.sb-input-btn>div:hover,.sb-btn:hover { background-color: ' . $color_2 . '; }';
        $css .= '.sb-list>.sb-right .sb-message, .sb-list>.sb-right .sb-message a,.sb-editor .sb-submit:hover { color: ' . $color_2 . '; }';
    }
    if ($color_3 != '') {
        $css .= '.sb-list>.sb-right,.sb-user-conversations>li:hover { background-color: ' . $color_3 . '; }';
    }
    echo '<style>' . $css . '</style>';
}

function sb_email_create($recipient_id, $sender_name, $sender_profile_image, $message, $attachments = []) {
    $recipient = false;
    $recipient_name = '';
    $recipient_email = '';
    $recipient_user_type = 'agent';

    if ($recipient_id == 'email-test') {
        $recipient_name = 'Test user';

    } else if ($recipient_id == -1) {
        $agents = sb_db_get('SELECT first_name, last_name, email FROM sb_users WHERE user_type = "agent" OR user_type = "admin"', false);
        for ($i = 0; $i < count($agents); $i++) {
            $recipient_name .= $agents[$i]['first_name'] . ' ' . $agents[$i]['last_name'] . ', ';
            $recipient_email .= $agents[$i]['email'] . ',';
        }
        $recipient_name = substr($recipient_name, 0, -2);
        $recipient_email = substr($recipient_email, 0, -1);
    } else {
        $recipient = sb_get_user($recipient_id);
        if ($recipient == false || $recipient['email'] == '') {
            return false;
        }
        $recipient_name = $recipient['first_name'] . ' ' . $recipient['last_name'];
        $recipient_email = $recipient['email'];
        $recipient_user_type = $recipient['user_type'];
    }

    $suffix = sb_is_agent($recipient_user_type) ? 'agent' : 'user';
    $settings = sb_get_external_settings('emails')['email-' . $suffix][0];
    $email = sb_email_create_content($settings['email-' . $suffix . '-subject'][0], $settings['email-' . $suffix . '-content'][0], $attachments, ['message' => $message, 'recipient_name' => $recipient_name, 'sender_name' => $sender_name, 'sender_profile_image' => $sender_profile_image]);
    return sb_email_send($recipient_email, $email[0], $email[1]);
}

function sb_email_create_content($subject, $message, $attachments, $replacements) {
    $attachments_code = '';
    for ($i = 0; $i < count($attachments); $i++) {
        $attachments_code .= '<a style="display:block;text-decoration:none;line-height:25px;color:rgb(102, 102, 102);" href="' . str_replace(' ', '%20', $attachments[$i][1]) . '">' . $attachments[$i][0] . '</a>';
    }
    if ($attachments_code != '') {
        $attachments_code = '<div style="margin-top: 30px">' . $attachments_code . '</div>';
    }
    if ($subject == '') {
        $subject = '[Support Board] New message from {sender_name}';
    }
    if ($message == '') {
        $message = '<br />Hello {recipient_name}!<br /><br />{message}{attachments}
                              <table cellspacing="0" border="0" cellpadding="0" bgcolor="transparent" style="border:none;border-collapse:separate;border-spacing:0;margin:0;table-layout:fixed">
                                <tr>
                                    <td valign="middle" width="50" align="left" style="border:none;font-family:Helvetica,Arial,sans-serif;padding:0;vertical-align:middle!important">
                                      <img width="32" height="32" src="{sender_profile_image}" style="border-radius:20px;display:inline-block;height:40px;max-width:100%;outline:none;text-decoration:none;width:40px">
                                    </td>
                                  <td valign="middle" align="left" style="border:none;font-family:Helvetica,Arial,sans-serif;padding:0;vertical-align:middle!important">
                                    <strong style="color:#000">{sender_name}</strong>
                                  </td>
                                </tr>
                             </table>';
    }
    $subject = str_replace(['{recipient_name}', '{sender_name}'], [$replacements['recipient_name'], $replacements['sender_name']], $subject);
    $message = str_replace(['{recipient_name}', '{sender_name}', '{sender_profile_image}', '{message}', '{attachments}'], [$replacements['recipient_name'], $replacements['sender_name'],  $replacements['sender_profile_image'], $replacements['message'], $attachments_code], $message);
    return [$subject, $message];
}

function sb_email_send($to, $subject, $message) {
    $settings = sb_get_setting('email-server');
    if (file_exists(SB_PATH . '/resources/phpmailer/PHPMailerAutoload.php') && $settings['email-server-host'] != '') {
        require SB_PATH . '/resources/phpmailer/PHPMailerAutoload.php';
        $port = $settings['email-server-port'];
        $mail = new PHPMailer;
        $message = nl2br(trim($message));
        $mail->isSMTP();
        $mail->Host = $settings['email-server-host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['email-server-user'];
        $mail->Password = $settings['email-server-password'];
        $mail->SMTPSecure = $port == 25 ? '' : $port == 465 ? 'ssl' : 'tls';
        $mail->Port = $port;
        $mail->setFrom($settings['email-server-from']);
        $mail->isHTML(true);
        $mail->Subject = trim($subject);
        $mail->Body    = $message;
        $mail->AltBody = $message;

        if (strpos($to, ',') > 0) {
            $emails = explode(',', $to);
            for ($i = 0; $i < count($emails); $i++) {
                $mail->addAddress($emails[$i]);
            }
        } else {
            $mail->addAddress($to);
        }
        if(!$mail->send()) {
            return new SBError($mail->ErrorInfo, 'sb_email_send');
        } else {
            return true;
        }
    } else {
        return mail($to, $subject, $message);
    }
}

function sb_email_send_test($to, $email_type) {
    $user = sb_get_active_user();
    $name = $user['first_name'] . ' ' . $user['last_name'];
    $image = SB_URL . '/media/user.svg';
    $attachments = [['Example link', $image], ['Example link two', $image]];
    $settings = sb_get_external_settings('emails')['email-' . $email_type][0];
    $email = sb_email_create_content($settings['email-' . $email_type . '-subject'][0], $settings['email-' . $email_type . '-content'][0], $attachments, ['message' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam', 'recipient_name' => $name, 'sender_name' => $name, 'sender_profile_image' => $user['profile_image']]);
    return sb_email_send($to, $email[0], $email[1]);
}

function sb_user_autodata($user_id) {
    $settings = [];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if (strlen($_SERVER['REMOTE_ADDR']) > 6) {
        $settings['ip'] = [$_SERVER['REMOTE_ADDR'], 'IP'];
        $ip_data = json_decode(file_get_contents('http://ip-api.com/json/' . $settings['ip'][0] . '?fields=status,country,countryCode,city,timezone,currency'), true);
        if (isset($ip_data['status']) && $ip_data['status'] == 'success') {
            if (isset($ip_data['city']) && isset($ip_data['country'])) {
                $settings['location'] = [$ip_data['city'] . ', ' . $ip_data['country'], 'Location'];
            }
            if (isset($ip_data['timezone'])) {
                $settings['timezone'] = [$ip_data['timezone'], 'Timezone'];
            }
            if (isset($ip_data['currency'])) {
                $settings['currency'] = [$ip_data['currency'], 'Currency'];
            }
            if (isset($ip_data['countryCode'])) {
                $settings['country_code'] = [$ip_data['countryCode'], 'Country Code'];
            }
        }
    }

    $browser = '';
    $agent = strtolower($user_agent);
    if (strpos($agent, 'safari/') and strpos($agent, 'opr/')) {
        $browser = 'Opera';
    } else if (strpos($agent, 'safari/') and strpos($agent, 'chrome/')) {
        $browser = 'Chrome';
    } else if (strpos($agent, 'msie')) {
        $browser = 'Internet Explorer';
    } else if (strpos($agent, 'firefox/')) {
        $browser = 'Firefox';
    } else if (strpos($agent, 'edge/')) {
        $browser = 'Microsoft Edge';
    } else if (strpos($agent, 'safari/') and strpos($agent, 'opr/') == false and strpos($agent, 'chrome/') == false) {
        $browser = 'Safari';
    };
    if ($browser != '') {
        $settings['browser'] = [$browser, 'Browser'];
    }

    $settings['browser_language'] = [strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)), 'Language'];

    $os = '';
    $os_array = ['/windows nt 10/i' =>  'Windows 10', '/windows nt 6.3/i' => 'Windows 8.1', '/windows nt 6.2/i' => 'Windows 8',  '/windows nt 6.1/i' => 'Windows 7',  '/windows nt 6.0/i' => 'Windows Vista', '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',  '/windows xp/i' => 'Windows XP', '/windows nt 5.0/i' => 'Windows 2000', '/windows me/i' => 'Windows ME', '/macintosh|mac os x/i' => 'Mac OS X', '/mac_powerpc/i' => 'Mac OS 9', '/linux/i' => 'Linux', '/ubuntu/i' => 'Ubuntu', '/iphone/i' => 'iPhone', '/ipod/i' => 'iPod', '/ipad/i' => 'iPad', '/android/i' => 'Android', '/blackberry/i' => 'BlackBerry', '/webos/i' => 'Mobile' ];
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os = $value;
        }
    }
    if ($os != '') {
        $settings['os'] = [$os, 'OS'];
    }

    if (isset($_SERVER['HTTP_REFERER'])) {
        $settings['current_url'] = [$_SERVER['HTTP_REFERER'], 'Current URL'];
    }

    return sb_add_new_user_extra($user_id, $settings);
}

function sb_clean_data() {
    sb_db_query('DELETE FROM sb_users WHERE user_type = "visitor" AND creation_time < "' . gmdate('Y-m-d H:i:s', time() - 86400) . '"');
    sb_db_query('DELETE FROM sb_conversations WHERE status_code = 4 AND creation_time < "' . gmdate('Y-m-d H:i:s', time() - 2592000) . '"');
}

function sb_current_url($user_id, $url = false) {
    if ($url === false) {
        $url = sb_db_get('SELECT value FROM sb_users_data WHERE user_id ="' . $user_id . '" and slug = "current_url" LIMIT 1');
        return isset($url['value']) ? $url['value'] : false;
    }
    return sb_db_query('UPDATE sb_users_data SET value = "' . $url . '" WHERE user_id ="' . $user_id . '" and slug = "current_url"');
}


function sb_installation($details) {
    $database = [];
    if (sb_db_check_connection() === true) {
        return true;
    }
    if (!isset($details['db-name']) || !isset($details['db-user']) || !isset($details['db-password']) || !isset($details['db-host'])) {
        return 'missing-details';
    } else {
        $database = ['name' => $details['db-name'][0], 'user' => $details['db-user'][0], 'password' => $details['db-password'][0], 'host' => $details['db-host'][0], 'port' => (isset($details['db-port']) && $details['db-port'][0] != '' ? intval($details['db-port'][0]) : ini_get('mysqli.default_port'))];
    }
    if (!isset($details['url'])) {
        return 'missing-url';
    } else {
        if (substr($details['url'], -1) == '/') {
            $details['url'] = substr($details['url'], 0, -1);
        }
    }
    $connection_check = sb_db_check_connection($database['name'], $database['user'], $database['password'], $database['host'], $database['port']);
    $db_respones = [];
    $success = '';
    if ($connection_check === true) {

        $connection = new mysqli($database['host'], $database['user'], $database['password'], $database['name'], $database['port']);
        $connection->set_charset('utf8mb4');
        $db_respones['users'] = $connection->query('CREATE TABLE IF NOT EXISTS sb_users (id INT NOT NULL AUTO_INCREMENT, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, password VARCHAR(100), email VARCHAR(255) UNIQUE, profile_image VARCHAR(255), user_type VARCHAR(10) NOT NULL, creation_time DATETIME NOT NULL, token VARCHAR(50) NOT NULL UNIQUE, last_activity DATETIME, typing INT DEFAULT -1, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $db_respones['users_data'] = $connection->query('CREATE TABLE IF NOT EXISTS sb_users_data (user_id INT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, value TEXT NOT NULL, FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE, UNIQUE INDEX sb_users_data_index (user_id, slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $db_respones['conversations'] = $connection->query('CREATE TABLE IF NOT EXISTS sb_conversations (id int NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, title VARCHAR(255), creation_time DATETIME NOT NULL, status_code TINYINT DEFAULT 0,  PRIMARY KEY (id), FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $db_respones['messages'] = $connection->query('CREATE TABLE IF NOT EXISTS sb_messages (id int NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, message TEXT NOT NULL, creation_time DATETIME NOT NULL, status_code TINYINT DEFAULT 0, attachments TEXT, payload TEXT, conversation_id INT NOT NULL, PRIMARY KEY (id), FOREIGN KEY (user_id) REFERENCES sb_users(id) ON DELETE CASCADE, FOREIGN KEY (conversation_id) REFERENCES sb_conversations(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
        $db_respones['settings'] = $connection->query('CREATE TABLE IF NOT EXISTS sb_settings (name VARCHAR(255) NOT NULL, value TEXT, PRIMARY KEY (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        if (isset($details['first-name']) && isset($details['last-name']) && isset($details['email']) && isset($details['password'])) {
            $now = gmdate('Y-m-d H:i:s');
            $token = bin2hex(openssl_random_pseudo_bytes(20));
            $db_respones['admin'] = $connection->query('INSERT IGNORE INTO sb_users(first_name, last_name, password, email, profile_image, user_type, creation_time, token, last_activity) VALUES ("' . sb_db_sanatize($details['first-name'][0]) . '", "' . sb_db_sanatize($details['last-name'][0]) . '", "' . sb_db_sanatize(defined('SB_WP') ? $details['password'][0] : password_hash($details['password'][0], PASSWORD_DEFAULT)) . '", "' . $details['email'][0] . '", "' . $details['url'] . '/media/user.svg' . '", "admin", "' . $now . '", "' . $token . '", "' . $now . '")');
        }

        $raw = file_get_contents(SB_PATH . '/resources/config-source.php');
        $raw = str_replace(['[url]', '[name]', '[user]', '[password]', '[host]', '[port]'], [$details['url'], $database['name'], $database['user'], $database['password'], $database['host'], (isset($details['db-port']) && $details['db-port'][0] != '' ? $database['port'] : '')], $raw);
        if (defined('SB_WP')) {
            $raw = str_replace('/* [extra] */', sb_wp_config(), $raw);
        }
        sb_file(SB_PATH . '/config.php', $raw);
        foreach ($db_respones as $key => $value) {
            if ($value !== true) {
                $success .= $key . ': ' . ($value === false ? 'false' : $value) . ',';
            }
        }
        if ($success == '') {
            return true;
        } else {
            return substr($success, 0, -1);
        }
    } else {
        return $connection_check;
    }
}

function sb_update_bot($name = '', $profile_image = '') {
    $bot = sb_db_get('SELECT id, profile_image, first_name, last_name FROM sb_users WHERE user_type = "bot" LIMIT 1');
    if ($name == '') {
        $name = 'Bot';
    }
    if ($profile_image == '') {
        $profile_image = SB_URL . '/media/user.svg';
    }
    $settings = ['profile_image' => [$profile_image], 'first_name' => [$name], 'user_type' => ['bot']];
    if ($bot == '') {
       return sb_add_user($settings);
    } else if ($bot['profile_image'] != $profile_image || $bot['first_name'] != $name){
       return sb_update_user($bot['id'], $settings);
    }
    return false;
}

function sb_file($path, $content) {
    try {
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function sb_debug($value) {
    if (file_exists('debug.txt')) {
        $value = file_get_contents('debug.txt') . PHP_EOL . $value;
    }
    sb_file('debug.txt', $value);
}

?>