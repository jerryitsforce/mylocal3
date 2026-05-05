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
                    customer_id: '',
                    customer_name: '',
                    customer_email: '',
                    phone_number: ''
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
            if (!_.isEmpty(record)) {
                this.customer_id(record.entity_id);
                this.customer_email(record.email);
                this.customer_name(record.name);
                this.phone_number(record.phone_number);
                /**
                 * modal close
                 */
                registry.get(this.parentName).closeModal();
            }
        }
    });
});
