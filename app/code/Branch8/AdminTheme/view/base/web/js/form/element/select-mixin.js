define([
    'underscore'
], function (_) {
    'use strict';

    return function (Abstract) {
        return Abstract.extend({
            /**
             * Parses incoming options, considers options with undefined value property
             *     as caption
             *
             * @param  {Array} nodes
             * @param {Object} captionValue
             * @return {Object} captionValue
             */
            parseOptions: function (nodes, captionValue) {
                var caption,
                    value;

                nodes = _.map(nodes, function (node) {
                    value = node.value;

                    if (node.disable === true) {
                        return null;
                    }

                    if (value === null || value === captionValue) {
                        if (_.isUndefined(caption)) {
                            caption = node.label;
                        }
                    } else {
                        return node;
                    }
                });

                return {
                    options: _.compact(nodes),
                    caption: _.isString(caption) ? caption : false
                };
            },

            /**
             * Recursively set to object item like value and item.value like key.
             *
             * @param {Array} data
             * @param {Object} result
             * @returns {Object}
             */
            indexOptions: function (data, result) {
                var value;

                result = result || {};

                data.forEach(function (item) {
                    value = item.value;

                    if (Array.isArray(value)) {
                        this.indexOptions(value, result);
                    } else {
                        result[value] = item;
                    }
                }, this);

                return result;
            },

            /**
             * Sets 'data' to 'options' observable array, if instance has
             * 'customEntry' property set to true, calls 'setHidden' method
             *  passing !options.length as a parameter
             *
             * @param {Array} data
             * @returns {Object} Chainable
             */
            setOptions: function (data) {
                var captionValue = this.captionValue || '',
                    result = this.parseOptions(data, captionValue),
                    isVisible;

                this.indexedOptions = this.indexOptions(result.options);

                this.options(result.options);

                if (!this.caption()) {
                    this.caption(result.caption);
                }

                if (this.customEntry) {
                    isVisible = !!result.options.length;

                    this.setVisible(isVisible);
                    this.toggleInput(!isVisible);
                }

                return this;
            }
        });
    };
});
