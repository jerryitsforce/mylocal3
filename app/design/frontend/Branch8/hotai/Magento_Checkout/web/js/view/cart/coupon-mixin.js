define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'mage/url',
    'Branch8_Checkout/js/action/reloadCartItems'
], function ($, _, getTotalsAction, totals, quote, priceUtils, url, reloadCartItems) {
    'use strict';

    return function (couponTarget) {
        return couponTarget.extend({
            defaults: {
                template: 'Tigren_SplitCart/coupon',
                oldCouponCode: '',
                rules: false,
            },

            options: {
                couponFormSelector: '#coupon-form',
                couponSelector: '.cart-summary .block.discount #coupon',
                couponButtonSelector: '.cart-summary .block.discount .action',
            },

            initialize: function () {
                this._super();
                this.observe('hasCouponCodeandError discountAmount');
                this.hasCouponCodeandError(false);
                this.discountAmount('');
                this.observeAdditionalEvents();
                const self = this;
                this.initCouponAmount();
                quote.totals.subscribe(function(data){
                    self.getCouponDiscountAmount(data);
                });
            },


            ajaxCompleteRes: function(success) {
                if(success) {
                    var deferred = $.Deferred();
                    getTotalsAction([], deferred);
                    $(this.options.couponButtonSelector).removeAttr('disabled');
                    /**
                     * Reload cart item block
                     */
                    reloadCartItems();

                } else {
                    // $(self.options.couponFormSelector).find('.field').addClass('error');
                    $(this.options.couponSelector).addClass('mage-error');
                    this.hasCouponCodeandError(true);
                    $('.mage-error').text($.mage.__("The coupon code isn't valid. Verify the code and try again."));
                }
            },

            ajaxCompleteBeforeSend: function() {
                // $('body').trigger('processStart');
                $('.form.form-cart').trigger('processCartStart');
                totals.isLoading(true);
                $(this.options.couponButtonSelector).attr('disabled', true);
            },

            ajaxCompleteAlways: function() {
                // $('body').trigger('processStop');
                $(this.options.couponButtonSelector).removeAttr('disabled');
                $('.form.form-cart').trigger('processCartStop');
                totals.isLoading(false);
            },

            resetCouponCodea: function () {
                this.hasCouponCodeandError(false);
                $(this.options.couponSelector).removeClass('mage-error');
                $(this.options.couponFormSelector).find('.field').removeClass('error');
                $('.mage-error').text('');
            },

            removeCouponCode: function () {
                $(this.options.couponSelector).val('');
                this.oldCouponCode = $(this.options.couponSelector).val();
                this.resetCouponCodea();
            },

            getCouponDiscountAmount: function (totals) {
                var self = this;
                if (totals && totals.extension_attributes && totals.extension_attributes.amrule_discount_breakdown) {
                    $.each(totals.extension_attributes.amrule_discount_breakdown, function(key, value){
                        if(value.is_coupon_discount){
                            self.discountAmount(value.rule_amount);
                        }
                    });
                }
            },

            observeAdditionalEvents: function () {
                var self = this,
                    actionButtonsSelector = '.cart-summary .block.discount .actions-toolbar';

                $(document.body).on('loadCoupon', function (e, response) {
                    if (!_.isEmpty(self.oldCouponCode) && _.isEmpty(response.coupon_code)) {
                        self.couponCode(self.oldCouponCode);
                        $(self.options.couponSelector).focus();
                    }
                });

                $(document).on('focus keyup', self.options.couponSelector, function () {
                    if ($(this).is(':placeholder-shown')) {
                        // $(actionButtonsSelector).hide();
                        $(self.options.couponSelector).attr('placeholder', '');
                    } else {
                        $(actionButtonsSelector).show();
                        $(self.options.couponSelector).attr('placeholder', $(self.options.couponSelector).attr('data-placeholder'));
                    }
                });

                $(document).on('keyup', self.options.couponSelector, function () {
                    self.hasCouponCodeandError(false);
                });

                $(document).on('focusout', self.options.couponSelector, function () {
                    $(actionButtonsSelector).show();
                    $(self.options.couponSelector).attr('placeholder', $(self.options.couponSelector).attr('data-placeholder'));
                });

                $(document).on('click', '.cart-summary .block.discount .no-focus', function () {
                    $(self.options.couponSelector).blur();
                });
            },

            /**
             * Store submitted coupon code to display when error message is returned
             */
            applyCouponCode: function () {
                var self = this;
                self.oldCouponCode = $(self.options.couponSelector).val();
                self.resetCouponCodea();

                // this._super();
                $.ajax({
                    url: url.build('checkout/cart/couponPost'),
                    data: {
                        coupon_code: $('#coupon').val()
                    },
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: () => self.ajaxCompleteBeforeSend(),
                    success: (res) => self.ajaxCompleteRes(res.success)
                }).always(function () {
                    self.ajaxCompleteAlways();
                });
            },

            cancelCouponCode: function () {
                var self = this;
                $.ajax({
                    url: url.build('checkout/cart/couponPost'),
                    data: {
                        remove: 1
                    },
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: () => self.ajaxCompleteBeforeSend(),
                    success: (res) => self.ajaxCompleteRes(res.success)
                }).always(function () {
                    self.ajaxCompleteAlways();
                });
            },
            initCouponAmount: function (){
                var totalFromQuote = quote.totals();
                var self = this;
                if (totalFromQuote && totalFromQuote.extension_attributes && totalFromQuote.extension_attributes.amrule_discount_breakdown) {
                    $.each(totalFromQuote.extension_attributes.amrule_discount_breakdown, function(key, value){
                        if(value.is_coupon_discount){
                            self.discountAmount(value.rule_amount);
                        }
                    });
                }
            }
        });
    };
});
