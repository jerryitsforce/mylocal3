/**
 * @return widget
 */

define([
    'jquery',
    'amBrandsFilter',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('am.brandsFilterInit', {
        options: {
            element: null,
            target: null
        },

        /**
         * @private
         */
        _create: function () {
            var self = this;

            $(this.options.element).on('click', function(e) {
                e.preventDefault();
                $(this).applyBrandFilter(self.options.target);
            });

            $('.ambrands-switch-lang button').on('click', function(e){
                console.log({this: $(this)})
                $('.ambrands-switch-lang button').removeClass('active');
                $(this).addClass('active');
                if($(this).hasClass('ambrands-lang-en')) {
                    $('body').removeClass('show-cn');
                } else {
                    $('body').addClass('show-cn');
                }
            });
        }
    });

    return $.am.brandsFilterInit;
});
