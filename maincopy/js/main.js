'use strict';

(function ($) {

    var main;
    var global;
    var upload_target;
    var admin;
    var timeout = false;
    var previous_search;
    var sb_current_user = false;
    var chat;
    var chat_editor;
    var chat_textarea;
    var chat_header;
    var chat_status;
    var chat_replies;
    var chat_emoji;
    var chat_scroll_area;
    var document_title = document.title;
    var CHAT_SETTINGS;
    var responsive = $(window).width() < 426;
    var today = new Date();

    $.fn.extend({ manualExpandTextarea: function () { var t = this[0]; t.style.height = "auto", t.style.maxHeight = "25px"; window.getComputedStyle(t); t.style.height = t.scrollHeight + "px", t.style.maxHeight = "", $(t).trigger("textareaChanged") }, autoExpandTextarea: function () { var t = this[0]; t.addEventListener("input", function (e) { $(t).manualExpandTextarea() }, !1) } });

    (function () { var t = [].slice; String.prototype.autoLink = function () { var n, a, r, i, c, e, l; return e = /(^|[\s\n]|<[A-Za-z]*\/?>)((?:https?|ftp):\/\/[\-A-Z0-9+\u0026\u2019@#\/%?=()~_|!:,.;]*[\-A-Z0-9+\u0026@#\/%=~()_|])/gi, 0 < (c = 1 <= arguments.length ? t.call(arguments, 0) : []).length ? (i = c[0], n = i.callback, r = function () { var t; for (a in t = [], i) l = i[a], "callback" !== a && t.push(" " + a + "='" + l + "'"); return t }().join(""), this.replace(e, function (t, a, i) { return "" + a + (("function" == typeof n ? n(i) : void 0) || "<a href='" + i + "'" + r + ">" + i + "</a>") })) : this.replace(e, "$1<a href='$2'>$2</a>") } }).call(this);

    var SBF = {

        ajax: function (data, onSuccess = false) {
            if (!('user_id' in data) && currentUser() != false) {
                data['user_id'] = currentUser().id;
            }
            if (typeof SB_LANG != 'undefined') {
                data['language'] = SB_LANG;
            }
            $.ajax({
                method: 'POST',
                url: SB_AJAX_URL,
                data: data
            }).done((response) => {
                let result;
                try {
                    result = JSON.parse(response);
                } catch (e) {
                    console.log(response);
                    SBF.error('Json parse error', `SBF.ajax.${data['function']}`);
                }
                if (result[0] == 'success') {
                    if (onSuccess != false) onSuccess(result[1]);
                } else {
                    SBF.error(result[1], `SBF.ajax.${data['function']}`);
                }
            });
        },

        cors: function (method = 'GET', url, onSuccess) {
            var xhr = new XMLHttpRequest();
            if ('withCredentials' in xhr) {
                xhr.open(method, url, true);
            } else if (typeof XDomainRequest != 'undefined') {
                xhr = new XDomainRequest();
                xhr.open(method, url);
            } else {
                return false;
            }
            xhr.onload = function () {
                onSuccess(xhr.responseText);
            };
            xhr.onerror = function () {
                return false;
            };
            xhr.send();
        },

        null: function (obj) { if (typeof (obj) !== 'undefined' && obj !== null && obj !== 'null' && obj !== false && (obj.length > 0 || typeof (obj) == 'number' || typeof (obj.length) == 'undefined') && obj !== 'undefined') return false; else return true; },

        deactivateAll: function () {
            $(global).find('.sb-popup, .sb-tooltip, .sb-list .sb-menu, .sb-select ul').sbActivate(false);
        },

        deselectAll: function () {
            if (window.getSelection) { window.getSelection().removeAllRanges(); }
            else if (document.selection) { document.selection.empty(); }
        },

        getURL: function (name) {
            return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [, ""])[1].replace(/\+/g, '%20') || "");
        },

        restoreJson: function (value) {
            if (SBF.null(value)) return '';
            return value.replace(/\\n/g, "\n").replace(/\\r/g, "\r").replace(/\\t/g, "\t").replace(/\\f/g, "\f").replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, "\\");
        },

        stringToSlug: function (string) {
            string = string.trim();
            string = string.toLowerCase();
            var from = "åàáãäâèéëêìíïîòóöôùúüûñç·/_,:;";
            var to = "aaaaaaeeeeiiiioooouuuunc------";
            for (var i = 0, l = from.length; i < l; i++) {
                string = string.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
            }
            return string.replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-+/, '').replace(/-+$/, '').replace(/ /g, ' ');
        },

        slugToString: function (string) {
            string = string.replace(/_/g, ' ');
            return string.charAt(0).toUpperCase() + string.slice(1);
        },

        random: function () {
            let chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let result = '';
            for (var i = 5; i > 0; --i) result += chars[Math.floor(Math.random() * 62)];
            return result;
        },

        isAgent: function (user_type) {
            return user_type == 'agent' || user_type == 'admin' || user_type == 'bot';
        },

        beautifyTime: function (datetime) {
            if (datetime == '0000-00-00 00:00:00') return '';
            let date = new Date(datetime.replace(' ', 'T'));
            let message_date = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds()));
            let diff_days = Math.round(((new Date()) - message_date) / (1000 * 60 * 60 * 24));
            let days = [sb_('Sunday'), sb_('Monday'), sb_('Tuesday'), sb_('Wednesday'), sb_('Thursday'), sb_('Friday'), sb_('Saturday')];
            if (diff_days < 1) {
                return message_date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (diff_days < 8) {
                return days[message_date.getDay()];
            } else {
                return message_date.toLocaleDateString();
            }
        },

        updateUsersActivity: function (user_id, return_user_id, onSuccess) {
            SBF.ajax({
                function: 'update-users-last-activity',
                user_id: user_id,
                return_user_id: return_user_id,
                check_slack: !admin && CHAT_SETTINGS['slack-active']
            }, (response) => {
                if (response === 'online') {
                    onSuccess('online');
                } else {
                    onSuccess('offline');
                }
            });
        },

        search: function (search, searchFunction) {
            search = search.toLowerCase();
            if (search == previous_search) {
                return;
            }
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                previous_search = search;
                searchFunction();
            }, 200);
        },

        searchClear: function (i, onSuccess) {
            let search = $(i).next().val();
            if (search != '') {
                onSuccess();
                $(i).next().val('');
            }
        },

        error: function (message, function_name) {
            if (message instanceof Error) message = message.message;
            if (message[message.length - 1] == '.') message = message.slice(0, -1);
            if (message.indexOf('Support Board Error') > -1) message = message.replace('Support Board Error ', '').replace(']:', ']');
            if (admin) SBF.dialog(`[Error] [${function_name}] ${message}. Check the console for more details.`, 'info');
            $(global).find('.sb-loading').sbLoading(false);
            throw new Error(`Support Board Error [${function_name}]: ${message}.`);
        },

        login: function (button) {
            if (!$(button).sbLoading()) {
                let area = $(button).closest('.sb-rich-login');
                let email = $(area).find('#email input').val();
                let password = $(area).find('#password input').val();
                if (email == '' || password == '') {
                    $(area).find('.sb-info').html(sb_('Please insert email and password.')).sbActivate();
                } else {
                    SBF.ajax({
                        function: 'login',
                        email: email,
                        password: password
                    }, (response) => {
                        if (response != false && Array.isArray(response)) {
                            if (!admin && this.isAgent(response[0]['user_type'])) {
                                $(area).find('.sb-info').html(sb_('You can not sign in as agent.')).sbActivate();
                            } else {
                                storage('login', response[1]);
                                if (admin) {
                                    location.reload();
                                } else {
                                    currentUser(new SBUser(response[0]));
                                    $(main).removeClass('sb-init-form-active');
                                    $(area).remove();
                                    SBChat.initialized = false;
                                    SBChat.initChat();
                                }
                            }
                        } else {
                            $(area).find('.sb-info').html(sb_('Invalid email or password.')).sbActivate();
                        }
                        $(button).sbLoading(false);
                    });
                    $(area).find('.sb-info').html('').sbActivate(false);
                    $(button).sbLoading(true);
                }
            }
        },

        logout: function () {
            SBChat.stopRealTime();
            SBF.ajax({
                function: 'logout'
            }, () => {
                storage('login', '');
                location.reload();
            });
        },

        reset: function () {
            localStorage.removeItem('support-board');
            this.logout();
        },

        dialog: function (text, type, onConfirm) {
            if (admin) {
                sbDialogAdmin(text, type, onConfirm);
            }
        },

        lightbox: function (content) {
            $(main).find('.sb-lightbox').sbActivate().find(' > div').html(content);
        },

        storage: function (key, value = false) {
            let settings = localStorage.getItem('support-board');
            if (settings === null) {
                settings = {};
            } else {
                settings = JSON.parse(settings);
            }
            if (value === false) {
                if (key in settings) {
                    return settings[key];
                } else {
                    return false;
                }
            } else {
                if (value == '') {
                    delete settings[key];
                } else {
                    settings[key] = value;
                }
                localStorage.setItem('support-board', JSON.stringify(settings));
            }
        }
    }

    function sbDelta(e) {
        let delta = e.originalEvent.wheelDelta;
        if (typeof delta == 'undefined') {
            delta = e.originalEvent.deltaY;
        }
        if (typeof delta == 'undefined') {
            delta = e.originalEvent.detail;
        }
        return delta;
    }

    function storage(key, value = false) {
        return SBF.storage(key, value);
    }

    function sb_(text) {
        if (SBF.null(CHAT_SETTINGS)) return text;
        let translations = CHAT_SETTINGS['translations'];
        if (translations != false && text in translations) {
            return translations[text] == '' ? text : translations[text];
        } else {
            return text;
        }
    }

    function currentUser(value) {
        if (typeof value == 'undefined') {
            return window.sb_current_user;
        } else {
            window.sb_current_user = value;
        }
    }

    function getCurrentUser(db = false, onSuccess) {
        SBF.ajax({
            function: 'get-active-user',
            storage: storage('login'),
            db: db,
            login_app: SBApps.login()
        }, (response) => {
            if (!response) {
                onSuccess();
                return false;
            } else {
                if ('user_type' in response) {
                    if (!admin && SBF.isAgent(response['user_type'])) {
                        let message = 'You are logged in as both agent and user. Logout or use another browser, Incognito Mode to login as user. You can force a logout by execute the function SBF.reset() in the console.';
                        if (!storage('double-lougin-alert')) {
                            storage('double-lougin-alert', true);
                            alert(message);
                        }
                        console.warn('Support Board: ' + message);
                    } else {
                        currentUser(new SBUser(response));
                        onSuccess();
                    }
                }
            }
        });
    }

    window.SBF = SBF;
    window.sb_current_user = sb_current_user;

    $.fn.sbActivate = function (show = true) {
        if (show) {
            $(this).addClass('sb-active');
        } else {
            $(this).removeClass('sb-active');
        }
        return this;
    };

    $.fn.sbActive = function () {
        return $(this).hasClass('sb-active');
    };

    $.fn.sbLoading = function (value = 'check') {
        if (value == 'check') {
            return $(this).hasClass('sb-loading');
        } else if (value == true) {
            $(this).addClass('sb-loading');
        } if (value == false) {
            $(this).removeClass('sb-loading');
        }
        return this;
    }

    $.fn.sbTogglePopup = function (button) {
        var showed = true;
        if ($(this).sbActive()) {
            $(this).sbActivate(false);
            $(global).removeClass('sb-popup-active');
            showed = false;
        } else {
            $(global).addClass('sb-popup-active');
            $(global).find('.sb-popup').sbActivate(false);
            $(this).css('left', $(button).offset().left + 15).sbActivate();
            SBF.deselectAll();
        }
        return showed;
    };

    $.fn.sbUploadFiles = function (onSuccess) {
        var files = $(this).prop('files');
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var form_data = new FormData();
            form_data.append('file', file);
            jQuery.ajax({
                url: SB_URL + '/include/upload.php',
                cache: false,
                contentType: false,
                processData: false,
                data: form_data,
                type: 'POST',
                success: function (response) {
                    onSuccess(response);
                }
            });
        }
        $(this).value = '';
    }

    class SBUser {
        constructor(details = {}, extra = {}) {
            this.details = details;
            this.extra = extra;
            this.conversations = [];
        }

        get id() {
            return this.get('id') == '' ? this.get('user_id') : this.get('id');
        }

        get(id) {
            if (id == 'full_name') return 'first_name' in this.details ? this.details['first_name'] + ' ' + this.details['last_name'] : '';
            if (id in this.details && !SBF.null(this.details[id])) return this.details[id];
            else return '';
        }

        getExtra(id) {
            if (id in this.extra && !SBF.null(this.extra[id])) return this.extra[id];
            else return '';
        }

        set(id, value) {
            this.details[id] = value;
        }

        setExtra(id, value) {
            this.extra[id] = value;
        }

        update(onSuccess) {
            SBF.ajax({
                function: 'get-user',
                user_id: this.id,
                extra: true
            }, (response) => {
                for (var i = 0; i < response['details'].length; i++) {
                    this.setExtra(response['details'][i]['slug'], response['details'][i]);
                }
                delete response['details'];
                this.details = response;
                onSuccess();
            });
        }

        getConversations(onSuccess, exclude_id) {
            SBF.ajax({
                function: 'get-user-conversations',
                user_id: this.id,
                exclude_id: exclude_id
            }, (response) => {
                let conversations = [];
                for (var i = 0; i < response.length; i++) {
                    let conversation = new SBConversation([new SBMessage(response[i])]);
                    conversation.set('id', response[i]['conversation_id']);
                    conversation.set('conversation_status_code', response[i]['conversation_status_code']);
                    conversations.push(conversation);
                }
                this.conversations = conversations;
                onSuccess(conversations);
            });
        }

        getConversationsCode(conversations = false) {
            let code = '';
            if (conversations == false) conversations = this.conversations;
            for (var i = 0; i < conversations.length; i++) {
                if (conversations[i] instanceof SBConversation) {
                    code += `<li data-conversation-status="${conversations[i].get('conversation_status_code')}" data-conversation-id="${conversations[i].id}">${conversations[i].getCode()}</li>`;
                } else {
                    SBF.error('Conversation not of type SBConversation', 'SBUser.getConversationsCode');
                }
            }
            if (code == '') {
                code = `<p class="sb-no-results">${sb_('No conversations found.')}</p>`;
            }
            return code;
        }

        getFullConversation(conversation_id, onSuccess) {
            SBF.ajax({
                function: 'get-conversation',
                conversation_id: conversation_id
            }, (response) => {
                let messages = [];
                for (var i = 0; i < response['messages'].length; i++) {
                    messages.push(new SBMessage(response['messages'][i]));
                }
                onSuccess(new SBConversation(messages, response['details']));
            });
        }

        addConversation(conversation) {
            if (conversation instanceof SBConversation) {
                let conversation_id = conversation.id;
                let is_new = true;
                for (var i = 0; i < this.conversations.length; i++) {
                    if (this.conversations[i].id == conversation_id) {
                        this.conversations[i] = conversation;
                        is_new = false;
                        break;
                    }
                }
                if (is_new) {
                    this.conversations.unshift(conversation);
                }
            } else {
                SBF.error('Conversation not of type SBConversation', 'SBUser.addConversation');
            }
        }

        isConversationsEmpty() {
            return this.conversations.length == 0;
        }

        isExtraEmpty() {
            return Object.keys(this.extra).length === 0 && this.extra.constructor === Object;
        }

        delete(onSuccess) {
            SBF.ajax({
                function: 'delete-user',
                user_id: this.id
            }, (response) => {
                $(document).trigger('SBUserDeleted', this.id);
                onSuccess();
                return true;
            });
        }
    }
    window.SBUser = SBUser;

    class SBMessage {
        constructor(details) {
            this.details = details;
            if ('first_name' in this.details) {
                this.details['full_name'] = this.details['first_name'] + ' ' + this.details['last_name'];
            }
            this.set('payload', this.get('payload') != '' ? JSON.parse(this.get('payload')) : {});
        }

        get id() {
            return this.get('id');
        }

        get(id) {
            if (id in this.details && !SBF.null(this.details[id])) return this.details[id];
            else return '';
        }

        set(id, value) {
            this.details[id] = value;
        }

        payload(value = false) {
            if (value !== false) {
                let payload = this.get('payload');
                payload[key] = value;
                this.set('payload', payload);
            }
            return this.get('payload');
        }

        getCode() {
            let agent = SBF.isAgent(this.details['user_type']);
            let admin_menu = admin && agent ? `<i class="sb-menu-btn sb-icon-menu"></i><ul class="sb-menu">${CHAT_SETTINGS['dialogflow-active'] ? `<li data-value="bot">${sb_('Send to Dialogflow')}</li>` : ''}<li data-value="delete">${sb_('Delete')}</li></ul>` : '';
            let message = this.render(this.details['message']);
            let attachments = !SBF.null(this.details['attachments']) ? JSON.parse(this.details['attachments']) : [];
            let attachments_code = '';
            let media_code = '';
            let thumb = admin || (agent && !CHAT_SETTINGS['hide-agents-thumb']) || (!agent && CHAT_SETTINGS['display-users-thumb']) ? `<div class="sb-thumb"><img src="${this.details['profile_image']}"><div>${this.details['full_name']}</div></div>` : '';
            let css = (!agent ? 'sb-right' : '') + (thumb == '' ? '' : ' sb-thumb-active');
            if (message == '' && attachments.length == 0) {
                return '';
            }

            if (agent) {
                let shortcodes = message.match(/\[.+?\]/g) || [];
                let rich = false;
                for (var i = 0; i < shortcodes.length; i++) {
                    let settings = SBRichMessages.shortcode(shortcodes[i]);
                    let rich_message = SBRichMessages.generate(settings[1], settings[0]);
                    if (rich_message != false) {
                        if (message.charAt(0) != '[') {
                            rich_message = rich_message.replace('sb-rich-message', 'sb-rich-message sb-margin-top');
                        }
                        if (message.charAt(message.length - 1) != ']') {
                            rich_message = rich_message.replace('sb-rich-message', 'sb-rich-message sb-margin-bottom');
                        }
                        message = message.replace(shortcodes[i], rich_message);
                        rich = true;
                    }
                }
                if (rich) {
                    css += ' sb-rich-cnt';
                }
            }

            if (attachments.length) {
                attachments_code = '<div class="sb-message-attachments">';
                for (var i = 0; i < attachments.length; i++) {
                    if (/.jpg|.png|.gif/.test(attachments[i][1])) {
                        media_code += `<div class="sb-image"><img src="${attachments[i][1]}" /></div>`;
                    } else {
                        attachments_code += `<a rel="noopener" target="_blank" href="${attachments[i][1]}">${attachments[i][0]}</a>`;
                    }
                }
                attachments_code += '</div>';
            }

            return `<div data-id="${this.details['id']}" class="${css}">${thumb}<div class="sb-cnt"><div class="sb-message${media_code != '' && message == '' ? ' sb-message-media' : ''}">${message}${media_code}</div>${attachments_code}<div class="sb-time">${SBF.beautifyTime(this.details['creation_time'])}</div></div>${admin_menu}</div>`;
        }

        render(message = false) {
            let len = message.length;
            if (message === false) {
                message = '' + this.details['message'];
            }

            let codes = message.match(/```([\s\S]*)```/) || [];
            for (var i = 0; i < codes.length; i++) {
                message = message.replace(codes[i], '[code-' + i + ']');
            }

            message = message.replace(/(?:\r\n|\r|\n)/g, '<br>');

            message = message.replace(/\*([^\**]+)\*/g, "<b>$1</b>");

            message = message.replace(/\_([^\__]+)\_/g, "<i>$1</i>");

            message = message.replace(/\_([^\__]+)\_/g, "<i>$1</i>");

            message = message.replace(/\~([^\~~]+)\~/g, "<del>$1</del>");

            message = message.replace(/\`([^\``]+)\`/g, "<code>$1</code>");

            message = message.replace(/u([0-9a-f]{4,5})/mi, '&#x$1;');

            if (((len == 6 || len == 5) && message.startsWith('&#x')) || len < 3 && message.match(/(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|\ud83c[\ude32-\ude3a]|\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])/)) {
                message = `<span class="emoji-large">${message}</span>`;
            }

            if (message.indexOf('http') > -1) {
                message = message.autoLink({ target: '_blank' });
            }

            for (var i = 0; i < codes.length; i++) {
                message = message.replace('[code-' + i + ']', '<pre>' + $.trim($.trim(codes[i]).substr(3, codes[i].length - 6).replace(/(?:\r\n|\r|\n)/g, '<br>'))) + '</pre>';
            }

            return message;
        }
    }
    window.SBMessage = SBMessage;

    class SBConversation {
        constructor(messages, details) {
            this.details = SBF.null(details) ? {} : details;
            if (Array.isArray(messages)) {
                this.messages = [];
                if (messages.length) {
                    if (messages[0] instanceof SBMessage) {
                        this.messages = messages;
                    } else {
                        SBF.error('Messages not of type SBMessages', 'SBConversation.constructor');
                    }
                }
            } else {
                SBF.error('Message array not of type Array', 'SBConversation.constructor');
            }
        }

        get id() {
            return this.get('id');
        }

        get(id) {
            if (id in this.details && !SBF.null(this.details[id])) {
                return this.details[id];
            }
            if (id == 'title') {
                if (!SBF.null(this.details['first_name'])) {
                    return this.details['first_name'] + ' ' + this.details['last_name'];
                } else if (this.messages.length) {
                    return this.messages[0].get('full_name');
                }
            }
            return '';
        }

        set(id, value) {
            this.details[id] = value;
        }

        getMessage(id) {
            for (var i = 0; i < this.messages.length; i++) {
                if (this.messages[i].id == id) {
                    return this.messages[i];
                }
            }
            return false;
        }

        updateMessage(id, value) {
            for (var i = 0; i < this.messages.length; i++) {
                if (this.messages[i].id == id) {
                    this.messages[i] = value;
                    return true;
                }
            }
            return false;
        }

        deleteMessage(id) {
            for (var i = 0; i < this.messages.length; i++) {
                if (this.messages[i].id == id) {
                    this.messages.splice(i, 1);
                    return true;
                }
            }
            return false;
        }

        getLastMessage() {
            let count = this.messages.length;
            return count > 0 ? this.messages[count - 1] : false;
        }

        addMessages(messages) {
            if (Array.isArray(messages)) {
                for (var i = 0; i < messages.length; i++) {
                    if (messages[i] instanceof SBMessage) {
                        this.messages.push(messages[i]);
                    }
                }
            } else {
                if (messages instanceof SBMessage) {
                    this.messages.push(messages);
                } else {
                    SBF.error('Messages not of type SBMessages', 'SBConversation.addMessages()');
                }
            }
        }

        getCode() {
            if (this.messages.length) {
                let message = this.messages[0].get('message');
                if (message.length > 114) {
                    message = message.substr(0, 114) + ' ...';
                }
                return `<div class="sb-conversation-item" data-user-id="${this.messages[0].get('user_id')}"><img src="${this.messages[0].get('profile_image')}"><div><span class="sb-name">${this.messages[0].get('full_name')}</span><span class="sb-time">${SBF.beautifyTime(this.messages[0].get('creation_time'))}</span></div><div class="sb-message">${message}</div></div>`;
            }
            return '';
        }
    }
    window.SBConversation = SBConversation;

    var SBChat = {
        replies_list: [],
        rich_messages_list: ['buttons', 'select', 'inputs', 'table', 'list'],
        emoji_options: { 'range': 0, 'range_limit': 48, 'list': [], 'list_now': [], 'touch': false },
        initialized: false,
        editor_listening: false,
        conversation: false,
        is_busy: false,
        chat_open: false,
        real_time: false,
        agent_id: -1,
        agent_online: false,
        user_online: false,
        expanded: false,
        main_header: true,
        start_header: false,
        desktop_notifications: false,
        flash_notifications: false,
        datetime_last_message: '2000-01-01 00:00:00',
        datetime_last_message_conversation: '2000-01-01 00:00:00',
        audio: false,
        tab_active: true,
        notifications: [],
        typing_settings: { typing: false, sent: false, timeout: false },
        email_sent: false,
        dialogflow_token: storage('dialogflow-token') == false ? -1 : storage('dialogflow-token'),
        timetable: true,
        dashboard: false,
        articles: false,
        slack_channel: [-1, -1],
        skip: false,

        sendMessage: function (user_id = -1, message = '', attachments = []) {
            if (SBChat.conversation == false) {
                SBChat.newConversation(admin ? 1 : 2, user_id, message, attachments);
                return;
            }
            if (user_id == -1) {
                user_id = admin ? SB_CURRENT_AGENT['id'] : currentUser().id;
            }
            if (message == '' && attachments.length == 0) {
                $(chat_editor).find('.sb-attachments > div').each(function () {
                    attachments.push([$(this).attr('data-name'), $(this).attr('data-url')]);
                });
                message = $(chat_textarea).val().trim();
            }

            if (message != '' || attachments.length) {
                let conversation_status = this.conversation.getLastMessage() != false ? admin ? 1 : 2 : -1;
                let is_user = user_id != CHAT_SETTINGS['bot-id'];
                this.busy(true);
                if (is_user) {
                    $(chat_textarea).val('').css('height', '');
                    $(chat_editor).find('.sb-attachments').html('');
                }
                SBF.ajax({
                    function: 'send-message',
                    user_id: user_id,
                    conversation_id: this.conversation.id,
                    message: message,
                    attachments: attachments,
                    conversation_status: conversation_status
                }, (response) => {

                    if ((admin && !this.user_online) || (!admin && !this.agent_online)) {
                        this.update();
                    }

                    if (!admin && this.dashboard && user_id == CHAT_SETTINGS['bot-id']) {
                        this.updateConversations();
                    }

                    if (!admin && is_user && CHAT_SETTINGS['follow'] && today.getDate() != storage('email')) {
                        this.followUp();
                    }

                    if (!this.email_sent && is_user && ((!admin && CHAT_SETTINGS['notify-agent-email']) || (admin && CHAT_SETTINGS['notify-user-email'] && currentUser().get('email') != ''))) {
                        SBF.ajax({
                            function: 'create-email',
                            recipient_id: admin ? currentUser().id : this.lastAgent(false) != false ? this.lastAgent(false)['user_id'] : -1,
                            sender_name: admin ? SB_CURRENT_AGENT['full_name'] : currentUser().get('full_name'),
                            sender_profile_image: admin ? SB_CURRENT_AGENT['profile_image'] : currentUser().get('profile_image'),
                            message: message,
                            attachments: attachments
                        }, () => {
                            this.email_sent = true;
                        });
                    }

                    if (response === true) {

                        if (is_user) {
                            this.sendBotMessage(message);
                        }

                        if (!admin && is_user && currentUser().get('user_type') == 'visitor') {
                            SBF.ajax({ function: 'update-user-to-lead', user_id: user_id }, () => {
                                currentUser().set('user_type', 'lead');
                                if (CHAT_SETTINGS['slack-active']) {
                                    this.slackMessage(user_id, currentUser().get('full_name'), currentUser().get('profile_image'), message, attachments);
                                }
                            });
                        } else if (CHAT_SETTINGS['slack-active'] && !this.skip) {
                            this.slackMessage(currentUser().id, (is_user ? currentUser().get('full_name') : CHAT_SETTINGS['bot-name']), (is_user ? currentUser().get('profile_image') : CHAT_SETTINGS['bot-image']), message, attachments);
                        }

                        if (this.timetable && is_user && !admin && CHAT_SETTINGS['timetable'] && !CHAT_SETTINGS['office-hours']) {
                            setTimeout(() => {
                                if (this.conversation != false) {
                                    this.sendMessage(CHAT_SETTINGS['bot-id'], '[timetable]');
                                    this.scrollBottom();
                                    this.timetable = false;
                                }
                            }, 5000);
                        }

                        if (this.articles && !admin && CHAT_SETTINGS['articles'] && !CHAT_SETTINGS['office-hours'] && !this.isInitDashboard()) {
                            setTimeout(() => {
                                if (this.conversation != false) {
                                    this.sendMessage(CHAT_SETTINGS['bot-id'], '[articles]');
                                    this.scrollBottom();
                                    this.articles = false;
                                }
                            }, 5000);
                        }
                    }

                    $(document).trigger('SBMessageSent', { conversation_id: this.conversation.id, conversation_status: conversation_status, message: message, attachments: attachments });
                    if (this.skip) this.skip = false;
                    this.busy(false);
                });
            }
        },

        sendBotMessage: function (message) {
            if (CHAT_SETTINGS['dialogflow-active'] && !admin && (!CHAT_SETTINGS['bot-office-hours'] || !CHAT_SETTINGS['office-hours'])) {
                setTimeout(() => {
                    SBF.ajax({
                        function: 'send-bot-message',
                        conversation_id: this.conversation.id,
                        message: message,
                        token: this.dialogflow_token
                    }, (response) => {
                        if (response['status'] == 'success-bot') {
                            if (this.dialogflow_token != response['token']) {
                                this.dialogflow_token = response['token'];
                                storage('dialogflow-token', response['token']);
                            }

                            if (CHAT_SETTINGS['slack-active'] && 'messages' in response && Array.isArray(response['messages'])) {
                                for (var i = 0; i < response['messages'].length; i++) {
                                    this.slackMessage(currentUser().id, CHAT_SETTINGS['bot-name'], CHAT_SETTINGS['bot-image'], response['messages'][i]['message'], response['messages'][i]['attachments']);
                                }
                            }
                        }
                    });
                }, CHAT_SETTINGS['bot-delay']);
            }
        },

        initChat: function () {
            if (!admin && CHAT_SETTINGS['popup'] && !storage('popup')) {
                this.popup();
            }
            if (!admin && CHAT_SETTINGS['privacy'] && !CHAT_SETTINGS['registration-required'] && !storage('privacy-approved')) {
                this.privacy();
                return;
            }
            if (typeof Notification !== 'undefined' && (CHAT_SETTINGS['desktop-notifications'] == 'all' || (!admin && CHAT_SETTINGS['desktop-notifications'] == 'users') || (admin && CHAT_SETTINGS['desktop-notifications'] == 'agents'))) {
                this.desktop_notifications = true;
            }
            if (CHAT_SETTINGS['flash-notifications'] == 'all' || (!admin && CHAT_SETTINGS['flash-notifications'] == 'users') || (admin && CHAT_SETTINGS['flash-notifications'] == 'agents')) {
                this.flash_notifications = true;
            }
            getCurrentUser(true, () => {
                let active = currentUser() !== false;
                let user_type = active ? currentUser().get('user_type') : false;
                if (CHAT_SETTINGS['registration-required'] && (!active || user_type == 'visitor' || user_type == 'lead')) {
                    this.registration();
                    return;
                }
                if ((CHAT_SETTINGS['visitors-registration'] || CHAT_SETTINGS['welcome']) && !active) {
                    this.addUserAndLogin(() => {
                        this.welcome();
                        this.finalizeInit();
                    });
                } else if (this.conversation == false && active) {
                    this.populateConversations();
                } else {
                    this.finalizeInit();
                }
                if (CHAT_SETTINGS['header-name'] && active && user_type == 'user') {
                    $(chat_header).find('.sb-title').html(`${sb_('Hello')} ${currentUser().get('full_name')}!`);
                }
                if (active) {
                    this.welcome();
                }
                setInterval(() => {
                    this.updateConversations();
                    this.updateUsersActivity();
                }, 10000);
                this.scrollBottom(true);
            });
        },

        finalizeInit: function () {
            if (!this.initialized) {
                $(main).attr('style', '');
                SBF.ajax({
                    function: 'current-url',
                    url: window.location.href
                });
                if (!admin) {
                    if (this.isInitDashboard()) {
                        this.showDashboard();
                    }
                    if (!responsive && window.innerHeight < 760) {
                        $(main).find(' > .sb-body').css('max-height', (window.innerHeight - 130) + 'px');
                    }
                }
                this.initialized = true;
            }
        },

        openChat: function () {
            if (this.initialized) {
                this.populate();
                this.headerAgent();
                this.updateUsersActivity();
                this.startRealTime();
                this.chat_open = true;
                this.popup('close');
                if (this.conversation != false && this.conversation.get('conversation_status_code') != 0) {
                    this.updateNotifications('remove', this.conversation.id);
                }
                $(main).sbActivate();
                $('body').addClass('sb-chat-open');
            }
        },

        openButton: function () {
            this.chat_open = $(main).sbActive();
            if (this.chat_open) {
                $(main).sbActivate(false);
                this.stopRealTime();
                this.chat_open = false;
                $('body').removeClass('sb-chat-open');
            } else {
                this.openChat();
            }
        },

        openConversation: function (conversation_id) {
            currentUser().getFullConversation(conversation_id, (response) => {
                this.setConversation(response);
                this.populate();
                this.hideDashboard();
                this.main_header = false;
                if (this.chat_open && response.get('conversation_status_code') != 0) {
                    this.updateNotifications('remove', conversation_id);
                }
            });
        },

        update: function () {
            if (this.conversation != false) {
                let last_message = this.conversation.getLastMessage();
                SBF.ajax({
                    function: 'get-new-messages',
                    conversation_id: this.conversation.id,
                    datetime: this.datetime_last_message_conversation
                }, (response) => {
                    if (this.conversation != false) {
                        if (Array.isArray(response) && response.length > 0 && (!last_message || last_message.id != response[0]['id'] || last_message.get('message') != response[0]['message'])) {
                            let code = '';
                            let messages = [];

                            for (var i = 0; i < response.length; i++) {
                                let message = new SBMessage(response[i]);
                                this.datetime_last_message_conversation = message.get('creation_time');

                                if (typeof message.payload() !== 'boolean' && 'event' in message.payload() && message.payload()['event'] == 'delete-message') {
                                    if (this.conversation.getMessage(message.id) !== false) {
                                        this.deleteMessage(message.id);
                                    }
                                    break;
                                }

                                if (this.conversation.getMessage(response[i]['id']) == false) {
                                    this.conversation.addMessages(message);
                                    code += message.getCode();
                                } else {
                                    this.conversation.updateMessage(message.id, message);
                                    $(chat).find(`[data-id="${message.id}"]`).replaceWith(message.getCode());
                                }
                                messages.push(message);
                            }
                            $(chat).append(code);

                            let last_message = this.conversation.getLastMessage();
                            let user_type = last_message.get('user_type');
                            if (!admin && this.chat_open && SBF.isAgent(user_type) && user_type != 'bot') {
                                if (last_message.get('message').indexOf('sb-rich-success') == -1) {
                                    this.setConversationStatus(0);
                                }
                                if (CHAT_SETTINGS['follow']) {
                                    clearTimeout(timeout);
                                }
                                CHAT_SETTINGS['dialogflow-active'] = false;
                            }

                            this.headerAgent();
                            if (!this.dashboard) {
                                this.scrollBottom();
                            }
                            setTimeout(function () {
                                $(chat_status).removeClass('sb-status-typing').addClass('sb-status-online').html(sb_('Online'));
                            }, 500);
                            $(document).trigger('SBChatNewMessages', { 'messages': messages });
                        }
                    }
                });
            }
        },

        updateConversations: function () {
            if (currentUser() != false) {
                SBF.ajax({
                    function: 'get-new-user-conversations',
                    datetime: this.datetime_last_message
                }, (response) => {
                    if (response.length) {
                        this.datetime_last_message = response[0]['creation_time'];
                        for (var i = 0; i < response.length; i++) {
                            let conversation_id = response[i]['conversation_id'];
                            let message = new SBMessage(response[i]);
                            let status = SBF.isAgent(response[i]['user_type']) ? 1 : 0;
                            let conversation = new SBConversation([message], { 'id': conversation_id, 'conversation_status_code': status });

                            currentUser().addConversation(conversation);
                            if (this.conversation == false) {
                                this.conversation = conversation;
                            }
                            this.conversation.set('conversation_status_code', status);

                            if (response[i]['user_id'] != currentUser().id && (this.conversation == false || this.conversation.id != conversation_id || !this.chat_open)) {
                                this.updateNotifications('add', conversation_id);
                                if (CHAT_SETTINGS['auto-open']) {
                                    this.openChat();
                                }
                            }

                            if (!this.tab_active) {

                                if (this.desktop_notifications) {
                                    if (Notification.permission !== 'granted') {
                                        Notification.requestPermission();
                                    } else {
                                        let notify = new Notification(message.get('full_name'), {
                                            body: message.get('message'),
                                            icon: message.get('profile_image')
                                        });
                                        notify.onclick = () => {
                                            SBChat.openChat();
                                            window.focus();
                                        }
                                    }
                                }

                                if (this.flash_notifications) {
                                    document.title = sb_('New message ...');
                                }
                            }
                        }
                        if (CHAT_SETTINGS['chat-sound'] && (!this.chat_open || !this.tab_active)) {
                            this.audio.play();
                        }
                        $(main).find('.sb-user-conversations').html(currentUser().getConversationsCode());
                    }
                });
            }
        },

        populate: function () {
            if (this.conversation != false) {
                let code = '';
                for (var i = 0; i < this.conversation.messages.length; i++) {
                    code += this.conversation.messages[i].getCode();
                }
                $(chat).html(code);
                this.scrollBottom();
                $(document).trigger('SBChatPopulateComplete', this.conversation);
            } else if (currentUser() != false && !currentUser().isConversationsEmpty()) {
                this.showDashboard();
            }
        },

        populateConversations: function () {
            if (currentUser() != false) {
                currentUser().getConversations((response) => {
                    let count = response.length;
                    if (count) {
                        this.datetime_last_message = response[0]['messages'][0].get('creation_time');
                        for (var i = 0; i < count; i++) {
                            if (response[i].get('conversation_status_code') == 1) {
                                this.updateNotifications('add', response[i].id);
                            }
                        }
                        $(main).removeClass('sb-no-conversations');
                        $(main).find('.sb-user-conversations').html(currentUser().getConversationsCode());
                    }
                    if (!this.initialized && count == 1 && !this.isInitDashboard()) {
                        this.openConversation(currentUser().conversations[0].id);
                    }
                    this.finalizeInit();
                });
            }
        },

        newConversation: function (status_code, user_id = -1, message = '', attachments = []) {
            if (currentUser() != false) {
                SBF.ajax({
                    function: 'new-conversation',
                    status_code: status_code
                }, (response) => {
                    if (response == 'user-not-found') {
                        this.addUserAndLogin(() => {
                            this.newConversation(status_code, user_id, message, attachments);
                        });
                        return;
                    }
                    let conversation = new SBConversation([], response['details']);
                    this.setConversation(conversation);
                    this.sendMessage(user_id, message, attachments);
                    $(document).trigger('SBChatNewConversationCreated', conversation);
                });
            } else {
                SBF.error('currentUser() not setted', 'SBChat.newConversation');
            }
        },

        setConversation: function (conversation) {
            if (conversation instanceof SBConversation) {
                this.conversation = conversation;
                this.datetime_last_message_conversation = this.conversation.getLastMessage() == false ? '2000-01-01 00:00:00' : this.conversation.getLastMessage().get('creation_time');
            } else {
                SBF.error('Value not of type SBConversation', 'SBChat.setConversation');
            }
        },

        startRealTime: function () {
            this.stopRealTime();
            this.real_time = setInterval(() => {
                if (!admin || (admin && this.user_online)) {
                    this.update();
                }
                this.typing(admin ? (currentUser() != false ? currentUser().id : -1) : this.agent_id, 'check');
            }, 1000);
        },

        stopRealTime: function () {
            clearInterval(this.real_time);
        },

        updateUsersActivity: function () {
            if (currentUser() != false) {
                SBF.updateUsersActivity(currentUser().id, this.agent_id, (response) => {
                    if (!this.typing_settings['typing']) {
                        if (response == 'online' || this.agent_id == CHAT_SETTINGS['bot-id']) {
                            $(chat_status).addClass('sb-status-online').html(sb_('Online'));
                            this.agent_online = true;
                        } else {
                            $(chat_status).removeClass('sb-status-online').html(sb_('Away'));
                            this.agent_online = false;
                        }
                    }
                });
            }
        },

        busy: function (value) {
            $(chat_editor).find('.sb-loader').sbActivate(value);
            this.is_busy = value;
        },

        initEditorBoxes: function () {
            SBF.ajax({
                function: 'saved-replies'
            }, (response) => {
                if (Array.isArray(response)) {
                    let code = '';
                    if (response.length > 0 && response[0]['reply-name'] != '') {
                        this.replies_list = response;
                        for (var i = 0; i < response.length; i++) {
                            code += `<li><div>${response[i]['reply-name']}</div><div>${response[i]['reply-text']}</div></li>`;
                        }
                    } else {
                        code = `<p class="sb-no-results">${sb_('No saved replies found. Add new replies from the Settings area.')}</p>`;
                    }
                    $(chat_replies).find('.sb-replies-list > ul').html(code);
                }
            });
        },

        headerAgent: function () {
            if (!admin && !this.dashboard && this.conversation != false && (this.agent_id == -1 || (SBF.isAgent(this.conversation.getLastMessage().get('user_type')) && this.conversation.getLastMessage().get('user_id') != this.agent_id))) {
                let agent = this.lastAgent();
                if (agent != false) {
                    this.agent_id = agent['user_id'];
                    if (this.start_header == false) {
                        this.start_header = [$(chat_header).html(), $(chat_header).attr('class')];
                    }
                    $(chat_header).addClass('sb-header-agent').removeClass('sb-header-main sb-header-brand').attr('data-agent-id', this.agent_id).html(`<div class="sb-dashboard-btn sb-icon-arrow-left"></div><div class="sb-profile"><img src="${agent['profile_image']}" /><div><span class="sb-name">${agent['full_name']}</span><span class="sb-status">${sb_('Away')}</span></div></div>`);
                    chat_status = $(chat_header).find('.sb-status');
                    this.main_header = false;
                    this.updateUsersActivity();
                }
            }
        },

        lastAgent: function (bot = true) {
            let agent = false;
            if (this.conversation != false) {
                for (var i = this.conversation.messages.length - 1; i > -1; i--) {
                    let user_type = this.conversation.messages[i].get('user_type');
                    if (SBF.isAgent(user_type) && (bot || (!bot && user_type != 'bot'))) {
                        agent = { user_id: this.conversation.messages[i].get('user_id'), full_name: this.conversation.messages[i].get('full_name'), profile_image: this.conversation.messages[i].get('profile_image') };
                        break;
                    }
                }
            }
            return agent;
        },

        scrollBottom: function (top = false) {
            setTimeout(() => {
                $(chat_scroll_area).scrollTop(top ? 0 : $(chat_scroll_area)[0].scrollHeight);
                this.scrollHeader();
            }, 20);
        },

        scrollHeader: function () {
            if (this.main_header && this.dashboard) {
                let scroll = $(chat_scroll_area).scrollTop();
                if (scroll > -1 && scroll < 1000) {
                    $(chat_header).find('.sb-content').css({ 'opacity': (1 - (scroll / 500)), 'top': (scroll / 10 * -1) + 'px' });
                };
            }
        },

        showDashboard: function () {
            if (!admin) {
                $(main).addClass('sb-dashboard-active');
                $(chat_header).removeClass('sb-header-agent sb-header-panel');
                if (this.start_header != false) {
                    $(chat_header).html(this.start_header[0]).addClass(this.start_header[1]);
                }
                $(chat_scroll_area).find(' > div').sbActivate(false);
                $(main).find('.sb-dashboard').sbActivate();
                this.populateConversations();
                this.conversation = false;
                this.agent_id = -1;
                this.stopRealTime();
                this.dashboard = true;
                this.main_header = true;
                this.scrollBottom(true);
            }
        },

        hideDashboard: function () {
            if (!admin) {
                $(chat).sbActivate();
                $(main).removeClass('sb-dashboard-active').find('.sb-dashboard').sbActivate(false);
                this.dashboard = false;
                this.headerAgent();
                this.scrollHeader(0);
                if (this.chat_open) {
                    this.startRealTime();
                }
            }
        },

        showPanel: function (name, title) {
            let panel = $(chat_scroll_area).find(' > .sb-panel-' + name);
            if (panel.length) {
                $(chat_scroll_area).find(' > div').sbActivate(false);
                $(panel).sbActivate();
                if (this.start_header == false) {
                    this.start_header = [$(chat_header).html(), $(chat_header).attr('class')];
                }
                $(chat_header).attr('class', 'sb-header sb-header-panel').html(`${sb_(title)}<div class="sb-dashboard-btn sb-icon-close"></div>`);
                this.dashboard = true;
            }
        },

        clear: function () {
            this.conversation = false;
            $(chat).html('');
        },

        updateNotifications: function (action = 'add', conversation_id) {
            if (action == 'add' && !this.notifications.includes(conversation_id)) {
                this.notifications.push(conversation_id);
            }
            if (action == 'remove') {
                for (var i = 0; i < this.notifications.length; i++) {
                    if (this.notifications[i] == conversation_id) {
                        this.notifications.splice(i, 1);
                        this.setConversationStatus(0);
                        break;
                    }
                }
            }
            let count = this.notifications.length;
            $(main).find('.sb-chat-btn span').attr('data-count', count).html(count > -1 ? count : 0);
        },

        setConversationStatus: function (status) {
            SBF.ajax({
                function: 'update-conversation-status',
                conversation_id: this.conversation.id,
                status: status
            }, () => {
                this.conversation.set('conversation_status_code', status);
            });
        },

        typing: function (user_id, action = 'check') {
            if (this.conversation != false) {
                if (action == 'check' && user_id != -1 && user_id != CHAT_SETTINGS['bot-id'] && (this.agent_online || (admin && this.user_online))) {
                    SBF.ajax({
                        function: 'is-typing',
                        user_id: user_id,
                        conversation_id: this.conversation.id
                    }, (response) => {
                        if (response && !this.typing_settings['typing']) {
                            if (!admin) {
                                $(chat_status).addClass('sb-status-typing').html(sb_('Typing'));
                            }
                            this.typing_settings['typing'] = true;
                            $(document).trigger('SBTyping', true);
                        } else if (!response && this.typing_settings['typing']) {
                            if (!admin) {
                                $(chat_status).removeClass('sb-status-typing').addClass('sb-status-online').html(sb_('Online'));
                            }
                            this.typing_settings['typing'] = false;
                            $(document).trigger('SBTyping', false);
                        }
                    });
                }
                if (action == 'set') {
                    if (!this.typing_settings['sent']) {
                        this.typing_settings['sent'] = true;
                        SBF.ajax({
                            function: 'set-typing',
                            user_id: user_id,
                            conversation_id: this.conversation.id
                        });
                        this.typing(user_id, 'set');
                    } else {
                        clearTimeout(this.typing_settings['timeout']);
                        this.typing_settings['timeout'] = setTimeout(() => {
                            SBF.ajax({
                                function: 'set-typing',
                                user_id: user_id,
                                conversation_id: -1
                            }, () => {
                                this.typing_settings['sent'] = false;
                            });
                        }, 2000);
                    }
                }
            }
        },

        showArticles: function (id = -1) {
            let panel = $(chat_scroll_area).find(' > .sb-panel-articles');
            let code = '';
            $(panel).html('').sbLoading(true);
            this.showPanel('articles', CHAT_SETTINGS['articles-title'] == '' ? 'Help Center' : CHAT_SETTINGS['articles-title']);
            this.getArticles(id, (articles) => {
                if (id == -1) {
                    for (var i = 0; i < articles.length; i++) {
                        code += `<div data-id="${articles[i]['id']}"><div>${articles[i]['title']}</div><span>${articles[i]['content']}</span></div>`;
                    }
                    $(panel).html(`<div class="sb-articles">${code}</div>`);
                } else {
                    if (articles !== false) {
                        $(panel).html(`<div data-id="${articles['id']}" class="sb-article"><div class="sb-title">${articles['title']}</div><div class="sb-content">${articles['content'].replace(/(?:\r\n|\r|\n)/g, '<br>')}</div>${articles['link'] == '' ? '' : `<a href="${articles['link']}" target="_blank" class="sb-btn-text"><i class="sb-icon-plane"></i>${sb_('Read more')}</a>`}</div>`);
                    }
                }
                $(panel).sbLoading(false);
            });
        },

        getArticles: function (id = -1, onSuccess = false) {
            if (this.articles === false || id != -1) {
                SBF.ajax({
                    function: 'get-articles',
                    id: id
                }, (response) => {
                    if (id == -1) {
                        this.articles = response;
                    }
                    if (onSuccess === false) {
                        return response;
                    } else {
                        onSuccess(response);
                    }
                });
            } else {
                if (onSuccess === false) {
                    return this.articles;
                } else {
                    onSuccess(this.articles);
                }
            }
        },

        searchArticles: function (search, btn) {
            if (search != '') {
                $(btn).sbLoading(true);
                SBF.ajax({
                    function: 'search-articles',
                    search: search
                }, (articles) => {
                    let code = '';
                    let count = articles.length;
                    if (count == 0) {
                        code += `<p class="sb-no-results">${sb_('No articles found.')}</p>`;
                    } else {
                        for (var i = 0; i < articles.length; i++) {
                            code += `<div data-id="${articles[i]['id']}"><div>${articles[i]['title']}</div><span>${articles[i]['content']}</span></div>`;
                        }
                    }
                    $(main).find('.sb-dashboard-articles .sb-articles').html(code);
                    $(btn).sbLoading(false);
                });
            }
        },

        categoryEmoji: function (category) {
            let list = this.emoji_options['list'];
            if (category == 'all') {
                this.emoji_options['list_now'] = list;
            } else {
                this.emoji_options['list_now'] = [];
                for (var i = 0; i < list.length; i++) {
                    if (list[i]['category'].startsWith(category)) {
                        this.emoji_options['list_now'].push(list[i]);
                    }
                }
            }
            this.emoji_options['range'] = 0;
            this.populateEmoji(0);
            this.populateEmojiBar();
        },

        mouseWheelEmoji: function (e) {
            let range = this.emoji_options['range'];
            if (sbDelta(e) > 0 || (responsive && typeof e.originalEvent.changedTouches !== 'undefined' && this.emoji_options['touch'] < e.originalEvent.changedTouches[0].clientY)) {
                range -= (range < 1 ? 0 : 1);
            } else {
                range += (range > this.emoji_options['range_limit'] ? 0 : 1);
            }
            $(chat_emoji).find('.sb-emoji-bar > div').sbActivate(false).eq(range).sbActivate();
            this.emoji_options['range'] = range;
            this.populateEmoji(range);
            e.preventDefault();
        },

        insertEmoji: function (emoji) {
            if (emoji.indexOf('.svg') > 0) {
                emoji = $.parseHTML(emoji)[0]['alt'];
            }
            this.insertText(emoji);
            $(chat_emoji).sbTogglePopup();
        },

        showEmoji: function (button) {
            if ($(chat_emoji).sbTogglePopup(button)) {
                if (!admin) {
                    $(chat_emoji).css({ left: $(chat_editor).offset().left + 20, top: $(chat_editor).offset().top - window.scrollY - 375 });
                }
                if ($(chat_emoji).find('.sb-emoji-list > ul').html() == '') {
                    jQuery.ajax({
                        method: 'POST',
                        url: SB_AJAX_URL,
                        data: {
                            function: 'emoji'
                        }
                    }).done((response) => {
                        this.emoji_options['list'] = JSON.parse(response);
                        this.emoji_options['list_now'] = this.emoji_options['list'];
                        this.populateEmoji(0);
                        this.populateEmojiBar();
                    });
                }
                SBF.deselectAll();
            }
        },

        populateEmoji: function (range) {
            let code = '';
            let per_page = responsive ? 42 : 49;
            let limit = range * per_page + per_page;
            let list_now = this.emoji_options['list_now'];
            if (limit > list_now.length) limit = list_now.length;
            this.emoji_options['range_limit'] = list_now.length / per_page - 1;
            this.emoji_options['range'] = range;
            for (var i = (range * per_page); i < limit; i++) {
                code += `<li>${list_now[i]['char']}</li>`;
            }
            $(chat_emoji).find('.sb-emoji-list').html(`<ul>${code}</ul>`);
        },

        populateEmojiBar: function () {
            let code = '<div class="sb-active"></div>';
            let per_page = responsive ? 42 : 49;
            for (var i = 0; i < this.emoji_options['list_now'].length / per_page - 1; i++) {
                code += '<div></div>';
            }
            this.emoji_options['range'] = 0;
            $(chat_emoji).find('.sb-emoji-bar').html(code);
        },

        clickEmojiBar: function (item) {
            let range = $(item).index();
            this.populateEmoji(range);
            this.emoji_options['range'] = range;
            $(chat_emoji).find('.sb-emoji-bar > div').sbActivate(false).eq(range).sbActivate();
        },

        searchEmoji: function (search) {
            SBF.search(search, () => {
                if (search.length > 1) {
                    let list = this.emoji_options['list'];
                    let list_now = [];
                    for (var i = 0; i < list.length; i++) {
                        if (list[i]['category'].toLowerCase().indexOf(search) > -1 || list[i]['name'].toLowerCase().indexOf(search) > -1) {
                            list_now.push(list[i]);
                        }
                    }
                    this.emoji_options['list_now'] = list_now;
                } else {
                    this.emoji_options['list_now'] = this.emoji_options['list'];
                }
                this.emoji_options['range'] = 0;
                this.populateEmoji(0);
                this.populateEmojiBar();
            });
        },

        insertContent: function (text) {
            this.insertText(text);
            SBF.deactivateAll();
            $(global).removeClass('sb-popup-active');
        },

        showBox: function (button) {
            $(chat_replies).sbTogglePopup(button);
            SBF.deselectAll();
        },

        searchReplies: function (search) {
            SBF.search(search, () => {
                let code = '';
                let all = search.length > 1 ? false : true;
                for (var i = 0; i < this.replies_list.length; i++) {
                    if (all || this.replies_list[i]['reply-name'].toLowerCase().indexOf(search) > -1 || this.replies_list[i]['reply-text'].toLowerCase().indexOf(search) > -1) {
                        code += `<li><div>${this.replies_list[i]['reply-name']}</div><div>${this.replies_list[i]['reply-text']}</div></li>`;
                    }
                }
                $(chat_replies).find('.sb-replies-list > ul').html(code);
            });
        },

        textareaChange: function (textarea) {
            let value = $(textarea).val();

            let last_char = value.charAt(textarea.selectionStart - 1);
            if (last_char == '#') {
                this.editor_listening = true;
            }
            if (this.editor_listening && last_char == ' ') {
                let keyword = value.substr(value.lastIndexOf('#') + 1).replace(' ', '');
                this.editor_listening = false;
                for (var i = 0; i < this.replies_list.length; i++) {
                    if (this.replies_list[i]['reply-name'] == keyword) {
                        $(textarea).val(value.substr(0, value.lastIndexOf('#')) + this.replies_list[i]['reply-text']);
                        return;
                    }
                }
            }

            if (value != '') {
                this.typing((admin ? SB_CURRENT_AGENT['id'] : currentUser().id), 'set');
                this.activateBar();
            } else {
                this.activateBar(false);
            }
        },

        insertText: function (text) {
            let textarea = $(chat_textarea).get(0);
            let index = 0;
            if ('selectionStart' in textarea) {
                index = textarea.selectionStart;
            } else if ('selection' in document) {
                textarea.focus();
                let selection = document.selection.createRange();
                var selection_length = document.selection.createRange().text.length;
                selection.moveStart('character', -textarea.value.length);
                index = selection.text.length - selection_length;
            }
            $(textarea).val($(textarea).val().substr(0, index) + text + $(textarea).val().substr(index));
            $(textarea).focus();
            $(textarea).manualExpandTextarea();
            this.activateBar();
        },

        enabledAutoExpand: function () {
            $(chat_textarea).autoExpandTextarea();
        },

        privacy: function () {
            SBF.ajax({
                function: 'get-block-setting',
                value: 'privacy'
            }, (response) => {
                $(chat_scroll_area).append(`<div class="sb-privacy sb-init-form" data-decline="${response['decline'].replace(/"/g, '')}"><div class="sb-title">${response['title']}</div><div class="sb-text">${response['message']}</div>` + (response['link'] != '' ? `<a target="_blank" href="${response['link']}">${response['link'].replace('http://', '').replace('https://', '').replace('www.', '')}</a>` : '') + `<div class="sb-buttons"><a class="sb-btn sb-approve">${response['btn-approve']}</a><a class="sb-btn sb-decline">${response['btn-decline']}</a></div></div>`);
                this.finalizeInit();
            });
            this.dashboard = true;
            $(main).addClass('sb-init-form-active');
        },
 
        popup: function (close = '') {
            if (close == 'close') {
                if ($(main).find('.sb-popup-message').length) {
                    storage('popup', true);
                    $(main).find('.sb-popup-message').remove();
                }
                return;
            }
            if (!this.chat_open) {
                SBF.ajax({
                    function: 'get-block-setting',
                    value: 'popup'
                }, (response) => {
                    setTimeout(() => {
                        if (!this.chat_open) {
                            $(main).append(`<div class="sb-popup-message">` + (response['image'] != '' ? `<img src="${response['image']}" />` : '') + (response['title'] != '' ? `<div class="sb-top">${sb_(response['title'])}</div>` : '') + `<div class="sb-text">${sb_(response['message'])}</div><div class="sb-icon-close"></div></div>`);
                        }
                    }, 1000);
                });
            }
        },

        followUp: function () {
            if (!admin && currentUser() != false && currentUser().get('email') == '') {
                if (timeout != false) {
                    clearTimeout(timeout);
                }
                timeout = setTimeout(() => {
                    if (this.conversation != false) {
                        this.sendMessage(CHAT_SETTINGS['bot-id'], '[email]');
                        this.scrollBottom();
                        storage('email', today.getDate());
                    }
                }, 15000);
            }
        },

        welcome: function () {
            if (CHAT_SETTINGS['welcome'] && !storage('welcome') && currentUser() != false) {
                SBF.ajax({
                    function: 'get-block-setting',
                    value: 'welcome'
                }, (response) => {
                    setTimeout(() => {
                        if (this.conversation === false) {
                            this.newConversation(3, CHAT_SETTINGS['bot-id'], sb_(response['message']));
                        } else {
                            this.sendMessage(CHAT_SETTINGS['bot-id'], sb_(response['message']));
                        }
                        if (response['open']) {
                            this.openChat();
                        }
                        if (response['sound']) {
                            this.audio.play();
                        }
                        this.skip = true;
                    }, 2000);
                    storage('welcome', true);
                });
            }
        },

        slackMessage: function (user_id, full_name, profile_image, message, attachments = []) {
            let conversation_id = this.conversation.id;
            SBF.ajax({
                function: 'send-slack-message',
                user_id: user_id,
                full_name: full_name,
                profile_image: profile_image,
                conversation_id: conversation_id,
                message: message,
                attachments: attachments,
                channel: this.slack_channel[0] == currentUser().id ? this.slack_channel[1] : -1
            }, (response) => {
                if (Array.isArray(response) && response[0] == 'success') {
                    this.slack_channel = [currentUser().id, response[1]];
                } else {
                    SBF.error(JSON.stringify(response), 'send-slack-message');
                }
            });
        },

        deleteMessage: function (message_id) {
            SBF.ajax({
                function: 'delete-message',
                message_id: message_id
            }, () => {
                this.conversation.deleteMessage(message_id);
                $(chat).find(`[data-id="${message_id}"]`).remove();
                $(document).trigger('SBMessageDeleted', message_id);
            });
        },

        registration: function () {
            $(chat_scroll_area).append(SBRichMessages.generate({}, 'registration', 'sb-init-form'));
            this.dashboard = true;
            this.finalizeInit();
            $(main).addClass('sb-init-form-active');
        },

        activateBar: function (show = true) {
            $(chat_editor).find('.sb-bar').sbActivate(show);
        },

        addUserAndLogin: function (onSuccess = false) {
            SBF.ajax({
                function: 'add-user-and-login',
                login_app: SBApps.login()
            }, (response) => {
                storage('login', response[1]);
                currentUser(new SBUser(response[0]));
                if (onSuccess !== false) {
                    onSuccess(response);
                }
            });
        },

        isInitDashboard: function () {
            return CHAT_SETTINGS['init-dashboard'] || (currentUser() != false && currentUser().conversations.length > 1);
        },
    }
    window.SBChat = SBChat;

    var SBRichMessages = {
        rich_messsages: {
            'buttons': '<div class="sb-buttons">[options]</div>',
            'select': '<div class="sb-select"><p></p><ul>[options]</ul></div>',
            'list': '<div class="sb-text-list">[values]</div>',
            'table': '<table><tbody>[header][values]</tbody></table>',
            'inputs': '<div class="sb-form">[values]</div>',
            'rating': `<div class="sb-rating"><div class="sb-input sb-input-textarea"><textarea></textarea></div><div><div data-rating="positive" class="sb-submit"><i class="sb-icon-like"></i>${sb_('Good')}</div><div data-rating="negative" class="sb-submit"><i class="sb-icon-dislike"></i>${sb_('Bad')}</div></div></div>`
        },
        cache: {},

        generate: function (settings, name, css = '') {
            let content;
            let success = '';
            let next = true;
            let id = 'id' in settings ? settings['id'] : SBF.random();

            if (name in this.rich_messsages) {
                content = this.rich_messsages[name];
            } else if (name in this.cache) {
                content = this.cache[name];
            } else if (CHAT_SETTINGS['rich-messages'].includes(name)) {
                content = '<div class="sb-rich-loading sb-loading"></div>';
                SBF.ajax({
                    function: 'get-rich-message',
                    name: name
                }, (response) => {
                    response = this.initInputs(response);
                    if (name == 'timetable') response = this.timetable(response);
                    $(main).find(`.sb-rich-message[id="${id}"]`).html(`<div class="sb-content">${response}</div>`);
                    this.cache[name] = response;
                    SBChat.scrollBottom(SBChat.dashboard ? true : false);
                });
                next = false;
            } else {
                return false;
            }

            if (next) {
                success = 'success' in settings;
                let content_settings = content.match(/\[.+?\]/g) || [];
                for (var i = 0; i < content_settings.length; i++) {
                    let setting = content_settings[i].slice(1, -1);
                    let code;
                    let options;
                    if (setting in settings) {
                        code = '';
                        options = settings[setting].split(',');
                        switch (setting) {
                            case 'options':
                                for (var j = 0; j < options.length; j++) {
                                    code += name == 'select' ? `<li data-value="${SBF.stringToSlug(options[j])}">${options[j]}</li>` : `<div class="sb-btn sb-submit">${options[j]}</div>`;
                                }
                                break;
                            case 'values':
                                let list = name == 'list';
                                let list_double = list && options.length && options[0].indexOf(':') > 0;
                                if (!list_double) {
                                    content = content.replace('sb-text-list', 'sb-text-list sb-text-list-single');
                                }
                                for (var j = 0; j < options.length; j++) {
                                    if (list_double) {
                                        code += `<div><div>${options[j].split(':')[0]}</div><div>${options[j].split(':')[1]}</div></div>`;
                                    } else if (list) {
                                        code += `<div>${$.trim(options[j])}</div>`;
                                    } else if (name == 'table') {
                                        let tds = options[j].split(':');
                                        code += '<tr>';
                                        for (var y = 0; y < tds.length; y++) {
                                            code += `<td>${tds[y]}</td>`;
                                        }
                                        code += '</tr>';
                                    } else if (name == 'inputs') {
                                        code += `<div id="${SBF.stringToSlug(options[j])}" data-type="text" class="sb-input sb-input-text"><span>${options[j]}</span><input autocomplete="false" type="text" required></div>`;
                                    }
                                }
                                if (name == 'inputs') {
                                    code += '<div class="sb-btn sb-submit">' + sb_('button' in settings ? settings['button'] : 'Send now') + '</div>';
                                }
                                break;
                            case 'header':
                                code += '<tr>';
                                for (var j = 0; j < options.length; j++) {
                                    code += `<th>${options[j]}</th>`;
                                }
                                code += '</tr>';
                                break;
                            default:
                                code = settings[setting];
                                break;
                        }
                        content = content.replace(content_settings[i], code);
                    }
                }
            }
            return `<div id="${id}" data-type="${name}" class="sb-rich-message sb-rich-${name} ${css}">` + ('title' in settings ? `<div class="sb-top">${sb_(settings['title'])}</div>` : '') + ('message' in settings ? `<div class="sb-text">${sb_(settings['message'])}</div>` : '') + `<div class="sb-content">${content}</div>` + (success ? `<div data-success="${settings['success'].replace(/"/g, '')}" class="sb-info"></div>` : '') + `</div>`;
        },

        submit: function (area, type, element) {
            if (!admin && !$(element).sbLoading() && !this.is_busy) {
                let error = '';
                let shortcode = '';
                let parameters = {};
                let success = $(area).find('[data-success]').length ? $(area).find('[data-success]').attr('data-success') : '';
                let rich_message_id = $(area).closest('.sb-rich-message').attr('id');
                let message_id = $(area).closest('[data-id]').attr('data-id');
                let message = '';
                let payload = { 'rich-messages': {} };
                let user_settings = { 'profile_image': ['', ''], 'first_name': ['', ''], 'last_name': ['', ''], 'email': ['', ''], 'password': ['', ''], 'user_type': ['', ''] };
                let settings = {};
                let input = element;
                let dialogflow_response = '';

                if (SBF.null(message_id)) {
                    message_id = -1;
                } else {
                    let item = SBChat.conversation.getMessage(message_id);
                    message = item.get('message');
                    payload = item.payload();
                    if (!('rich-messages' in payload)) {
                        payload['rich-messages'] = {};
                    }
                }
                if ((!$(element).hasClass('sb-btn')) || (!$(element).hasClass('sb-select'))) {
                    input = $(element).closest('.sb-btn,.sb-select');
                }
                $(input).sbLoading(true);
                $(area).find('.sb-info').html('').sbActivate(false);

                switch (type) {
                    case 'email':
                        settings = SBForm.getAll(area);
                        if ('first_name' in settings) {
                            user_settings['user_type'] = ['user', ''];
                        }
                        $.extend(user_settings, settings);
                        error = 'Please insert a valid email.' + ('first_name' in settings ? '' : ' All fields are required.');
                        success = `${sb_(success == '' ? 'Your email is' : success)} *${user_settings['email'][0]}*`;
                        payload['rich-messages'][rich_message_id] = { type: type, result: settings };
                        payload['event'] = 'update-user';
                        parameters = { function: 'update-user-and-message', settings: user_settings, payload: payload };
                        dialogflow_response = 'email-complete';
                        break;
                    case 'select':
                    case 'buttons':
                        settings = $(element).html();
                        success = `${sb_(success == '' ? 'Your selected the option' : success)} *${settings}*`;
                        payload['rich-messages'][rich_message_id] = { type: type, result: settings };
                        parameters = { function: 'update-message', payload: payload };
                        dialogflow_response = settings;
                        break;
                    case 'inputs':
                        settings = SBForm.getAll(area);
                        success = `${sb_(success == '' ? 'You inserted the values below.' : success)} [list values="`;
                        error = 'All fields are required.';
                        for (var key in settings) {
                            success += `${settings[key][1].replace(/:|,/g, '')}:${settings[key][0].replace(/:|,/g, '')},`;
                        }
                        success = success.slice(0, -1) + '"]';
                        payload['rich-messages'][rich_message_id] = { type: type, result: settings };
                        parameters = { function: 'update-message', payload: payload };
                        dialogflow_response = 'inputs-complete';
                        break;
                    case 'registration':
                        let settings_extra = SBForm.getAll(area.find('.sb-form-extra'));
                        let payload_settings = $.extend({}, user_settings, settings_extra);
                        $.extend(user_settings, SBForm.getAll(area.find('.sb-form-main')));
                        success = `${sb_(success == '' ? 'Your account details are the following.' : success)} [list values="`;
                        for (var key in user_settings) {
                            let value = user_settings[key][0].replace(/:|,/g, '');
                            if (value != '') {
                                if (key == 'profile_image') {
                                    value = value.substr(value.lastIndexOf('/') + 1);
                                }
                                if (key == 'password' || key == 'password-check') {
                                    value = '********';
                                    payload_settings[key] = '********';
                                }
                                success += `${user_settings[key][1].replace(/:|,/g, '')}:${value},`;
                            }
                        }
                        for (var key in settings_extra) {
                            if (settings_extra[key][0] != '') {
                                success += `${settings_extra[key][1].replace(/:|,/g, '')}:${settings_extra[key][0].replace(/:|,/g, '')},`;
                            }
                        }
                        user_settings['user_type'] = ['user', ''];
                        success = success.slice(0, -1) + '"]';
                        payload['rich-messages'][rich_message_id] = { type: type, result: payload_settings };
                        payload['event'] = 'update-user';
                        parameters = { function: currentUser() != false ? 'update-user-and-message' : 'add-user-and-login', settings: user_settings, settings_extra: settings_extra, payload: payload };
                        error = area.find('#password').length ? 'Please insert your name, last name, a valid email and a password of min 8 chars.' : 'Please insert your name, last name and a valid email.';
                        dialogflow_response = 'registration-complete';
                        break;
                    case 'rating':
                        let rating = $(element).attr('data-rating');
                        success = `${sb_('Your rating is')} *${sb_(rating)}*. ${sb_(success == '' ? 'Thank you for your feedback!' : success)}`;
                        settings = { conversation_id: SBChat.conversation.id, agent_id: SBChat.agent_id, user_id: currentUser().id, message: area.find('textarea').val(), rating: (rating == 'positive' ? 1 : -1) };
                        payload['rich-messages'][rich_message_id] = { type: type, result: settings };
                        parameters = { function: 'set-rating', payload: payload, settings: settings };
                        dialogflow_response = rating;
                        break;

                }

                shortcode = message.substr(message.indexOf('[' + type))
                shortcode = shortcode.substr(0, shortcode.indexOf(']') + 1);

                if (error != '' && SBForm.errors(area)) {
                    SBForm.showErrorMessage(area, error);
                    $(input).sbLoading(false);
                    if (SBChat.dashboard || (SBChat.conversation !== false && SBChat.conversation.getLastMessage().id == message_id)) {
                        SBChat.scrollBottom();
                    }
                    return false;
                }
                if (message_id != -1) {
                    $.extend(parameters, {
                        message_id: message_id,
                        message: message != '' ? message.replace(shortcode, success) : success,
                        payload: payload
                    });
                }
                SBF.ajax(parameters, (response) => {
                    if (response != false && response != 'duplicate-email') {
                        switch (type) {
                            case 'email':
                                for (var key in user_settings) {
                                    currentUser().set(key, user_settings[key][0]);
                                }
                                break;
                            case 'registration':
                                if (currentUser() == false) {
                                    storage('login', response[1]);
                                    currentUser(new SBUser(response[0]));
                                    $(main).removeClass('sb-init-form-active');
                                    $(area).remove();
                                    SBChat.initChat();
                                    SBChat.sendMessage(CHAT_SETTINGS['bot-id'], success);
                                    if (!SBChat.isInitDashboard()) {
                                        SBChat.hideDashboard();
                                    }
                                } else {
                                    for (var key in user_settings) {
                                        currentUser().set(key, user_settings[key][0]);
                                    }
                                }
                                break;
                        }
                        if (message_id == -1) {
                            $(element).closest('.sb-rich-message').html(success);
                        } else {
                            SBChat.setConversationStatus(2);
                        }
                        if (type != 'email' && type != 'registration' && type != 'login') {
                            SBChat.sendBotMessage(`${rich_message_id}|${type}|${dialogflow_response}`);
                        }
                        if (CHAT_SETTINGS['slack-active']) {
                            SBChat.slackMessage(currentUser().id, currentUser().get('full_name'), currentUser().get('profile_image'), success);
                        }
                    } else {
                        let error = '';
                        if (response == 'duplicate-email') {
                            error = 'This email is already in use. Please use another email.';
                        } else {
                            error = 'Error. Please check your information and try again.';
                        }
                        SBForm.showErrorMessage(area, error);
                        $(input).sbLoading(false);
                    }
                });
            }
        },

        shortcode: function (shortcode) {
            if (shortcode.indexOf(' ') < 0) {
                return [shortcode.slice(1, -1), {}];
            }
            let result = {};
            let shortcode_name = shortcode.substr(1, shortcode.indexOf(' ') - 1);
            shortcode = shortcode.slice(1, -1).substr(shortcode_name.length + 1);
            let settings = shortcode.split('" ');
            for (var i = 0; i < settings.length; i++) {
                if (settings[i].indexOf('=') > -1) {
                    let item = settings[i].split('=');
                    result[$.trim(item[0])] = item[1].replace(/"/g, '');
                }
            }
            return [shortcode_name, result];
        },

        initInputs: function (code) {
            code = $.parseHTML('<div>' + code + '</div>');
            $(code).find('.sb-input input').each(function () {
                if ($(this).val() != '') {
                    $(this).siblings().addClass('sb-active sb-filled');
                }
            });
            return $(code).html();
        },

        timetable: function (code) {
            let table = $.parseHTML(`<div>${code}</div>`);
            $(table).find('[data-time]').each(function () {
                let times = $(this).attr('data-time').split('|');
                code = ''
                for (var i = 0; i < times.length; i++) {
                    if (times[i] == 'closed') {
                        code += sb_('Closed');
                        break;
                    } else if (times[i] != '') {
                        let hours = parseInt(times[i].split(':')[0]);
                        let minutes = times[i].split(':')[1];
                        if (hours > 24) {
                            hours -= 24;
                        }
                        if (hours < 0) {
                            hours = 24 - hours;
                        }
                        let time = new Date(`01-01-2000 ${hours}:${minutes} UTC`);
                        code += time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + (i == 0 || i == 2 ? `<span>${sb_('to')}</span>` : i == 1 && times[i + 1] != '' ? `<br />` : '');
                    }
                }
                $(table).find(' > div > span').html(`<i class="sb-icon-clock"></i> ${sb_('Time zone')} ${Intl.DateTimeFormat().resolvedOptions().timeZone}`);
                $(this).html(code);
            });
            return $(table).html();
        }
    }

    var SBForm = {

        getAll: function (area) {
            let settings = {};
            $(area).find('.sb-input').each((i, element) => {
                settings[$(element).attr('id')] = this.get(element);
            });
            return settings;
        },

        get: function (input) {
            let type = $(input).data('type');
            let name = $(input).find(' > span').html();
            switch (type) {
                case 'image':
                    let url = $(input).find('.image').attr('data-url');
                    return [SBF.null(url) ? '' : url, name];
                    break;
                case 'select':
                    return [$(input).find('select').val(), name];
                    break;
                default:
                    return [$(input).find('input').val(), name];
                    break;
            }
        },

        set: function (item, value) {
            if ($(item).length) {
                let type = $(item).data('type');
                switch (type) {
                    case 'image':
                        if (value == '') {
                            $(item).find('.image').removeAttr('data-url').removeAttr('style');
                        } else {
                            $(item).find('.image').attr('data-url', value).css('background-image', `url(${value})`);
                        }
                        break;
                    case 'select':
                        $(item).find('select').val(value);
                        break;
                    default:
                        $(item).find('input').val(value);
                        break;
                }
                return true;
            }
            return false;
        },

        clear: function (area) {
            $(area).find('.sb-input').each((i, element) => {
                this.set(element, '');
            });
            this.set($(area).find('#user_type'), 'user');
        },

        errors: function (area) {
            let have_errors = false;
            let items = $(area).find('[required]');
            $(items).each(function (i) {
                let type = $(this).attr('type');
                let value = $(this).val();
                if (value == '') {
                    have_errors = true;
                    return false;
                } else if (type == 'password') {
                    if (type == 'password' && (value.length < 8 || (items.length > (i + 1) && $(items[i + 1]).attr('type') == 'password' && $(items[i + 1]).val() != value))) {
                        have_errors = true;
                        return false;
                    }
                } else if (type == 'email' && (value.indexOf('@') < 0 || value.indexOf('.') < 0)) {
                    have_errors = true;
                    return false;
                }
            });
            return have_errors;
        },

        showErrorMessage: function (area, message) {
            $(area).find('.sb-info').html(sb_(message)).sbActivate();
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                $(area).find('.sb-info').sbActivate(false);
            }, 7000);
        },

        showSuccessMessage: function (area, message) {
            $(area).find('.sb-info').remove();
            $(area).addClass('sb-success').find('.sb-content').html(`<div class="sb-text">${message}</div>`);
        }
    }
    window.SBForm = SBForm;

    var SBApps = {

        isWP: function () {
            return CHAT_SETTINGS['wp'];
        },

        isLoggedWP: function () {
            return CHAT_SETTINGS['wp'] && SB_WP_ACTIVE_USER !== false;
        },

        login: function () {
            if (this.isWP()) {
                return CHAT_SETTINGS['wp-users-system'] == 'wp' ? SB_WP_ACTIVE_USER : false
            }
            return false;
        }

    }

    $(document).ready(function () {

        main = $('body').find('.sb-conversation, .sb-chat');
        admin = $(main).hasClass('sb-conversation');

        if (main.length) {
            chat = $(main).find('.sb-list');
            chat_editor = $(main).find('.sb-editor');
            chat_textarea = $(chat_editor).find('textarea');
            chat_scroll_area = $(main).find(admin ? '.sb-list' : '.sb-scroll-area');
            chat_header = $(chat_scroll_area).find('.sb-header');
            chat_replies = $(chat_editor).find('.sb-replies');
            chat_emoji = $(chat_editor).find('.sb-emoji');
            SBChat.enabledAutoExpand();
            SBChat.audio = $(main).find("#sb-audio")[0];

            SBF.ajax({
                function: 'get-front-settings'
            }, (response) => {
                CHAT_SETTINGS = response;
                if (!admin && !CHAT_SETTINGS['chat-manual-init'] && (!CHAT_SETTINGS['disable-office-hours'] || CHAT_SETTINGS['office-hours']) && (!CHAT_SETTINGS['chat-login-init'] || (!SBApps.isWP() && storage('login') != false) || (SBApps.isWP() && SBApps.isLoggedWP())) && (!SBApps.isWP() || CHAT_SETTINGS['wp-show-ids'].length == 0 || CHAT_SETTINGS['wp-show-ids'].includes(SB_WP_PAGE_ID))) {
                    SBChat.initChat();
                }
            });

            if (!admin) {
                $(chat_editor).on('keydown', 'textarea', function (e) {
                    if (e.which == 13) {
                        $(chat_editor).find('.sb-submit').click();
                        e.preventDefault;
                        return false;
                    }
                });
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState == 'hidden') {
                SBChat.stopRealTime();
                SBChat.tab_active = false;
            } else {
                SBChat.startRealTime();
                SBChat.tab_active = true;
                document.title = document_title;
            }
        }, false);

        if (main.length == 0) {
            main = $('body').find('.sb-admin');
            admin = true;
        }
        if (admin) {
            global = $(main).closest('.sb-admin');
        } else {
            global = main;
        }

        $(chat_scroll_area).on('scroll', function (e) {
            SBChat.scrollHeader();
        });

        $(chat).on('click', '.sb-menu-btn', function () {
            let menu = $(this).parent().find('.sb-menu');
            let active = $(menu).sbActive();
            SBF.deactivateAll();
            if (!active) {
                $(menu).sbActivate();
                SBF.deselectAll();
            }
        });

        if (responsive) {
            $(chat_editor).on('click', '.sb-textarea', function () {
                $(main).addClass('sb-header-hidden');
                $(this).find('textarea').focus();
            });

            $(chat_editor).on('focusout', '.sb-textarea', function () {
                setTimeout(() => {
                    $(main).removeClass('sb-header-hidden');
                }, 300);
            });

            $(chat_editor).on('click', '.sb-submit', function () {
                $(chat_textarea).blur();
            });
        }

        $(chat).on('click', '.sb-menu li', function () {
            $(this).parent().sbActivate(false);
        });

        $(chat).on('click', '.sb-menu [data-value="delete"]', function () {
            let message = $(this).closest('[data-id]');
            let message_id = $(message).attr('data-id');
            if (SBChat.user_online) {
                SBF.ajax({
                    function: 'update-message',
                    user_id: SBChat.conversation.getMessage(message_id).get('user_id'),
                    message_id: message_id,
                    message: '',
                    attachments: [],
                    payload: { 'event': 'delete-message' }
                }, () => {
                    SBChat.conversation.deleteMessage(message_id);
                    $(chat).find(`[data-id="${message_id}"]`).remove();
                });
            } else {
                SBChat.deleteMessage(message_id);
            }
        });

        $(chat_editor).on('click', '.sb-submit', function () {
            if (!SBChat.is_busy) {
                if (currentUser() == false && !admin) {
                    SBChat.addUserAndLogin(() => {
                        SBChat.newConversation(2);
                    });
                    return;
                } else {
                    SBChat.sendMessage();
                }
                SBChat.activateBar(false);
            }
        });

        $(main).on('click', '.sb-chat-btn,.sb-responsive-close-btn', function () {
            SBChat.openButton();
        });

        $(main).on('click', '.sb-dashboard-btn', function () {
            SBChat.showDashboard();
        });

        $(main).on('click', '.sb-user-conversations li', function () {
            SBChat.openConversation($(this).attr('data-conversation-id'));

        });

        $(main).on('click', '.sb-btn-new-conversation', function () {
            SBChat.clear();
            SBChat.hideDashboard();
        });

        $(chat_editor).on('click', '.sb-btn-attachment', function () {
            if (!SBChat.is_busy) {
                $(chat_editor).find('.sb-upload-files').val('').click();
            }
        });

        $(chat_editor).on('click', '.sb-attachments > div > i', function (e) {
            $(this).parent().remove();
            if ($(chat_textarea).val() == '' && $(chat_editor).find('.sb-attachments > div').length == 0) {
                SBChat.activateBar(false);
            }
            e.preventDefault();
            return false;
        });

        $(chat_editor).find('.sb-upload-files').change(function (data) {
            SBChat.busy(true);
            $(this).sbUploadFiles(function (response) {
                response = JSON.parse(response);
                if (response[0] == 'success') {
                    if (response[1] == 'extension_error') {
                        SBF.dialog('The file you are trying to upload has an extension that is not allowed.', 'info');
                    } else if ($(upload_target).hasClass('sb-input-image')) {
                        $(upload_target).find('.image').attr('data-url', '').css('background-image', '');
                        setTimeout(() => {
                            $(upload_target).find('.image').attr('data-url', response[1]).css('background-image', `url(${response[1]}?v=${SBF.random()})`).append('<i class="sb-icon-close"></i>');
                            upload_target = false;
                        }, 500);
                    } else {
                        let name = response[1].substr(response[1].lastIndexOf('/') + 1);
                        $(chat_editor).find('.sb-attachments').append(`<div data-name="${name}" data-url="${response[1]}">${name}<i class="sb-icon-close"></i></div>`);
                        SBChat.activateBar();
                    }
                } else {
                    SBF.error(response[1], 'sb-upload-files.change');
                }
                SBChat.busy(false);
            });
        });

        $(main).on('click', '.sb-btn-all-articles', function () {
            SBChat.showArticles();
        });

        $(main).on('click', '.sb-articles > div', function () {
            SBChat.showArticles($(this).attr('data-id'));
        });

        $(main).on('click', '.sb-dashboard-articles .sb-input-btn .sb-submit', function () {
            SBChat.searchArticles($(this).parent().find('input').val(), this);
        });

        $(main).on('click', '.sb-image', function () {
            SBF.lightbox($(this).html());
        });

        $(main).on('click', '.sb-lightbox-overlay,.sb-lightbox-overlay > i', function () {
            $(main).find('.sb-lightbox').sbActivate(false);
            return false;
        });

        $(chat_editor).on('click', '.sb-btn-emoji', function () {
            SBChat.showEmoji(this);
        });

        $(chat_emoji).on('click', '.sb-emoji-list li', function (e) {
            SBChat.insertEmoji($(this).html());
            if (responsive) {
                clearTimeout(timeout);
            }
        });

        $(chat_emoji).find('.sb-emoji-list').on('touchend', function (e) {
            timeout = setTimeout(() => {
                SBChat.mouseWheelEmoji(e);
            }, 50);
        });

        $(chat_emoji).find('.sb-emoji-list').on('mousewheel DOMMouseScroll', function (e) {
            SBChat.mouseWheelEmoji(e);
        });

        $(chat_emoji).find('.sb-emoji-list').on('touchstart', function (e) {
            SBChat.emoji_options['touch'] = e.originalEvent.touches[0].clientY;
        });

        $(chat_emoji).on('click', '.sb-emoji-bar > div', function () {
            SBChat.clickEmojiBar(this);
        });

        $(chat_emoji).on('click', '.sb-select li', function () {
            SBChat.categoryEmoji($(this).data('value'));
        });

        $(chat_emoji).find('.sb-search-btn input').on("change keyup paste", function () {
            SBChat.searchEmoji($(this).val());
        });

        $(chat_editor).on('click', '.sb-btn-saved-replies', function () {
            SBChat.showBox(this);
        });

        $(chat_replies).on('click', '.sb-replies-list li', function () {
            SBChat.insertContent($(this).find('div:last-child').html());
        });

        $(chat_replies).find('.sb-search-btn input').on('keyup', function () {
            SBChat.searchReplies($(this).val());
        });

        $(chat_textarea).on('keyup', function () {
            SBChat.textareaChange(this);
        });

        $(main).on('click', '.sb-privacy .sb-approve', function () {
            storage('privacy-approved', true);
            $(this).closest('.sb-privacy').remove();
            $(main).removeClass('sb-init-form-active');
            $(chat_header).find(' > div').css({ 'opacity': 1, 'top': 0 });
            SBChat.initialized = false;
            SBChat.initChat();
        });

        $(main).on('click', '.sb-privacy .sb-decline', function () {
            let privacy = $(this).closest('.sb-privacy');
            $(privacy).find('.sb-text').html($(privacy).attr('data-decline'));
            $(privacy).find('.sb-decline').remove();
            SBChat.scrollBottom(true);
        });

        $(main).on('click', '.sb-popup-message .sb-icon-close', function () {
            SBChat.popup('close');
        });

        $(main).on('click', '.sb-rich-message .sb-submit,.sb-rich-message .sb-select ul li', function () {
            let message = $(this).closest('.sb-rich-message');
            SBRichMessages.submit(message, $(message).attr('data-type'), this);
        });

        $(main).on('click', '.sb-rich-message .sb-input > span', function () {
            $(this).sbActivate();
            $(this).siblings().focus();
        });
        $(main).on('focus', '.sb-rich-message .sb-input input', function () {
            $(this).siblings().sbActivate();
        });

        $(main).on('click', '.sb-rich-message .sb-input input', function () {
            $(this).siblings().removeClass('sb-filled');
        });

        $(main).on('focusout', '.sb-rich-message .sb-input input', function () {
            if ($(this).val() == '') {
                $(this).siblings().sbActivate(false);
            } else {
                $(this).siblings().addClass('sb-filled sb-active');
            }
        });

        $(main).on('click', '.sb-rich-registration .sb-login-area', function () {
            $(this).closest('.sb-rich-registration').replaceWith(SBRichMessages.generate({}, 'login', 'sb-init-form'));
            SBChat.scrollBottom(true);
        });

        $(main).on('click', '.sb-rich-login .sb-registration-area', function () {
            $(this).closest('.sb-rich-login').replaceWith(SBRichMessages.generate({}, 'registration', 'sb-init-form'));
            SBChat.scrollBottom(true);
        });

        $(main).on('click', '.sb-rich-login .sb-submit-login', function () {
            SBF.login(this);
        });

        $(global).on('click', '.sb-search-btn > i', function () {
            $(this).parent().toggleClass('sb-active');
            $(this).parent().find('input').focus();
            $(global).find('.sb-select ul').sbActivate(false);
        });

        $(global).on('click', '.sb-select', function () {
            $(this).find('ul').toggleClass('sb-active');
        });

        $(global).on('click', '.sb-select li', function () {
            let select = $(this).closest('.sb-select');
            let value = $(this).data('value');
            var item = value === '' ? $(select).find('.sb-active') : $(select).find(`[data-value="${value}"]`);
            $(select).find('li').sbActivate(false);
            $(select).find('p').html($(item).html());
            $(item).sbActivate();
        });

        $(global).on('click', '.sb-input-image .image', function () {
            upload_target = $(this).parent();
            $(chat_editor).find('.sb-upload-files').click();
        });

        $(global).on('click', '.sb-input-image .image > .sb-icon-close', function (e) {
            $(this).parent().removeAttr('data-url').css('background-image', '');
            e.preventDefault();
            return false;
        });
    });
}(jQuery));