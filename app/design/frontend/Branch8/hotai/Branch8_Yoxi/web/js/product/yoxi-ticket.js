/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    "jquery/ui"
], function ($, $t, mageTemplate, alert) {
    'use strict';
    $.widget('mage.yoxiTicket', {
        options: {
            backUrl: ''
        },
        _create: function () {
            var self = this;
            var indexValue = 0;
            var relatedProductData = $.parseJSON(self.options.yoxiTicket);
            if ($.isArray(relatedProductData)) {
                $(document).ajaxComplete(function ( event, request, settings ) {
                    $("#yoxi-ticket-block-loader").hide();
                    $("#yoxi-ticket-block-wrapper").show();
                });
            }
        }
    });
    return $.mage.yoxiTicket;
});
