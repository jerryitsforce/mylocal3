define([
    'jquery',
    'plugins/DOMPurify',
    'tabs',
    'matchMedia',
    'mage/translate',
    'domReady!',
    'mage/loader'
], function ($, DOMPurify) {
    'use strict';
    $.widget(
        'b8.blog',
        {
            _create: function () {
                this._loadMore();
            },

            /**
             * Bind the click event for the "Load More" button
             */
             _loadMore: function () {
                var self = this;
                var jQ = $.noConflict();
                var latest = false;
                jQ(document).on('click', '#load-more', function (e) {
                    e.preventDefault();

                    jQ('body').loader('show');
                    // Retrieve current page and calculate next page
                    var currentPage = parseInt(jQ(this).data('current-page'));
                    // console.log('Current page: ', currentPage);
                    if (isNaN(currentPage)) {
                        console.error('Invalid current page number');
                        jQ('body').loader('hide');
                        return;  // Exit if the current page is not a number
                    }

                    var nextPage = currentPage + 1;
                    
                    // console.log('Current page: ', currentPage, nextPage, latest);

                    if(latest){
                        jQ('body').loader('hide');
                        return;
                    }

                    // AJAX call to load more categories
                    var url = window.location.href,
                        url = url.replace(window.location.search, ''),
                        pageCount = $('#blog-page-count').text();
                    jQ.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            'p': nextPage,
                            'is_scroll': 1
                        },
                        success: function (response) {
                            try {
                                const sanitizedHtml = DOMPurify.sanitize(response);
                                jQ('.load-more-container').before(sanitizedHtml);
                                jQ('#load-more').data('current-page', nextPage);

                                // Hide the button if no more categories to load
                                if (nextPage === parseInt(pageCount)) {
                                    jQ('.load-more-container').addClass('latest').hide();
                                    latest = true;
                                }
                            } catch (err) {
                                console.error('Error processing response: ', err);
                                alert($.mage.__('An error occurred while loading more orders.'));
                            } finally {
                                jQ('body').loader('hide');
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            // Handle AJAX error
                            console.error('AJAX request failed: ', textStatus, errorThrown);
                            alert($.mage.__('Error while loading more orders. Please try again later.'));
                            jQ('body').loader('hide');
                        }
                    });
                });
            }
        }
    );
    return $.b8.blog;
});
