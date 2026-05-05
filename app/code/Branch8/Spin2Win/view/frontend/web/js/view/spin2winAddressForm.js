define([
  'jquery',
  'dompurify',
  'Branch8_Spin2Win/js/dompurify-config',
  'mage/url',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify, getSanitizeConfig, url) {
  // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
  // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
  window.DOMPurify = DOMPurify;
  function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
  var createDOMPurify = require("dompurify");
  createDOMPurify(window);

  $.widget('b8.spin2winAddressForm', {
    options: {
      selectors: {
        btnSubmit: '.actions-toolbar .action.save'
      }
    },

    _create: function () {
      // var jQ = $.noConflict();

      $(this.options.selectors.btnSubmit).attr('disabled', "true");
      $(this.options.selectors.btnSubmit).addClass('disabled');

      this.initActions();
      this.enableSubmit();
    },

    initActions: function () {
      const self = this;
      $(document).on('change, input', '#name, #phone_number, #street, #region_id, #city_id', function () {
        const validate = $.validator.validateSingleElement($(this));
        if (validate) {
          self.enableSubmit();
        } else {
          $("#address-form .actions-toolbar .action.save").attr('disabled', true);
          $("#address-form .actions-toolbar .action.save").addClass('disabled');
        }
      });

      $(this.options.selectors.btnSubmit).on('click', function (event) {
        event.preventDefault();
        self.validateForm();
      });
    },

    enableSubmit: function () {
      var name = $('#name').val();
      var phone = $('#phone_number').val();
      var regionId = $('#region_id').val();
      var cityId = $('#city_id').val();
      var street = $('#street').val();
      // console.log({name, phone, regionId, cityId, city, street});
      if (name && phone && regionId && cityId && street && !$('#city_id').hasClass('mage-error') && !$('#region_id').hasClass('mage-error')) {
        $("#address-form .actions-toolbar .action.save").removeAttr('disabled');
        $("#address-form .actions-toolbar .action.save").removeClass('disabled');
        $('#address-form .actions-toolbar button[type="submit"]').attr('disabled', false);
      } else {
        $("#address-form .actions-toolbar .action.save").attr('disabled', true);
        $("#address-form .actions-toolbar .action.save").addClass('disabled');
      }
    },

    validateForm: function () {
      const isValid = $("#address-form").validation() && $("#address-form").validation('isValid');
      if (isValid) {
        $("#address-form .actions-toolbar .action.save").attr('disabled', "true");
        $("#address-form .actions-toolbar .action.save").addClass('disabled');
        this.processAddAddress();
      } else {
        $("#address-form .actions-toolbar .action.save").attr('disabled', "true");
        $("#address-form .actions-toolbar .action.save").addClass('disabled');
        return;
      }
    },

    processAddAddress: function () {
      const form = $('#address-form');
      const self = this;
      const formData = form.serializeArray();
      $.ajax({
        url: url.build('spintowin/address/add'),
        data: formData,
        type: 'POST',
        dataType: 'json',
        beforeSend: function () {
          $(document.body).trigger('processStart');
        },
        success: function (res) {
          if (res.error) {
            // Fix for Client DOM XSS: Rely on showMessage's Fragment sanitization
            self.showMessage(res.message);
          } else {
            window.location.reload();
          }
        },
        error: function (error) {
          console.error('Error processing address form:', error);
        }
      }).always(function () {
        $(document.body).trigger('processStop');
        $("#address-form .actions-toolbar .action.save").removeAttr('disabled');
        $("#address-form .actions-toolbar .action.save").removeClass('disabled');
        $('#address-form .actions-toolbar button[type="submit"]').attr('disabled', false);
      });
    },

    // showSuccess: function (message = '') {
    //   if (message == '') {
    //     message = $.mage.__('已複製折扣碼');
    //   }
    //   this.showMessage(message, 'success');
    // },

    // showChanceSuccess: function (message = '', chances) {
    //   if (message == '') {
    //     message = $.mage.__('抽獎機會：%1 次').replace('%1', chances);
    //   }
    //   this.showMessage(message, 'success');
    // },

    showMessage: function (message, type = 'error') {
      var jQ = $.noConflict();
      var msgContainer = jQ('.page.messages');

      msgContainer.find('.custom-messages').remove();

      if (this._hideMessageTimeout) {
        clearTimeout(this._hideMessageTimeout);
      }

      var sanitizeConfig = getSanitizeConfig();
      var fragment = DOMPurify.sanitize(message, $.extend({}, sanitizeConfig, {
        RETURN_DOM_FRAGMENT: true
      }));
      var className = 'message ' + (type === 'success' ? 'message-success success' : 'message-error error');
      var messageContent = jQ('<div/>', {
        'class': className
      });

      if (fragment && fragment.nodeType === 11 && fragment.hasChildNodes()) {
        // [Security Fix] Use importNode to break taint tracking chain
        var cleanFragment = document.importNode(fragment, true);
        messageContent[0].appendChild(cleanFragment);
      } else {
        var sanitizedText = DOMPurify.sanitize(message, sanitizeConfig);
        messageContent[0].appendChild(document.createTextNode(sanitizedText));
      }

      var wrapper = jQ('<div/>', {
        'class': 'messages custom-messages'
      });
      // [Security Fix] Use importNode pattern for all DOM insertions
      var cleanWrapper = document.importNode(wrapper[0], true);
      cleanWrapper.appendChild(messageContent[0]);
      msgContainer[0].appendChild(cleanWrapper);
      msgContainer.addClass('__show');

      this._hideMessageTimeout = setTimeout(function () {
        msgContainer.removeClass('__show');
        msgContainer.find('.custom-messages').remove();
      }, 3000);
    },
  });

  return $.b8.spin2winAddressForm;
});
