/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "jquery",
    "mage/smart-keyboard-handler",
    "mage/translate",
    "plugins/headroom",
    'Magento_PageBuilder/js/events',
    'plugins/DOMPurify',
    'mage/url',
    "Magento_Customer/js/customer-data",
    "slideUpSticky",
    "plugins/stickykit",
    "slick",
    "matchMedia",
    "mage/mage",
    "mage/dropdowns",
    "domReady!",
], function ($, keyboardHandler, _, Headroom, events, DOMPurify, urlBuilder, customerData) {
    "use strict";

    if (typeof Theme == "undefined") {
        var Theme = {};
    }

    Theme.Scripts = {
        init: function () {
            this.headerHeadroom();
            this.backToTop();
            this.clearSearchInput();
            this.openSearch();
            this.accordionFooter();
            this.recentlyViewedBlockMobile();
            // this.productPointBlock();
            this.popularCategorySectionViewMore();
            this.additionalHeadroom();
            this.accordionCartSidebar();
            // this.stickyCartSidebar();
            this.cmsBlockOrderThingsToNote();
            this.navState();
            this.customerDropdown();

            // 移除頁面 loading 效果
            this.removePageLoading()
            this.removeStoreData();

            // moving inline script to file (optimize home page)
            this.storeCatalogBreadcrumbs();
            this.clearCouponInput(); 

            this.searchForm();
        },

        isSafari: function () {
            return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        },

        removePageLoading: function(){
            $('body').addClass('page-loaded');
        },

        headerHeadroom: function () {
            // Header sticky
            var pageHeader = document.querySelector("header");
            if (!pageHeader)
                return;
            var headerHeight = pageHeader.offsetHeight;
            var headroom = new Headroom(pageHeader, {
                offset: headerHeight/2,
                onPin: function () {
                    if ($('.product.media').length) {
                        $('.product.media').trigger('sticky_kit:refresh', [{offset_top: pageHeader.offsetHeight}]);
                    }
                },
                onUnpin: function () {
                    if ($('.product.media').length) {
                        $('.product.media').trigger('sticky_kit:refresh', [{offset_top: 0}]);
                    }
                }
            });
            headroom.init();

            // home page search block content class - bg brand primary
            if($('body').hasClass('cms-index-index')) {
                $('.block-search .block-content').addClass('bg-brand-primary');
            }
        
            // Move header nav (header-right) out header page - Mobile
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    $('.header-right').insertAfter('.page-header');
                }, this),
                exit: $.proxy(function () {

                    if($('body').hasClass('checkout-index-index')) {
                        if (!$('.header.content .header-right').length) {
                            $('.header-right').insertAfter('#header-hotaipoints');
                        }
                    } else {
                        if (!$('.header.content .header-right').length) {
                            $('.header-right').insertBefore('.header.content .block-search');
                        }
                    }

                }, this),
            });
        },

        additionalHeadroom: function () {
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    if ($("#livesearch_root").length) {
                        var timer = setInterval(function () {
                            if ($('.ds-widgets-header-wrapper').length) {
                                $('.ds-widgets-header-wrapper').slideUpSticky();
                                clearInterval(timer);
                            }
                        }, 1000);
                    }

                }, this),
                exit: $.proxy(function () {
                }, this),
            });
        },

        toTopButtonDisplay: function () {
            const wHeight = $(window).height();
            const toTopBtn = $("#back-to-top");

            if ($(window).scrollTop() > wHeight / 2) {
                toTopBtn.fadeIn();
            } else {
                toTopBtn.fadeOut();
            }
        },

        backToTop: function () {
            // Back to top
            var self = this;

            $(document).ready(function() {
                self.toTopButtonDisplay();

                $(window).on("scroll", function() {
                    self.toTopButtonDisplay();
                });
            });

            $(document).on("click", "#back-to-top", function () {
                $("html,body").animate(
                    {
                        scrollTop: 0,
                    },
                    700
                );
            });
        },

        clearSearchInput: function () {
            // Block Search - Clear action
            var $searchInput = $("#search"),
                $searchBlock = $("#search").closest(".block-search");
            if($searchInput.val()){
                $searchBlock.addClass("typing");
            }

            mediaCheck({
                media: "(max-width: 768px)",
                entry: function () {
                    // INPUT
                    $searchInput.on("input.search", function () {
                        var val = $(this).val(),
                            html = $('html');

                        if (val) {
                            $searchBlock.addClass("typing");

                            if (html.hasClass('nav-before-open')) {
                                html.addClass('nav-search-typing');
                            }
                        } else {
                            $searchBlock.removeClass("typing");

                            if (html.hasClass('nav-before-open')) {
                                html.removeClass('nav-search-typing');
                            }
                        }
                    });

                    // FOCUS
                    $searchInput.on("focusin.search", function () {
                        $('html').addClass('--with-search-suggestion');
                    });

                    // CLICK
                    $searchInput.on("click.search", function () {
                        if ($('#search_autocomplete').find('.livesearch.popover-container').length) {
                            $('html').addClass('--with-search-suggestion');
                        }
                    });

                    // BLUR
                    $searchInput.on("focusout.search", function () {
                        $('html').removeClass('--with-search-suggestion');
                    });
                },

                exit: function () {
                    // 🔥 clean everything when leaving mobile
                    $searchInput.off('.search');
                    $(document).off('.search');

                    $('html').removeClass('--with-search-suggestion nav-search-typing');
                    $searchBlock.removeClass("typing");
                }
            });

            $(document).on("click", ".block-search .action.clear", function () {
                var html = $('html')
                $searchInput.val("");
                $searchBlock.removeClass("typing");
                if(html.hasClass('nav-before-open')){
                    html.removeClass('nav-search-typing');
                }
            });
        },

        openSearch: function () {
            // Open search - Mobile
            var $searchInput = $("#search"),
                $searchBlock = $("#search").closest(".block-search");
            $('.block-search .block-title').on('click', function () {
                var html = $('html');
                if (html.hasClass('search-open')) {
                    html.removeClass('search-open');
                    setTimeout(function () {
                        html.removeClass('search-before-open');
                    }, 200);
                    window.ReactNativeWebView?.postMessage(
                        JSON.stringify({
                            type: 'SEARCH_CLOSED',
                            data: ''
                        })
                    );
                } else {
                    html.addClass('search-before-open');
                    $searchInput.val("");
                    $searchBlock.removeClass("typing");
                    setTimeout(function () {
                        html.addClass('search-open');
                        $searchInput.trigger('focus');
                        $searchInput.trigger('click');
                    }, 200);
                    window.ReactNativeWebView?.postMessage(
                        JSON.stringify({
                            type: 'SEARCH_OPENED',
                            data: ''
                        })
                    );
                }
            });

            $('.block-search .action.close-btn').on('click', function () {
                console.log('theme.js close search button clicked');
                var html = $('html');
                html.removeClass('nav-search-typing');
                html.removeClass('--with-search-suggestion');
                if (html.hasClass('search-open')) {
                    html.removeClass('search-open');
                    setTimeout(function () {
                        html.removeClass('search-before-open');
                    }, 300);
                }
            });

        },

        clearCouponInput: function () {
            // 監聽輸入
            $(document).on("input", "#coupon", function () {
                var $this = $(this),
                    $couponBlock = $this.closest(".block.discount");
                
                if ($this.val()) {
                    $couponBlock.addClass("typing");
                } else {
                    $couponBlock.removeClass("typing");
                }
            });
            
            // 點擊清除
            $(document).on("click", ".block.discount .action.clear", function () {
                var $couponBlock = $(this).closest(".block.discount"),
                    $couponInput = $couponBlock.find("#coupon"),
                    placeholder = $couponInput.attr("data-placeholder");
                
                $couponInput.val("").attr("placeholder", placeholder).trigger("change");
                $couponBlock.removeClass("typing");
            });
        },

        accordionFooter: function () {
            // Accordion Footer - Mobile
            $(document).on("click", ".footer-links .heading", function () {
                if (!$(this).hasClass("active")) {
                    $(".footer-links .heading").removeClass("active");
                    $(this).addClass("active");
                } else {
                    $(this).removeClass("active");
                }
            });
        },

        recentlyViewedBlockMobile: function () {
            // Recently viewed products - Mobile
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    $('li.top-recently-viewed').appendTo('.nav-recently-viewed .list-recently-viewed');
                    if (!$('li.top-recently-viewed .empty').length)
                        $('<div class="empty abs-visually-hidden-desktop">' + $.mage.__('No item') + '</div>').appendTo('li.top-recently-viewed .admin__data-grid-outer-wrap');
                }, this),
                exit: $.proxy(function () {
                    if (!$('.header.links li.top-recently-viewed').length)
                        $('li.top-recently-viewed').appendTo('.header.links');
                }, this),
            });
        },

        productPointBlock: function () {
            // Product Grid Point Block - Tabs container - Mobile
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    if ($('[data-appearance="point_grid"]').length) {
                        $('[data-appearance="point_grid"]').each(function () {
                            var $productRow = $(this).find(".product-point-row");
                            if ($(this).find(".product-point-tabs").length) return;
                            $('<div class="product-point-tabs"></div>').insertBefore(
                                $productRow
                            );

                            $(".point-range").each(function () {
                                $(this).clone().appendTo(".product-point-tabs");
                            });

                            $(this)
                                .find(".product-point-row .product-point-col")
                                .eq(0)
                                .addClass("active");

                            $(this).find(".point-range").eq(0).addClass("active");
                        });
                    }

                    var jQ = $.noConflict();
                    jQ(document).on("click", ".point-range", function () {
                        var colTrigger = DOMPurify.sanitize(jQ(this).attr("data-tab-trigger"));
                        var $productPointEle = jQ(this).closest(
                            '[data-appearance="point_grid"]'
                        );
                        $productPointEle.find(".point-range").removeClass("active");
                        $productPointEle
                            .find(".product-point-col")
                            .removeClass("active");
                        jQ(this).addClass("active");
                        jQ('[data-tab-content="' + colTrigger + '"]').addClass("active");
                    });
                }, this),
                exit: $.proxy(function () {
                }, this),
            });
        },

        popularCategorySectionViewMore: function () {
            var section = $('.popular-topic-category-section'),
                btnViewMore = section.find('.view-more-btn > [data-element="empty_link"]'),
                btnViewLess = section.find('.view-less-btn > [data-element="empty_link"]');
            if (section.length) {
                btnViewMore.on("click", function () {
                    section.addClass('show-all');
                });
                btnViewLess.on("click", function () {
                    section.removeClass('show-all');
                });
            }
        },

        accordionCartSidebar: function () {
            var $summaryArrowSpan = $('<span />').addClass('summary-arrow').empty();
            if (!$(".cart-summary").find('.summary-arrow').length) {
                $(".cart-summary").addClass("collapse").prepend($summaryArrowSpan);
            }

            $(document).on("click", ".summary-arrow", function () {
                var $cartSummary = $(this).parent('.cart-summary, .opc-block-summary');
                if (!$cartSummary.hasClass("collapse")) {
                    $cartSummary.addClass("collapse");
                    $cartSummary.parents(".cart-container, .checkout-column.opc").removeClass("show-summary-bg");
                } else {
                    $cartSummary.removeClass("collapse");
                    $cartSummary.parents(".cart-container, .checkout-column.opc").addClass("show-summary-bg");
                }
            });
        },

        stickyCartSidebar: function () {
            mediaCheck({
                media: "(min-width: 1281px)",
                entry: $.proxy(function () {
                    $(".cart-summary").mage("sticky", {
                        container: "#maincontent",
                    });
                }, this),
                exit: $.proxy(function () {
                }, this),
            });
        },

        cmsBlockOrderThingsToNote: function () {
            var section = $('.collapse-section'),
                btnViewMore = section.find('.view-all-btn > [data-element="empty_link"]'),
                btnViewLess = section.find('.view-less-btn > [data-element="empty_link"]');
            if (section.length) {
                btnViewMore.on("click", function () {
                    section.addClass('show-all');
                });
                btnViewLess.on("click", function () {
                    section.removeClass('show-all');
                });
            }
        },

        navState: function(){
            const navs = [
                '/customer/account',
                'customer/address',
                '/wishlist',
                '/member',
                // '/sales/parentOrder/history',
                '/hotaipay/creditcard'
            ];

            const isOrderManagement = window.location.href.includes('/sales/parentOrder/history');

            // Check if window.location.href contains any of the navs
            const isNavsMatch = navs.some(href => window.location.href.includes(href));
            if (isOrderManagement) {
                $('.nav-item.nav-order-history').addClass('active');
            } else if (isNavsMatch) {
              $('.customer-account .customer-name').addClass('current');
            }



            const noti = [
                '/notification/customer/notification'
            ];

            // Check if window.location.href contains any of the notification link
            const isNotisMatch = noti.some(href => window.location.href.includes(href));

            if (isNotisMatch) {
              $('.magenest-notification .magenest-notification-action').addClass('current');
            }
        },

        customerDropdown: function(){
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    $(document).on("click.redirect", ".dropdown.customer-account .dropdown-toggle", function () {
                        // check if url is order success page, then show popup first
                        if (window.location.href.includes('checkout/onepage/success') && !$(this).hasClass('clicked') && $('.order-success-gift-box').length) {
                            $('#order-success-gift-box-confirm-modal').modal('openModal');
                            $(this).addClass('clicked');
                            return;
                        }
                        window.location.href = $(this).attr('data-href');
                    });
                }, this),
                exit: $.proxy(function () {
                    $(".dropdown.customer-account .dropdown-toggle").removeClass('clicked');
                    $(document).off("click.redirect", ".dropdown.customer-account .dropdown-toggle");
                    $(document).on("click.toggleDropdown", ".dropdown.customer-account .dropdown-toggle", function () {
                        var elem = $(this),
                            parent = $(this).parent(),
                            dropdown = $('[data-target="dropdown"]', parent);
                        if (!elem.hasClass('active')) {
                            elem.attr('aria-expanded', false);
                            dropdown.attr('aria-hidden', true);
                            elem.addClass('active');
                            parent.addClass('active');
                        } else {
                            elem.attr('aria-expanded', true);
                            dropdown.attr('aria-hidden', false);

                            elem.removeClass('active');
                            parent.removeClass('active');
                        }
                    });
                }, this),
            });
        },

        removeStoreData: function () {
            var currentPath = window.location.href;
            // var hasStoredAdd = window.localStorage.getItem('hasStoredAdd');

            if (!currentPath.includes('checkout')
                && !currentPath.includes('customer/address/new')
                && !currentPath.includes('customer/address/edit/')) {
                var customer = customerData.get('customer');
                if (customer().firstname) {
                    console.log('reset store data - removeStoreData');
                    this.clearStoreData();
                }
            }
            if(!currentPath.includes('checkout')) {
                window.localStorage.removeItem('default_convenience_store');
            }
        },

        clearStoreData: function () {
            // Clear store data
            console.log('reset store data - clearStoreData');
            $.ajax({
                url: urlBuilder.build('checkout/address/clearData'),
                type: "POST",
                success: function (response) {
                    if (response.success) {
                        console.log('Store data cleared successfully');
                        window.localStorage.removeItem('hasStoredAdd');
                    }
                },
                error: function (err) {
                    // check the err for error details
                }
            });
        },

        storeCatalogBreadcrumbs: function() {
            // console.log('breadcrumbs script loaded', $('body').hasClass('catalog-category-view'));
            if($('body').hasClass('catalog-category-view')) {
                const $breadcrumbs = $('.breadcrumbs').first();
                const $items = $breadcrumbs.find('.items').first();
                const $itemList = $items.find('.item');

                let itemList = [];
                // console.log($itemList.length)
                $itemList.each(function(){
                    const $this = $(this);

                    if($this.hasClass('home')) {
                        //continue to next loop
                        return;
                    }

                    const $atag = $this.find('a').first();

                    const item = {
                        name: 'category',
                        label: ($atag.length ? $atag.text() : $this.text()).trim(),
                        link: $atag.length ? $atag.attr('href') : window.location.href, //final breadcrumb link is current page
                    };
                    itemList.push(item);
                });

                // console.log('current category breadcrumb', itemList);

                //store itemList to local storage with key referer_category_crumbs
                localStorage.setItem('referer_category_crumbs', JSON.stringify(itemList));
            } else {
                if (!$('body').hasClass('catalog-product-view')) {
                    //remove referer_category_crumbs
                    localStorage.removeItem('referer_category_crumbs');
                }
            }
        },

        searchForm: function () {
            // Search form submit
            $('#search_mini_form').on('submit', function (e) {
                console.log('Mini search form submit detected!');
                // check if url is order success page, then show popup first
                if (window.location.href.includes('checkout/onepage/success') && !$(this).hasClass('clicked') && $('.order-success-gift-box').length) {
                    $('#order-success-gift-box-confirm-modal').modal('openModal');
                    $(this).addClass('clicked');
                    e.preventDefault();
                }
            });
        },
    }

    // Active bottom navigation - Mobile
    function isCurrent(ele) {
        var currentPath = window.location.href,
            path = $(ele).attr('href'),
            regex = new RegExp(path + '$');
        if (regex.test(currentPath)) {
            $(ele).addClass('active');
        }
    }

    isCurrent('.nav-home');
    isCurrent('.minicart-wrapper .action.showcart');

    $(".panel.header > .header.links").clone().appendTo("#store\\.links");
    $("#store\\.links li a").each(function () {
        var id = $(this).attr("id");

        if (id !== undefined) {
            $(this).attr("id", id + "_mobile");
        }
    });
    keyboardHandler.apply();

    Theme.Scripts.init();
});
