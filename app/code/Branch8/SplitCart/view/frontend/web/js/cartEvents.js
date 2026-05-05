/**
 * Define events for cart:
 *  reload_cart_row_data : reload cart data
 */
define(['jquery', './EventName'], function ($, EventName) {
    'use strict';

    $.widget('branch8.cartEvents', {
        /**
         *
         * @private
         */
        _create: function () {
            $(document).on(
                EventName.RELOAD_ITEM_ROW_DATA,
                function (event, data) {
                    if (data && data.length) {
                        $.each(data, function (index, item) {
                            const id = item.id;
                            // qty
                            $('#cart-' + id + '-qty').val(
                                item.qty
                            );
                            //option
                          /*  $('[data-role="item-options-' + id + '"]').html(
                                item.optionHtml
                            );*/
                            // sub total
                  /*          $('[data-role="item-subtotal-' + id + '"]').html(
                                item.subTotal
                            );*/
                         /*   console.log({
                                itemOptionHtml: $('[data-role="item-options-' + id + '"]')
                            })*/
                        })
                        $(document).trigger('rebindQuickCartItem');
                    }
                }
            );
        }
    })
    return $.branch8.cartEvents;
});
