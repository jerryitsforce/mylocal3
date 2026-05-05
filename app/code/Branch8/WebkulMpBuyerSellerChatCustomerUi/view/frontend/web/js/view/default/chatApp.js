/**********************************
 Control all chat Screen
 *********************************/
define([
    'ko',
    'jquery',
    'Branch8_WebkulMpBuyerSellerChat/js/view/abstractChatApp',
    'uiRegistry',
    'uiLayout',
    'mageUtils',
    'Branch8_WebkulMpBuyerSellerChat/js/model/status',
    'Branch8_WebkulMpBuyerSellerChat/js/model/SettingManager',
    'Branch8_WebkulMpBuyerSellerChat/js/model/uiConfig',
    'Branch8_WebkulMpBuyerSellerChat/js/model/until',
    'Branch8_WebkulMpBuyerSellerChat/js/model/direction',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/model/replyBoxList',
    'Branch8_WebkulMpBuyerSellerChat/js/model/socketCommunicateObject',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/profile-chat-change-status',
    'niceScroll'
], function (
    ko,
    $,
    AbstractChatApp,
    uiRegistry,
    layout,
    mageUtils,
    STATUS,
    SettingManager,
    uiConfig,
    until,
    direction,
    replyBoxList,
    socketCommunicateObject,
    profileChangeStatusAction
) {
    const chatScreenTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/view/default/chatApp/chatScreen',
        displayArea: 'chatScreen'
    };

    'use strict';
    return AbstractChatApp.extend({
        STATUS: STATUS,
        isConnected: false,
        SettingManager: SettingManager,
        listSeller: '[data-role=\'list-sellers\']',
        defaults: {
            imports: {
                "conversationList": "${ $.parentName }:conversationList",
                "customerChatProfile": "${ $.parentName }:customerChatProfile",
                "profileData": "${ $.parentName }:customerChatProfile"
            }
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            this.listenAndCorrespondEventFromSocket();
            if (this.customerChatProfile()) {
                this.connectToSocket();
            }
            return this;
        },
        /**
         *
         * @returns {*}
         */
        connectToSocket: function () {
            if (this.isConnected === true) {
                return;
            }
            socketCommunicateObject.setCustomerConnected(
                this.customerChatProfile()
            );
            this.isConnected = true;
            return this;
        },
        /**
         * getContactClass
         * @param data
         */
        getContactClass: function (data) {
            let activeClass = 'contact';
            if (this.currentConversationData() &&
                this.currentConversationData().conversationUniqueId === data.conversationUniqueId) {
                activeClass = activeClass += ' active';
            }
            return activeClass;
        },
        /**
         *
         * @param input
         * @returns {string}
         */
        getCssStatus: function (input) {
            let value = parseInt(input);
            return 'status ' + STATUS.getChatStatusClasses(value);
        },
        /**
         *
         * @param value
         * @returns {*}
         */
        getChatStatusLabel: function (value) {
            return STATUS.getChatStatusLabel(value);
        },
        /**
         *
         * @param conversationUniqueId
         */
        getChatScreenInstance: function (conversationUniqueId) {
            const identify = this.getReplyChatBoxIdentify(
                conversationUniqueId
            ), instances = this.getRegion('chatScreen')
                .filter(function (component) {
                    return component.identify === identify
                })
            if (instances.length) {
                return instances[0];
            }
            return null;
        },
        /**
         *
         */
        afterRender: function () {
            $(this.listSeller).niceScroll(uiConfig.niceScrollConfig);
        },
        /**
         *
         * @param conversationData
         */
        sellerRowClick: function (conversationData) {
            this.activeChatScreen(conversationData);
            // reset counter
            this.resetUnreadMessageCounter(conversationData.conversationUniqueId);
        },
        /**
         * Active Chat Reply Box by key
         */
        activeChatScreen: function (conversationData) {
            const identify = this.getReplyChatBoxIdentify(
                conversationData.conversationUniqueId
            ), elems = this.getRegion('chatScreen')();
            if (replyBoxList[identify] === undefined) {
                this.createChatScreenComponent(conversationData);
            }
            this.deActiveChatScreen(identify);
            elems.forEach(function (component) {
                if (component.identify === identify) {
                    component.visible(true);
                }
            })
            this.currentConversationData(conversationData);
            return this;
        },

        /**
         *
         * @param ignore
         * @returns {*}
         */
        deActiveChatScreen: function (ignore) {
            const self = this, elems = this.getRegion('chatScreen')();
            elems.forEach(function (component) {
                const identify = self.getReplyChatBoxIdentify(
                    component.conversationData().conversationUniqueId
                );
                if (identify !== ignore) {
                    component.visible(false)
                }
            })
            return this;
        },
        /**
         *
         * @param conversationUniqueId
         * @returns {string}
         */
        getReplyChatBoxIdentify: function (conversationUniqueId) {
            return "replyChatbox" + '_' + conversationUniqueId;
        },
        /**
         *
         * @param conversationData
         */
        createChatScreenComponent: async function (conversationData) {
            const identify = this.getReplyChatBoxIdentify(
                conversationData.conversationUniqueId
            );
            let templateData, rendererComponent;
            replyBoxList[identify] = conversationData;
            templateData = {
                parentName: this.name,
                name: identify,
                identify: identify
            };
            rendererComponent = mageUtils.template(chatScreenTemplate, templateData);
            mageUtils.extend(rendererComponent, {
                identify: identify,
                defaultConversationData: conversationData,
                defaultVisible: true,
                defaultShowLoader: false,
                generalConfig: this.generalConfig
            });
            layout([rendererComponent]);
        },
        /**
         *
         */
        minimizeChatWindow: function () {
            const parent = uiRegistry.get(this.parentName, function (component) {
                component.hideChatBox();
            });
        },

        /**
         * changeChatStatus
         */
        changeChatStatus: function (data, event) {
            const statusOptions = $('#status-options'),
                profileImage = $('#profile-img'),
                input = $(event.currentTarget).data('status');
            profileChangeStatusAction({
                'unique_id': this.customerChatProfile().uniqueId,
                'status': input,
            })
            /**********************************
             1.Notify to all seller I changed status
             2.Update avatar with status on demand
             **********************************/
            profileImage.removeClass()
                .addClass(this.getCssStatus(input));
            statusOptions.removeClass('active')
            /**********************************
             Emit socket
             **********************************/
            const emitData = {
                status: input,
                uniqueID: this.customerChatProfile().uniqueId,
                receiverList: this.getReceiverList()
            }, socketAction = 'customerStatusChange';
            this.emitSocketMessageToSeller(socketAction, emitData);
        },
        /**
         *
         */
        enableDisableSound: function (data, event) {
            if ($(event.target).hasClass('disable')) {
                $(event.target)
                    .addClass('enable')
                    .addClass('fa-volume-up')
                    .removeClass('disable')
                    .removeClass('fa-volume-off');
                this.SettingManager.setSetting('sound', 'enable');
            } else {
                $(event.target)
                    .addClass('disable')
                    .addClass('fa-volume-off')
                    .removeClass('enable')
                    .removeClass('fa-volume-up');
                this.SettingManager.setSetting('sound', 'disable');
            }
            this.playSound();
        },

        /**
         * playSound
         */
        playSound: function () {
            if (this.SettingManager.getSetting('sound') === 'enable') {
                $('[data-role=\'model-chat-controls\']').find('#myAudio').get(0).play();
            }
        },
        /**
         * showStatusSelections
         */
        showHideStatusSelection: function (force) {
            const statusOptions = $('#status-options');
            if (statusOptions.hasClass('active')) {
                statusOptions.removeClass('active');
            } else {
                statusOptions.addClass('active');
            }
        },
        /**
         * This function track when use upload new image profile
         */
        useNewProfileImageHandler: function (newImage) {
            this.closeChangeImagePanel();
            /**********************************
             1. Update profile
             2. Notify to sellers ,your profile image was changed
             **********************************/
            let profileData = this.customerChatProfile();
            profileData.image = newImage.url
            this.customerChatProfile(profileData);
            const action = 'customerProfileChange',
                sellers = this.getReceiverList(),
                emitData = {
                    profileData: profileData,
                    receiverList: sellers
                };
            this.emitSocketMessageToSeller(action, emitData);
        },

        /**
         * getReceiverList
         */
        getReceiverList: function () {
            return this.conversationList().map(function (conversation) {
                return conversation.sellerUniqueId
            })
        },
        /**
         * closeChangeImageScreen
         */
        closeChangeImagePanel: function () {
            $('[data-role=\'chatPanel\']').removeClass('open-panel-profile');
        },

        /**
         * openChangeImagePanel
         */
        openChangeImagePanel: function () {
            $('[data-role=\'chatPanel\']').addClass('open-panel-profile');
        },
        /**
         * getChatProfile
         */
        getChatProfile: function () {
            return this.profileData;
        },
        /****************************************
         SOCKET PROCESS
         *****************************************/
        /**
         *
         * @param action
         * @param details
         */
        emitSocketMessageToSeller: function (action, details) {
            if (Array.isArray(details)) {
                details.forEach(function (detail) {
                    socketCommunicateObject[action](detail);
                })
            } else {
                socketCommunicateObject[action](details);
            }
        },
        /**
         * listenAndCorrespondEventFromSocket
         */
        listenAndCorrespondEventFromSocket: function () {
            const self = this;
            /**
             * Receive new message from Seller
             */
            socketCommunicateObject.isCustomerHasNewMessage().subscribe(
                self.customerMessageReceivedHandler.bind(self)
            );
            /**
             * Receive new status signature from seller
             */
            socketCommunicateObject.isSellerStatusChanged().subscribe(
                self.sellerStatusChangeHandler.bind(self)
            );

            /**
             * Receive change profile status signature from seller
             */
            socketCommunicateObject.isSellerProfileChanged().subscribe(
                self.sellerProfileChangeHandler.bind(self)
            );

            /**
             * Receive change profile status signature from seller
             */
            socketCommunicateObject.isSellerTypingMessage().subscribe(
                self.isSellerTypingMessageHandler.bind(self)
            );
        },
        /**
         *
         * @param newMessage
         * @returns {Promise<boolean>}
         */
        customerMessageReceivedHandler: async function (newMessage) {
            const conversationUniqueId = newMessage.conversationUniqueId,
                chatScreenInstance = this.getChatScreenInstance(conversationUniqueId),
                self = this;
            /**
             *  1. if chat screen is active => Push message to seller chat screen
             *  2. if chat screen not active but have instance => Push message to seller chat screen
             *  3. if not have instance, nothing to do , message autoload when click on menu seller
             */

            if (!chatScreenInstance) {
                this.increaseMessageCounterByOne(conversationUniqueId);
                return false;
            }
            chatScreenInstance.appendMessages(
                [newMessage], chatScreenInstance.DIRECTION.NEXT
            );
            /**
             * if is active state , scroll to last message otherwise add counter
             *
             */
            if (chatScreenInstance.visible()) {
                chatScreenInstance.scrollToLastMessage();
            } else {
                this.increaseMessageCounterByOne(conversationUniqueId);
                this.refreshChatConversationData(chatScreenInstance);
            }
            this.playSound();
        },
        /**
         *
         * @param chatInstance
         */
        refreshChatConversationData: function (chatInstance) {
            const conversationData = this.conversationList().filter(function (item) {
                if (item.conversationUniqueId === chatInstance.conversationData().conversationUniqueId) {
                    return item;
                }
            });
            if (conversationData.length) {
                chatInstance.conversationData(conversationData[0]);
            }
        },
        /**
         *
         * @returns {string}
         */
        getCounterKey: function () {
            return 'customerTotalUnreadMessages'
        },
        /**
         *
         * @param newMessage
         * @returns {Promise<boolean>}
         */
        sellerStatusChangeHandler: async function (newMessage) {
            const {status, uniqueId} = newMessage;
            let currentConversation = this.currentConversationData();
            const modifiedConversationList = this.conversationList().map(function (item) {
                if (item.sellerUniqueId === uniqueId) {
                    item.sellerChatStatus = status
                }
                return item;
            });
            this.getRegion('chatScreen')().map(function (component) {
                if (component.conversationData().sellerUniqueId === uniqueId) {
                    let modified = component.conversationData();
                    modified.sellerChatStatus = status;
                    component.conversationData(modified)
                }
                return component;
            })
            if (currentConversation && currentConversation.sellerUniqueId === uniqueId
            ) {
                currentConversation.sellerChatStatus = status;
                this.currentConversationData(currentConversation);
            }

            this.refreshContactList(modifiedConversationList);
        },

        /**
         *
         * @param modifiedConversationList
         */
        refreshContactList: function (modifiedConversationList) {
            this.conversationList([]);
            this.conversationList(modifiedConversationList);
            this.conversationList.notifySubscribers();
        },

        /**
         * sellerProfileChangeHandler
         * @param newMessage
         * @returns {Promise<void>}
         */
        sellerProfileChangeHandler: async function (newMessage) {
            const {image, name, status, uniqueId} = newMessage;
            const modifiedConversationList = this.conversationList().map(function (item) {
                if (item.sellerUniqueId === uniqueId) {
                    item.sellerImage = image
                }
                return item;
            });
            this.getRegion('chatScreen')().map(function (component) {
                if (component.conversationData().sellerUniqueId === uniqueId) {
                    let modified = component.conversationData();
                    modified.sellerImage = image;
                    component.conversationData(modified)
                }
                return component;
            });
            this.refreshContactList(modifiedConversationList);
        },

        /**
         * isSellerTypingMessageHandler
         */
        isSellerTypingMessageHandler: function (detail) {
            const {
                conversationUniqueId,
                message,
                name,
                uniqueId
            } = detail;
            const typingMessage = $.mage.__('%1 is typing message').replace("%1", name);
            const chatScreen = this.getChatScreenInstance(conversationUniqueId);
            if (chatScreen) {
                chatScreen.setTypingMessage(typingMessage);
            }
        },
        /****************************************
         END SOCKET PROCESS
         *****************************************/
    })
})
