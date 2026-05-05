define(
    [
        'ko',
        'underscore',
        'Webkul_MpBuyerSellerChat/js/model/socket-provider'
    ],
    function (ko, _, socketProvider) {
        'use strict';
        let socket = socketProvider.getSocketObject(),
            customerMessageReceived = ko.observable(),
            sellerMessageReceiver = ko.observable(),
            sellerReceiveNewConversation = ko.observable(),
            sellerTypingMessage = ko.observable(),
            customerTypingMessage = ko.observable(),
            customerStatusUpdated = ko.observable(),
            customerProfileUpdated = ko.observable(),
            sellerStatusUpdated = ko.observable(),
            sellerProfileUpdated = ko.observable(),
            isChatError = ko.observable(false),
            chatErrorData = ko.observable(''),
            customerBlocked = ko.observable('');
        if (socket !== false) {
            /**********************************************
             SELlER
             ***********************************************/
            /**
             * if customer recieved message.
             */
            socket.on('seller:received:new-message', function (data) {
                sellerMessageReceiver(data);
            });
            /**
             * if customer profile change and notify seller about it.
             */
            socket.on('seller:received:customer-profile-change', function (data) {
                customerProfileUpdated(data);
            });
            /**
             * if customer status change and notify seller about it.
             */
            socket.on('seller:received:customer-status-change', function (data) {
                customerStatusUpdated(data);
            });

            socket.on('seller:received:new-conversation', function (data) {
                sellerReceiveNewConversation(data);
            });

            socket.on('seller:received:typing-message', function (data) {
                customerTypingMessage(data);
            });

            /**********************************************
             CUSTOMER
             ***********************************************/
            /**
             * if customer recieved message.
             */
            socket.on('customer:received:new-message', function (data) {
                customerMessageReceived(data);
            });

            /**
             * if seller status change and notify customers about it.
             */
            socket.on('customer:received:seller-status-change', function (data) {
                sellerStatusUpdated(data);
            });

            /**
             * if seller profile change and notify customers about it.
             */
            socket.on('customer:received:seller-profile-change', function (data) {
                sellerProfileUpdated(data);
            });

            /**
             * if customer status change and notify seller about it.
             */
            socket.on('customer blocked by seller', function (data) {
                customerBlocked(data);
            });
            /**
             *
             */
            socket.on('customer:received:typing-message', function (data) {
                sellerTypingMessage(data);
            });
        }
        return {

            /**
             * setSellerConected
             * send socket event when seller logged in
             */
            setSellerConected: function (sellerDetails) {
                socketProvider.setSellerConected(sellerDetails);
            },

            /**
             * setCustomerConnected
             * @param details
             */
            setCustomerConnected: function (details) {
                socketProvider.setCustomerConnected(details);
            },
            /**
             * send new message to customer by seller
             */
            sendMessageToCustomer: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('seller:send:new-message', sendDetails);
                }
            },

            /**
             * send new message to seller by customer
             */
            sendMessageToSeller: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer:send:new-message', sendDetails);
                }
            },
            /**
             *
             * @param sendDetails
             */
            customerStartNewConversation:function (sendDetails){
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer:send:start-new-conversation', sendDetails);
                }
            },

            /**
             * seller status changed
             */
            sellerStatusChange: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('seller:send:seller-status-change', sendDetails);
                }
            },

            /**
             * seller profile changed
             */
            sellerProfileChange: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('seller:send:seller-profile-change', sendDetails);
                }
            },
            /**
             *
             * @param sendDetails
             */
            sellerTypingMessageSend: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('seller:send:typing-message', sendDetails);
                }
            },
            /**
             *
             * @param sendDetails
             */
            customerTypingMessageSend: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer:send:typing-message', sendDetails);
                }
            },
            /**
             * seller blocked the customer
             */
            sellerBlockCustomer: function (data) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer block event', data);
                }
            },

            /**
             * customer status changed
             */
            customerStatusChange: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer:send:customer-status-change', sendDetails);
                }
            },

            /**
             * customer profile changed
             */
            customerProfileChange: function (sendDetails) {
                var socket = socketProvider.getSocketObject();
                if (socket !== false) {
                    socket.emit('customer:send:customer-profile-change', sendDetails);
                }
            },

            /**
             * return observer to reply-management.js
             */
            isCustomerBlocked: function () {
                return customerBlocked;
            },

            /**
             * return observer to reply-management.js
             */
            isCustomerHasNewMessage: function () {
                return customerMessageReceived;
            },
            /**
             * return observer to active-model.js
             */
            isSellerHasNewMessage: function () {
                return sellerMessageReceiver;
            },

            /**
             *
             * @returns {*}
             */
            isCustomerTypingMessage:function (){
                return customerTypingMessage;
            },
            /**
             *
             * @returns {*}
             */
            isSellerTypingMessage:function (){
                return sellerTypingMessage;
            },
            /**
             * sellerReceiveNewConversation
             * @returns {*}
             */
            isSellerHasNewConversation: function () {
                return sellerReceiveNewConversation;
            },
            /**
             * return observer to active-model.js
             */
            isCustomerStatusChanged: function () {
                return customerStatusUpdated;
            },

            /**
             * return observer to active-model.js
             */
            isCustomerProfileChanged: function () {
                return customerProfileUpdated;
            },

            /**
             * return observer to active-model.js
             */
            isSellerStatusChanged: function () {
                return sellerStatusUpdated;
            },

            /**
             * return observer to active-model.js
             */
            isSellerProfileChanged: function () {
                return sellerProfileUpdated;
            },

            /**
             * set if chat has an error
             */
            setChatError: function (value) {
                isChatError(value);
            },
            /**
             * return observable
             */
            getChatError: function () {
                return isChatError;
            },
            /**
             * set chat text
             */
            setChatErrorData: function (value) {
                chatErrorData(value);
            },
            /**
             * get chat text
             */
            getChatErrorData: function () {
                return chatErrorData;
            }
        };
    }
);
