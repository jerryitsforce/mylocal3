define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    $.validator.setDefaults({
        highlight: function (element) {
            var $el = $(element);
            $el.addClass('mage-error');
            $el.closest('.cart-qty-actions').addClass('error');
        },

        unhighlight: function (element) {
            var $el = $(element);
            $el.removeClass('mage-error');
            $el.parents('.cart-qty-actions').removeClass('error');
        }
    });
});
