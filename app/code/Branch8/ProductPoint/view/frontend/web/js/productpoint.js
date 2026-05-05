/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'Branch8_GA4/js/actions/ga4push',
    'Magento_Customer/js/customer-data',
    'plugins/DOMPurify',
    'matchMedia',
], function ($, ga4push, customerData , DOMPurify) {
    'use strict';

    function pushViewItemListEvent(element) {
        console.log('pushViewItemListEvent', element.find('.product-point-col'));

        element.find('.product-point-col').each(function () {
            const isShowing = !$(this).hasClass('point-tab-content') || $(this).hasClass('active');
            if (isShowing) {
                const ecommerce = {
                    promotion_id: $(this).data('promotion-id'),
                    promotion_name: $(this).data('promotion-name'),
                    item_list_id: $(this).data('item-list-id'),
                    item_list_name: $(this).data('item-list-name'),
                    items: []
                }


                $(this).find('.ga4-item-json').each(function () {

                    try {
                        var item = JSON.parse($(this).val());
                    } catch (error) {
                        console.error('Invalid JSON', $(this).val());
                        return;
                    }

                    Object.keys(item).forEach(key => {
                        if (item[key] === undefined || item[key] === null || item[key] === '') {
                            delete item[key];
                        }
                    });

                    ecommerce.items.push({
                        ...item,
                        item_id: parseInt(item.item_id),
                    });
                });
                const viewListEventData = {
                    event: 'view_item_list',
                    ecommerce: ecommerce
                }

                ga4push([viewListEventData]);
            }
        });
    }

    function isDisplay(element) {
        let isDisplay = element.attr('data-display') !== 'false';

        if (isDisplay) {
            element.parentsUntil('#maincontent').each(function() {
                if ($(this).attr('data-display') === 'false') {
                    isDisplay = false;
                    return false;
                }
            });
        }
        return isDisplay;
    }

    function isOnScreen ($element) {
        const $window = $(window);
        const viewport = {
            top: $window.scrollTop(),
            bottom: $window.scrollTop() + $window.height()
        };
        const bounds = $element.offset();
        bounds.bottom = bounds.top + $element.outerHeight();
        return (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
    }

    function checkAndPushGA4Event($element) {
        console.log('checkAndPushGA4Event', $element.data('rendered'), window[$element.data('ga4-event-uid')]);
        if (window[$element.data('ga4-event-uid')] || !$element.data('rendered')) {
            return;
        }

        pushViewItemListEvent($element);
        window[$element.data('ga4-event-uid')] = true
    }

    function renderTabsProductPointUi(){
        // Product Grid Point Block - Tabs container - Mobile
        mediaCheck({
            media: "(max-width: 768px)",
            entry: $.proxy(function () {
                var jQ = $.noConflict();
                if ($('[data-appearance="point_grid"]').length) {
                    $('[data-appearance="point_grid"]').each(function () {
                        var $productRow = $(this).find(".product-point-row");
                        $productRow.find('.product-point-col').addClass('point-tab-content');

                        if ($(this).find(".product-point-tabs").length) return;
                        $('<div class="product-point-tabs"></div>').insertBefore(
                            $productRow
                        );

                        const $pointTabs = $(this).find(".product-point-tabs");

                        $(this).find('.point-range').each(function () {

                            const $tabItem = jQ(this).clone();

                            $tabItem.on('click', function () {
                                const colTrigger = DOMPurify.sanitize(jQ(this).attr("data-tab-trigger"));
                                const $productPointEle = jQ(this).closest(
                                    '[data-appearance="point_grid"]'
                                );
                                $productPointEle.find(".point-range").removeClass("active");
                                $productPointEle
                                    .find(".product-point-col")
                                    .removeClass("active");
                                jQ(this).addClass("active");
                                jQ('[data-tab-content="' + colTrigger + '"]').addClass("active");


                                //find parent element by id product-point-container
                                const $productPointContainer = $(this).closest('#product-point-container');
                                window[$productPointContainer.data('ga4-event-uid')] = false
                                checkAndPushGA4Event($productPointContainer);
                            });
                            $pointTabs.append($tabItem)
                            // $(this).clone().appendTo(".product-point-tabs");
                        });

                        $(this)
                            .find(".product-point-row .product-point-col")
                            .eq(0)
                            .addClass("active");

                        $(this).find(".point-range").eq(0).addClass("active");
                    });
                }
            }, this),
            exit: $.proxy(function () {
                if ($('[data-appearance="point_grid"]').length) {
                    $('[data-appearance="point_grid"]').each(function () {
                        $(this).find(".product-point-col").removeClass("point-tab-content").removeClass("active");
                        $(this).find(".product-point-tabs").remove();

                        const $productPointContainer = $(this).find('#product-point-container');
                        if(isOnScreen($productPointContainer)) {
                            window[$productPointContainer.data('ga4-event-uid')] = false
                            checkAndPushGA4Event($productPointContainer);
                        }

                    });
                }


            }, this),
        });
    }

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_ProductPoint/js/productpoint': function (dataInfo) {
            var jQ = $.noConflict(),
                element = jQ(dataInfo.selector);
            const isProductCarousel = dataInfo.selector === '#product-point-container'

            // let ecommerce = {
            //     promotion_id: dataInfo?.info?.promotion_id || undefined,
            //     promotion_name: dataInfo?.info?.promotion_name|| undefined,
            //     item_list_id: dataInfo?.info?.item_list_id|| undefined,
            //     item_list_name: dataInfo?.info?.item_list_name|| undefined,
            // }

            // ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key

            if (isProductCarousel && isDisplay(element)) {
                //#product-point-container
                window[element.data('ga4-event-uid')] = false

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
                            checkAndPushGA4Event(element);
                        } else {
                            window[element.data('ga4-event-uid')] = false;
                        }
                    });
                };
                //TODO: create shared observer to use in other widgets
                const observer = new IntersectionObserver(handleIntersection, observerOptions);
                observer.observe(element[0]);
            }

            element.attr('data-rendered', 'false');

            function isElementInViewport(el) {
                var rect = el[0].getBoundingClientRect();
                // console.log('Magento_CatalogWidget/js/product', (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0), el, rect, rect.top - (window.innerHeight*4/5), window.innerHeight);
                return (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0);
                // return (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0);
            }

            function loadData() {
                if (isElementInViewport(element) && element.attr('data-rendered') === 'false') {
                    console.log('Element is in viewport', element);
                    element.attr('data-rendered', 'true');
                    // Perform AJAX request
                    jQ.ajax({
                        url: element.data('src')+'?_=' + new Date().getTime(),
                        type: 'post',
                        data: jQ.extend(dataInfo.info, {isAjax: 1}),
                        dataType: 'html'
                    }).done(function (data) {
                        const sanitizedData = DOMPurify.sanitize(data);
                        if(sanitizedData && sanitizedData.includes('page-wrapper')){
                            window.location.reload();
                            return;
                        }
                        element.html('<div class="points-discount">'+sanitizedData+'</div>');
                        element.attr('data-rendered', 'true');
                        element.removeClass('co-loading-ajax');
                        const customer = customerData.get('customer');
                        const totalPoints = customer()?.customer_point_formated || 0;
                        console.log('totalPoints', totalPoints);
                        $('.points-discount .sub-heading .price').text(totalPoints);

                        if(isProductCarousel){
                            renderTabsProductPointUi();
                            console.log('isOnScreen', isOnScreen(element),isDisplay(element))

                            if (isOnScreen(element) && isDisplay(element)) {
                                checkAndPushGA4Event(element)
                            }
                            element.find(".product-point-actions.view-all").on("click", function () {
                                if (Object.keys(ecommerce).length) {
                                    ga4push([{event: 'show_more_item', ecommerce}]);
                                } else {
                                    console.info('GA4 Event "show_more_item" is not pushed because of missing required parameters');
                                }
                            });
                        }
                    });
                }
            }

            loadData();

            // Scroll event listener
            $(window).on('scroll', function() {
                loadData();
            });
        }
    };
});
