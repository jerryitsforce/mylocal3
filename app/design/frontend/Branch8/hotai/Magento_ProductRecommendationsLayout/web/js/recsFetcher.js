define(["recommendationsSDK", "jquery", "recommendationsEvents", "magentoStorefrontEvents", "dataServicesBase"], function (
    sdk,
    $
) {
    "use strict"
    const pagePreconfiguredDeferred = $.Deferred()
    return {
        "Magento_ProductRecommendationsLayout/js/recsFetcher": function () {
            const requestEvent = new CustomEvent("request")
            const client = new RecommendationsClient()
            document.dispatchEvent(requestEvent)
            client.fetchPreconfigured().then(res => {
                const units = res.data.results
                const filteredUnits = units.filter(unit => unit?.products)

                const responseEvent = new CustomEvent("response", {
                    detail: filteredUnits,
                })

                document.dispatchEvent(responseEvent)
                pagePreconfiguredDeferred.resolve(units)
            })
        },

        "fetchPagePreconfigured": function () {
            // For testing reload recommendation products
            const client = new RecommendationsClient();
            client.fetchPreconfigured().then(res => {
                console.log('fetchPreconfigured');
                console.log(res);
            });
            return pagePreconfiguredDeferred.promise()
        },

        "fetchUnit": async function (options) {
            const unitDeferred = $.Deferred()
            const requestEvent = new CustomEvent("request")
            const client = new RecommendationsClient(options)

            document.dispatchEvent(requestEvent)
            const response = await client.fetchPreconfigured(options)
            const unit = response.data
            var responseEvent = new CustomEvent("response", {
                detail: [unit],
            })
            if (unit.status !== 404) document.dispatchEvent(responseEvent)
            unitDeferred.resolve(unit)
            return unitDeferred.promise()
        },
    }
})
