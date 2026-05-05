define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.setupCardSuccess', {
    options: {
    },

    _create: function () {        
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('新增信用卡成功'),
        modalClass: 'modal-custom card-binding-completed-popup',
        buttons: [{
            text: $.mage.__('確定'),
            class: 'action primary action-primary',
            click: function () {
              // console.log('重新綁定');
              this.closeModal();
            }
        }]
      };

      var popup = modal(options, $('#modal-card-binding-completed'));
      $('#modal-card-binding-completed').modal('openModal');
    },
});

return $.b8.setupCardSuccess;
});
