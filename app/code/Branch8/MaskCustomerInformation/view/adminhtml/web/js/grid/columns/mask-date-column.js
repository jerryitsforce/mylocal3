/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/date'
], function (Column) {
    'use strict';

    return Column.extend({
        /**
         * Formats incoming date based on the 'dateFormat' property.
         *
         * @returns {String} Formatted date.
         */
        getLabel: function (value, format) {
            const secure = this.secure || undefined;
            console.log({
                value:value
            })
            if (secure) {
                return value.dob;
            }
            var date;
            if (this.storeLocale !== undefined) {
                moment.locale(this.storeLocale, utils.extend({}, this.calendarConfig));
            }

            date = moment.utc(this._super());

            if (!_.isUndefined(this.timezone) && moment.tz.zone(this.timezone) !== null) {
                date = date.tz(this.timezone);
            }

            date = date.isValid() && value[this.index] ?
                date.format(format || this.dateFormat) :
                '';

            return date;
        }
    });
});
