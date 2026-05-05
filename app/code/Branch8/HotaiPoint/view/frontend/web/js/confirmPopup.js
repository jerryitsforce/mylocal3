define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'plugins/DOMPurify',
  'mage/url',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal, DOMPurify, urlBuilder) {
  $.widget('b8.hotaiPointConfirmPopup', {
    options: {
    },

    _create: function () {
      var self = this;
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('確定移轉點數'),
        modalClass: 'modal-custom hotaipoint-confirm-popup',
        buttons: [{
            text: $.mage.__('取消'),
            class: 'action secondary action-secondary',
            click: function () {
              this.closeModal();
            }
        },{
          text: $.mage.__('確定'),
          class: 'action primary action-primary',
          click: async function () {
            var modal= this,
                transferTo= DOMPurify.sanitize($('#transfer-to').val()),
                transferPoint= DOMPurify.sanitize($('#transfer-point').val()),
                transferCheckCode= DOMPurify.sanitize($('#sms-verification-code').val().trim().toUpperCase());

            if(transferTo && transferPoint) {
              // call transfer api
              // var url = encodeURIComponent(location.origin + "/rest/V1/hotaipoint/transfer");
              var responseText = {
                'success': $.mage.__('已成功移轉點數'),
                'error': $.mage.__('Something went wrong.')
              };

              try {
                $('body').trigger('processStart');
                const resultCode = await fetch(urlBuilder.build('rest/V1/hotaipoint/transfer'), {
                    method: "POST",
                    headers: {
                      "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                      transferTo: transferTo,
                      transferPoint: transferPoint,
                      transferCheckCode: transferCheckCode
                    })
                  })
                  .then((response) => response.json())
                  .then((result) => {
                    return JSON.parse(result);
                  });

                if (resultCode['success'] == true) {
                  self.showMessage(responseText['success'], 'success');
                  $('#transfer-to').val('');
                  $('#transfer-point').val('');
                  $('#transfer-submit-btn').addClass('disabled').prop('disabled', true);
                  var redirectUrl = urlBuilder.build('hotai_point/detail/');

                  // 加上查詢參數（如果需要）
                  redirectUrl += (redirectUrl.indexOf('?') > -1 ? '&' : '?') + 't=tab-redeemed';

                  // 執行重導
                  setTimeout(function() {
                    window.location.href = redirectUrl;
                  }, 500); // 讓使用者看到成功訊息後再重導
                } else if (resultCode['success'] == false) {

                  const errorRes = resultCode['errMsg'];
                  let errorResData = null;
                  if(errorRes.includes('Response string after decrypt')) {
                    errorResData = JSON.parse(errorRes);
                  }
                  console.error(resultCode['errMsg']);
                  if(errorResData && errorResData['Response string after decrypt']) {
                    const errorResDataParsed = JSON.parse(errorResData['Response string after decrypt']);
                    if(errorResDataParsed['returnMsg']) {
                      // self.showMessage(errorResDataParsed['returnMsg']);
                      self.limitPopup(errorResDataParsed['returnMsg']);
                      urlBuilder.build('hotai_point/detail/');
                    } else {
                      self.showMessage(resultCode['errMsg']?? responseText['error']);
                    }
                  } else {
                    self.showMessage(resultCode['errMsg']?? responseText['error']);
                  }
                } else {
                  self.showMessage(responseText['error']);
                }
                modal.closeModal(true);
                $('body').trigger('processStop');
              } catch (error) {
                console.error(error);
                $('body').trigger('processStop');
                self.showMessage(responseText['error']);
                modal.closeModal(true);
              }
            }
          }
      }]
      };

      modal(options, $('#confirm-transfer-modal'));
      // $('#confirm-transfer-modal').modal('openModal');
    },

    showMessage: function (message, type = 'error') {
      var jQ = $.noConflict();
      var msgContainer = jQ('.page.messages');
      var messageContent = jQ('<div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'"/>');
      messageContent.text(DOMPurify.sanitize(message));

      const raw = messageContent.prop('outerHTML');
      const clean = DOMPurify.sanitize(raw, {
        ALLOWED_TAGS: ['b', 'span', 'div', 'br', 'i'],
        FORBID_TAGS: ['script', 'iframe'],
        FORBID_ATTR: ['onerror', 'onclick']
      });

      var customMessages = jQ('<div>').addClass('messages custom-messages');
      customMessages.html(clean);
      msgContainer.append(customMessages);
      msgContainer.addClass('__show');
      var timeCheck;
      clearTimeout(timeCheck);
      timeCheck = setTimeout(function () {
        msgContainer.removeClass('__show');
        msgContainer.find('.custom-messages').remove();
      }, 3000);
    },

    limitPopup: function (title) {
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__(title),
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
      $('#limit-point-transfer-modal').modal('openModal');
    }
});

return $.b8.hotaiPointConfirmPopup;
});
