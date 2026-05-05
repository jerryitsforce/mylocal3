/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'matchMedia',
    'Magento_PageBuilder/js/utils/breakpoints',
    'Magento_PageBuilder/js/events',
    'Branch8_GA4/js/actions/ga4push',
    'Magento_Customer/js/customer-data',
    'plugins/DOMPurify',
    'swiper',
    'slick'
], function ($, _, mediaCheck, breakpointsUtils, events, ga4push, customerData, DOMPurify, Swiper) {
    'use strict';

    /**
     * Build slick
     *
     * @param {jQuery} $carouselElement
     * @param {Object} config
     */
    function buildSlick($carouselElement, config) {
        /**
         * Prevent each slick slider from being initialized more than once which could throw an error.
         */
        if ($carouselElement.hasClass('slick-initialized')) {
            $carouselElement.slick('unslick');
        }

        config.slidesToScroll = config.slidesToShow;


        if($carouselElement.parents('.products-swiper').length){
            new Swiper('.products-swiper[data-content-type="products"]', {
                wrapperClass: 'product-items',
                slideClass: 'product-item',
                slidesPerView: 'auto',
                centeredSlides: config.centerMode,
                autoplay: config.autoplay ? {
                    delay: config.autoplaySpeed,
                } : false,
                loop: config.infinite,
                spaceBetween: 0,
                pagination: config.dots ? {
                    el: '.swiper-pagination',
                    type: 'bullets',
                } : false,
                navigation: config.arrows ? {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                } : false,

                //add swiper-slide-visible class to slides are visible
                watchSlidesProgress: true,
                slideVisibleClass: 'swiper-slide-visible',
                on: {
                    slideChange: function () {
                        console.log('slideChange');
                        pushGa4EventAfterInitSlick($carouselElement.parents('.products-swiper').first())
                    },
                },
            });
        }else{
            $carouselElement.slick(config);
        }
    }

    /**
     * Initialize slider.
     *
     * @param {jQuery} $element
     * @param {Object} slickConfig
     * @param {Object} breakpoint
     */
    function initSlider($element, slickConfig, breakpoint, isAjax = false , configSlidesToShow) {
        var productCount = $element.find('.product-item').length,
            $carouselElement = $($element.find('.product-items.widget-product-carousel')),
            centerModeClass = 'center-mode',
            carouselMode = $element.data('carousel-mode'),
            slidesToShow = breakpoint.options.products[carouselMode] ?
                breakpoint.options.products[carouselMode].slidesToShow :
                breakpoint.options.products.default.slidesToShow,
            variableWidth = breakpoint.options.products[carouselMode] ?
            breakpoint.options.products[carouselMode].variableWidth :
            breakpoint.options.products.default.variableWidth;

            if(configSlidesToShow && !_.isNaN(parseFloat(configSlidesToShow))){
                slidesToShow = configSlidesToShow;
            }

        slickConfig.slidesToShow = parseFloat(slidesToShow);
        slickConfig.variableWidth = variableWidth;

        if (isAjax) {
            $carouselElement = $element.find('.product-items.widget-product-carousel');
        }

        if (carouselMode === 'continuous' && productCount > slickConfig.slidesToShow) {
            $element.addClass(centerModeClass);
            slickConfig.centerPadding = $element.data('center-padding');
            slickConfig.centerMode = true;
        } else {
            $element.removeClass(centerModeClass);
            slickConfig.infinite = $element.data('infinite-loop');
        }

        if(configSlidesToShow == 'variableWidth'){
            // slickConfig.slidesToShow = 1
            slickConfig.variableWidth = true;
            slickConfig.infinite = true
        }else{
            if(configSlidesToShow == 'continuous'){
                if(breakpoint.conditions['max-width'] === '640px'){
                    slickConfig.slidesToShow = 1
                }
                slickConfig.centerMode = true;
                slickConfig.centerPadding = $element.data('center-padding');
                slickConfig.infinite = true
            }else{
                if(carouselMode !== 'continuous'){
                    slickConfig.centerMode = false;
                }
            }
            slickConfig.infinite = $element.data('infinite-loop');
            // slickConfig.variableWidth = false;
        }

        if(configSlidesToShow == 'unSlider'){
            if ($carouselElement.hasClass('slick-initialized')) {
                $carouselElement.slick('unslick');
            }
        }else{
            buildSlick($carouselElement, slickConfig);
        }
        pushGa4EventAfterInitSlick($element);
    }


    function pushGa4EventAfterInitSlick($element){
        window[$element.data('ga4-event-uid')] = false
        const eventData = getElementGa4EventData($element)

        if (isOnScreen($element) && isDisplay($element)) {
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

        let eventData = {
            event: 'view_item_list',
            ecommerce
        }

        const vipSection = $element.parents('.home-vip-section').first();
        if (vipSection.length > 0) {
            const customer = customerData.get('customer')();
            eventData.vip = customer.customer_group_name;
        }

        return eventData;
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

    function buildThresholdList(numSteps) {
        let thresholds = [0.0];

        for (let i = 1; i <= numSteps; i++) {
            let ratio = i / numSteps;
            thresholds.push(ratio);
        }

        thresholds.push(1.0);
        return thresholds;
    }


    return function (config, element, isAjax = false) {
        var $parentElement = $(element).parents('.react-product-carousel');
        if ($parentElement.length) {
            element = $parentElement.parent();
        }
        var $element = $(element),
            $carouselElement = $($element.find('.product-items.widget-product-carousel')),
            currentViewport = config.currentViewport,
            currentBreakpoint = config.breakpoints[currentViewport],
            slickConfig = {
                autoplay: $element.data('autoplay'),
                autoplaySpeed: $element.data('autoplay-speed') || 0,
                arrows: $element.data('show-arrows'),
                dots: $element.data('show-dots'),
            };

        if (isAjax) {
            $carouselElement = $element.find('.widget-product-carousel');
        }

        const vipSection = $element.parents('.home-vip-section').first();

        let sliderId = $element.attr('id');
        let useChildElement=false
        if (!sliderId) {
            sliderId = $element.children('div').attr('id');
            useChildElement=true
        }
        window.sessionStorage.removeItem(sliderId);
        /*
          @Issue
            Currently localstorage database getting oversize (>10mb) because breakpoint config saved to here many times
           ex:when page cleared , new unique id created but old ID not removed
          */
        if (!useChildElement) {
            window.sessionStorage.setItem(sliderId, JSON.stringify(config));
            $element.attr('data-break-point-config', JSON.stringify(config));
        } else {
            $element.children('div').attr('data-break-point-config', JSON.stringify(config));
        }

        var jQ = $.noConflict();
        if (vipSection.length > 0) {

            $element.find('a.product-item-link').each(function () {
                jQ(this).attr('data-section', 'vip')
                let href = DOMPurify.sanitize(jQ(this).attr('href'));
                let url = new URL(href);
                let params = new URLSearchParams(url.search);
                params.set('section', 'vip');
                url.search = params.toString();
                jQ(this).attr('href', DOMPurify.sanitize(url.toString()));
            });

            $element.find('a.product-item-photo').each(function () {
                jQ(this).attr('data-section', 'vip')
                let href = DOMPurify.sanitize(jQ(this).attr('href'));
                let url = new URL(href);
                let params = new URLSearchParams(url.search);
                params.set('section', 'vip');
                url.search = params.toString();
                jQ(this).attr('href', DOMPurify.sanitize(url.toString()));
            });

            $element.find('form[data-role="tocart-form"]').each(function () {
                //add hidden input name vip_section to form
                $(this).append('<input type="hidden" name="section" value="vip">');
            });
        }

        const checkAndPushGA4Event = function ($element) {
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

            if (!isDisplay($element)) {
                return
            }

            const eventData = getElementGa4EventData($element);

            ga4push([eventData]);
            window[$element.data('ga4-event-uid')] = true
        }

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

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: buildThresholdList(100),
        };

        if (isDisplay) {
            //TODO: create shared observer to use in other widgets
            const observer = new IntersectionObserver(handleIntersection, observerOptions);
            observer.observe($element[0]);
        }


        //BestSeller Product Carousel
        $element.find(".product-item.view-more").on("click", function () {
            let ecommerce = {
                promotion_id: $element.data('promotion-id') || undefined,
                promotion_name: $element.data('promotion-name') || undefined,
                item_list_id: $element.data('item-list-id') || undefined,
                item_list_name: $element.data('item-list-name') || undefined,
            }

            ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key

            if (Object.keys(ecommerce).length) {
                ga4push([{event: 'show_more_item', ecommerce}]);
            } else {
                console.info('GA4 Event "show_more_item" is not pushed because of missing required parameters');
            }
        });


        _.each(config.breakpoints, function (breakpoint, index) {
            mediaCheck({
                media: breakpointsUtils.buildMedia(breakpoint.conditions),

                /** @inheritdoc */
                entry: function () {
                    var configSlidesToShow = index == 'desktop' ? $element.data('slidetoshow-desktop') :
                    index == 'tablet' ? $element.data('slidetoshow-tablet') : $element.data('slidetoshow-mobile');
                    initSlider($element, slickConfig, breakpoint, isAjax, configSlidesToShow);
                }
            });
        });

        //initialize slider when content type is added in mobile viewport
        if (currentViewport === 'mobile') {
            initSlider($element, slickConfig, currentBreakpoint, isAjax);
        }

        // Redraw slide after content type gets redrawn
        events.on('contentType:redrawAfter', function (args) {
            if ($carouselElement.closest(args.element).length) {
                $carouselElement.slick('setPosition');
            }
        });

        events.on('stage:viewportChangeAfter', function (args) {
            var breakpoint = config.breakpoints[args.viewport];

            initSlider($element, slickConfig, breakpoint, isAjax);
        });

        $element.on('afterChange', function(event, slick, currentSlide) {
            pushGa4EventAfterInitSlick($element)
        });

    };
});
