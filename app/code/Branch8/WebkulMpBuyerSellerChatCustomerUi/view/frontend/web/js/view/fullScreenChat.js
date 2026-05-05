define([
    'ko',
    'jquery',
    'uiComponent',
    'Webkul_MpBuyerSellerChat/js/model/socket-provider',
    'jquery-ui-modules/draggable',
    'niceScroll',
    'domReady!'
], function (ko, $, Component, socketProvider) {
    'use strict';
    return Component.extend({
        minimized: ko.observable(false),
        showLoader: socketProvider.getshowLoader(),
        chatBoxVisible: ko.observable(false),
        defaults: {
            template: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/fullScreenChat'
        },
        /**
         * Init
         */
        initialize: function () {
            var self = this;
            this._super();
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe(['conversationList']);
            this.conversationList(
                this.defaultConversationList || []
            );
            return this;
        },
        /**
         * Show Chat Box handle
         */
        showChatBox: function () {
            this.chatBoxVisible(true);
            $('body').css('overflow', 'hidden');
            localStorage.setItem('showfullScreenChat', true);
        },
        /**
         * Hide Chat Box handle
         */
        hideChatBox: function () {
            this.chatBoxVisible(false);
            $('body').css('overflow', '');
            localStorage.setItem('showfullScreenChat', false);
        },

        onTouchMove: function(event) {
            event.stopPropagation();
        },
        /**
         *
         */
        afterRender: function () {
            $(document).trigger('fullScreenChatLoadComplete', {
                'chatComponent': this
            });
            //$('[data-role="chatPanel"]').draggable();
        }
    })
});
