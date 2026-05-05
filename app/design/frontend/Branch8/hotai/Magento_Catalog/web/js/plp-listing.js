define([
  'jquery',
  'domReady!'
], function ($) {
  'use strict';
  $.widget(
    'b8.plplisting',
    {
      _create: function () {
        'use strict';

        const showMoreLabel = $.mage.__('Show more content');
        const showLessLabel = $.mage.__('Show less content');
        const charLimit = $(window).width() > 768 ? 100 : 60;
        
        const $textContent = $('.page-layout-category-promotion-listing-page .category-view .promotion-banner .promotion-description');
        const originalText = $textContent.text();
        var isExpanded = false;

        // Check if the content exceeds the character limit
        if (originalText.length > charLimit) {
          const truncatedText = originalText.substring(0, charLimit) + '...';

          // Display truncated text and show "Load More" button
          $textContent.text(truncatedText);

          const $toggleLink = $('<a href="#" class="load-more-toggle">' + showMoreLabel + '</a>');

          $textContent.append($toggleLink);

          function toggleText() {
            if (!isExpanded) {
              $textContent.text(originalText);
              $toggleLink.text(showLessLabel);
            } else {
              $textContent.text(truncatedText);
              $toggleLink.text(showMoreLabel);
            }

            isExpanded = !isExpanded;
            $textContent.append($toggleLink); 
            $toggleLink.off('click').on('click', function (event) {
              event.preventDefault();
              toggleText(); 
            });
          }          

          // Initial event registration
          $toggleLink.on('click', function (event) {
            event.preventDefault();
            toggleText();
          });
        }

        $textContent.css('display',  'unset');
      },
    }
  );


  return $.b8.plplisting;
});
