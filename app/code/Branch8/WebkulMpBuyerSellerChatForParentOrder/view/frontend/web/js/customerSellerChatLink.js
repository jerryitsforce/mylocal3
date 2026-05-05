define([
    'underscore',
    'uiRegistry',
    'jquery',
    'Branch8_WebkulMpBuyerSellerChat/js/model/until',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/actions/customer-start-chat',
    'Branch8_WebkulMpBuyerSellerChat/js/actions/send-message',
    'Branch8_WebkulMpBuyerSellerChat/js/model/direction',
    'mage/template',
    'text!Branch8_WebkulMpBuyerSellerChatForParentOrder/template/order_chat_message.html'
], function (
    _,
    registry,
    $,
    UNTIL,
    customerStartChat,
    sendMessageAction,
    direction,
    template,
    orderTemplate
) {

    $.widget('mage.customerSellerChatLink', {
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
            $(document).on('fullScreenChatLoadComplete', (function (e, data) {
                this.chatComponent = data.chatComponent;
                $(self.options.pattern).css("display", "block");
            }).bind(this));
            $(this.options.pattern).off('click', this.options.pattern);
            $($(document.body)).on('click', this.options.pattern, this.handleChatLinkClick.bind(this));
        },
        /**
         * Handle event click chat
         */
        handleChatLinkClick: async function (e) {
            $(e.currentTarget).addClass('disable');
            const pushed = !!$(e.currentTarget).data('message-pushed');
            const {item, order, conversation} = $(e.currentTarget).data('chat'),
                customerMiniChatComponent = this.getComponent('fullScreenChat'),
                chatApp = this.getComponent('fullScreenChat.chat-history'),
                conversationList = customerMiniChatComponent.conversationList(),
                isInlist = conversationList.filter(
                    (conversationData) => conversationData.conversationUniqueId === conversation.conversationUniqueId
                ).length > 0,
                orderMessage = this.buildOrderMessage(order, item),
                meta=_.union(
                    [{'key': 'code','value':'order'}],
                    this.buildMeta(order, 'order'),
                    this.buildMeta(item, 'item'),
                );
            console.log({
                meta: meta
            })
            let sellerConversation = conversation;
            if (pushed === false) {
                $(e.currentTarget).data('message-pushed', true);
                let messageData = {
                    conversationId: sellerConversation.conversationUniqueId,
                    senderUniqueId: sellerConversation.customerUniqueId,
                    receiverUniqueId: sellerConversation.sellerUniqueId,
                    message: orderMessage,
                    unique_id: UNTIL.messageUniqueIdGenerate(),
                    msgType: "html",
                    messageType:"html",
                    dateTime: UNTIL.toServerDate(),
                    meta:meta
                }
                if (!sellerConversation.conversationUniqueId) {
                    const newConversation = JSON.parse(await Promise.resolve(customerStartChat({
                        seller_id: sellerConversation.sellerId
                    }))), emitNewConversationAction = 'customerStartNewConversation';
                    conversationList.push(newConversation)
                    chatApp.conversationList(
                        conversationList
                    );
                    messageData.conversationId = newConversation.conversationUniqueId
                    sellerConversation = newConversation;
                    chatApp.emitSocketMessageToSeller(emitNewConversationAction,
                        {
                            receiverId: sellerConversation.sellerUniqueId,
                            newConversation: newConversation,
                            newMessage: messageData
                        });
                }
                sendMessageAction(messageData, function (res) {
                    const {erros, messages} = res,
                        emitAction = 'sendMessageToSeller';
                    if (!!erros === false) {
                        customerMiniChatComponent.showChatBox();
                        chatApp.activeChatScreen(
                            sellerConversation
                        );
                        // build chat screen;
                        const chatScreen = chatApp.getChatScreenInstance(
                            sellerConversation.conversationUniqueId
                        );
                        if (chatScreen) {
                            const message = chatScreen.createNewMessage(
                                messageData
                            );
                            chatScreen.appendMessages([message], 'next');
                        }
                        setTimeout(function () {
                            chatApp.emitSocketMessageToSeller(emitAction, messages);
                        }, 3000)
                    }
                    $(e.currentTarget).removeClass('disable');
                })
            } else {
                customerMiniChatComponent.showChatBox();
                chatApp.activeChatScreen(sellerConversation);
                $(e.currentTarget).removeClass('disabled');
            }
        },
        /**
         *
         * @param data
         * @param prefix
         * @returns {{key: string, value: *}[]}
         */
        buildMeta:function (data,prefix){
            return Object.entries(data).map(([key, value], index) => ({
                key: `${prefix}_${key}`,
                value: value
            }));
        },
        /**
         *
         * @param order
         * @param item
         * @returns {*}
         */
        buildOrderMessage: function (order, item) {
            return template(
                orderTemplate,
                {
                    data: {
                        order: order,
                        item: item
                    }
                }
            );
        },
        /**
         *
         * @returns {*|null}
         */
        getComponent: function (path) {
            return registry.get(path);
        }
    });
    return $.mage.customerSellerChatLink;
});
