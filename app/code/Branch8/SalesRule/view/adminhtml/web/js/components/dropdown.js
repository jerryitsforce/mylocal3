define([
    'ko',
    'Magento_Ui/js/grid/filters/elements/ui-select',
    'jquery',
    'mage/translate'
], function (ko, Abstract, $, $t) {
    'use strict';

    return Abstract.extend({
        defaults: {
            selectAllBtn: $t('Select All'),
            deSelectAllBtn: $t("Deselect All"),
            selectDeselectAllBtn: $t('Select All'),
            initValue: []
        },
        /**
         *
         */
        initialize: function () {
            this._super();
            console.log({
                element: this
            })
            return this;
        },
        /**
         *
         * @param data
         * @returns {*}
         */
        toggleOptionSelected: function (data) {
            var isSelected = this.isSelected(data.value);

            if (this.lastSelectable && data.hasOwnProperty(this.separator)) {
                return this;
            }

            if (!this.multiple) {
                if (!isSelected) {
                    this.value(data.value);
                }
                this.listVisible(false);
            } else {
                if (!isSelected) { /*eslint no-lonely-if: 0*/
                    this.value.push(data.value);
                } else {
                    this.value(_.without(this.value(), data.value));
                }
            }
            this.updateIput();
            return this;
        },
        /**
         *
         * @param optgroup
         */
        removeSelected: function (value, data, event) {
            this._super();
            this.updateIput();
        },
        /**
         *
         */
        updateIput: function () {
            window[this.jsFormObject].updateElement.value = this.value().join(',');
        },
        /**
         * Validate initial value actually exists
         */
        validateInitialValue: function () {
            if (_.isEmpty(this.value())) {
                this.validationLoading(false);
                return;
            }

            $.ajax({
                url: this.validationUrl,
                type: 'GET',
                dataType: 'json',
                context: this,
                data: {
                    ids: this.value()
                },

                /** @param {Object} response */
                success: function (response) {
                    if (!_.isEmpty(response)) {
                        this.options([]);
                        this.success({
                            options: response
                        });
                    }
                    //  this.filterChips().updateActive();
                },

                /** set empty array if error occurs */
                error: function () {
                    this.options([]);
                },

                /** stop loader */
                complete: function () {
                    this.validationLoading(false);
                    this.setCaption();
                }
            });
        },
        /**
         *
         */
        selectAll: function () {
            const self = this;
            this.value([]);
            this.options(this.cacheOptions.lastOptions);
            _.each(this.options(), function (item, key) {
                self.toggleOptionSelected(item);
            })
        },
        /**
         *
         */
        deselectAll: function () {
            const self = this;
            this.value([]);
            this.options(this.cacheOptions.lastOptions);
        }
    });
});
