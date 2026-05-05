/**
 * Base Abstract Class for Screen Chats , prevent duplicate code
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'mageUtils',
    'uiLayout',
    'uiRegistry',
    'Branch8_WebkulMpBuyerSellerChat/js/model/status',
    'Branch8_WebkulMpBuyerSellerChat/js/model/direction',
    'Branch8_WebkulMpBuyerSellerChat/js/model/until',
    'Branch8_WebkulMpBuyerSellerChat/js/model/MessageType',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/load-recently',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/load-history',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/send-message',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/updateLastReadMessage'
], function (
    ko,
    $,
    Component,
    mageUtils,
    layout,
    uiRegistry,
    STATUS,
    DIRECTION,
    UNTIL,
    MessageType,
    loadRecentlyAction,
    loadHistoryAction,
    sendMessageAction,
    updateLastReadMessageAction
) {
    'use strict';

    var mediaUploadRenderTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Branch8_WebkulMpBuyerSellerChat/js/view/mediaUpload',
        dataScope: 'fileUpload',
        displayArea: 'fileUpload',
        placeholderType: 'image',
        config: {
            allowedExtensions: 'jpg jpeg gif png webp mp4 webm',
            maxFileSize: 2097152,
            isMultipleFiles: false,
            template: 'Branch8_WebkulMpBuyerSellerChat/view/mediaUpload/element/image',
            previewTmpl: 'Branch8_WebkulMpBuyerSellerChat/view/mediaUpload/element/attachment-preview',
            maxImageUploadCount: 1,
            uploaderConfig: {
                url: null
            }
        }
    }

    return Component.extend({
        lastReadMessageId: '',
        chatApp: null,
        debounceHandleScroll: '',
        chatScreenId: '',
        STATUS: STATUS,
        UNTIL: UNTIL,
        DIRECTION: DIRECTION,
        MessageType: MessageType,
        loadRecentlyAction: loadRecentlyAction,
        loadHistoryAction: loadHistoryAction,
        stopTrackLastReadMessage:false,
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe([
                    'conversationData',
                    'visible',
                    'messages',
                    'showLoader',
                    'typingMessageText'
                ]);
            const self = this;
            this.messages([]);
            this.typingMessageText('');
            this.conversationData(this.defaultConversationData);
            this.canLoadPrevious = true;
            this.visible(!!this.defaultVisible);
            this.showLoader(!!this.defaultShowLoader);
            this.debounceHandleScroll = _.debounce(this.handleScroll, 300);
            this.loadRecentlyAction({
                chat_profile_id: this.getChatApp().getChatProfile().uniqueId,
                conversation_unique_id: this.conversationData().conversationUniqueId
            }, function (response) {
                const messages = response.messages || [];
                let currentMessages = self.messages();
                messages.map(function (item) {
                    currentMessages.push(item)
                })
                self.messages(currentMessages);
                self.scrollToLastMessage();
                self.afterLoadRecentlyAction(response);
            }, this.getChatApp().generalConfig?.endPoints?.loadRecently);
            this.emojifyIconClickHandle();
            this.chatScreenId = '#chat-window-' + this.conversationData().conversationUniqueId;
            return this;
        },
        //handle by child
        afterLoadRecentlyAction: function () {

        },
        /**
         * handleScroll
         */
        handleScroll: function (data, event) {
            const scrollTop = event.target.scrollTop;
            // load previous message;
            if (scrollTop <= 0 && this.canLoadPrevious === true) {
                this.loadPreviousMessage();
                return;
            }
            //
            this.checkAndMarkMessagedAsRead();
        },

        /**
         * activeTypingMessage
         */
        setTypingMessage: function (typingMessage) {
            const self = this;
            this.typingMessageText(typingMessage);
            setTimeout(function () {
                self.typingMessageText('');
            }, 3500)
        },
        /**
         * check and mark message as read if it is displayed on screen
         * checkAndMarkMessagedAsRead
         */
        checkAndMarkMessagedAsRead: function () {
            try {
                const chatScreenElement = document.getElementById(
                    this.chatScreenId.substring(1)
                ), self = this;
                const chatBoxRect = chatScreenElement.getBoundingClientRect();
                let originMessages = this.messages();
                let processed = 0;
                const unreadList = originMessages.map(function (message) {
                    if (message !== undefined && !!message.is_read === false) {
                        const visible = self.isMessageDisplayOnScreen(message);
                        if (visible) {
                            message.is_read = true;
                        }
                        processed += 1;
                    }
                    return message;
                })
                if (processed > 0) {
                    this.messages(unreadList);
                }
                this.getChatApp().decreaseMessageCounter(
                    this.conversationData().conversationUniqueId,
                    processed
                );
                this.getChatApp().decreaseTotalUnreadMessages(
                    processed
                );
            } catch (e) {
                console.log(e)
            }
        },
        /**
         *
         * @param message
         * @returns {boolean}
         */
        isMessageDisplayOnScreen: function (message) {
            const chatScreenElement = document.getElementById(
                this.chatScreenId.substring(1)
            );
            const chatBoxRect = chatScreenElement.getBoundingClientRect();
            const bounding = document.getElementById(
                'message-' + message.unique_id
            ).getBoundingClientRect();
            return bounding.top >= chatBoxRect.top
                && bounding.bottom <= chatBoxRect.bottom;
        },
        /**
         * loadPreviousMessage
         */
        loadPreviousMessage: function (profileId) {
            const self = this;
            if (!this.messages().length) {
                return;
            }
            const payload = {
                chatProfileId: profileId ? profileId : this.getChatApp().getChatProfile().uniqueId,
                conversationUniqueId: this.conversationData().conversationUniqueId,
                direction: DIRECTION.PREVIOUS,
                anchorDate: this.messages()[0].date
            }
            this.showLoader(true)
            loadHistoryAction(payload, function (res) {
                self.showLoader(false);
                if (res.messages.length) {
                    self.appendMessages(res.messages, self.DIRECTION.PREVIOUS);
                } else {
                    // stop load previous if not found
                    self.canLoadPrevious = false;
                }
            }, this.getChatApp().generalConfig?.endPoints?.loadHistory);
        },

        /**
         *
         * @param newMessages
         * @param where
         * @returns {*}
         */
        appendMessages: function (newMessages, where) {
            this.UNTIL.appendMessages(this.messages, newMessages, where);
            return this;
        },

        /**
         * Close emojity when click
         */
        closeEmojityPopup: function () {
            const wrapper = this.chatScreenId + ' .write-form-wrapper';
            if ($(wrapper).length) {
                $(wrapper).removeClass('open-emoji');
            }
        },

        /**
         * getFormAndSubmit
         */
        getFormAndSubmit: function () {
            const form = document.getElementById(
                'reply_form_' + this.conversationData().conversationUniqueId
            );
            this.sendMessage(form);
        },
        /**
         *
         * @param form
         * @returns {*|string}
         */
        getMessageFromForm: function (form) {
            let formData = $(form).serializeArray(),
                message = '';
            formData.forEach(function (entry) {
                formData[entry.name] = entry.value;
            });
            message = UNTIL.filter(formData.message)
            console.log({
                message:message
            })
            return message;
        },
        /**
         *
         * @param message
         * @param inputPayload
         * @param callBack
         * @returns {boolean}
         */
        ajaxSaveMessage: function (
            message,
            inputPayload,
            callBack
        ) {
            const endpoint = this.getChatApp().generalConfig?.endPoints?.sendMessage || null,
                payLoad = _.extend({
                    'message': message,
                    'dateTime': UNTIL.toServerDate(),
                    'msgType': MessageType.text
                }, inputPayload);
            sendMessageAction(payLoad, function (res) {
                if (callBack) {
                    callBack(res);
                }
            }, endpoint)
        },

        /**
         *
         * @param override
         * @returns {Object}
         */
        createNewMessage: function (override = {}) {
            const id = this.UNTIL.messageUniqueIdGenerate();
            return _.extend({
                conversationUniqueId: this.conversationData().conversationUniqueId,
                date: UNTIL.toServerDate(),
                id: id,
                unique_id: id,
                message: "",
                messageType: MessageType.text,
                receiverName: '',
                receiverUniqueId: '',
                senderName: '',
                senderUniqueId: '',
                meta: null,
                is_read: false
            }, override);
        },
        /**
         * sendMessages
         * @param payLoad
         * @param appendMessages
         * @param callBack
         */
        sendMessages: function (
            payLoad,
            appendMessages = [],
            callBack
        ) {
            const self = this;
            if (appendMessages && appendMessages.length) {
                self.appendMessages(appendMessages, DIRECTION.NEXT)
                self.scrollToLastMessage();
            }
            return this;
        },
        /**
         *
         * @param data
         * @param event
         * @returns {boolean}
         */
        replyByEnter: function (data, event) {
            if (event.which === 13 && !event.shiftKey) {
                $(event.target).parents('form').submit()
            } else if (event.shiftKey && event.keyCode === 13) {
                return true;
            } else {
                return true;
            }
        },

        /**
         * getChatApp
         * @returns {*}
         */
        getChatApp: function () {
            if (this.chatApp === null) {
                this.chatApp = uiRegistry.get(this.parentName)
            }
            return this.chatApp;
        },
        /**
         * validateMessage
         */
        validateMessage: function (message) {
            let valid = true;
            /*if (UNTIL.containHtmlTag(message)) {
                alert($.mage.__('HTML tags are not allowed.'));
                valid = false;
            }*/
            if (_.isEmpty(message)) {
                valid = false;
            }
            return valid;
        },

        /**
         *
         */
        scrollToLastMessage: function () {
            const self = this;
            setTimeout(function () {
                const messageListElement = document.getElementById(
                    "messageFrame-" + self.conversationData().conversationUniqueId
                );
                if (messageListElement) {
                    const lastChild = messageListElement.lastElementChild;
                    if (lastChild) {
                        lastChild.scrollIntoView({behavior: 'smooth', block: 'end'});
                    }
                }
            }, 300)
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initChildren: function () {
            this.createMediaUploadComponent();
            return this;
        },
        /**
         *
         * @returns {*}
         */
        createMediaUploadComponent: function () {
            const templateData = {
                parentName: this.name,
                name: 'mediaUploader',
                conversationUniqueId: this.conversationData().converationUniqueId,
                config: {
                    conversationUniqueId: this.conversationData().converationUniqueId
                },
                conversationData: this.conversationData()
            }, rendererComponent = mageUtils.template(
                mediaUploadRenderTemplate,
                templateData
            );
            rendererComponent['config']['uploaderConfig'] = {
                url: this.generalConfig.chatUploadMediaPath,
                conversationUniqueId: this.conversationData().converationUniqueId
            }
            rendererComponent['config']['conversationUniqueId'] = this.conversationData().converationUniqueId
            layout([rendererComponent]);
            return this;
        },
        /**
         *
         * showFileUploader
         * @param data
         * @param event
         */
        showFileUploader: function (data, event) {
            const wrapper = $(event.currentTarget).closest('.write-form-wrapper'),
                uploadPathName = this.name + '.' + 'mediaUploader',
                fileUpload = uiRegistry.get(uploadPathName);
            fileUpload.showNativeBrowserUpload();
            ['open-emoji'].map(function (cssClass) {
                wrapper.removeClass(cssClass)
            });

        },
        /**
         * getCounterKey
         */
        getCounterKey: function () {
            // implement in subclass
        },
        /**
         * sendMessage
         */
        sendMessage: function (message) {
            // implement in subclass
        },
        /**
         * newMediaAddedHandler
         * @param newValue
         */
        newMediaAddedHandler: function (newValue) {
            // implement in subclass
        },
        /**
         *
         * @returns {boolean}
         */
        shouldTrackLastReadMessage: function () {
            // implement in subclass
        },
        /**
         *
         * initEmoji
         * @param data
         * @param event
         */
        initEmoji: function (data, event) {
        },
        /**
         * openEmojiBox
         * @param data
         * @param event
         */
        openEmojiBox: function (data, event) {
            // const wrapper = $(event.currentTarget).closest('.write-form-wrapper');
            // ['open-fileUpload'].map(function (cssClass) {
            //     wrapper.removeClass(cssClass)
            // });
            // if (wrapper.hasClass('open-emoji')) {
            //     wrapper.removeClass('open-emoji');
            // } else {
            //     wrapper.addClass('open-emoji');
            // }
        },

        /**
         * emojifyIconClickHandle
         */
        emojifyIconClickHandle: function () {
            const emojityPatttern = this.chatScreenId + " .smiley_pad > .emoji",
                messageBox = this.chatScreenId + " [name=\"message\"]";
            $('body').delegate(emojityPatttern, 'click', function () {
                let emoji = $(this).attr('alt');
                $(messageBox).val(function (i, text) {
                    return text + emoji;
                });
                $(messageBox).focus();
            });
        },

        /**
         *
         * @param message
         */
        trackLastReadMessage: function () {
            const yourProfile = this.getChatProfile();
            if (!this.shouldTrackLastReadMessage() || !this.messages().length) {
                return;
            }
            const self = this, messages = this.messages(),
                enpoint = this.getChatApp().generalConfig?.endPoints?.updateLastReadMessage || null;
            let i = messages.length - 1,
                found = 0, message, isOnScreen;
            /***************************************************************
             Find from bottom to top,if message on display on screen ,
             consider mark it as read
             ***************************************************************/
            while (true) {
                message = messages[i];
                isOnScreen = self.isMessageDisplayOnScreen(message);
                if (isOnScreen || i < 0) {
                    found = message;
                    break;
                }
                i--;
            }
            if (found && this.stopTrackLastReadMessage === false) {
                const lastMessage = found;
                if (this.lastReadMessageId === lastMessage.unique_id) {
                    return;
                }
                const lastReadMessagePayload = {
                    'conversation_id': lastMessage.conversationUniqueId,
                    'profile_unique_id': yourProfile.uniqueId,
                    'message_unique_id': lastMessage.unique_id
                }
                setTimeout(function () {
                    updateLastReadMessageAction(lastReadMessagePayload, function (respone, statusCode) {
                        if (statusCode && statusCode == 404) {
                            this.stopTrackLastReadMessage = true;
                        }
                        self.lastReadMessageId = lastMessage.unique_id
                    }, false, enpoint);
                }, 5000)
            }
        }
    });
});
