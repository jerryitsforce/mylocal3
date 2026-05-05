define([
    "jquery"
], function ($) {
    'use strict';
    $.widget('mage.stageListing', {
        _create: function () {
            $(document).ajaxComplete(function ( event, request, settings ) {
                $("#staging-product-block-wrapper .admin__data-grid-loading-mask").hide();
            });
        }
    });
    return $.mage.stageListing;
});
