/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    "jquery/ui"
], function ($, $t, mageTemplate, alert) {
    'use strict';
    $.widget('mage.generalTicket', {
        options: {
            backUrl: ''
        },
        _create: function () {
            var self = this;
            var indexValue = 0;
            var relatedProductData = $.parseJSON(self.options.generalTicket);
            if ($.isArray(relatedProductData)) {
                $(document).ajaxComplete(function ( event, request, settings ) {
                    $("#general-ticket-block-loader").hide();
                    $("#general-ticket-block-wrapper").show();
                });
            }
        }
    });
    return $.mage.generalTicket;
});

