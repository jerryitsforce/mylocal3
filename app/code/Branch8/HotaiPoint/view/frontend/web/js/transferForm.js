define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'plugins/DOMPurify',
  'matchMedia',
  'mage/validation',
  'moment',
  'mage/url',
  'underscore',
  'domReady!'
], function ($, modal, DOMPurify, matchMedia, validation, moment, urlBuilder, _) {
  'use strict';

  $.widget('b8.hotaiPointTransferForm', {
    options: {
      smsTimestampKey: 'smsVerificationCodeTimestamp',
      smsExpireMinutes: 5,
      retrySms: {
        // 重試次數
        number: 0,
        // 重試最大次數
        max: 3,
        // 重試延遲時間，每隔N秒重試一次
        delay: 3 * 1000, 
      },
      // 簡訊驗證碼 AJAX 超時時間
      smsTimeout: 10 * 1000,
    },

    _create: function () {
      this.checkValidation();
      this.actions();
      this.resetTransfer()
    },

    // ========== 時間戳管理共用方法 ==========

    // 取得當前時間戳（秒）
    getCurrentTimestamp: function () {
      return Math.floor(Date.now() / 1000);
    },

    // 取得儲存的時間戳
    getStoredTimestamp: function () {
      return localStorage.getItem(this.options.smsTimestampKey);
    },

    // 計算剩餘秒數
    // startTimestamp: 開始時間戳（儲存的時間）
    // 計算公式：(開始時間 + 5分鐘) - 當前時間
    calculateRemainingSeconds: function () {
      var startTime = parseInt(this.getStoredTimestamp(), 10);
      var expireTime = startTime + (this.options.smsExpireMinutes * 60);
      return Math.max(0, expireTime - this.getCurrentTimestamp());
    },

    // 檢查是否過期
    // startTimestamp: 開始時間戳（儲存的時間）
    isExpired: function (startTimestamp) {
      if (!startTimestamp || startTimestamp === '0') {
        return true;
      }
      return this.calculateRemainingSeconds(startTimestamp) <= 0;
    },

    // 設定開始時間戳（儲存當前時間）
    setStartTimestamp: function () {
      let timestamp = this.getCurrentTimestamp();
      localStorage.setItem(this.options.smsTimestampKey, timestamp.toString());
      return timestamp;
    },

    // 清除時間戳
    clearTimestamp: function () {
      localStorage.removeItem(this.options.smsTimestampKey);
    },

    checkValidation: function () {
      var transferTo = $('#transfer-to').val(),
        transferPoint = $('#transfer-point').val(),
        transferToInvalid = $('#transfer-to').attr('aria-invalid'),
        transferPointInvalid = $('#transfer-point').attr('aria-invalid');

      if (transferTo && transferPoint && transferToInvalid === 'false' && transferPointInvalid === 'false') {
        $('#transfer-submit-btn').removeClass('disabled').prop('disabled', false);
      } else {
        $('#transfer-submit-btn').addClass('disabled').prop('disabled', true);
      }
    },

    actions: function () {
      var self = this,
        transferTo = $('#transfer-to'),
        transferPoint = $('#transfer-point');

      transferTo.on('input change', function () {
        const validate = $.validator.validateSingleElement($(this));
        if (validate) {
          self.checkValidation();
        } else {
          $('#transfer-submit-btn').addClass('disabled').prop('disabled', true);
        }
      });

      transferPoint.on('input change', function () {
        const validate = $.validator.validateSingleElement($(this));
        if (validate) {
          self.checkValidation();
        } else {
          $('#transfer-submit-btn').addClass('disabled').prop('disabled', true);
        }
      });

      $('#transfer-submit-btn').on('click', function (e) {
        e.preventDefault();
        self.switchStep(2);
      });

      // 取得簡訊驗證碼
      $('#get-sms-verification-code-btn').on('click', function (e) {
        e.preventDefault();
        self.getSMSVerificationCode();
      });

      // 上一步
      $('#back-to-transfer-btn').on('click', function (e) {
        e.preventDefault();
        self.switchStep(1);
      });

      // 轉移前確認
      $('#confirm-transfer-btn').on('click', function (e) {
        e.preventDefault();
        self.processTransferConfirm();
      });
    },
    processTransferConfirm: function () {
      var self = this;
      var smsCode = $('#sms-verification-code').val().trim();
      // 檢查簡訊驗證碼是否為6碼數字
      if (!/^\d{6}$/.test(smsCode)) {
        // 觸發驗證顯示錯誤訊息
        self.showErrorMessagePopup({
          title: '驗證碼錯誤',
          content: '驗證碼輸入錯誤或未輸入，請輸入6碼驗證碼'
        });
        return;
      }

      // 檢查輸入時間是否過期
      var storedTimestamp = self.getStoredTimestamp();

      if (self.isExpired(storedTimestamp)) {
        self.showErrorMessagePopup({
          title: '驗證碼過期',
          content: '驗證碼已過期，請重新取得驗證碼'
        });
        return;
      }

      // 驗證通過，繼續執行後續邏輯
      // 執行轉移確認邏輯
      self.processForm();
    },

    processForm: function () {
      var form = $('#hotai-point-transfer-form');
      if (form.validation() && form.validation('isValid')) {
        var transferTo = $('#transfer-to').val(),
          transferPoint = $('#transfer-point').val();

        if (transferTo && transferPoint) {
          $("#info-transfer-to").text(transferTo);
          $("#info-transfer-point").text(transferPoint);
          $('#confirm-transfer-modal').modal('openModal');
        }
      }
    },

    resetTransfer: function () {
      $('#transfer-to').val('');
      $('#transfer-point').val('');
      this.switchStep(1);
      this.options.retrySms.number = 0;
    },

    // 切換步驟
    switchStep: function (step) {
      var self = this;
      var transferTo = $('#transfer-to');
      var transferPoint = $('#transfer-point');
      var transferInfoPhone = $('.hotai-point-form-container .transfer-confirm-details .transfer-info-phone');
      var transferInfoPoint = $('.hotai-point-form-container .transfer-confirm-details .hotai-customer-points');

      switch (step) {
        case 1:
          // back step 1 & reset step 2
          transferInfoPhone.text('');
          transferInfoPoint.text('');
          $('#sms-verification-code').val('');
          $('#confirm-transfer-btn').prop('disabled', true);
          $('.sms-verification-code-input-container label').text('****');
          $('.sms-verification-code-resend-container .wait-message-time').text('00:00');
          $('.sms-verification-code-resend-container').attr('data-waiting', 'false');
          self.clearTimestamp();
          break;
        case 2:
          transferInfoPhone.text(transferTo.val());
          transferInfoPoint.text(transferPoint.val());
          self.getSMSVerificationCode();
          break;
        default:
          break;
      }

      $('.hotai-point-form-container').attr('data-current-step', step);
    },

    // 取得簡訊驗證碼
    getSMSVerificationCode: function () {
      var self = this;
      console.log('# try again getSMSVerificationCode:', self.options.retrySms.number);
      // 取得storage中的時間戳記，如果沒有，新增一個
      var storedTimestamp = self.getStoredTimestamp();
      if (self.isExpired(storedTimestamp)) {
        // 呼叫 API 取得簡訊驗證碼
        $.ajax({
          url: urlBuilder.build('rest/V1/hotaipoint/getmemberotp'),
          type: 'POST',
          timeout: self.options.smsTimeout,
          headers: {
            'Content-Type': 'application/json; charset=utf-8'
          },
          data: JSON.stringify({
            transferTo: $('#transfer-to').val()
          }),
          beforeSend: function () {
            $('body').trigger('processStop'); // 避免重疊觸發 processStart
            $('body').trigger('processStart');
          },
          success: function (response) {
            $('body').trigger('processStop');

            var result = JSON.parse(response);
            if(result.exceedinglimit === true) {
              // ✅ 使用結構化的方式傳遞連結資訊
              self.showErrorMessagePopup({
                title: '本日寄送次數達上限',
                content: '您的點數移轉簡訊驗證碼已達每日寄送上限（5次），請於明日再進行操作。如有疑問，請聯繫客服，謝謝。',
                link: {
                  href: 'helpdesk',
                  text: '客服',
                  insertAfter: '請聯繫'
                }
              });
              return;
            }

            if (result.success) {
              $('.sms-verification-code-input-container label').text(result.returnMsg);
              self.clearTimestamp();
              $('#confirm-transfer-btn').prop('disabled', false);
              // 設定開始時間戳（儲存當前時間）
              var startTimestamp = self.setStartTimestamp();
              self.setSMSVerificationCodeTime(startTimestamp);
            } else {
              if (result?.returnCode === "0339") {
                self.processForm();
              }
              self.showErrorMessagePopup({
                content: result?.errMsg
              });
            }
            
          },
          error: function (jqXHR, textStatus, errorThrown) {
            // 顯示 SMS 驗證碼上限 popup（如果 errorMsg 為 null，會使用預設內容）
            var errorMsg = jqXHR.responseJSON?.errMsg;
            // 過濾可能的 HTML 內容
            if (errorMsg && typeof errorMsg === 'string') {
              errorMsg = errorMsg.replace(/<[^>]*>/g, ''); // 移除所有 HTML 標籤
            }

            console.log('# textStatus', textStatus);
            console.log('# errorMsg', errorMsg);
            if(self.contentFilter(errorMsg)) {
              console.log('# 執行重試:');
              // 不符合預期或網路不穩進行以下處理
              // 超過最大次數，顯示預設內容
              // 重試次數小於最大次數，重試
              if(self.options.retrySms.number < self.options.retrySms.max) {
                self.options.retrySms.number++;
                setTimeout(function () {
                  self.getSMSVerificationCode();
                }, self.options.retrySms.delay);
              } else {
                self.options.retrySms.number = 0;
                self.smsModalDefault();
                $('body').trigger('processStop');
              }
            } else {
              console.log('# 顯示點數錯誤訊息:');
              self.showErrorMessagePopup({
                content: errorMsg
              });
              $('body').trigger('processStop');
            }
          }
        })

      } else {
        // 使用儲存的時間戳繼續倒數
        self.setSMSVerificationCodeTime(parseInt(storedTimestamp, 10));
      }
    },

    // setTimeout - 統一使用開始時間戳作為參數
    setSMSVerificationCodeTime: function (startTimestamp) {
      var self = this;
      // 計算剩餘時間（秒）：(開始時間 + 5分鐘) - 當前時間
      var remainingSeconds = self.calculateRemainingSeconds();

      // 確保剩餘時間不超過5分鐘（300秒）
      var maxSeconds = self.options.smsExpireMinutes * 60;
      remainingSeconds = Math.min(remainingSeconds, maxSeconds);

      var waitMessageTimeText = '';
      if (remainingSeconds > 0) {
        var minutes = Math.floor(remainingSeconds / 60);
        var seconds = remainingSeconds % 60;
        waitMessageTimeText = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
      }

      $('.sms-verification-code-resend-container .wait-message-time').html(waitMessageTimeText);

      // 如果已過期，清除時間戳並停止倒數
      if (waitMessageTimeText === '' || remainingSeconds <= 0) {
        $('#confirm-transfer-btn').prop('disabled', true);
        $('.sms-verification-code-resend-container').attr('data-waiting', 'false');
        self.clearTimestamp();
        return;
      }

      $('.sms-verification-code-resend-container').attr('data-waiting', 'true');

      // 每秒更新一次（傳入相同的開始時間戳，由 calculateRemainingSeconds 計算剩餘時間）
      setTimeout(function () {
        self.setSMSVerificationCodeTime(startTimestamp);
      }, 1000);
    },

    // 檢查 content 是否為空或不符合預期
    contentFilter: function (content) {
      return [
        '',
        'NULL',
        'Something went wrong while requesting hotai point api.'
      ].includes(content) || _.isEmpty(content);
    },

    // 顯示預設內容的 modal
    smsModalDefault: function () {
      console.log('# 執行顯示預設內容的 smsModalDefault:');

      var self = this;

      var $modal = $('#sms-limit-modal');
      var $contentContainer = $modal.find('.modal-custom-content');
      $contentContainer.empty();
      $contentContainer.text('請點擊下方的「重新發送」，我們將立即重新為您寄出簡訊驗証碼。');

      console.log('# 執行顯示預設內容的 modal:');

      var options = {
        type: 'popup',
        responsive: false,
        title: '傳送簡訊驗證碼失敗',
        modalClass: 'modal-custom hotaipoint-sms-limit-popup',
        buttons: [{
          text: $.mage.__('重新發送'),
          class: 'action primary action-primary',
          click: function () {
            self.getSMSVerificationCode();
            this.closeModal();
          }
        }]
      };

      var popup = modal(options, $modal);
      popup.openModal();
    },

    // 顯示 SMS 驗證碼上限 popup
    // data: { title: '標題', content: '內容' }
    showErrorMessagePopup: function (data) {
      console.log('# 執行顯示錯誤訊息的 showErrorMessagePopup:');
      var self = this;
      data = data || {};

      var $modal = $('#sms-limit-modal');
      var $contentContainer = $modal.find('.modal-custom-content');
      $contentContainer.empty();

      // 設定 title 和 content（使用預設值或傳入的值）
      var title = data.title || $.mage.__('訊息通知');
      var content = data.content || '';
      var link = data.link;

      if (typeof content === 'string') {
        content = content.replace(/<[^>]*>/g, '');
      }

      var $p = $('<p>');

      if (link && link.href && link.text && link.insertAfter) {
        var safeHref = self.getSafeHref(link.href);
        var insertIndex = content.indexOf(link.insertAfter);

        if (insertIndex !== -1 && safeHref !== null) {
          var beforeLink = content.substring(0, insertIndex + link.insertAfter.length);
          var afterLink = content.substring(insertIndex + link.insertAfter.length);

          $p.append(document.createTextNode(beforeLink));

          if (safeHref) {
            var $link = $('<a>').attr('rel', 'noopener noreferrer');

            // 使用 setAttribute 並明確驗證
            $link[0].setAttribute('href', safeHref);
            $link.text(link.text);
            $p.append($link);
          } else {
            // 如果 URL 不安全，只顯示文字
            $p.append(document.createTextNode(link.text));
          }

          $p.append(document.createTextNode(afterLink));
        } else {
          $p.text(content);
        }
      } else {
        $p.text(content);
      }

      $contentContainer.append($p);
      console.log('# 執行顯示預設內容的 modal:');

      var options = {
        type: 'popup',
        responsive: false,
        title: title,
        modalClass: 'modal-custom hotaipoint-sms-limit-popup',
        buttons: [{
          text: $.mage.__('我知道了'),
          class: 'action primary action-primary',
          click: function () {
            self.options.retrySms.number = 0;
            this.closeModal();
          }
        }]
      };

      var popup = modal(options, $modal);
      popup.openModal();

    },

    getSafeHref: function(linkIdentifier) {
      // 定義允許的連結白名單（硬編碼，無法被外部輸入影響）
      var allowedLinks = {
        'helpdesk': '/helpdesk/ticket/',
        'contact': '/contact/',
        'faq': '/faq/',
        'terms': '/terms/',
        'privacy': '/privacy/'
      };

      // 如果傳入的是白名單中的 key，返回對應的路徑
      if (allowedLinks.hasOwnProperty(linkIdentifier)) {
        return allowedLinks[linkIdentifier];
      }

      // 如果傳入的是相對路徑，進行嚴格驗證
      if (typeof linkIdentifier === 'string') {
        // 只允許以 / 開頭的相對路徑，且不包含危險字元
        var safePathPattern = /^\/[a-zA-Z0-9\-_\/]*$/;

        if (safePathPattern.test(linkIdentifier)) {
          // 額外檢查：不允許 .. 或連續的 //
          if (linkIdentifier.indexOf('..') === -1 &&
              linkIdentifier.indexOf('//') === -1 &&
              linkIdentifier.indexOf('javascript') === -1 &&
              linkIdentifier.indexOf('data:') === -1 &&
              linkIdentifier.indexOf('vbscript') === -1) {
            return linkIdentifier;
          }
        }
      }

      // 不符合條件，返回 null（不建立連結）
      return null;
    },

    sanitizeUrl: function(url) {
      if (!url || typeof url !== 'string') {
        return '#';
      }

      url = url.trim();
      var allowedProtocols = ['http:', 'https:', 'mailto:', 'tel:'];

      try {
        var parsedUrl = new URL(url, window.location.origin);

        if (allowedProtocols.indexOf(parsedUrl.protocol) === -1) {
          return '#';
        }

        if (parsedUrl.protocol === 'http:' || parsedUrl.protocol === 'https:') {
          if (parsedUrl.origin === window.location.origin) {
            return parsedUrl.href;
          }
          return '#';
        }

        return parsedUrl.href;
      } catch (e) {
        if (url.startsWith('/') && !url.startsWith('//')) {
          return url;
        }
        return '#';
      }
    },

  });

  return $.b8.hotaiPointTransferForm;
});
