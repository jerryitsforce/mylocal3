define(['jquery'], function ($) {
    /**
     *
     */
    return async function (options) {

        let titles = [], values = [], oldValues = [], count = 0, skus = {};
        const acceptedTypes = [
            'radio', 'drop-down', 'drop_down',
        ];
        $.each(options, function (key, option) {
            if (acceptedTypes.includes(option.type)) {
                let title = option.title,
                    valueArr = [],
                    oldValueArr = [];
                titles.push(title);
                if (option.is_require != 1) {
                    count++;
                }
                $.each(option.values, function (index, value) {
                    valueArr.push(value.title);
                    if ($('#product_option_' + option.option_id + '_select_' + value.option_type_id + '_title').length) {
                        const val = $('#product_option_' + option.option_id + '_select_' + value.option_type_id + '_title')
                            .data('store-label');
                        oldValueArr.push(val);
                    } else {
                        oldValueArr.push(value.title);
                    }
                    skus[value.title] = value.sku;
                });
                if (valueArr.length) {
                    values.push(valueArr);
                    oldValues.push(oldValueArr);
                }
            }
        });

        return {
            titles: titles,
            values: values,
            oldValues: oldValues,
            count: count,
            skus: skus
        }
    }
})
