define(["Magento_PageBuilder/js/utils/object"], function (objectUtil) {
    function Converter() {}
    Converter.prototype = {
        // from master.html
        fromDom: function (data, config) {
            var recId = objectUtil
                .get(data, config.html_variable)
                .match(/unit_id="(.*?)"/)[1]
            objectUtil.set(data, "unit_id", recId)
            return data
        },
        // to master.html
        toDom: function (data, config) {
            const {
                unit_id: unitId,
                item_list_id: itemListId,
                item_list_name: itemListName,
                promotion_id: promotionId,
                promotion_name: promotionName,
            } = data;
            console.log("PageBuilderRecommendation", data)

            var htmlVariableText = '{{block class="Magento\\PageBuilderProductRecommendations\\Block\\PageBuilderRecommendation" ' +
                'unit_id="' + unitId + '" ' +
                'item_list_id="' + itemListId + '" ' +
                'item_list_name="' + itemListName + '" ' +
                'promotion_id="' + promotionId + '" ' +
                'promotion_name="' + promotionName + '"' +
                '}}';

            objectUtil.set(data, config.html_variable, htmlVariableText);
            return data
        },
    }
    return Converter
})
