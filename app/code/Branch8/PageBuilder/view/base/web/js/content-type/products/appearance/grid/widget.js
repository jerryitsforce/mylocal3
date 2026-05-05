define([
    'jquery',
    'Branch8_GA4/js/actions/ga4push',
    'Magento_Customer/js/customer-data',
    'plugins/DOMPurify'
], function ($, ga4push, customerData, DOMPurify) {
    'use strict';

    return function (config, element) {
        const $element = $(element);

        let ecommerce = {
            promotion_id: $element.data('promotion-id') || undefined,
            promotion_name: $element.data('promotion-name') || undefined,
            item_list_id: $element.data('item-list-id') || undefined,
            item_list_name: $element.data('item-list-name') || undefined,
        };

        ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key
        ecommerce.items = [];

        //超值商品專區
        //For index, Can be counted together with the recommendation area in the left hand side
        //Take "本月主打(highlight this month)," the recommendation area in the left hand side, as 0

        const $greatValueSection = $element.parents('.great-value-product-section').first();
        const isLeftColumn = $element.parents('.col-left').length > 0;
        let itemIndex = 0;

        if ($greatValueSection.length && isLeftColumn) {
            return;
        }

        const processItems = $elements => {
            $elements.each(function() {
                try {
                    const item = JSON.parse($(this).val());
                    Object.keys(item).forEach(key =>
                        (item[key] == null || item[key] === '') && delete item[key]
                    );
                    ecommerce.items.push({
                        ...item,
                        item_id: parseInt(item.item_id),
                        index: itemIndex++
                    });
                } catch (error) {
                    console.error('Invalid JSON', $(this).val());
                }
            });
        };

        if ($greatValueSection.length && $element.parents('.col-right').length) {
            const $leftCol = $greatValueSection.find('.col-left').first();

            const $products = $leftCol.find('[data-content-type="products"]')
                .filter((i, el) => $(el).data('display') !== false);

            processItems($products.find('.ga4-item-json'));

            const $recommendations = $leftCol.find('[data-content-type="product_recommendations"]')
                .filter((i, el) => $(el).data('display') !== false);

            processItems($recommendations.find('.ga4-item-json'));
        }
        //超值商品專區

        processItems($element.find('.ga4-item-json'));

        const eventData = {
            event: 'view_item_list',
            ecommerce
        }

        const vipSection = $element.parents('.home-vip-section').first();
        var jQ = $.noConflict();
        if (vipSection.length > 0) {
            const customer = customerData.get('customer')();
            eventData.vip = customer.customer_group_name;

            $element.find('a.product-item-link').each(function() {
                jQ(this).attr('data-section', 'vip')
                let href = DOMPurify.sanitize(jQ(this).attr('href'));
                let url = new URL(href);
                let params = new URLSearchParams(url.search);
                params.set('section', 'vip');
                url.search = params.toString();
                jQ(this).attr('href', DOMPurify.sanitize(url.toString()));
            });

            $element.find('a.product-item-photo').each(function() {
                jQ(this).attr('data-section', 'vip')
                let href = DOMPurify.sanitize(jQ(this).attr('href'));
                let url = new URL(href);
                let params = new URLSearchParams(url.search);
                params.set('section', 'vip');
                url.search = params.toString();
                jQ(this).attr('href', DOMPurify.sanitize(url.toString()));
            });

            $element.find('form[data-role="tocart-form"]').each(function() {
                //add hidden input name vip_section to form
                $(this).append('<input type="hidden" name="section" value="vip">');
            });

        }

        /**
         * This part of code is to handle the GA4 event push whenever the element is visible on screen.
         */
        //DO NOT: create variable for key $element.data('ga4-event-uid')
        window[$element.data('ga4-event-uid')] = false

        function buildThresholdList(numSteps) {
            let thresholds = [0.0];

            for (let i = 1; i <= numSteps; i++) {
                let ratio = i / numSteps;
                thresholds.push(ratio);
            }

            thresholds.push(1.0);
            return thresholds;
        }

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: buildThresholdList(100),
        };

        const handleIntersection = (entries, observer) => {
            entries.forEach((entry) => {
                let isIntersecting = entry.isIntersecting

                if (isIntersecting) {
                    checkAndPushGA4Event($element);
                } else {
                    window[$element.data('ga4-event-uid')] = false;
                }
            });
        };
        const observer = new IntersectionObserver(handleIntersection, observerOptions);
        observer.observe($element[0]);


        const isOnScreen = function($element) {
            const $window = $(window);
            const viewport = {
                top: $window.scrollTop(),
                bottom: $window.scrollTop() + $window.height()
            };
            const bounds = $element.offset();
            bounds.bottom = bounds.top + $element.outerHeight();
            return (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
        };

        const checkAndPushGA4Event = function($element) {
            if (window[$element.data('ga4-event-uid')]) {
                return;
            }

            const isWrappedByTabs = $element.closest('[data-content-type="tab-item"]').length > 0;
            if (isWrappedByTabs) {
                const tabItemParent = $element.closest('[data-content-type="tab-item"]').first()

                const isHidden = (
                    tabItemParent.is(':hidden') ||
                    tabItemParent.css('visibility') === 'hidden' ||
                    tabItemParent.css('opacity') === '0' ||
                    tabItemParent.attr('hidden') !== undefined ||
                    tabItemParent.hasClass('hidden')
                );

                if (isHidden) {
                    return
                }
            }
            ga4push([eventData]);
            window[$element.data('ga4-event-uid')] = true
        }

        if(isOnScreen($element)) {
            checkAndPushGA4Event($element);
        }
    };
});
