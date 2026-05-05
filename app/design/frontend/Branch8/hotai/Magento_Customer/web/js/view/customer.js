/**
* Copyright © Magento, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'domReady!'
], function ($, Component, customerData, customerInfomation) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.customer = customerInfomation.customer();
            this.showButton();
        },

        initObservable: function () {
            const self = this;
            this._super();
            customerInfomation.customer.subscribe(function(newValue) {
              console.log('Customer Info data changed:', newValue(), self.customer());
              self.customer = newValue;
              self.showButton();
            });
            this.showButton();
            return this;
        },

        showButton: function() {
          $('#header-customer-logged').hide();
          console.log('Customer Info:', typeof this.customer, (typeof this.customer == 'function')? this.customer() : '');
          if(typeof this.customer == 'function' && this.customer()?.data_id && this.isLogin()) {
            $('#header-customer-logged').removeAttr('style');
            $.removeCookie('confirm_productalert');

            // show hotaipoints info on header
            var customerInfo = customerData.get('customer')();
            // console.log('Customer Info:', customerInfo);
            $('#header-hotaipoints').removeAttr('style');
            $('.header-right').addClass('showing');
            $('#header-dropdown-hotaipoints').removeAttr('style');
            if(customerInfo?.customer_point_formated) {
                $('#header-hotaipoints .hotai-customer-points > span').text(customerInfo?.customer_point_formated);
                $('#header-dropdown-hotaipoints .hotai-totalpoints').text(customerInfo?.customer_point_formated);
            }

          }
        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        }
    });
});