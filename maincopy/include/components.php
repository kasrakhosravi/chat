<?php

?>

<?php

function sb_component_chat() {
    sb_js_global();
    sb_css();
    $header_headline = sb_get_setting('header-headline');
    $header_message = sb_get_setting('header-msg');
    $background = sb_get_setting('header-img');
    $icon = sb_get_setting('chat-icon');
    $header_type = sb_get_setting('header-type');
    $css = '';
    if (sb_get_setting('rtl')) {
        $css .= ' sb-rtl';
    }
    if (sb_get_setting('chat-position') == 'left') {
        $css .= ' sb-chat-left';
    }
?>
<div class="sb-main sb-chat sb-no-conversations<?php echo $css ?>" style="display: none; transition: none;">
    <div class="sb-body">
        <div class="sb-scroll-area">
            <div class="sb-header sb-header-main"<?php if ($background != '') echo 'style="background-image: url(' . $background . ')"' ?>>
                <div class="sb-content">
                <?php 
    if ($header_type == 'brand') { 
        echo '<div class="sb-brand"><img src="'. sb_get_setting('brand-img') . '" alt="" /></div>';
    }
                ?>
                <div class="sb-title">
                    <?php echo  sb_($header_headline != '' ? $header_headline : 'Support') ?>
                </div>
                <div class="sb-text">
                    <?php echo  sb_($header_message != '' ? $header_message : 'Will be with you in a moment!') ?>
                </div>
                <?php 
    if ($header_type == 'agents' || $header_type == false) {
        $agents = sb_get_users(-1, ['creation_time' , 'DESC'], ['agent', 'admin'], '', [0, 2]);
        $code = '';
        for ($i = 0; $i < count($agents); $i++) {
            $code .= '<div><span>' . $agents[$i]["first_name"] . '</span><img src="'. $agents[$i]["profile_image"] . '" alt="" /></div>';
        }
        echo '<div class="sb-profiles">' . $code . '</div>';
    }
                ?>
                </div>
            </div>
            <div class="sb-list sb-active"></div>
            <div class="sb-dashboard">
                <div class="sb-dashboard-conversations">
                   <div class="sb-title"><?php sb_e('Conversations') ?></div>
                   <ul class="sb-user-conversations"></ul>
                   <div class="sb-btn sb-btn-new-conversation"><?php sb_e('New conversation') ?></div>
                </div>
                <?php if (sb_get_setting('articles-active')) { echo sb_get_rich_message('articles'); } ?>
            </div>
            <?php if (sb_get_setting('articles-active')) { echo '<div class="sb-panel sb-panel-articles"></div>';  } ?>
        </div>
        <?php sb_component_editor(); ?>
    </div>
    <div class="sb-chat-btn">
        <span data-count="0"></span>
        <img class="sb-icon" alt="" src="<?php echo $icon != '' ? $icon : SB_URL . '/media/lines.svg' ?>" />
        <img class="sb-close" alt="" src="<?php echo SB_URL ?>/media/close.svg" />
    </div>
    <i class="sb-icon sb-icon-close sb-responsive-close-btn"></i>
    <audio id="sb-audio" preload="auto"><source src="<?php echo SB_URL ?>/media/sound.mp3" type="audio/mpeg"></audio>
    <div class="sb-lightbox"><div></div></div>
    <div class="sb-lightbox-overlay"><i class="sb-icon-close"></i></div>
</div>
<?php } ?>

<?php

function sb_component_editor($admin = false) {
?>
<div class="sb-editor">
    <div class="sb-textarea"><textarea placeholder="<?php sb_e('Write a message ...') ?>"></textarea></div>
    <div class="sb-attachments"></div>
    <div class="sb-bar">
        <div class="sb-bar-icons">
            <div class="sb-btn-attachment" data-sb-tooltip="<?php sb_e('Attach a file') ?>"></div>
            <div class="sb-btn-saved-replies" data-sb-tooltip="<?php sb_e('Add a saved reply') ?>"></div>
            <div class="sb-btn-emoji" data-sb-tooltip="<?php sb_e('Add an emoji') ?>"></div>
        </div>
        <div class="sb-icon-send sb-submit" data-sb-tooltip="<?php sb_e('Send message') ?>"></div>
        <img class="sb-loader" src="<?php echo SB_URL ?>/media/loader.svg" alt="" />
    </div>
    <div class="sb-popup sb-emoji">
        <div class="sb-header">
            <div class="sb-select">
                <p>
                    <?php sb_e('All') ?>
                </p>
                <ul>
                    <li data-value="all" class="sb-active">
                        <?php sb_e('All') ?>
                    </li>
                    <li data-value="Smileys">
                        <?php sb_e('Smileys & Emotions') ?>
                    </li>
                    <li data-value="People">
                        <?php sb_e('People & Body') ?>
                    </li>
                    <li data-value="Animals">
                        <?php sb_e('Animals & Nature') ?>
                    </li>
                    <li data-value="Food">
                        <?php sb_e('Food & Drink') ?>
                    </li>
                    <li data-value="Travel">
                        <?php sb_e('Travel & Places') ?>
                    </li>
                    <li data-value="Activities">
                        <?php sb_e('Activities') ?>
                    </li>
                    <li data-value="Objects">
                        <?php sb_e('Objects') ?>
                    </li>
                    <li data-value="Symbols">
                        <?php sb_e('Symbols') ?>
                    </li>
                </ul>
            </div>
            <div class="sb-search-btn">
                <i class="sb-icon sb-icon-search"></i>
                <input type="text" placeholder="<?php sb_e('Search emoji ...') ?>" />
            </div>
        </div>
        <div class="sb-emoji-list">
            <ul></ul>
        </div>
        <div class="sb-emoji-bar"></div>
    </div>
    <?php 
    if ($admin) { ?>
    <div class="sb-popup sb-replies">
        <div class="sb-header">
            <div class="sb-title">
                <?php sb_e('Saved replies') ?>
            </div>
            <div class="sb-search-btn">
                <i class="sb-icon sb-icon-search"></i>
                <input type="text" autocomplete="false" placeholder="<?php sb_e('Search replies ...') ?>" />
            </div>
        </div>
        <div class="sb-replies-list sb-scroll-area">
            <ul></ul>
        </div>
    </div>
    <?php  } ?>
    <form class="sb-upload-form-editor" action="#" method="post" enctype="multipart/form-data">
        <input type="file" name="files[]" class="sb-upload-files" multiple />
    </form>
</div>
<?php } ?>

<?php

function sb_profile_box() {
?>
<div class="sb-profile-box sb-lightbox">
    <div class="sb-top-bar">
        <div class="sb-profile">
            <img src="<?php echo SB_URL ?>/media/user.svg" />
            <span>--</span>
        </div>
        <div>
            <a class="sb-edit sb-btn sb-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-edit-area">
                <i class="sb-icon-user"></i><?php sb_e('Edit user') ?>
            </a>
            <a class="sb-start-conversation sb-btn sb-icon">
                <i class="sb-icon-message"></i><?php sb_e('Start a conversation') ?>
            </a>
            <a class="sb-close sb-btn-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-table-area">
                <i class="sb-icon-close"></i>
            </a>
        </div>
    </div>
    <div class="sb-main sb-scroll-area">
        <div>
            <div class="sb-title">
                <?php sb_e('Details') ?>
            </div>
            <div class="sb-profile-list"></div>
            <div class="sb-agent-area"></div>
        </div>
        <div>
            <div class="sb-title">
                <?php sb_e('User conversations') ?>
            </div>
            <ul class="sb-user-conversations"></ul>
        </div>
    </div>
</div>
<?php } ?>

<?php

function sb_profile_edit_box() {
?>
<div class="sb-profile-edit-box sb-lightbox">
    <div class="sb-info"></div>
    <div class="sb-top-bar">
        <div class="sb-profile">
            <img src="<?php echo SB_URL ?>/media/user.svg" />
            <span>--</span>
        </div>
        <div>
            <a class="sb-save sb-btn sb-icon">
                <i class="sb-icon-check"></i><?php sb_e('Save changes') ?>
            </a>
            <a class="sb-close sb-btn-icon" data-button="toggle" data-hide="sb-profile-area" data-show="sb-table-area">
                <i class="sb-icon-close"></i>
            </a>
        </div>
    </div>
    <div class="sb-main sb-scroll-area">
        <div class="sb-details">
            <div class="sb-title">
                <?php sb_e('Edit details') ?>
            </div>
            <div class="sb-edit-box">
                <div id="profile_image" data-type="image" class="sb-input sb-input-image sb-profile-image">
                    <span><?php sb_e('Profile image') ?></span>
                    <div class="image"><div class="sb-icon-close"></div></div>
                </div>
                <div id="user_type" data-type="select" class="sb-input sb-input-select">
                    <span><?php sb_e('Type') ?></span>
                    <select>
                        <option value="user"><?php sb_e('User') ?></option>
                        <option value="agent"><?php sb_e('Agent') ?></option>
                        <option value="admin"><?php sb_e('Admin') ?></option>
                    </select>
                </div>
                <div id="first_name" data-type="text" class="sb-input">
                    <span><?php sb_e('First name') ?></span>
                    <input type="text" required />
                </div>
                <div id="last_name" data-type="text" class="sb-input">
                    <span><?php sb_e('Last name') ?></span>
                    <input type="text" required />
                </div>
                <div id="password" data-type="text" class="sb-input">
                    <span><?php sb_e('Password') ?></span>
                    <input type="password" />
                </div>
                <div id="email" data-type="email" class="sb-input" >
                    <span><?php sb_e('Email') ?></span>
                    <input type="email" required />
                </div>
            </div>
             <a class="sb-delete sb-btn-text sb-btn-red">
                <i class="sb-icon-delete"></i><?php sb_e('Delete user') ?>
            </a>
        </div>
        <div class="sb-additional-details">
            <div class="sb-title">
                <?php sb_e('Edit additional details') ?>
            </div>
            <div class="sb-edit-box">
                <div id="phone" data-type="text" class="sb-input">
                    <span><?php sb_e('Phone') ?></span>
                    <input type="text" />
                </div>
                 <div id="city" data-type="text" class="sb-input">
                    <span><?php sb_e('City') ?></span>
                    <input type="text" />
                </div>
                <div id="country" data-type="text" class="sb-input">
                    <span><?php sb_e('Country') ?></span>
                    <input type="text" />
                </div>
                <div id="language" data-type="text" class="sb-input">
                    <span><?php sb_e('Language') ?></span>
                    <input type="text" />
                </div>
                <div id="birthdate" data-type="text" class="sb-input">
                    <span><?php sb_e('Birthdate') ?></span>
                    <input type="text" />
                </div>
                <div id="company" data-type="text" class="sb-input">
                    <span><?php sb_e('Company') ?></span>
                    <input type="text" />
                </div>
                <div id="facebook" data-type="text" class="sb-input">
                    <span><?php sb_e('Facebook') ?></span>
                    <input type="text" />
                </div>
                <div id="twitter" data-type="text" class="sb-input">
                    <span><?php sb_e('Twitter') ?></span>
                    <input type="text" />
                </div>
                <div id="linkedin" data-type="text" class="sb-input">
                    <span><?php sb_e('Linkedin') ?></span>
                    <input type="text" />
                </div>
                <div id="website" data-type="text" class="sb-input">
                    <span><?php sb_e('Website') ?></span>
                    <input type="text" />
                </div>
                <?php 
    $additional_fields = sb_get_setting('user-additional-fields');   
    if ($additional_fields != false) {
        $code = '';
        for ($i = 0; $i < count($additional_fields); $i++) {
            $value = $additional_fields[$i];
            if ($value['extra-field-name'] != '') {
                $code .= '<div id="' . $value['extra-field-slug'] . '" data-type="text" class="sb-input"><span>' . $value['extra-field-name'] . '</span><input type="text"></div>';
            }
        }
        echo $code;
    }
                ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php

function sb_login_box() {
?>
<style>
body, html {
    background: #ffffff !important;
}
.sb-admin {
    background: #ffffff !important;
}
.sb-admin-box {
    border: none !important;
}
.sb-admin-box .sb-top-bar img {
    margin-left: -10px !important;
}
.sb-btn, a.sb-btn, a.sb-rich-btn.sb-btn {
    background-color: #279EFF !important;
}
.sb-btn:hover, .sb-btn:active, a.sb-btn:hover, a.sb-btn:active, a.sb-rich-btn.sb-btn:hover, a.sb-rich-btn.sb-btn:active {
    background-color: #228ade !important;
}
.sb-input>input:focus, .sb-input>select:focus, .sb-input>textarea:focus {
    border: 1px solid #279EFF;
    box-shadow: none !important;
}
</style>
<form class="sb sb-rich-login sb-admin-box">
    <div class="sb-info"></div>
    <div class="sb-top-bar">
        <a href="https://supportonsite.co"><img src="<?php echo SB_URL ?>/media/logo.svg" /></a>
        <div class="sb-title"><?php sb_e('Login') ?></div>  
    </div>
    <div class="sb-main">
        <div id="email" class="sb-input">
            <span><?php sb_e('Email') ?></span>
            <input type="text" />
        </div>
        <div id="password" class="sb-input">
            <span><?php sb_e('Password') ?></span>
            <input type="password" />
        </div>
        <div class="sb-bottom">
             <div class="sb-btn sb-submit-login"><?php sb_e('Go') ?></div>
        </div>
    </div>
</form>
<?php } ?>

<?php

function sb_dialog() {
?>
<div class="sb-dialog-box sb-lightbox">
    <p></p>
    <div>
        <a class="sb-confirm sb-btn"><?php sb_e('Confirm') ?></a>
        <a class="sb-cancel sb-btn sb-btn-red"><?php sb_e('Cancel') ?></a>
        <a class="sb-close sb-btn"><?php sb_e('Close') ?></a>
    </div>
</div>
<?php } ?>

<?php

function sb_dialogflow_intent_box() {
?>
<div class="sb-lightbox sb-dialogflow-intent-box">
    <div class="sb-info"></div>
    <div class="sb-top-bar">
         <div>Dialogflow Intent</div>
         <div>
            <a class="sb-send sb-btn sb-icon">
                <i class="sb-icon-check"></i><?php sb_e('Send') ?> Intent
            </a>
            <a class="sb-close sb-btn-icon">
                <i class="sb-icon-close"></i>
            </a>
        </div>
    </div>
    <div class="sb-main">
        <div class="sb-title sb-intent-add"><?php sb_e('Add user expressions') ?> <i data-sb-tooltip="<?php sb_e('Add expression') ?>" class="sb-btn-icon sb-icon-plus"></i></div>
        <div class="sb-input-setting sb-type-text sb-first">
            <input type="text">
        </div>
        <div class="sb-title"><?php sb_e('Response from the bot') ?></div>
        <div class="sb-input-setting sb-type-textarea">
            <textarea></textarea>
        </div>
        <div class="sb-title"><?php sb_e('Language') ?></div>
        <?php echo sb_dialogflow_languages_list() ?>
    </div>
</div>
<?php } ?>

<?php

function sb_updates_box() {
?>
<div class="sb-lightbox sb-updates-box">
    <div class="sb-info"></div>
    <div class="sb-top-bar">
         <div><?php sb_e('Updates center') ?></div>
        <div>
            <a class="sb-close sb-btn-icon">
                <i class="sb-icon-close"></i>
            </a>
        </div>
    </div>
    <div class="sb-main">
       <div class="sb-bottom">
            <a class="sb-update sb-btn sb-icon">
                <i class="sb-icon-reload"></i><?php sb_e('Update now') ?>
            </a>
            <a href="http://board.support/changes" target="_blank" class="sb-btn-text">
                <i class="sb-icon-clock"></i><?php sb_e('Change log') ?>
            </a>
       </div>
    </div>
</div>
<?php } ?>

<?php
function sb_app_box() {
?>
<div class="sb-lightbox sb-app-box" data-app="">
    <div class="sb-info"></div>
    <div class="sb-top-bar">
        <div></div>
        <div>
            <a class="sb-close sb-btn-icon">
                <i class="sb-icon-close"></i>
            </a>
        </div>
    </div>
    <div class="sb-main">
        <p></p>
        <div class="sb-title"><?php sb_e('License key') ?></div>
        <div class="sb-input-setting sb-type-text">
            <input type="text" required>
        </div>
        <div class="sb-bottom">
            <a class="sb-activate sb-btn sb-icon">
                <i class="sb-icon-check"></i><?php sb_e('Activate') ?>
            </a>
             <a class="sb-btn sb-icon sb-btn-app-puchase" target="_blank" href="#">
                <i class="sb-icon-plane"></i><?php sb_e('Purchase license') ?>
            </a>
            <a class="sb-btn-text sb-btn-app-details" target="_blank" href="#">
                <i class="sb-icon-help"></i><?php sb_e('Read more') ?>
            </a>
       </div>
    </div>
</div>
<?php } ?>

<?php

function sb_installation_box($error = false) {
?>
<div class="sb-main sb-admin sb-admin-start">
    <form class="sb-intall sb-admin-box">
        <?php 
        echo $error === false || $error == 'installation' ? '<div class="sb-info"></div>' : '<div class="sb-info sb-active">' . sb_('We have troubles to connect to your database, please insert again the below information. Error: ') . $error . '.</div>';
        ?>
        <div class="sb-top-bar">
            <img src="<?php echo (SB_URL == '' || SB_URL == '[url]' ? '' : SB_URL . '/') ?>media/logo.svg" />
            <div class="sb-title"><?php sb_e('Get Started') ?></div>  
            <div class="sb-text"><?php sb_e('Enter the user details of the main account you will use to login into your SupportOnSite dashboard.') ?></div>
        </div>
        <style>
        .sb-admin-box .sb-top-bar {
        padding-bottom: 0px !important;
        }
        .sb-admin-box .sb-top-bar img {
        margin-left: -10px !important;
        }
        .sb-btn, a.sb-btn, a.sb-rich-btn.sb-btn {
        background-color: #279EFF !important;
        }
        .sb-btn:hover, .sb-btn:active, a.sb-btn:hover, a.sb-btn:active, a.sb-rich-btn.sb-btn:hover, a.sb-rich-btn.sb-btn:active {
        background-color: #228ade !important;
        }
        .sb-input>input:focus, .sb-input>select:focus, .sb-input>textarea:focus {
        border: 1px solid #279EFF;
        box-shadow: none !important;
        }
        .sb-admin {
        background: #ffffff !important;
        }
        .sb-admin-box {
        border: none !important;
        }
        </style>
        <div class="sb-main">
            <div id="db-name" class="sb-input">
                <input type="hidden" value="<?php echo $_COOKIE["thedb"]; ?>" required />
            </div>
            <div id="db-user" class="sb-input">
                <input type="hidden" value="root" required />
            </div>
            <div id="db-password" class="sb-input">
                <input type="hidden" value="Ftf51678@1" />
            </div>
            <div id="db-host" class="sb-input">
                <input type="hidden" value="localhost" required />
            </div>
            <div id="db-port" class="sb-input">
                <input type="hidden" placeholder="Default" />
            </div>
            <?php if ($error === false || $error == 'installation') { ?>
            <div id="first-name" class="sb-input">
                <span><?php sb_e('First Name') ?></span>
                <input type="text" required />
            </div>
            <div id="last-name" class="sb-input">
                <span><?php sb_e('Last Name') ?></span>
                <input type="text" required />
            </div>
            <div id="email" class="sb-input">
                <span><?php sb_e('Email') ?></span>
                <input type="email" required />
            </div>
            <div id="password" class="sb-input">
                <span><?php sb_e('Password') ?></span>
                <input type="password" required />
            </div>
            <div id="password-check" class="sb-input">
                <span><?php sb_e('Confirm Password') ?></span>
                <input type="password" required />
            </div>
            <?php } ?>
            <div class="sb-bottom">
                 <div class="sb-btn sb-submit-installation"><?php sb_e('Go') ?></div>
            </div>
        </div>
    </form>
</div>
<?php } ?>

<?php

function sb_component_admin() { 
    $sb_settings = json_decode(file_get_contents(SB_PATH . '/resources/json/settings.json'), true);
    $active_user = sb_get_active_user();
?>
<style>
.sb-board .sb-user-details {
    background-color: #ffffff !important;
}
.sb-profile-list>ul>li .sb-icon {
    color: #279EFF !important;
}
.sb-editor .sb-bar-icons>div:hover:before {
    color: #279EFF !important;
}
.sb-editor .sb-submit {
    color: #279EFF !important;
}
.sb-board>.sb-admin-list .sb-scroll-area li.sb-active {
    border-left-color: #279EFF !important;
}
.sb-board .sb-conversation .sb-list>div .sb-menu-btn:hover {
    color: #279EFF !important;
}
.sb-search-btn>i:hover {
    color: #279EFF !important;
}
.sb-btn-icon:hover {
    border-color: #279EFF !important;
    color: #279EFF !important;
}
.sb-admin>.sb-header>.sb-admin-nav-right .sb-account .sb-menu li {
    padding: 10px !important;
    border-radius: 20px !important;
}
@media only screen and (max-width: 600px) {
  .sb-admin>.sb-header>.sb-admin-nav>img {
    margin: 7px 7px !important;
   }
   .sb-admin .sb-lightbox .sb-top-bar .sb-save i, .sb-admin .sb-lightbox .sb-top-bar .sb-send i, .sb-admin .sb-area-users .sb-new-user i, .sb-admin .sb-area-settings .sb-save-changes i {
    left: 9px !important;
    color: #ffffff !important;
    line-height: 43px !important;
   }
   .sb-admin .sb-area-users .sb-top-bar .sb-new-user i {
    line-height: 43px !important;
    left: 9px !important;
   }
   .sb-admin .sb-menu-mobile>div, .sb-admin .sb-menu-mobile>ul {
    top: 30px !important;
   }
   .sb-admin .sb-menu-mobile>i.sb-active {
    color: #279EFF !important;
   }
   .sb-admin .sb-area-settings .sb-top-bar .sb-save-changes i {
    line-height: 43px !important;
    right: 10px !important;
   }
   .sb-admin .sb-menu-wide:after, .sb-admin .sb-table-users th:first-child:after {
    right: 12px !important;
   }
   .sb-btn-icon i {
    line-height: 38px !important;
   }
}
</style>
    <div class="sb-main sb-admin" style="opacity: 0">
        <?php
    if ($active_user != false && sb_is_agent($active_user['user_type'])) { ?>
        <div class="sb-header">
            <div class="sb-admin-nav">
                <img src="<?php echo SB_URL ?>/media/icon.svg" />
                <div>
                    <a id="sb-messages" class="sb-active">
                        <span>
                            <?php sb_e('Messages') ?>
                        </span>
                    </a>
                    <a id="sb-users">
                        <span>
                            <?php sb_e('Users') ?>
                        </span>
                    </a>
                    <?php
        if ($active_user['user_type'] == 'admin') {
            echo '<a id="sb-settings"><span>' . sb_('Settings') . '</span></a>';
        }
                    ?>
                </div>
            </div>
            <div class="sb-admin-nav-right sb-menu-mobile">
                <i class="sb-icon-menu"></i>
                <div class="sb-desktop">
                    <div class="sb-account">
                        <img src="<?php echo SB_URL ?>/media/user.svg" />
                        <div>
                            <a class="sb-profile">
                                <img src="<?php echo SB_URL ?>/media/user.svg" />
                                <span>--</span>
                            </a>
                            <ul class="sb-menu">
                                <li data-value="edit-profile">
                                    <?php sb_e('Edit Profile') ?>
                                </li>
                                <li data-value="logout">
                                    <?php sb_e('Logout') ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="mailto:hi@supportonsite.co" class="sb-docs">
                        <i class="sb-icon-help"></i>
                    </a>
                </div>
                <div class="sb-mobile">
                    <a href="#" class="edit-profile">
                        <?php sb_e('Edit Profile') ?>
                    </a>
                    <a href="#" class="logout">
                        <?php sb_e('Logout') ?>
                    </a>
                    <a href="mailto:hi@supportonsite.co" class="sb-docs">
                        <?php sb_e('Help') ?>
                    </a>
                </div>
            </div>
        </div>
        <main>
            <div class="sb-active sb-area-conversations">
                <div class="sb-board">
                    <div class="sb-admin-list">
                        <div class="sb-top">
                            <div class="sb-select">
                                <p>
                                    <?php sb_e('Inbox') ?>
                                    <span></span>
                                </p>
                                <ul>
                                    <li data-value="0" class="sb-active">
                                        <?php sb_e('Inbox') ?>
                                        <span></span>
                                    </li>
                                    <li data-value="3">
                                        <?php sb_e('Archive') ?>
                                    </li>
                                    <li data-value="4">
                                        <?php sb_e('Trash') ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="sb-search-btn">
                                <i class="sb-icon sb-icon-search"></i>
                                <input type="text" autocomplete="false" placeholder="<?php sb_e('Search for keywords or users ...') ?>" />
                            </div>
                        </div>
                        <div class="sb-scroll-area">
                            <ul></ul>
                        </div>
                    </div>
                    <div class="sb-conversation">
                        <div class="sb-top">
                            <i class="sb-btn-back sb-icon-arrow-left"></i>
                            <a></a>
                            <div class="sb-labels"></div>
                            <div class="sb-menu-mobile">
                                <i class="sb-icon-menu"></i>
                                <ul>
                                    <li>
                                        <a data-value="read" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Mark as read') ?>">
                                            <i class="sb-icon-check-circle"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a data-value="csv" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Download CSV') ?>">
                                            <i class="sb-icon-file"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a data-value="inbox" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Send to inbox') ?>">
                                            <i class="sb-icon-back"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a data-value="archive" class="sb-btn-icon" data-sb-tooltip="<?php sb_e('Archive conversation') ?>">
                                            <i class="sb-icon-download"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a data-value="delete" class="sb-btn-icon sb-btn-red" data-sb-tooltip="<?php sb_e('Delete conversation') ?>">
                                            <i class="sb-icon-delete"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a data-value="empty-trash" class="sb-btn-icon sb-btn-red" data-sb-tooltip="<?php sb_e('Empty trash') ?>">
                                            <i class="sb-icon-delete"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="sb-list"></div>
                        <?php sb_component_editor(true); ?>
                        <div class="sb-no-conversation-message">
                            <div>
                                <label>
                                    <?php sb_e('Select a conversation or start a new one') ?>
                                </label>
                                <p>
                                    <?php sb_e('Select a conversation from the left menu or start a new conversation from the users area.') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="sb-user-details">
                        <div class="sb-top">
                            <?php sb_e('Details') ?>
                        </div>
                        <div class="sb-scroll-area">
                            <div class="sb-profile">
                                <img src="<?php echo SB_URL ?>/media/user.svg" />
                                <span class="sb-name">--</span>
                            </div>
                            <div class="sb-profile-list"></div>
                            <h3>
                                <?php sb_e('User conversations') ?>
                            </h3>
                            <ul class="sb-user-conversations"></ul>
                        </div>
                        <div class="sb-no-conversation-message"></div>
                    </div>
                </div>
            </div>
            <div class="sb-area-users">
                <div class="sb-top-bar">
                    <div>
                        <h2>
                            <?php sb_e('Users list') ?>
                        </h2>
                        <ul class="sb-menu-wide sb-menu-users">
                            <li data-type="all" class="sb-active">
                                <?php sb_e('All') ?>
                                <span data-count="0">(0)</span>
                            </li>
                            <li data-type="user">
                                <?php sb_e('Users') ?>
                                <span data-count="0">(0)</span>
                            </li>
                            <li data-type="lead">
                                <?php sb_e('Leads') ?>
                                <span data-count="0">(0)</span>
                            </li>
                            <li data-type="visitor">
                                <?php sb_e('Visitors') ?>
                                <span data-count="0">(0)</span>
                            </li>
                            <li data-type="online">
                                <?php sb_e('Online') ?>
                            </li>
                            <?php
        if ($active_user['user_type'] == 'admin') {
            echo '<li data-type="agent">' . sb_('Agents & Admins') . '</li>';
        }
                            ?>
                        </ul>
                        <div class="sb-menu-buttons">
                            <a data-value="csv" class="sb-btn-icon" data-sb-tooltip="Download CSV">
                                <i class="sb-icon-file"></i>
                            </a>
                            <a data-value="delete" class="sb-btn-icon" data-sb-tooltip="Delete users" style="display: none;">
                                <i class="sb-icon-delete"></i>
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="sb-search-btn">
                            <i class="sb-icon sb-icon-search"></i>
                            <input type="text" autocomplete="false" placeholder="<?php sb_e('Search users ...') ?>" />
                        </div>
                        <a class="sb-btn sb-icon sb-new-user">
                            <i class="sb-icon-user"></i><?php sb_e('Add new user') ?>
                        </a>
                    </div>
                </div>
                <div class="sb-scroll-area">
                    <table class="sb-table sb-table-users">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" />
                                </th>
                                <th data-field="first_name">
                                    <?php sb_e('Full name') ?>
                                </th>
                                <th data-field="email">
                                    <?php sb_e('Email') ?>
                                </th>
                                <th data-field="user_type">
                                    <?php sb_e('Type') ?>
                                </th>
                                <th data-field="last_activity">
                                    <?php sb_e('Last activity') ?>
                                </th>
                                <th data-field="creation_time" class="sb-active">
                                    <?php sb_e('Registration date') ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="sb-loading"></div>
            </div>
            <?php
        if ($active_user['user_type'] == 'admin') {
            ?>
            <div class="sb-area-settings">
                <div class="sb-top-bar">
                    <div>
                        <h2>
                            <?php sb_e('Settings') ?>
                        </h2>
                        <ul class="sb-menu-wide">
                            <li id="tab-chat" class="sb-active">
                                <?php sb_e('Chat') ?>
                            </li>
                            <li id="tab-notifications">
                                <?php sb_e('Notifications') ?>
                            </li>
                            <li id="tab-users">
                                <?php sb_e('Users') ?>
                            </li>
                            <li id="tab-various">
                                <?php sb_e('Other') ?>
                            </li>
                            <li id="tab-apps">
                                <?php sb_e('Embed Code') ?>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <a class="sb-btn sb-save-changes sb-icon">
                            <i class="sb-icon-check"></i><?php sb_e('Save Changes') ?>
                        </a>
                    </div>
                </div>
                <div class="sb-scroll-area">
                    <style>
                    xmp {
                    background-color: #efefef;
                    padding: 10px;
                    color: #717171;
                    border-radius: 10px;
                    font-size: 14px;
                    white-space: normal;
                    }
                    xmp {
                    margin-bottom: 20px;
                    }
                    @media only screen and (max-width: 600px) {
                    xmp {
                    font-size: 8.5px;
                    }
                    }
                    </style>
                    <div class="sb-active">
                        <?php sb_populate_settings('chat', $sb_settings) ?>
                    </div>
                    <div>
                        <?php sb_populate_settings('notifications', $sb_settings) ?>
                    </div>
                    <div>
                        <?php sb_populate_settings('users', $sb_settings) ?>
                    </div>
                    <div>
                        <?php sb_populate_settings('various', $sb_settings) ?>
                    </div>
                    <div>
                            <div style="width: 57% !important; margin-top: -30px !important; display: block !important;" class="thecode">
                    <xmp><!-- START SUPPORTONSITE CODE --> 
                    <script src="https://supportonsite.co/<?php echo basename($_SERVER['REQUEST_URI']);
?>/js/min/jquery.min.js"></script> <script src="https://supportonsite.co/<?php echo basename($_SERVER['REQUEST_URI']);
?>/js/init.js"></script> <!-- END SUPPORTONSITE CODE --></xmp>
                    </div>
                    </div>
                    <div>
                        <div class="sb-articles-area sb-v-tab">
                            <ul class="sb-nav"></ul>
                            <div class="sb-content" data-article-id="-1">
                                <h2>
                                    <?php sb_e('Article title') ?>
                                    <a class="sb-btn sb-icon sb-add-article">
                                        <i class="sb-icon-plus"></i><?php sb_e('Add new article') ?>
                                    </a>
                                </h2>
                                <div class="sb-input-setting sb-type-text sb-article-title">
                                    <div>
                                        <input type="text" />
                                    </div>
                                </div>
                                <h2>
                                    <?php sb_e('Insert your content') ?>
                                </h2>
                                <div class="sb-input-setting sb-type-textarea sb-article-content">
                                    <div>
                                        <textarea></textarea>
                                    </div>
                                </div>
                                <h2>
                                    <?php sb_e('External link') ?>
                                </h2>
                                <div class="sb-input-setting sb-type-text sb-article-link">
                                    <div>
                                        <input type="text" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="sb-translations sb-v-tab"></div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </main>
        <?php
        sb_profile_box();
        sb_profile_edit_box();
        sb_dialog();
        sb_dialogflow_intent_box();
        sb_updates_box();
        sb_app_box();
        ?>
        <form class="sb-upload-form-admin sb-upload-form" action="#" method="post" enctype="multipart/form-data">
            <input type="file" name="files[]" class="sb-upload-files" multiple />
        </form>
        <div class="sb-info-card"></div>
        <?php
    } else {
        sb_login_box();
    } ?>
        <div class="sb-lightbox-overlay"></div>
        <div class="sb-loading-global sb-loading sb-lightbox"></div>
        <input type="email" name="email" style="display:none" autocomplete="email" />
        <input type="password" name="hidden" style="display:none" autocomplete="new-password" />
    </div>
<?php } ?>