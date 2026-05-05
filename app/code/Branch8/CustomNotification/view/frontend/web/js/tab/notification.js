define(
    [
        'jquery',
        'underscore',
        'mage/translate',
        'plugins/DOMPurify'
    ],
    function ($, _, $t, DOMPurify) {
        'use strict';
        return function (config) {
            var urlBase = config.baseUrl;
            var jQ = $.noConflict();
            $(document).ready(function () {
                $(".category-notification-btn").on('click touch', function () {
                    var id = this.id;
                    var notification_type_id = id.replace('filter-', '');
                    if ($("#" + id).hasClass('is_filter')) {
                        window.location.href = urlBase;
                    } else {
                        window.location.href = urlBase + "?type=" + notification_type_id;
                    }
                    return false;
                })
            });

            jQ(document).on('click', '#load-more', function (e) {
                e.preventDefault();
                var button = $(this);
                var currentPage = parseInt(button.data('noti-page'));
                var nextPage = currentPage + 1;
                var pageSize = config.pageSize;
                var activeFilterBtn = $(".category-notification-btn.is_filter");
                var notification_type_id = activeFilterBtn.attr('id').replace('filter-', '');
                jQ.ajax({
                    url: config.url,
                    type: 'GET',
                    data: {
                        'noti-page': nextPage,
                        'pageSize': pageSize,
                        'mobile-type': notification_type_id
                    },
                    beforeSend: function() {
                        $('body').loader('show');
                        // button.text($t('Loading...')).prop('disabled', true);
                    },
                    success: function (response) {
                        if (response.success) {
                            jQ('.table-body').append(DOMPurify.sanitize(response.html));
                            button.data('noti-page', nextPage);

                            if (!response.has_more) {
                                button.hide();
                            }
                        } else {
                            console.warn('Response returned as unsuccessful');
                            alert($t('Unable to load more notifications.'));
                        }
                    },
                    error: function () {
                        alert($t('Error while loading more notifications. Please try again later.'));
                    },
                    complete: function() {
                        $('body').loader('hide');
                        // button.text($t('更多活動')).prop('disabled', false);
                    }
                });
            });
        }
    },
);
