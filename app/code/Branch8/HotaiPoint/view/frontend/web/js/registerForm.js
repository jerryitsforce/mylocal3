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

  $.widget('b8.hotaiPointRegistrationForm', {
    options: {
      serialNumber: '',
      qrType: '',
      qrCodeUrl: '',
    },

    _create: function () {
      this.checkValidation();
      this.actions();
      if (this.options.serialNumber !== '') {
        this.processForm();
      }
    },

    checkValidation: function () {
      var serialNumber= $('#serial-number').val();

      if(serialNumber) {
        $('#registration-submit-btn').removeClass('disabled').prop('disabled', false);
      } else {
        $('#registration-submit-btn').addClass('disabled').prop('disabled', true);
      }
    },

    actions: function () {
      var self = this,
          serialNumber= $('#serial-number');

      serialNumber.on('input change', function () {
        const validate = $.validator.validateSingleElement($(this));
        if (validate) {
          self.checkValidation();
        } else {
          $('#registration-submit-btn').addClass('disabled').prop('disabled', true);
        }
      });

      $('#registration-submit-btn').on('click', function (e) {
        e.preventDefault();
        self.processForm();
      });
    },

    processForm: async function () {
      var self = this,
          serialNumber= DOMPurify.sanitize($('#serial-number').val());

      if(serialNumber) {
        var url = encodeURI(location.origin + "/rest/V1/hotaipoint/register");
        var responseText = {
          'success': $.mage.__('已成功登錄點數'),
          'error': $.mage.__('Something went wrong.')
        };

        try {
          $('body').trigger('processStart');
          const resultCode = await fetch(url, {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({
                serialNumber: serialNumber
              })
            })
            .then((response) => response.json())
            .then((result) => {
              return JSON.parse(result);
            });

          if (resultCode['success'] == true) {
            self.showMessage(responseText['success'], 'success');
            $('#serial-number').val('');
            $('#registration-submit-btn').addClass('disabled').prop('disabled', true);
            var redirectUrl = urlBuilder.build('hotai_point/detail/');

            // 加上查詢參數（如果需要）
            redirectUrl += (redirectUrl.indexOf('?') > -1 ? '&' : '?') + 't=tab-history';

            // 執行重導
            setTimeout(function() {
                window.location.href = redirectUrl;
              }, 500); // 讓使用者看到成功訊息後再重導

          } else if (resultCode['success'] == false) {
            const errorRes = resultCode['errMsg'];
            const errorResData = JSON.parse(errorRes);
            console.error(resultCode['errMsg']);
            console.log(errorResData);
            if(errorResData['Response string after decrypt']) {
              const errorResDataParsed = JSON.parse(errorResData['Response string after decrypt']);
              if(errorResDataParsed['returnMsg']) {
                self.showMessage(errorResDataParsed['returnMsg']);
              } else {
                self.showMessage(resultCode['errMsg']?? responseText['error']);
              }
            } else {
              self.showMessage(resultCode['errMsg']?? responseText['error']);
            }
          } else {
            self.showMessage(responseText['error']);
          }
          // modal.closeModal(true);
          $('body').trigger('processStop');
        } catch (error) {
          console.error(error);
          $('body').trigger('processStop');
          // self.showMessage(responseText['error']);
        }
      }
    },

    showMessage: function (message, type = 'error') {
      var jQ = $.noConflict();
      var msgContainer = jQ('.page.messages');
      var messageContent = jQ('<div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'"/>');
      messageContent.text(DOMPurify.sanitize(message));

      const raw = messageContent.prop('outerHTML');
      const clean = DOMPurify.sanitize(raw, {
        ALLOWED_TAGS: ['div', 'br', 'span'],
        FORBID_TAGS: ['script', 'style', 'iframe'],
        FORBID_ATTR: ['onerror', 'onload']
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
    }
  });

  return $.b8.hotaiPointRegistrationForm;
});
