define([
        'underscore',
        'jquery',
        'ko',
        'uiComponent',
        'Branch8_HelpDesk/js/action/load-message',
        'Branch8_HelpDesk/js/model/messages',
        'moment', 'uiRegistry', 'mage/translate',
        'mage/url', 'Magento_Ui/js/modal/alert',
        'mage/validation'],
    function (
        _,
        $,
        ko,
        Component,
        loadActions,
        Messages,
        moment,
        uiRegistry
    ) {
        'use strict';

        /**
         * Calculate time ago label
         * @param time
         * @returns {Object|number|string|*|string}
         */
        function time_ago(time) {
            switch (typeof time) {
                case 'number':
                    break;
                case 'string':
                    time = +new Date(time);
                    break;
                case 'object':
                    if (time.constructor === Date) time = time.getTime();
                    break;
                default:
                    time = +new Date();
            }
            var time_formats = [[60, 'seconds', 1], // 60
                [120, '1 minute ago', '1 minute from now'], // 60*2
                [3600, 'minutes', 60], // 60*60, 60
                [7200, '1 hour ago', '1 hour from now'], // 60*60*2
                [86400, 'hours', 3600], // 60*60*24, 60*60
                [172800, 'Yesterday', 'Tomorrow'], // 60*60*24*2
                [604800, 'days', 86400], // 60*60*24*7, 60*60*24
                [1209600, 'Last week', 'Next week'], // 60*60*24*7*4*2
                [2419200, 'weeks', 604800], // 60*60*24*7*4, 60*60*24*7
                [4838400, 'Last month', 'Next month'], // 60*60*24*7*4*2
                [29030400, 'months', 2419200], // 60*60*24*7*4*12, 60*60*24*7*4
                [58060800, 'Last year', 'Next year'], // 60*60*24*7*4*12*2
                [2903040000, 'years', 29030400], // 60*60*24*7*4*12*100, 60*60*24*7*4*12
                [5806080000, 'Last century', 'Next century'], // 60*60*24*7*4*12*100*2
                [58060800000, 'centuries', 2903040000] // 60*60*24*7*4*12*100*20, 60*60*24*7*4*12*100
            ];
            var seconds = (+new Date() - time) / 1000, token = $.mage.__('ago'), list_choice = 1;

            if (seconds === 0) {
                return $.mage.__('Just now')
            }
            if (seconds < 0) {
                seconds = Math.abs(seconds);
                token = $.mage.__('from now');
                list_choice = 2;
            }
            var i = 0, format;
            while (format = time_formats[i++]) if (seconds < format[0]) {
                if (typeof format[2] == 'string') return format[list_choice]; else return Math.floor(seconds / format[2]) + ' ' + format[1] + ' ' + token;
            }
            return time;
        }

        return Component.extend({
            isLoading: ko.observable(false),
            messages: Messages.messages,
            totalCount: Messages.total_count,
            shouldVisible: null,
            defaults: {
                template: 'Branch8_HelpDesk/view-ticket/history-ticket',
                imports: {
                    newMessage: 'ticketCreateForm.postReply:newMessage'
                }
            },
            /**
             * Init
             */
            initialize: function () {
                var self = this;
                this._super();
                const perPage = self.configuration.messagePerPage;
                loadActions.registerLoginCallback(async function (response) {
                    self.isLoading(false);
                    if (!response.errors) {
                        self.rebuildPaging(
                            response.total_count,
                            perPage
                        );
                    }
                });
                self.loadMessages(1);
            },
            /**
             *
             * @returns {Promise<void>}
             */
            rebuildPaging: async function rebuildPaging(total, perPage) {
                const self = this;
                const paging = await self.getPagingComponent();
                paging.pageSize(perPage);
                paging.totalCount(total);
            },
            /**
             *
             * @returns {*}
             */
            initObservable: function () {
                var self = this;
                this._super().observe({
                    currentPage: 1,
                    needToBuildPaging: true,
                    newMessage: false
                });
                this.currentPage.subscribe(function (value) {
                    self.loadMessages(value);
                })
                this.shouldVisible = ko.computed(function () {
                    return Messages.messages().length > 0
                }, this);
                this.newMessage.subscribe(function () {
                    self.loadMessages(self.currentPage());
                })
                return this;
            },

            /**
             *
             */
            loadMessages: function (page) {
                this.isLoading(true);
                const ticketId = this.configuration.ticket_id;
                loadActions(this.configuration.loadUrl, ticketId, page);
            },
            /**
             *
             * @param element
             * @returns {*|number|string|string|Object}
             */
            timeAgo: function (element) {
                return time_ago(moment.utc(element.created_at)._d);
            },
            /**
             *
             * @param element
             * @returns {*}
             */
            getCommenter: function (element) {
                const isCustomer = _.isEmpty(element.customer_email) === false, amonymous = 'Anonymous';
                const time = time_ago(moment.utc('2024-01-09 15:48:33')._d);
                let commenter;
                if (isCustomer) {
                    commenter = element.customer_name || amonymous;
                } else {
                    commenter = element.user_name || amonymous
                }
                return commenter;
            },
            /**
             *
             * @param attachment
             * @returns {*}
             */
            getAttachmentLink: function (attachment) {
                return this.configuration.mediaBasePath + attachment.path
            },
            /**
             *
             * @returns {*}
             */
            getPagingComponent: function () {
                const attachmentComponentName = 'ticketCreateForm.history.paging';
                return uiRegistry.get(attachmentComponentName);
            },
        });
    });
