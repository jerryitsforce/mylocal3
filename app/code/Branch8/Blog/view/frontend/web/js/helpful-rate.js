/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    $.widget('mageplaza.BlogHelpfulRate', {
        options: {
            url: '',
            post_id: '',
            mode: ''
        },
        _create: function () {
            var post_id = this.options.post_id,
                url = this.options.url,
                self = this;

            // Handle initial state and subscriptions
            var branch8Data = customerData.get('branch8_customer_data');
            
            var getBlogPostData = function() {
                var data = branch8Data();
                return (data && data.blog_post_data) ? data.blog_post_data : {};
            };

            var updateReviewState = function(data) {
                if (data && data.posts && data.posts[post_id]) {
                    self.disableReview(data.posts[post_id].type);
                } else {
                    self.enableAllReview();
                }
            };

            // Initialization for mode 1 (server-side check)
            if (self.options.mode === 1) {
                $.ajax({
                    url: url,
                    type: "post",
                    data: {
                        post_id: post_id,
                        action: '3',
                        mode: self.options.mode
                    },
                    showLoader: false,
                    success: function (response) {
                        if (response.status === 0) {
                            self.disableReview(response.action);
                        }
                    }
                });
            } else {
                // For guest mode (or mode 0), use CustomerData
                updateReviewState(getBlogPostData());
                branch8Data.subscribe(function() {
                    updateReviewState(getBlogPostData());
                });
            }

            $('#blog-review div').each(function () {
                var el = this;

                $(el).on('click', function () {
                    var action = 0,
                        currentPosts = {},
                        likeId = 0;

                    var sectionData = getBlogPostData();
                    if (sectionData && sectionData.posts) {
                        currentPosts = sectionData.posts;
                    }

                    if ($(this).hasClass('blog-like')) {
                        action = 1;
                    }

                    if (typeof currentPosts[post_id] !== "undefined" && self.options.mode === 0) {
                        likeId = currentPosts[post_id].likeId;
                    }

                    $.ajax({
                        url: url,
                        type: "post",
                        data: {
                            post_id: post_id,
                            action: action,
                            mode: self.options.mode,
                            likeId: likeId
                        },
                        showLoader: true,
                        success: function (response) {
                            if (response['status']) {
                                if (response["sumLike"]) {
                                    $('#blog-review .blog-like .blog-view')
                                        .text('(' + response["sumLike"] + ')');
                                } else {
                                    $('#blog-review .blog-like .blog-view')
                                        .text('');
                                }
                                if (response["sumDislike"]) {
                                    $('#blog-review .blog-dislike .blog-view')
                                        .text('(' + response["sumDislike"] + ')');
                                } else {
                                    $('#blog-review .blog-dislike .blog-view')
                                        .text('');
                                }

                                if (self.options.mode === 1) {
                                    self.enableAllReview();
                                    if (response['postLike']) {
                                        self.disableReview(action);
                                    }
                                }
                                // For mode 0, CustomerData subscription handles the UI update
                                
                                // Invalidate section to force reload if not already triggered by sections.xml
                                // (Though sections.xml should handle it for 'blog/post/review')
                            }

                            $('html, body').animate({
                                scrollTop: $('body').offset().top
                            }, 500);
                        }
                    });
                });
            });
        },
        disableReview: function (action) {
            if ('' + action === '1') {
                $('.blog-like').css('background-color', '#658259');
                $('.blog-dislike').css('background-color', '#EC3A3C');
            } else {
                $('.blog-dislike').css('background-color', '#9a6464');
                $('.blog-like').css('background-color', '#6AA84F');
            }
        },
        enableReview: function (action) {
            if ('' + action === '1') {
                $('.blog-like').css('background-color', '#6AA84F');
            } else {
                $('.blog-dislike').css('background-color', '#EC3A3C');
            }
        },
        enableAllReview: function () {
            $('.blog-like').css('background-color', '#6AA84F');
            $('.blog-dislike').css('background-color', '#EC3A3C');
        }
    });

    return $.mageplaza.BlogHelpfulRate;
});
