define([
        'jquery',
        'mage/translate',
        'underscore',
        'Magento_Catalog/js/product/view/product-ids-resolver',
        'Magento_Customer/js/customer-data',
        'plugins/DOMPurify',
        'Magento_Ui/js/modal/modal',
        'mage/url',
        'Magento_Catalog/js/price-utils',
        'jquery-ui-modules/widget'
    ],
    function ($, $t, _, idsResolver, customerData, DOMPurify, modal, urlBuilder, priceUtils) {
        'use strict';

        return function (catalogAddToCart) {
            $.widget('mage.catalogAddToCart', catalogAddToCart, {
                _create: function () {
                    this._super();
                    this._doSomethingAfterAddedProductToCart();
                    // $('.action.buynow').prop('disabled', false);
                    var self = this;
                    if($('#modal_fullpoint_confirm_popup').length) {
                        var options = {
                            type: 'popup',
                            responsive: false,
                            title: $.mage.__('Confirmation'),
                            modalClass: 'modal-custom pdp-fullpoint-confirmation-popup',
                            clickableOverlay: false,
                            buttons: [],
                            closed: function () {
                                $('#product-buynow-button').removeAttr('data-confirmed');
                                if(!$('#product-buynow-button').attr('data-confirmed') || $('#product-buynow-button').attr('data-confirmed') != 1) {
                                   self.enableAllAddToCartButton();
                                }
                            }
                            
                        };
                        
                        var popup = modal(options, $('#modal_fullpoint_confirm_popup'));
                    }

                    if($('#modal_fullpoint_error_popup').length) {
                        var errorOptions = {
                            type: 'popup',
                            responsive: false,
                            title: $.mage.__('點數不足'),
                            modalClass: 'modal-custom pdp-fullpoint-error-popup',
                            buttons: [{
                                text: $.mage.__('我知道了'),
                                class: 'action primary action-primary',
                                click: function () {
                                    this.closeModal();
                                }
                            }],
                            closed: function () {
                                $('#product-buynow-button').removeAttr('data-confirmed');
                               self.enableAllAddToCartButton();
                            }
                        };
                        
                        var errorPopup = modal(errorOptions, $('#modal_fullpoint_error_popup'));
                    }

                    $(document).on('click', '#fullpoint_confirm_payment_button', function(e) {
                        e.preventDefault();

                        const $confirmButton = $(this);
                        const $buyNowButton = $('#product-buynow-button');

                        if($('.limit-qty-text-popup.error').length) {
                            return;
                        }

                        if ($confirmButton.prop('disabled')) {
                            return;
                        }

                        $confirmButton.prop('disabled', true).addClass('disabled');

                        if ($buyNowButton.length) {
                            self.enableAllAddToCartButton();

                            $buyNowButton
                                .attr('data-confirmed', '1')
                                .trigger('click');
                        } else {
                            console.warn('#product-buynow-button not found.');
                            $confirmButton.prop('disabled', false).removeClass('disabled');
                        }
                    });

                    $(document).on('click', '.fullpoint-pdp-options-container .swatch-option', function() {
                        var optionId = $(this).attr('data-option-id');
                        if($(this).hasClass('selected')) {
                            $(this).closest('.swatch-attribute-options').find('.swatch-option').removeClass('selected');
                        } else {
                            $(this).closest('.swatch-attribute-options').find('.swatch-option').removeClass('selected');
                            $(this).addClass('selected');
                        }
                        var optionId = $(this).attr('data-option-id');
                        $("#product-options-wrapper .swatch-option[data-option-id='" + optionId + "']").trigger('click');
                    });

                    $(document).on('click', '.fullpoint-pdp-options-container .batch-setting-id', function() {
                        $('.fullpoint-pdp-options-container .batch-setting-id').removeClass('active');
                        $(this).addClass('active');
                        $("#product-options-wrapper .batch-setting-id[data-batch-id='" + $(this).attr('data-batch-id') + "']").trigger('click');
                    });
                },

                _doSomethingAfterAddedProductToCart: function () {
                    const cartData = customerData.get('cart');
                    let currentCount = cartData()['summary_count'];

                    cartData.subscribe(function () {
                        if (currentCount !== undefined) {
                            if (cartData()['summary_count'] > currentCount) {
                                // do something here
                                if (window.top.location.href !== window.location.href) {
                                    setTimeout(function() {
                                        $('button.mfp-close', parent.document).trigger('click');
                                    }, 1000);
                                }
                            }
                            currentCount = cartData()['summary_count'];
                        }
                    });
                },

                ajaxSubmit: function (form) {
                    var self = this,
                        productIds = idsResolver(form),
                        formData;
                    let link = self.element?.context?.baseURI;
                    var jQ = $.noConflict();
                    self.disableAddToCartButton(form);
                    formData = new FormData(form[0]);

                    var addToCartButton = ((window.isBuyNow || window.cartRedirectLink) && this.options.addToCartButtonSelector == '.action.tocart') ?  $(form).find('.action.buynow') : $(form).find(this.options.addToCartButtonSelector);
                    
                    if(addToCartButton.hasClass('buynow') && addToCartButton.attr('data-full-point') == 1) {
                        formData.append('fullpoint_virtual', '1');
                        localStorage.removeItem('b8_countdown_end_time');
                        if(addToCartButton.attr('data-confirmed') == 1) {
                            $('#product-buynow-button').removeAttr('data-confirmed');
                        } else {
                            var $activeImg = $('.fotorama__stage__frame.fotorama__active img');
                            var src = $activeImg?.attr('src') || $('.fotorama__stage__frame.fotorama__active')?.attr('href') || '';
                            if(src === '' || !src) {
                                src = $('.gallery-placeholder__image').attr('src') || '';
                            }
                            $('.fullpoint-pdp-photo').html(`<img src="${src}" alt="">`);

                            var $productPrice = $('.product-info-main-wrapper .product-info-price').html();
                            $('.fullpoint-pdp-price').html($productPrice);

                            var $productOptions = $('.product-info-main-wrapper .product-options-wrapper .fieldset').html();
                            if(!$productOptions) {
                               $('.fullpoint-pdp-options-container').addClass('hide');
                            } else {
                                $('.fullpoint-pdp-options-container').html($productOptions);
                                // $(document).on('updatePrice', '[data-role=priceBox]', function (event, data) {
                                //     const newPrice = $(this)
                                //         .find('[data-price-type="finalPrice"]')
                                //         .data('price-amount');

                                //     console.log('Price updated:', newPrice);

                                //     // Your custom logic here
                                // });
                            }

                            var $productQty = $('#product_addtocart_form .field.qty').html();
                            $('.fullpoint-pdp-box-tocart').html($productQty);

                            $('.fullpoint-pdp-box-tocart').find('.input-text.qty').on('change', function() {
                                var valQty= parseInt($(this).val() || 0);
                                if(valQty<0)
                                    valQty = 0;
                                $('.box-tocart').find('#qty').val(valQty);
                                $('.box-tocart').find('#qty').trigger('change');

                                var finalPrice = $('.price-container.price-final_price meta[itemprop="price"]').attr('content');
                                var totalPrice = finalPrice * valQty;
                                $('#fullpoint_total_price').text(new Intl.NumberFormat('en-US').format(totalPrice));
                            }); 
                            
                            $(document).on('change', '.fullpoint-pdp-box-tocart #qty', function(e){
                                var valQty= parseInt($(this).val() || 0);
                                if(valQty<0)
                                    valQty = 0;
                                $('.box-tocart').find('#qty').val(valQty);
                                $('.box-tocart').find('#qty').trigger('change');
                            });


                            var finalPrice = $('.price-container.price-final_price meta[itemprop="price"]').attr('content');
                            var qty = $('.box-tocart .input-text.qty').val() || 1;
                            var totalPrice = finalPrice * qty;
                            $('#fullpoint_total_price').text(new Intl.NumberFormat('en-US').format(totalPrice));
                            $('.fullpoint-pdp-box-tocart').find('.input-text.qty').val(qty);

                            if($('#modal_fullpoint_confirm_popup').length) {
                                $('#modal_fullpoint_confirm_popup').modal('openModal');
                            }
                            return;
                        }
                    } else {
                        $(self.options.minicartSelector).trigger('contentLoading');
                    }

                    // Reload messages before call ajax action 
                    // customerData.reload(['messages']);
                    jQ.ajax({
                        url: form.attr('action'),
                        data: formData,
                        type: 'post',
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,

                        /** @inheritdoc */
                        beforeSend: function () {
                            if (self.isLoaderEnabled()) {
                                $('body').trigger(self.options.processStart);
                            }
                        },

                        /** @inheritdoc */
                        success: function (res) {
                            var eventData, parameters;
                            $(document).trigger('ajax:addToCart', {
                                'sku': form.data().productSku,
                                'productIds': productIds,
                                'form': form,
                                'response': res
                            });

                            if (self.isLoaderEnabled()) {
                                $('body').trigger(self.options.processStop);
                            }

                            // if (res.messages) {
                            //     const sanitizedMessages = DOMPurify.sanitize(res.messages, {
                            //         ALLOWED_TAGS: ['div', 'ol', 'ul', 'li', 'img', 'p', 'span', 'b', 'strong'], 
                            //         ALLOWED_ATTR: ['style', 'class', 'src', 'width', 'height', 'alt', 'title', 'href']
                            //     });
                            //     jQ(self.options.messagesSelector).html(sanitizedMessages);
                            // }

                            if(res?.fullpoint_virtual?.success == false){
                                window.fullPointError = true;
                                var customerInfo = customerData.get('customer')();
                                var hotaiPoints = customerInfo?.customer_point_formated || '0';
                                $('#fullpoint_error_message > span').text(hotaiPoints);
                                $('#modal_fullpoint_error_popup').modal('openModal');
                                $('#modal_fullpoint_confirm_popup').modal('closeModal');
                                $('#fullpoint_confirm_payment_button').prop('disabled', false).removeClass('disabled');
                                self.enableAddToCartButton(form);
                                return;
                            }

                            if(res?.fullpoint_virtual?.success == true){
                                var checkoutFullpointUrl = $('#fullpoint_checkout_url').val();
                                self._redirect(checkoutFullpointUrl);
                                return;
                            }
                            
                            if (res.backUrl) {
                                eventData = {
                                    'form': form,
                                    'redirectParameters': []
                                };

                                // trigger global event, so other modules will be able add parameters to redirect url
                                $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                                if (eventData.redirectParameters.length > 0 &&
                                    window.location.href.split(/[?#]/)[0] === res.backUrl
                                ) {
                                    parameters = res.backUrl.split('#');
                                    parameters.push(eventData.redirectParameters.join('&'));
                                    res.backUrl = parameters.join('#');
                                }
                                if (link) res.backUrl = link;
                                if (window.top.location.href === window.location.href) {
                                    self._redirect(res.backUrl);
                                    return;
                                } else {
                                    let parser = document.createElement('a');
                                    parser.href = res.backUrl;
                                    if (parser.hostname === window.location.hostname) {
                                        if (parser.pathname.includes('/checkout/') && !parser.pathname.includes('/checkout/cart')) {
                                            self._redirect(res.backUrl);
                                            return;
                                        }
                                    } else {
                                        self._redirect(res.backUrl);
                                        return;
                                    }
                                    let messagesSection = ['messages'];
                                    customerData.reload(messagesSection);
                                    self.enableAddToCartButton(form);
                                }
                                return;
                            }

                            if(addToCartButton.hasClass('buynow') && !addToCartButton.attr('data-full-point')) {
                                console.warn('Buy now -  product added to cart, but no redirect url provided.');
                                self._redirect(urlBuilder.build('checkout/cart'));
                            }

                            if (window.cartRedirectLink) self._redirect(window.cartRedirectLink);

                            if (res.minicart) {
                                const sanitizedMinicart = DOMPurify.sanitize(res.minicart);
                                jQ(self.options.minicartSelector).replaceWith(sanitizedMinicart);
                                jQ(self.options.minicartSelector).trigger('contentUpdated');
                            }

                            if (res.product && res.product.statusText) {
                                $(self.options.productStatusSelector)
                                    .removeClass('available')
                                    .addClass('unavailable')
                                    .find('span')
                                    .text(res.product.statusText);
                            }
                            self.enableAddToCartButton(form);

                            /* Hide add to cart popup - mobile */
                            $('.product-add-form').removeClass('__show __tocart-form __buynow-form');
                            $('body').removeClass('options-form__show');
                        },

                        /** @inheritdoc */
                        error: function (res) {
                            $(document).trigger('ajax:addToCart:error', {
                                'sku': form.data().productSku,
                                'productIds': productIds,
                                'form': form,
                                'response': res
                            });
                        },

                        /** @inheritdoc */
                        complete: function (res) {
                            if (res.state() === 'rejected') {
                                location.reload();
                            }
                            try {
                                const json = JSON.parse(res.responseText);
                                if (window?.top?.jQuery && json.reloadData
                                ) {
                                    window?.top?.jQuery(window.parent.document).trigger('reload_item_row_data', [
                                        json.reloadData
                                    ])
                                }
                                if (window?.top?.jQuery?.magnificPopup
                                    && json.closeMagnificPopup
                                ) {
                                   window.top.jQuery.magnificPopup.close();
                                    /*now we ajax load cart, so do not reload*/
                                    // window.top.location.reload();
                                }
                            } catch (e) {
                                console.log(e)
                            }
                            window.cartRedirectLink = '';
                        }
                    });
                },
                /**
                 * @param {String} form
                 */
                disableAddToCartButton: function (form) {
                    var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...'),
                        addToCartButton = ((window.isBuyNow || window.cartRedirectLink) && this.options.addToCartButtonSelector == '.action.tocart') ?  $(form).find('.action.buynow') : $(form).find(this.options.addToCartButtonSelector),
                        addToCartButtonAditional = addToCartButton[0].id === 'product-buynow-button' ? $("#bottom-buynow-button") : $("#bottom-tocart-button");

                    addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
                    addToCartButton.prop('disabled', true);
                    addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
                    addToCartButton.prop('title', addToCartButtonTextWhileAdding);

                    if(!addToCartButton.hasClass('buynow') && $('.action.buynow').length) {
                        $('.action.buynow').prop('disabled', true);
                    } else if(addToCartButton.hasClass('buynow') && $('.action.tocart').length) {
                        $('.action.tocart').prop('disabled', true);
                    }

                    if (addToCartButtonAditional.length) {
                        addToCartButtonAditional.addClass(this.options.addToCartButtonDisabledClass);
                        addToCartButtonAditional.find('span').text(addToCartButtonTextWhileAdding);
                        addToCartButtonAditional.prop('disabled', true);
                    }
                },

                /**
                 * @param {String} form
                 */
                enableAddToCartButton: function (form) {
                    var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added'),
                        self = this,
                        addToCartButton = ((window.isBuyNow || window.cartRedirectLink) && this.options.addToCartButtonSelector == '.action.tocart') ?  $(form).find('.action.buynow') : $(form).find(this.options.addToCartButtonSelector),
                        addToCartButtonAditional = addToCartButton[0].id === 'product-buynow-button' ? $("#bottom-buynow-button") : $("#bottom-tocart-button");

                    addToCartButton.find('span').text(addToCartButtonTextAdded);
                    addToCartButton.prop('title', addToCartButtonTextAdded);

                    if (addToCartButtonAditional.length) {
                        addToCartButtonAditional.find('span').text(addToCartButtonTextAdded);
                    }

                    setTimeout(function () {
                        var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart');
                        if(!addToCartButton.hasClass('buynow')) {
                            addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                            addToCartButton.find('span').text(addToCartButtonTextDefault);
                            addToCartButton.prop('title', addToCartButtonTextDefault);
                            addToCartButton.prop('disabled', false);
                            $('.action.buynow').prop('disabled', false);
                        }

                        if (addToCartButtonAditional.length && !addToCartButtonAditional.hasClass('buynow')) {
                            addToCartButtonAditional.find('span').text(addToCartButtonTextDefault);
                            addToCartButtonAditional.removeClass(self.options.addToCartButtonDisabledClass);
                            addToCartButtonAditional.prop('disabled', false);
                        }
                    }, 1000);
                },

                /**
                 * @param {String} form
                 */
                enableAllAddToCartButton: function () {
                    var self = this;
                   $('#product-addtocart-button, #product-buynow-button, #bottom-tocart-button, #bottom-buynow-button')
                    .removeClass(self.options.addToCartButtonDisabledClass)
                    .prop('disabled', false);
                },


                /**
                 * @private
                 */
                _redirect: function (url) {
                    var urlParts, locationParts, forceReload;
                    const inIframe = () => window.self !== window.top;

                    urlParts = url.split('#');
                    locationParts = inIframe ? window.top.location.href.split('#') : window.location.href.split('#');
                    forceReload = urlParts[0] === locationParts[0];

                    if (inIframe) {
                        window.top.location.assign(url);
                        if (forceReload) {
                            window.location.reload();
                        }
                    } else {
                        window.location.assign(url);

                        if (forceReload) {
                            window.location.reload();
                        }
                    }
                },
            });

            return catalogAddToCart;
        };
    }
);
