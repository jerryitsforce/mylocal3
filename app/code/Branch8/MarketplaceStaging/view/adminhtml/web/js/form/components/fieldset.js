/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/lib/collapsible',
    'underscore',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/Variant/VariantProcessHandler',
    'mage/translate'
], function ($, Collapsible, _, VariantProcessHandler, $t) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            template: 'ui/form/fieldset',
            collapsible: false,
            changed: false,
            loading: false,
            error: false,
            opened: false,
            level: 0,
            visible: true,
            initializeFieldsetDataByDefault: false, /* Data in some fieldsets should be initialized before open */
            disabled: false,
            listens: {
                'opened': 'onVisibilityChange'
            },
            additionalClasses: {}
        },

        /**
         * Extends instance with defaults. Invokes parent initialize method.
         * Calls initListeners and pushParams methods.
         */
        initialize: function () {
            _.bindAll(this, 'onChildrenUpdate', 'onChildrenError', 'onContentLoading');
            const self = this;
            
            const initListen = function() {
                if (window.useManageVariant2 === true || window.useManageVariant2 === 1 || window.useManageVariant2 === "1") {
                    VariantProcessHandler.listen();
                } else if (typeof window.useManageVariant2 !== 'boolean' && window.useManageVariant2 !== 1 && window.useManageVariant2 !== 0 && window.useManageVariant2 !== "1" && window.useManageVariant2 !== "0") {
                    // If not defined or not a valid flag yet, wait a bit and try again
                    setTimeout(initListen, 500);
                }
            };
            initListen();

            return this._super()
                ._setClasses();
        },

        /**
         * Initializes components' configuration.
         *
         * @returns {Fieldset} Chainable.
         */
        initConfig: function () {
            this._super();
            this._wasOpened = this.opened || !this.collapsible;
            return this;
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @returns {Object} Reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('changed loading error visible');
            const self = this;
            $(document).on(
                'change',
                'input[name]',
                this.optionValueTitleChangingHandle.bind(self)
            );
            $(document).on(
                'focus',
                'input[name^="product[options]"][name*="[values]"][name$="[title]"]',
                this.cacheOptionValueBeforechanging
            );
            return this;
        },
        /**
         *
         * @param event
         * @returns {Promise<void>}
         */
        optionValueTitleChangingHandle: async function (event) {
            const patternTitle = /^product\[options\]\[(.+)\]\[values\]\[(.+)\]\[title\]$/,
                matchTitle = $(event.target).attr('name').match(patternTitle),
                self = this;
            if (window.useManageVariant2 === 0 || !matchTitle) {
                return
            }
            const oldVal = $(event.target).data('old-value');
            const newVal = $(event.target).val();
            const validate = $.validator.validateSingleElement($(event.target));
            let sku = '';
            try {
                sku = $(event.target).closest('td')
                    .nextAll('td').find('[data-index="sku"] input').val();
            } catch (e) {
                console.error(e);
            }
            if (!validate || !sku) {
                return;
            }
            if (oldVal !== newVal) {
                const warningText = $t(
                    'Combinations have been re-generated. Please click "Manage Variations" check Cost/Stock/SKU/Price for all Variations before saving!'
                );
                const warning = $t('Warning:');
                $('.wk-note.combonation-check-change.wk-warning-box').remove();
                $('[data-index=custom_options]').append(
                    '<div class="wk-note combonation-check-change wk-warning-box  message message-notice admin__scope-old ">\n' +
                    '        <strong>' + warning + '</strong> ' + warningText +
                    '</div>'
                );
                await VariantProcessHandler.buildVariantFormData();
            }
        },
        /**
         *
         * @param event
         */
        cacheOptionValueBeforechanging: function (event) {
            $(event.target).data('old-value', $(event.target).val()); // save old value
        },
        /**
         * Calls parent's initElement method.
         * Assigns callbacks on various events of incoming element.
         *
         * @param  {Object} elem
         * @return {Object} - reference to instance
         */
        initElement: function (elem) {
            const self = this;
            elem.initContainer(this);
            elem.on({
                'update': this.onChildrenUpdate,
                'loading': this.onContentLoading,
                'error': this.onChildrenError
            });

            // Listen for reordering in dynamic rows (like custom options list or values list)
            this._listenToDynamicRows(elem);

            if (this.disabled) {
                try {
                    elem.disabled(true);
                } catch (e) {

                }
            }

            return this;
        },

        /**
         * Recursively listen for changes in dynamic-rows elements
         * @param {Object} elem
         * @private
         */
        _listenToDynamicRows: function (elem) {
            const self = this;
            if (elem.component && elem.component.indexOf('dynamic-rows') !== -1) {
                if (typeof elem.elems === 'function' && elem.elems.subscribe) {
                    elem.elems.subscribe(_.debounce(function () {
                        if (window.useManageVariant2) {
                            VariantProcessHandler.buildVariantFormData();
                        }
                    }, 500));
                }
            }
            // Also check children if they are already initialized or when they get added
            if (typeof elem.elems === 'function') {
                elem.elems().forEach(child => this._listenToDynamicRows(child));
                if (elem.elems.subscribe) {
                    elem.elems.subscribe(children => {
                        children.forEach(child => this._listenToDynamicRows(child));
                    });
                }
            }
        },

        /**
         * Is being invoked on children update.
         * Sets changed property to one incoming.
         *
         * @param  {Boolean} hasChanged
         */
        onChildrenUpdate: function (hasChanged) {
            if (!hasChanged) {
                hasChanged = _.some(this.delegate('hasChanged'));
            }
            this.bubble('update', hasChanged);
            this.changed(hasChanged);
        },

        /**
         * Extends 'additionalClasses' object.
         *
         * @returns {Group} Chainable.
         */
        _setClasses: function () {
            var additional = this.additionalClasses,
                classes;

            if (_.isString(additional)) {
                additional = this.additionalClasses.split(' ');
                classes = this.additionalClasses = {};

                additional.forEach(function (name) {
                    classes[name] = true;
                }, this);
            }

            _.extend(this.additionalClasses, {
                'admin__collapsible-block-wrapper': this.collapsible,
                _show: this.opened,
                _hide: !this.opened,
                _disabled: this.disabled
            });

            return this;
        },

        /**
         * Handler of the "opened" property changes.
         *
         * @param {Boolean} isOpened
         */
        onVisibilityChange: function (isOpened) {
            if (!this._wasOpened) {
                this._wasOpened = isOpened;
            }
            $('body').trigger('customOptionInitComplete');
        },

        /**
         * Is being invoked on children validation error.
         * Sets error property to one incoming.
         *
         * @param {String} message - error message.
         */
        onChildrenError: function (message) {
            var hasErrors = false;

            if (!message) {
                hasErrors = this._isChildrenHasErrors(hasErrors, this);
            }

            this.error(hasErrors || message);

            if (hasErrors || message) {
                this.open();
            }
        },

        /**
         * Returns errors of children if exist
         *
         * @param {Boolean} hasErrors
         * @param {*} container
         * @return {Boolean}
         * @private
         */
        _isChildrenHasErrors: function (hasErrors, container) {
            var self = this;

            if (hasErrors === false && container.hasOwnProperty('elems')) {
                hasErrors = container.elems.some('error');

                if (hasErrors === false && container.hasOwnProperty('_elems')) {
                    container._elems.forEach(function (child) {

                        if (hasErrors === false) {
                            hasErrors = self._isChildrenHasErrors(hasErrors, child);
                        }
                    });
                }
            }

            return hasErrors;
        },

        /**
         * Callback that sets loading property to true.
         */
        onContentLoading: function (isLoading) {
            this.loading(isLoading);
        }
    });
});
