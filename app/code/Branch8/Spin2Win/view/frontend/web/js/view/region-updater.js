/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'mage/template',
    'underscore',
    'plugins/DOMPurify',
    'Branch8_Spin2Win/js/dompurify-config',
    'jquery-ui-modules/widget',
    'mage/validation'
], function ($, $t, mageTemplate, _, DOMPurify, getSanitizeConfig) {
    'use strict';

    $.widget('b8.spinDirectoryRegionUpdater', {
        options: {
            regionTemplate:
                '<option value="<%- data.value %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
                '<%- data.title %>' +
                '</option>',
            isRegionRequired: true,
            isZipRequired: true,
            isCountryRequired: true,
            currentRegion: null,
            isMultipleCountriesAllowed: true,
            cityListId: '#city_id',
            cityInput: '#city',
            countryListId: '#country',
        },

        
        /**
         *
         * @private
         */
        _create: function () {
            const self = this;

            this._initCountryElement();

            this.currentRegionOption = this.options.currentRegion;
            this.regionTmpl = mageTemplate(this.options.regionTemplate);

            // set default country
            const country = this.element.find('option:selected').val();
            
            if (country != 'TW') {
                country = 'TW';
            }

            // load region data by country
            this._updateRegion(country);

            // click on region list remove no-selected class
            $(document).on('click', this.options.regionListId, $.proxy(function (e) {
                $(this).removeClass('no-selected');
            }));

            // click on country list remove no-selected class
            $(document).on('click', this.options.cityListId, $.proxy(function (e) {
                $(this).removeClass('no-selected');
            }));

            // click out of region list and country list add no-selected class if matching value is empty
            $(document).click(function(event) { 
                var target = $(event.target);
                var regionList = $(self.options.regionListId).parent();
                var cityList = $(self.options.cityListId).parent();
                if (!regionList.is(target) && regionList.has(target).length === 0) { 
                    if($(self.options.regionListId).val() == ''){
                        $(self.options.regionListId).addClass('no-selected');
                    } else {
                        $(self.options.regionListId).removeClass('no-selected');
                    }
                } 
                if (!cityList.is(target) && cityList.has(target).length === 0) { 
                    if($(self.options.cityListId).val() == ''){
                        $(self.options.cityListId).addClass('no-selected');
                    } else {
                        $(self.options.cityListId).removeClass('no-selected');
                    }
                } 
            });

            
            $(document).on('change', this.options.countryListId, $.proxy(function (e) {
                self.setOption = false;
                self.currentRegionOption = $(e.target).val();
                if(self.currentRegionOption != 'TW'){
                    $(e.target).val('TW');
                    $(e.target).trigger('change');
                }
            }, this));

            // region list change event
            $(document).on('change', this.options.regionListId, $.proxy(function (e) {
                self.setOption = false;
                self.currentRegionOption = $(e.target).val();

                if (!self.currentRegionOption || self.currentRegionOption == '') {
                    $(self.options.regionInputId).val('');
                } else {
                    var regionVal = $(self.options.regionListId).find('option:selected').text();
                    $(self.options.regionInputId).val(regionVal);
                }
                self._updateCity();
            }, this));

            // country list change event
            $(document).on('change', this.options.cityListId, $.proxy(function (e) {
                const cityVal = $(e.target).val();
                if (!cityVal || cityVal == '') {
                    $(self.options.cityInput).val('');
                } else {
                    $(self.options.cityInput).val(cityVal);
                }
                if($('.spin2win-notification-detail__address-form').length){
                    self.enableSubmit();
                }
            }, this));
            $(this.options.regionInputId).on('focusout', $.proxy(function () {
                this.setOption = true;
            }, this));
            
        },

        /**
         *
         * @private
         */
        _initCountryElement: function () {
            if (this.options.isMultipleCountriesAllowed) {
                this.element.parents('div.field').show();
                this.element.on('change', $.proxy(function (e) {
                    // clear region inputs on country change
                    $(this.options.regionListId).val('');
                    $(this.options.regionInputId).val('');
                    this._updateRegion($(e.target).val());
                }, this));

                if (this.options.isCountryRequired) {
                    this.element.addClass('required-entry');
                    this.element.parents('div.field').addClass('required');
                }
            } else {
                this.element.parents('div.field').hide();
            }
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
            selectElement.append($.proxy(function () {
                var sanitizedName = DOMPurify.sanitize(value.name, getSanitizeConfig());
                var name = sanitizedName.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&'),
                    tmplData,
                    tmpl;

                if (value.code && $(name).is('span')) {
                    key = value.code;
                    sanitizedName = $(name).text();
                }

                tmplData = {
                    value: DOMPurify.sanitize(key, getSanitizeConfig()),
                    title: sanitizedName,
                    isSelected: false
                };

                if (this.options.defaultRegion === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.regionTmpl({
                    data: tmplData
                });

                tmpl = DOMPurify.sanitize(tmpl, getSanitizeConfig({ALLOWED_TAGS: ['option'], ALLOWED_ATTR: ['value', 'selected']}));

                return $(tmpl);
            }, this));
        },

        /**
         * Takes clearError callback function as first option
         * If no form is passed as option, look up the closest form and call clearError method.
         * @private
         */
        _clearError: function () {
            var args = ['clearError', this.options.regionListId, this.options.regionInputId, this.options.postcodeId];

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
         * @param {String} country - 2 uppercase letter for country code
         * @private
         */
        _updateRegion: function (country) {
            // Clear validation error messages
            var regionList = $(this.options.regionListId),
                regionInput = $(this.options.regionInputId),
                postcode = $(this.options.postcodeId),
                label = regionList.parent().siblings('label'),
                container = regionList.parents('div.field'),
                regionsEntries,
                regionId,
                regionData;

            if (country != 'TW') {
                country = 'TW';
            }
    
            this._clearError();
            this._checkRegionRequired(country);

            // Populate state/province dropdown list if available or use input box
            if (this.options.regionJson[country]) {
                this._removeSelectOptions(regionList);
                regionsEntries = _.pairs(this.options.regionJson[country]);
                $.each(regionsEntries, $.proxy(function (key, value) {
                    regionData = value[1];
                    regionId = regionData.id;
                    this._renderSelectOption(regionList, regionId.toString(), regionData);
                }, this));

                if (this.currentRegionOption) {
                    regionList.val(this.currentRegionOption);
                }

                if (this.setOption) {
                    regionList.find('option').filter(function () {
                        return this.text === regionInput.val();
                    }).attr('selected', true);
                }

                if (this.options.isRegionRequired) {
                    regionList.addClass('required-entry').removeAttr('disabled');
                    container.addClass('required').show();
                } else {
                    regionList.removeClass('required-entry validate-select').removeAttr('data-validate');
                    container.removeClass('required');

                    if (!this.options.optionalRegionAllowed) { //eslint-disable-line max-depth
                        regionList.hide();
                        container.hide();
                    } else {
                        regionList.removeAttr('disabled').show();
                    }
                }

                regionList.addClass('required-entry');
                regionList.show();
                regionInput.hide();
                if(regionList.val() == ''){
                    regionList.addClass('no-selected');
                }
                label.attr('for', regionList.attr('id'));

                this._updateCity();
            } else {
                this._removeSelectOptions(regionList);

                if (this.options.isRegionRequired) {
                    regionInput.addClass('required-entry').removeAttr('disabled');
                    container.addClass('required').show();
                } else {
                    if (!this.options.optionalRegionAllowed) { //eslint-disable-line max-depth
                        regionInput.attr('disabled', 'disabled');
                        container.hide();
                    }
                    container.removeClass('required');
                    regionInput.removeClass('required-entry');
                }

                regionList.removeClass('required-entry').prop('disabled', 'disabled').hide();
                regionInput.show();
                label.attr('for', regionInput.attr('id'));
            }

            // If country is in optionalzip list, make postcode input not required
            if (this.options.isZipRequired) {
                $.inArray(country, this.options.countriesWithOptionalZip) >= 0 ?
                    postcode.removeClass('required-entry').closest('.field').removeClass('required') :
                    postcode.addClass('required-entry').closest('.field').addClass('required');
            }

            // Add defaultvalue attribute to state/province select element
            regionList.attr('defaultvalue', this.options.defaultRegion);
            this.options.form.find('[type="submit"]').removeAttr('disabled').show();
        },

        /**
         * Check if the selected country has a mandatory region selection
         *
         * @param {String} country - Code of the country - 2 uppercase letter for country code
         * @private
         */
        _checkRegionRequired: function (country) {
            var self = this;

            this.options.isRegionRequired = false;
            $.each(this.options.regionJson.config['regions_required'], function (index, elem) {
                if (elem === country) {
                    self.options.isRegionRequired = true;
                }
            });
        },

        _updateCity: function () {
            const self = this;
            var cityList = [];

            var string = JSON.stringify($eaCitiesJson),
                cityList  = JSON.parse(string),
                cityValue = $("[name*='city']").val(),
                region_id = $("[name*='region_id']").val(),
                city = $("#city"),
                selectCity = $("#city_id"),
                htmlSelect = '<option value="">' + $t("請選擇鄉鎮市區") + '</option>',
                options;

            if(selectCity.length == 0){
                selectCity = $("<select class='required-entry validate-unsupported-city' name='city_id' id='city_id'></select>");
            }

            selectCity.find('option').remove();
            if(cityValue == ''){
                selectCity.addClass('no-selected');
            }    

            if(cityList.length > 0){  
                var cityOptions = [];  
                if(region_id != ''){
                    $.each(cityList, function (index, value) {
                        if (value.region_id == region_id) {
                            cityOptions.push(value.city_name);
                        }
                    });
                }

                if(cityOptions.length > 0) {
                    $.each(cityOptions, function (index, value) {
                        if ( value.toUpperCase() == cityValue.toUpperCase()) {
                            options = '<option value="' + value + '" selected>' + value + '</option>';
                        } else {
                            options = '<option value="' + value + '">' + value + '</option>';
                        }

                        htmlSelect += options;
                    });
                }
                selectCity.append(htmlSelect);
                city.hide();
            }
            selectCity.removeClass('mage-error');
            city.parent().find('.mage-error').remove();
            city.parent().append(selectCity);
            this.enableSubmit();
        },

        enableSubmit: function() {
            if($('#city_id').hasClass('mage-error') || $('#region_id').hasClass('mage-error')){
                $('#city_id').val(null);
            }
        }
    });

    return $.b8.spinDirectoryRegionUpdater;
});
