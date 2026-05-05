/**
* Copyright © Magento, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'Magento_Ui/js/modal/confirm',
    'domReady!'
], function ($, Component, customerData, customerInfomation, confirm) {
    'use strict';

    let _b8Interval = null;
    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.customer = customerInfomation.customer();
        },
        initObservable: function () {
          this._super();
          
          this.reloadCustomerData(1, 6000); 
          this.showButton();
          customerInfomation.customer.subscribe(function(newValue) {
            console.log('Customer Info changed:', newValue());
          });

          $(document).on('click', '.authorization-links li:first-child > a', function(event, data) {
            event.preventDefault();
            var url = $(this).attr('href');
            // console.log('Login link clicked, Customer Info:', customerData.get('customer')(), url);
            confirm({
                title: $.mage.__('註冊會員'),
                content: $.mage.__('我們將引導你至和泰會員中心進行註冊...'),
                modalClass: 'registration-reminder-popup',
                buttons: [{
                    text: $.mage.__('確定前往'),
                    class: 'action-primary',
                    click: function () {
                        window.location.href = url;
                    }
                }]
            });
          });
         
          return this;
        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        },

        showButton: function() {
          $('#header-customer-not-logged').hide();
          // console.log('Customer Info:', typeof this.customer, (typeof this.customer == 'function') ? this.customer() : '');
          if(typeof this.customer == 'function' && this.customer()?.data_id && !this.isLogin()) {
            $('#header-customer-not-logged').removeAttr('style');
            $.removeCookie('confirm_productalert');
          }
        },

        isObjectEmpty(obj) {
          return obj && typeof obj === 'object' && !Array.isArray(obj) && Object.keys(obj).length === 0;
        },

        reloadCustomerData: function(delay, maxCounter = 5000) {
          const self = this;
          if(_b8Interval) {
            clearInterval(_b8Interval);
          }
          let counter = 0; 
          _b8Interval = setInterval(function() {
            // Stop if counter > 5000
            if(counter > maxCounter) {
              clearInterval(_b8Interval);
              self.showButton();
              var headerElem = $('#header-customer-not-logged');
              if(!self.customer && headerElem.length && !headerElem.is(':visible')) {
                headerElem.removeAttr('style');
                $.removeCookie('confirm_productalert');
              }
              return;
            }
            // Stop if header element is visible
            var headerElem = $('#header-customer-not-logged');
            var headerElem2 = $('#header-customer-logged');
            if((headerElem.length && headerElem.is(':visible')) || (headerElem2.length && headerElem2.is(':visible'))) {
              clearInterval(_b8Interval);
              self.showButton();
              return;
            }
            // ...existing code...
            if(typeof self.customer == 'function' && self.customer()?.data_id) {
              clearInterval(_b8Interval);
              self.showButton();
              customerInfomation.customer(self.customer);
            }
            self.customer = customerData.get('customer');
            counter++;
          }, delay);
        }
    });
});