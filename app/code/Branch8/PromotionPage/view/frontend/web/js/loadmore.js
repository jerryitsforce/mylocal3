define([
    'jquery',
    'mage/url',
    'plugins/DOMPurify',
    'tabs',
    'matchMedia',
    'mage/translate',
    'domReady!'
], function ($,urlBuilder, DOMPurify) {
    'use strict';
    $.widget(
        'b8.loadmore',
        {
            _create: function () {
                'use strict';
                console.log('Loadmore Loaded')
                this._bindEvents();
            },

            /**
             * Bind the click event for the "Load More" button
             */
            _bindEvents: function () {
                var self = this;
                var jQ = $.noConflict();
                jQ(document).on('click', '#load-more', function (e) {
                    e.preventDefault();
                    jQ(this).prop('disabled', true);
                    jQ(this).addClass("disabled");

                    // Retrieve current page and calculate next page
                    var currentPage = parseInt(jQ(this).data('event-page'));
                    if (isNaN(currentPage)) {
                        console.error('Invalid current page number');
                        return;  // Exit if the current page is not a number
                    }

                    var nextPage = currentPage + 1;
                    var pageSize = self.options.pageSize;

                    // Validate page size
                    if (isNaN(pageSize) || pageSize <= 0) {
                        console.error('Invalid page size');
                        alert($.mage.__('Invalid page size.'));
                        return;  // Exit if page size is not valid
                    }

                    // AJAX call to load more categories
                    jQ.ajax({
                        url: urlBuilder.build('promotionpage/ajax/loadmore'),
                        type: 'GET',
                        data: {
                            'event-page': nextPage,
                            'pageSize': pageSize
                        },
                        success: function (response) {
                            try {
                                // Check if the response is successful
                                if (response.success) {
                                    const sanitizedResHtml = DOMPurify.sanitize(response.html);
                                    jQ('.category-list').append(sanitizedResHtml);
                                    jQ('#load-more').data('event-page', nextPage);

                                    // Hide the button if no more categories to load
                                    if (!response.has_more) {
                                        jQ('#load-more').hide();
                                    }
                                } else {
                                    console.warn('Response returned as unsuccessful');
                                    alert($.mage.__('Unable to load more categories.'));
                                }
                            } catch (err) {
                                console.error('Error processing response: ', err);
                                alert($.mage.__('An error occurred while loading more categories.'));
                            }
                            jQ(this).prop('disabled', false);
                            jQ(this).removeClass("disabled");
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            // Handle AJAX error
                            console.error('AJAX request failed: ', textStatus, errorThrown);
                            alert($.mage.__('Error while loading more categories. Please try again later.'));
                            jQ(this).prop('disabled', false);
                            jQ(this).removeClass("disabled");
                        }
                    });
                });
            }
        }
    );
    return $.b8.loadmore;
});
