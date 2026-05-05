/**********************************
 Control all chat Screen
 *********************************/
define([
    'ko',
    'jquery',
    'uiRegistry',
    'Branch8_WebkulMpBuyerSellerChatCustomerUi/js/view/default/chatApp',
], function (
    ko,
    $,
    registry,
    DefaultChatApp
) {
    'use strict';
    return DefaultChatApp.extend({
        defaults: {
            template: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/fullScreenChat/chatApp',
            newContactTemplate: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/fullScreenChat/chatApp/addNewContactTemplate',
            settingTemplate: 'Branch8_WebkulMpBuyerSellerChatCustomerUi/view/fullScreenChat/chatApp/settingsTemplate',
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'customerChatProfile',
                    'currentConversationData'
                ]);
            self.searchContact('');
            this.filteredConversationList = ko.computed(function () {
                var filter = self.searchContact().toLowerCase();
                if (!filter) {
                    return self.conversationList();
                } else {
                    return ko.utils.arrayFilter(self.conversationList(), function (conversation) {
                        return conversation.sellerName.toLowerCase().indexOf(filter) !== -1;
                    });
                }
            });
            /**
             *
             * @type {(function(): *)|*}
             */
            self.debouncedFilterContacts = _.debounce(function () {
                self.filteredConversationList(); // Trigger re-evaluation of the computed observable
            }, 300);
            self.searchContact.subscribe(self.debouncedFilterContacts);
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            this.activeDefaultConversation();
            return this;
        },
        /**
         *
         */
        activeDefaultConversation: function () {
            const params = new URLSearchParams(window.location.search),
                autoOpen = params.get('openChat'),
                result = this.conversationList().filter(item => item.conversationUniqueId === params.get('conversation_id'));
            if (result.length) {
                this.sellerRowClick(result[0]);
            }
            if(autoOpen){
                registry.async(this.parentName)(
                    function (parentComponent) {
                        parentComponent.showChatBox()
                    }
                );
            }

        },
    })
})
