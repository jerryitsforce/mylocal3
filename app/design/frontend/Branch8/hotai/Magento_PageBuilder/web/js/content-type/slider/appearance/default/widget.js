/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_PageBuilder/js/events',
    'Branch8_GA4/js/actions/ga4push',
    'slick'
], function ($, events, ga4push) {
    'use strict';

    return function (config, sliderElement) {
        var $element = $(sliderElement);

        //DO NOT: create variable for key $element.data('ga4-event-uid')
        window[$element.data('ga4-event-uid')] = false

        let isDisplay = $element.attr('data-display') !== 'false';

        if (isDisplay) {
            $element.parentsUntil('#maincontent').each(function() {
                if ($(this).attr('data-display') === 'false') {
                    isDisplay = false;
                    return false;
                }
            });
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

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: buildThresholdList(100),
        };

        const handleIntersection = (entries, observer) => {
            entries.forEach((entry) => {
                let isIntersecting = entry.isIntersecting

                if (isIntersecting) {
                    if (!window[$element.data('ga4-event-uid')]) {
                        const $currentSlide = $element.find('.slick-current');
                        const ga4Data = $currentSlide.find('.has-ga4-data').first();
                        const creative_name = ga4Data.data('creative-name') || undefined;
                        const creative_slot = ga4Data.data('creative-slot') || undefined;
                        const promotion_id = ga4Data.data('promotion-id') || undefined;
                        const promotion_name = ga4Data.data('promotion-name') || undefined;
                        const item_list_id = ga4Data.data('item-list-id') || undefined;
                        const item_list_name = ga4Data.data('item-list-name') || undefined;
                        const isShowRecommendation = ga4Data.data('show-recommend') === "show";
                        const recommendationProducts = $currentSlide.find('.block.related-products').first();
                        let promotionEcommerce = {creative_name, creative_slot, promotion_id, promotion_name};
                        promotionEcommerce = JSON.parse(JSON.stringify(promotionEcommerce)); //remove undefined key

                        let productListEcommerce = {
                            item_list_id,
                            item_list_name,
                            promotion_id,
                            promotion_name,
                        };

                        productListEcommerce = JSON.parse(JSON.stringify(productListEcommerce)); //remove undefined key
                        productListEcommerce.items = [];

                        if (Object.keys(promotionEcommerce).length) {
                            ga4push([{event: 'view_promotion', ecommerce: promotionEcommerce}])
                        } else {
                            console.log(`GA4 Event "view_promotion" is not pushed because of missing required parameters`);
                        }

                        if (recommendationProducts.length && isShowRecommendation) {
                            recommendationProducts.find('.ga4-item-json').each(function () {

                                const $productItemParent = $(this).closest('.product-item').first();
                                //check css display = none -> ignore

                                if ($productItemParent.css("display") === "none") {
                                   return;
                                }

                                try {
                                    var item = JSON.parse($(this).val());
                                } catch (error) {
                                    console.error('Invalid JSON', $(this).val());
                                    return;
                                }

                                productListEcommerce.items.push({
                                    ...item,
                                    item_id: parseInt(item.item_id),
                                });
                            });

                            ga4push([{event: 'view_item_list', ecommerce: productListEcommerce}])
                        }

                        window[$element.data('ga4-event-uid')] = true;
                    }
                } else {
                    window[$element.data('ga4-event-uid')] = false;
                }
            });
        };

        if (isDisplay) {
            const observer = new IntersectionObserver(handleIntersection, observerOptions);
            observer.observe($element[0]);
        }

        const isOnScreen = function ($element) {
            const $window = $(window);
            const viewport = {
                top: $window.scrollTop(),
                bottom: $window.scrollTop() + $window.height()
            };
            const bounds = $element.offset();
            bounds.bottom = bounds.top + $element.outerHeight();
            return (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
        };

        const pushGA4 = function (eventName, slideIndex, slick) {
            const currentSlideElement = slick.$slides[slideIndex];
            //get get closest element by class .has-ga4-data
            const ga4Data = $(currentSlideElement).find('.has-ga4-data').first();
            const recommendationProducts = $(currentSlideElement).find('.block.related-products').first();
            let isShowRecommendation = ga4Data.data('show-recommend') === "show";
            // let recommendationId = ga4Data.data('recommendation-id') || false;

            if (ga4Data.length) {
                const creative_name = ga4Data.data('creative-name') || undefined;
                const creative_slot = ga4Data.data('creative-slot') || undefined;
                const promotion_id = ga4Data.data('promotion-id') || undefined;
                const promotion_name = ga4Data.data('promotion-name') || undefined;
                const item_list_id = ga4Data.data('item-list-id') || undefined;
                const item_list_name = ga4Data.data('item-list-name') || undefined;

                let promotionEcommerce = {creative_name, creative_slot, promotion_id, promotion_name};
                let productListEcommerce = {item_list_id, item_list_name, promotion_id, promotion_name};

                promotionEcommerce = JSON.parse(JSON.stringify(promotionEcommerce)); //remove undefined key
                productListEcommerce = JSON.parse(JSON.stringify(productListEcommerce)); //remove undefined key


                if (eventName === 'show_more_item') {
                    ga4push([{event: eventName, ecommerce: productListEcommerce}])
                } else {
                    if (Object.keys(promotionEcommerce).length) {
                        ga4push([{event: eventName, ecommerce: promotionEcommerce}])
                    } else {
                        console.log(`GA4 Event "${eventName}" is not pushed because of missing required parameters`);
                    }
                    if (recommendationProducts.length && isShowRecommendation && eventName === 'view_promotion') {
                        let ecommerce = {
                            ...productListEcommerce,
                            items: []
                        }

                        recommendationProducts.find('.ga4-item-json').each(function () {

                            const $productItemParent = $(this).closest('.product-item').first();
                            //check css display = none -> ignore

                            if ($productItemParent.css("display") === "none") {
                                return;
                            }

                            try {
                                var item = JSON.parse($(this).val());
                            } catch (error) {
                                console.error('Invalid JSON', $(this).val());
                                return;
                            }
                            ecommerce.items.push({
                                ...item,
                                item_id: parseInt(item.item_id),
                            });
                        });

                        if (ecommerce.items.length > 0) {
                            ga4push(
                                [{event: 'view_item_list', ecommerce}]
                            )
                        }
                    }
                }
            }
        }

        /**
         * Prevent each slick slider from being initialized more than once which could throw an error.
         */
        if ($element.hasClass('slick-initialized')) {
            $element.slick('unslick');
        }

        $element.on('init ', function(event, slick){
            if($element.hasClass('banner-slider')){
                var sliderHeight = 0;

                $element.find('.pagebuilder-slide-wrapper').each(function() {
                    var sliderHeightWrap = $(this).height();
                    if (sliderHeightWrap > sliderHeight) {
                        sliderHeight = sliderHeightWrap;
                    }
                });

                const $slideDots = $element.find('.slick-dots'),
                    $slideArrows = $element.find('.slick-arrow');
                
                $slideDots.css('top', sliderHeight)
                $slideArrows.css('top', sliderHeight/2)
            }

            //Init Event for VIP Banner Slider
            if($element.hasClass("home-vip-slider")){
                const vipSlides = $element.find("> .slick-list > .slick-track > .slick-slide");
                if(vipSlides.length < 1){
                    return;
                }

                vipSlides.each(function() {
                    const slideWrapper = $(this).find(".pagebuilder-slide-wrapper");
                    if (slideWrapper.length) {
                        const backgroundColor = slideWrapper.css("background-color");
                        const roundedCorner =
                            '<div class="rounded-corner" >' +
                            '<svg class="box-desc-rounder" viewBox="0 0 201 207" xmlns="http://www.w3.org/2000/svg">' +
                            '<path d="M201 0H0V207C10 86 99 6 201 0Z" fill="' + backgroundColor + '"></path>' +
                            '</svg></div>';

                        if(slideWrapper.find(".rounded-corner").length < 1) {
                            slideWrapper.append(roundedCorner);
                        }
                    }
                });

                vipSlides.bind("click", function(){
                    const index = $(this).index();
                    // console.log("Vip banner click: Index =", index);
                    // const rowProductMobileClass = "row-vip-product-slider-mobile";
                    const rowProductActiveClass = "row-vip-product-slider-active";

                    const oldSlider = $(this).parent().find(".banner-selected");
                    const rowEmpty = $(this).parents(".banner-item-content").find("." + rowProductActiveClass);
                    if(oldSlider && rowEmpty){
                        rowEmpty.append(oldSlider.find(".row-home-vip-product"));
                        rowEmpty.removeClass(rowProductActiveClass);
                    }

                    let windowWidth = $(window).width();
                    const productSliders = $(this).parents(".banner-item-content").find(" > div > .row-home-vip-product");

                    vipSlides.removeClass("banner-selected");
                    $(this).addClass("banner-selected");

                    productSliders.removeClass("active");
                    if(productSliders.length > index){
                        $(productSliders[index]).addClass("active");
                    }

                    if (windowWidth <= 1024) {
                        const slideContainer = $(this).find(".main-container");
                        if(slideContainer && slideContainer.find(".row-home-vip-product").length > 0){
                            return;
                        }

                        const rowSelect = $(productSliders[index]);
                        rowSelect.parent().addClass(rowProductActiveClass);
                        slideContainer.append($(productSliders[index]));

                        const slideWrapper = $(this).find(".pagebuilder-slide-wrapper");
                        const backgroundColor = slideWrapper.css("background-color");
                        slideContainer.css("background-color", backgroundColor);
                    }
                });

                $(vipSlides[0]).trigger("click");
            }


            if(isOnScreen($element) && isDisplay) {
                //after init push view promotion event for first slide
                pushGA4('view_promotion', 0, slick);
                window[$element.data('ga4-event-uid')] = true;
            }

            //Slide/Button on slide click event
            if (slick.$slides.length > 0) {
                slick.$slides.each(function(index, slide) {
                    // const $slideButtons = $(slide).find('.pagebuilder-slide-button');
                    // const $slideLinks = $(slide).find('a[data-element="link"]');
                    const recommendationProducts = $(slide).find('.block.related-products').first();
                    const $slideNormalLinks = $(slide).find('a'); //insert in editor

                    //slide button click event
                    // if($slideButtons.length > 0) {
                    //     $slideButtons.each(function (i, slideButton) {
                    //         const isBelongToRecommendation = $(slideButton).closest('.related-products')?.length > 0;
                    //
                    //         if (!isBelongToRecommendation) {
                    //             $(slideButton).on('click', function (e) {
                    //                 console.log('click slide button', e.target);
                    //                 pushGA4('select_promotion', index, slick);
                    //             });
                    //         }
                    //     });
                    // }
                    //slide links click event
                    // if($slideLinks.length > 0) {
                    //     $slideLinks.each(function (i, slideLink) {
                    //         const isBelongToRecommendation = $(slideLink).closest('.related-products')?.length > 0;
                    //
                    //         if (!isBelongToRecommendation) {
                    //             $(slideLink).on('click', function (e) {
                    //                 console.log('click slide link 3', e.target);
                    //                 pushGA4('select_promotion', index, slick);
                    //             });
                    //         }
                    //     });
                    // }
                    if($slideNormalLinks.length > 0) {
                        $slideNormalLinks.each(function (i, slideLink) {
                            const isBelongToRecommendation = $(slideLink).closest('.related-products')?.length > 0;

                            if (!isBelongToRecommendation) {
                                $(slideLink).on('click', function (e) {
                                     pushGA4('select_promotion', index, slick);
                                });
                            }
                        });
                    }

                    //recommendation products
                    if (recommendationProducts.length) {

                        //show more item click event
                        recommendationProducts.find('.pagebuilder-slide-button').on('click', function() {
                            pushGA4('show_more_item', index, slick);
                        });
                    }
                });
            }
        });

        $element.slick({
            autoplay: $element.data('autoplay'),
            autoplaySpeed: $element.data('autoplay-speed') || 0,
            fade: $element.data('fade'),
            infinite: $element.data('infinite-loop'),
            arrows: $element.data('show-arrows'),
            dots: $element.data('show-dots'),
            responsive: [
                { breakpoint: 640 },
                { breakpoint: 769 },
                { breakpoint: 1281 },
            ]
        });

        $element.on('breakpoint ', function(event, slick, breakpoint){
            if($element.hasClass('banner-slider')){
                const $slideHeight = $element.find('.pagebuilder-slide-wrapper').height(),
                    $slideDots = $element.find('.slick-dots'),
                    $slideArrows = $element.find('.slick-arrow');

                $slideDots.css('top', $slideHeight)
                $slideArrows.css('top', $slideHeight/2)
            }

            if($element.hasClass("home-vip-slider")) {
                const vipSlides = $element.find("> .slick-list > .slick-track > .slick-slide");
                $(vipSlides[0]).trigger("click");
            }

        });

        $element.on('afterChange', function(event, slick, currentSlide) {
            if(isOnScreen($element) && isDisplay) {
                pushGA4('view_promotion', currentSlide, slick);
                window[$element.data('ga4-event-uid')] = true;
            } else {
                window[$element.data('ga4-event-uid')] = false;
            }
        });


        // Redraw slide after content type gets redrawn
        events.on('contentType:redrawAfter', function (args) {
            if ($element.closest(args.element).length) {
                $element.slick('setPosition');
            }
        });
        // eslint-disable-next-line jquery-no-bind-unbind
        events.on('stage:viewportChangeAfter', $element.slick.bind($element, 'setPosition'));
    };
});
