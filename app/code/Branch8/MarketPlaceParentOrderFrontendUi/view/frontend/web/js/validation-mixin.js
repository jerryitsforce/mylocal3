define([
    'jquery'
], function($) {
    'use strict';

    return function (targetWidget) {
        $.widget('mage.validation', targetWidget, {

            /**
             * Remove the Scroll and Focus on first invalid form field actions from Return/Exchange form
             */
            listenFormValidateHandler: function (event, validation) {
                if ($(event.currentTarget).attr('id') === 'wk_new_rma_form') {
                    return false;
                }

                this._super(event, validation);
            }
        });

        return $.mage.validation;
    }
});
