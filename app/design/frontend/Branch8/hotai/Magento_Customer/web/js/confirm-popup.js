define([
  'jquery',
  'Magento_Ui/js/modal/confirm',
  'matchMedia',
  'domReady!'
], function ($, confirm) {
  $.widget('b8.confirmPopup', {
    options: {
    },

    /**
     * @private
     */
    _create: function () {
        $(this.element).on('click', function (event) {
          event.preventDefault();
          const ua = navigator.userAgent;
          const isHotaiApp = $('body').hasClass('hotai-app') || /HotaiApp/i.test(ua);

          if (isHotaiApp) {
              window.ReactNativeWebView?.postMessage(JSON.stringify({ type: 'ON_APP_REGISTER', data: '' }));
          } else {
              var url = $(this).attr('href');
              confirm({
                  title: $.mage.__('註冊會員'),
                  content: $.mage.__('我們將引導你至和泰會員中心進行註冊...'),
                  modalClass: 'registration-reminder-popup',
                  buttons: [{
                      text: $.mage.__('確定前往'),
                      class: 'action-primary',
                      click: function () {
                          window.location.href = url;
                      }
                  }]
              });
          }
        });
    }
});

return $.b8.confirmPopup;
});
