define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'Branch8_GiftToFriend/js/action/create-ticket',
    'uiRegistry',
    'mage/url',
    "mage/adminhtml/events",
    'mage/adminhtml/wysiwyg/tiny_mce/setup',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/validation'
], function (_, $, ko, Component, createGiftTicketAction, uiRegistry, urlBuilder) {
    'use strict';


    return Component.extend({
        form: null,
        requiredElements: [],
        canPost: ko.observable(false),
        isLoading: ko.observable(false),
        customer: ko.observable(null),
        isLoaded: ko.observable(false),
        orderNumber: ko.observable(''),
        defaults: {
            template: 'Branch8_GiftToFriend/new-ticket-form'
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
            this.isLoaded(true);
            return this;
        },

        /**
         * Init
         */
        initialize: function () {
            var self = this;
            this._super();
            createGiftTicketAction.registerLoginCallback(function () {
                self.isLoading(false);
            });
            this.customer(this.configurations.customer);
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
         * Get Order Increment ID
         * @return {*}
         */
        getOrderIncrementId: function () {
            return this.configurations.orderIncrementId;
        },

        hasOrderId: function () {
            return this.configurations.orderId? true : false;
        },

        /**
         * Get Order ID
         * @return {*}
         */
        getOrderId: function () {
            return this.configurations.orderId;
        },
        /**
         * Get recipient name
         * @return {*}
         */
        getRecipientName: function () {
            return this.configurations.recipientName;
        },
        /**
         * Get recipient phone
         * @return {*}
         */
        getRecipientPhone: function () {
            return this.configurations.recipientPhone;
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
                createGiftTicketAction(postData);
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
                        "autolink lists link charmap media noneditable table " +
                        "contextmenu paste code help table",
                },
                files_browser_window_url: ""
            });
            content.setup("exact");
            this.rebindEventHandler();
        },

        rebindEventHandler: function (form) {
            const self = this;
            $(document).on('change', '#customer_name, #email, #telephone, #title, #category, #content_area', function() {
                const validate = $.validator.validateSingleElement($(this));
                if (validate) {
                    self.enableSubmit();
                } else {
                    self.canPost(false);
                }
            });

            $(document).on('change', '#email_masked', function(){
                $('#email').val($(this).val());
                $('#email').trigger('change');
            });

            $(document).on('change', '#telephone_masked', function(){
                $('#telephone').val($(this).val());
                $('#telephone').trigger('change');
            });

            $(document).on('input', '#email_masked', function(){
                $('#email').val($(this).val());
                $('#email').trigger('input');
            });

            $(document).on('input', '#telephone_masked', function(){
                $('#telephone').val($(this).val());
                $('#telephone').trigger('input');
            });

            $(document).on('input', '#customer_name, #email, #telephone, #title, #category, #content_area', function(){
                const validate = $.validator.validateSingleElement($(this));
                if (validate) {
                    self.enableSubmit();
                } else {
                    self.canPost(false);
                }
            });
        },

        enableSubmit: function() {
            var name = $('#customer_name').val(),
                email = $('#email').val(),
                phone = $('#telephone').val(),
                title = $('#title').val(),
                catagory = $('#category').val(),
                content = $('#content_area').val();
            if(name && email && phone && title && catagory && content){
                this.canPost(true);
            } else{
                this.canPost(false);
            }
        }
    });
});
