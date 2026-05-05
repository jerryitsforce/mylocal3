define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'Branch8_HelpDesk/js/action/create-ticket',
    'uiRegistry',
    'mage/url',
    "mage/adminhtml/events",
    'mage/adminhtml/wysiwyg/tiny_mce/setup',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/validation'
], function (_, $, ko, Component, createTicketAction, uiRegistry, urlBuilder) {
    'use strict';


    return Component.extend({
        form: null,
        requiredElements: [],
        canPost: ko.observable(false),
        isLoading: ko.observable(false),
        defaults: {
            template: 'Branch8_HelpDesk/new-ticket-form'
        },
        /**
         * initObservable
         */
        initObservable: function () {
            this._super()
                .observe([
                    'disableEmailInput',
                    'disablePhoneInput'
                ]);
            this.disableEmailInput(true);
            this.disablePhoneInput(true);

            return this;
        },

        /**
         * Init
         */
        initialize: function () {
            var self = this;
            this._super();
            createTicketAction.registerLoginCallback(function () {
                self.isLoading(false);
            });
        },

        /**
         * Get url of order history
         * @returns {*}
         */
        getUrlOrderHistory: function () {
            return urlBuilder.build('sales/parentOrder/history');
        },

        /**
         * GetCategoryOptions
         * @returns array
         */
        getCategoryOptions: function () {
            return this.configurations.categoryOptions;
        },

        /**
         *
         * GetPriorityOptions
         * @returns array
         */
        getPriorityOptions: function () {
            return this.configurations.priorityOptions;
        },

        /**
         * Get File Upload text
         * @returns {*}
         */
        getFileUploadText: function () {
            return this.configurations.maxFileUploadText;
        },

        /**
         * Create ticket by Ajax
         * @param formUiElement
         * @param event
         */
        createTicket: async function (formUiElement, event) {
            var postData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray();

            event.stopPropagation();
            formDataArray.forEach(function (entry) {
                postData[entry.name] = entry.value;
            });
            const attachmentComponent = await this.getAttachmentComponent(),
                attachment = attachmentComponent.value();
            const findYourOrderComponent = await this.findYourOrderComponent(),
                findYourOrder = findYourOrderComponent.value();
            if (attachment) {
                postData['attachment'] = attachment;
            }
            if (findYourOrder) {
                let postKey = findYourOrderComponent.configuration.postKey || 'order';
                postData[postKey] = Array.isArray(findYourOrder) ? findYourOrder.join(',') : findYourOrder;
            }
            if (this.validate()
            ) {
                this.isLoading(true);
                createTicketAction(postData);
            }
        },

        /**
         * Validate
         * @return {jQuery}
         */
        validate: function () {
            var form = '#helpdesk-form';
            return $(form).validation() && $(form).validation('isValid');
        },
        /**
         *
         * @returns {*}
         */
        getAttachmentComponent: function () {
            const attachmentComponentName = 'ticketCreateForm.attachment';
            return uiRegistry.get(attachmentComponentName);
        },
        /**
         *
         * @returns {*}
         */
        findYourOrderComponent: function () {
            const findYourOrderComponent = 'ticketCreateForm.findYourOrder';
            return uiRegistry.get(findYourOrderComponent);
        },

        /**
         * Check select is selected or not
         */
        selectOnChange: function () {
            var elem = $('#category');
            if (elem.val() === "0" || elem.val() === "") {
                elem.addClass("empty");
            } else {
                elem.removeClass("empty");
            }
        },

        /**
         *
         */
        afterRender: function () {
            const self = this;
            let content = new wysiwygSetup("content", {
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
            this.rebindEventHandler();

        },
        /**
         *
         */
        unmaskElement: function (enableElement, trigger) {
            this[trigger](false);
            $(enableElement).attr('type', 'text').focus();
            this.rebindEventHandler();
        },
        /**
         *
         */
        rebindEventHandler: function (form) {
            const self = this;
            this.form = document.getElementById('helpdesk-form');
            const elements = this.form.querySelectorAll('input, textarea, select');
            elements.forEach(element => {
                const dataValidate = element.dataset?.validate || undefined;
                const isNotValidType = element.disabled || element.type === 'hidden';
                try {
                    if (dataValidate !== undefined
                        && isNotValidType === false
                        && $.parseJSON(dataValidate.replace(/'/g, '"'))['required'] === true) {
                        element.addEventListener('input', _.debounce(self.validateForm.bind(self), 100));
                        element.addEventListener('change', _.debounce(self.validateForm.bind(self), 100)); // For <select>
                        this.requiredElements.push(element);
                    }
                } catch (e) {
                    console.log(e)
                }

            });
        },
        /**
         *
         */
        validateForm: function (event) {
            // Check if the form is valid
            const self = this;
            var form = '#helpdesk-form';
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
