define([
    'ko',
    'uiComponent',
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'Branch8_Spin2Win/js/model/spin-data',
    'matchMedia'
], function (ko, Component, $, urlBuilder, customerData, customerInfomation, spinData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_Spin2Win/trigger-action'
        },
        isTriggerBtnVisible: ko.observable(false),
        title: ko.observable('Spin to Win!'),
        imgUrl: ko.observable(''),
        data: ko.observable(spinData.data),
        buttonSize: ko.observable(null),
        // customer: spinData.customer,

        initialize: function () {
            this._super();
            this.customer = customerInfomation.customer();
            $('.spin-trigger-wrapper').removeClass('show');
        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        },

        initObservable: function () {
            const self = this;
            this._super();
            this.callAjaxData();

            customerInfomation.customer.subscribe(function(newValue) {
                console.log('Spintowin Customer Info data changed:', newValue, self.customer(), self.data(), self.data()?.button, self.isLogin());
                self.customer = newValue;
                if (self.data()) {
                    const newData = self.data();
                    const button = self.data()?.button;
                    if(button) {
                        if(button?.label) {
                            self.title(button.label);
                        }

                        if(button?.image && newData.mediaUrl) {
                            self.imgUrl(newData.mediaUrl + button.image);
                        }

                        const buttonSize = JSON.parse(button?.button_size || '{}');
                        const isDesktop = window.innerWidth > 768;
                        if(buttonSize?.desktop_width) {
                            $("#spin-trigger-btn").attr('data-desktop-width', buttonSize.desktop_width);
                        }
                        if(buttonSize?.mobile_width) {
                            $("#spin-trigger-btn").attr('data-mobile-width', buttonSize.mobile_width);
                        }
                        if(buttonSize?.desktop_height) {
                            $("#spin-trigger-btn").attr('data-desktop-height', buttonSize.desktop_height);
                        }
                        if(buttonSize?.mobile_height) {
                            $("#spin-trigger-btn").attr('data-mobile-height', buttonSize.mobile_height);
                        }
                        if (isDesktop && buttonSize?.desktop_width && buttonSize?.desktop_height) {
                            $("#spin-trigger-btn").css({
                                'width': buttonSize.desktop_width + 'px',
                                'height': buttonSize.desktop_height + 'px'
                            });
                        }
                        if (!isDesktop && buttonSize?.mobile_width && buttonSize?.mobile_height) {
                            $("#spin-trigger-btn").css({
                                'width': buttonSize.mobile_width + 'px',
                                'height': buttonSize.mobile_height + 'px'
                            });
                        }
                        
                        const specifyPage = button?.specify_page || '';
                        const pageList = specifyPage.split(',').map(p => p.trim());
                        const currentUrl = window.location.pathname.replace(/\/\//g, '/');

                        const isPageMatch = (page) => {
                            if ((page === '/checkout' || page === '/checkout/') && (currentUrl === '/checkout/' || currentUrl === '/checkout')) {
                                return true;
                            }
                            if ((page === '/checkout/cart' || page === '/checkout/cart/') && (currentUrl === '/checkout/cart/' || currentUrl === '/checkout/cart')) {
                                return true;
                            }

                            if (page === '/checkout' || page === '/checkout/cart' || page === '/checkout/' || page === '/checkout/cart/') {
                                return false;
                            }
                            
                            return currentUrl.startsWith(page);
                        };

                        const isSpecifiedPage = specifyPage!== '' && pageList.some(page => isPageMatch(page));
                        if(button?.show && button.show === '1' && self.isLogin()) {
                            if (button?.display_scope && button.display_scope === '1' && $('body').hasClass('cms-index-index')) {
                                self.isTriggerBtnVisible(true);
                                $('.spin-trigger-wrapper').addClass('show');
                            } else if (button?.display_scope && button.display_scope === '2' && ($('body').hasClass('cms-index-index') || isSpecifiedPage)) {
                                self.isTriggerBtnVisible(true);
                                $('.spin-trigger-wrapper').addClass('show');
                            }
                        } else {
                            $('.spin-trigger-wrapper').removeClass('show');
                        }

                    }
                } else {
                    self.callAjaxData();
                }
            });

            // var customerInfo = customerData.get('customer')();

            // data.subscribe(function (newData) {
            //     console.log('New spin data received:', newData);
            //     const button = newData?.button;
            //     if(button) {
            //         if(button?.label) {
            //         self.title(button.label);
            //         }

            //         if(button?.image && newData.mediaUrl) {
            //             self.imgUrl(newData.mediaUrl + button.image);
            //         }
            //         if(button?.show && button.show !== '0' && self.isLogin()) {
            //             self.isTriggerBtnVisible(true);
            //             $('.spin-trigger-wrapper').addClass('show');
            //         } else {
            //             $('.spin-trigger-wrapper').removeClass('show');
            //         }
            //     }

            // });

            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    if($('#spin-trigger-btn').length && $('#spin-trigger-btn').attr('data-mobile-width') && $('#spin-trigger-btn').attr('data-mobile-height')) {
                        $('#spin-trigger-btn').css({
                            'width': $('#spin-trigger-btn').attr('data-mobile-width') + 'px',
                            'height': $('#spin-trigger-btn').attr('data-mobile-height') + 'px'
                        });
                    }
                }, this),
                exit: $.proxy(function () {
                    if($('#spin-trigger-btn').length && $('#spin-trigger-btn').attr('data-desktop-width') && $('#spin-trigger-btn').attr('data-desktop-height')) {
                        $('#spin-trigger-btn').css({
                            'width': $('#spin-trigger-btn').attr('data-desktop-width') + 'px',
                            'height': $('#spin-trigger-btn').attr('data-desktop-height') + 'px'
                        });
                    }
                }, this),
            });

            return this;
        },

        callAjaxData: function () {
            const self = this;
            $.ajax({
                type: "POST",
                url: urlBuilder.build("spintowin/index/index"),
                data: { current_url: location.href, spinId: this.spinId },
                dataType: "json",
                cache: false,
                success: function (response) {
                    if (response.success) {
                        spinData.data(response.data);
                        self.data(response.data);
                        const newData = response.data;
                        const button = newData?.button;
                        if(button) {
                            if(button?.label) {
                                self.title(button.label);
                            }

                            if(button?.image && newData.mediaUrl) {
                                self.imgUrl(newData.mediaUrl + button.image);
                            }

                            const buttonSize = JSON.parse(button?.button_size || '{}');
                            const isDesktop = window.innerWidth > 768;
                            if(buttonSize?.desktop_width) {
                                $("#spin-trigger-btn").attr('data-desktop-width', buttonSize.desktop_width);
                            }
                            if(buttonSize?.mobile_width) {
                                $("#spin-trigger-btn").attr('data-mobile-width', buttonSize.mobile_width);
                            }
                            if(buttonSize?.desktop_height) {
                                $("#spin-trigger-btn").attr('data-desktop-height', buttonSize.desktop_height);
                            }
                            if(buttonSize?.mobile_height) {
                                $("#spin-trigger-btn").attr('data-mobile-height', buttonSize.mobile_height);
                            }
                            if (isDesktop && buttonSize?.desktop_width && buttonSize?.desktop_height) {
                                $("#spin-trigger-btn").css({
                                    'width': buttonSize.desktop_width + 'px',
                                    'height': buttonSize.desktop_height + 'px'
                                });
                            }
                            if (!isDesktop && buttonSize?.mobile_width && buttonSize?.mobile_height) {
                                $("#spin-trigger-btn").css({
                                    'width': buttonSize.mobile_width + 'px',
                                    'height': buttonSize.mobile_height + 'px'
                                });
                            }
                            
                            const specifyPage = button?.specify_page || '';
                            const pageList = specifyPage.split(',').map(p => p.trim());
                            const currentUrl = window.location.pathname.replace(/\/\//g, '/');

                            const isPageMatch = (page) => {
                                if ((page === '/checkout' || page === '/checkout/') && (currentUrl === '/checkout/' || currentUrl === '/checkout')) {
                                    return true;
                                }
                                if ((page === '/checkout/cart' || page === '/checkout/cart/') && (currentUrl === '/checkout/cart/' || currentUrl === '/checkout/cart')) {
                                    return true;
                                }

                                if (page === '/checkout' || page === '/checkout/cart' || page === '/checkout/' || page === '/checkout/cart/') {
                                    return false;
                                }
                                
                                return currentUrl.startsWith(page);
                            };

                            const isSpecifiedPage = specifyPage!== '' && pageList.some(page => isPageMatch(page));
                            if(button?.show && button.show === '1' && self.isLogin()) {
                                if (button?.display_scope && button.display_scope === '1' && $('body').hasClass('cms-index-index')) {
                                    self.isTriggerBtnVisible(true);
                                    $('.spin-trigger-wrapper').addClass('show');
                                } else if (button?.display_scope && button.display_scope === '2' && ($('body').hasClass('cms-index-index') || isSpecifiedPage)) {
                                    self.isTriggerBtnVisible(true);
                                    $('.spin-trigger-wrapper').addClass('show');
                                }
                            } else {
                                $('.spin-trigger-wrapper').removeClass('show');
                            }
                        }
                    }
                },
                error: function (response) {
                  console.error('callAjaxData error:', response);
                },
                finally: function(response) {
                  console.error('callAjaxData finally:', response);
                }
            });
        },

        spin: function () {
          const layout = this.data()?.layout;
          if (layout && layout.view && layout.view === 'page' && layout.page_url) {
            const isHotaiApp = $('body').hasClass('hotai-app');
            const url = urlBuilder.build(this.data().layout.page_url);
            if (isHotaiApp) {
                window.ReactNativeWebView?.postMessage(
                    JSON.stringify({type: 'OPEN_NEW_SCREEN', data: {
                        url,
                        title: layout.page_title || ''
                    }})
                );
            } else {
                // check if url is order success page, then show popup first
                if (window.location.href.includes('checkout/onepage/success') && !$('#spin-trigger-btn').hasClass('clicked') && $('.order-success-gift-box').length) {
                   $('#order-success-gift-box-confirm-modal').modal('openModal');
                   $('#spin-trigger-btn').addClass('clicked');
                    return;
                }
                window.location.href = url;
            }
          }
        },

        close: function () {
            this.isTriggerBtnVisible(false);
            $('.spin-trigger-wrapper').removeClass('show');
        }
    });
});
