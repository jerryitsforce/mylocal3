define([
    'underscore',
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
], function (_, $, ko, Component, postMessage, uiRegistry) {
    'use strict';

    return Component.extend({
        form: null,
        requiredElements: [],
        isLoading: ko.observable(false),
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
            });
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            var self = this;
            this._super().observe({
                newMessage: false
            });
            return this;
        },
        /**
         * Create ticket by Ajax
         * @param formUiElement
         * @param event
         */
        postMessage: async function (formUiElement, event) {
            var postData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray();
            const ticketId = this.configuration.ticket_id,
                attachmentComponent = await this.getAttachmentComponent(),
                attachment = attachmentComponent.value();
            event.stopPropagation();
            formDataArray.forEach(function (entry) {
                postData[entry.name] = entry.value;
            });
            if (attachment) {
                postData['attachment'] = attachment;
            }
            if (this.validate()
            ) {
                this.isLoading(true);
                this.canPost(false);
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
         * @return {jQuery}
         */
        validate: function () {
            var form = '#message-form';
            return $(form).validation() && $(form).validation('isValid');
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
            const self = this;
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
        },
        /**
         *
         */
        validateForm: function (event) {
            // Check if the form is valid
            const self = this;
            var form = '#message-form';
            let valid = true;
            for (const el of this.requiredElements) {
                if (_.isEmpty($(el).val())) {
                    valid = false;
                    break;
                }
            }
            this.canPost(valid);
        }
    });
});
