/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    "jquery/ui"
], function ($, $t, mageTemplate, alert) {
    'use strict';
    $.widget('mage.generalNonTicket', {
        options: {
            backUrl: ''
        },
        _create: function () {
            var self = this;
            var indexValue = 0;
            var generalNonTicket = $.parseJSON(self.options.generalNonTicket);
            if ($.isArray(generalNonTicket)) {
                $(document).ajaxComplete(function ( event, request, settings ) {
                    $("#general-non-ticket-block-loader").hide();
                    $("#general-non-ticket-block-wrapper").show();
                });
            }
        }
    });
    return $.mage.generalNonTicket;
});

