define([
    'jquery'
], function ($) {
    'use strict';

    const ORDER_NOTE_LIMIT = 300;

    return {
        /**
         * Validate order note length (max 300 characters)
         *
         * @returns {Boolean}
         */
        validate: function () {
            var orderNoteSelector = $('textarea[name="order_note"]'),
                orderNoteValue = orderNoteSelector.val() || '',
                isValid = orderNoteValue.length <= ORDER_NOTE_LIMIT;

            if (!isValid) {
                orderNoteSelector.addClass('error');
                $(document).trigger('order-note-error', [$.mage.__('Limit 300 characters')]);
            } else {
                orderNoteSelector.removeClass('error');
                $(document).trigger('order-note-error', ['']);
            }

            return isValid;
        }
    };
});
