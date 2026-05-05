define('modalPopup', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.modalPopup', {
        options: {
            popup: '.popup',
            btnDismiss: '[data-dismiss="popup"]',
            btnHide: '[data-hide="popup"]'
        },

        /** @inheritdoc */
        _create: function () {
            this.fade = this.element;
            this.popup = $(this.options.popup, this.fade);
            this.btnDismiss = $(this.options.btnDismiss, this.popup);
            this.btnHide = $(this.options.btnHide, this.popup);

            this._events();
        },

        /**
         * @private
         */
        _events: function () {
            var self = this;

            this.btnDismiss
                .on('click.dismissModalPopup', function () {
                    self.fade.remove();
                });

            this.btnHide
                .on('click.hideModalPopup', function () {
                    self.fade.hide();
                });
        }
    });

    return $.mage.modalPopup;
});

define('useDefault', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.useDefault', {
        options: {
            field: '.field',
            useDefault: '.use-default',
            checkbox: '.use-default-control',
            label: '.use-default-label'
        },

        /** @inheritdoc */
        _create: function () {
            this.el = this.element;
            this.field = $(this.el).closest(this.options.field);
            this.useDefault = $(this.options.useDefault, this.field);
            this.checkbox = $(this.options.checkbox, this.useDefault);
            this.label = $(this.options.label, this.useDefault);
            this.origValue = this.el.attr('data-store-label');

            this._events();
        },

        /**
         * @private
         */
        _events: function () {
            var self = this;

            this.el.on(
                'change.toggleUseDefaultVisibility keyup.toggleUseDefaultVisibility',
                $.proxy(this._toggleUseDefaultVisibility, this)
            ).trigger('change.toggleUseDefaultVisibility');

            this.checkboxon('change.setOrigValue', function () {
                if ($(this).prop('checked')) {
                    self.el
                        .val(self.origValue)
                        .trigger('change.toggleUseDefaultVisibility');

                    $(this).prop('checked', false);
                }
            });
        },

        /**
         * @private
         */
        _toggleUseDefaultVisibility: function () {
            var curValue = this.el.val(),
                origValue = this.origValue;

            this[curValue != origValue ? '_show' : '_hide'](); //eslint-disable-line eqeqeq
        },

        /**
         * @private
         */
        _show: function () {
            this.useDefault.show();
        },

        /**
         * @private
         */
        _hide: function () {
            this.useDefault.hide();
        }
    });

    return $.mage.useDefault;
});

define('loadingPopup', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.loadingPopup', {
        options: {
            message: 'Please wait...',
            timeout: 5000,
            timeoutId: null,
            callback: null,
            template: null
        },

        /** @inheritdoc */
        _create: function () {
            this.template =
                '<div class="popup popup-loading">' +
                '<div class="popup-inner">' + this.options.message + '</div>' +
                '</div>';

            this.popup = $(this.template);

            this._show();
            this._events();
        },

        /**
         * @private
         */
        _events: function () {
            var self = this;

            this.element
                .on('showLoadingPopup', function () {
                    self._show();
                })
                .on('hideLoadingPopup', function () {
                    self._hide();
                });
        },

        /**
         * @private
         */
        _show: function () {
            var options = this.options,
                timeout = options.timeout;

            $('body').trigger('processStart');

            if (timeout) {
                options.timeoutId = setTimeout(this._delayedHide.bind(this), timeout);
            }
        },

        /**
         * @private
         */
        _hide: function () {
            $('body').trigger('processStop');
        },

        /**
         * @private
         */
        _delayedHide: function () {
            this._hide();

            this.options.callback && this.options.callback();

            this.options.timeoutId && clearTimeout(this.options.timeoutId);
        }
    });

    return $.mage.loadingPopup;
});

define('collapsable', [
    'jquery',
    'jquery/ui',
    'jquery/jquery.tabs'
], function ($) {
    'use strict';

    $.widget('mage.collapsable', {
        options: {
            parent: null,
            openedClass: 'opened',
            wrapper: '.fieldset-wrapper'
        },

        /** @inheritdoc */
        _create: function () {
            this._events();
        },

        /** @inheritdoc */
        _events: function () {
            var self = this;

            this.element
                .on('show', function (e) {
                    var fieldsetWrapper = $(this).closest(self.options.wrapper);

                    fieldsetWrapper.addClass(self.options.openedClass);
                    e.stopPropagation();
                })
                .on('hide', function (e) {
                    var fieldsetWrapper = $(this).closest(self.options.wrapper);

                    fieldsetWrapper.removeClass(self.options.openedClass);
                    e.stopPropagation();
                });
        }
    });

    return $.mage.collapsable;
});

define('js/theme', [
    'jquery',
    'mage/smart-keyboard-handler',
    'mage/ie-class-fixer',
    'collapsable',
    'domReady!'
], function ($, keyboardHandler) {
    'use strict';

    /* @TODO refactor collapsable as widget and avoid logic binding with such a general selectors */
    $('.collapse').collapsable();

    $.each($('.entry-edit'), function (i, entry) {
        $('.collapse:first', entry).filter(function () {
            return $(this).data('collapsed') !== true;
        }).collapse('show');
    });

    keyboardHandler.apply();
});
