define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'rjsResolver'
], function ($, Select, $do, rjsResolver) {
    'use strict';
    return Select.extend({
        defaults: {
            customName: '${ $.parentName }.${ $.index }_input'
        },
        /**
         * Change currently selected option
         *
         * @param {String} id
         */
        selectOption: function (id) {
            rjsResolver(function () {
                $do.get('div[data-index="notification_type"]', function (elem) {
                    var selectedValue = $("#" + id).val();
                    console.log(selectedValue);
                    if (selectedValue === 'order_status_update') {
                        $('div[data-index="order_status"]').show();
                        $('div[data-index="order_status_review"]').hide();
                        $('div[data-index="set_abandoned_cart_time"]').hide();
                        $('div[data-index="redirect_url"]').hide();
                        $('div[data-index="url_key"]').hide();
                    } else if (selectedValue === 'abandoned_cart_reminds') {
                        $('div[data-index="order_status"]').hide();
                        $('div[data-index="order_status_review"]').hide();
                        $('div[data-index="set_abandoned_cart_time"]').show();
                        $('div[data-index="redirect_url"]').show();
                        $('div[data-index="url_key"]').hide();
                    } else if (selectedValue === 'review_reminders') {
                        $('div[data-index="order_status"]').hide();
                        $('div[data-index="order_status_review"]').show();
                        $('div[data-index="set_abandoned_cart_time"]').hide();
                        $('div[data-index="redirect_url"]').show();
                        $('div[data-index="url_key"]').hide();
                    } else if (selectedValue !== 'order_status_update'
                        && selectedValue !== 'abandoned_cart_reminds'
                        && selectedValue !== 'review_reminders'
                        && selectedValue !== 'return_exchange'
                        && selectedValue !== undefined
                        && selectedValue !== ''
                    ) {
                        $('div[data-index="order_status"]').hide();
                        $('div[data-index="order_status_review"]').hide();
                        $('div[data-index="set_abandoned_cart_time"]').hide();
                        $('div[data-index="redirect_url"]').hide();
                        $('div[data-index="url_key"]').show();
                    } else {
                        $('div[data-index="order_status"]').hide();
                        $('div[data-index="order_status_review"]').hide();
                        $('div[data-index="set_abandoned_cart_time"]').hide();
                        $('div[data-index="redirect_url"]').hide();
                        $('div[data-index="url_key"]').hide();
                    }
                });
            });
        },
    });
});
