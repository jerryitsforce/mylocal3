define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.confirmationPopup', {
    options: {
      modalId: 'modal-customer-confimation'
    },

    _create: function () {
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('確定離開編輯'),
        modalClass: 'modal-custom customer-confirmation-popup',
        buttons: [{
          text: $.mage.__('取消'),
          class: 'action secondary action-secondary',
          click: function () {
            this.closeModal();
          }
        }, {
          text: $.mage.__('確定'),
          class: 'action primary action-primary',
          click: function () {
            const url = window.sessionStorage.getItem('goToURL');
            this.closeModal();
            if (url && !url.toLowerCase().startsWith('javascript:')) {
              window.sessionStorage.removeItem('goToURL');
              window.location.href = url;
            }
          }
        }]
      };

      const popup = modal(options, $('#' + this.options.modalId));
      const self = this;

      $(document).ready(function () {
        window.sessionStorage.removeItem('accInfoInputChanged');
        window.sessionStorage.removeItem('goToURL');

        $('a').on('click', function (event) {
          const checkedInput = window.sessionStorage.getItem('accInfoInputChanged');
          if (checkedInput && checkedInput !== '') {
            event.stopPropagation();
            event.preventDefault();

            // Get the href attribute of the clicked link
            var href = $(this).attr('href');

            // Check if the href is not empty and is not a hash link
            // Check if the href is valid and safe
            if (href && href !== '#' && !href.toLowerCase().startsWith('javascript:')) {
              event.preventDefault();
              window.sessionStorage.setItem('goToURL', href);
              $('#' + self.options.modalId).modal('openModal');
            }
          }
        });
      });

      // $(window).on('popstate', function(event) {
      //   event.stopPropagation();
      //   console.log('Back button was pressed.');
      //   $('#modal-card-binding-failed').modal('openModal');
      //   event.preventDefault();
      // });
    }
  });

  return $.b8.confirmationPopup;
});
