<?php

namespace Branch8\Catalog\Model\Product\Action;

use Branch8\Catalog\Model\ConfigData;
use Magento\Catalog\Model\Product;

class ValidateImageTags
{
    private ConfigData $configData;

    const ERR_CODE = 'INVALIDATE_IMAGE_TAGS';

    /**
     * @param ConfigData $configData
     */
    public function __construct(
        ConfigData $configData,
    )
    {
        $this->configData = $configData;
    }

    /**
     * @param Product $product
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Product $product)
    {
        $tagsNeedValidate = $this->configData->getImageTagsNeedToValidate();
        $error = [];
        foreach ($tagsNeedValidate as $code => $tag) {
            if (!$product->getData($code) || $product->getData($code) === 'no_selection') {
                $error[$code] = $tag;
            }
        }
        return $error;
    }
}
