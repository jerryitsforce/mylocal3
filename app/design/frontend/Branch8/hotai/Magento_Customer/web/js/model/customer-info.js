define([
    'ko',
    'Magento_Customer/js/customer-data'
], function (ko, customerData) {
    'use strict';

    var customerInfo = {
        customer: ko.observable(customerData.get('customer')),
    };

    return customerInfo;
});