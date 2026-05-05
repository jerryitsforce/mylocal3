var config = {
    map: {
        '*': {
            'Magento_Checkout/template/minicart/content.html': 'Branch8_CatalogCustom/template/minicart/content.html',
            'Magento_Checkout/js/view/minicart': 'Branch8_CatalogCustom/js/view/minicart',
            "productGallery": "Branch8_CatalogCustom/js/product-gallery",
            "b8_category_custom": "Branch8_CatalogCustom/js/category-custom"
        }
    },
    paths: {
        'fabric': 'Branch8_CatalogCustom/lib/editor/ds/fabric',
        'tui-code-snippet': 'Branch8_CatalogCustom/lib/editor/ds/tui-code-snippet.min',
        'tui-color-picker': 'Branch8_CatalogCustom/lib/editor/ds/tui-color-picker',
        'tui-image-editor': 'Branch8_CatalogCustom/lib/dist/tui-image-editor',
        'tuiImageWhiteTheme': 'Branch8_CatalogCustom/lib/editor/js/theme/white-theme',
        'tuiImageBlackTheme': 'Branch8_CatalogCustom/lib/editor/js/theme/black-theme'
    },
    shim: {
        'fabric': {
            'deps': ['jquery']
        },
        'tui-code-snippet': {
            'deps': ['jquery']
        },
        'tui-color-picker': {
            'deps': ['jquery']
        },
        'tui-image-editor': {
            'deps': ['jquery']
        },
        'tuiImageWhiteTheme': {
            'deps': ['jquery']
        },
        'tuiImageBlackTheme': {
            'deps': ['jquery']
        }
    }
};
