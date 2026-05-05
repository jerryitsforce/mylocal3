define([
    'jquery',
    'Branch8_GA4/js/actions/ga4push',
    'Magento_Customer/js/customer-data',
    'jquery/jquery.cookie',
    'domReady!'
], function ($, ga4push, customerData) {
    'use strict';

    return function customGa4(config) {
        console.log('call customGa4', config);

        const $categoryLink = $('.block-category-link');

        customerData.get('branch8_customer_data').subscribe(function (data) {
            if (data && data.branch8_ga4 && data.branch8_ga4.reload_sections) {
                console.log('Customer reload sections from CustomerData', data.branch8_ga4.reload_sections);
                const sections = data.branch8_ga4.reload_sections.split(',');
                customerData.reload(sections);
            }
        });

        $categoryLink.on('click', function(e){
            const $parent = $(this).parent().first();

            if ($parent.hasClass('manufacturer-exclusive__category-header')) {
                const findValid = type => $parent.find(`[data-content-type="${type}"]`)
                    .filter((i, el) => $(el).data('display') !== false);

                const $dataElement = findValid('products').add(findValid('product_recommendations'))
                    .first().closest('[data-content-type]');

                if (!$dataElement.length) return;

                const { itemListId, itemListName, promotionId, promotionName } = $dataElement.data();
                if (itemListId || itemListName || promotionId || promotionName) {
                    const ecomData = {
                        item_list_id: itemListId || undefined,
                        item_list_name: itemListName || undefined,
                        promotion_id: promotionId || undefined,
                        promotion_name: promotionName || undefined
                    };

                    ga4push([{
                        event: 'show_more_item',
                        ecommerce: Object.fromEntries(Object.entries(ecomData).filter(([_, v]) => v !== undefined))
                    }]);
                }
            }
        });


        $(document).on('click', '.show-more-item', function() {
            const $parent = $(this).closest('[data-content-type="row"]');

            if (!$parent.length) return;

            const findValid = type => $parent.find(`[data-content-type="${type}"]`)
                .filter((i, el) => $(el).data('display') !== false);

            const $dataElement = findValid('products').add(findValid('product_recommendations'))
                .first();

            if (!$dataElement.length) return;

            const { itemListId, itemListName, promotionId, promotionName } = $dataElement.data();

            if (itemListId || itemListName || promotionId || promotionName) {
                const ecomData = {
                    item_list_id: itemListId || undefined,
                    item_list_name: itemListName || undefined,
                    promotion_id: promotionId || undefined,
                    promotion_name: promotionName || undefined
                };

                ga4push([{
                    event: 'show_more_item',
                    ecommerce: Object.fromEntries(Object.entries(ecomData).filter(([_, v]) => v !== undefined))
                }]);
            }
        });


        $(document).on('click', '.product-item-link', function (e) {
            const isQuickView =  $(e.target).hasClass('magetop-quickview') || $(e.target).parents('.magetop-quickview').length;

            console.log('product-item-link', $(e.target).hasClass('product-item-link'), $(e.target).parents('.product-item-link').length, $(e.target).attr('class'), isQuickView, $(e.target))
            if(($(e.target).hasClass('product-item-link') || $(e.target).parents('.product-item-link').length) && !isQuickView ) {
                productLinkOnClick($(this));
            }
        });

        $(document).on('click', '.product-item-photo', function (e) {
            const isQuickView = $(e.target).hasClass('magetop-quickview') || $(e.target).parents('.magetop-quickview').length;

            console.log('product-item-photo', $(e.target).hasClass('product-item-photo'), $(e.target).parents('.product-item-photo').length,$(e.target).attr('class'), isQuickView, $(e.target))
            if(($(e.target).hasClass('product-item-photo') || $(e.target).parents('.product-item-photo').length) && !isQuickView ) {
                productLinkOnClick($(this));
            }
        });


        function productLinkOnClick($element) {
            const $parent = $element.closest('.product-item').first()
            const itemListId = $parent.data('item-list-id');
            const itemListName = $parent.data('item-list-name');
            const promotionId = $parent.data('promotion-id');
            const promotionName = $parent.data('promotion-name');

            const isGreatValueSection = $element.parents('.great-value-product-section').length > 0;
            const isRightColumn = $element.parents('.col-right').length > 0;

            const ga4ItemDataInput = $parent.find('.ga4-item-json').first();

            try {
                var ga4ItemData = ga4ItemDataInput && ga4ItemDataInput.val() ? JSON.parse(ga4ItemDataInput.val()) : null;
            } catch (error) {
                console.error('Invalid JSON', $(this).val());
                return;
            }

            if (ga4ItemData) {
                let ecommerce = {
                    item_list_id: itemListId || undefined, //set undefined to remove key if value is null
                    item_list_name: itemListName || undefined,
                    promotion_id: promotionId || undefined,
                    promotion_name: promotionName || undefined
                }

                ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key

                ga4ItemData.item_list_id = itemListId || undefined;
                ga4ItemData.item_list_name = itemListName || undefined;
                ga4ItemData.promotion_id = promotionId || undefined;
                ga4ItemData.promotion_name = promotionName || undefined;

                Object.keys(ga4ItemData).forEach(key => {
                    if (ga4ItemData[key] === undefined || ga4ItemData[key] === null || ga4ItemData[key] === '') {
                        delete ga4ItemData[key];
                    }
                });

                if (isGreatValueSection && isRightColumn)  {
                    ga4ItemData.index = parseInt(ga4ItemData.index || 0) + 1;
                } else {
                    console.log("not great value - right column");
                }


                ecommerce.items = [ga4ItemData];

                let eventData = {
                    event: 'select_item',
                    type: "商品卡",
                    ecommerce
                }
                const vipSection = $element.parents('.home-vip-section').first();

                if ($element.data('section') === 'vip' || vipSection.length) {
                    const customer = customerData.get('customer')();
                    console.log("customer", customer)
                    eventData.vip = customer.customer_group_name;
                }
                if ($element.data('section') === 'point') {

                    const customer = customerData.get('customer')();
                    console.log("customer", customer)
                    eventData.hotaiPoints = customer.customer_point;

                }



                ga4push([eventData]);
            }

        }
    };
});
