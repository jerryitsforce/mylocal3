define([
    'jquery',
    "Branch8_GA4/js/actions/ga4push",
    'plugins/DOMPurify',
    'matchMedia',
    'mage/mage',
    'slickSlider',
    'slideUpSticky',
    'matchMedia',
    'domReady!'
], function ($, ga4push, DOMPurify) {
    'use strict';
    $.widget(
        'b8.categoryCustom',
        {
            options: {
                ajaxUrl: '',
                widgetSettings: {}
            },

            _create: function () {
                'use strict';
                
                // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
                // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
                window.DOMPurify = DOMPurify;
                function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
                var createDOMPurify = require("dompurify");
                createDOMPurify(window);

                var self = this;
                const anchor = DOMPurify.sanitize(window.location.hash, { USE_PROFILES: { html: true } }),
                    isValid = $.mage.isValidSelector(anchor),
                    anchorId = anchor.replace("#", "");
                const $widget = $(".category_list_widget");

                // Initial load for active tab or hash
                var initialCategory = anchor && isValid ? anchorId : null;
                if (!initialCategory) {
                    var firstTab = $('.category-list-title-item > a.active');
                    if (firstTab.length) {
                        initialCategory = firstTab.attr('href').replace('#', '');
                    }
                }

                if (initialCategory) {
                    var initialBlock = $('#' + initialCategory);
                    if (initialBlock.length) {
                        this._loadCategory(initialBlock.data('category-id'), initialBlock);
                    }
                }

                if (anchor && isValid) {
                    $('.category-list-title-item > a').removeClass('active');
                    var targetElement = document.getElementById(anchorId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: "smooth" });
                    }
                    $('.category-list-title-item a').filter(function () {
                        return this.getAttribute('href') === anchor;
                    }).addClass('active');
                }

                $('.category-list-title-item > a').on('click', function (event) {
                    event.preventDefault();

                    // Update active link state
                    $('.category-list-title-item > a').removeClass('active');
                    $(this).addClass('active');

                    // Get target element ID from href
                    const anchorKey = $(this).attr("href").replace("#", "");
                    const element = document.getElementById(anchorKey);
                    if (!element) return; // safety check

                    // Load category if not loaded
                    var $block = $(element);
                    self._loadCategory($block.data('category-id'), $block);

                    // Calculate fixed header height if present
                    const headerHeight = $("header").outerHeight() || 0;
                    const titleHeight = $(".category-list-title").outerHeight() || 0;

                    let overlapHeight = 0;
                    const pageYOffset = window.pageYOffset;
                    const eleTop = element.getBoundingClientRect().top;

                    // Add header height if scrolled past the element
                    if (eleTop < 0) {
                        overlapHeight += headerHeight;
                    }

                    // Add sticky title height if title is pinned/unpinned
                    if ($('.category-list-title').hasClass('headroom--unpinned') || ($('.category-list-title').hasClass('headroom--pinned') && !$('.category-list-title').hasClass('headroom--top'))) {
                        overlapHeight += titleHeight;
                    }

                    if (($('.category-list-title').hasClass('headroom--pinned') && $('.category-list-title').hasClass('headroom--top')) || (!$('.category-list-title').hasClass('headroom--unpinned') && !$('.category-list-title').hasClass('headroom--pinned') && $('.category-list-title').hasClass('headroom--top'))) {
                        const element = document.querySelector('.category-list-title');
                        var marginBottomValue = 0;
                        if (element) {
                            marginBottomValue = parseFloat(window.getComputedStyle(element).marginBottom);
                        }
                        overlapHeight += 2 * titleHeight + marginBottomValue;
                    }

                    // Scroll with offset
                    const y = eleTop + pageYOffset - overlapHeight;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                });


                mediaCheck({
                    media: "(max-width: 768px)",
                    entry: $.proxy(function () {
                        if (!$('[data-content-type="product_recommendations"] .block-static-block').length > 0) {
                            if ($('[data-appearance="carousel"] .product-items').length) {
                                $('.product-items').on('init', function (event, slick) {
                                    $('.category-list-title').slideUpSticky();
                                });
                            } else {
                                $(".category-list-title").slideUpSticky();
                            }
                        }

                        var pageHeader = document.querySelector("header"),
                            headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
                        if ($('.page-header').hasClass('headroom--pinned')) {
                            $(".category-list-title").css("top", headerHeight + 'px');
                        }
                    }, this),
                    exit: $.proxy(function () {
                        if (!$('[data-content-type="product_recommendations"] .block-static-block').length > 0) {
                            if ($('[data-appearance="carousel"] .product-items').length) {
                                $('.product-items').on('init', function (event, slick) {
                                    $('.category-list-title').slideUpSticky();
                                });
                            } else {
                                $(".category-list-title").slideUpSticky();
                            }
                        }
                        var pageHeader = document.querySelector("header"),
                            headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
                        if ($('.page-header').hasClass('headroom--pinned')) {
                            $(".category-list-title").css("top", headerHeight + 'px');
                        }
                    }, this),
                });

                function throttle(fn, wait) {
                    let lastTime = 0;
                    return function (...args) {
                        const now = new Date().getTime();
                        if (now - lastTime >= wait) {
                            fn.apply(this, args);
                            lastTime = now;
                        }
                    };
                }

                const handleScroll = () => {
                    let pageYOffset = window.pageYOffset;
                    let overlapHeight = $(".category-list-title").outerHeight();
                    let sections = document.querySelectorAll(".category-block");
                    let current;

                    sections.forEach((section) => {
                        const sectionTop = section.offsetTop;
                        if ($('.page-header').hasClass('headroom--pinned')) {
                            const pageHeader = document.querySelector("header");
                            const headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
                            $(".category-list-title").css("top", headerHeight + 'px');
                            overlapHeight += headerHeight;
                        }

                        if (pageYOffset > (sectionTop - (window.innerHeight / 2))) {
                            // Lazy load on scroll
                            self._loadCategory($(section).data('category-id'), $(section));
                        }

                        if (pageYOffset > (sectionTop + $(section).outerHeight() - ($(section).find('.product-item-details').outerHeight() || 100) - overlapHeight)) {
                            var nextEle = $(section).next();
                            if (nextEle.length) {
                                current = nextEle.attr("id");
                            }
                        }
                    });

                    if ($(".category-list-title").hasClass("headroom--top")) {
                        $(".category-list-title-item > a").removeClass("active");
                        $(".category-list-title-item:first-child > a").addClass("active");
                    } else {
                        $(".category-list-title-item").each(function () {
                            if ($(this).find("a").attr("href").replace("#", "") === current) {
                                $(".category-list-title-item > a").removeClass("active");
                                $(this).find("a").addClass("active");
                            }
                        });
                    }
                };

                // Attach throttled scroll handler
                window.addEventListener("scroll", throttle(handleScroll, 100));

                // Initial scroll check to load visible blocks
                handleScroll();

                function buildThresholdList(numSteps) {
                    let thresholds = [0.0];

                    for (let i = 1; i <= numSteps; i++) {
                        let ratio = i / numSteps;
                        thresholds.push(ratio);
                    }

                    thresholds.push(1.0);
                    return thresholds;
                }

                function isOnScreen($element) {
                    const $window = $(window);
                    const viewport = {
                        top: $window.scrollTop(),
                        bottom: $window.scrollTop() + $window.height(),
                    };
                    const bounds = $element.offset();
                    if (!bounds) return false;
                    bounds.bottom = bounds.top + $element.outerHeight();
                    return !(viewport.bottom < bounds.top || viewport.top > bounds.bottom);
                }

                const checkAndPushGA4Event = function ($element, eventData) {
                    if (window[$element.data('ga4-event-uid')]) {
                        return;
                    }

                    if (eventData.ecommerce.items.length > 0) {
                        ga4push([eventData]);
                        window[$element.data('ga4-event-uid')] = true
                    }
                }

                const getViewItemListEventData = function ($element) {
                    let ecommerce = {
                        item_list_id: $element.data("item-list-id") || undefined,
                        item_list_name: $element.data("item-list-name") || undefined,
                    };
                    ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key
                    ecommerce.items = [];

                    $element.find(".ga4-item-json").each(function () {

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

                        const item = JSON.parse($(this).val());

                        Object.keys(item).forEach((key) => {
                            if (item[key] === undefined || item[key] === null || item[key] === "") {
                                delete item[key];
                            }
                        });
                        ecommerce.items.push({
                            ...item,
                            item_id: parseInt(item.item_id),
                        });;
                    });

                    return {
                        event: 'view_item_list',
                        ecommerce
                    }
                }

                const handleIntersection = (entries, observer) => {
                    entries.forEach((entry) => {
                        let isIntersecting = entry.isIntersecting
                        let $element = $(entry.target);


                        if (isIntersecting) {
                            // Also trigger load if not loaded
                            self._loadCategory($element.data('category-id'), $element);
                            checkAndPushGA4Event($element, getViewItemListEventData($element));
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
                const observer = new IntersectionObserver(handleIntersection, observerOptions);


                $widget.find(".category-block").each(function () {
                    let $element = $(this);

                    window[$element.data('ga4-event-uid')] = false

                    if (isOnScreen($element)) {
                        // self._loadCategory($element.data('category-id'), $element); // handleScroll already does this
                        ga4push([getViewItemListEventData($element)]);
                        window[$element.data('ga4-event-uid')] = true
                    }

                    observer.observe($element[0]);
                });


                $widget.on("click", ".view-all", function () {
                    const $parent = $(this).closest(".category-block");
                    let ecommerce = {
                        item_list_id: $parent.data("item-list-id") || undefined,
                        item_list_name: $parent.data("item-list-name") || undefined,
                    };
                    ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key
                    const eventData = {
                        event: 'show_more_item',
                        ecommerce
                    }
                    ga4push([eventData]);

                })
            },

            _loadCategory: function (categoryId, $block) {
                var self = this;
                if ($block.data('loaded') || $block.data('loading')) {
                    return;
                }

                $block.data('loading', true);
                $block.find('.ajax-loader').show();

                var params = $.extend({}, this.options.widgetSettings, {
                    category_id: categoryId
                });

                $.ajax({
                    url: this.options.ajaxUrl,
                    data: params,
                    type: 'GET',
                    success: function (response) {
                        if (response.html) {
                            const sanitizedData = DOMPurify.sanitize(response.html, {
                                USE_PROFILES: { html: true },
                                ADD_TAGS: ['form', 'input', 'button', 'select', 'textarea', 'option'],
                                ADD_ATTR: ['data-mage-init', 'data-bind', 'data-role', 'action', 'method', 'type', 'name', 'value']
                            });
                            $block.find('.category-products-wrapper').html(sanitizedData);
                            $block.data('loaded', true);

                            // Initialize slick slider
                            $block.find('[data-action="widget-slider-custom"] .product-items').each(function () {
                                $(this).slickSlider({
                                    template: "slick6Products",
                                });
                            });

                            // Re-apply Magento JS and trigger events
                            $('body').trigger('contentUpdated');

                            // Push GA4 event after load if it's on screen
                            // (IntersectionObserver might have already triggered but with no items)
                        }
                    },
                    error: function () {
                        console.error("Failed to load category products for ID: " + categoryId);
                    },
                    complete: function () {
                        $block.data('loading', false);
                        $block.find('.ajax-loader').hide();
                    }
                });
            }
        });
    return $.b8.categoryCustom;
});
