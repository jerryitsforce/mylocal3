/**********************************
 Control all chat Screen
 *********************************/
define([
    'underscore',
    'ko',
    'jquery',
    'Magento_Customer/js/customer-data',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/view/default/chatApp',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/actions/load-chat-profile'
], function (
    _,
    ko,
    $,
    customerData,
    defaultChatApp,
    loadChatProfileAction
) {

    'use strict';
    return defaultChatApp.extend({
        defaults: {
            template: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/miniChat/chatApp'
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'isLoggedIn',
                    'customerChatProfile',
                    'currentConversationData'
                ]);
            let customer = customerData.get('customer')() || {},
                isLoggedIn = true;
            if (_.isEmpty(customer) || !customer.hasOwnProperty('email')) {
                isLoggedIn = false;
            }
            this.isLoggedIn(isLoggedIn);
            return this;
        },

        /**
         *
         * @param sellerUniqueId
         */
        getConversationBySellerUniqueId: function (sellerUniqueId) {
            const conversationList = this.conversationList() || [];
            if (conversationList.length === 0) {
                return false;
            }
            const filtered = conversationList.filter(function (item) {
                if (item.sellerUniqueId === sellerUniqueId) {
                    return item;
                }
            })
            return filtered.length > 0 ? filtered[0] : null;
        },
        /**
         *
         * @returns {Promise<*>}
         */
        loadChatProfile: async function () {
            if (!this.customerChatProfile()) {
                const chatProfile = JSON.parse(await Promise.resolve(loadChatProfileAction()));
                this.customerChatProfile(chatProfile);
                this.profileData = chatProfile;
                /**
                 * connect  to socket
                 */
                this.connectToSocket();
            }
            return this;
        },
        /**
         *
         * @param conversation
         */
        addNewChatConversation: function (conversation) {
            const conversationList = this.conversationList() || [];
            conversationList.push(conversation);
            this.conversationList(conversationList);
        }
    })
})
