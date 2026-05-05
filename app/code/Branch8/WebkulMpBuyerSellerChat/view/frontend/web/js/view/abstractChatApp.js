/**
 * Base Abstract Class for Screen Chats , prevent duplicate code
 */
define([
    'ko',
    'jquery',
    'uiComponent',
], function (
    ko,
    $,
    Component
) {
    'use strict';
    ko.bindingHandlers.normalizeText = {
        update: function (element, valueAccessor) {
            var value = ko.unwrap(valueAccessor()) || '';
            value = value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            // normalize all newline types to <br>
            value = value.replace(/\r\n|\n|\r/g, '<br>');
            element.innerHTML = value;
        }
    };
    return Component.extend({

        /**
         * initObservable
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe([
                    'filteredConversationList',
                    'conversationList',
                    'searchContact',
                    'totalUnreadMessages'
                ]);
            return this;
        },
        /**
         *
         * @param conversationUniqueId
         * @returns {*}
         */
        increaseMessageCounterByOne: function (conversationUniqueId) {
            const counterKey = this.getCounterKey();
            const filtered = this.conversationList().filter(function (item) {
                if (item.conversationUniqueId === conversationUniqueId) {
                    return item;
                }
            });
            if (filtered) {
                this.updateMessageCounter(
                    conversationUniqueId,
                    filtered[0][counterKey] + 1
                )
            }
            return this;
        },
        /**
         *
         * @param conversationUniqueId
         * @returns {*}
         */
        decreaseMessageCounter: function (conversationUniqueId, counter) {
            const counterKey = this.getCounterKey();
            const filtered = this.conversationList().filter(function (item) {
                if (item.conversationUniqueId === conversationUniqueId) {
                    return item;
                }
            });
            const remain = filtered[0][counterKey] - counter;
            if (filtered) {
                this.updateMessageCounter(
                    conversationUniqueId,
                    remain >= 0 ? remain : 0
                )
            }
            return this;
        },
        /**
         *
         * @param conversationUniqueId
         * @param counter
         */
        updateMessageCounter: function (conversationUniqueId, counter) {
            const counterKey = this.getCounterKey();
            const modifiedConversationList = this.conversationList().map(function (item) {
                if (item.conversationUniqueId === conversationUniqueId) {
                    item[counterKey] = counter;
                }
                return item;
            });
            if (modifiedConversationList.length) {
                this.refreshContactList(modifiedConversationList);
            }
        },
        /**
         *
         * @param conversationUniqueId
         * @returns {*}
         */
        resetUnreadMessageCounter: function (conversationUniqueId) {
            let conversationData = null;
            const counterKey = this.getCounterKey();
            const modifiedConversationList = this.conversationList().map(function (item) {
                if (item.conversationUniqueId === conversationUniqueId) {
                    item[counterKey] = 0;
                    conversationData = item;
                }
                return item;
            });
            this.refreshContactList(modifiedConversationList);
            return this;
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
         *
         * @param value
         */
        updateTotalUnreadMessages: function (value) {
            this.totalUnreadMessages(value);
            return this;
        },
        /**
         *
         * @param value
         * @returns {*}
         */
        decreaseTotalUnreadMessages: function (value) {
            const newValue = this.totalUnreadMessages() > 0 ? this.totalUnreadMessages() - value : 0;
            this.totalUnreadMessages(newValue > 0 ? newValue : 0);
            return this;
        },
        /**
         *
         * @param value
         * @returns {*}
         */
        increaseTotalUnreadMessages: function (value) {
            this.totalUnreadMessages(this.totalUnreadMessages() + value);
            return this;
        }
    })
});
