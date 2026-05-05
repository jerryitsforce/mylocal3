define(
    [
        'ko',
        'underscore',
        'Webkul_MpBuyerSellerChat/js/socket.io',
    ],
    function (ko, _, io,) {
        'use strict';
        var socketWorking = ko.observable(false),
            socketObject = ko.observable(false);
        return {
            config: {},
            /**
             *
             * @param config
             */
            init: async function (config) {
                this.config = config;
                const host = this.config.host;
                try {
                    const status = socketObject(io(host, {transports: ['websocket']}));
                    socketWorking(true);
                } catch (e) {
                    socketWorking(false);
                }
            },
            /**
             * get socket object
             */
            getSocketObject: function () {
                if (socketWorking()) {
                    return socketObject();
                }
                return false;
            },
        };
    }
);
