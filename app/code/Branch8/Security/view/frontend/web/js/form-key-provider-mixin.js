define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return function (target) {
        return function (settings) {
            var inputSelector = 'input[name="form_key"]',
                formKey = window.FORM_KEY;

            var updateFormKeys = function (key) {
                if (!key) return;
                var inputElements = document.querySelectorAll(inputSelector);
                if (inputElements.length) {
                    Array.prototype.forEach.call(inputElements, function (element) {
                        element.setAttribute('value', key);
                    });
                }
                window.FORM_KEY = key;
            };

            // Use window.FORM_KEY if already set
            if (formKey) {
                updateFormKeys(formKey);
            }

            // Subscribe to CustomerData to get the real session form key (FPC-safe)
            var branch8Data = customerData.get('branch8_customer_data');
            branch8Data.subscribe(function (data) {
                if (data && data.b8_form_key && data.b8_form_key.form_key) {
                    updateFormKeys(data.b8_form_key.form_key);
                }
            });

            // Initial load from CustomerData
            var currentData = branch8Data();
            if (currentData && currentData.b8_form_key && currentData.b8_form_key.form_key) {
                updateFormKeys(currentData.b8_form_key.form_key);
            }

            // Execute original purely for any side effects
            if (!window.FORM_KEY) {
                target(settings);
            }
        };
    };
});
