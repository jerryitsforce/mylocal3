define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.hotaiPointLimitPopup', {
    options: {
    },

    _create: function () {        
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('本日已達點數轉移上限'),
        modalClass: 'modal-custom hotaipoint-limit-popup',
        buttons: [{
            text: $.mage.__('確定'),
            class: 'action primary action-primary',
            click: function () {
              this.closeModal();
            }
        }]
      };

      var popup = modal(options, $('#limit-point-transfer-modal'));
      // $('#limit-point-transfer-modal').modal('openModal');
    },
});

return $.b8.hotaiPointLimitPopup;
});
