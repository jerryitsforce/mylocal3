define([
    'ko',
    'jquery',
    'underscore',
    'mageUtils',
    'Branch8_WebkulMpBuyerSellerChat/js/view/abstractChatApp',
    'uiLayout',
    'Branch8_WebkulMpBuyerSellerChat/js/model/SettingManager',
    'Branch8_WebkulMpBuyerSellerChat/js/model/status',
    'Branch8_WebkulMpBuyerSellerChatSellerUi/js/model/chatBoxPositionCalculator',
    'Webkul_MpBuyerSellerChat/js/model/socket-provider',
    'Branch8_WebkulMpBuyerSellerChat/js/model/socketCommunicateObject',
    'Branch8_WebkulMpBuyerSellerChatSellerUi/js/action/enable-seller-chat',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/profile-chat-change-status',
    'Magento_Ui/js/modal/alert',
    'domReady'
], function (
    ko,
    $,
    _,
    mageUtils,
    AbstractChatApp,
    layout,
    SettingManager,
    STATUS,
    chatBoxPositionCalculator,
    socketProvider,
    socketCommunicateObject,
    startChatAction,
    profileChangeStatusAction,
    alert
) {
    'use strict';

    var customerChatBoxTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Branch8_WebkulMpBuyerSellerChatSellerUi/js/view/active-model/chatScreen',
        displayArea: 'customerChatScreen'
    };

    return AbstractChatApp.extend({
        SettingManager: SettingManager,
        STATUS: STATUS,
        chatFrameContainer: '',
        isServerRunning: socketProvider.isServerRunning,
        getSoundUrl: window.chatboxCoreConfig.soundUrl,
        defaults: {
            template: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/active-model',
            headerTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/header',
            profileBoxTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/profileBox',
            chatConversationsTemplate: 'Branch8_WebkulMpBuyerSellerChatSellerUi/view/activeModel/chatConversationsList'
        },
        currentConversationPageSize: 1,
        conversationList: ko.observableArray([]),
        conversationListMap: new Map(),
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            const self = this;
            this._super();
            this.isServerRunning = socketProvider.isServerRunning;
            socketCommunicateObject.setSellerConected(this.chatProfile);
            this.listenAndCorrespondEventFromSocket();
            $(window).resize(this.rePositionAllChatBox.bind(this));
            this.totalUnreadMessages(this.chatProfile.totalUnreadMessages);
            return this
        },
        /**
         *
         */
        getAllActiveWindowChats: function () {
            const self = this;
            return self.getRegion('customerChatScreen')().filter(function (e) {
                return e.isActive() === true;
            });
        },
        /**
         *
         */
        loadConversations: function () {
            const ajaxLoadConversation = this.generalConfig.ajaxLoadConversation,
                ajaxLoadChatConversationUrl = this.generalConfig.ajaxLoadConversationUrl += '?' + new Date().getTime();
            if (ajaxLoadConversation === true) {
                const self = this,
                    chatData = $('#chat-data'),
                    chatList = chatData.find('.chatList');
                chatList.addClass('loader');
                setTimeout(function (){
                    self.ajaxLoadChatConversation(
                        self.currentConversationPageSize, ajaxLoadChatConversationUrl
                    ).done(function (conversations) {
                        conversations.forEach(function (item) {
                            self.upsertConversation(item);
                        })
                    }).fail(function (err) {
                        console.error('Error:', err);
                        // handle error
                    }).always(function () {
                        chatList.removeClass('loader');
                    });
                },300)
            }
        },

        /**
         *
         * @param conv
         */
        upsertConversation: function (conversation) {
            const existing = this.conversationListMap.get(conversation.conversationUniqueId);
            if (existing) {
                // Merge new data
                Object.assign(existing, conversation);
                // Reposition in sorted array
                this._moveToCorrectPosition(existing);
            } else {
                // New conversation
                this.conversationListMap.set(conversation.conversationUniqueId, conversation);
                this._insertInSortedPosition(conversation);
            }
        },
        /**
         * Insert maintaining sorted order (binary search)
         */
        _insertInSortedPosition(conversation) {
            let low = 0, high = this.conversationList().length;
            while (low < high) {
                const mid = (low + high) >> 1; // midpoint
                if (this._compareConversation(conversation, this.conversationList()[mid]) > 0) {
                    low = mid + 1;
                } else {
                    high = mid;
                }
            }
            this.conversationList.splice(low, 0, conversation);
        },
        /**
         * Remove and re-insert to correct position
         */
        _moveToCorrectPosition(conversation) {
            const index = this.conversationList().indexOf(conversation);
            if (index > -1) {
                this.conversationList().splice(index, 1);
            }
            this._insertInSortedPosition(conversation);
        },
        /**
         *
         * @param a
         * @param b
         * @returns {number}
         * @private
         */
        _compareConversation: function (a, b) {
            const unreadA = a.sellerTotalUnreadMessages > 0 ? 1 : 0;
            const unreadB = b.sellerTotalUnreadMessages > 0 ? 1 : 0;
            if (unreadA !== unreadB) {
                return unreadB - unreadA; // unread first
            }
            return b.lastUpdateUCTimestamp - a.lastUpdateUCTimestamp; // newest first
        },
        /**
         *
         * @param page
         * @param url
         * @returns {*|jQuery}
         */
        ajaxLoadChatConversation: function (page, url) {
            const deferred = $.Deferred();
            const requestUrl = url += '?' + new Date().getTime();
            $.ajax({
                url: requestUrl,
                method: 'GET',
                data: {
                    page: page
                }
            }).done(function (data) {
                deferred.resolve(data);
            }).fail(function (error) {
                deferred.reject(error);
            });
            return deferred.promise();
        },
        /**
         *
         */
        rePositionAllChatBox: function () {
            let index=0;
            const elems = this.getAllActiveWindowChats().map(e => {
                e.screenIndex = index++;
                return e;
            });
            if (elems && elems.length) {
                chatBoxPositionCalculator.rePositionForAllChatBox(elems);
            }
        },
        /**
         *
         */
        rePositionAllMinizedChatBox: function () {
            let index=0;
            const elems = this.getAllActiveWindowChats().map(e => {
                e.screenIndex = index++;
                return e;
            });
            if (elems && elems.length) {
                chatBoxPositionCalculator.rePositionForAllChatBox(elems);
            }
        },
        /**
         * open chat panel in scale page layout mode if set to true
         */
        checkScaleOut: function () {
            const self = this;
            if (this.SettingManager.getSetting('maximize') === true) {
                self.openChatPanel();
            }
        },


        /**
         * chatFrameContainerRenderAfter
         * @param data
         * @param event
         */
        chatFrameContainerRenderAfter: function (data, event) {
            this.chatFrameContainer = $("#chat_window_container");
            chatBoxPositionCalculator.setContainer(this.chatFrameContainer);
        },

        /**
         * openChatPanel
         * open seller right chat panel
         */
        openChatPanel: function (data, el) {
            const self = this,
                ele = $('.chat__menu'),
                chatData = $('#chat-data'),
                scalePageLayout = this.SettingManager.getSetting('scale_page_layout');
            this.showProfileBox(false);
            ele.addClass('_show');
            if (ele.hasClass('fixed')) {
                ele.removeClass('fixed');
            }
            chatData.find('.model-search-bar').show();
            chatData.find('.server-error').css("display", "block");
            chatData.find('.chatList').show();

            if (scalePageLayout) {
                self.scalePageLayout();
            } else {
                self.overlayPageLayout();
            }
            this.maximize(true);
            this.SettingManager.setSetting('maximize', true)
        },

        /**
         * closeChatPanel
         */
        closeChatPanel: function () {
            const chatDataElement = $('#chat-data'),
                body = $('body'), chatMenu = $('.chat__menu'),
                ele = $('.seller-chat-controls');
            chatMenu.removeClass('_show');
            chatDataElement.find('.model-search-bar').hide();
            chatDataElement.find('.server-error').hide();
            chatDataElement.find('.chatList').hide();
            if (ele.hasClass('_expanded')
                && ele.children().hasClass('_show')
            ) {
                ele.removeClass('_expanded');
                ele.children().removeClass('_show');
            }
            if (this.SettingManager.getSetting('scale_page_layout')) {
                if (body.hasClass('scale')) {
                    body.removeClass('scale');
                }
            } else {
                this.overlayPageLayout();
            }
            this.maximize(false);
            this.SettingManager.setSetting('maximize', false)
        },

        /**
         * return seller side green check vector
         */
        greenCheckImage: function () {
            return window.chatboxCoreConfig.greenCheck;
        },

        /**
         * right panel controls manage
         */
        showControlList: function (model, event) {
            const ele = $('.seller-chat-controls');
            if (event.target.className === 'seller_controls') {
                if (ele.hasClass('_expanded')
                    && ele.children().hasClass('_show')) {
                    ele.removeClass('_expanded');
                    ele.children().removeClass('_show');
                }
                if ($(event.target).siblings().hasClass('_expended')) {
                    $(event.target).siblings().removeClass('_expended');
                    $(event.target).siblings().hide();
                } else {
                    $(event.target).siblings().addClass('_expended');
                    $(event.target).siblings().show();
                }
            }
        },
        /**
         *
         * @returns {*}
         */
        sellerControlsResponsive: function () {
            return window.chatboxCoreConfig.minimize;
        },
        /**
         *
         */
        overlayPageLayout: function () {
            const overlay = $("#overlay"),
                body = $('body');
            if (body.hasClass('scale')) {
                body.removeClass('scale');
            }
            if (overlay.siblings('.control-group-item').hasClass('weight')
                && overlay.siblings('.check-scale-page').hasClass('checked')) {
                overlay.siblings('.control-group-item').removeClass('weight');
                overlay.siblings('.check-scale-page').removeClass('checked');
            }
            overlay.addClass('weight');
            overlay.siblings('.check-overlay').addClass('checked');
            this.SettingManager.removeSetting('scale_page_layout');
        },

        /**
         * scale page layout
         */
        scalePageLayout: function (event) {
            var scalePageLayoutElement = $("#scale_page_layout"),
                body = $('body');
            if (!body.hasClass('scale')) {
                body.addClass('scale');
            }
            if (scalePageLayoutElement.siblings('.control-group-item').hasClass('weight')
                && scalePageLayoutElement.siblings('.check-overlay').hasClass('checked')) {
                scalePageLayoutElement.siblings('.control-group-item').removeClass('weight');
                scalePageLayoutElement.siblings('.check-overlay').removeClass('checked');
            }
            scalePageLayoutElement.addClass('weight');
            scalePageLayoutElement.siblings('.check-scale-page').addClass('checked');
            this.SettingManager.setSetting('scale_page_layout', true);
        },
        /**
         *
         * @param action
         * @param details
         */
        emitSocketMessageToCustomer: function (action, details) {
            if (Array.isArray(details)) {
                details.forEach(function (detail) {
                    socketCommunicateObject[action](detail);
                })
            } else {
                socketCommunicateObject[action](details);
            }
        },
        /**
         *
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'chatEnabled',
                    'showSellerLoader',
                    'showProfileBox',
                    'sellerChatStatus',
                    'maximize',
                    'profileData',
                    'minizedItems',
                    'minizedItemsRender',
                    'minizedItemsRemainRender'
                ]);
            this.searchContact('');
            this.profileData(this.chatProfile);
            this.chatEnabled(parseInt(this.profileData().status) >= 0);
            this.showSellerLoader(false);
            this.showProfileBox(false);
            this.maximize(false);
            this.filteredConversationList = ko.computed(function () {
                var filter = self.searchContact().toLowerCase();
                if (!filter) {
                    return self.conversationList();
                } else {
                    return ko.utils.arrayFilter(
                        self.conversationList(),
                        function (conversationData) {
                            return conversationData.customerName.toLowerCase().indexOf(filter) !== -1;
                        });
                }
            });
            this.minizedItems = ko.observableArray([]);
            this.minizedItemsRenderer = ko.observableArray([]);
            this.minizedItemsRemainRender = ko.observableArray([]);
            this.minizedItemsRenderer =  ko.computed(() => {
                return self.minizedItems().slice(0, 5);
            });
            this.minizedItemsRemainRender =  ko.computed(() => {
                return self.minizedItems().slice(5);
            });
            self.getRegion('customerChatScreen').subscribe(function (newArray) {
                const minizedItems = newArray.filter(function (e) {
                    return e.minimized() === true;
                })
                self.minizedItems(minizedItems);
            });
            /**
             * Debounce
             * @type {(function(): *)|*}
             */
            self.debouncedFilterContacts = _.debounce(function () {
                self.filteredConversationList();
            }, 300);
            self.searchContact.subscribe(self.debouncedFilterContacts);
            this.conversationList(this.defaultCustomerSellerList);
            return this;
        },

        /**
         * playSound
         */
        playSound: function () {
            if (this.SettingManager.getSetting('sound') === 'enable') {
                $('.model-chat-controls').find('#myAudio').get(0).play();
            }
        },
        /**
         * checkSound
         */
        checkSound: function () {
            if (this.SettingManager.getSetting('sound') === 'disable') {
                $('.model-chat-controls').find('.wk_chat_sound').click();
            } else {
                this.SettingManager.setSetting('sound', 'enable');
            }
        },
        /**
         *
         * @param data
         * @param el
         */
        showHideControls: function (data, el) {
            const element = $(el.target);
            if (this.maximize() === true) {
                if (element.hasClass('_expanded')) {
                    element.children().removeClass("_show");
                    element.removeClass('_expanded');
                } else {
                    this._refreshControls();
                    element.children().addClass("_show");
                    element.addClass('_expanded');
                }
            }
        },

        /**
         *
         */
        hideControlList: function () {
            var target = $('.wk_chat_customer_options');
            var element = $('.wk_seller_controls');
            if (target.hasClass('_show')) {
                target.removeClass('_show');
            }
            if (element.hasClass('_expended')) {
                element.removeClass('_expended');
                element.hide();
            }
        },
        /**
         * hide seller opened controls
         */
        _refreshControls: function () {
            const element = $('.wk_seller_controls');
            if (element.hasClass('_expended')) {
                element.removeClass('_expended');
                element.hide();
            }
        },
        /**
         * return seller retract up vector
         */
        sellerRetractUp: function () {
            return window.chatboxCoreConfig.sellerRetract;
        },

        /**
         * return current status of seller
         */
        getSellerChatStatus() {
            const status = this.profileData().status,
                label = STATUS.getChatStatusLabel(status),
                element = $('.chat_status_seller_options.' + label);
            if (!element.hasClass('weight')) {
                element.addClass('weight');
            }
            return label;
        },
        /**
         *
         */
        showHideProfileBox: function () {
            let element = $(".seller-chat-controls"),
                newValue = !this.showProfileBox();
            element.click();
            this.showProfileBox(newValue);
        },

        /**
         * enableDisableSound
         * @param data
         * @param event
         */
        enableDisableSound: function (data, event) {
            if ($(event.target).hasClass('disable')) {
                $(event.target).removeClass('disable')
                    .removeClass('fa-volume-off')
                    .addClass('enable')
                    .addClass('fa-volume-up');
                this.SettingManager.setSetting('sound', 'enable');
            } else {
                $(event.target).removeClass('enable')
                    .removeClass('fa-volume-up')
                    .addClass('disable')
                    .addClass('fa-volume-off');
                this.SettingManager.setSetting('sound', 'disable');
            }
            this.playSound();
        },

        /**
         * showSelectedImage
         */
        showSelectedImage: function () {
            var oFReader = new FileReader();
            if (!_.isUndefined(document.getElementById("seller_profile_image").files[0])
            ) {
                oFReader.readAsDataURL(document.getElementById("seller_profile_image").files[0]);
                oFReader.onload = function (oFREvent) {
                    document.getElementById("seller-profile-image").src = oFREvent.target.result;
                };
            }
        },
        /**
         *
         * @returns {*}
         */
        sellerControls: function () {
            return window.chatboxCoreConfig.sellerOptions;
        },
        /**
         * hide seller opened controls
         */
        hideSellerControls: function () {
            const element = $('.seller-chat-controls');
            if (screen.width > 768) {
                const target = $('.wk_chat_history_options');
                if (target.hasClass('_expended')) {
                    target.removeClass("_expended");
                    target.hide();
                }
            }
            if (element.hasClass('_expanded')) {
                element.removeClass('_expanded');
                $('.seller-chat-controls-container').removeClass('_show');
            }
        },
        /**
         *
         */
        useNewProfileImageHandler: function (newImage) {
            this.closeChangeImagePanel();
            /**********************************
             1. Update profile
             2. Notify to customer ,your profile image was changed
             **********************************/
            let profileData = this.profileData();
            profileData.image = newImage.url
            this.profileData(profileData);
            const action = 'sellerProfileChange',
                emitData = {
                    profileData: profileData,
                    receiverList: this.getReceiverList()
                };
            this.emitSocketMessageToCustomer(action, emitData);
        },
        /**
         *
         */
        closeChangeImagePanel: function () {
            this.showProfileBox(false);
        },
        /**
         *
         * @param data
         * @param event
         */
        changeSellerStatus(data, event) {
            /**********************************
             1.Notify to all customer I changed status
             2.Chane profile Image in box
             **********************************/
            const value = this.profileData().status,
                label = STATUS.getChatStatusLabel(value),
                chatStatusActiveElement = $('.chat_status_seller_options.' + label),
                newStatus = event.currentTarget.id,
                socketAction = 'sellerStatusChange',
                emitData = {
                    uniqueId: this.profileData().uniqueId,
                    status: newStatus,
                    receiverList: this.getReceiverList()
                };
            let profileData = this.profileData();
            if (chatStatusActiveElement.hasClass('weight')) {
                chatStatusActiveElement.removeClass('weight');
            }
            profileData.status = newStatus;
            this.profileData(profileData);
            $(event.currentTarget).children().addClass("weight");
            profileChangeStatusAction({
                'unique_id': this.profileData().uniqueId,
                'status': newStatus,
                receiverList: this.getReceiverList()
            })
            this.emitSocketMessageToCustomer(socketAction, emitData);
        },

        /**
         * getReceiverList
         */
        getReceiverList: function () {
            return this.conversationList().map(function (conversation) {
                return conversation.customerUniqueId
            })
        },
        /**
         * enableSellerChat
         */
        enableSellerChat: function () {
            const self = this,
                action = 'sellerStatusChange';
            this.showSellerLoader(true);
            /**********************************
             1.notify to All Customer ,Seller Is online
             2.Update status to database
             ***********************************/
            this.emitSocketMessageToCustomer(action, {
                status: STATUS.ONLINE,
                uniqueId: this.profileData().uniqueId,
                receiverList: this.getReceiverList()
            });
            startChatAction({
                'unique_id': this.profileData().uniqueId,
                'status': STATUS.ONLINE
            }, function (res) {
                self.showSellerLoader(false);
                self.chatEnabled(true);
            });
        },

        /**
         *
         */
        checkIsBlocked: function () {

        },

        showblockUserBox: function () {

        },
        /**
         *
         * @param element
         * @param event
         * @returns {*}
         */
        showHidePopover: function (element, event) {
            $(event.currentTarget).hasClass('_show') ? $(event.currentTarget).removeClass('_show') : $(event.currentTarget).addClass('_show')
            return this;
        },
        /**
         * getChatBoxComponentByIdentify
         * @param identify
         * @returns {*|null}
         */
        getChatBoxComponentByIdentify: function (identify) {
            const elems = this.getRegion('customerChatScreen')();
            if (elems && elems.length) {
                const filters = elems.filter(function (component) {
                    return component.identify === identify
                })
                return filters && filters.length > 0 ? filters[0] : null
            }
            return null;
        },
        /**
         * activeChatWindow
         * @param data
         * @param event
         * @returns {*}
         */
        activeChatWindow: function (data, event) {
            const identify = this.getChatBoxIdentify(
                data.conversationUniqueId
            ), elems = this.getRegion('customerChatScreen')();
            if (!this.existChatInstanceInlist(identify)) {
                this.createCustomerChatBox(data);
            } else {
                elems.forEach(function (component, index) {
                    if (component.identify === identify) {
                        component.showChatWindow();
                    }
                })
            }
            return this;
        },
        /**
         *
         * @param data
         * @param component
         * @returns {*}
         */
        showChatWindow: function (data, component) {
            component.chatApp.activeChatWindow(data);
            return this;
        },
        /**
         *
         */
        deleteChatScreen:function (identify){
            const elems = this.getRegion('customerChatScreen')();
            const array = elems.forEach(function (element) {
                if (element.identify === identify) {
                    clearInterval(element.trackLastMessageInterval);
                    element.destroy();
                }
            });
            this.rePositionAllChatBox();
            return this;
        },
        /**
         *
         * @param identify
         * @returns {*|boolean}
         */
        existChatInstanceInlist: function (identify) {
            const elems = this.getRegion('customerChatScreen')();
            const array = elems.filter((element) => element.identify === identify);
            return array && array.length > 0
        },
        /**
         * getChatBoxIdentify
         * @param conversationUniqueId
         * @returns {string}
         */
        getChatBoxIdentify: function (conversationUniqueId) {
            return "customerChatBox" + '_' + conversationUniqueId;
        },

        /**
         * createCustomerChatBox
         * @param conversationData
         * @returns {Promise<void>}
         */
        createCustomerChatBox: async function (conversationData) {
            const identify = this.getChatBoxIdentify(
                conversationData.conversationUniqueId
            ), elems = this.getAllActiveWindowChats();
            let index = elems.length > 0 ? elems.length : 0;
            const position = chatBoxPositionCalculator.calculatePosition(
                index
            );
            let templateData, rendererComponent;
            templateData = {
                parentName: this.name,
                name: identify,
                identify: identify
            };
            rendererComponent = mageUtils.template(
                customerChatBoxTemplate,
                templateData
            );
            mageUtils.extend(rendererComponent, {
                screenIndex: index,
                identify: identify,
                defaultConversationData: conversationData,
                defaultVisible: true,
                defaultShowLoader: false,
                generalConfig: this.generalConfig,
                defaultPosition: position,
                parentChatApp:this
            });
            layout([rendererComponent]);
        },
        /**
         * getChatProfile
         */
        getChatProfile: function () {
            return this.profileData();
        },
        /******************************************
         SOCKET PROCESS
         ********************************************/
        /**
         * listenAndCorrespondEventFromSocket
         */
        listenAndCorrespondEventFromSocket: function () {
            const self = this;
            /**
             * Receive new message from customer
             */
            socketCommunicateObject.isSellerHasNewMessage().subscribe(
                self.sellerMessageReceivedHandler.bind(self)
            );
            /**
             * Receive change profile signature
             */
            socketCommunicateObject.isCustomerProfileChanged().subscribe(
                self.customerProfileChangedHandler.bind(self)
            );

            /**
             * Receive Status Change signature
             */
            socketCommunicateObject.isCustomerStatusChanged().subscribe(
                self.customerStatusChangedHandler.bind(self)
            );
            /**
             * new customer start conversation signature
             */
            socketCommunicateObject.isSellerHasNewConversation().subscribe(
                self.customerStartNewConversationHandler.bind(self)
            );

            /**
             * typing message
             */
            socketCommunicateObject.isCustomerTypingMessage().subscribe(
                self.customerTypingMessageHandler.bind(self)
            );
        },

        /**
         * sellerMessageReceivedHandler
         * Receive New Message from Customer
         *  1. Insert new message to chatScreen
         *  2. Highlight new message seller list
         */
        sellerMessageReceivedHandler: function (message) {
            const conversationUniqueId = message.conversationUniqueId,
                identity = this.getChatBoxIdentify(conversationUniqueId),
                component = this.getChatBoxComponentByIdentify(identity)
            this.increaseMessageCounterByOne(conversationUniqueId);
            this.increaseTotalUnreadMessages(
                1
            );
            if (component) {
                component.appendMessages([message], component.DIRECTION.NEXT);
                if (component.isActive()) {
                    component.scrollToLastMessage();
                }
                this.playSound();
            }
            const converstation = this.conversationListMap.get(conversationUniqueId);
            if (!converstation) return;
            converstation.lastUpdateUCTimestamp = Date.now();
            this.upsertConversation(converstation);
            console.log({
                convestation:converstation
            })
            if(this.generalConfig.notifyWhenHaveMessage){
                const timeout = this.generalConfig.closeAfter;
                const alertConfig = {
                    title: $.mage.__('You have received a new message from customer “%1“').replace(
                        '%1', converstation.customerNickName
                    )
                };
                alert(alertConfig);
            }
        },
        /**
         *
         * @returns {string}
         */
        getCounterKey: function () {
            return 'sellerTotalUnreadMessages'
        },
        /**
         * customerProfileChangedHandler
         * Receive profile change signature
         *  1. Change avatar on contact list
         *  2. Change avatar on chatScreen list
         */
        customerProfileChangedHandler: function (message) {
            const {image, name, status, uniqueId} = message;
            const modifiedConversationLists = this.conversationList().map(function (item) {
                if (item.customerUniqueId === uniqueId) {
                    item.customerImage = image
                }
                return item;
            });
            this.getRegion('customerChatScreen')().map(function (component) {
                if (component.conversationData().customerUniqueId === uniqueId) {
                    let modified = component.conversationData();
                    modified.customerImage = image;
                    component.conversationData(modified)
                }
                return component;
            })
            this.refreshContactList(modifiedConversationLists);
        },
        /**
         *
         * @param id
         * @returns {any|null}
         */
        getConverstationById: function (id) {
            return this.conversationListMap.get(id) || null;
        },
        /**
         * customerStatusChangedHandler
         * Receive status-change signature
         *  1. Change status on contact list
         *  2. Change status on chatScreen list
         */
        customerStatusChangedHandler: function (message) {
            const {uniqueId, status} = message;
            const modifiedConversationLists = this.conversationList().map(function (item) {
                if (item.customerUniqueId === uniqueId) {
                    item.customerChatStatus = status
                }
                return item;
            });

            this.getRegion('customerChatScreen')().map(function (component) {
                if (component.conversationData().customerUniqueId === uniqueId) {
                    let modified = component.conversationData();
                    modified.customerChatStatus = status;
                    component.conversationData(modified)
                }
                return component;
            })
            this.refreshContactList(modifiedConversationLists);
        },
        /**
         *
         * @param data
         */
        customerStartNewConversationHandler: function (data) {
            const modifiedConversationLists = this.conversationList(),
                newId = data.newConversation.conversationUniqueId,
                haveMessage = data.newMessage !== undefined;
            let alertConfig = {
                title: haveMessage ? $.mage.__('You have new message from client "%1" ').replace(
                    '%1', data.newConversation.customerName
                ) : $.mage.__('client "%1" have just added you into contact').replace(
                    '%1', data.newConversation.customerName
                ),
                actions: {
                    always: function () {
                    }
                }
            };
            // check if existed on list not need to add

            const filtered = modifiedConversationLists.filter(function (item) {
                if (newId === item.conversationUniqueId) {
                    return item;
                }
            })

            if (filtered.length === 0) {
                //modifiedConversationLists.push(data.newConversation);
                //this.refreshContactList(modifiedConversationLists);
                this.upsertConversation(data.newConversation);
            }
            if (data.newMessage !== undefined) {
                this.increaseMessageCounterByOne(data.newConversation.conversationUniqueId)
            }
            // notify to seller you have new message from new contact
            alert(alertConfig);
        },

        /**
         *
         * @param data
         */
        customerTypingMessageHandler: function (detail) {
            const {
                conversationUniqueId,
                message,
                name,
                uniqueId
            } = detail;
            const typingMessage = $.mage.__('%1 is typing message').replace("%1", name),
                identity = this.getChatBoxIdentify(conversationUniqueId),
                chatScreen = this.getChatBoxComponentByIdentify(identity);
            if (chatScreen) {
                chatScreen.setTypingMessage(typingMessage);
            }
        }
        /******************************************
         END SOCKET PROCESS
         ********************************************/
    });
});
