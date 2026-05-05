define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.remindPointPopup', {
    options: {
      modalId: 'modal-reminder-point',
    },

    _create: function () {        
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('商品限制點數提醒'),
        modalClass: 'modal-custom remind-point-popup',
        buttons: [{
            text: $.mage.__('我知道了'),
            class: 'action primary action-primary',
            click: function () {
                this.closeModal();
            }
        }]
      };

      const popup = modal(options, $('#'+this.options.modalId));
      // $('#'+this.options.modalId).modal('openModal');
    }
});

return $.b8.remindPointPopup;
});
