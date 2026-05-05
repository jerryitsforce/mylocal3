require([
    'jquery'
], function($) {
    'use strict';

    let isHiddenField = 'select[name="product[is_hidden]"]';
    let statusField = '#edit-product input[name="status"]';
    let statusFieldStaging = '#staging-product input[name="product[status]"]';
    let typeField = '#form-customer-product-new select[name="type"]';
    let arrayVirtual = ['ticket', 'ticket_yoxi', 'ticket_edenred', 'ticket_fami', 'ticket_redeem', 'ticket_non_redeem', 'ticket_7ELEVEN', 'ticket_openhub'];

    jQuery(document).ajaxComplete(function() {
        toggleStatus('edit');
        toggleStatus('staging');

        $(statusField).on('change', function() {console.log('edit');
            toggleStatus('edit');
        });
        $(statusFieldStaging).on('change', function() {console.log('staging');
            toggleStatus('staging');
        });
        $(typeField).on('change', function() {
            if ($(this).val() == 'virtual') {
                $("#form-customer-product-new select[name=\"set\"] option").each(function() {
                    let option = $(this).text().trim();
                    if($.inArray(option, arrayVirtual) !== -1) {
                        $(this).prop('disabled', false);
                    } else {
                        $(this).prop('disabled', true);
                    }
                });
            } else {
                $("#form-customer-product-new select[name=\"set\"] option").prop('disabled', false);
            }
        });
    });

    function toggleStatus(mode) {
        var prefix = '#edit-product ';
        if(mode == 'staging'){
            var prefix = '#staging-product ';
        }
        if($(prefix+'#status2').is(':checked')) {
            $(prefix+isHiddenField).prop('disabled', false);
        }else if($(prefix+'#status1').is(':checked')){
            $(prefix+isHiddenField).prop('disabled', true);
        }
    }
});
