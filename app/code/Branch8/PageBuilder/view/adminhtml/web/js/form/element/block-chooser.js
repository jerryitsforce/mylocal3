/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiLayout',
    'uiElement',
    'Magento_PageBuilder/js/config',
    'mage/translate',
    'mage/utils/objects'
], function ($, layout, Element, Config, $t, objectUtils) {
    'use strict';

    return Element.extend({
        id: null,
        meta: {},
        errorMessage: null,
        displayMetadata: true,
        messages: {
            UNKOWN_ERROR: $t('Sorry, there was an error getting requested content. ' +
                'Please contact the store owner.'),
            UNKNOWN_SELECTION: $t('The currently selected block does not exist.')
        },
        defaults: {
            template: 'Branch8_PageBuilder/form/element/block-chooser',
            requestParameter: null,
            dataUrlConfigPath: null,
            modalName: null,
            buttonComponentConfig: {
                title: '${ $.buttonTitle }',
                component: 'Magento_Ui/js/form/components/button',
                actions: [{
                    targetName: '${ $.modalName }',
                    actionName: 'openModal'
                }]
            },
            requestData: {
                method: 'POST',
                data: {
                    'form_key': window.FORM_KEY
                }
            },
            listens: {
                id: 'updateFromServer'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this._super()
                .observe('id meta errorMessage displayMetadata');
        },

        /**
         * Updates the block data from the server
         *
         * @returns void
         */
        updateFromServer: function () {
            if  (!this.id().length) {
                this.meta({});
                return;
            }
        },

        /**
         * Creates the button component for rendering
         *
         * @returns {Object} The button component
         */
        getButton: function () {
            var elementConfig = this.buttonComponentConfig;

            elementConfig.name = this.name + '_button';
            layout([elementConfig]);

            return this.requestModule(elementConfig.name);
        },

        /**
         * Determines the status label for the currently loaded block
         *
         * @returns {String}
         */
        getUnitNameForRecUnit: function () {
            if(this.id()){
                var fields = this.id().split('_');
                var unitName = fields[1];
                return unitName;
            }
            return 'Not Selected';
        },

        /**
         * Determines the visibility of the remove button
         *
         * @returns boolean
         */
        rmVisible: function () {
            return !!this.id();

        },

        /**
         * Determines the status label for the currently loaded block
         *
         * @returns {String}
         */
        rmUnitSelect: function () {
            this.meta({});
            this.id('');
            $('input[name="unit_id"]').val('').trigger('change');
        }
    });
});
