define([
    "uiComponent",
    "jquery",
    'ko'
], function(Component, $, ko) {
    "use strict"
    var searchKeywords = ko.observableArray([]);
    return Component.extend({
        defaults: {
            template: "Branch8_PageBuilderSearch/keywords-search.html",
            recs: [],
        },
        initialize: function(config) {
            this._super(config);
            this.limit = config.limit;
            this.url_search = config.url_search;
            this.url_ajax = config.url_ajax;
            this.page = config.page;
            this.total = config.total;
            this.id = config.id;
            this.callAjax();
            return this;
        },

        refresh: function() {
            this.page = 1;
            this.callAjax();
        },

        callAjax: function() {
            const element = $("#"+this.id);
            element.attr('data-rendered', 'false');
            
            function isElementInViewport(el) {
                var rect = el[0].getBoundingClientRect();
                // console.log('Magento_CatalogWidget/js/product', (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0), el, rect, rect.top - (window.innerHeight*4/5), window.innerHeight);
                return (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0);
                // return (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0);
            }

            var self = this;
            function loadData() {
                if (isElementInViewport(element) && element.attr('data-rendered') === 'false') {
                    // console.log('Element is in viewport', element);
                    element.attr('data-rendered', 'true');
                    // Perform AJAX request
                    $.ajax({
                        url: self.url_ajax,
                        type: "get",
                        data: {
                            limit: self.limit,
                            page: self.page
                        },
                        context: { url_search: self.url_search },
                        success: function(response) {
                            element.removeClass('co-loading-ajax');
                            searchKeywords([]);
                            $.each(response, (function(i, v) {
                                searchKeywords.push({
                                    text: v,
                                    url: self.url_search + "?q=" + v
                                });
                            }).bind(self));
                        },
                        error: function(xhr) {
                            element.removeClass('co-loading-ajax');
                            //Do Something to handle error
                        }
                    });
                }
            }

            loadData();

            // Scroll event listener
            $(window).on('scroll', function() {
                loadData();
            });
            
        },

        getSearchKeywords: function() {
            return searchKeywords;
        },

        getSearchUrl: function() {
            return this.url_search;
        },

    })
})
