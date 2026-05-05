define([
    'ko',
    'jquery',
    'Branch8_MarketPlaceProductDiscussion/js/BaseQuestioning/BaseTabContent',
    'uiLayout',
    'uiRegistry',
    'mageUtils',
    '../actions/getThread',
    'mage/translate',
    'Branch8_MarketPlaceProductDiscussionSeller/js/actions/saveMessage',
    'Branch8_MarketPlaceProductDiscussionSeller/js/actions/toggleStatus',
    'Branch8_MarketPlaceProductDiscussion/js/actions/searchCriteriaBuilder',
    'plugins/DOMPurify',
    '../model/Thread',
    'Branch8_MarketPlaceProductDiscussion/js/model/storeData'
], function (ko, $, Component, layout, registry, mageUtils, getThread, $t, saveMessage, toggleStatus, searchCriteriaBuilder, DOMPurify, Thread, storeData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionSeller/questioning/tab-content',
            imports: {
                filters: '${ $.parentName }.filters'
            }
        },
        /**
         *
         * @returns {*}
         */
        initConfig: function () {
            this._super();
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe(['filters']);
            return this;
        },
        /**
         *
         * @param page
         */
        loadThreads: function (page) {
            const self = this,
                requestData = {tabs: {}};
            requestData.tabs[self.settings.code] = searchCriteriaBuilder.build(self.settings.code, this.filters(), page);
            return getThread(requestData, function (res) {
                if (res?.data?.[self.settings.code].items) {
                    self.updateData(res?.data?.[self.settings.code]);
                }
                return res;
            })
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super()
                .createPagingComponent();
            return this;
        },
        /**
         *
         */
        updateData: function (data) {
            this.items(data.items.map(function (item) {
                return Thread(item);
            }));
            this.total(data.total);
            return this;
        },
        /**
         *
         * @param item
         */
        toggleStatus: function (item, event) {
            const self = this,
                status = $(event.currentTarget).is(":checked")
            const request = {
                'thread': item.id,
                'status': status ? 1 : 0
            };
            toggleStatus(request);
            return true;
        }
        ,
        /**
         *
         * @param item
         * @param event
         */
        editReply: function (item, event) {
            const parent = $(event.currentTarget).closest('.action-group');
            $(event.currentTarget).parents('.seller-thread-messages').find('.seller-thread-message').hide();
            parent.find('.group1').show();
            parent.find('.group2').hide();
            return true;
        }
        ,
        /**
         *
         * @param item
         * @param event
         * @returns {boolean}
         */
        cancelReply: function (item, event) {
            const parent = $(event.currentTarget).closest('.action-group');
            parent.find('.group2').show();
            parent.find('.group1').hide();
            $(event.currentTarget).parents('.seller-thread-messages').find('.seller-thread-message').removeAttr('style');
            return true;
        }
        ,
        /**
         *
         * @param message
         * @param event
         * @returns {boolean}
         */
        updateReply: function (message, event) {
            const parent = $(event.currentTarget).closest('.action-group'),
                textArea = parent.find('textarea'),
                self = this, threadId = message.parent_id;
            parent.find('.group1').show();
            parent.find('.group2').hide();
            const request = {
                'id': message.id,
                'thread': threadId,
                'message': this.sanitize(textArea.val())
            };
            saveMessage(request, function (res) {
                self.afterSubmitReply(parent, res, threadId)
            })
            return true;
        }
        ,
        /**
         *
         * @param parent
         * @param res
         * @param threadId
         */
        afterSubmitReply: function (parent, res, threadId) {
            const self = this;
            parent.find('.group1').hide();
            parent.find('.group2').show();
            if (threadId) {
                const item = self.items().find(i => i.id === threadId);
                if (item) {
                    item.has_messages(true);
                    item.messages([res.data.message]);
                    self.items.valueHasMutated();
                }
            }
        }
        ,
        /**
         *
         */
        addReply: function (thread, event) {
            const parent = $(event.currentTarget).closest('.action-group'),
                textArea = parent.find('textarea'),
                threadId = thread.id,
                self = this,
                request = {
                    'thread': thread.id,
                    'message': this.sanitize(textArea.val())
                };
            saveMessage(request, function (res) {
                // remove item out when this is unreplied tab;
                if (threadId) {
                    const item = self.items().find(i => i.id === threadId);
                    if (item) {
                        self.items.remove(item);
                    }
                    self.reloadTabs(['replied']);
                    storeData.getValueByKey('unreplied', 'total')(
                        storeData.getValueByKey('unreplied', 'total')() - 1
                    );
                    registry.async(self.parentName)(function (questioningComponent) {
                        questioningComponent.reUpdateDropDownOptions('unreplied');
                    })
                }
            })
        }
        ,
        /**
         *
         * @param text
         * @param config
         * @returns {*}
         */
        sanitize: function (text = '', config = {}) {
            const defaultConfig = {
                ALLOWED_TAGS: [''],
                ALLOWED_ATTR: [''],
                FORBID_TAGS: [''],
                FORBID_ATTR: [''],
                ADD_TAGS: [''],
                ALLOW_DATA_ATTR: false,
                SAFE_FOR_TEMPLATES: true
            };
            return DOMPurify.sanitize(text, _.extend(defaultConfig, config))
        }
    });
})
;
