var config = {
    deps: [
        "js/idle-time-checker",
        "js/cleanup-legacy-react-carousel-storage",
    ],
    paths: {
        uiComponent:  "Magento_Ui/js/lib/core/collection",
        uiClass:      "Magento_Ui/js/lib/core/class",
        uiCollection: "Magento_Ui/js/lib/core/collection",
        uiElement:    "Magento_Ui/js/lib/core/element/element",
        uiEvents:     "Magento_Ui/js/lib/core/events",
        uiLayout:     "Magento_Ui/js/core/renderer/layout",
        uiRegistry:   "Magento_Ui/js/lib/registry/registry"
    },
    config: {
        mixins: {
            'mageplaza/core/owl.carousel': {
                'js/owl-carousel-mixin': true
            }
        }
    }
};
