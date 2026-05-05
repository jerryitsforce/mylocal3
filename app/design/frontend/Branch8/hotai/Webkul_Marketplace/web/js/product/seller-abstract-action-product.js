/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    "jquery/ui",
    'mage/calendar',
    './validateProduct',
    './additional-validators'
], function ($, $t, alert, DOMPurify, ui, calendar, validateProduct, addtionnalValidators) {
    'use strict';

    $.widget('mage.sellerAbstractAction', {
        options: {
            errorMessageSku: $t("SKU can\'t be left empty"),
            ajaxErrorMessage: $t('There was error during fetching results.'),
            ajaxValidateError:$t('Something went wrong when validate product'),
            validateUrl: null,
            productid: 0
        },
        alowSubmit: false,
        /**
         *
         * @param state
         * @returns {mage.sellerEditProduct}
         */
        enableDisableButtons: function (state) {
            if (state) {
                $('.button').css({
                    'opacity': '1',
                    'cursor': 'pointer',
                }).removeAttr('disabled', 'disabled');
            } else {
                $('.button').css({
                    'opacity': '0.7',
                    'cursor': 'default',
                }).attr('disabled', 'disabled');
            }
            return this;
        },
        /**
         *
         * @param res
         * @TODO refactor
         */
        handleErrorMessage: function (res) {
            if (res.error && res.message && res.code === 'INVALIDATE_IMAGE_TAGS') {
                alert({
                    content: res.message
                })
            }
        },
        /**
         *
         * @returns {Promise<*|jQuery>}
         */
        validate: async function () {
            const self = this,
                validateUrl = self.options.validateUrl,
                formArray = $(self.formElement).serializeArray(),
                form = $(self.formElement),
                jsonData = {};
            $.each(formArray, function (_, field) {
                jsonData[field.name] = field.value;
            });
            let isValid = self.formElement.valid();
            const additionaValid =  addtionnalValidators.validate(form, jsonData, false);
            console.log({
                additionaValid:additionaValid,
            })
            if (!additionaValid || !isValid) {
                return false;
            }

            if (isValid) {
                if ($('#description_ifr').length) {
                    var desc = document.getElementById('description_ifr').contentWindow.document.body.innerHTML;
                    $('#description-error').remove();
                    if (desc === "" || desc === null) {
                        $('#description-error').remove();
                        $('#description').parent().append('<div class="mage-error" generated="true" id="description-error">This is a required field.</div>');
                    }
                }
                if ($("#product_options_container").length && $("#product_options_container").find(':input:disabled').length) {
                    $("#product_options_container").find(':input:disabled').removeAttr('disabled');
                }
                $("#qty").length ? $("#qty").removeAttr('disabled') : '';
                $("#is_in_stock").length ? $("#is_in_stock").removeAttr('disabled') : '';

                if (validateUrl) {
                    const respone = await validateProduct(validateUrl, jsonData);
                    if (respone.code != 200) {
                        isValid = false;
                        alert({
                            content: self.options.ajaxValidateError
                        })

                    } else if (respone?.res?.error && respone.res.error === true) {
                        isValid = false;
                        self.handleErrorMessage(respone.res);
                    }
                }
            }
            return isValid;
        },
        /**
         *
         * @private
         */
        _create: function () {
            const self = this;
            this.formElement = $(this.options.formElement);
            this.formElement.off('submit').on('submit', async function (e) {
                if (self.allowSubmit) {
                    return;
                }
                e.preventDefault();
                self.allowSubmit = await self.validate();
                if (self.allowSubmit) {
                    self.enableDisableButtons(false);
                    this.submit();
                } else {
                    self.enableDisableButtons(true);
                }
            });
        }
    });
    return $.mage.sellerAbstractAction;
});
