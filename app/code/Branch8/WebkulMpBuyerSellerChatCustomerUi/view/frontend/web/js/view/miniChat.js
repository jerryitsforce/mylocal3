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
            template: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/miniChat'
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
            this._super();
            return this;
        },
        /**
         * Show Chat Box handle
         */
        showChatBox: function () {
            this.chatBoxVisible(true);
            localStorage.setItem('showMiniChat', true);
        },
        /**
         * Hide Chat Box handle
         */
        hideChatBox: function () {
            this.chatBoxVisible(false);
            localStorage.setItem('showMiniChat', false);
        },
        /**
         *
         */
        afterRender: function () {
            $(document).trigger('miniChatLoadComplete', {
                'chatComponent': this
            });
            $('[data-role="chatPanel"]').draggable();
        }
    })
});
