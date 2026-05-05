require([
    'jquery',
    'mage/url',
    'perfectScrollbar',
    'plugins/DOMPurify'
], function ($, urlBuilder, PerfectScrollbar, DOMPurify) {
    "use strict";

    var jQ = $.noConflict();
    
    jQ(document).ready(function () {
        const loadMoreButton = jQ('#load-more');
        const faqContentWrapper = jQ('.faq-tab-content-wrapper');
        const faqPaginationWrapper = jQ('.faq-pagination');
        const MENU_OFFSET = 100;
        const SCROLL_TO_ANIMATION_TIME = 200;
    
        const tabs = $('.faq-tab');
        const activedTab = $('.faq-tab.active');
        if(activedTab.length > 0) {
            const categoryId = activedTab.data('category-id');
            loadCategoryContent(categoryId, 1);
            loadMoreButton.data('faq-page', 1);
        } else if(tabs.length > 0) {
            loadCategoryContent(tabs[0].dataset.categoryId, 1);
        }

        
        $(".faq-tab-navigation").on("beforeOpen", function (event) {
            const categoryId = event.target.dataset.categoryId;
            
            loadCategoryContent(categoryId, 1, false, true);
                
            loadMoreButton.data('faq-page', 1);
        });

        

        // Load category content using AJAX
        function loadCategoryContent(categoryId, page, loadMore = false, changePage = false) {
            jQ.ajax({
                url: urlBuilder.build('faq/category/load'),
                type: 'GET',
                data: {
                    'category_id': categoryId,
                    'page': page
                },
                success: function (response) {
                    try {
                        if (!loadMore) {
                            faqContentWrapper.find('ol').empty(); // Clear the placeholder or previous content
                        }

                        if (response.success && response.html) {
                            const sanitizedResHtml = DOMPurify.sanitize(response.html);
                            if (loadMore) {
                                // Append the new <li> items to the existing <ol> list (avoid adding a new <ol>)
                                faqContentWrapper.find('ol').append(sanitizedResHtml);
                            } else {
                                // Replace content for pagination or initial load
                                faqContentWrapper.find('ol').html(sanitizedResHtml); // Replace <li> items only
                            }

                            // Update the "Load More" button visibility (for mobile)
                            if (response.has_more) {
                                loadMoreButton.show();
                            } else {
                                loadMoreButton.hide(); // Hide if there's no more content to load
                            }

                            // Update the pagination section (for desktop)
                            const sanitizedResPagination = DOMPurify.sanitize(response.pagination);
                            faqPaginationWrapper.html(sanitizedResPagination); // Update pagination HTML

                            if(loadMore) {
                                $(".faqs-list").accordion('refresh');
                            }
                            else {
                                if(changePage) {
                                    $(".faqs-list").accordion('destroy');                        
                                }

                                $(".faqs-list").accordion({
                                    collapsible: true, multipleCollapsible: false, openedState: "active", animate: { duration: 0 },
                                    activate: function (event, ui) {
                                        if (ui.newHeader.length > 0) {
                                            const offset = ui.newHeader.offset().top - MENU_OFFSET;
                                            $('html, body').animate({
                                                scrollTop: offset
                                            }, SCROLL_TO_ANIMATION_TIME);
                                        }
                                    }
                                });
                            }
                            
                            // Bind pagination click events for desktop pagination
                            bindPagination();

                        } else {
                            faqContentWrapper.html('<p>No FAQs available for this category.</p>'); // Show no content message
                            loadMoreButton.hide(); // Hide "Load More" button if no content
                            faqPaginationWrapper.empty(); // Clear pagination if no content
                        }
                    } catch (err) {
                        console.error('Error processing response: ', err);
                        // alert($.mage.__('An error occurred while loading the FAQs.'));
                        showMessage($.mage.__('An error occurred while loading the FAQs.'));
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX request failed: ', textStatus, errorThrown);
                    // alert($.mage.__('Error while loading FAQs. Please try again later.'));
                    showMessage($.mage.__('Error while loading FAQs. Please try again later.'));
                }
            });
        }

        // Handle "Load More" button clicks
        loadMoreButton.on('click', function (e) {
            e.preventDefault();
            const categoryId = $('.faq-tab.active a').data('category-id');
            const currentPage = parseInt(loadMoreButton.data('faq-page'), 10);
            const nextPage = currentPage + 1;


            loadCategoryContent(categoryId, nextPage, true, false); // Append more content
            // Update the page number in the button
            loadMoreButton.data('faq-page', nextPage);
        });
        // Function to bind click events to pagination links
        function bindPagination() {
            $('.pager .page, .pager .previous, .pager .next').on('click', function (e) {
                e.preventDefault();

                if ($(this).hasClass('disabled')) {
                    return;    
                } else {
                    const page = $(this).data('page');
                    const categoryId = $('.faq-tab.active a').data('category-id');
                    
                    loadCategoryContent(categoryId, page, false, true);
                    loadMoreButton.data('faq-page', page);

                    const offset = $(".faq-tab-navigation").offset().top - MENU_OFFSET;
                    $('html, body').animate({
                        scrollTop: offset
                    }, SCROLL_TO_ANIMATION_TIME);
                }
            });
        }
        function setupPerfectScrollbar() {
            const container = document.querySelector('.faq-tab-navigation-wrapper');
            const ps = new PerfectScrollbar(container);        
            $(window).resize(function() {           
                ps.update();
            });
        }

        function showMessage(message, type = 'error') {
            var msgContainer = $('.page.messages');
            msgContainer.append('<div class="messages custom-messages"><div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'">' + message + '</div></div>');
            msgContainer.addClass('__show');
            var timeCheck;
            clearTimeout(timeCheck);
            timeCheck = setTimeout(function () {
                msgContainer.removeClass('__show');
                msgContainer.find('.custom-messages').remove();
            }, 3000);
        }

        bindPagination();

        setupPerfectScrollbar();
    });
});
