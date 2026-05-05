define([
    'uiComponent',
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'mage/url'
], function (Component, $, customerData, alert, urlBuilder) {
    'use strict';

    return Component.extend({
        /**
         *
         */
        initialize: function () {
            this._super();
        },
        /**
         *
         */
        askQuestion: function () {
            const customer = customerData.get('customer')();
            if (!customer || !customer.firstname) {
                alert({
                    modalClass: 'prd-discussion-confirm-modal',
                    title: $.mage.__('Please log in before asking a question.'),
                    content: $.mage.__('為了確保您能收到回覆通知，請先登入會員後再進行商品提問'),
                    buttons: [
                        {
                            text: $.mage.__('Cancel'),
                            class: 'action action-cancel',

                            /**
                             * Click handler.
                             */
                            click: function () {
                                this.closeModal(true);
                            }
                        },
                        {
                            text: $.mage.__('Go to Login/Register'),
                            class: 'action-primary action-accept',

                            /**
                             * Click handler.
                             */
                            click: function () {
                                window.location.href = urlBuilder.build('customer/account/login');
                            }
                        },
                    ]
                });
                return;
            }
            window.location.href = this.configurations.askForQuestionUrl;
        }
    });
});
