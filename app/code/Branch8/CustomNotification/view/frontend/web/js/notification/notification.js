define(
    [
        'jquery',
        'rjsResolver',
        'uiComponent',
        'ko',
        'underscore',
        'mageUtils'
    ],
    function ($, rjsResolver, Component, ko, _, utils) {
        'use strict';
        return Component.extend({
            default: {
                template: {
                    name: "Magenest_NotificationBox/notification"
                },
                allNotification: null,
                urlNotificationTab: null,
                description: null,
                titleBackgroundColorValue: null,
                visibleNotification: false,
                totalNotificationUnread: 0,
                visibleNotificationBox: false,
                boxPosition: '',
                boxWidth: '300px'
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'allNotification',
                        'urlNotificationTab',
                        'description',
                        'titleBackgroundColorValue',
                        'visibleNotification',
                        'totalNotificationUnread',
                        'visibleNotificationBox',
                        'boxPosition',
                        'boxWidth'
                    ]);
                return this;
            },

            callAjax: function (tab) {
                var self = this;
                var filteredNotifications;
                $.ajax({
                    method: "POST",
                    dataType: "json",
                    url: BASE_URL + "notibox/customer/getNotificationData",
                    success: function (result) {
                        if (!result['customerNotLogin']) {
                            $('.notificationBox').show();
                            self.visibleNotificationBox(true);
                            if (result['unreadNotification'] < 100) {
                                self.totalNotificationUnread(result['unreadNotification']);
                            } else {
                                self.totalNotificationUnread('99+');
                            }
                            self.boxPosition(self.boxPositionClass + " magenest-notification-box");
                            self.boxWidth(self.notificationBoxWidth + "px");
                            if (tab == 'tab2') {
                                filteredNotifications = _.filter(result['allNotification'], function (notification) {
                                    return notification.notification_type == 4;
                                });
                            } else {
                                filteredNotifications = _.filter(result['allNotification'], function (notification) {
                                    return notification.notification_type != 4;
                                });
                            }
                            if (!result['allNotification'].length) {
                                self.visibleNotification(true);
                            } else {
                                self.allNotification(filteredNotifications);
                            }
                        }
                    }
                });
            },
            initialize: function () {
                var self = this;
                this._super();
                self.urlNotificationTab(self.url);
                self.titleBackgroundColorValue(self.backgroundColorValue);

                var $win = $(window); // or $box parent container
                var $box = $(".magenest-notification");
                rjsResolver(function () {
                    $('.magenest-notification-action, .magenest-notification-icon').on('click', function() {
                        // check if url is order success page, then show popup first
                        if (window.location.href.includes('checkout/onepage/success') && !$(this).hasClass('clicked') && $('.order-success-gift-box').length) {
                            $('#order-success-gift-box-confirm-modal').modal('openModal');
                            $(this).addClass('clicked');
                            return;
                        }
                        window.location.href = self.url;
                    });
                    $('.mgn-notification-wrapper .table-body .table-row').on('click', function() {
                        const notificationDetailURL = $(this).find(".action").attr("href");
                        if (notificationDetailURL) {
                            window.location.href = notificationDetailURL;
                        } else {
                            console.error("No URL found for the notification detail.");
                        }
                    });
                    $(document).on("click.Bst", function (event) {
                        var $box = $(".magenest-notification");
                        var $boxAction = $(".magenest-notification .magenest-notification-action");
                        if (
                            $boxAction.has(event.target).length ||
                            $boxAction.is(event.target)
                        ) {
                            $boxAction.toggleClass('active');
                            $(".magenest-notification-box").toggle();
                        }

                        if (
                            $box.has(event.target).length === 0 && // Checks if descendants of $box were clicked
                            !$box.is(event.target) // Checks if the $box itself was clicked
                        ) {
                            $box.removeClass('active');
                            $boxAction.removeClass('active');
                            $(".magenest-notification-box").hide();
                        }

                    });
                    self.loadAllNotifications('tab1');
                });
            },

            loadAllNotifications: function (tab) {
                this.callAjax(tab);
                // Remove "active" class from all tabs
                $('.tab').removeClass('active');

                // Add "active" class to the clicked tab
                $('.tab[data-tab="' + tab + '"]').addClass('active');
            }
        });
    }
);
