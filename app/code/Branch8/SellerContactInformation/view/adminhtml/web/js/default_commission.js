require(["jquery","mage/translate", 'domReady!'], function($, $t){
    var sellerCommissionClicked = false;
    jQuery(document).ajaxComplete(function() {
        if(!sellerCommissionClicked){
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('tab') != null && urlParams.get('tab') == 'contract'){
                $('#tab_block_seller_edit_commission_tab_view').click();
            }else{
                $('#tab_block_seller_edit_commission_tab_view').click();
                $('#tab_customer').click();
            }
            sellerCommissionClicked = true;
        }
    });

    $('body').on('click', '#marketplace_update_default_commission', function(){

        var form = $('#contractForm');
        var validator = form.data('validator');
        if (!validator) {
            form.validation({});
            validator = form.data('validator');
        }
        if (!form.valid()) {
            return;
        }
        $('body').loader('show');
        $.ajax({
            url: $(this).attr('data-action'),
            type: 'POST',
            data: {
                'default_commission_rate': $('#marketplace_default_commisssion_rate').val(),
                'default_min_commission_rate': $('#marketplace_default_min_commisssion_rate').val(),
                'special_commission': $('#marketplace_special_commission').val(),
                'enable_special_commission': $('#marketplace_enable_special_commission').is(':checked') ? 1 : 0,
                'form_key': FORM_KEY
            },
            success: function (response) {
                if(response.success == 1){
                    alert($t('Update successfully'));
                    $('#tab_block_seller_edit_commission_tab_view').click();
                }
                $('body').loader('hide');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert($t('Update Default Total Commission and Default Verification fail'));
                $('body').loader('hide');
            }
        });
    });

    // Custom JS for special_commission toggle
    $(document).ready(function() {
        // Note: Using event delegation for robustness as elements might be dynamically rendered
        var specialCommissionField = $('#marketplace_special_commission');

        function toggleSpecialCommissionField() {
            var isChecked = $('#marketplace_enable_special_commission').is(':checked');
            if (isChecked) {
                specialCommissionField.prop('disabled', false);
            } else {
                specialCommissionField.prop('disabled', true);
                specialCommissionField.val(''); // Clear value when disabled
            }
        }

        // Set initial state on page load
        toggleSpecialCommissionField();

        // Attach event listener for changes using event delegation
        $(document).on('change', '#marketplace_enable_special_commission', toggleSpecialCommissionField);
    });
});
