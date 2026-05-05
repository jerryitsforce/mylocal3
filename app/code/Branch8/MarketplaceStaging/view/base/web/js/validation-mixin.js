define(['jquery'], function ($) {
    'use strict';
    return function (targetWidget) {
        $.validator.addMethod(
            'validate-no-sku-duplicate',
            function (value, element) {
                try {
                    const currentSku = $.trim(value).toLowerCase();
                    const parentForm = $(element).closest('form');
                    if (!parentForm.length) return true;

                    var map = {}, $all = parentForm.find('.wkv-sku.no-duplicate');
                    $all.removeClass('duplicate-sku-error');
                    
                    $all.each(function () {
                        var val = $(this).val();
                        if (val === undefined || val === null) return;
                        var v = $.trim(val).toLowerCase();
                        if (!v) return; // ignore empty
                        if (!map[v]) map[v] = [];
                        map[v].push(this);
                    });
                    
                    $.each(map, function (k, els) {
                        if (els.length > 1) {
                            $(els).addClass('duplicate-sku-error');
                        }
                    });
                    
                    if (!currentSku) {
                        return true;
                    }
                    return !(map[currentSku] && map[currentSku].length > 1);
                } catch (e) {
                    console.error("Error in validate-no-sku-duplicate:", e);
                    return true; // fail safe
                }
            },
            function () {
               return $.mage.__('Duplicate value detected.')
            },
        )
        return targetWidget;
    }
});
