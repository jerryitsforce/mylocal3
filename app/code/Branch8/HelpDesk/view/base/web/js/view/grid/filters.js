define([
    'underscore',
    'mageUtils',
    'Magento_Ui/js/grid/filters/filters'
], function (_, utils, component) {
    'use strict';

    function removeEmpty(data) {
        var result = utils.mapRecursive(data, utils.removeEmptyValues.bind(utils));

        return utils.mapRecursive(result, function (value) {
            return _.isString(value) ? value.trim() : value;
        });
    }

    return component.extend({
        /**
         *
         * @returns {*}
         */
        apply: function () {
            this.set('applied', removeEmpty(this.filters));
            return this;
        },
    });
});
