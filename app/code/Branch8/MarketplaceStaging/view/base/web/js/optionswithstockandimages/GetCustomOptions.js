define([
    "jquery",
    'mage/url'
], function ($, urlBuilder) {
    /**
     *
     */
    let cache = {};

    return async function (args, customoptioncurl, useCache = true) {
        let result;
        customoptioncurl = customoptioncurl || urlBuilder.build('marketplacestaging/variation/getcustomoption');
        
        try {
            // Optimization: If args contains the whole product, only send options to reduce payload
            let postData = args;
            if (args.product && args.product.options) {
                postData = { product: { options: args.product.options } };
            }

            if (useCache) {
                const cacheKey = JSON.stringify(postData);
                if (cache[cacheKey]) {
                    return cache[cacheKey];
                }
            }

            result = await $.ajax({
                url: customoptioncurl,
                type: 'POST',
                data: postData
            });

            if (useCache) {
                const cacheKey = JSON.stringify(postData);
                cache[cacheKey] = result;
            }

            return result;
        } catch (error) {
            console.error("getCustomOptions AJAX Error:", error);
        }
    }
})
