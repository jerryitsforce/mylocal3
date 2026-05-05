/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/* @api */
define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Branch8_OneStepCheckout/js/model/payment',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'mage/translate'
], function ($, ko, _, Component, additionalValidators, checkPayment, url, $t, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_HotaiPay/payment/form',
            transactionResult: '',
            creditCardList: ''
        },
        redirectAfterPlaceOrder: false,
        creditCardDetailData: ko.observable(null),
        coBranded: ko.observable(''),
        initialize: function () {
            this._super()
                .initChildren();
            var creditCardDetailData = window.checkoutConfig.payment.hotaipay?.creditCardDetailData;
            
            var cardList = [];
            _.map(creditCardDetailData, function(value, key) {
                 // Set default credit card
                 if (_.isEmpty(this.creditCardList())) {
                    // var creditCartData = JSON.parse(value);
                    if (value['DefaultCard']) {
                        // console.log('default card', value['DefaultCard']);
                        this.creditCardList(key);
                    }
                }
                cardList.push(value);
            }.bind(this));

            if(cardList) {
                // console.log(cardList);
                this.creditCardDetailData(cardList);
            }

            const self = this;
            checkPayment.selectedHotaiPayCard.subscribe(function(value){
                // console.log('selectedHotaiPayCard', value);
                if(value) {
                    const isCoBranded = self.checkCoBranded(value);
                    if(isCoBranded){
                        self.coBranded(`Hotai Co-Branded Card + ${value['CardType']}`);
                    } else {
                        self.coBranded('');
                    }
                } else {
                    self.coBranded('');
                }
            });
        },

        afterPlaceOrder: function () {
            $.mage.redirect('/hotaipay/payment/checkout');
        },

        initObservable: function () {
            this._super()
                .observe([
                    'creditCardList'
                ]);

            return this;
        },

        getCode: function() {
            return 'hotaipay';
        },

        getData: function() {
            // console.log('getData', {
            //     'method': this.getCode(),
            //     'additional_data': {
            //         'creditcard_token_id': this.creditCardList(),
            //         'creditcard_detail_data': this.getCreditCardListsData(this.creditCardList())
            //     }
            // });
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'creditcard_token_id': this.creditCardList(),
                    'creditcard_detail_data': this.getCreditCardListsData(this.creditCardList())
                }
            };

            return data;
        },

        getCreditCardLists: function() {
            var creditCardLists = window.checkoutConfig.payment.hotaipay.creditCardLists;
            return _.map(creditCardLists, function(value, key) {
                // Set default credit card
                if (_.isEmpty(this.creditCardList()) && !_.isEmpty(this.getCreditCardListsData(key))) {
                    var creditCartData = JSON.parse(this.getCreditCardListsData(key));
                    if (creditCartData['DefaultCard']) {
                        this.creditCardList(key);
                    }
                }

                return {
                    'value': key,
                    'creditcard_list': value
                }
            }.bind(this));
        },

        getCreditCardListsData: function(key){
            var creditCardDetailData = window.checkoutConfig.payment.hotaipay.creditCardDetailData;

            if(!creditCardDetailData[key]){
                return '';
            }
            return  JSON.stringify(creditCardDetailData[key]);
        },

        setupCreditCardUrl: function(){
            var url = window.location.origin + '/hotaipay/creditcard/add';
            return url;
        },

        setRedirectUrl: function(){
            var redirectUrl = window.location;
            return redirectUrl;
        },

        getSetupCreditCardValue: function(){
            return $.mage.__("Setup HotaiPay Payment Credit Card");
        },

        getDefaultClass: function(isDefault){
            return isDefault? 'default' : '';
        },

        getCardTypeClass: function(cardType){
            return cardType? cardType.toLowerCase() : '';
        },

        getCardNoMask: function(cardNoMask){
            return cardNoMask.replace(/-/g, " ");
        },

        isValidCard: function(card){
            return card['IsAvailable'] && !card['IsOverwrite'];
        },

        getCardInvalidClass: function(card){
            return !this.isValidCard(card)? 'invalid' : '';
        },

        selectOption: function(value){
            // console.log(typeof value);
            // console.log('selectOption',  value);
            checkPayment.isSelectedPaymentMethod(true);
            checkPayment.selectedHotaiPayCard(value);
        },

        getCoBranded: function(){
            return this.coBranded?? '';
        },

        checkCoBranded: function(card){
            var hotaiAffinityCode = [8686, 8687, 8688, 8689, 8690];
            console.log('checkCoBranded', this.coBranded());
            if(!card){
                return false;
            }
            if(hotaiAffinityCode.includes(parseInt(card['AffinityCode']))){
                return true;
            }
            return false;
        },
    });
});
