define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, getTotalsAction, fullScreenLoader) {
    'use strict';

    return function (config, element) {
        $(element).click(function () {
            var point = $('#point-money-collect-input').val();
            if (point && !isNaN(point)) {
                fullScreenLoader.startLoader();
                $.ajax({
                    url: config.url,
                    data: {point: point},
                    type: 'post',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            var deferred = $.Deferred();
                            getTotalsAction([], deferred);
                            $.when(deferred).done(function() {
                                fullScreenLoader.stopLoader();
                            });
                        } else {
                            fullScreenLoader.stopLoader();
                            alert(response.error_message);
                        }
                    },
                    error: function () {
                        fullScreenLoader.stopLoader();
                        alert('Something went wrong.');
                    }
                });
            }
        });
    };
});
