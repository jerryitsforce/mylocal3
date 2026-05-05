require([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
], function ($, $do) {
    const ticket_attribute_sets = [
        'ticket_redeem',
        'ticket_non_redeem',
        'ticket_giveaway',
        'ticket_yoxi',
        'ticket_edenred',
        'ticket_fami'
    ];

    jQuery(document).ajaxComplete(function() {
        $do.get('select[name="product[virtual_product_type]"]', function (elem) {
            let attribute_set_name = $('[data-index="attribute_set_id"]').find('.admin__action-multiselect-text').text();
            let virtual_product_type = $(elem);

            virtual_product_type.prop('disabled', true)

            if (!isTicketAttributeSet(attribute_set_name, ticket_attribute_sets)) {
                virtual_product_type.val(1).change();
            }
        });
    });

    function isTicketAttributeSet(attribute_set_name) {
        const lowerStr = attribute_set_name.toLowerCase();

        const lowerArr = ticket_attribute_sets.map(item => item.toLowerCase());

        return lowerArr.includes(lowerStr);
    }
});