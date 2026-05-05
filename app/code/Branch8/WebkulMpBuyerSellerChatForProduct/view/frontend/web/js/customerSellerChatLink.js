define([
    'uiRegistry',
    'jquery',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/actions/customer-start-chat'
], function (
    registry,
    $,
    customerStartChat
) {
    $.widget('mage.customerSellerChatLinkProduct', {
        chatComponent: null,
        options: {
            'pattern': '[data-role=\'chat-with-seller\']'
        },
        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            const self = this;
            $(document).on('customerMiniChatLoadComplete', (function (e, data) {
                this.chatComponent = data.chatComponent;
                $(self.options.pattern).css("display", "block");
            }).bind(this));
            $(this.options.pattern).off('click')
                .on('click', this.handleChatLinkClick.bind(this));
        },
        /**
         * Handle event click chat
         */
        handleChatLinkClick: async function (e) {
            $(e.currentTarget).addClass('processing');
            const {seller} = $(e.currentTarget).data('chat'),
                customerMiniChatComponent = this.getComponent('customerMiniChat'),
                chatApp = this.getComponent('customerMiniChat.chat-history');
            /*****************
             if not logged in show chat bot let customer know
             *****************/
            if (!chatApp.isLoggedIn()) {
                $(e.currentTarget).removeClass('processing');
                customerMiniChatComponent.showChatBox();
                return;
            }
            if (!chatApp.customerChatProfile()) {
                await chatApp.loadChatProfile();
            }
            const sellerUniqueId = seller.uniqueId,
                sellerId = seller.sellerId,
                conversation = chatApp.getConversationBySellerUniqueId(
                    sellerUniqueId
                );
            /*****************
             1. Show Chat Box
             2. Active chat screen
             *****************/
            if (conversation) {
                $(e.currentTarget).removeClass('processing');
                chatApp.activeChatScreen(conversation);
                customerMiniChatComponent.showChatBox();
                return;
            }
            /*****************
             1. Start New Chat Conversation with this seller
             2. Show Chat Box
             3. Active chat screen
             *****************/
            const newConversation = JSON.parse(await Promise.resolve(customerStartChat({
                seller_id: sellerId
            })))
            chatApp.addNewChatConversation(newConversation);
            chatApp.activeChatScreen(newConversation);
            customerMiniChatComponent.showChatBox();
            $(e.currentTarget).removeClass('processing');
        },
        /**
         *
         * @returns {*|null}
         */
        getComponent: function (path) {
            return registry.get(path);
        }
    });
    return $.mage.customerSellerChatLinkProduct;
});
