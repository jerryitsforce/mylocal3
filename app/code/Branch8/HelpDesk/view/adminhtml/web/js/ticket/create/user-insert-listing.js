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
                    user_id: '',
                    username: '',
                    email: '',
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
                this.user_id(record.user_id);
                this.username(record.email);
                this.email(record.email);
                /**
                 * modal close
                 */
                registry.get(this.parentName).closeModal();
            }
        }
    });
});
