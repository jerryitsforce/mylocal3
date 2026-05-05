define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  
  $.widget('b8.hotaiPayEdit', {
    options: {
      redirectUrl: '',
    },

    _create: function () {        
      var self = this;
      $(document).on('click', '#alias_edit_btn', function () {
        self.processEdit();
      });
      $(document).on('click', '#alias_delete_btn', function () {
        $('#modal-credit-card-delete').modal('openModal');
      });
    },

    processEdit: async function(data) {
      var self = this;
      var url = location.origin + "/rest/V1/edit/creditcard/aliasname";
      var responseText = {
        'success': $.mage.__('Completed Editing'),
        'error': $.mage.__('Error.')
      };
      
      var form = $('#edit_alias_form');
      var id = form.data('id');
      // var aliasName = $('#card_alias').val();
      // var isDefaultCard = $("#is_default_card");

      var aliasName = document.getElementById("card_alias").value;
      var isDefaultCard = document.getElementById("is_default_card");
      var isDefaultCardValue = '';

      if (aliasName === '') {
        var aliasName = '';
      }

      if (isDefaultCard.checked) {
        isDefaultCardValue = document.getElementById("is_default_card").value;
      }

      if (form.validation() && form.validation('isValid')) {
       try {
        const resultCode = await fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body:  JSON.stringify({
              tokenId: id.toString(),
              aliasName: aliasName,
              isDefaultCard: isDefaultCardValue
            })
          })
          .then((response) => response.json())
          .then((result) => {
            return JSON.parse(result);
          });
  
        if (resultCode['success'] == true) {
          // console.log(responseText['success']);
          self.showMessage(responseText['success'], 'success');
          window.location.href = self.options.redirectUrl;
        } else if (resultCode['success'] == false) {
          console.error(resultCode['error']);
          self.showMessage(responseText['error']);
        } else {
          console.error(responseText['error']);
          self.showMessage(responseText['error']);
        }
      } catch (error) {
        console.error(error);
        self.showMessage(error);
      }
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

return $.b8.hotaiPayEdit;
});
