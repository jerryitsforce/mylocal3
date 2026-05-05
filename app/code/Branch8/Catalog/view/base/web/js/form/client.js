/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mageUtils',
    'uiClass',
    'uiRegistry',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify'
], function ($, _, utils, Class, registry, alert, DOMPurify) {
    'use strict';

    /**
     *
     * @param data
     * @param url
     * @param selectorPrefix
     * @param messagesClass
     * @returns {*|jQuery}
     */
    function beforeSave(data, url, selectorPrefix, messagesClass) {
        var save = $.Deferred();

        data = utils.serialize(utils.filterFormData(data));
        data['form_key'] = window.FORM_KEY;

        if (!url || url === 'undefined') {
            return save.resolve();
        }

        $('body').trigger('processStart');

        $.ajax({
            url: url,
            data: data,

            /**
             * Success callback.
             * @param {Object} resp
             * @returns {Boolean}
             */
            success: function (resp) {
                if (!resp.error) {
                    save.resolve();

                    return true;
                }

                $('body').notification('clear');
                const errorCode = resp.code || '';
                $.each(resp.messages || [resp.message] || [], function (key, message) {
                    $('body').notification('add', {
                        error: resp.error,
                        message: message,

                        /**
                         * Insert method.
                         *
                         * @param {String} msg
                         */
                        insertMethod: function (msg) {
                            var sanitizedMsg = DOMPurify.sanitize(msg, {
                                ALLOWED_TAGS: ['p', 'b', 'i', 'br', 'span'],
                                ALLOWED_ATTR: ['class', 'style'],
                                FORBID_TAGS: ['script', 'iframe', 'style'],
                                FORBID_ATTR: ['onerror', 'onload', 'onclick']
                            });
                            var $wrapper = $('<div></div>').addClass(messagesClass).html(sanitizedMsg);
                            $('.page-main-actions', selectorPrefix).after($wrapper);
                            const imageTab = $('[data-index=gallery]'),
                                imageComponent = 'product_form.product_form.gallery';
                            if (errorCode === 'INVALIDATE_IMAGE_TAGS' && imageTab.length) {
                                $('html, body').animate({
                                    scrollTop: $('[data-index=gallery]', selectorPrefix).offset().top
                                });
                                registry.get(imageComponent, function (r) {
                                    if(r.close()){
                                        r.toggleOpened();
                                    }
                                    alert({
                                        content: sanitizedMsg
                                    });
                                })
                            } else {
                                $('html, body').animate({
                                    scrollTop: $('.page-main-actions', selectorPrefix).offset().top
                                });
                            }
                        }
                    });
                });
            },

            /**
             * Complete callback.
             */
            complete: function () {
                $('body').trigger('processStop');
            }
        });

        return save.promise();
    }

    return Class.extend({

        /**
         * Assembles data and submits it using 'utils.submit' method
         */
        save: function (data, options) {
            var url = this.urls.beforeSave,
                save = this._save.bind(this, data, options);

            beforeSave(data, url, this.selectorPrefix, this.messagesClass).then(save);

            return this;
        },

        /**
         * Save data.
         *
         * @param {Object} data
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _save: function (data, options) {
            var url = this.urls.save;

            $('body').trigger('processStart');
            options = options || {};

            if (!options.redirect) {
                url += 'back/edit';
            }

            if (options.ajaxSave) {
                utils.ajaxSubmit({
                    url: url,
                    data: data
                }, options);

                $('body').trigger('processStop');

                return this;
            }

            utils.submit({
                url: url,
                data: data
            }, options.attributes);

            return this;
        }
    });
});
