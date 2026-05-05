define([
    'ko',
    'jquery',
    'uiComponent',
    'Branch8_Rma/js/model/order',
    'uiRegistry'
], function (ko, $, Component, order, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/form/city'
        },

        cities: ko.observable([]),
        isShow: ko.observable(true),

        initialize: function () {
            var self = this;
            this._super();
            // console.log('city rendering');
            // console.log(window.cities);

            this.cities.subscribe(function (newValue) {
                // console.log('cities updated:', newValue);
                $('#return_or_exchange_order_address_city').val(order.info()?.shipping_address_region? order.info()?.shipping_address_city?? '' : '').change();
            });
            
            // var orderInfoData = order.info();
            // if(orderInfoData?.isConvenienceStore) {
            //     this.isShow(false);
            // } else {
            //     this.isShow(true);
            // }

            // order.info.subscribe(function (newValue) {
            //     newValue?.isConvenienceStore ? self.isShow(false) : self.isShow(true);
            // });

            return this;
        },
    });
});