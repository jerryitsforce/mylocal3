/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    "jquery/ui"
], function ($, $t, mageTemplate, alert) {
    'use strict';
    $.widget('mage.familyBonus', {
        options: {
            backUrl: ''
        },
        _create: function () {
            var self = this;
            var indexValue = 0;
            var familyBonus = $.parseJSON(self.options.familyBonus);
            if ($.isArray(familyBonus)) {
                $(document).ajaxComplete(function ( event, request, settings ) {
                    $("#family-bonus-block-loader").hide();
                    $("#family-bonus-block-wrapper").show();
                });
            }
        }
    });
    return $.mage.familyBonus;
});

