/**CANA Teddy Bear Sun Screenl 50ml
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'tabs',
    'matchMedia',
    'domReady!'
], function ($) {
    'use strict';

    const $brandListWidget = $('.ambrands-brandlist-widget');
    const $staticWidget = $('.sub-static-menu');

    function resetBrandListFilter() {
        if($('body').hasClass('show-cn')) {
            $('.ambrands-letter.ambrands-letter-ch.letter-ㄅ').click();
        } else {
            $('.ambrands-letter.ambrands-letter-en.letter-A').click();
        }
    }

    $.widget(
        'b8.menu',
        {
            _create: function () {
                'use strict';

                this._activeFirstMenu(this);

                mediaCheck({
                    media: "(max-width: 768px)",
                    entry: $.proxy(function () {
                        $('#mainMenu').attr('data-type','mobile');
                        var $menuIcon = $(".action.nav-toggle");
                        var $navigation = $(".page-wrapper > .navigation");
                        const $html = $('html');

                        if ($menuIcon.length && $navigation.length) {
                            $menuIcon.on("click", function() {
                                $navigation.addClass("show");
                                $("body").addClass("navopen");
                                console.log('navopen');
                                $('.ambrands-brandlist-widget.active, .level2.dropdown-submenu.active, .menu-flagship.active, .sub-static-menu.active, .megamenu-item.level2').removeClass('active');


                                if ($html.hasClass('nav-open')) {
                                    console.log('navopen 1');
                                    $html.removeClass('nav-open');
                                    $staticWidget.removeClass('active');
                                    $html.removeClass('nav-before-open');
                                    // setTimeout(function () {
                                    //     $html.removeClass('nav-before-open');
                                    // }, 100);
                                    window.ReactNativeWebView?.postMessage(
                                        JSON.stringify({
                                            type: 'MEGAMENU_CLOSED',
                                            data: ''
                                        })
                                    );
                                } else {
                                    console.log('navopen 2');
                                    $html.addClass('nav-before-open');
                                    $html.addClass('nav-open');
                                    $staticWidget.addClass('active');
                                    window.ReactNativeWebView?.postMessage(
                                        JSON.stringify({
                                            type: 'MEGAMENU_OPENED',
                                            data: ''
                                        })
                                    );
                                    // setTimeout(function () {
                                    //     $html.addClass('nav-open');
                                    // }, 100);
                                    // setTimeout(function () {
                                    //     $staticWidget.addClass('active');
                                    // }, 500);
                                }

                                $(".header.content .block-search").removeClass("typing");
                                $("#search").val("");
                                $html.removeClass('nav-search-typing');

                                resetBrandListFilter();
                            });
                        }
                        $("#hide-nav-button").on("click", function() {
                            console.log('hide-nav-button');
                            $navigation.removeClass("show");
                            $("body").removeClass("navopen");

                            $html.removeClass('nav-open');
                            $staticWidget.removeClass('active');
                            $html.removeClass('nav-before-open');

                            window.ReactNativeWebView?.postMessage(
                                JSON.stringify({
                                    type: 'MEGAMENU_CLOSED',
                                    data: ''
                                })
                            );
                            // setTimeout(function () {
                            //     $html.removeClass('nav-before-open');
                            // }, 300);
                        })

                        $(document).on('mouseover', '.ambrands-brandlist-widget, .level2.dropdown-submenu, .menu-flagship, .sub-static-menu, .megamenu-item.level2', function () {
                            if(!$(this).hasClass('active')) {
                                $('.ambrands-brandlist-widget.active, .level2.dropdown-submenu.active, .menu-flagship.active, .sub-static-menu.active, .megamenu-item.level2.active').removeClass('active');
                                $(this).addClass('active');

                                if($(this).hasClass('ambrands-brandlist-widget')) {
                                    resetBrandListFilter();
                                }
                            }
                        });

                        $(document).on('touchstart', 'span' , function() {
                            const $parent = $(this).closest('.ambrands-brandlist-widget, .level2.dropdown-submenu, .menu-flagship, .sub-static-menu, .megamenu-item.level2');

                            if($parent.length ) {
                                if(!$parent.hasClass('active')) {
                                    $('.ambrands-brandlist-widget.active, .level2.dropdown-submenu.active, .menu-flagship.active, .sub-static-menu.active, .megamenu-item.level2.active').removeClass('active');
                                    $parent.addClass('active');

                                    if($parent.hasClass('ambrands-brandlist-widget')) {
                                        resetBrandListFilter();
                                    }
                                }
                            }
                        })

                    }, this),
                    exit: $.proxy(function () {
                        $('#mainMenu').attr('data-type','desktop');
                        this.desktopAction(this);

                        $(document).on('mouseover', '.ambrands-brandlist-widget, .level2.dropdown-submenu' , function() {
                            if(!$(this).hasClass('active')) {
                                $('.ambrands-brandlist-widget.active, .level2.dropdown-submenu.active, .megamenu-item.level2').removeClass('active');

                                $(this).addClass('active');

                                if($(this).hasClass('ambrands-brandlist-widget')) {
                                    resetBrandListFilter();
                                }
                            }
                        });
                    }, this),
                });

                // Tabs
                const initCategoryTabs = function () {
                    const tabs = $(".category-tabs");
                    if (!tabs.length) {
                        setTimeout(initCategoryTabs, 100);
                        return;
                    }
                    tabs.each(function () {
                        const tabsParent = $(this).parents('.dropdown-menu-lv2');
                        const tabItems = tabsParent.find(".tab-item-name");
                        let catTabItem = '';
                        tabItems.each(function () {
                            const tabItem = $(this);
                            const tabHtml = tabItem.find("p").html();
                            const tabId = tabItem.data("id");
                            catTabItem += "<div class='tab-cat-item'><a href='#tab-"+ tabId +"' data-id='"+ tabId +"' title='"+ tabHtml +"'>" + tabHtml + "</a></div>";
                        });
                        $(this).html(catTabItem);
                        $('.tab-cat-item').each(function() {
                            const link = $(this).find('a');
                            $(link).on('click', function(e) {
                                $('.tab-cat-item').removeClass('active');
                                $('.tab-cat-item a').removeClass('active');
                                $(this).parent().addClass('active');
                                $(this).addClass('active');
                                const tab = $("#tab-"+ $(this).data('id'));
                                $('.dropdown-menu-lv2').animate(
                                    {scrollTop: tab.offset().top - 50},
                                   200
                                );
                            });
                        });
                    });
                };

                initCategoryTabs();

                this.setReferrerBreadcrumbItem();

            },

            _activeFirstMenu: (self) => {
                $('.navigation').addClass('loaded-menu');
            },

            desktopAction: (self) => {
                self.action();
            },

            action: () => {
                var closetimer = 0;

                function canceltimer () {
                    if(closetimer) {
                        window.clearTimeout(closetimer);
                        closetimer = null;
                    }
                }

                function hoverOutAction () {
                    closetimer = window.setTimeout(closeAction, closetimer);
                }

                function closeAction() {
                   toggleMenuHovering(false);
                   $('.ambrands-brandlist-widget').removeClass('active');
                   $staticWidget.removeClass('active');
                }

                function toggleMenuHovering(enableHovering, $target) {
                    const elementsToPad = document.querySelectorAll('body, header');

                    if(enableHovering) {
                        const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
                        elementsToPad.forEach(el => el.style.paddingRight = scrollBarWidth + 'px');

                        var dropdownHeight = 0;
                        if($target.closest('.menu-flagship').length) {
                            dropdownHeight = $('.menu-flagship .dropdown-menu-content')[0].offsetHeight;
                        } else {
                            dropdownHeight = $('.menu-cats .dropdown-menu-content')[0].offsetHeight;
                        }
                        $('.dropdown-menu-content').css('--dropdown-height', `${dropdownHeight}px`);

                        $('.navigation').addClass('hovering');
                        $('html').addClass('menu-hovering');
                    } else {
                        elementsToPad.forEach(el => el.style.paddingRight = '');

                        $('html').removeClass('menu-hovering');
                        $('.navigation').removeClass('hovering');
                    }
                }


                function hoverAction () {
                    // console.log('hoverAction');
                    canceltimer();
                    toggleMenuHovering(false);

                    toggleMenuHovering(true, $(this));

                    if($(this).closest('.menu-flagship').length) {
                        $('.ambrands-brandlist-widget').removeClass('active');
                        $staticWidget.removeClass('active');
                    } else {
                        $('.level2.dropdown-submenu.active').removeClass('active');
                        if($('#mainMenu').data('type') === 'mobile') {
                            // $staticWidget.addClass('active');
                        } else {
                            console.log('show brand list widget', {a: $('.ambrands-brandlist-widget').length, b: $('.ambrands-brandlist-widget').length, c: $("#react-brand-widget").length});
                            $('.ambrands-brandlist-widget').addClass('active');
                        }
                        resetBrandListFilter();
                    }
                }

                $('.menu-cats > .level0.dropdown-toggle, .menu-flagship').on('mouseenter touchstart', hoverAction);
                $('.menu-cats, .menu-flagship').on('mouseleave touchend',  hoverOutAction);
            },

            setReferrerBreadcrumbItem: function () {
                 $('.megamenu-item').on('click', function(){
                    if ($(this).find('> a').attr('href') !== '#') {
                        const megamenuId = $(this).data('megamenu-id');
                        localStorage.setItem('megamenu_referrer', megamenuId);
                        // console.log('megamenuId2', megamenuId);
                    }
                });
            }
        }
    );
    return $.b8.menu;
});
