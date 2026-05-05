define([
  'jquery',
  'plugins/DOMPurify',
  'Branch8_Spin2Win/js/dompurify-config',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify, getSanitizeConfig) {
  $.widget('b8.spin2win', {
    options: {},

    _create: function () {
      var jQ = $.noConflict();
      var pageName = jQ('.breadcrumbs .items > li:last-child strong').text();
      var text = jQ('.breadcrumbs .items > li:nth-last-child(2) a').text();
      var contentPageName = jQ('<span class="spin2win-page-name"/>');
      contentPageName.text(pageName);
      var contentText = jQ('<span/>');
      contentText.text(text);
      const content = DOMPurify.sanitize(
        contentText.prop('outerHTML') + contentPageName.prop('outerHTML'),
        getSanitizeConfig()
      );
      jQ('.breadcrumbs .item:nth-last-child(2) a').addClass('txt-ready').html(content);
      jQ('.breadcrumbs .items > li:last-child').addClass('txt-hide');
    }
});

return $.b8.spin2win;
});
