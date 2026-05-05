var config = {
    map: {
        '*': {
            b8HotaiPointTransferForm: 'Branch8_HotaiPoint/js/transferForm',
            b8HotaiPointRegisterForm: 'Branch8_HotaiPoint/js/registerForm',
            b8HotaiPointConfirmPopup: 'Branch8_HotaiPoint/js/confirmPopup',
            b8HotaiPointLimitPopup: 'Branch8_HotaiPoint/js/limitPopup',
            html5QrcodeScanner: 'Branch8_HotaiPoint/js/lib/html5-qrcode-wrapper'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'Branch8_HotaiPoint/js/validation-mixin': true
            }
        }
    }
}
