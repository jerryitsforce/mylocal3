/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            tinyMCEModal: 'Branch8_MarketplaceStaging/js/tinyMCE/modal',
            stageListing: 'Branch8_MarketplaceStaging/js/product/stage-listing',
            stageUpsellProduct: 'Branch8_MarketplaceStaging/js/product/upsell-product',
            stageCrosssellProduct: 'Branch8_MarketplaceStaging/js/product/crosssell-product',
            stageRelatedProduct: 'Branch8_MarketplaceStaging/js/product/related-product',
            stageProductGallery: 'Branch8_MarketplaceStaging/js/product-gallery',
            stageDescriptionGallery: 'Branch8_MarketplaceStaging/js/description-gallery',
            stageOpenVideoModal: 'Branch8_MarketplaceStaging/js/video-modal',
            stageSellerEditProduct: 'Branch8_MarketplaceStaging/js/product/stage-edit-product',
            stagePlaceOptions: 'Branch8_MarketplaceStaging/js/bundle/place-options',
            stageGroupedProduct: 'Branch8_MarketplaceStaging/js/grouped/grouped-product',
            stageGroupedProductAdd: 'Branch8_MarketplaceStaging/js/grouped/grouped-product-add',
            managevariation: 'Branch8_MarketplaceStaging/js/optionswithstockandimages/managevariation',
        }
    },
    bundles: {
        "Branch8_MarketplaceStaging/js/product/customtheme": [
            "modalPopup",
            "useDefault",
            "collapsable"
        ]
    },
    config: {
        mixins: {
            'mage/validation': {
                'Branch8_MarketplaceStaging/js/validation-mixin': true
            }
        }
    }
};
