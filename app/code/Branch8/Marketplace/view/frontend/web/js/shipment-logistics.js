/**
 * Branch8 Marketplace
 * Dynamically add Logistics Pickup button and modify carrier dropdown
 * for Shipment Create page
 */
require([
    'jquery',
    'mage/translate',
    'plugins/DOMPurify',
    'domReady!'
], function($, $t, DOMPurify) {
    'use strict';

    // Only run on shipment create page
    if (window.location.pathname.indexOf('marketplace/order_shipment/create') === -1) {
        return;
    }

    var jQ = $.noConflict();

    // Function to show messages
    function showMessage(type, msg) {
        var messageClass = 'message';
        if (type === 'error') {
            messageClass += ' error';
        } else if (type === 'success') {
            messageClass += ' success';
        }

        var messageHtml = '<div class="' + messageClass + '">' + DOMPurify.sanitize(msg) + '</div>';
        var messageContainer = jQ('#shipment-logistics-message');

        if (!messageContainer.length) {
            messageContainer = jQ('<div id="shipment-logistics-message" style="margin-bottom: 10px;"></div>');
            jQ('.wk-mp-order-shipping-address .block-content').prepend(messageContainer);
        }

        messageContainer.html(messageHtml);

        if (type === 'success') {
            setTimeout(function() {
                messageContainer.fadeOut(function() {
                    jQ(this).html('').show();
                });
            }, 5000);
        }
    }

    // Function to get carriers with status
    function getCarriersWithStatus(callback) {
        // Get seller ID from session or page
        var sellerId = jQ('input[name="seller_id"]').val();

        jQ.ajax({
            url: '/b8marketplace/logistics/getCarriers',
            type: 'GET',
            dataType: 'json',
            data: {
                seller_id: sellerId
            },
            success: function(response) {
                if (response.success && response.carriers) {
                    callback(response.carriers);
                } else {
                    // Fallback: use basic carriers
                    callback(getBasicCarriers());
                }
            },
            error: function() {
                // Fallback: use basic carriers
                callback(getBasicCarriers());
            }
        });
    }

    // Fallback basic carriers
    function getBasicCarriers() {
        return {
            '新竹物流': { label: '新竹物流', connected: false },
            '黑貓宅急便': { label: '黑貓宅急便', connected: false },
            '郵局': { label: '郵局', connected: false }
        };
    }

    // Initialize on page load
    function init() {
        // 1. Add logistics pickup button and print button after tracking number input
        var trackingInput = jQ('input[name="tracking_id"]');
        if (trackingInput.length && !jQ('#logistics-pickup-shipment').length) {
            var buttonHtml = '<button class="button logistics-pickup-number" ' +
                'title="Logistics Pickup Number" type="button" id="logistics-pickup-shipment">' +
                '<span><span>' + $t('物流取號') + '</span></span></button>' +
                '<button class="button print-waybill-all" ' +
                'title="Print Waybill" type="button" id="print-waybill-all" ' +
                'style="margin-left: 10px; display: none;">' +
                '<span><span>' + $t('列印') + '</span></span></button>';

            // Insert buttons after the tracking number input's parent td
            trackingInput.closest('tr').after('<tr><td colspan="2" style="text-align: left; padding-left: 0;">' + buttonHtml + '</td></tr>');
        }

        // Check waybill status and show/hide print button
        checkWaybillStatus();

        // 2. Update existing select options with connection status
        var carrierSelect = jQ('select[name="carrier"]');
        if (carrierSelect.length) {
            // Get carriers with status and update options
            getCarriersWithStatus(function(carriers) {
                // Get current selected value
                var currentValue = carrierSelect.val();

                // Update each option's text if it has connection status
                carrierSelect.find('option').each(function() {
                    var optionValue = jQ(this).val();
                    if (optionValue && carriers[optionValue]) {
                        jQ(this).text(carriers[optionValue].label);
                    }
                });

                // Restore selected value
                if (currentValue) {
                    carrierSelect.val(currentValue);
                }
            });
        }

        // 3. Add hidden order ID field
        var orderId = getOrderIdFromUrl();
        if (orderId && !jQ('#shipment-order-id').length) {
            jQ('#marketplace-shipment-form').append(
                '<input type="hidden" id="shipment-order-id" value="' + orderId + '" />'
            );
        }

        // 4. Bind button click events
        jQ(document).on('click', '#logistics-pickup-shipment', handleLogisticsPickup);
        jQ(document).on('click', '#print-waybill-all', handlePrintWaybills);
    }

    // Check waybill status for order items
    function checkWaybillStatus() {
        // Get all order items
        var orderItems = [];
        jQ('input[name^="shipment[items]"]').each(function() {
            var itemId = jQ(this).attr('name').match(/\[(\d+)\]/);
            if (itemId && itemId[1]) {
                orderItems.push(itemId[1]);
            }
        });

        if (orderItems.length === 0) {
            return;
        }

        // Check waybill status via AJAX
        jQ.ajax({
            url: '/b8marketplace/logistics/checkWaybills',
            type: 'GET',
            data: {
                order_items: orderItems
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.has_waybills) {
                    // Store waybills data
                    jQ('#print-waybill-all').data('waybills', response.waybills);
                    // Show print button
                    jQ('#print-waybill-all').show();

                    // Check if all items have waybills - if yes, disable pickup button
                    var totalItems = orderItems.length;
                    var pickedItems = response.waybills.length;

                    if (totalItems > 0 && pickedItems >= totalItems) {
                        // All items already have waybills - disable pickup button
                        jQ('#logistics-pickup-shipment')
                            .prop('disabled', true)
                            .css('opacity', '0.5')
                            .css('cursor', 'not-allowed')
                            .attr('title', $t('此訂單已完成取號'));
                    }
                }
            },
            error: function(e) {
                console.log('Error checking waybill status:', e);
            }
        });
    }

    // Handle print waybills button click
    function handlePrintWaybills(e) {
        e.preventDefault();

        var waybills = jQ(this).data('waybills');
        if (!waybills || waybills.length === 0) {
            alert($t('沒有可列印的運單'));
            return;
        }

        // Print each waybill
        jQ.each(waybills, function(index, waybill) {
            var imageUrl = '/logistics/waybill/image/id/' + waybill.id + '/action/display';
            var printWindow = window.open(imageUrl, 'WaybillImage_' + waybill.id, 'width=800,height=600,scrollbars=yes,resizable=yes');

            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            }

            // Add a small delay between opening multiple windows
            if (index < waybills.length - 1) {
                setTimeout(function() {}, 500);
            }
        });
    }

    // Get order ID from URL or form
    function getOrderIdFromUrl() {
        // Try from URL parameters
        var urlParams = new URLSearchParams(window.location.search);
        var orderId = urlParams.get('id');

        if (orderId) {
            return orderId;
        }

        // Try from form action URL
        var formAction = jQ('#marketplace-shipment-form').attr('action');
        if (formAction) {
            var match = formAction.match(/\/id\/(\d+)/);
            if (match && match[1]) {
                return match[1];
            }
        }

        return null;
    }

    // Handle logistics pickup button click
    function handleLogisticsPickup(e) {
        e.preventDefault();

        // Get selected carrier
        var carrier = jQ('select[name="carrier"]').val();
        if (!carrier || carrier.trim() === '') {
            showMessage('error', $t('請選擇物流載具'));
            return;
        }

        // Get order ID
        var orderId = jQ('#shipment-order-id').val();
        if (!orderId) {
            orderId = getOrderIdFromUrl();
        }

        if (!orderId) {
            console.log('Order ID not found. URL:', window.location.href);
            console.log('Form action:', jQ('#marketplace-shipment-form').attr('action'));
            showMessage('error', $t('找不到訂單 ID，請重新整理頁面'));
            return;
        }

        console.log('Order ID:', orderId);

        // Get form key
        var formKey = jQ('#marketplace-shipment-form input[name="form_key"]').val();
        if (!formKey) {
            showMessage('error', $t('Form key not found.'));
            return;
        }

        // Get all order items to ship
        var orderItems = [];
        jQ('input[name^="shipment[items]"]').each(function() {
            var itemId = jQ(this).attr('name').match(/\[(\d+)\]/);
            if (itemId && itemId[1] && jQ(this).val() > 0) {
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

        jQ.each(orderItems, function(k, v) {
            form.append('order_items[]', DOMPurify.sanitize(v.toString()));
        });

        // Show loading
        jQ('body').trigger('processStart');

        // Send AJAX request
        jQ.ajax({
            type: 'POST',
            url: '/b8marketplace/order/pickupNumber',
            processData: false,
            mimeType: 'multipart/form-data',
            contentType: false,
            data: form,
            dataType: 'json',
            success: function(response) {
                jQ('body').trigger('processStop');

                if (response.success == 1) {
                    showMessage('success', response.msg);

                    // Re-check waybill status and show print button
                    setTimeout(function() {
                        checkWaybillStatus();
                    }, 1000);
                } else {
                    showMessage('error', response.msg || $t('Failed to create pickup number, please try again.'));
                }
            },
            error: function(e) {
                jQ('body').trigger('processStop');
                console.log(e);
                showMessage('error', $t('An error occurred while creating pickup number.'));
            }
        });
    }

    // Initialize when DOM is ready
    init();
});
