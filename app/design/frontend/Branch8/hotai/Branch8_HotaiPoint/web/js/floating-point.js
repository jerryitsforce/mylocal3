define([
  "jquery",
  "domReady!"
], function ($) {
    'use strict';

    return function floatingPointBtn(config) {
      // Point floating button
      $(document).on("click", ".point-floating-button .link", function () {
          $("html,body").animate({
                  scrollTop: $('.product-point-section').offset().top,
              },
              400
          );
      });
      
      // Close point link button
      $(document).on(
          "click",
          ".point-floating-button .action.close",
          function () {
              $(this).closest(".point-floating-button").addClass("closed");
          }
      );
    };
});
