define([
    'underscore',
    'jquery',
    'ko',
    'uiComponent',
    'mage/translate',
    'uiRegistry',
    'mageUtils',
    'uiLayout',
    'Branch8_MarketPlaceProductDiscussion/js/actions/searchCriteriaBuilder',
    'tabs'
], function (_, $, ko, Component, $t, registry, mageUtils, layout, searchCriteriaBuilder) {
    'use strict';

    return Component.extend({
        tabs: [
            {'code': 'all', 'title': $t('All'), 'sortOrder': 0},
            {'code': 'unreplied', 'title': $t('Unreplied'), 'sortOrder': 1},
            {'code': 'replied', 'title': $t('Replied'), 'sortOrder': 2},
        ],
        counter: {
            all: ko.observable(0),
            replied: ko.observable(0),
            unreplied: ko.observable(0),
        },
        tabIndex: {
            all: 0,
            unreplied: 1,
            replied: 2,
        },
        isLoading: ko.observable(false), /**
         *
         * @returns {string|string}
         */
        getTabCodeFromHash: function () {
            return window.location.hash ? window.location.hash.replace('#tab-', '') : "all"
        },
        /**
         *
         * @param code
         */
        activeTab: function (code) {
            const index = this.tabIndex[code] || 0,
                mageTabs = $(this.tabWrapper).data('mageTabs');
            if (mageTabs) {
                mageTabs.activate(index);
            }
        },
        initConfig: function () {
            this._super();
            this.rendererComponents = {};
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super().observe({
                'currentActiveTab': '',
                'filters': {}
            });
            this.currentActiveTab(
                window.location.hash ? window.location.hash.replace('#', '') : "tab-all"
            );
            this.counterTextAll = ko.pureComputed(function () {
                return self.counter['all']() < 99 ? self.counter['all']() : '99+'
            });
            this.counterTextReplied = ko.pureComputed(function () {
                return self.counter['replied']() < 99 ? self.counter['replied']() : '99+'
            });
            this.counterTextUnReplied = ko.pureComputed(function () {
                return self.counter['unreplied']() < 99 ? self.counter['unreplied']() : '99+'
            });
            return this;
        },

        /**
         *
         * @returns {*}
         */
        initChildren: function () {
            return this;
        },
        /**
         *
         * @returns {*}
         */
        afterRender: function () {
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            const self = this;
            this._super()
                .initChildren();
            _.each(this.tabs, async function (data) {
                await self.createTabComponent(data);
            });
            let loaded = 0, total = 3;
            this.tabs.forEach(function (data) {
                const name = self.name + '.' + data.code;
                registry.async(name)(function () {
                    loaded++;
                    if (loaded === total) {
                        self.initTabWidget();
                    }
                });

            });
            return this;
        },
        /**
         *
         */
        initTabWidget: function () {
            $(this.tabWrapper).tabs({
                "openedState": "active",
                "animate": {"duration": 100}
            });
            this.activeTab(this.getTabCodeFromHash());
        },
        /**
         *
         * @param data
         * @returns {Promise<void>}
         */
        createTabComponent: async function (data) {
            let templateData, rendererComponent;
            templateData = {
                parentName: this.name,
                name: data.code
            };
            rendererComponent = mageUtils.template(this.tabTemplate, templateData);
            mageUtils.extend(rendererComponent, {
                settings: data,
                defaultVisible: false,
                visible: ko.observable(true),
                defaultShowLoader: false,
                displayArea: this.name + '.' + data.code,
                filters: this.filters,
            });
            this.rendererComponents[data.code] = this.name + '.' + data.code;
            layout([rendererComponent]);
        },
        /**
         *
         * @param code
         */
        getCounterText: function (code) {
            switch (code) {
                case 'all':
                    return this.counterTextAll;
                case 'replied':
                    return this.counterTextReplied;
                case 'unreplied':
                    return this.counterTextUnReplied;
            }
        },
        /**
         *
         * @param tabCodes
         * @returns {{tabs: {[p: string]: *}}|{tabs: {all: *, unreplied: *, replied: *}}}
         */
        buildRequest: function (tabCodes) {
            const filters = this.filters();
            if (tabCodes) {
                const codes = Array.isArray(tabCodes) ? tabCodes : [tabCodes];

                return {
                    tabs: Object.fromEntries(
                        codes.map(code => [
                            code,
                            searchCriteriaBuilder.build(code, filters)
                        ])
                    )
                };
            }
            return {
                tabs: {
                    all: searchCriteriaBuilder.build('all', filters),
                    unreplied: searchCriteriaBuilder.build('unreplied', filters),
                    replied: searchCriteriaBuilder.build('replied', filters)
                }
            };
        },

        /**
         *
         * @param tabCodes
         * @param inputRequestData
         * @param getThreadAction
         */
        reloadTabs: function (tabCodes, inputRequestData, getThreadAction) {
            const self = this;
            const codes = Array.isArray(tabCodes) ? tabCodes : (tabCodes ? [tabCodes] : this.tabs.map(t => t.code));
            const filtered = this.tabs.filter(tab => codes.includes(tab.code));
            const requestData = inputRequestData ? inputRequestData : this.buildRequest(codes);

            if (!getThreadAction) {
                return;
            }

            return getThreadAction(requestData, function (res) {
                filtered.forEach(function (setting) {
                    const name = self.name + '.' + setting.code;
                    if (res?.data?.[setting.code]) {
                        const total = res.data[setting.code].total;
                        registry.async(name)(async function (component) {
                            component.updateData(
                                res.data[setting.code]
                            );
                            const paging = await component.getPagingComponent();
                            paging.totalCount(total);
                            paging.pageSize(res.data[setting.code].pageSize ? res.data[setting.code].pageSize : 5);
                            self.counter[setting.code](total);
                            self.afterReloadTab(setting, total);
                        });
                    }
                })
            })
        },

        /**
         * Hook for subclasses
         */
        afterReloadTab: function (setting, total) {
            // To be overridden
        }
    });
});
