define([
  'jquery',
  'plugins/DOMPurify',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify) {
  $.widget('b8.giftBoxConfirm', {
    options: {},

    _create: function () {
      console.log('Gift Box Confirm Widget Initialized');
    }
  });

  return $.b8.giftBoxConfirm;
});
