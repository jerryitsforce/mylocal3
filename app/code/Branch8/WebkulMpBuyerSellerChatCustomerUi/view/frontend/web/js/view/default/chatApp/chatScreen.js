define([
    'jquery',
    'underscore',
    'uiRegistry',
    'mageUtils',
    'Branch8_WebkulMpBuyerSellerChat/js/view/abstractChatScreen',
    'Branch8_WebkulMpBuyerSellerChat/js/model/uiConfig',
    'niceScroll',
], function (
    $,
    _,
    uiRegistry,
    mageUtils,
    AbstractChatScreen,
    uiConfig
) {
    'use strict';

    return AbstractChatScreen.extend({
        niceScrollElement: null,
        chatApp: null,
        defaults: {
            imports: {
                "customerChatProfile": "${ $.parentName }:customerChatProfile"
            },
            template: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/default/chatApp/chatScreen',
            emojiTyTemplate: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/default/chatApp/chatScreen/emojity',
            fileUploadTemplate: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/default/chatApp/chatScreen/media',
            counterScroll: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/default/chatApp/chatScreen/scrollButton',
            typingMessageTemplate: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/default/chatApp/chatScreen/typingMessage',
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            // call to create media uploader;
            this.initChildren();
            this.debounceHandleTypingMessage = _.debounce(this.typingMessage, 300);
            return this;
        },
        /**
         * Handle to auto update last read message
         */
        getIsChatScreenActive: function () {
            return this.visible() === true;
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'customerChatProfile'
                ]);
            this.chatScreenId = '#chat-screen-' + this.conversationData().conversationUniqueId;
            // update last read message interval
            setInterval(function () {
                self.trackLastReadMessage();
            }, 3000)
            return this;
        },

        /**
         *
         * @param utcDateTimeString
         * @returns {string|*}
         */
        toLocaleDateTime: function (utcDateTimeString) {
            return until.toLocaleDateTime(utcDateTimeString);
        },
        /**
         *
         * @param form
         * @returns {boolean}
         */
        sendMessage: function (form) {
            const self = this,
                sendingMessage = this.getMessageFromForm(form),
                emitAction = 'sendMessageToSeller',
                uniqueId = this.UNTIL.messageUniqueIdGenerate();
            this.closeEmojityPopup();
            if (!this.validateMessage(sendingMessage)) {
                return false;
            }
            $(form).trigger('reset');
            this.ajaxSaveMessage(sendingMessage, {
                'conversationId': this.conversationData().conversationUniqueId,
                'senderUniqueId': this.conversationData().customerUniqueId,
                'receiverUniqueId': this.conversationData().sellerUniqueId,
                'uniqueId': uniqueId,
            }, function (res) {
                if (res.errors) {
                    alert('Something error');
                }
            });
            /* append message to list */
            const message = this.createNewMessage(
                {
                    'unique_id': uniqueId,
                    'message': sendingMessage,
                    'messageType': this.MessageType.text,
                    'receiverName': this.conversationData().sellerName,
                    'receiverUniqueId': this.conversationData().sellerUniqueId,
                    'senderName': this.conversationData().customerName,
                    'senderUniqueId': this.conversationData().customerUniqueId,
                }
            );
            self.appendMessages([message], this.DIRECTION.NEXT)
            self.scrollToLastMessage();
            /*  emit message to seller by socket*/
            this.getChatApp().emitSocketMessageToSeller(
                emitAction, message
            );
            self.getChatApp().playSound();
        },
        /**
         * Track when customer add file to chat box
         * newMediaAddedHandler
         * Process media if it added from mediaUpload Component
         */
        newMediaAddedHandler: function (newValue) {
            const self = this,
                uniqueId = this.UNTIL.messageUniqueIdGenerate();
            if (newValue.length) {
                const meta = this.UNTIL.convertRawMediaToMetaMessage(
                    newValue[0],
                    ['url', 'file', 'name', 'type']
                ), msgType = this.MessageType.fromMediaExtensionToMessageType(
                    newValue[0].type
                ), inputPayLoad = {
                    'conversationId': this.conversationData().conversationUniqueId,
                    'senderUniqueId': this.conversationData().customerUniqueId,
                    'receiverUniqueId': this.conversationData().sellerUniqueId,
                    'message': newValue[0].name,
                    'dateTime': this.UNTIL.toServerDate(),
                    'msgType': this.MessageType.image,
                    'unique_id': uniqueId,
                    'meta': meta
                }, emitAction = 'sendMessageToSeller';
                this.ajaxSaveMessage(newValue[0].name, inputPayLoad);
                // push message to chatbox
                const message = this.createNewMessage({
                    'message': newValue[0].name,
                    'senderUniqueId': this.conversationData().customerUniqueId,
                    'receiverUniqueId': this.conversationData().sellerUniqueId,
                    'messageType': msgType,
                    'receiverName': this.conversationData().sellerName,
                    'senderName': this.conversationData().customerName,
                    'unique_id': uniqueId,
                    'meta': meta
                });
                self.appendMessages([message], this.DIRECTION.NEXT)
                self.scrollToLastMessage();
                // emit message to seller by socket
                this.getChatApp().emitSocketMessageToSeller(
                    emitAction, message
                );
            }
        },
        /**
         * getChatProfile
         */
        getChatProfile: function () {
            return this.customerChatProfile();
        },

        /**
         * getMessageImage
         */
        getMessageImage: function (message) {
            const messageOfYou = message.senderUniqueId === this.getChatProfile().uniqueId;
            return messageOfYou ? this.customerChatProfile().image : this.conversationData().sellerImage;
        },
        /**
         * isActive
         */
        shouldTrackLastReadMessage: function () {
            return this.visible() === true;
        },
        /**
         *
         */
        scrollerClick: function () {
            this.scrollToLastMessage();
            let conversationData = this.conversationData();
            conversationData.customerTotalUnreadMessages = 0;
            this.conversationData(conversationData);
        },
        /**
         * typingMessage
         */
        typingMessage: function (data, event) {
            const message = $(event.currentTarget).val(),
                emitFunction = 'customerTypingMessageSend';
            if (message) {
                this.getChatApp().emitSocketMessageToSeller(emitFunction, {
                    name: this.conversationData().customerName,
                    conversationUniqueId:this.conversationData().conversationUniqueId,
                    message: message,
                    receiverList: [
                        this.conversationData().sellerUniqueId
                    ]
                })
            }
        },
    });
});
