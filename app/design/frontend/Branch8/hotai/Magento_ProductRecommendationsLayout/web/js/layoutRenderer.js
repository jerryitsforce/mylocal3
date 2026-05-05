define([
    "Magento_ProductRecommendationsLayout/js/abstractRenderer",
    "Magento_ProductRecommendationsLayout/js/recsFetcher",
    "dataServicesBase",
    "jquery",
], function (abstractRenderer, recsFetcher, ds, $) {
    "use strict"
    return abstractRenderer.extend({
        initialize: function (config) {
            this._super(config)
            this.recsFetcher = recsFetcher
            this.config = config
            this.getRecs()
            const self = this;
            // For test
            $(document).on('click', '#rec_reload',  function(){
                console.log('reload click');
                self.getRecs()
            });
            return this
        },

        getRecs: async function () {
            this.recsFetcher.fetchPagePreconfigured().then(
                function (response) {
                    var units = this.processResponse(response)
                    // check if it is for this pagePlacement
                    units = units.filter(
                        unit =>
                            unit.pagePlacement === this.pagePlacement &&
                            unit.products.length > 0,
                    )
                    units.forEach(unit => {
                        unit.ga4EventUid = this.generateUID(20)
                        this.addGa4Data(unit)
                        this.recs.push(unit)
                    })
                }.bind(this),
            )
        },

        addGa4Data: function (unit) {
            //This func add ga4 data to unit for AlsoBought and YouMayLike only
            const blockNames = [
                'product_recommendations_product_below_content',
                'product_recommendations_category_before_content',
                'product_recommendations_category_below_content',
            ]
            if (blockNames.includes(this.config?.blockName)) {
                unit.blockName = this.config?.blockName
                if (this.config?.blockName === 'product_recommendations_product_below_content') {
                    if (unit.typeId !== 'viewed-bought' && unit.typeId !== 'recently-viewed') {
                        return unit;
                    }
                    if (unit.typeId === 'viewed-bought') {
                        unit.itemListId = 'product_alsoBought';
                        unit.itemListName = '商品頁_其他人也買過';
                        unit.promotionId = 'alsoBought_' + this.config?.curProductId;
                        unit.promotionName = 'alsoBought_' + this.config?.curProductName;
                    } else {
                        unit.itemListId = 'product_youMayLike';
                        unit.itemListName = '商品頁_猜你喜歡';
                        unit.promotionId = 'youMayLike_' + this.config?.curProductId;
                        unit.promotionName = 'youMayLike_' + this.config?.curProductName;
                    }
                }

                if (this.config?.blockName === 'product_recommendations_category_before_content' || this.config?.blockName === 'product_recommendations_category_below_content') {
                    unit.itemListId = 'search_youMayLike';
                    unit.itemListName = '搜尋頁_猜你喜歡';
                    unit.promotionId = 'youMayLike';
                    unit.promotionName = '猜你喜歡';
                }

                unit.products.forEach((product, index) => {
                    try {
                        if (product.image && product.image.url) {
                            const params = new URLSearchParams();
                            params.set('optimize', 'medium');
                            params.set('fit', 'bounds');
                            params.set('height', this.config.imageHeight || '372');
                            params.set('width', this.config.imageWidth || '372');
                            if(product.image.url.indexOf("?") !== -1) {
                                product.image.url += '&' + params.toString();
                            } else {
                                product.image.url += '?' + params.toString();
                            }
                        }
                        // product.url = 'http:' + product.url.replace('//hotai.docker/', '//hotai-magento.test/') // TODO: Remove this line
                        const url = new URL(product.url);
                        url.searchParams.set('item_list_id', unit.itemListId);
                        url.searchParams.set('item_list_name', unit.itemListName);
                        url.searchParams.set('promotion_id', unit.promotionId);
                        url.searchParams.set('promotion_name', unit.promotionName);
                        url.searchParams.set('position', index);

                        product.url = url.toString();
                    } catch (error) {
                        const params = new URLSearchParams();
                        params.set('item_list_id', unit.itemListId);
                        params.set('item_list_name', unit.itemListName);
                        params.set('promotion_id', unit.promotionId);
                        params.set('promotion_name', unit.promotionName);

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
    })
})
