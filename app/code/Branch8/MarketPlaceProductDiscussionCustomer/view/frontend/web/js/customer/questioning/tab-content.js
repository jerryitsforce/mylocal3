define([
    'ko',
    'jquery',
    'Branch8_MarketPlaceProductDiscussion/js/BaseQuestioning/BaseTabContent',
    '../actions/getThread',
    '../actions/deleteThread',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
], function (ko, $, Component, getThread, deleteThreadAction, confirm, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionCustomer/questioning/tab-content',
            pagingComponent: 'Branch8_MarketPlaceProductDiscussion/js/paging',
            pageSize: 5
        },

        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            this.createPagingComponent();
            return this;
        },

        /**
         *
         */
        updateData: function (data) {
            this.items(data.items);
            this.total(data.total);
            return this;
        },

        /**
         * Delete thread
         *
         * @param {Object} item
         */
        deleteThread: function (item) {
            const self = this;
            if (!item.id) {
                return;
            }
            confirm({
                content: $t('Are you sure you want to delete this thread?'),
                actions: {
                    confirm: function () {
                        deleteThreadAction({
                            thread_id: item.id
                        }, function (res) {
                            if (res.success) {
                                const found = self.items().find(i => i.id === item.id);
                                if (found) {
                                    self.items.remove(found);
                                }
                                switch (self.settings.code) {
                                    case 'all':
                                        self.reloadTabs(['all', 'replied', 'unreplied']);
                                        break;
                                    case 'replied':
                                        self.reloadTabs(['all', 'replied']);
                                        break;
                                    case 'unreplied':
                                        self.reloadTabs(['all', 'unreplied']);
                                        break;
                                }
                            }
                        }, function (err) {
                        });
                    }
                }
            });
        }
    });
});
