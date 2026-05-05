/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'underscore',
    'Branch8_Rma/js/parseTaiwanAddress',
    'plugins/DOMPurify',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($, mageTemplate, _, parseTaiwanAddress, DOMPurify) {
    'use strict';

    $.widget('mage.taiwanCityUpdater', {
        options: {
            regionTemplate:
                '<option value="<%- data.value %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
                '<%- data.title %>' +
                '</option>',
            isCityRequired: true,
            currentCity: null,
            isMultipleCountriesAllowed: true,
            regionCitiesJson: {},
            cityListId: null,
            splitAddressTrigger: null,
            zipcodeInput: null,
            addressInput: null
        },
        /**
         *
         */
        parseAddress: function () {
            const address = $(this.options.addressInput).val();
            if (!address) {
                return;
            }
            const parse = parseTaiwanAddress(address);
            if (parse) {
                const {zipcode, city, region, address} = parse[0];
                if (region) {
                    this.currentCity = city;
                    this.element.val(region).trigger('change');
                    if (this.options.zipcodeInput) {
                        $(this.options.zipcodeInput).val(zipcode);
                    }
                    if (this.options.addressInput) {
                        $(this.options.addressInput).val(address)
                    }

                }
            }
        },
        /**
         *
         * @private
         */
        _create: function () {
            this.currentCity = this.options.currentCity;
            this.currentCityOpions = [];
            this.regionTmpl = mageTemplate(this.options.regionTemplate);
            this._updateCity(this.element.find('option:selected').val());
            if (this.options.splitAddressTrigger) {
                $(this.options.splitAddressTrigger).bind('click', _.debounce(this.parseAddress.bind(this), 500));
                $(this.options.splitAddressTrigger).removeAttr('disabled');
            }
            this.element.on('change', $.proxy(function (e) {
                $(this.options.cityListId).val('');
                this._updateCity($(e.target).val());
            }, this));
        },
        /**
         * Remove options from dropdown list
         *
         * @param {Object} selectElement - jQuery object for dropdown list
         * @private
         */
        _removeSelectOptions: function (selectElement) {
            selectElement.find('option').each(function (index) {
                if (index) {
                    $(this).remove();
                }
            });
        },

        /**
         * Render dropdown list
         * @param {Object} selectElement - jQuery object for dropdown list
         * @param {String} key - region code
         * @param {Object} value - region object
         * @private
         */
        _renderSelectOption: function (selectElement, key, value) {
            var jQ = $.noConflict();
            selectElement.append($.proxy(function () {
                var name = value.name.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&'),
                    tmplData,
                    tmpl;

                if (value.code && jQ(name).is('span')) {
                    key = value.code;
                    value.name = jQ(name).text();
                }
                key = DOMPurify.sanitize(key);
                value.name = DOMPurify.sanitize(value.name);

                tmplData = {
                    value: key,
                    title: value.name,
                    isSelected: false
                };

                if (this.options.defaultRegion === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.regionTmpl({
                    data: tmplData
                });

                return jQ(tmpl);
            }, this));
        },

        /**
         * Takes clearError callback function as first option
         * If no form is passed as option, look up the closest form and call clearError method.
         * @private
         */
        _clearError: function () {
            var args = ['clearError', this.options.regionListId, this.options.regionInputId];
            if (this.options.clearError && typeof this.options.clearError === 'function') {
                this.options.clearError.call(this);
            } else {
                if (!this.options.form) {
                    this.options.form = this.element.closest('form').length ? $(this.element.closest('form')[0]) : null;
                }
                this.options.form = $(this.options.form);
                this.options.form && this.options.form.data('validator') &&
                this.options.form.validation.apply(this.options.form, _.compact(args));

                // Clean up errors on region & zip fix
                $(this.options.regionInputId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.regionListId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.postcodeId).removeClass('mage-error').parent().find('[generated]').remove();
            }
        },

        /**
         * Update dropdown list based on the country selected
         *
         * @param {String} region - 2 uppercase letter for country code
         * @private
         */
        _updateCity: function (region) {
            // Clear validation error messages
            var cityListId = $(this.options.cityListId),
                regionInput = $(this.options.regionInputId),
                selectedRegion = this.options.regionCitiesJson.filter((filterItem) => filterItem.value === region)
            let cities = [];
            this.currentCityOpions = [];
            this._clearError();
            if (selectedRegion.length) {
                cities = selectedRegion[0].cities;
            }
            if (cities) {
                this._removeSelectOptions(cityListId);
                $.each(cities, $.proxy(function (key, city) {
                    this.currentCityOpions.push(city.name);
                    this._renderSelectOption(cityListId, city.name, {name: city.name})
                }, this));

                if (this.currentCity && this.currentCityOpions.includes(this.currentCity)) {
                    cityListId.val(this.currentCity);
                } else {
                    cityListId.val('');
                }

                if (this.setOption) {
                    cityListId.find('option').filter(function () {
                        return this.text === regionInput.val();
                    }).attr('selected', true);
                }
            }
            // Add defaultvalue attribute to state/province select element
            cityListId.attr('defaultvalue', this.options.defaultCity);
            this.options.form.find('[type="submit"]').removeAttr('disabled').show();
        }
    });

    return $.mage.taiwanCityUpdater;
});
