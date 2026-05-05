define([
    'uiLayout',
    'uiRegistry',
    'mageUtils',
    'uiComponent',
    'jquery',
    'underscore',
    'ko',
    'mage/url',
    'plugins/DOMPurify',
    'mage/translate'
], function (layout, registry, mageUtils, Component, $, _, ko, urlBuilder, DOMPurify, $t) {
    'use strict';

    const pagingTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: '${ $.$data.component }',
        displayArea: 'paging',
        visible: false
    };
    const sanitizeHtmlConfig = {
        ALLOWED_TAGS: ['div', 'h4', 'a', 'span'],
        ALLOWED_ATTR: ['class', 'data-thread-id', 'id', 'href'],
        FORBID_TAGS: ['iframe', 'style', 'link', 'object', 'embed'],
        FORBID_ATTR: ['onerror', 'onload', 'onmouseover'],
        ADD_TAGS: ['script'],
        ALLOW_DATA_ATTR: false,
        SAFE_FOR_TEMPLATES: true
    };

    return Component.extend({
        createPaging: false,
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionCustomer/product/disscussion/threads',
            listTemplate: 'Branch8_MarketPlaceProductDiscussionCustomer/product/disscussion/threads/list',
            pagingComponent: 'Branch8_MarketPlaceProductDiscussion/js/paging'
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            var self = this;
            this._super()
                .observe({
                    threads: [],
                    loading: true,
                    totalThreads: 0,
                    currentPage: 1,
                    page: 1
                });
            this.hasItems = ko.pureComputed(() => {
                return this.threads().length > 0 && !this.loading();
            });
            this.viewFullThreadUrl = ko.computed(() => {
                return this.configurations.viewFull || '#';
            });
            this.page.subscribe(function (value) {
                self.currentPage(value);
                self.loadThreads();
            })
            return this;
        },
        /**
         *
         */
        toggleDiscussionCls: function () {
            if (!this.loading() && this.threads().length < 1) {
                $('#discussions').addClass('no-discussion-wrapper');
            } else {
                $('#discussions').removeClass('no-discussion-wrapper');
            }
        },
        /**
         *
         * @param total
         */
        updateTotalText: function (total) {
            const reg = `[data-ui-id=page-title-wrapper]`, element = $(reg);
            if (element.length) {
                element.text($t('全部問答(%1)').replace('%1', total))
            }
        },
        /**
         *
         */
        initialize: function () {
            this._super();
            this.loadThreads();
            if (this.configurations.showPaging) {
                this.createPagingComponent();
            }
            return this;
        },
        /**
         *
         */
        loadThreads: function () {
            this.loading(true);
            const self = this;
            $.ajax({
                url: this.configurations.url,
                type: 'GET',
                data: {
                    product_id: this.configurations.productId,
                    page: this.currentPage()
                }
            }).done(async (response) => {
                if (response.success && response.threads.length) {
                    const sanitizedThreads = response.threads.map((thread) => {
                        thread.title = DOMPurify.sanitize(
                            thread.title,
                            sanitizeHtmlConfig
                        );
                        return thread;
                    });
                    this.threads.push.apply(this.threads, sanitizedThreads);
                    this.totalThreads(response.total_count);
                    if (this.totalThreads() > 10 && this.configurations.showPaging === true) {
                        registry.async(this.name + '.' + 'paging')(function (pagingComponent) {
                            pagingComponent.pageSize(self.configurations.pageSize || 10);
                            pagingComponent.totalCount(response.total_count);
                        })
                    }
                    if (this.configurations.pageType === 'list') {
                        this.updateTotalText(response.total_count);
                    }
                    this.loading(false);
                } else {
                    this.loading(false);
                }
                this.toggleDiscussionCls();
            }).fail((err) => {
                console.error('Load threads error:', err);
                this.loading(false);
                this.toggleDiscussionCls();
            }).always(() => {
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
                pageSize: this.pageSize || 10
            });
            layout([rendererComponent]);
        }
    });
});
