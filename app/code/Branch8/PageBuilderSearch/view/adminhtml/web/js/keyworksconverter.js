define(["Magento_PageBuilder/js/utils/object"], function(objectUtil) {
    function Converter() {}
    Converter.prototype = {
        // from master.html
        fromDom: function(data, config) {

            return data
        },
        // to master.html
        toDom: function(data, config) {
            var htmlVariableText =
                '{{block class="Branch8\\PageBuilderSearch\\Block\\KeywordsSearch" limit="' +
                data["limit"] +
                '"}}'
            objectUtil.set(data, config.html_variable, htmlVariableText)
            return data
        },
    }
    return Converter
})