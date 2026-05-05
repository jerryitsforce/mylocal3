/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data'
],function ($, modal, customerData) {
    'use strict';

    return function (config, element) {
        let order_id = config.order_id,
            options = {
                type: 'popup',
                responsive: true,
                modalClass: 'order-modal cancel-order-modal',
                title: $.mage.__('Failed Delivery Reason'),
                buttons: [
                    {
                        text: $.mage.__('Think Again'),
                        class: 'action-secondary action-dismiss close-modal-button',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                    text: $.mage.__('Failed Delivery'),
                    class: 'action-primary action-accept failed-delivery-order-button',

                    /** @inheritdoc */
                    click: function () {
                        let thisModal = this,
                            reason_description = $('#failed-delivery-description-' + order_id).val();

                        $.ajax({
                            showLoader: true,
                            type: 'POST',
                            url: `${config.url}b8marketplace/order/faileddelivery`,
                            dataType: 'json',
                            data: {
                                'order_id': config.order_id,
                                'id': config.order_id,
                                'reason_description': reason_description
                            },
                            complete: function (response) {
                                location.reload();
                                // let type = 'success',
                                //     message;

                                // if (response.responseJSON.success) {
                                //     message = $.mage.__(response.responseJSON.message);
                                //     location.reload();
                                // } else {
                                //     type = 'error';
                                //     message = $.mage.__(response.responseJSON.message);
                                // }

                                setTimeout(function () {
                                    customerData.set('messages', {
                                        messages: [{
                                            text: message,
                                            type: type
                                        }]
                                    });
                                }, 500);
                                thisModal.closeModal(true);
                            }
                        });
                    }
                }]
            };

        $(element).on('click', function () {
            $('#failed-delivery-modal-' + order_id).modal('openModal');
        });

        modal(options, $('#failed-delivery-modal-' + order_id));
    };
});
