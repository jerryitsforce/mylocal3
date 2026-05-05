/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'underscore',
    'escaper',
    'jquery/jquery-storageapi'
], function ($, Component, customerData, _, escaper) {
    'use strict';
    var timerClose;
    return Component.extend({
        defaults: {
            cookieMessages: [],
            cookieMessagesObservable: [],
            messages: [],
            hasMessageObservable: false,
            allowedTags: ['div', 'span', 'b', 'strong', 'i', 'em', 'u', 'a']
        },

        /**
         * Extends Component object by storage observable messages.
         */
        initialize: function () {
            var self = this;
            this._super().observe(
                [
                    'cookieMessagesObservable',
                    'hasMessageObservable'
                ]
            );

            // The "cookieMessages" variable is not used anymore. It exists for backward compatibility; to support
            // merchants who have overwritten "messages.phtml" which would still point to cookieMessages instead of the
            // observable variant (also see https://github.com/magento/magento2/pull/37309).
            this.cookieMessages = _.unique($.cookieStorage.get('mage-messages'), 'text');
            this.cookieMessagesObservable(this.cookieMessages);

            this.messages = customerData.get('messages').extend({
                disposableCustomerData: 'messages'
            });

            console.log("messsage:", { ms: this.messages(), dk: (this.messages() && this.messages()?.messages && this.messages().messages.length > 0) ? true : false });
            self.hasMessageObservable((this.messages() && this.messages()?.messages && this.messages().messages.length > 0) ? true : false);

            // this.messages.subscribe(function (newValues) {
            //     console.log("messsage:", {newValues, dk: (newValues && newValues.messages.length > 0)});
            //     self.hasMessageObservable((newValues && newValues.messages.length > 0));
            // })

            this.clearMageMessagesCookie();

            //observer if browser
            document.addEventListener("visibilitychange", function () {
                if (document.hidden) {
                    console.log("Tab inactive → purgeMessages()");
                    self.purgeMessages();
                }
            });

            this.togglePlaceholderMessage();
        },

        /**
         * Prepare the given message to be rendered as HTML
         *
         * @param {String} message
         * @return {String}
         */
        prepareMessageForHtml: function (message) {
            return escaper.escapeHtml(message, this.allowedTags);
        },
        purgeMessages: function () {
            const self = this;
            console.log('-----------------purgeMessages ');
            // console.log(this.messages().messages);
            const hasMessage = $('.page.messages > .messages div[data-ui-id="checkout-cart-validationmessages-message-error"').length;

            if (hasMessage) {
                this.toggleMessages();
            }



            if (!_.isEmpty(this?.messages()?.messages)) {
                console.log('-----------------purgeMessages clear messages');
                // Move purgeMessages to toggleMessages;
                // customerData.set('messages', {});
            }

            // Remove cookie message
            if (!_.isEmpty(this.cookieMessages)) {
                this.cookieMessages = [];
                this.clearMageMessagesCookie();
            }


            // // Clear cookie + observable
            if (!_.isEmpty(this.cookieMessagesObservable())) {
                let self = this;
                setTimeout(function () {
                    self.cookieMessagesObservable([]);
                }, 6000);
            }
        },

        toggleMessages: function ($parent) {
            var self = this;
            console.log('toggleMessages', $parent);
            clearTimeout(timerClose);
            var hasMessage = ($parent && $parent?.messages() && $parent.messages()?.messages && $parent.messages().messages.length > 0) ? true : false;
            console.log("messsage:", { dk: hasMessage });
            self.hasMessageObservable(hasMessage);

            if (hasMessage) {
                $('.page.messages .cookie-messages .message').remove();
            }

            if (!$('body').hasClass('page-layout-seller-2columns-left')) {
                if ($('body.checkout-cart-index').length) {
                    $('.page.messages').addClass('__show');
                    // Remove cookie message
                    self.cookieMessages = [];
                    this.clearMageMessagesCookie();
                    customerData.set('messages', {});
                    timerClose = setTimeout(function () {
                        $('.page.messages').removeClass('__show');
                        $('.page.messages .message').remove();
                        self.clearMageMessagesCookie();
                    }, 6000);
                    const hasMessage = $('.page.messages > .messages div[data-ui-id="checkout-cart-validationmessages-message-error"').length;
                    if (hasMessage) {
                        setTimeout(function () {
                            $('.page.messages > .messages').remove();
                        }, 6000);
                    }
                } else {
                    $('.page.messages').addClass('__show');
                    // Remove cookie message
                    self.cookieMessages = [];
                    this.clearMageMessagesCookie();

                    console.log('set messags empty');
                    customerData.set('messages', {});
                    timerClose = setTimeout(function () {
                        $('.page.messages').removeClass('__show');
                        $('.page.messages .message').remove();
                        console.log('hide message');
                        // //reload messages section
                        console.log('Reload messages after timeout');
                        self.clearMageMessagesCookie();
                        var messages_section = ['messages'];
                        customerData.reload(messages_section, true, true);
                    }, 6000);

                    const hasMessage = $('.page.messages > .messages div[data-ui-id="checkout-cart-validationmessages-message-error"').length;
                    if (hasMessage) {
                        setTimeout(function () {
                            $('.page.messages > .messages').remove();
                        }, 6000);
                    }
                }
            }
        },
        togglePlaceholderMessage: function () {
            $('[data-placeholder="messages"]').on('toggleMessage', function () {
                var timer;
                clearTimeout(timer);
                $('.page.messages').addClass('__show');
                timer = setTimeout(function () {
                    $('.page.messages').removeClass('__show');
                    $('[data-placeholder="messages"] .messages').remove();
                }, 6000);
            });
        },
        clearMageMessagesCookie: function () {
            var domain = window.location.hostname;
            var path = '/';
            // 1. Try via storage API
            $.cookieStorage.set('mage-messages', null, { path: path });

            // 2. Try native removal for current domain and subdomains
            var cookieBase = "mage-messages=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=" + path;
            document.cookie = cookieBase + ";";
            document.cookie = cookieBase + "; SameSite=Lax;";
            document.cookie = cookieBase + "; domain=" + domain + ";";
            document.cookie = cookieBase + "; domain=" + domain + "; SameSite=Lax;";
            document.cookie = cookieBase + "; domain=." + domain + ";";
            document.cookie = cookieBase + "; domain=." + domain + "; SameSite=Lax;";

            // 3. Handle root domain if it's a subdomain
            var domainParts = domain.split('.');
            if (domainParts.length > 2) {
                var rootDomain = domainParts.slice(-2).join('.');
                document.cookie = cookieBase + "; domain=" + rootDomain + ";";
                document.cookie = cookieBase + "; domain=" + rootDomain + "; SameSite=Lax;";
                document.cookie = cookieBase + "; domain=." + rootDomain + ";";
                document.cookie = cookieBase + "; domain=." + rootDomain + "; SameSite=Lax;";
            }
        }
    });
});
