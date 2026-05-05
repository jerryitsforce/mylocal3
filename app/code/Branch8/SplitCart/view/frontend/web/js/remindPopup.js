define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.cartRemindPopup', {
    options: {
      modalId: 'modal-cart-remind-popup'
    },

    _create: function () {        
      this._checkAndShow();
      // Avoid contentUpdated listener which causes loops. Signal-based logic is enough.
      
      // Listen for signals from AJAX Mixin or other components
      $(document).on('hotai:showLimitError', function(event, data) {
          this._checkAndShow(data);
      }.bind(this));
    },

    /**
     * Check if there are limit errors or product errors in DOM and show the respective popup
     * @param {Object|string|null} data Optional message or object from AJAX signal
     * @private
     */
    _checkAndShow: function(data) {
      var self = this;
      var ajaxMsg = null;
      var isLimitAjax = false;

      if (typeof data === 'string') {
          ajaxMsg = data;
          isLimitAjax = true; // Legacy support: assume limit if only string passed to "showLimitError"
      } else if (data && typeof data === 'object') {
          ajaxMsg = data.msg || null;
          isLimitAjax = (data.isLimit !== undefined) ? data.isLimit : (ajaxMsg !== null);
      }

      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('商品狀態異常通知'),
        modalClass: 'modal-custom confirm-cart-remind-popup',
        buttons: [{
            text: $.mage.__('回到購物車'),
            class: 'action primary action-primary',
            click: function () {
                this.closeModal();
            }
        }]
      };

      var limitError = $('.error-limit, .has-limit-error');
      var hasLimit = (limitError.length > 0 || isLimitAjax);
      
      var modalEl = $('#' + this.options.modalId);
      if (hasLimit) {
          options.title = $.mage.__('商品超過購買數量限制');
          modalEl.find('.generic-msg').hide();
          
          if (ajaxMsg) {
              modalEl.find('.limit-msg').html(ajaxMsg).show();
          } else {
              modalEl.find('.limit-msg').show();
          }
      } else {
          options.title = $.mage.__('商品狀態異常通知');
          modalEl.find('.generic-msg').show();
          modalEl.find('.limit-msg').hide();
          
          if (ajaxMsg) {
              modalEl.find('.generic-msg').html(ajaxMsg);
          }
      }

      // Ensure modal is initialized
      if (!modalEl.data('mageModal')) {
          modal(options, modalEl);
      }
      // Always sync the title to match the current error type
      modalEl.modal('setTitle', options.title);

      var productCartError = $('.product-cart-item-error, .has-limit-error, .has-error');
      if(productCartError.length > 0 || ajaxMsg){
          // Avoid multiple opens by using a single gatekeeper
          if (!modalEl.closest('.modal-popup').hasClass('_show')) { 
              modalEl.modal('openModal');
          }
      }
    }
});

return $.b8.cartRemindPopup;
});
