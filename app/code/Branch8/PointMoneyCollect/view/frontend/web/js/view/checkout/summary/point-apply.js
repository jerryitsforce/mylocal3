define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Ui/js/model/messageList',
    'mage/validation'
], function ($, ko, Component, quote, priceUtils, totals, getTotalsAction, fullScreenLoader, defaultTotal, cartCache, $t, getPaymentInformationAction, messageList) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_PointMoneyCollect/checkout/summary/point-apply'
        },
        pointToUse: ko.observable(window.checkoutConfig.point_discount_info.used_point),
        messageClass: ko.observable(''),
        messageText: ko.observable(''),
        totals: quote.getTotals(),
        isApplied: ko.observable(false),
        hasError: ko.observable(false),
        isAllowSubmit: ko.observable(false),

        initialize: function () {
            this._super();

            var checkoutAddressData = window.checkoutConfig.checkoutAddress;
            if (checkoutAddressData) {
                var parsedData = checkoutAddressData;
                if (parsedData && parsedData.appliedPoint) {
                    this.pointToUse(parsedData.appliedPoint);
                    this.applyPoint();
                }
            }

            if(this.pointToUse() !== null){
                this.isApplied(true);
            }
            this.observeAdditionalEvents();
            return this;
        },

        observeAdditionalEvents: function () {
            var self = this,
                inputSelector = '#point_input';
            let maxAllowed = self.getCustomerPointRaw();

            $(document).on('focus keyup', inputSelector, function () {
                if ($(this).is(':placeholder-shown')) {
                    $(inputSelector).attr('placeholder', '');
                    self.isAllowSubmit(true);
                } else {
                    $(inputSelector).attr('placeholder', $(inputSelector).attr('data-placeholder'));
                    let numericValue = parseFloat($(inputSelector).val()) || 0;
                    if (numericValue > maxAllowed) {
                        self.pointToUse(maxAllowed);
                    }
                }
            });

            $(document).on('input', inputSelector, function () {
               this.value = this.value.replace(/\D/g, '');
            });

            $(document).on('keyup', inputSelector, function () {
                self.hasError(false);
            });

            $(document).on('blur', inputSelector, function () {
                if((self.pointToUse() === null || $(this).val() === '') && !self.hasError()){
                    self.isAllowSubmit(false);
                    $(inputSelector).attr('placeholder', $(inputSelector).attr('data-placeholder'));
                    self.applyPoint(0);
                } else {
                    // console.log('pointToUse', self.pointToUse());
                    self.applyPoint();
                }
            });
        },

        getPointRange: function(){
            if(this.totals()['extension_attributes']['point_range'] != undefined){
                var pointRangeToUse = this.totals()['extension_attributes']['point_range'];
                return pointRangeToUse[0] == pointRangeToUse[1] ?
                    pointRangeToUse[0].toLocaleString('zh-TW') :
                    pointRangeToUse[0].toLocaleString('zh-TW') + ' - ' + pointRangeToUse[1].toLocaleString('zh-TW');
            }
            return '0';
        },

        getCustomerPointRaw: function () {
            if (window.checkoutConfig.point_discount_info &&
                window.checkoutConfig.point_discount_info.customer_point !== undefined) {

                return parseFloat(window.checkoutConfig.point_discount_info.customer_point) || 0;
            }
            return 0;
        },

        getCustomerPoint: function(){
           return this.getCustomerPointRaw().toLocaleString('zh-TW');
        },

        resetPoint: function(){
            var inputSelector = '#point_input';
            $('.point-right').removeClass('error').removeClass('success');
            this.isAllowSubmit(false);
            this.hasError(false);
            this.messageClass('');
            this.messageText('');
            $(inputSelector).val('');
        },

        applyPoint: function(point = null){
            $('.point-right').removeClass('error').removeClass('success');
            if(parseFloat(this.pointToUse()) == parseFloat(window.checkoutConfig.point_discount_info.used_point)){
                return;
            }
            //call ajax to apply
            fullScreenLoader.startLoader();
            const pointUsed = point !== null ? point : this.pointToUse();
            $.ajax({
                method: "POST",
                url: "/pointmoneycollect/cart/apply/point/" + pointUsed,
                async: false,
                dataType: "json",
            }).done(function( applyPointData ) {
                fullScreenLoader.stopLoader();
                if(applyPointData == null){
                    //show generate error
                    this.showMessage('error', $t('An error occurred, please try again.'));
                }else{
                    if(applyPointData.success) {
                        //reload payment block
                        getPaymentInformationAction();
                        //reload total bolck
                        cartCache.set('totals', null);
                        defaultTotal.estimateTotals();

                        this.showMessage('success', '');
                        window.checkoutConfig.point_discount_info.used_point = parseFloat(this.pointToUse());
                    }else{
                        //show exactly message
                        this.showMessage('error', applyPointData.msg);
                    }
                }
            }.bind(this));

        },

        showMessage: function(type, msg){
            var self = this;
            $('.point-right').addClass(type);
            if(type == 'success'){
                self.hasError(false);
                self.isApplied(true);
                this.messageClass('mage-success');
            }else{
                self.hasError(true);
                this.messageClass('mage-error');
            }
            this.messageText(msg);
        }
    });
});
