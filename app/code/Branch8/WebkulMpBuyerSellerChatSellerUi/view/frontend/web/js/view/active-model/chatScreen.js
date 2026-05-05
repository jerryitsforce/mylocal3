define([
    'underscore',
    'jquery',
    'jquery-ui-modules/draggable',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'Branch8_WebkulMpBuyerSellerChat/js/view/abstractChatScreen'
], function (
    _,
    $,
    draggable,
    domObserver,
    AbstractChatScreenComponent,
) {
    'use strict';
    /**
     * AbstractChatScreenComponent
     */
    return AbstractChatScreenComponent.extend({
        parentChatApp: null,
        chatWindowEle: '',
        trackLastMessageInterval:'',
        position: {},
        defaults: {
            imports: {
                "sellerChatProfile": "${ $.parentName }:profileData"
            },
            template: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen',
            headerTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/header',
            messageListTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/messageList',
            mediaTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/media',
            customerProfileTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/customer-profile',
            emojiTyTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/emojity',
            typingMessageTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatScreen/typingMessage',
        },

        /**
         * initialize
         * @returns {*}
         */
        initialize: function () {
            const self = this;
            this._super();
            this.trackLastMessageInterval = setInterval(function () {
                self.trackLastReadMessage();
            }, 3000);
            this.debounceHandleTypingMessage = _.debounce(this.typingMessage, 300);
            return this;
        },

        /**
         * isActive
         */
        shouldTrackLastReadMessage: function () {
            return this.isActive() === true;
        },

        /**
         * initObservable
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'minimized',
                    'isActive',
                    'sellerChatProfile'
                ]);
            this.minimized(false);
            this.isActive(false);
            // call to create media uploader;
            this.initChildren();
            return this;
        },
        /**
         * getChatProfile
         */
        getChatProfile: function () {
            return this.sellerChatProfile();
        },
        /**
         *
         */
        afterRender: function () {
            const id = '#chat-window-' + this.conversationData().conversationUniqueId;
            this.chatWindowEle = $(id);
            if (this.defaultVisible) {
                this.showChatWindow();
            }
            this.setDefaultChatBoxPosition(this.defaultPosition);
            $(id).draggable();

            domObserver.get('.reply-container', function (replyContainer) {
                if (replyContainer) {
                    $(replyContainer).css('user-select', 'text').on('mousedown', function (e) {
                        e.stopPropagation();
                    });
                }
            });
        },

        /**
         * setDefaultChatBoxPosition
         * @param position
         * @returns {*}
         */
        setDefaultChatBoxPosition: function (position) {
            this.chatWindowEle[0].style.left = position.left;
            this.chatWindowEle[0].style.top = position.top;
            this.chatWindowEle[0].style.zIndex = `${position.zIndex}`;
            this.chatWindowEle[0].style.display = 'block';
            this.position = position;
            return this;
        },

        /**
         * minimizeChatWindow
         * @param data
         * @param el
         */
        minimizeChatWindow: function (data, el) {
            this.chatWindowEle.removeClass('_maxmimize')
                .addClass('hidden')
                .addClass('_minimize');
            this.minimized(true);
            this.isActive(false);
            this.setPostion({
                top: this.chatWindowEle[0].style.top,
                left: this.chatWindowEle[0].style.left,
                zIndex: this.chatWindowEle[0].style.zIndex,
            });
            this.parentChatApp.rePositionAllChatBox();
            this.parentChatApp.getRegion('customerChatScreen').valueHasMutated();
        },
        /**
         *
         */
        setPostion: function (postion) {
            this.position = postion;
            return this;
        },

        /**
         * maxmizeChatWindow
         * @param data
         * @param el
         */
        maxmizeChatWindow: function (data, el) {
            this.chatWindowEle
                .removeClass('_minimize')
                .removeClass('hidden')
                .addClass('_maxmimize')
                .addClass('_show');
            this.minimized(false);
            this.isActive(true);
            this.parentChatApp.rePositionAllChatBox();
            this.parentChatApp.getRegion('customerChatScreen').valueHasMutated();

        },
        /**
         * showChatWindow, it different with maximize or minimize action
         */
        showChatWindow: function (force) {
            this.maxmizeChatWindow();
        },
        /**
         * showHideSellerChatWindow
         */
        showHideSellerChatWindow: function () {

        },
        /**
         * showCustomerOptions
         */
        showCustomerOptionsHandle: function () {
            const customerOptions = this.chatWindowEle.find(
                '[data-role=\'wk-chat-customer-options\']'
            );
            if (customerOptions.length) {
                customerOptions.addClass('_show');
            }
        },

        /**
         * closeCustomerOptions
         */
        closeCustomerOptions: function () {
            const customerOptions = this.chatWindowEle.find(
                '[data-role=\'wk-chat-customer-options\']'
            );
            if (customerOptions.length) {
                customerOptions.removeClass('_show');
            }
        },
        /**
         * visitProfile
         */
        visitProfile: function () {
            const profile = this.chatWindowEle.find(
                '[data-role=\'customer-profile\']'
            );
            if (profile.length) {
                profile.addClass('_show').css('display', 'block');
                this.closeCustomerOptions();
            }
        },

        /**
         * closeSellerChatWindow
         */
        closeSellerChatWindow: function () {
            this.parentChatApp.deleteChatScreen(this.identify);
        },
        /**
         *
         */
        closeProfilePanel: function () {
            const profile = this.chatWindowEle.find(
                '[data-role=\'customer-profile\']'
            );
            if (profile.length) {
                profile.removeClass('_show').css('display', 'none');
            }
        },

        /**
         * closeDropDowns
         */
        closeDropDowns: function () {

        },

        /**
         * unblockCustomer
         */
        unblockCustomer: function () {

        },

        /**
         * blockCustomer
         */
        blockCustomer: function () {

        },

        /**
         * showControlList
         */
        showControlList: function () {

        },

        /**
         * typingMessage
         */
        typingMessage: function (data, event) {
            const message = $(event.currentTarget).val(),
                emitFunction = 'sellerTypingMessageSend';
            if (message) {
                this.getChatApp().emitSocketMessageToCustomer(emitFunction, {
                    name: this.conversationData().sellerName,
                    conversationUniqueId: this.conversationData().conversationUniqueId,
                    message: message,
                    receiverList: [
                        this.conversationData().customerUniqueId
                    ]
                })
            }
        },

        /**
         *
         * @param form
         * @returns {*|boolean}
         */
        sendCustomerMessage: function (form) {
            const self = this,
                sendingMessage = this.getMessageFromForm(form),
                emitAction = 'sendMessageToCustomer',
                uniqueId = this.UNTIL.messageUniqueIdGenerate();
            this.closeEmojityPopup();
            if (!this.validateMessage(sendingMessage)) {
                return false;
            }
            $(form).trigger('reset');
            this.ajaxSaveMessage(sendingMessage, {
                'conversationId': this.conversationData().conversationUniqueId,
                'senderUniqueId': this.conversationData().sellerUniqueId,
                'receiverUniqueId': this.conversationData().customerUniqueId,
                'unique_id': uniqueId
            }, function (res) {
                if (res.errors) {
                    alert('Something error');
                }
            });
            // push new message into chat box
            const message = this.createNewMessage(
                {
                    'unique_id': uniqueId,
                    'message': sendingMessage,
                    'messageType': this.MessageType.text,
                    'receiverName': this.conversationData().customerName,
                    'receiverUniqueId': this.conversationData().customerUniqueId,
                    'senderName': this.conversationData().sellerName,
                    'senderUniqueId': this.conversationData().sellerUniqueId,
                }
            );
            self.appendMessages([message], this.DIRECTION.NEXT)
            self.scrollToLastMessage();
            // emit new message to customer socket
            self.getChatApp().emitSocketMessageToCustomer(emitAction, message);
            self.getChatApp().playSound();
            return this;
        },

        /**
         * Track when seller add file to chat box
         * newMediaAddedHandler
         * Process media if it added from mediaUpload Component
         */
        newMediaAddedHandler: function (newValue) {
            const self = this,
                emitAction = 'sendMessageToCustomer',
                uniqueId = this.UNTIL.messageUniqueIdGenerate();
            if (newValue.length) {
                const meta = this.UNTIL.convertRawMediaToMetaMessage(newValue[0],
                    ['url', 'file', 'name', 'type']
                ), msgType = this.MessageType.fromMediaExtensionToMessageType(
                    newValue[0].type
                );
                const inputPayLoad = {
                    'conversationId': this.conversationData().conversationUniqueId,
                    'senderUniqueId': this.conversationData().sellerUniqueId,
                    'receiverUniqueId': this.conversationData().customerUniqueId,
                    'message': newValue[0].name,
                    'dateTime': this.UNTIL.toServerDate(),
                    'msgType': msgType,
                    'uniqueId': uniqueId,
                    'meta': meta
                };
                this.ajaxSaveMessage(newValue[0].name, inputPayLoad, function (res) {
                    console.log('Upload successfully');
                });
                // push message to chatbox
                const message = this.createNewMessage({
                    'message': newValue[0].name,
                    'unique_id': uniqueId,
                    'messageType': msgType,
                    'receiverName': this.conversationData().customerName,
                    'receiverUniqueId': this.conversationData().customerUniqueId,
                    'senderName': this.conversationData().sellerName,
                    'senderUniqueId': this.conversationData().sellerUniqueId,
                    'meta': meta
                });
                self.appendMessages([message], this.DIRECTION.NEXT)
                self.scrollToLastMessage();
                // emit message to customer
                self.getChatApp().emitSocketMessageToCustomer(emitAction, message);
            }
        },
        /**
         *
         */
        afterLoadRecentlyAction:function (res){
            const convestation = this.getChatApp().getConverstationById(this.conversationData().conversationUniqueId);
            if (convestation) {
                convestation.sellerTotalUnreadMessages = res.totalUnreadMessages;
                this.getChatApp().upsertConversation(convestation);
            }
        },
    });
});
