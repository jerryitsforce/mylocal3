define([
    'jquery',
    'mage/translate',
    'plugins/DOMPurify',
    'mage/utils/wrapper',
    'mage/template',
    'mage/validation',
    'underscore',
    'jquery/ui'
], function ($, $t, DOMPurify) {
    'use strict';
    return function () {

        var string = JSON.stringify($eaCitiesJson),
            obj = JSON.parse(string);
        var jQ = $.noConflict();
        $(document).ajaxComplete(function(event,xhr,settings) {
            if (settings.url.indexOf('customer_address_edit') !== -1) {
                setTimeout(function(){
                    var cityInput = jQ("input[name='city']").eq(1);
                    var cityInputId;
                    var cityInputValue;
                    cityInput.each(function(){
                        cityInputId = DOMPurify.sanitize(cityInput.attr('id')?.toString());
                        cityInputValue = DOMPurify.sanitize(cityInput.val()?.toString());
                    });
                    var htmlSelect = '<option>' + $t("Select city or district") + '</option>';
                    var options;

                    var region_id = DOMPurify.sanitize(jQ("[name*='region_id']").val()?.toString());
                    var region = [];
                    if (region_id) {
                        $.each(obj, function (index, value) {
                            if (value.region_id == region_id) {
                                region.push(value.city_name);
                            }
                        });
                    }

                    var citySelectId = 'S'+cityInputId;
                    var citySelectHtml = "<select id='"+citySelectId+"' class='admin__control-select' name='city_id'></select>";
                    cityInput.hide();
                    cityInput.after(DOMPurify.sanitize(citySelectHtml));

                    $.each(region, function (index, value) {
                        if (value == cityInputValue) {
                            options = '<option value="' + value + '" selected>' + value + '</option>';
                        } else {
                            options = '<option value="' + value + '">' + value + '</option>';
                        }
                        htmlSelect += options;
                    });

                    var newCitySelect = jQ("select[name*='city_id']");
                    newCitySelect.append(DOMPurify.sanitize(htmlSelect));
                    newCitySelect.change(function(){
                        var changeValue = DOMPurify.sanitize(newCitySelect.val()?.toString());
                        cityInput.val(changeValue).trigger("change");
                    });
                }, 1000);
            }
        });

        $(document).on('change', "[name*='region_id']", function () {
            var region_id = DOMPurify.sanitize($(this).val()?.toString()),
                regionName = this.name,
                cityInputName = regionName.replace("region_id", "city"),
                region = [];

            var citySelect = jQ("select[name*='city_id']");
            if (citySelect.length) {
                citySelect.remove();
            }
            if (region_id) {
                /* check exist and generate city select */
                $.each(obj, function (index, value) {
                    if (value.region_id == region_id) {
                        region.push(value.city_name);
                    }
                });
                var cityInput = jQ("[name*='" + cityInputName + "']").eq(1),
                    cityInputNode = document.querySelectorAll("[name*='" + cityInputName + "']"),
                    cityInputId = DOMPurify.sanitize(cityInputNode[1].id),
                    htmlSelect = '<option>' + $t("Select city or district") + '</option>',
                    options;

                var citySelectId = 'S'+cityInputId;
                var citySelectHtml = "<select id='"+citySelectId+"' class='admin__control-select' name='city_id'></select>";
                cityInput.hide();     
                cityInput.after(DOMPurify.sanitize(citySelectHtml));

                $.each(region, function (index, value) {
                    options = '<option value="' + value + '">' + value + '</option>';
                    htmlSelect += options;
                });

                var newCitySelect = jQ("select[name*='city_id']");
                newCitySelect.append(DOMPurify.sanitize(htmlSelect));
                newCitySelect.change(function(){
                    var changeValue = DOMPurify.sanitize(newCitySelect.val()?.toString());
                    cityInput.val(changeValue).trigger("change");
                });
            }
        });
    };
});
