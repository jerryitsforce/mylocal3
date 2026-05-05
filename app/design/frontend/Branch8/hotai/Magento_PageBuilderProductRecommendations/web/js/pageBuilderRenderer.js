define([
    "Magento_ProductRecommendationsLayout/js/abstractRenderer",
    "Magento_ProductRecommendationsLayout/js/recsFetcher",
    "jquery",
], function (abstractRenderer, recsFetcher, $) {
    "use strict"
    return abstractRenderer.extend({
        initialize: function (config) {
            this._super(config)
            this.recsFetcher = recsFetcher
            if (config.unitId) this.getRecs(config)
            return this
        },

        getRecs: function (config) {

            const options = {
                unitId: config.unitId.substring(0, 36),
                pageType: "PageBuilder",
                defaultStoreViewCode: config.defaultStoreViewCode,
                alternateEnvironmentId: config.alternateEnvironmentId,
            }

            const element = $("#" + config.id);
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
                    self.recsFetcher.fetchUnit(options).then(
                        function (response) {
                            response = [response]
                            var unit = self.processResponse(response)[0];
                            element.removeClass('co-loading-ajax');
                            if (unit !== undefined) {
                                unit.ga4EventUid = self.generateUID(20)
                                unit.itemListId = config.itemListId;
                                unit.itemListName = config.itemListName;
                                unit.promotionId = config.promotionId;
                                unit.promotionName = config.promotionName;
                                unit.eventUniqueId = config.eventUniqueId;

                                if (config.itemListId || config.itemListName || config.promotionId || config.promotionName) {

                                    if (unit.products?.length > 0) {

                                        unit.products.forEach((product, index) => {
                                            try {
                                                if (product.image && product.image.url) {
                                                    const params = new URLSearchParams();
                                                    params.set('optimize', 'medium');
                                                    params.set('fit', 'bounds');
                                                    params.set('height', config.imageHeight || '372');
                                                    params.set('width', config.imageWidth || '372');
                                                    if(product.image.url.indexOf("?") !== -1) {
                                                        product.image.url += '&' + params.toString();
                                                    } else {
                                                        product.image.url += '?' + params.toString();
                                                    }
                                                }
                                                // product.url = 'http:' + product.url.replace('//hotai.docker/', '//hotai-magento.test/') // TODO: Remove this line
                                                const url = new URL(product.url);
                                                if (config.itemListId) url.searchParams.set('item_list_id', config.itemListId);
                                                if (config.itemListName) url.searchParams.set('item_list_name', config.itemListName);
                                                if (config.promotionId) url.searchParams.set('promotion_id', config.promotionId);
                                                if (config.promotionName) url.searchParams.set('promotion_name', config.promotionName);
                                                url.searchParams.set('position', index);

                                                product.url = url.toString();
                                            } catch (error) {
                                                const params = new URLSearchParams();
                                                if (config.itemListId) params.set('item_list_id', config.itemListId);
                                                if (config.itemListName) params.set('item_list_name', config.itemListName);
                                                if (config.promotionId) params.set('promotion_id', config.promotionId);
                                                if (config.promotionName) params.set('promotion_name', config.promotionName);

                                                params.set('position', index);

                                                if(product.url?.indexOf("?") !== -1) {
                                                    product.url += '&' + params.toString();
                                                } else {
                                                    product.url += '?' + params.toString();
                                                }
                                            }
                                            product.position = index;
                                        });
                                    }
                                }
                                self.recs.push(unit);
                            }
                        }.bind(this),
                    )
                }
            }

            loadData();

            // Scroll event listener
            $(window).on('scroll', function() {
               loadData();
            });


        },
    })
})
