define([
  'jquery',
  'plugins/DOMPurify',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify) {
  $.widget('b8.giftNote', {
    options: {
      giftNoteSectionEle: '.giftbox-confirm-info',
      giftNoteViewBtn: '#giftbox-confirm-show-more-button'
    },

    _create: function () {
      this._bindEvents();
    },

    _bindEvents: function () {
      var self = this;
      $(document).on('click', self.options.giftNoteViewBtn, function (event) {
        event.preventDefault();
        $(this).parents(self.options.giftNoteSectionEle).addClass('expanded');
      });
    }
  });

  return $.b8.giftNote;
});
