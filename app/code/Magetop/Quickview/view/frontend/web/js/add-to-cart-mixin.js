define([
        'jquery',
        'mage/translate',
        'underscore',
        'Magento_Catalog/js/product/view/product-ids-resolver',
        'plugins/DOMPurify',
        'jquery-ui-modules/widget'
    ],
    function ($, $t, _, idsResolver, DOMPurify) {
        'use strict';

        return function (catalogAddToCart) {
            $.widget('mage.catalogAddToCart', catalogAddToCart, {
                ajaxSubmit: function (form) {
                    var self = this,
                        productIds = idsResolver(form),
                        formData;
                    let link = '';
                    var jQ = $.noConflict();
                    if (self.element.context) {
                        link = self.element.context.baseURI;
                    }
                    $(self.options.minicartSelector).trigger('contentLoading');
                    self.disableAddToCartButton(form);
                    formData = new FormData(form[0]);

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
                                if (link) {
                                    res.backUrl = link;
                                    self._redirect(res.backUrl);
                                }
                                return;
                            }

                            if (res.messages) {
                                const sanitizedMessages = DOMPurify.sanitize(res.messages, {
                                    ALLOWED_TAGS: ['div', 'ol', 'ul', 'li', 'img', 'p', 'span', 'b', 'strong'], 
                                    ALLOWED_ATTR: ['style', 'class', 'src', 'width', 'height', 'alt', 'title', 'href']
                                });
                                jQ(self.options.messagesSelector).html(sanitizedMessages);
                            }

                            if (res.minicart) {
                                const sanitizedMinicart = DOMPurify.sanitize(res.minicart, {
                                    ALLOWED_TAGS: ['div', 'ol', 'ul', 'li', 'img', 'p', 'span', 'b', 'strong'], 
                                    ALLOWED_ATTR: ['style', 'class', 'src', 'width', 'height', 'alt', 'title', 'href']
                                }); 
                                const safeContainer = `<div class="safe-minicart">${sanitizedMinicart}</div>`;

                                jQ(self.options.minicartSelector).replaceWith(safeContainer);
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
                        }
                    });
                }
            });

            return catalogAddToCart;
        };
    }
);
