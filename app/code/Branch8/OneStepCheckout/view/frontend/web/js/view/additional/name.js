define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'mage/translate',
    'mage/validation'
], function (ko, $, Component, $t) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            return this;
        },

        onUpdate: function (newValue) {
            this._super();
            const validate =  $.validator.validateSingleElement($('#'+this.uid));
            // console.log('onUpdate',{newValue, validate});
        }
    });
});
