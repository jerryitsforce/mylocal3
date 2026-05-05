require([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
], function ($, $do) {
    const module_attribute_set_name = 'ticket_yoxi';
    const module_virtual_product_type = "2";

    const module_reset_display_barcode_value = 0;
    const module_allow_display_barcode_value = [0];

    jQuery(document).ajaxComplete(function () {
        // 調整"是否顯示條碼"(app/code/Branch8/HotaiCore/Model/Product/DisplayBarcode.php)的選項
        $do.get('select[name="product[display_barcode]"]', function (elem) {
            let attribute_set_name = $('[data-index="attribute_set_id"]').find('.admin__action-multiselect-text').text();
            let current_value = $(elem).val();
            let $options = $(elem).find('option');

            if (!matchAttributeSetName(attribute_set_name)) {
                return;
            }

            $options.each(function () {
                let $option = $(this);
                $option.prop('disabled', false);
            });

            if (!module_allow_display_barcode_value.includes(parseInt(current_value))) {
                $(elem).val(module_reset_display_barcode_value).change();
            }

            $options.each(function () {
                let $option = $(this);
                if (!module_allow_display_barcode_value.includes(parseInt($option.val()))) {
                    $option.prop('disabled', true);
                }
            });
        });

        $do.get('select[name="product[virtual_product_type]"]', function (elem) {
            let attribute_set_name = $('[data-index="attribute_set_id"]').find('.admin__action-multiselect-text').text();
            let virtual_product_type = $(elem);

            if (matchAttributeSetName(attribute_set_name)) {
                virtual_product_type.val(module_virtual_product_type).change();
            }
        });
    });

    function matchAttributeSetName(name) {
        return name.toLowerCase() == module_attribute_set_name.toLowerCase()
    }
});