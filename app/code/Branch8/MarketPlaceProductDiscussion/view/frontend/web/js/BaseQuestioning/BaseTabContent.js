define([
    'ko',
    'uiComponent',
    'uiRegistry',
    'uiLayout',
    'mageUtils',
    'Branch8_MarketPlaceProductDiscussion/js/model/storeData'
], function (ko, Component, registry, layout, mageUtils, storeData) {
    'use strict';

    const pagingTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: '${ $.$data.component }',
        displayArea: 'paging',
        visible: false
    };

    return Component.extend({
        defaults: {
            pagingComponent: 'Branch8_MarketPlaceProductDiscussion/js/paging'
        },

        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super().observe({'page': 1});
            const arr = ['items', 'currentPage', 'pageSize', 'filters', 'total'];
            arr.forEach((value) => {
                self[value] = storeData[self.settings.code][value];
            });
            this.page.subscribe(function (value) {
                self.currentPage(value);
                self.reloadTabs([self.settings.code]);
            })
            return this;
        },

        /**
         *
         */
        reloadTabs: function (tabs) {
            const self = this, parent = self.parentName;
            registry.async(parent)(function (parentComponent) {
                parentComponent.reloadTabs(tabs)
            });
        },

        /**
         *
         */
        getPagingComponent: async function () {
            const name = this.name + '.paging';
            return new Promise(function (resolve) {
                registry.async(name)(function (paging) {
                    resolve(paging);
                });
            });
        },

        /**
         *
         * @param data
         * @returns {Promise<void>}
         */
        createPagingComponent: async function (data) {
            let templateData, rendererComponent;
            templateData = {
                parentName: this.name,
                name: 'paging',
                component: this.pagingComponent
            };
            rendererComponent = mageUtils.template(pagingTemplate, templateData);
            mageUtils.extend(rendererComponent, {
                settings: data,
                defaultVisible: false,
                visible: ko.observable(false),
                defaultShowLoader: false,
                displayArea: this.name + '.' + 'paging',
                pageSize: this.pageSize
            });
            layout([rendererComponent]);
        },
    })
})
