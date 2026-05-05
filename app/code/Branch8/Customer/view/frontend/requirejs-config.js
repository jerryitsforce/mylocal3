/**
 * Organization: Branch8
 */
var config = {
    map: {
        '*': {
            b8MemberLoader: 'Branch8_Customer/js/memberLoader',
            b8MemberInfo: 'Branch8_Customer/js/memberInfo',
            b8MemberHotaiPay: 'Branch8_Customer/js/memberHotaiPay',
            b8SetupCreditCard: 'Branch8_Customer/js/setupCreditCard',
            b8SetupCardFail: 'Branch8_Customer/js/setupCardFail',
            b8SetupCardSuccess: 'Branch8_Customer/js/setupCardSuccess',
            b8CustomerAccount: 'Branch8_Customer/js/customerAccount',
            b8ConfirmationPopup: 'Branch8_Customer/js/confirmationPopup',
            b8AutoLogout: 'Branch8_Customer/js/autoLogout',
            b8ReactAccount: 'Branch8_Customer/js/react-account'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'Branch8_Customer/js/validation-mixin': true
            },
            'Magento_Directory/js/region-updater': {
                'Branch8_Customer/js/region-updater-mixin': true
            }
        }
    }

};
