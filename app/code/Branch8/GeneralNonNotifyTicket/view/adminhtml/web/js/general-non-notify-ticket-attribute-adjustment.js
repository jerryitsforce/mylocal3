require([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
], function ($, $do) {
    const module_attribute_set_name = 'ticket_non_redeem';
    const module_virtual_product_type = "6";

    jQuery(document).ajaxComplete(function () {
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