define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert'
], function ($, urlBuilder, $t, confirm, alert) {
    'use strict';

    return function (target) {
        return target.extend({
            initialize: function () {
                $('body').delegate('.temp-edit', 'click', function () {
                    var $url = $(this).attr('data-url');
                    confirm({
                        content: $t("Are you sure you want to edit this product ?"),
                        actions: {
                            confirm: function () {
                                window.location = $url;
                            },
                            cancel: function () {
                                return false;
                            }
                        }
                    });
                });
                $('body').on('click', '.mp-edit', function () {
                    var $this = $(this),
                        productId = $this.attr('data-product-id'),
                        ajaxUrl = urlBuilder.build('marketplacectrl/product/editpermission');

                    $.ajax({
                        url: ajaxUrl,
                        data: {product_id: productId},
                        showLoader: true,
                        type: 'POST',
                        dataType: 'json',
                        success: function (response) {
                            if (response.status) {
                                if (response.draft) {
                                    confirmDialog(
                                        $this.attr('data-url-draft'),
                                        $t('This product already has a draft. Do you want to delete the existing draft and create a new one?')
                                    );
                                } else {
                                    confirmDialog(
                                        $this.attr('data-url'),
                                        $t('Are you sure you want to edit this product ?')
                                    );
                                }
                            } else {
                                alertDialog($t('Editing is currently disabled for this product'));
                            }
                        },
                        error: function () {
                            alertDialog($t('Editing is currently disabled for this product'));
                        }
                    });

                    /**
                     * Show a confirmation dialog with specified content and actions.
                     *
                     * @param {string} url - URL for redirection after confirmation.
                     * @param {string} content - Content for the confirmation dialog.
                     */
                    function confirmDialog(url, content) {
                        confirm({
                            content: content,
                            actions: {
                                confirm: function () {
                                    window.location = url;
                                },
                                cancel: function () {
                                    return false;
                                }
                            }
                        });
                    }

                    /**
                     * Show an alert dialog with specified content and actions.
                     *
                     * @param {string} content - Content for the alert dialog.
                     */
                    function alertDialog(content) {
                        alert({
                            content: content,
                            actions: {
                                confirm: function () {
                                    return false;
                                }
                            }
                        });
                    }
                }).on('click', '.mp-delete', function () {
                    var $url = $(this).attr('data-url');
                    confirm({
                        content: $t("Are you sure you want to delete this product ?"),
                        actions: {
                            confirm: function () {
                                window.location = $url;
                            },
                            cancel: function () {
                                return false;
                            }
                        }
                    });
                });
            }
        });
    };
});
