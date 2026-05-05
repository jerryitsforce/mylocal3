define(['jquery', "mage/translate", 'Magento_Ui/js/modal/alert', 'plugins/DOMPurify'], function ($, $t, alert, DOMPurify) {

    return {
        /**
         *
         * @param form
         * @returns {{}}
         */
        serializeDeepAdvanced: function (form) {
            const result = {};
            for (let [fullKey, value] of new FormData(form).entries()) {
                // Skip raw File objects (from file inputs) as we use hidden inputs for filenames
                if (value instanceof File) {
                    continue;
                }

                const keys = fullKey
                    .replace(/\]/g, '')
                    .split('[');
                let cur = result;
                keys.forEach((key, i) => {
                    const isLast = i === keys.length - 1;
                    const nextKey = keys[i + 1];

                    const isIndex = !isNaN(key);
                    const nextIsIndex = !isNaN(nextKey);

                    if (isLast) {
                        cur[key] = (key === 'sku') ? value : (isNaN(value) ? value : Number(value));
                        return;
                    }

                    if (!cur[key]) {
                        cur[key] = nextIsIndex ? [] : {};
                    }

                    cur = cur[key];
                });
            }
            return result;
        },
        /**
         *
         * @param arrays
         * @param separator
         * @returns {*}
         */
        combineNArrays: function (arrays, separator = '_') {
            if (arrays.length === 1) {
                return arrays[0];
            }
            return arrays.reduce((acc, curr) => {
                return acc.flatMap(a =>
                    curr.map(b => a ? `${a}${separator}${b}` : b)
                );
            }, ['']);
        },
        /**
         *
         * @param array_of_arrays
         * @param isOld
         * @returns {*[]}
         */

        combineArrays: function (array_of_arrays, isOld = false) {
            // console.log('odometer',odometer);
            // console.log('formCombination',formCombination);
            if (!array_of_arrays) {
                return [];
            }
            if (!Array.isArray(array_of_arrays)) {
                return [];
            }
            if (array_of_arrays.length == 0) {
                return [];
            }
            for (let i = 0; i < array_of_arrays.length; i++) {
                if (!Array.isArray(array_of_arrays[i]) || array_of_arrays[i].length == 0) {
                    return [];
                }
            }
            let odometer = new Array(array_of_arrays.length);
            odometer.fill(0);

            let output = [];
            let newCombination = this.formCombination(odometer, array_of_arrays);
            if (!isOld && jQuery.inArray(newCombination, output) != -1) {
                alert({'content': $t("Combination can not be created, don't use same option title for mutiple options.")});
                return [];
            }
            output.push(newCombination);
            while (this.odometer_increment(odometer, array_of_arrays)) {
                newCombination = this.formCombination(odometer, array_of_arrays);
                if (!isOld && jQuery.inArray(newCombination, output) != -1) {
                    alert({'content': $t("Combination can not be created, don't use same option title for mutiple options.")});
                    return [];
                }
                output.push(newCombination);
            }
            return output;
        },
        /**
         *
         * @param odometer
         * @param array_of_arrays
         * @returns {*}
         */
        formCombination: function (odometer, array_of_arrays) {
            const formCombination = odometer.reduce(
                function (accumulator, odometer_value, odometer_index) {
                    return "" + accumulator + array_of_arrays[odometer_index][odometer_value] + '_';
                },
                ""
            );
            return formCombination;
        },
        /**
         *
         * @param odometer
         * @param array_of_arrays
         * @returns {boolean}
         */
        odometer_increment: function (odometer, array_of_arrays) {
            for (let i_odometer_digit = odometer.length - 1; i_odometer_digit >= 0; i_odometer_digit--) {
                let maxee = array_of_arrays[i_odometer_digit].length - 1;

                if (odometer[i_odometer_digit] + 1 <= maxee) {
                    odometer[i_odometer_digit]++;
                    return true;
                } else {
                    if (i_odometer_digit - 1 < 0) {
                        return false;
                    } else {
                        odometer[i_odometer_digit] = 0;
                        continue;
                    }
                }
            }
        },
        /**
         *
         * @param element
         * @returns {*}
         */
        getId: function (element) {
            return DOMPurify.sanitize($(element).data('id')?.toString());
        },

        /**
         *
         * @param id
         * @returns {number}
         */
        getPrice: function (id) {
            var price = 0,
                element = '#wkv-price-' + id;
            if ($(element).length && $(element).val() > 0) {
                price = $('#wkv-price-' + id).val();
            }
            if (price === 0) {
                price = this.getProductPrice();
            }
            return price;
        },
        /**
         *
         * @param id
         * @returns {number}
         */
        getCost: function (id) {
            var cost = 0, element = '#wkv-cost-' + id;
            if ($(element).length && $(element).val() > 0) {
                cost = $(element).val();
            }
            if (parseInt(cost) === 0) {
                cost = this.getProductCost();
            }
            return cost;
        },
        /**
         *
         * @param costSetting
         * @param commissionPercent
         * @param cost
         * @param price
         * @returns {number|*}
         */
        calculateCost: function (costSetting, commissionPercent, cost, price) {
            commissionPercent = parseInt(commissionPercent) || 0;
            if (parseInt(costSetting) === 0) {
                return cost;
            } else {
                return Math.round(price - (price * (commissionPercent / 100)));
            }
        },
        /**
         *
         * @param id
         * @returns {number}
         */
        getCommissionPercent: function (id) {
            var commissionPercent = 0, element = '#wkv-commission_percent-' + id;
            if ($(element).length) {
                if(isAllowNegativeGrossProfit){
                    commissionPercent = $(element).val();
                }else if($(element).val() > 0){
                    commissionPercent = $(element).val();
                }
            }
            if (parseInt(commissionPercent) === 0) {
                commissionPercent = this.getProductCommissionPercent();
            }
            return commissionPercent;
        },
        /**
         *
         * @param cost
         * @param price
         * @returns {number}
         */
        calculateCommissionRate: function (cost, price) {
            cost = parseInt(cost) || 0;
            price = parseInt(price) || 0;
            if (price === 0) {
                return 0;
            }
            var rate = Math.round((100 - (cost * 100 / price)));
            if(isAllowNegativeGrossProfit){
                return rate;
            }else if (rate < 0) {
                rate = 0;
            }
            return rate;
        },
        /**
         *
         * @returns {number}
         */
        getProductCommissionPercent: function () {
            var commissionPercent = 0, element = `input[name="product[commission_percent]"]`;
            if ($(element).length) {
                if(isAllowNegativeGrossProfit){
                    commissionPercent = $(element).val();
                }else if($(element).val() > 0){
                    commissionPercent = $(element).val();
                }
                
            }
            return commissionPercent;
        },
        /**
         *
         * @param id
         * @returns {number}
         */
        getCostSetting: function (id) {
            var costSetting = 1, element = `#wkv-cost_setting-` + id;
            if ($(element).length && parseInt($(element).val()) === 0) {
                costSetting = 0;
            }
            return costSetting;
        },
        /**
         *
         * @returns {number}
         */
        getProductCost: function () {
            var cost = 0, element = `input[name="product[cost]"]`;
            if ($(element).length && $(element).val() > 0) {
                cost = $(element).val();
            }
            return cost;
        },
        /**
         *
         * @returns {*}
         */
        getProductPrice: function () {
            var price = 0, element = 'input[name="product[special_price]"]';
            if ($(element).length && $(element).val() > 0) {
                price = $('input[name="product[special_price]"]').val();
            } else {
                price = $('input[name="product[price]"]').val();
            }
            return price;
        },
        /**
         *
         * @returns {number}
         */
        getProductCostSetting: function () {
            var costSetting = 1, element = `input[name="product[cost_setting]"]`;
            if ($(element).length) {
                costSetting = $(element).val();
            }
            return costSetting;
        }
    }
})
