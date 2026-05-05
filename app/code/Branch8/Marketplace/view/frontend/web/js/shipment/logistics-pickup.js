/**
 * Branch8 Marketplace
 * Logistics Pickup for Shipment Create Page
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'plugins/DOMPurify',
    'Magento_Ui/js/modal/alert'
], function ($, modal, $t, DOMPurify, alert) {
    'use strict';

    return function (config, element) {
        var jQ = $.noConflict();

        // Show message function
        function showMessage(type, msg) {
            var messageClass = 'message';
            if (type === 'error') {
                messageClass += ' error';
            } else if (type === 'success') {
                messageClass += ' success';
            }

            var messageHtml = '<div class="' + messageClass + '">' + DOMPurify.sanitize(msg) + '</div>';

            // Find or create message container
            var messageContainer = jQ('#shipment-logistics-message');
            if (!messageContainer.length) {
                jQ('.wk-mp-tracking-header').after('<div id="shipment-logistics-message"></div>');
                messageContainer = jQ('#shipment-logistics-message');
            }

            messageContainer.html(messageHtml);

            // Auto hide success message after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    messageContainer.fadeOut(function() {
                        jQ(this).html('').show();
                    });
                }, 5000);
            }
        }

        // Logistics pickup button handler
        jQ(document).on('click', '#logistics-pickup-shipment', function(e) {
            e.preventDefault();

            // Get selected carrier
            var carrier = jQ('.carrier-select').val();
            if (!carrier || carrier.trim() === '') {
                showMessage('error', $t('Please select a logistics carrier.'));
                return;
            }

            // Get order ID from hidden field
            var orderId = jQ('#shipment-order-id').val();
            if (!orderId) {
                showMessage('error', $t('Order ID not found.'));
                return;
            }

            // Get form key
            var formKey = jQ('#marketplace-shipment-form input[name="form_key"]').val();
            if (!formKey) {
                showMessage('error', $t('Form key not found.'));
                return;
            }

            // Get all order items (we'll send all items for shipment)
            // In shipment create page, we don't have checkboxes, so we'll get all items
            var orderItems = [];
            jQ('input[name^="shipment[items]"]').each(function() {
                var itemId = jQ(this).attr('name').match(/\[(\d+)\]/);
                if (itemId && itemId[1]) {
                    orderItems.push(itemId[1]);
                }
            });

            if (orderItems.length === 0) {
                showMessage('error', $t('No items found to create pickup number.'));
                return;
            }

            // Create form data
            var form = new FormData();
            form.append('carrier', DOMPurify.sanitize(carrier.toString()));
            form.append('id', DOMPurify.sanitize(orderId.toString()));
            form.append('form_key', DOMPurify.sanitize(formKey.toString()));

            // Add selected items
            jQ.each(orderItems, function(k, v) {
                form.append('order_items[]', DOMPurify.sanitize(v.toString()));
            });

            // Show loading
            jQ('body').trigger('processStart');

            // Send AJAX request
            jQ.ajax({
                type: "POST",
                url: "/b8marketplace/order/pickupNumber",
                processData: false,
                mimeType: "multipart/form-data",
                contentType: false,
                data: form,
                dataType: 'json',
                success: function (response) {
                    jQ('body').trigger('processStop');

                    if (response.success == 1) {
                        showMessage('success', response.msg);

                        // Optionally, you can auto-fill the tracking number if needed
                        // var trackingNumber = response.tracking_number;
                        // if (trackingNumber) {
                        //     jQ('input[name="tracking_id"]').val(trackingNumber);
                        // }
                    } else {
                        showMessage('error', response.msg || $t('Failed to create pickup number, please try again.'));
                    }
                },
                error: function (e) {
                    jQ('body').trigger('processStop');
                    console.log(e);
                    showMessage('error', $t('An error occurred while creating pickup number.'));
                }
            });
        });
    };
});
