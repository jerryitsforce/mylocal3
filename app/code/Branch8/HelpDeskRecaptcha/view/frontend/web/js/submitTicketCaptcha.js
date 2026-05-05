/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global grecaptcha */
define(['jquery', 'Magento_ReCaptchaFrontendUi/js/reCaptcha', 'Branch8_HelpDesk/js/action/create-ticket',
        'Magento_ReCaptchaFrontendUi/js/registry'
    ], function ($, Component, creatAction, registry) {
        'use strict';
        return Component.extend({
            /**
             *
             */
            widgetId: null,
            /**
             *
             */
            initialize: function () {
                var self = this;
                this._super();
                creatAction.registerLoginCallback(function (postData) {
                    if (grecaptcha !== undefined && self.widgetId) {
                        grecaptcha.reset(self.widgetId);
                    }
                });
            },
            /**
             * Override from parent
             */
            initCaptcha: function () {
                var $parentForm,
                    $wrapper,
                    $reCaptcha,
                    widgetId,
                    parameters;

                if (this.captchaInitialized || this.settings ===

                    void 0) {
                    return;
                }

                this.captchaInitialized = true;

                /*
                 * Workaround for data-bind issue:
                 * We cannot use data-bind to link a dynamic id to our component
                 * See:
                 * https://stackoverflow.com/questions/46657573/recaptcha-the-bind-parameter-must-be-an-element-or-id
                 *
                 * We create a wrapper element with a wrapping id and we inject the real ID with jQuery.
                 * In this way we have no data-bind attribute at all in our reCAPTCHA div
                 */
                $wrapper = $('#' + this.getReCaptchaId() + '-wrapper');
                $reCaptcha = $wrapper.find('.g-recaptcha');
                $reCaptcha.attr('id', this.getReCaptchaId());

                $parentForm = $wrapper.parents('form');

                if (this.settings === undefined) {

                    return;
                }

                parameters = _.extend(
                    {
                        'callback': function (token) { // jscs:ignore jsDoc
                            this.reCaptchaCallback(token);
                            this.validateReCaptcha(true);
                        }.bind(this),
                        'expired-callback': function () {
                            this.validateReCaptcha(false);
                        }.bind(this)
                    },
                    this.settings.rendering
                );

                if (parameters.size === 'invisible' && parameters.badge !== 'inline') {
                    nonInlineReCaptchaRenderer.add($reCaptcha, parameters);
                }

                // eslint-disable-next-line no-undef
                widgetId = grecaptcha.render(this.getReCaptchaId(), parameters);
                /**
                 * we cache this widget id to refresh captcha after submit
                 */
                this.widgetId = widgetId;
                this.initParentForm($parentForm, widgetId);
                registry.ids.push(this.getReCaptchaId());
                registry.captchaList.push(widgetId);
                registry.tokenFields.push(this.tokenField);

            }
        });
    });
