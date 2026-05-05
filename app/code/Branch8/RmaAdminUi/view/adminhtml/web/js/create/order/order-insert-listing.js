/**
 * @api
 */
define([
    'underscore',
    'jquery',
    'Magento_Ui/js/form/components/insert-listing',
    'uiRegistry'
], function (_, $, InsertListing, registry) {
    'use strict';
    return InsertListing.extend({
        defaults: {
            imports: {
                onSelectedChange: '${ $.selectionsProvider }:selectedRow'
            },
            exports: {}
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe({
                    order_id: '',
                    order_increment_id: '',
                    customer_name: '',
                    customer_phone: '',
                    shipping_zipcode: '',
                    shipping_city: '',
                    shipping_region: '',
                    shipping_address_0: '',
                    shipping_address_1: '',
                    shipping_address_2: ''
                });

            return this;
        },
        /**
         * Updates externalValue every time row is selected,
         * if it is configured by 'dataLinks.imports'
         * Also suppress dataLinks so import/export of selections will not activate each other in circle
         *
         */
        onSelectedChange: function (record) {
            this.setExternalValue(record);
            if (_.isEmpty(record) === false) {
                const specialFragment = /[^a-zA-Z0-9 ]/g;
                this.order_id(record.entity_id);
                this.order_increment_id(record.increment_id);
                this.customer_name(record.customer_name);
                this.shipping_zipcode(record.shipping_zipcode ? record.shipping_zipcode.replace(specialFragment, "") : "");
                this.shipping_region(record.shipping_region || "");
                this.shipping_city(record.shipping_city || "");
                if (!record.shipping_address.trim()) {
                    this.closeModal();
                    return;
                }
                const lines = record.shipping_address.split('\n');
                if (lines.length) {
                    for (let i = 0; i < 1; i++) {
                        let addressLine = lines[i];
                        if (record.shipping_region && addressLine) {
                            addressLine = this.removeTextFromAddress(addressLine, record.shipping_region);
                        }
                        if (record.shipping_city && addressLine) {
                            addressLine = this.removeTextFromAddress(addressLine, record.shipping_city);
                        }
                        if (record.shipping_zipcode && addressLine) {
                            addressLine = this.removeTextFromAddress(addressLine, record.shipping_zipcode ? record.shipping_zipcode.replace(specialFragment, "") : "");
                        }
                        this['shipping_address_' + i](addressLine);
                        /*      if(!record.shipping_region || !record.shipping_region || )*/
                    }
                }
                /**
                 * modal close
                 */
                this.closeModal();
            }
        },
        /**
         *
         */
        closeModal: function () {
            return registry.get(this.parentName).closeModal();
        },
        /**
         *
         * @param address
         * @param region
         * @returns {*}
         */
        removeTextFromAddress: function (address, region) {
            return address.replace(region, "").replace(/,+/g, ',').replace(/^,|,$/g, '');
        }
    });
});
