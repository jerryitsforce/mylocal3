define(
    [
        'Magento_Ui/js/form/element/region',
        'uiRegistry',
        'mageUtils',
        'uiLayout'
    ],
    function (
        Component,
        registry,
        utils,
        layout
    ) {
        'use strict';

        return Component.extend({
            /**
             * Set default region value
             */
            initialize: function (config) {
                this._super();
                if (window.checkoutConfig.amdefault && window.checkoutConfig.amdefault.region) {
                    registry.get(this.parentName + '.' + 'region_id_input', function (region) {
                        if (!region.value() ) {
                            var country = registry.get(this.parentName + '.' + 'country_id');
                            if (country.value() == window.checkoutConfig.amdefault.country_id) {
                                region.value(window.checkoutConfig.amdefault.region);
                            }
                        }
                    }.bind(this));
                }

                return this;
            },


            /**
             * Creates input from template, renders it via renderer.
             *
             * @returns {Object} Chainable.
             */
            initInput: function () {

                var inputNode = {
                    parent: '${ $.$data.parentName }',
                    component: 'Magento_Ui/js/form/element/abstract',
                    template: '${ $.$data.template }',
                    provider: '${ $.$data.provider }',
                    name: '${ $.$data.index }_input',
                    placeholder: '${ $.$data.inputNodePlaceholder }',
                    dataScope: '${ $.$data.customEntry }',
                    customScope: '${ $.$data.customScope }',
                    sortOrder: {
                        after: '${ $.$data.name }'
                    },
                    displayArea: 'body',
                    label: '${ $.$data.label }'
                };

                layout([utils.template(inputNode, this)]);

                return this;
            }
        });
    }
);
