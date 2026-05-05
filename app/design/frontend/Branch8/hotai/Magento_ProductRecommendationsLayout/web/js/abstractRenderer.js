define([
    "uiComponent",
    "dataServicesBase",
    "jquery",
    "Magento_Catalog/js/price-utils",
    "Branch8_GA4/js/actions/ga4push",
    'plugins/DOMPurify',
    'swiper',
    'Magento_PageBuilder/js/events',
    "slick",
    "slickSlider",
    "mage/translate",
    'domReady!'
], function (Component, ds, $, priceUnits, aga4push, DOMPurify, Swiper, events) {
    "use strict"
    return Component.extend({
        defaults: {
            template:
                "Magento_ProductRecommendationsLayout/recommendations.html",
            recs: [],
        },
        initialize: function (config) {
            this._super(config)
            this.pagePlacement = config.pagePlacement
            this.placeholderUrl = config.placeholderUrl
            this.priceFormat = config.priceFormat
            this.priceUnits = priceUnits
            this.currencyConfiguration = config.currencyConfiguration
            this.alternateEnvironmentId = config.alternateEnvironmentId
            this.productIdsPreOrder = config.productIdsPreOrder
            this.productIdsVip = config.productIdsVip
            this.imageWidth = config.imageWidth
            this.imageHeight = config.imageHeight
            this.showReloadButton = false;

            require(['Magento_Customer/js/customer-data'], function (customerData) {
                this._customerData = customerData;

                customerData.get('restrictedproductids').subscribe(function (restrictedData) {
                    if (restrictedData && restrictedData.ids && restrictedData.ids.length > 0) {
                        this.hideRestrictedProductsDynamic(restrictedData.ids);
                    }
                }.bind(this));

                // Also apply immediately if data is already available (from localStorage)
                var currentData = customerData.get('restrictedproductids')();
                if (currentData && currentData.ids && currentData.ids.length > 0) {
                    // Wait for KO render to finish before hiding
                    setTimeout(function () {
                        this.hideRestrictedProductsDynamic(currentData.ids);
                    }.bind(this), 1500);
                }
            }.bind(this));

            return this
        },
        /**
         * @returns {Element}
         */
        initObservable: function () {
            return this._super().observe(["recs"])
        },

        //Helper function to add addToCart button & convert currency
        /**
         *
         * @param {@} response is type Array.
         * @returns type Array.
         */
        processResponse(response) {
            const units = []
            if (!response.length || response[0].unitId === undefined) {
                return units
            }

            let restrictedIds = [];
            if (this._customerData) {
                const restrictedData = this._customerData.get('restrictedproductids')();
                if (restrictedData && restrictedData.ids) {
                    restrictedIds = restrictedData.ids.map(String);
                }
            }

            for (let i = 0; i < response.length; i++) {
                if (restrictedIds.length > 0) {
                    response[i].products = response[i].products.filter(function(product) {
                        return restrictedIds.indexOf(String(product.productId)) === -1;
                    });
                }

                response[i].products = response[i].products.slice(
                    0,
                    response[i].displayNumber,
                )
                for (let j = 0; j < response[i].products.length; j++) {
                    if (response[i].products[j].productId) {
                        const form_key = window.FORM_KEY
                        const productId = response[i].products[j].productId;
                        const product = response[i].products[j];
                        const url = this.createAddToCartUrl(
                            response[i].products[j].productId,
                        )
                        // Check if productId exists in productIdsPreOrder
                        response[i].products[j].isPreOrder = !!(productId && this.productIdsPreOrder.includes(productId));

                        response[i].products[j].isVip = !!(productId && this.productIdsVip.includes(productId));
                        if (response[i].products[j].image && response[i].products[j].image.url) {
                            const params = new URLSearchParams();
                            params.set('optimize', 'medium');
                            params.set('fit', 'bounds');
                            params.set('height', this.imageHeight || '372');
                            params.set('width', this.imageWidth || '372');
                            if(response[i].products[j].image.url.indexOf("?") !== -1) {
                                response[i].products[j].image.url += '&' + params.toString();
                            } else {
                                response[i].products[j].image.url += '?' + params.toString();
                            }
                        }

                        const postUenc = this.encodeUenc(url)
                        const addToCart = {form_key, url, postUenc}
                        response[i].products[j].addToCart = addToCart
                    }

                    if (
                        this.currencyConfiguration &&
                        response[i].products[j].currency !==
                            this.currencyConfiguration.currency
                    ) {
                        if (response[i].products[j].prices === null) {
                            response[i].products[j].prices = {
                                minimum: {final: null},
                            }
                        } else {
                            response[i].products[j].prices.minimum.final =
                                response[i].products[j].prices &&
                                response[i].products[j].prices.minimum &&
                                response[i].products[j].prices.minimum.final
                                    ? this.convertPrice(
                                          response[i].products[j].prices.minimum
                                              .final,
                                      )
                                    : null
                        }
                        response[i].products[j].currency =
                            this.currencyConfiguration.currency
                    }
                }
                units.push(response[i])
            }
            units.sort((a, b) => a.displayOrder - b.displayOrder)
            return units
        },



        loadJsAfterKoRender: function (self, unit) {

            const ecommerce = {
                promotion_id: unit.promotionId,
                promotion_name: unit.promotionName,
                item_list_id: unit.itemListId,
                item_list_name: unit.itemListName,
                items: []
            };

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


            const checkAndPushGA4Event = function ($element) {
                if (window[$element.data('ga4-event-uid')]) {
                    return;
                }

                ecommerce.items = [];
                $element.find('.ga4-item-json').each(function() {

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

                Object.keys(ecommerce).forEach(key => {
                    if (ecommerce[key] === undefined || ecommerce[key] === null || ecommerce[key] === '') {
                        delete ecommerce[key];
                    }
                });

                if (ecommerce?.items?.length) {
                    window.ga4Push([{event: 'view_item_list', ecommerce}]);
                }

                window[$element.data('ga4-event-uid')] = true
            }

            const renderEvent = new CustomEvent("render", {detail: unit})
            document.dispatchEvent(renderEvent)

            $(self).trigger('product_recommendations_rendered', {unit})

            const blockNames = [
                'product_recommendations_product_below_content',
                'product_recommendations_category_before_content',
                'product_recommendations_category_below_content',
            ]
            if (blockNames.includes(unit.blockName)) {
                const elements = self.filter(function (element) {
                    return $(element).hasClass('unit-element')
                })
                if(elements.length) {
                    const $element = $(elements[0])

                    console.log('@@@@@', $element)

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

                    $element.find('.product-items').on('init', function () {
                        if (isOnScreen($element)) {
                            checkAndPushGA4Event($element);
                            window[$element.data('ga4-event-uid')] = true
                        }
                    })

                    // if (isOnScreen($element)) {
                    //     checkAndPushGA4Event($element);
                    //     window[$element.data('ga4-event-uid')] = true
                    // }
                }
            }


            // Flagship PLP Landing Page
            // adding product count label
            const $flagShipLaningPage = $('.flagship.hot-items .product-items');
            if ($flagShipLaningPage.length) {
                $('.flagship.hot-items .product-item-info').each(function(index){
                    if(!$(this).find('.product-count-item').length) {
                        $(this).prepend("<span class='product-count-item "+(index === 0? 'first': index===1 ? 'second': index===2 ? 'three': '')+"'>"+(index+1)+"</span>");
                    }
                });
            }

            /**
             *
             * Build Slider Template
             */
            const $carouselElement = $('[data-content-type="product_recommendations"][data-appearance="carousel"] [data-unit-id="' + unit.unitId + '"] .product-items');

            if ($carouselElement.length) {
                $carouselElement.each(function () {
                    const $carouselItem = $(this);
                    const $productRecommendationsElement = $carouselItem.closest('[data-appearance="carousel"]'),
                        carouselConfigData = $productRecommendationsElement.data(),
                        slickParams = {
                            desktop: {
                                slidesToShow: $.isNumeric(carouselConfigData.slidetoshowDesktop) ? carouselConfigData.slidetoshowDesktop : 6,
                                centerMode: carouselConfigData.carouselMode === 'continuous' || carouselConfigData.slidetoshowDesktop === 'continuous',
                                variableWidth: carouselConfigData.carouselMode === 'variableWidth' || carouselConfigData.slidetoshowDesktop === 'variableWidth'
                            },
                            tablet: {
                                slidesToShow: $.isNumeric(carouselConfigData.slidetoshowTablet) ? carouselConfigData.slidetoshowTablet : (carouselConfigData.slidetoshowTablet === 'continuous' ? 1 : 4),
                                centerMode: carouselConfigData.carouselMode === 'continuous' || carouselConfigData.slidetoshowTablet === 'continuous',
                                variableWidth: carouselConfigData.carouselMode === 'variableWidth' ||carouselConfigData.slidetoshowTablet === 'variableWidth'
                            },
                            mobile: {
                                slidesToShow: $.isNumeric(carouselConfigData.slidetoshowMobile) ? carouselConfigData.slidetoshowMobile : (carouselConfigData.slidetoshowMobile === 'continuous' ? 1 : 2),
                                centerMode: carouselConfigData.carouselMode === 'continuous' || carouselConfigData.slidetoshowMobile === 'continuous',
                                variableWidth: true,
                                infinite: true
                            }
                        };

                    const slickConfig = {
                        dots: carouselConfigData.showDots,
                        arrows: carouselConfigData.showArrows,
                        infinite: carouselConfigData.infiniteLoop || slickParams.desktop.centerMode || slickParams.desktop.variableWidth,
                        autoplay: carouselConfigData.autoplay,
                        autoplaySpeed: carouselConfigData.autoplaySpeed,
                        centerMode: slickParams.desktop.centerMode && unit.products.length > slickParams.desktop.slidesToShow,
                        centerPadding: carouselConfigData.centerPadding ? carouselConfigData.centerPadding : '95px',
                        slidesToShow: slickParams.desktop.slidesToShow,
                        slidesToScroll: slickParams.desktop.slidesToShow,
                        variableWidth: slickParams.desktop.variableWidth,
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: carouselConfigData.slidetoshowTablet === "unSlider" ? "unslick" : {
                                    centerMode: slickParams.tablet.centerMode && unit.products.length > slickParams.tablet.slidesToShow,
                                    infinite: carouselConfigData.infiniteLoop || slickParams.tablet.centerMode || slickParams.tablet.variableWidth,
                                    variableWidth: slickParams.tablet.variableWidth,
                                    slidesToShow: slickParams.tablet.slidesToShow,
                                    slidesToScroll: slickParams.tablet.slidesToShow,
                                }
                            },
                            {
                                breakpoint: 769,
                                settings: carouselConfigData.slidetoshowMobile === "unSlider" ? "unslick" : {
                                    centerMode: slickParams.mobile.centerMode && unit.products.length > slickParams.mobile.slidesToShow,
                                    infinite: carouselConfigData.infiniteLoop || slickParams.mobile.centerMode || slickParams.mobile.variableWidth,
                                    variableWidth: slickParams.mobile.variableWidth,
                                    slidesToShow: slickParams.mobile.slidesToShow,
                                    slidesToScroll: slickParams.mobile.slidesToShow
                                }
                            }
                        ]
                    }
                    if($('body').hasClass('page-layout-category-official-page')){
                        // apply for offical category landing page
                        // let slickConfigCustom = {
                        //     arrows: slickConfig.arrows,
                        //     // infinite: slickConfig.infinite,
                        //     infinite: true,
                        //     autoplay: slickConfig.autoplay,
                        //     slidesToShow: 3,
                        //     slidesToScroll: 3,
                        //     variableWidth: true,
                        //     responsive: [
                        //         {
                        //             breakpoint: 1280,
                        //             settings: {
                        //                 slidesToShow: 2,
                        //                 slidesToScroll: 2,
                        //                 variableWidth: true,
                        //             }
                        //         },
                        //         {
                        //             breakpoint: 768,
                        //             settings: {
                        //                 variableWidth: true,
                        //                 slidesToShow: 2,
                        //                 slidesToScroll: 2
                        //             }
                        //         }
                        //     ]
                        // };
                        // if($carouselItem.parents('.official.hot-items-row').length) {
                        //     slickConfigCustom = {
                        //         arrows: slickConfig.arrows,
                        //         // infinite: slickConfig.infinite,
                        //         infinite: true,
                        //         autoplay: slickConfig.autoplay,
                        //         slidesToShow: 4,
                        //         slidesToScroll: 4,
                        //         variableWidth: true,
                        //         centerMode: true,
                        //         responsive: [
                        //             {
                        //                 breakpoint: 1280,
                        //                 settings: {
                        //                     slidesToShow: 3,
                        //                     slidesToScroll: 3,
                        //                     variableWidth: true,
                        //                 }
                        //             },
                        //             {
                        //                 breakpoint: 768,
                        //                 settings: {
                        //                     variableWidth: true,
                        //                     slidesToShow: 1,
                        //                     slidesToScroll: 1
                        //                 }
                        //             }
                        //         ]
                        //     };
                        // }
                        // if (!$carouselItem.hasClass('slick-initialized')) {
                        //     $carouselItem.slick(slickConfigCustom);
                        // }


                    } else if($('body').hasClass('page-layout-category-flagship-page')) {
                         // apply for flagship category landing page
                        //  let slickConfigCustom = {
                        //     arrows: slickConfig.arrows,
                        //     // infinite: slickConfig.infinite,
                        //     infinite: true,
                        //     autoplay: slickConfig.autoplay,
                        //     slidesToShow: 6,
                        //     slidesToScroll:6,
                        //     variableWidth: true,
                        //     responsive: [
                        //         {
                        //             breakpoint: 1024,
                        //             settings: {
                        //                 slidesToShow: 4,
                        //                 slidesToScroll: 4,
                        //                 variableWidth: true,
                        //             }
                        //         },
                        //         {
                        //             breakpoint: 768,
                        //             settings: {
                        //                 variableWidth: true,
                        //                 slidesToShow: 2,
                        //                 slidesToScroll: 2
                        //             }
                        //         }
                        //     ]
                        // };
                        // if (!$carouselItem.hasClass('slick-initialized')) {
                        //     $carouselItem.slick(slickConfigCustom);
                        // }
                    } else {
                        // if (!$carouselItem.hasClass('slick-initialized')) {
                        //     $carouselItem.slick(slickConfig);
                        // }
                    }
                    $carouselItem.on('init', function(){
                        if($('.sub-cats-tabs-row').length > 0){
                            $('.sub-cats-tabs-row').slideUpSticky();
                        }
                        if($('.category-list-title').length > 0){
                            $('.category-list-title').slideUpSticky();
                        }
                    });

                    if($carouselElement.parents('.products-swiper').length){
                        new Swiper('[data-content-type="product_recommendations"][data-appearance="carousel"] [data-unit-id="' + unit.unitId + '"] .products.wrapper', {
                            wrapperClass: 'product-items',
                            slideClass: 'product-item',
                            slidesPerView: 'auto',
                            centeredSlides: slickConfig.centerMode,
                            autoplay: slickConfig.autoplay ? {
                                delay: slickConfig.autoplaySpeed,
                            } : false,
                            loop: slickConfig.infinite,
                            spaceBetween: 0,
                            pagination: slickConfig.dots ? {
                                el: '.swiper-pagination',
                                type: 'bullets',
                            } : false,
                            navigation: slickConfig.arrows ? {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            } : false,

                            //add swiper-slide-visible class to slides are visible
                            watchSlidesProgress: true,
                            slideVisibleClass: 'swiper-slide-visible',
                            on: {
                                slideChange: function () {
                                    console.log('slideChange');
                                    checkAndPushGA4Event($carouselElement.parents('.products-swiper').first())
                                },
                                afterInit: function () {
                                    const $element = $carouselElement.parents('.unit-element').first()
                                    if (isOnScreen($element)) {
                                        checkAndPushGA4Event($element);
                                        window[$element.data('ga4-event-uid')] = true
                                    }
                                }
                            },
                        });
                    } else {
                        if (!$carouselItem.hasClass('slick-initialized')) {
                            $carouselItem.slick(slickConfig);
                        }
                    }
                });
                // Redraw slide after content type gets redrawn
                events.on('contentType:redrawAfter', function (args) {
                    if ($carouselElement.closest(args.element).length) {
                        $carouselElement.slick('setPosition');
                    }
                });

                events.on('stage:viewportChangeAfter', function () {
                    $carouselItem.slick(slickConfig);
                });
            }else{
                if($('.sub-cats-tabs-row').length > 0){
                    $('.sub-cats-tabs-row').slideUpSticky();
                }
                if($('.category-list-title').length > 0){
                    $('.category-list-title').slideUpSticky();
                }
            }


            // Carousel product recommendations below main content
            const $categoryCarouselRecommendations = $('.product-recommendations-below-main-content .block-products-list .product-items');
            const pageType = $('body').hasClass('page-products') ? 'category' : '';
            if($categoryCarouselRecommendations.length){
                $categoryCarouselRecommendations.each(function(){
                    const $recommendationsItem = $(this);
                    if (!$recommendationsItem.hasClass('slick-initialized')) {
                        $recommendationsItem.slickSlider({
                            template: pageType === 'category' ? 'slick4Products' : 'slick6Products'
                        });
                    }
                });
            }

            // Add Quickview
            var jQ = $.noConflict();
            const quickviewEnabled = DOMPurify.sanitize(jQ('#quickview_data').attr('data-quickview-enabled')),
            quickviewProductUrl = DOMPurify.sanitize(jQ('#quickview_data').attr('data-quickview-product-url')),
            quickviewProductItemInfo = DOMPurify.sanitize('.' + jQ('#quickview_data').attr('data-product-item-info')),
            quickviewProductImageWrapper = DOMPurify.sanitize('.widget.block-products-list .' + jQ('#quickview_data').attr('data-product-image-wrapper')),
            quickviewButtonText = DOMPurify.sanitize(jQ('#quickview_data').attr('data-button-text'));
            var id_product;

            if (quickviewEnabled == 1) {
                jQ(quickviewProductImageWrapper).each(
                    function () {
                        if (jQ(this).parents('.product-item-photo-oos').length || jQ(this).hasClass('product-item-photo-oos')) {
                            return;
                        }
                        if (!jQ(this).parents(quickviewProductItemInfo).find('.magetop-bt-quickview').length) {
                            if (jQ(this).parents(quickviewProductItemInfo).find('.actions-primary input[name="product"]').val() != '') {
                                id_product = DOMPurify.sanitize(jQ(this).parents(quickviewProductItemInfo).find('.actions-primary input[name="product"]').val());
                            }
                            if (!id_product) {
                                id_product = DOMPurify.sanitize(jQ(this).parents(quickviewProductItemInfo).find('.price-box').data('product-id'));
                            }
                            if(jQ('body').hasClass('page-layout-category-official-page')) {
                                if (id_product) {
                                    jQ(this).parents('.product-item-info').find('.product-item-photo').prepend('<div id="quickview-' + id_product + '" class="magetop-bt-quickview quickview-product-recommendation"><a class="magetop-quickview" data-quickview-url="' + quickviewProductUrl + 'id/' + id_product + '" rel="nofollow" href="javascript:void(0);" ><span>' + quickviewButtonText + '</span></a></div>');
                                }
                            } else {
                                if (id_product) {
                                    jQ(this).prepend('<div id="quickview-' + id_product + '" class="magetop-bt-quickview quickview-product-recommendation"><a class="magetop-quickview" data-quickview-url="' + quickviewProductUrl + 'id/' + id_product + '" rel="nofollow" href="javascript:void(0);" ><span>' + quickviewButtonText + '</span></a></div>');
                                }
                            }
                        }
                    }
                )
            }

            // For test
            if(!$("#rec_reload").length)
                $('.product-recommendations-below-main-content').append("<span id='rec_reload' style='display: none;'>Reload</span>");


            if(unit.products.length >= unit.displayNumber){
                $('.reload-recommendations').show();
            }

            $(document).on('click', '.reload-recommendations',  function(){
                var reloadEle = $(this);
                reloadEle.find('button').addClass('loading');
                setTimeout(function(){
                    reloadEle.find('button').removeClass('loading');
                }, 3000);
            });


            // showing product slider in mobile view
            const $catDefaultHotItemsListing = $('.cat-default-hot-items-listing .product-items');
            if ($catDefaultHotItemsListing.length) {
                $('.cat-default-hot-items-listing .product-items .product-item-info').each(function(index){
                    if(!$(this).find('.product-count-item').length) {
                        $(this).prepend("<span class='product-count-item "+(index === 0? 'first': index===1 ? 'second': '')+"'>"+(index+2)+"</span>");
                    }
                });
            }

            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    if ($catDefaultHotItemsListing.length) {
                        let slickConfigCustom = {
                            arrows: true,
                            infinite: true,
                            autoplay: false,
                            slidesToShow: 2,
                            slidesToScroll:2,
                            variableWidth: true
                        };

                        if (!$catDefaultHotItemsListing.hasClass('slick-initialized')) {
                            $catDefaultHotItemsListing.slick(slickConfigCustom);
                        }
                    }
                }, this),
                exit: $.proxy(function () {
                    if ($catDefaultHotItemsListing.length && $catDefaultHotItemsListing.hasClass('slick-initialized')) {
                        $catDefaultHotItemsListing.slick('unslick');
                        $catDefaultHotItemsListing.removeClass('slick-initialized');
                    }
                }, this),
            });
        },

        convertPrice: function (price) {
            return parseFloat(price * this.currencyConfiguration.rate)
        },

        createAddToCartUrl(productId) {
            const currentLocationUENC = encodeURIComponent(
                this.encodeUenc(BASE_URL),
            )
            const postUrl =
                BASE_URL +
                "checkout/cart/add/uenc/" +
                currentLocationUENC +
                "/product/" +
                productId
            return postUrl
        },

        encodeUenc: function (value) {
            const regex = /=/gi
            return btoa(value).replace(regex, ",")
        },

        generateUID: (length) => {
            let result = '';
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            const charactersLength = characters.length;
            let counter = 0;
            while (counter < length) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
                counter += 1;
            }
            return result;
        },

        hideRestrictedProductsDynamic: function(restrictedIds) {
            restrictedIds = restrictedIds.map(String);

            var getProductIdFromItem = function($item) {
                // 1. data-product-id attribute trên chính thẻ li
                var id = $item.attr('data-product-id');
                if (id) return String(id);

                // 2. input[name="product"] (chỉ có khi addToCartAllowed = true)
                id = $item.find('input[name="product"]').val();
                if (id) return String(id);

                // 3. ga4-item-json: parse JSON để lấy item_id
                try {
                    var ga4raw = $item.find('.ga4-item-json').val();
                    if (ga4raw) {
                        var ga4data = JSON.parse(ga4raw);
                        if (ga4data && ga4data.item_id) return String(ga4data.item_id);
                    }
                } catch(e) {}

                return null;
            };

            var hasHidden = false;
            // Iterate all product items within Product Recommendations
            $('[data-content-type="product_recommendations"] .product-item, .product-recommendations-below-main-content .product-item, .product-recommendations-product-below-content .product-item, .product-recommendations-category-before-content .product-item, .unit-element .product-item').each(function() {
                var $item = $(this);
                if ($item.hasClass('slick-cloned')) return; // ignore cloned, we handle them via slick

                var id_product = getProductIdFromItem($item);

                if (id_product && restrictedIds.indexOf(id_product) !== -1) {
                    if (!$item.hasClass('restricted-hidden')) {
                        $item.addClass('restricted-hidden');
                        hasHidden = true;
                    }
                }
            });

            if (hasHidden) {
                 // For slick sliders:
                 var $carousels = $('[data-content-type="product_recommendations"] .slick-initialized, .product-recommendations-below-main-content .slick-initialized, .unit-element .slick-initialized');
                 
                    $carousels.each(function() {
                    try {
                        var $carousel = $(this);
                        $carousel.slick('slickFilter', function(){
                            var id = $(this).attr('data-product-id');
                            if (!id) id = $(this).find('input[name="product"]').val();
                            if (!id) {
                                try {
                                    var ga4raw = $(this).find('.ga4-item-json').val();
                                    if (ga4raw) { var d = JSON.parse(ga4raw); id = d && d.item_id ? String(d.item_id) : null; }
                                } catch(e) {}
                            }
                            return !(id && restrictedIds.indexOf(String(id)) !== -1);
                        });
                    } catch(e) {
                        console.error('Error applying slickFilter', e);
                    }
                 });

                 // For swiper sliders:
                 var $swipers = $('.products-swiper .swiper-initialized, .unit-element .swiper-initialized, [data-content-type="product_recommendations"] .swiper-initialized');
                 $swipers.each(function() {
                    try {
                        var swiper = this.swiper || $(this)[0].swiper;
                        if (swiper && swiper.slides) {
                            var slidesToRemove = [];
                            for (var i = 0; i < swiper.slides.length; i++) {
                                var slide = swiper.slides[i];
                                var id = $(slide).attr('data-product-id');
                                if (!id) id = $(slide).find('input[name="product"]').val();
                                if (!id) {
                                    try {
                                        var ga4raw = $(slide).find('.ga4-item-json').val();
                                        if (ga4raw) { var d = JSON.parse(ga4raw); id = d && d.item_id ? String(d.item_id) : null; }
                                    } catch(e2) {}
                                }
                                if (id && restrictedIds.indexOf(String(id)) !== -1) {
                                    slidesToRemove.push(i);
                                }
                            }
                            if (slidesToRemove.length > 0) {
                                slidesToRemove.reverse();
                                swiper.removeSlide(slidesToRemove);
                                swiper.update();
                            }
                        }
                    } catch(e) {
                        console.error('Error removing Swiper slides', e);
                    }
                 });
                 
                 // For items that are not in sliders (just normal layout):
                 $('.restricted-hidden').hide();
            }
        },
    })
})
