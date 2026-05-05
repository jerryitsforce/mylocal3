define([], function () {
    'use strict';
    let data = {
        wkvariationscomb: [],
        combination: [],
        oldComb: [],
        skus: [],
        titles: [],
        savedData: [],
        weightDisabled: false,
        formattedSaved: [],
        wk_manage_variation: [],
        values: [],
        oldValues: [],
        currentVariationComboData: [],
        followSimpleSkuCostSetting: true,
        followSimpleSkuPriceSetting: true,
    }
    return {
        /**
         *
         * @returns {{combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}}
         */
        getData: function (key) {
            if (key) {
                return data[key];
            }
            return data;
        },
        /**
         *
         * @param key
         * @param value
         */
        setData: function (key, value) {
            data[key] = value;
            return this;
        }
    }
});
