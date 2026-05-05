/**
 * Organization: Branch8
 */
var config = {
    map: {
        '*': {
            b8ConfirmPopup: 'Magento_Customer/js/confirm-popup',
            b8ConfirmLogout: 'Magento_Customer/js/confirm-logout',
            b8AddressForm: 'Magento_Customer/js/address-form',
            fullWidthRemover: 'Magento_Customer/js/fullWidthRemover'
        }
    },
    shim: {
        'Magento_Customer/js/fullWidthRemover': {
            deps: ['jquery', 'mage/validation']
        }
    }
};
