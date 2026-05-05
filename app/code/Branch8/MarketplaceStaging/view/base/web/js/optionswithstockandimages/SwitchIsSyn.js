define([
    "jquery",
    'mage/url',
    'Magento_Ui/js/modal/alert'
], function ($, urlBuilder, alert) {
    /**
     *
     */
    return function (args, syncUrl, successCallback, errorCallback) {
        let result;
        syncUrl = syncUrl || urlBuilder.build('marketplacestaging/variation/getdatasync');
        try {
            result = $.ajax({
                type: "GET",
                url: syncUrl,
                data: args,
                cache: false,
                success: function (response) {
                    if (successCallback) {
                        successCallback(response);
                    }

                },
                /**
                 *
                 * @param response
                 */
                error: function (response) {
                    if (errorCallback) {
                        errorCallback(response);
                    }
                }
            });
            return result;
        } catch (error) {
            console.error(error);
        }
    }
})
