define(["Magento_PageBuilder/js/utils/object"], function (objectUtil) {
    function Converter() {}
    Converter.prototype = {
        // from master.html
        fromDom: function (data, config) {
            if (objectUtil.get(data, config.html_variable).match(/unit_id="(.*?)"/)) {
                let recId = objectUtil
                    .get(data, config.html_variable)
                    .match(/unit_id="(.*?)"/)[1]
                objectUtil.set(data, "unit_id", recId)
            }
            if (objectUtil.get(data, config.html_variable).match(/condition_option_value="(.*?)"/)) {
                let conditionOptionValue = objectUtil
                    .get(data, config.html_variable)
                    .match(/condition_option_value="(.*?)"/)[1]
                objectUtil.set(data, "condition_option_value", conditionOptionValue)
            }
            if (data.conditions_encoded) {
                data.condition_option = data.condition_option || "condition";
                data[data.condition_option] = this.decodeWysiwygCharacters(this.decodeHtmlCharacters(data.condition_option_value || ""));
                data.conditions_encoded = this.decodeWysiwygCharacters(data.conditions_encoded || "");
                data[data.condition_option + "_source"] = data.conditions_encoded;
            }
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
                products_count: productsCount,
                sort_order: sortOrder,
                condition_option: conditionOption,
                conditions_encoded: conditionsEncoded,
                in_page: inPage
            } = data;

            var htmlVariableText = '{{block class="Magento\\PageBuilderProductRecommendations\\Block\\PageBuilderRecommendation" ' +
                'unit_id="' + unitId + '" ' +
                'item_list_id="' + itemListId + '" ' +
                'item_list_name="' + itemListName + '" ' +
                'promotion_id="' + promotionId + '" ' +
                'promotion_name="' + promotionName + '"' +
                'in_page="' + inPage + '"' +
                '}}';
            if (!unitId) htmlVariableText = '';
            var attributes = {
                type: "Magento\\CatalogWidget\\Block\\Product\\ProductsList",
                template: "Magento_CatalogWidget::product/widget/content/grid.phtml",
                // template: "Magento_CatalogWidget::product/widget/content/banner_grid.phtml",
                anchor_text: "",
                id_path: "",
                show_pager: 0,
                products_count: productsCount,
                is_ajax: true,
                condition_option: conditionOption,
                item_list_id: itemListId,
                item_list_name: itemListName,
                promotion_id: promotionId,
                promotion_name: promotionName,
                in_page: inPage,
                condition_option_value: "",
                type_name: "Catalog Products List",
                conditions_encoded: this.encodeWysiwygCharacters(conditionsEncoded || "")
            };

            if (sortOrder) {
                attributes.sort_order = sortOrder;
            }

            if (typeof data[data.condition_option] === "string") {
                attributes.condition_option_value = this.encodeWysiwygCharacters(data[data.condition_option]);
            }

            if (attributes.conditions_encoded.length === 0 || conditionOption === 'none') {
                objectUtil.set(data, 'conditions_encoded', '');
                objectUtil.set(data, config.html_variable, htmlVariableText);
                return data;
            }

            htmlVariableText = htmlVariableText + this.buildDirective(attributes);
            objectUtil.set(data, config.html_variable, htmlVariableText);
            return data
        },

        encodeWysiwygCharacters: function (content) {
            return content.replace(/\{/g, "^[").replace(/\}/g, "^]").replace(/"/g, "`").replace(/\\/g, "|").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        },

        buildDirective: function(attributes) {
            return "{{widget " + this.createAttributesString(attributes) + "}}";
        },

        createAttributesString: function(attributes) {
            let result = "";

            _.each(attributes, (value, name) => {
                result += name + "=\"" + String(value).replace(/"/g, "&quote;") + "\" ";
            });

            return result.substr(0, result.length - 1);
        },

        decodeWysiwygCharacters: function(content) {
            return content.replace(/\^\[/g, "{").replace(/\^\]/g, "}").replace(/`/g, "\"").replace(/\|/g, "\\").replace(/&lt;/g, "<").replace(/&gt;/g, ">");
        },

        decodeHtmlCharacters: function(content) {
            if (content) {
                const htmlDocument = new DOMParser().parseFromString(content, "text/html");

                return htmlDocument.body ? htmlDocument.body.textContent : content;
            }

            return content;
        }
    }
    return Converter
})
