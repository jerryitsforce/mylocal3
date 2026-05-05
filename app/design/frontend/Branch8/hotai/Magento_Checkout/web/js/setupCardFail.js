define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.checkoutSetupCardFail', {
    options: {
    },

    _create: function () {        
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('新增信用卡失敗'),
        modalClass: 'modal-custom card-binding-failed-popup',
        buttons: [{
            text: $.mage.__('下次再說'),
            class: 'action secondary action-secondary',
            click: function () {
                this.closeModal();
            }
        },
        {
          text: $.mage.__('重新綁定'),
          class: 'action primary action-primary',
          click: function () {
              this.closeModal();
              $('.form-card-wrapper .action.btn-submit').click();
              // console.log('重新綁定');
          }
      }]
      };

      var popup = modal(options, $('#modal-card-binding-failed'));
      $('#modal-card-binding-failed').modal('openModal');
    }
});

  return $.b8.checkoutSetupCardFail;
});
