define([
    'jquery',
    'plugins/DOMPurify',
    'matchMedia',
    'mage/mage',
    'slideUpSticky',
    'domReady!'
], function ($, DOMPurify) {
    'use strict';

    $.widget('b8.pdpViewMore', { 
      _create: function () {
        'use strict';
        console.log('PDP View More widget created', this.options, this.element);
        var self = this;
        var $content = $(this.element).find(".product-value-inner");
        var $btn = $(this.element).find(".action.more");
        $btn.off("click");
        $btn.on("click", function () {
          console.log('click More btn');
          $content.toggleClass("expanded");
          if ($content.hasClass("expanded")) {
            $btn.parent().removeClass('show');
            // $btn.text("Show less");
          } else {
            // $btn.text("Show more");
          }
        });

        self.updateScrollHeight();
      },

      updateScrollHeight: function() {
        var self = this;
        var $content = $(this.element).find(".product-value-inner");
        var $btn = $(this.element).find(".action.more");
        // var collapsedHeight = 150; // same as CSS max-height

        mediaCheck({
            media: "(max-width: 768px)",
            entry: $.proxy(function () {
              var collapsedHeight = self.options.minHeightMobile || 587;
              console.log('mobile', collapsedHeight, $content.prop("scrollHeight"));
              if ($content.prop("scrollHeight") > collapsedHeight) {
                $content.removeClass("expanded");
                $btn.parent().addClass("show");
              }
            }, this),
            exit: $.proxy(function () {
              var collapsedHeight = self.options.minHeightDesktop || 712;
              console.log('desktop', collapsedHeight, $content.prop("scrollHeight"));
              if ($content.prop("scrollHeight") > collapsedHeight) {
                $content.removeClass("expanded");
                $btn.parent().addClass("show");
              }
            }, this),
        });
      }
    });

    return $.b8.pdpViewMore;
});
