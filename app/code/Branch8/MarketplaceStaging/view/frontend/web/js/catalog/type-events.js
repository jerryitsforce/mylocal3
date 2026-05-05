define([
    'jquery',
    'Branch8_MarketplaceStaging/js/product/weight-handler'
], function ($, weight) {
    'use strict';

    return {
        $type: $('#product_stage_type_id'),

        /**
         * Constructor component
         */
        'Branch8_MarketplaceStaging/js/catalog/type-events': function () {
            this.init();
        },

        /**
         * Init
         */
        init: function () {

            if (weight.productHasWeight()) {
                this.type = {
                    virtual: 'virtual',
                    real: this.$type.val() //simple, configurable
                };
            } else {
                this.type = {
                    virtual: this.$type.val(), //downloadable, virtual, grouped, bundle
                    real: 'simple'
                };
            }
            this.type.current = this.$type.val();

            this.bindAll();
        },

        /**
         * Bind all
         */
        bindAll: function () {
            $(document).on('setStageTypeProduct', function (event, type) {
                this.setType(type);
            }.bind(this));

            //direct change type input
            this.$type.on('change', function () {
                this.type.current = this.$type.val();
                this._notifyType();
            }.bind(this));
        },

        /**
         * Set type
         * @param {String} type - type product (downloadable, simple, virtual ...)
         * @returns {*}
         */
        setType: function (type) {
            return this.$type.val(type || this.type.real).trigger('change');
        },

        /**
         * Notify type
         * @private
         */
        _notifyType: function () {
            $(document).trigger('changeStageTypeProduct', this.type);
        }
    };
});
