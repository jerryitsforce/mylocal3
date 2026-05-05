define([
    'jquery',
    'mage/url',
    'plugins/DOMPurify',
    'tabs',
    'matchMedia',
    'mage/translate',
    'domReady!',
    'mage/loader'
], function ($,urlBuilder, DOMPurify) {
    'use strict';
    $.widget(
        'b8.history',
        {
            _create: function () {
                this._bindEvents();
            },

            /**
             * Bind the click event for the "Load More" button
             */
            _bindEvents: function () {
                var self = this;
                var jQ = $.noConflict();
                var latest = false;
                jQ(document).on('click', '#load-more', function (e) {
                    e.preventDefault();

                    jQ('body').loader('show');
                    // Retrieve current page and calculate next page
                    var currentPage = parseInt(jQ(this).data('event-page'));
                    // console.log('Current page: ', currentPage);
                    if (isNaN(currentPage)) {
                        console.error('Invalid current page number');
                        jQ('body').loader('hide');
                        return;  // Exit if the current page is not a number
                    }

                    var nextPage = currentPage + 1;
                    var pageSize = self.options.pageSize;

                    
                    // console.log('Current page: ', currentPage, nextPage, pageSize, latest);

                    // Validate page size
                    if (isNaN(pageSize) || pageSize <= 0) {
                        console.error('Invalid page size');
                        alert($.mage.__('Invalid page size.'));
                        jQ('body').loader('hide');
                        return;  // Exit if page size is not valid
                    }

                    if(latest){
                        jQ('body').loader('hide');
                        return;
                    }

                    // AJAX call to load more categories
                    jQ.ajax({
                        url: urlBuilder.build('sales/ajax/loadmore') + window.location.search,
                        type: 'GET',
                        data: {
                            'event-page': nextPage,
                            'pageSize': pageSize
                        },
                        success: function (response) {
                            try {
                                // console.log('Response: ', response, response.success, response.has_more , !response.has_more? 'ok':'no');
                                // Check if the response is successful
                                if (response.success) {
                                    const sanitizedHtml = DOMPurify.sanitize(response.html);
                                    jQ('.load-more-container').before(sanitizedHtml);
                                    jQ('#load-more').data('event-page', nextPage);

                                    // Hide the button if no more categories to load
                                    if (!response.has_more) {
                                        jQ('.load-more-container').addClass('latest').hide();
                                        latest = true;
                                    }
                                } else {
                                    console.warn('Response returned as unsuccessful');
                                    alert($.mage.__('Unable to load more orders.'));
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
    return $.b8.history;
});
