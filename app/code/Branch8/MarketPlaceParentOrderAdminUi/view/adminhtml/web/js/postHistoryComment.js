define([
    'jquery',
    'jquery/ui',
    'Branch8_MarketPlaceParentOrderAdminUi/js/action/postHistoryAction',
    'mage/validation'
], function ($, ui, postMessage) {
    'use strict';
    $.widget('mage.parentOrderPostComment', {
        options: {
            trigger: '[data-role="note-trigger"]',
            noteList: '[data-role="note-list"]'
        },
        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            this._super();
            const noteList = $(this.options.noteList);
            postMessage.registerLoginCallback(function (postData, response) {
                if (response.errors === false && response.new) {
                    response.new.each(function (e) {
                        noteList.prepend(e)
                    })
                }else{
                    alert(response.messages.join(''));
                }
            });
            $(this.options.trigger).on('click', this.submit.bind(this))
        },
        /**
         *
         */
        submit: function () {
            var postData = {
                'message': $("#history_comment").val(),
                'status': $("#history_status").val(),
                'is_customer_notified': !!$("#history_notify").is(":checked"),
                'history_visible': !!$("#history_visible").is(":checked")
            };
            postMessage(this.options.postUrl, postData);
        }
    });
    return $.mage.parentOrderPostComment;
});
