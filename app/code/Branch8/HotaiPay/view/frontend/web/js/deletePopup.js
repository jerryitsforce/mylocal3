define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'mage/url',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal, urlBuilder) {
  $.widget('b8.hotaiPayDeletePopup', {
    options: {
      modalId: 'modal-credit-card-delete'
    },

    _create: function () { 
      var self = this;       
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('確定刪除'),
        modalClass: 'modal-custom confirm-delete-popup',
        buttons: [{
            text: $.mage.__('取消'),
            class: 'action secondary action-secondary',
            click: function () {
                this.closeModal();
            }
        },{
          text: $.mage.__('確定'),
          class: 'action primary action-primary',
          click: function () {
              this.closeModal();
              self.processDelete($('.creditcard-member-table .action.action-delete.deleted').data('id') || $('#alias_delete_btn').data('id'));
          }
      }]
      };

      const popup = modal(options, $('#'+this.options.modalId));
    },

    processDelete: async function(id) {
      var url = location.origin + "/rest/V1/delete/creditcard";
      var responseText = {
        'success': $.mage.__('Deleted.'),
        'error': $.mage.__('Something went wrong.')
      };
      var self = this;
      try {
        $('body').trigger('processStart');
        const resultCode = await fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body: JSON.stringify({
              tokenId: id
            })
          })
          .then((response) => response.json())
          .then((result) => {
            return JSON.parse(result);
          });
  
        if (resultCode['success'] == true) {
          console.log(responseText['success']);
          self.showMessage(responseText['success'], 'success');
          window.location.href = urlBuilder.build('hotaipay/creditcard/listaction');
        } else if (resultCode['success'] == false) {
          console.error(resultCode['error']);
          self.showMessage(responseText['error']);
        } else {
          console.error(responseText['error']);
          self.showMessage(responseText['error']);
        }
        $('body').trigger('processStop');
      } catch (error) {
        console.error(responseText['error']);
        console.error(error);
        $('body').trigger('processStop');
        // self.showMessage(responseText['error']);
      }
    },

    showMessage: function (message, type = 'error') {
      var msgContainer = $('.page.messages');
      msgContainer.append('<div class="messages custom-messages"><div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'">' + message + '</div></div>');
      msgContainer.addClass('__show');
      var timeCheck;
      clearTimeout(timeCheck);
      timeCheck = setTimeout(function () {
        msgContainer.removeClass('__show');
        msgContainer.find('.custom-messages').remove();
      }, 3000);
    }
});

return $.b8.hotaiPayDeletePopup;
});
