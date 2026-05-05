define(['jquery'], function ($) {
    'use strict';

    return function (targetWidget) {
        $.widget('mage.updateShoppingCart', targetWidget, {
            /**
             * Form validation failed. Intercept limit purchase errors to show custom popup instead of technical alert.
             * @param {Object} response
             */
            onError: function (response) {
                var errorMessage = response.error_message || '';

                // Detect our hidden marker (three zero-width spaces \u200B)
                if (errorMessage.indexOf('\u200B\u200B\u200B') !== -1) {
                    var cleanMsg = errorMessage.replace(/\u200B\u200B\u200B/g, '');
                    
                    // Signal the centralized gatekeeper to show the popup
                    $(document).trigger('hotai:showLimitError', {msg: cleanMsg, isLimit: true});

                    // REVERT UI quantity inputs to original values
                    this.element.find('[data-role=cart-item-qty]').each(function() {
                        var inp = $(this);
                        var originalQty = inp.attr('data-item-qty');
                        if (originalQty !== undefined) {
                            inp.val(originalQty);
                        }
                    });

                    return;
                }

                return this._super(response);
            }
        });

        return $.mage.updateShoppingCart;
    };
});
