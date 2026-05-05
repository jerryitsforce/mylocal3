define([
    "jquery",
    "Magento_Ui/js/modal/alert",
    "mage/translate",
    "jquery/ui"
], function ($, alert, $t) {
    "use strict";

    $.widget('WebkulMpBuyerSellerChatCustomer.testconnection', {
        /**
         *
         */
        options: {
            ajaxUrl: '',
            testConnection: '#buyer_seller_chat_general_settings_test_connection',
            host: '#buyer_seller_chat_general_settings_host_name',
            port: '#buyer_seller_chat_general_settings_port_number',
        },
        /**
         *
         * @private
         */
        _create: function () {
            var self = this;

            $(this.options.testConnection).click(function (e) {
                e.preventDefault();
                self._ajaxSubmit();
            });
        },
        /**
         *
         * @private
         */
        _ajaxSubmit: function () {
            $.ajax({
                url: this.options.ajaxUrl,
                data: {
                    host: $(this.options.host).val(),
                    port: $(this.options.port).val()
                },
                dataType: 'json',
                showLoader: true,
                success: function (result) {
                    alert({
                        title: result.status ? $t('Success') : $t('Error'),
                        content: result.content
                    });
                }
            });
        }
    });

    return $.WebkulMpBuyerSellerChatCustomer.testconnection;
});
