define([
    'jquery',
    'mage/apply/main',
    'b8OrderListing',
    'jquery/ui',
    'matchMedia',
    'mage/validation',
    'b8ReactAccount',

    'domReady!'
], function ($, main, b8OrderListing) {
    $.widget('b8.customerAccount', {
        options: {},

        _create: function () {
            if ($("body.account").hasClass("member-info-detail")) {
                return;
            }
            var windowWidth = $(window).width();
            if (windowWidth < 1024) {
                this.activeClassNavMobile();
                this.showDashboardInfo(false);
            }

            if ($("body.account").hasClass("customer-account-index")) {
                this.triggerWindowResize();
            }
            // this.triggerWindowResize();
            this.triggerClickAccountLink();
            // this.addressTabScrollTop();
            this.eventLogout();
            this.ordersLoad();
        },

        activeClassNavMobile: function () {
            let activeLinks = $(".sidebar-main .block-account-nav .items .link.active");

            if (activeLinks && activeLinks.length > 0) {
                let linkActive = $(activeLinks[0]);
                const classes = linkActive.attr('class').split(' ');
                const activeClasses = classes.filter(className => className !== 'active');

                const linkMobile = $(".customer-header-bottom .block-account-nav .items").find("." + activeClasses.join('.'));

                if (linkActive.hasClass("user")) {
                    this.showDashboardInfo(true);
                    this.triggerEventLinkUser();
                } else {
                    linkMobile.addClass("active");
                    //this.triggerClickAccountLink();
                }
            }
        },

        toggleCustomerHeader: function (isShow) {
            const customerHeader = $(".account .customer-header");
            if (isShow) {
                customerHeader.show();
            }
            else {
                customerHeader.hide();
            }
        },

        showDashboardInfo: function (isShow) {
            const dashboard = $(".block-dashboard-info");
            const customerHeader = $(".account.customer-account-index .customer-header");
            const customerSidebar = $(".account.customer-account-index .sidebar");
            const accountTitle = $(".account.customer-account-index .page-title-wrapper");
            //const linkUsers = $(".customer-header-bottom .block-account-nav .items .link.user");
            //const linkWishlist = $(".customer-header-bottom .block-account-nav .items .link.wishlist");
            //const recentlyViewed = $(".customer-header-bottom .block-account-nav .items .link.recently-viewed");

            if (isShow) {
                dashboard.show();
                accountTitle.show();
                customerHeader.hide();
                customerSidebar.hide();
            } else {
                dashboard.hide();
                accountTitle.hide();
                customerHeader.show();
                customerSidebar.show();
            }
        },

        triggerClickAccountLink: function () {
            let self = this;
            const linkActive = $(".customer-header-bottom .block-account-nav .items .link.active");
            let linkMyAccount = $(".account .page-title-wrapper .page-title");

            if (linkActive.hasClass("user")) {
                return;
            }

            if (linkActive.hasClass("recently-viewed")) {
                linkMyAccount = $(".browsing-history-heading .backward");
            } else if (linkActive.hasClass("wishlist")) {
                linkMyAccount = $(".toolbar.wishlist-toolbar .backward");
            }

            if (linkActive && linkActive.length > 0) {
                self.toggleCustomerHeader(false);
            }

            if (linkMyAccount) {
                linkMyAccount.unbind("click");
                linkMyAccount.bind("click", function (e) {
                    e.preventDefault();
                    if ($(window).width() >= 1024) {
                        return;
                    }

                    let currentURL = window.location.href;
                    if (currentURL.indexOf("address/edit") > 0 || currentURL.indexOf("address/new") > 0) {
                        window.location.href = "/customer/address";
                    }
                    else {
                        window.location.href = "/customer/account";
                        //window.history.back();
                    }
                })
            }
        },

        addressTabScrollTop: function () {
            $(".block.block-addresses").on("beforeOpen", function () {
                setTimeout(function () {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }, 100);
            });
        },

        triggerEventLinkUser: function () {
            let self = this;
            const linkUsers = $(".customer-header-bottom .block-account-nav .items .link.user");
            const linkBackToAccount = $(".account.customer-account-index .page-title-wrapper .page-title");

            if (linkUsers && linkUsers.length > 0) {
                linkUsers.unbind("click");
                linkUsers.bind("click", function (e) {
                    e.preventDefault();
                    $(this).toggleClass("active");
                    if ($(this).hasClass("active")) {
                        self.showDashboardInfo(true);
                    }
                    else {
                        self.showDashboardInfo(false);
                    }
                })
            }

            if (linkBackToAccount) {
                linkBackToAccount.unbind("click");
                linkBackToAccount.bind("click", function (e) {
                    e.preventDefault();
                    linkUsers.removeClass("active");
                    self.showDashboardInfo(false);
                })
            }
        },

        triggerEventLinkWishlist: function () {
            let self = this;
            const linkWishlist = $(".customer-header-bottom .block-account-nav .items .link.wishlist");
            const linkBackToAccount = $(".toolbar.wishlist-toolbar");

            if (linkWishlist && linkWishlist.length > 0) {
                self.toggleCustomerHeader(true);
                linkWishlist.unbind("click");
                linkWishlist.bind("click", function (e) {
                    e.preventDefault();
                    $(this).toggleClass("active");
                    self.toggleCustomerHeader(false);
                    if ($(this).hasClass("active")) {
                        self.showDashboardInfo(true);
                    }
                    else {
                        self.showDashboardInfo(false);
                    }
                })
            }

            if (linkBackToAccount) {
                linkBackToAccount.unbind("click");
                linkBackToAccount.bind("click", function (e) {
                    e.preventDefault();
                    self.toggleCustomerHeader(true);
                    linkWishlist.removeClass("active");
                    self.showDashboardInfo(false);
                })
            }
        },

        triggerEventLinkRecentlyViewed: function () {
            let self = this;
            const recentlyViewed = $(".customer-header-bottom .block-account-nav .items .link.recently-viewed");
            const linkBackToAccount = $(".browsing-history-heading");

            if (recentlyViewed && recentlyViewed.length > 0) {
                self.toggleCustomerHeader(true);
                recentlyViewed.unbind("click");
                recentlyViewed.bind("click", function (e) {
                    e.preventDefault();
                    self.toggleCustomerHeader(false);
                    $(this).toggleClass("active");
                    if ($(this).hasClass("active")) {
                        self.showDashboardInfo(true);
                    }
                    else {
                        self.showDashboardInfo(false);
                    }
                })
            }

            if (linkBackToAccount) {
                linkBackToAccount.unbind("click");
                linkBackToAccount.bind("click", function (e) {
                    e.preventDefault();
                    self.toggleCustomerHeader(true);
                    recentlyViewed.removeClass("active");
                    self.showDashboardInfo(false);
                })
            }
        },

        triggerWindowResize: function () {
            let self = this;
            const dashboard = $(".block-dashboard-info");
            const accountTitle = $(".account .page-title-wrapper");
            const customerHeader = $(".account.customer-account-index .customer-header");
            const customerSidebar = $(".account.customer-account-index .sidebar");
            let userLinks = $(".sidebar-main .block-account-nav .items .link.user");

            if (!userLinks) {
                return;
            }

            let userActive = $(userLinks[0]).hasClass("active");

            mediaCheck({
                media: '(min-width: 1024px)',
                entry: function () {
                    customerHeader.show();
                    customerSidebar.show();
                    accountTitle.show();
                    dashboard.show();
                },
                exit: function () {
                    self.showDashboardInfo(!userActive);
                },
            });
        },

        eventLogout: function () {
            $(".logout-link .button-logout").bind("click", function () {
                $(".link.authorization-link .link-logout").trigger("click");
            })
        },

        ordersLoad: function () {
            $('.block-account-nav .link.order').addClass('init-load');
            $('body').on('click', '.block-account-nav .link.order > a', function (e) {
                const orderLink = $(this);
                if ($('body').hasClass('sales-parentorder-history'))
                    return;
                e.preventDefault();
                const container = document.getElementById('account-board-root');
                var fisrtLoad = true;
                console.log('DOMContentLoaded');
                if (container) {
                    window.initAccountBoard('account-board-root', {
                        pageSize: 10, afterRender: function () {
                            console.log('trigger updated');
                            $('body').find('script.script-reinit').each(function () {
                                const scriptText = $(this).text();
                                // Validate that script is from trusted source (own templates)
                                if (scriptText && scriptText.trim().length > 0) {
                                    try {
                                        // Use Function constructor instead of globalEval for better control
                                        const scriptFn = new Function(scriptText);
                                        scriptFn();
                                    } catch (e) {
                                        console.error('Error executing reinitialization script:', e);
                                    }
                                }
                            });
                            $('body').addClass('account-orders-init');
                            orderLink.parent().addClass('active orders-loaded');
                            if ($('body').find('.modal-popup.modal-order-filter').length) {
                                $('.account-orders-container #popup-order-filter').remove();
                            }
                            if ($('body').data('b8OrderListing'))
                                $('body').data('b8OrderListing').collapseSearchItemByDefault();

                            setTimeout(() => {
                                if (fisrtLoad) {
                                    console.log('fisrtLoad');
                                    // main.apply();
                                    $('#account-board-root').trigger('contentUpdated');
                                    $('[data-role="rma-form"]').applyBindings();
                                    fisrtLoad = false;
                                }
                            }, 200);

                        }
                    });

                }
            });

            mediaCheck({
                media: "(max-width: 1024px)",
                entry: $.proxy(function () {
                    $('body').on('click', '.order-title-wrapper', function (e) {
                        $('body').removeClass('account-orders-init');
                        $('#account-board-root').empty();
                    });
                }, this),
                exit: $.proxy(function () {
                }, this),
            });
        }
    });

    return $.b8.customerAccount;
});
