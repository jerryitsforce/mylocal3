define([
    'jquery',
    'ko',
    'uiComponent',
    'Branch8_HelpDesk/js/action/post-message',
    'uiRegistry',
    "mage/adminhtml/events",
    'mage/adminhtml/wysiwyg/tiny_mce/setup',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'mage/validation'
], function ($, ko, Component, postMessage, uiRegistry) {
    'use strict';

    return Component.extend({
        isLoading: ko.observable(false),
        notify: ko.observable(true),
        canPost: ko.observable(true),
        defaults: {
            template: 'Branch8_HelpDesk/view-ticket/post-reply'
        },
        /**
         * Init
         */
        initialize: function () {
            var self = this;
            this._super();
            postMessage.registerLoginCallback(function (postData, response) {
                self.isLoading(false);
                self.canPost(true);
                if (!response.errors) {
                    self.newMessage(Date.now());
                    self.clearForm();
                }
                self.displayMessage(response.messages, response.errors ? 'error' : 'success');
            });
        },
        /**
         *
         * @param message
         * @param type
         */
        displayMessage: function (message, type) {
            this.messageRes(message);
            this.messageType(type);
            $("#post-reply-message").fadeIn()
            var self = this;
            setTimeout(function () {
               $("#post-reply-message").fadeOut()
            }, 3000)
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            var self = this;
            this._super().observe({
                newMessage: false,
                messageRes: '',
                messageType: '',
                displayAdvancedOptions: false
            });
            return this;
        },
        /**
         * Create ticket by Ajax
         * @param formUiElement
         * @param event
         */
        postMessage: async function (formUiElement, event) {
            var postData = {};
            const ticketId = this.configuration.ticket_id,
                attachmentComponent = await this.getAttachmentComponent(),
                attachment = attachmentComponent.value();
            postData['content'] = $('#message').val();
            postData['notify'] = this.notify();
            event.stopPropagation();
            if (attachment && attachment.length) {
                postData['attachment'] = attachment;
            }
            if (this.validate()
            ) {
                this.canPost(false);
                this.isLoading(true);
                postMessage(this.configuration.postUrl, ticketId, postData);
            }
        },
        /**
         *
         * @returns {*}
         */
        getAttachmentComponent: function () {
            const attachmentComponentName = 'ticketCreateForm.postReply.attachment';
            return uiRegistry.get(attachmentComponentName);
        },
        /**
         * Validate
         * @return {boolean}
         */
        validate: function () {
            let message = $("#message").addClass('required'), check = true;
            if (!$.validator.validateSingleElement("#message")) {
                check = false;
            }
            message.removeClass('required');
            return check;
        },
        /**
         *
         */
        clearForm: async function () {
            $("#message").val('');
            if (tinymce.get('message')
                && tinymce.get('message')
            ) {
                tinymce.get('message').setContent('');
            }
            const attachComponent = await this.getAttachmentComponent();
            attachComponent.value([]);
        },
        /**
         *
         */
        afterRender: function () {
            let content = new wysiwygSetup("message", {
                "width": "100%",
                "height": "200px",
                "plugins": [],
                "tinymce": {
                    "toolbar": "formatselect | bold italic underline | " +
                        "alignleft aligncenter alignright |" +
                        "bullist numlist |" +
                        "link table charmap", "plugins": "advlist " +
                        "autolink lists link charmap media " +
                        "code help table",
                },
                files_browser_window_url: ""
            });
            content.setup("exact");
        }
    });
});
