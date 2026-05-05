define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.cartDeletePopup', {
    options: {
      modalId: 'modal-confirm-delete'
    },

    _create: function () {        
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('刪除商品'),
        modalClass: 'modal-custom confirm-delete-popup',
        buttons: [{
            text: $.mage.__('加入追蹤'),
            class: 'action secondary action-secondary',
            click: function () {
                this.closeModal();
                this.element.trigger('addItemToWishlist');
            }
        },{
          text: $.mage.__('確認刪除'),
          class: 'action primary action-primary',
          click: function () {
              this.closeModal();
              this.element.trigger('deleteItem');
          }
      }]
      };

      const popup = modal(options, $('#'+this.options.modalId));
      // $('#'+this.options.modalId).modal('openModal');
    }
});

return $.b8.cartDeletePopup;
});
