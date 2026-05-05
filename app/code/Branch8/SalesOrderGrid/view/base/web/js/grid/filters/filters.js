/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'mageUtils',
    'Magento_Ui/js/grid/filters/filters',
    'uiLayout',
    'mage/translate',

], function (utils, Collection,layout, $t) {

    return Collection.extend({
        defaults: {
            templates: {
                filters: {
                    base: {
                        parent: '${ $.$data.filters.name }',
                        name: '${ $.$data.column.index }',
                        provider: '${ $.$data.filters.name }',
                        dataScope: '${ $.$data.column.index }',
                        label: '${ $.$data.column.label }',
                        imports: {
                            visible: '${ $.$data.column.name }:visible'
                        }
                    },
                    text: {
                        component: 'Magento_Ui/js/form/element/abstract',
                        template: 'ui/grid/filters/field'
                    },
                    select: {
                        component: 'Magento_Ui/js/form/element/select',
                        template: 'ui/grid/filters/field',
                        options: '${ JSON.stringify($.$data.column.options) }',
                        caption: ' ',
                        selectedPlaceholders: {
                            defaultPlaceholder: $t('Select...'),
                            lotPlaceholders: $t('Selected')
                        }
                    },
                    multiselect: {
                        component: 'Magento_Ui/js/form/element/ui-select',
                        formElement: 'multiselect',
                        template: 'ui/grid/filters/elements/ui-select',
                        options: '${ JSON.stringify($.$data.column.options) }',
                        selectedPlaceholders: {
                            defaultPlaceholder: $t('Select...'),
                            lotPlaceholders: $t('Selected')
                        },
                        caption: ' '
                    },
                    dateRange: {
                        component: 'Branch8_SalesOrderGrid/js/grid/filters/range',
                        rangeType: 'date'
                    },
                    datetimeRange: {
                        component: 'Branch8_SalesOrderGrid/js/grid/filters/range',
                        rangeType: 'datetime'
                    },
                    textRange: {
                        component: 'Branch8_SalesOrderGrid/js/grid/filters/range',
                        rangeType: 'text'
                    }
                }
            }
        },
        /**
         *
         * @param column
         * @returns {*}
         */
        addFilter: function (column) {
            var index       = column.index,
                processed   = this._processed,
                filter;

            if (!column.filter || column.filter ==='false' || _.contains(processed, index)) {
                return this;
            }

            filter = this.buildFilter(column);

            processed.push(index);

            layout([filter]);

            return this;
        },
        /**
         * Creates filter component configuration associated with the provided column.
         *
         * @param {Column} column - Column component with a basic filter declaration.
         * @returns {Object} Filters' configuration.
         */
        buildFilter: function (column) {
            var filters = this.templates.filters,
                filter = column.filter,
                type = filters[filter.filterType],
                key = column.filter;
            if (column.filterSelectType) {
                key = column.filterSelectType
            }
            if (_.isObject(filter) && type) {
                filter = utils.extend({}, type, filter);
            } else if (_.isString(filter)) {
                filter = filters[key];
            }

            if (filter === undefined) {

            }
            if (filter.placeholder) {
                filter.placeholder = '';
            }

            if (filter.caption) {
                filter.caption = '';
            }

            if (filter?.selectedPlaceholders?.defaultPlaceholder) {
                filter.selectedPlaceholders.defaultPlaceholder = '';
            }
            if (column.filterPlaceholder) {
                filter.caption = $t(column.filterPlaceholder);
                filter.placeholder = $t(column.filterPlaceholder);
                if (column?.selectedPlaceholders?.defaultPlaceholder !== undefined) {
                    filter.selectedPlaceholders.defaultPlaceholder = $t(column.filterPlaceholder);
                }
            }

            filter = utils.extend({}, filters.base, filter);
            if (column.filterConfig) {
                filter = utils.extend(filter, column.filterConfig);
            }
            //Accepting labels as is.
            filter.__disableTmpl = {
                label: 1,
                options: 1
            };

            filter = utils.template(filter, {
                filters: this,
                column: column
            }, true, true);

            filter.__disableTmpl = {
                label: true
            };
            return filter;
        }
    });
});
