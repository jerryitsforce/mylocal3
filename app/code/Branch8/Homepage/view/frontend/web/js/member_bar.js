define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko'
], function (Component, customerData, ko) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            var branch8Data = customerData.get('branch8_customer_data');

            this.memberBar = ko.pureComputed(function() {
                var data = branch8Data();
                return (data && data.member_bar) ? data.member_bar : {};
            });
            this.time = function(){
                const d = new Date();
                let hour = d.getHours();
                if (hour < 12) {
                    return "good morning";
                } else if (hour < 17) {
                    return "good afternoon";
                } else {
                    return "good evening";
                }
            }
        }
    });
});