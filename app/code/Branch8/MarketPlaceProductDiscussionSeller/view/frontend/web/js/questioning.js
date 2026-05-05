define([
    'underscore',
    'jquery',
    'ko',
    'Branch8_MarketPlaceProductDiscussion/js/BaseQuestioning',
    'Branch8_MarketPlaceProductDiscussionSeller/js/actions/getThread',
    'Branch8_MarketPlaceProductDiscussionSeller/js/actions/saveMessage',
    'uiRegistry',
    'mage/translate',
    'Branch8_MarketPlaceProductDiscussion/js/model/storeData',
    'Branch8_MarketPlaceProductDiscussionSeller/js/questioning/filter',
], function (_, $, ko, BaseQuestioning, getThread, saveMessage, registry, $t, storeData) {
    'use strict';
    /**
     *
     */
    return BaseQuestioning.extend({
        filterForm: null,

        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionSeller/questioning'
        },
        tabWrapper: "#seller-questions-tab-wrapper",
        /**
         *
         */
        tabTemplate: {
            parent: '${ $.$data.parentName }',
            name: '${ $.$data.name }',
            component: 'Branch8_MarketPlaceProductDiscussionSeller/js/questioning/tab-content',
            displayArea: 'tabContent',
            visible: false
        },
        /**
         *
         * @returns {*}
         */
        initConfig: function () {
            this._super();
            this.rendererComponents = {};
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            const self = this;
            this._super();
            console.log({
                self: this,
                storeData:storeData
            })
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super().observe({
                'dropDownOptions': [
                    {'value': 'all', 'label': ko.observable($t('All Comment'))},
                    {'value': 'unreplied', 'label': ko.observable($t('Pending Replied (%1)').replace('%1', 0))},
                    {'value': 'replied', 'label': ko.observable($t('Replied (%1)').replace('%1', 0))}
                ],
                selectTabLabel: $t('客戶留言 (%1 則)').replace('%1', 0),
                'dataTotalOptions': [
                    {'value': 'all', 'total': storeData.getValueByKey('all', 'total')},
                    {'value': 'unreplied', 'total': storeData.getValueByKey('unreplied', 'total')},
                    {'value': 'replied', 'total':  storeData.getValueByKey('replied', 'total')}
                ]
            });
            this.selectTabLabel = ko.computed(function () {
                const item = self.dataTotalOptions().find(c => c.value === self.currentActiveTab());
                return $t('客戶留言 (%1 則)').replace('%1', item ? item.total() : 0);
            })
            this.currentActiveTab.subscribe(function () {
                const item = self.dropDownOptions().find(c => c.value === self.currentActiveTab());
                self.activeTab(item.value);
            })
            return this;
        },
        /**
         *
         * @param tab
         */
        reUpdateDropDownOptions: function (tab) {
            const self = this;
            const item = self.dropDownOptions().find(i => i.value === tab)
            let label = ''
            const total = storeData.getValueByKey(tab, 'total');
            if (item) {
                switch (tab) {
                    case 'all':
                        label = $t('All Comment');
                        break;
                    case 'replied':
                        label = $t('Replied (%1)').replace('%1', total());
                        break;
                    case 'unreplied':
                        label = $t('Pending Replied (%1)').replace('%1', total());
                        break;
                }
                item.label(label);
            }
            console.log({
                dropDownOptions:self.dropDownOptions
            })
        },
        /**
         *
         */
        initTabWidget: function () {
            this._super();
            this.reloadTabs(['all', 'replied', 'unreplied'], null, getThread);
        },
        /**
         *
         * @param tabCodes
         * @param inputRequestData
         */
        reloadTabs: function (tabCodes, inputRequestData) {
            return this._super(tabCodes, inputRequestData, getThread);
        },
        /**
         * @param setting
         * @param total
         */
        afterReloadTab: function (setting, total) {
            let label = '';
            switch (setting.code) {
                case 'all':
                    label = $t('All Comment')
                    break;
                case 'replied':
                    label = $t('Replied (%1)').replace('%1', total)
                    break;
                case 'unreplied':
                    label = $t('Pending Replied (%1)').replace('%1', total)
                    break;
            }
            const item = this.dropDownOptions().find(i => i.value === setting.code);
            if (item) {
                item.label(label);
            }
            const totalOption = this.dataTotalOptions().find(o => o.value === setting.code);
            if (totalOption) {
                totalOption.total(total);
            }
        },
        /**
         *
         * @returns {*}
         */
        renderMoreFilter: function () {
            const self = this;
            $('#more-filter-wrapper').threadFilter({
                triggerButton: "#more-filter-dropdown-button",
                modalClass: 'prompt seller-thread-filter-modal',
                validation: true,
                promptField: '[data-role="promptField"]',
                validationRules: ['required-entry'],
                buttons: [
                    {
                        text: $.mage.__('清除篩選'),
                        class: 'action action-dismiss',

                        /**
                         * Click handler.
                         */
                        click: function () {
                            this.clearFilter();
                            this.closeModal();
                            self.setFilters()
                                .reloadTabs(['all', 'replied', 'unreplied']);
                        }
                    }, {
                        text: $.mage.__('套用篩選'),
                        class: 'action action-accept',

                        /**
                         * Click handler.
                         */
                        click: function () {
                            if (this.validate()) {
                                this.closeModal();
                                self.setFilters()
                                    .reloadTabs(['all', 'replied', 'unreplied']);
                            }
                        }
                    }
                ]
            });
            return this;
        },
        /**
         *
         */
        setFilters: function (input = null) {
            if (input) {
                this.filters(input)
            } else {
                let filters = {},
                    data = $('#more-filter').serializeArray();
                $.each(data, function (_, field) {
                    var match = field.name.match(/^filters\[(.+)\]$/);
                    if (match && field.value) {
                        filters[match[1]] = field.value;
                    }
                });
                this.filters(filters);
            }
            return this;
        },
        /**
         *
         */
        initCalendar: function (element) {
            $(element).calendar({
                dateFormat: 'mm/dd/yyyy',
                showsTime: false,
                buttonImageOnly: false,
                buttonText: $t('Select Date'),
                showOn: 'button',
                showButtonPanel: true,
                currentText: $t('Today')
            });
        },
        /**
         *
         */
        openMoreFilter: function () {
            $("#more-filter-wrapper").data('mageThreadFilter').openModal()
        }
    });
});
