define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Ui/js/form/form',
    'Branch8_MarketPlaceProductDiscussionCustomer/js/product/actions/createThread',
    'Branch8_MarketPlaceProductDiscussionCustomer/js/product/model/new-thread',
    'mage/translate',
    'mage/validation'
], function (_, $, ko, Component, createThreadAction, newThreadModel) {
    'use strict';
    return Component.extend({
        form: null,
        requiredElements: [],

        isLoading: ko.observable(false),
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussionCustomer/form'
        },
        /**
         * initObservable
         */
        initObservable: function () {
            const self = this;
            this._super().observe([
                'canPost',
                'content',
                'lengthClass'
            ]);
            this.canPost = newThreadModel.canPost;
            this.content('');
            this.lengthClass('');
            this.remaining = ko.computed(function () {
                return self.content().length
            });
            this.lengthClass = ko.computed(function () {
                return self.content().length > self.configurations.maxLength ? 'invalid' : '';
            });
            return this;
        },
        /**
         * Validate
         * @return {jQuery}
         */
        validate: function () {
            var form = '#create-thread-form';
            return $(form).validation() && $(form).validation('isValid');
        },
        /**
         *
         */
        afterRender: function () {
            const self = this;
            this.form = document.getElementById('create-thread-form');
            $("#content").attr('data-validate', JSON.stringify({
                'required': true,
                'validate-length': true,
                'validate-blacklist-words': true,
                'maxlength': self.configurations.maxLength
            }))
            this.form.querySelectorAll('input, textarea, select').forEach(element => {
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
         * Init
         */
        initialize: function () {
            var self = this;
            createThreadAction.registerLoginCallback(function () {
                self.isLoading(false);
            });
            this._super();
        },
        /**
         *
         * @param formUiElement
         * @param event
         * @returns {Promise<void>}
         */
        postThread: async function (formUiElement, event) {
            var postData = {},
                formElement = $(event.currentTarget),
                formDataArray = formElement.serializeArray(),
                self = this;
            event.stopPropagation();
            formDataArray.forEach(function (entry) {
                postData[entry.name] = entry.value;
            });
            postData['productId'] = self.configurations.productId;
            postData['referer'] = self.configurations.referer;
            if (this.validate()
            ) {
                if(!this.canPost()) {
                    return;
                }
                this.canPost(false);
                this.isLoading(true);
                // this.sendRateLimit(self.configurations.rateLimit)
                createThreadAction(postData);
            }
        },
        /**
         *
         */
        validateForm: function (event) {
            let valid = true;
            for (const el of this.requiredElements) {
                if (_.isEmpty($(el).val())) {
                    valid = false;
                    break;
                }
            }
            if (!valid) {
                return valid;
            }
            const isValid = $.validator.validateSingleElement($("#content"));
            return this.canPost(isValid);
        },
        /**
         *
         */
        sendRateLimit: function (seconds = 30) {
            let btn = $("#submit-question");
            let remain = seconds;
            btn.prop('disabled', true);
            let timer = setInterval(function () {
                btn.find('You can send again in ' + remain + 's');
                btn.find('span').html(remain + 's');
                remain--;
                if (remain < 0) {
                    clearInterval(timer);
                    btn.prop('disabled', false);
                    btn.find('span').text($t('Send out'));
                }
            }, 1000);
        }
    });
});
