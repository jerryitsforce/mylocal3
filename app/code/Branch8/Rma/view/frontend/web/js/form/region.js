define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Branch8_Rma/js/model/order',
    'uiRegistry',
    'plugins/DOMPurify'
], function (ko, $, Component, customerData, order, registry, DOMPurify) {
    'use strict';
    var countryData = window.countryData;
    var cities = window.cities;

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/form/region'
        },
        regions: ko.observable([]),
        isShow: ko.observable(true),

        initialize: function () {
            var self = this;
            this._super();
            // console.log('region rendering');
            // console.log(this.regions());
            
            // this.regions.subscribe(function (newValue) {
            //     console.log('regions updated:', newValue);
            // });

            var regionData = countryData? countryData['TW'] : [];
            var regionItems = [];
            $.each(regionData, function (key, region) {
                regionItems.push({value: region.code, label: region.name, id: key});
            });
            this.regions(regionItems ?? []);

            var orderInfoData = order.info();
            // if(orderInfoData?.isConvenienceStore) {
            //     this.isShow(false);
            // } else {
            //     this.isShow(true);
            // }

            if(orderInfoData?.shipping_address_region) {
                // console.log('order info:', orderInfoData);
                $('#return_or_exchange_order_address_region').val(orderInfoData.shipping_address_region).change();
            } else {
                $('#return_or_exchange_order_address_region').val('').change();
            }

            order.info.subscribe(function (newValue) {
                // newValue?.isConvenienceStore ? self.isShow(false) : self.isShow(true);
                // console.log('order info updated:', newValue);
                if(newValue?.shipping_address_region) {
                    $('#return_or_exchange_order_address_region').val(newValue.shipping_address_region).change();
                } else {
                    $('#return_or_exchange_order_address_region').val('').change();
                }
            });

            return this;
        },

        initObservable: function () {
            this._super();

            return this;
        },

        updateValue: function () {
            // console.log('update region');
            var jQ = $.noConflict();
            var newValue = DOMPurify.sanitize(jQ('#return_or_exchange_order_address_region').val());
            var newId = DOMPurify.sanitize(jQ('#return_or_exchange_order_address_region option[value="'+newValue+'"').attr('data-id'));
            // console.log('Selected region:', newValue, newId);
                // Update the cities in the city-select component
                var citySelect = registry.get('rmaForm.rmaCity');
                if (citySelect) {
                    citySelect.cities(cities? cities[newId] : "");
                    // console.log('Updated cities:', citySelect.cities(), order.info()?.shipping_address_city);
                    $('#return_or_exchange_order_address_city').val(order.info()?.shipping_address_region? order.info()?.shipping_address_city?? '' : '').change();
                }
        }
    });
});
