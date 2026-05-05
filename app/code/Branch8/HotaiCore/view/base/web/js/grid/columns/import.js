define([
  "Magento_Ui/js/grid/columns/column",
  "jquery",
  "mage/template",
  "text!Branch8_HotaiCore/templates/grid/cells/product/import.html",
  "Magento_Ui/js/modal/modal",
], function (Column, $, mageTemplate, importFormTemplate) {
  "use strict";
  return Column.extend({
    defaults: {
      bodyTmpl: "ui/grid/cells/html",
      fieldClass: { "data-grid-html-cell": true },
    },
    gethtml: function (row) {
      return row[this.index + "_html"];
    },
    getFormaction: function (row) {
      return row[this.index + "_formaction"];
    },
    getDownloadsampleaction: function (row) {
      return row[this.index + "_downloadsampleaction"];
    },
    getDownloadsampleformkey: function (row) {
      return row[this.index + "_downloadsampleformkey"];
    },
    getProductid: function (row) {
      return row[this.index + "_productid"];
    },
    getLabel: function (row) {
      return row[this.index + "_html"];
    },
    getTitle: function (row) {
      return row[this.index + "_title"];
    },
    getSubmitlabel: function (row) {
      return row[this.index + "_submitlabel"];
    },
    getCancellabel: function (row) {
      return row[this.index + "_cancellabel"];
    },
    getDisaplyImportButton: function (row) {
      return row[this.index + "_disaplyImportButton"];
    },
    getFormkey: function (row) {
      return row[this.index + "_formkey"];
    },
    preview: function (row) {
      if (!this.getDisaplyImportButton(row)) {
        return;
      }

      let id = '#hotai-core-import-form-' + this.getProductid(row);

      var modalHtml = mageTemplate(importFormTemplate, {
        html: this.gethtml(row),
        title: this.getTitle(row),
        label: this.getLabel(row),
        formaction: this.getFormaction(row),
        downloadsampleaction: this.getDownloadsampleaction(row),
        productid: this.getProductid(row),
        formkey: this.getFormkey(row),
        downloadsampleformkey: this.getDownloadsampleformkey(row),
        submitlabel: this.getSubmitlabel(row),
        cancellabel: this.getCancellabel(row)
      });
      var previewPopup = $("<div/>").html(modalHtml);
      previewPopup.modal({
        title: this.getTitle(row),
        innerScroll: true,
        modalClass: "_image-box",
        buttons: [],
      }).trigger("openModal");

      $(id + ' select[name="notify_limit_type"]').on('change', function () {
        let templateValue = $(this).val();

        if (templateValue === '1') {
          $(id + ' .use_end_time').show();
          $(id + ' .due_days').hide();
        }

        if (templateValue === '2') {
          $(id + ' .use_end_time').hide();
          $(id + ' .due_days').show();
        }
      });

      $(id + ' select[name="notify_limit_type"]').change();

      // 切換手動 / 自動匯入區塊，並處理 required 屬性
      var form = $(id);
      var manualSection = form.find('.manual-import-section');
      var autoSection = form.find('.auto-import-section');
      var autoFields = form.find('#auto_serial_length, #auto_char_type, #auto_group_count');

      form.on('change', 'input[name="import_mode"]', function () {
        updateImportMode($(this).val());
      });

      // 初始化一次，根據預設選項設定狀態
      var initialMode = form.find('input[name="import_mode"]:checked').val() || 'manual';
      updateImportMode(initialMode);

      // auto 模式下檢查字元池組合是否足夠
      form.on('submit', function (event) {
        validateAutoImportCapacity(event);
      });

      function updateImportMode(mode) {
        if (mode === 'auto') {
          manualSection.hide();
          autoSection.show();
          autoFields.prop('required', true);
        } else {
          autoSection.hide();
          manualSection.show();
          autoFields.prop('required', false);
        }
      }

      function validateAutoImportCapacity(event) {
        var currentMode = form.find('input[name="import_mode"]:checked').val() || 'manual';
        if (currentMode !== 'auto') {
          return;
        }

        var lengthValue = parseInt(form.find('#auto_serial_length').val(), 10);
        var groupCountValue = parseInt(form.find('#auto_group_count').val(), 10);
        var charTypeValue = form.find('#auto_char_type').val();
        var prefixValue = form.find('#auto_prefix').val() || '';

        // 若長度或組數非正數，交給 HTML5 其他驗證處理
        if (!lengthValue || lengthValue < 1 || !groupCountValue || groupCountValue < 1) {
          return;
        }

        var prefixLength = prefixValue.length;

        // 確保總長度大於固定開頭長度
        if (prefixLength >= lengthValue) {
          alert('無法生成：序號總長度必須大於固定開頭長度，請調整固定開頭或序號長度');
          if (event && event.preventDefault) {
            event.preventDefault();
          }
          return;
        }

        var bodyLength = lengthValue - prefixLength;

        // 確認總長度是否過長
        var totalLength = lengthValue;
        if (totalLength > 20) {
          var confirmed = window.confirm(
            '序號總長度已達 ' +
              totalLength +
              '，是否確定送出？'
          );
          if (!confirmed) {
            if (event && event.preventDefault) {
              event.preventDefault();
            }
            return;
          }
        }

        var poolSize = 0;
        if (charTypeValue === 'alnum') {
          poolSize = 62; // 52 個英文字母（大小寫）+ 10 個數字
        } else if (charTypeValue === 'num') {
          poolSize = 10;
        } else if (charTypeValue === 'alpha') {
          poolSize = 52; // 26 個大寫 + 26 個小寫
        }

        if (!poolSize || bodyLength <= 0) {
          return;
        }

        var maxUnique = Math.pow(poolSize, bodyLength);

        if (maxUnique < groupCountValue) {
          alert(
            '無法生成：目前設定最大只能產生 ' +
              maxUnique +
              ' 組不重複序號，請增加長度或調整字元組成'
          );
          if (event && event.preventDefault) {
            event.preventDefault();
          }
        }
      }
    },
    getFieldHandler: function (row) {
      return this.preview.bind(this, row);
    },
  });
});
