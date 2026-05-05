define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Branch8_SingleDeviceLogin/js/model/socket-provider',
    'Branch8_SingleDeviceLogin/js/model/socketCommunicateObject',
    'Magento_Ui/js/modal/modal',
    'mage/url'
], function ($, customeData, socketProvider, socketObject, modal, urlBuilder) {

    /**
     * Check if running inside HotaiApp (React Native WebView)
     * If true, skip socket.io operations - React Native handles it separately
     *
     * @returns {boolean}
     */
    function isHotaiApp() {
        var userAgent = navigator.userAgent || '';
        var hasHotaiAppUA = userAgent.indexOf('HotaiApp') !== -1;
        var hasHotaiAppClass = document.body && document.body.classList.contains('hotai-app');

        return hasHotaiAppUA || hasHotaiAppClass;
    }

    return async function (config) {
        // Skip socket.io for HotaiApp - React Native handles single device login separately
        if (isHotaiApp()) {
            console.log('Skip socket.io for HotaiApp - React Native handles single device login separately')
            return;
        }

        const customer = customeData.get('customer')();
        await socketProvider.init(config);
        socketObject.setSocketObject(socketProvider.getSocketObject()).setConfig(config);
        socketObject.listenLogoutEvents();
        const loginToken = socketObject.getLoginToken();
        if (loginToken && customer.customer_id) {
            const detail = {
                'customerId': customer.customer_id,
                'login_token': socketObject.getLoginToken(),
                'device_type': customer?.device_type ? customer.device_type : 'web'
            };
            socketObject.startNewSession(detail)
        }
        customeData.get('customer').subscribe(function (newValue) {
            const sidLoginToken = socketObject.getLoginToken();
            if (newValue.customer_id && sidLoginToken) {
                const customer = customeData.get('customer')();
                const detail = {
                    'customerId': newValue.customer_id,
                    'login_token': sidLoginToken,
                    'device_type': customer?.device_type ? customer.device_type : 'web'
                };
                socketObject.startNewSession(detail)
            }
        })
    }
})
