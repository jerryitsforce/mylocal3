define([
    'jquery',
    'Branch8_GA4/js/actions/ga4push',
], function ($, ga4push) {
    'use strict';

    return function (config, element) {

        function pushGa4EventAfterInit($element){
            window[$element.data('ga4-event-uid')] = false
            const eventData = getElementGa4EventData($element)

            if (isOnScreen($element) && isDisplay($element) && isVisible($element)) {
                ga4push([eventData]); // push view_promotion event to GA4 after init
                window[$element.data('ga4-event-uid')] = true
            }
        }


        function getElementGa4EventData($element) {
            let ecommerce = {
                promotion_id: $element.data('promotion-id') || undefined,
                promotion_name: $element.data('promotion-name') || undefined,
                item_list_id: $element.data('item-list-id') || undefined,
                item_list_name: $element.data('item-list-name') || undefined,
            }
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
                    const isSlickSlide = $(this).closest('.slick-initialized').length > 0 && !$(this).closest('.slick-initialized').hasClass('banner-slider');
                    const isSwipeSlide = $(this).closest('.swiper-initialized').length > 0

                    if (isSlickSlide) {
                        const isSlickActive = $(this).closest('.slick-slide').hasClass('slick-active');
                        const isSlickCloned = $(this).closest('.slick-slide').hasClass('slick-cloned');
                        if (!isSlickActive || isSlickCloned) {
                            return;
                        }
                    } else if (isSwipeSlide) {
                        const isVisibleSlide = $(this).closest('.product-item').hasClass('swiper-slide-visible')
                        if (!isVisibleSlide) {
                            return;
                        }
                    }

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


            return {
                event: 'view_item_list',
                ecommerce
            }
        }

        function isDisplay($element) {
            let isDisplay = $element.attr('data-display') !== 'false';

            if (isDisplay) {
                $element.parentsUntil('#maincontent').each(function() {
                    if ($(this).attr('data-display') === 'false') {
                        isDisplay = false;
                        return false;
                    }
                });
            }
            return isDisplay;
        }


        function isVisible($element) {
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
                    return false;
                }
            }

            let invisible = false;
            //.popular-topic-category-section
            if($element.closest('.pagebuilder-column-group.invisible').length > 0) {
                invisible = $element.closest('.popular-topic-category-section.show-all').length === 0
            }

            return !invisible;
        }

        /**
         * This part of code is to handle the GA4 event push whenever the element is visible on screen.
         */
        const $element = $(element);

        //DO NOT: create variable for key $element.data('ga4-event-uid')
        window[$element.data('ga4-event-uid')] = true; //wait for init

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

        if (isDisplay($element)) {
            const observer = new IntersectionObserver(handleIntersection, observerOptions);
            observer.observe($element[0]);
        }

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

            if (!isVisible($element)) {
                return
            }

            let eventData = getElementGa4EventData($element)

            if (!eventData?.ecommerce?.items || eventData?.ecommerce?.items?.length === 0) {
                return;
            }

            ga4push([eventData]);
            window[$element.data('ga4-event-uid')] = true
        }


        //after Slick init
        $element.on("init", function(event, data) {
            pushGa4EventAfterInit($element)
        });

        //after Slick change slide
        $element.on('afterChange', function(event, slick, currentSlide) {
            pushGa4EventAfterInit($element)
        });

    };
});
