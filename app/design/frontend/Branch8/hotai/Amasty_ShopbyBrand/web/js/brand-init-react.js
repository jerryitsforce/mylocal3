define([
    "jquery",
    "amBrandsFilter"
], function ($) {
    'use strict';

    // This is the function that will be returned by the module
    return function () {
        $(".ambrands-filters-block").on('click', '.filter-letter', function(e) {
            e.preventDefault();
            $(this).applyBrandFilter(".brand-letter");
        });
        $('.ambrands-switch-lang button').on('click', function(e){
            if($(this).hasClass('ambrands-lang-en')) {
                $('body').removeClass('show-cn');
            } else {
                $('body').addClass('show-cn');
            }
        });
    };
});
