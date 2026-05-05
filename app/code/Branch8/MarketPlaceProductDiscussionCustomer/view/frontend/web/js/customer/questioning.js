define([
    'underscore',
    'jquery',
    'ko',
    'Branch8_MarketPlaceProductDiscussion/js/BaseQuestioning',
    './actions/getThread',
    'uiLayout',
    'uiRegistry',
    'mageUtils',
    'mage/translate',
    'Branch8_MarketPlaceProductDiscussion/js/model/storeData',
    'tabs',
    'mage/validation'
], function (_, $, ko, Component, getThread, layout, registry, mageUtils, $t, storeData) {
    'use strict';

    return Component.extend({
        tabTemplate: {
            parent: '${ $.$data.parentName }',
            name: '${ $.$data.name }',
            component: 'Branch8_MarketPlaceProductDiscussionCustomer/js/customer/questioning/tab-content',
            displayArea: 'tabContent',
            visible: false
        },
        tabs: [
            {'code': 'all', 'title': $t('All'), 'sortOrder': 0},
            {'code': 'replied', 'title': $t('Replied'), 'sortOrder': 1},
            {'code': 'unreplied', 'title': $t('Unreplied'), 'sortOrder': 2}
        ],
        counter: {
            all: storeData.all.total,
            replied: storeData.replied.total,
            unreplied: storeData.unreplied.total,
        },
        tabIndex: {
            all: 0,
            replied: 1,
            unreplied: 2
        },
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionCustomer/questioning'
        },
        tabWrapper: "#questions-tab-wrapper",
        /**
         *
         * @returns {*}
         */
        initConfig: function () {
            this._super();
            return this;
        },
        /**
         * initObservable
         */
        initObservable: function () {
            this._super();
            this.currentActiveTab(
                window.location.hash ? window.location.hash.replace('#', '') : "tab-all"
            );
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            return this;
        },
        /**
         *
         */
        initTabWidget: function () {
            this._super();
            this.activeTab(this.getTabCodeFromHash());
            this.reloadTabs(['all', 'replied', 'unreplied']);
        },
        /**
         *
         * @param tabCodes
         * @param inputRequestData
         */
        reloadTabs: function (tabCodes, inputRequestData) {
            return this._super(tabCodes, inputRequestData, getThread);
        }
    });
});
