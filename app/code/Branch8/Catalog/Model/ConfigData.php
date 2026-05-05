<?php

namespace Branch8\Catalog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigData
{
    const XML_PATH_IMAGE_TAGS = 'branch8_catalog/images/image_tags_to_validate';
    const XML_PATH_IMAGE_TAGS_VALIDATE = 'branch8_catalog/images/validate_image_tags';


    private ScopeConfigInterface $scopeConfig;
    private \Magento\Eav\Model\Config $eavConfig;

    public function __construct(
        ScopeConfigInterface      $scopeConfig,
        \Magento\Eav\Model\Config $eavConfig
    )
    {
        $this->eavConfig = $eavConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImageTagsNeedToValidate()
    {
        $attributes = explode(',', (string)$this->scopeConfig->getValue(self::XML_PATH_IMAGE_TAGS));
        if (!$attributes) {
            $attributes = ['image', 'small_image', 'thumbnail'];
        }
        $tagImages = [];
        foreach ($attributes as $code) {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $code);
            if (!$attribute->getId()) {
                continue;
            }
            $tagImages[$code] = $attribute->getFrontend()->getLabel();
        }
        return $tagImages;
    }

    /**
     * @return bool
     */
    public function enableValidateImageTags()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IMAGE_TAGS_VALIDATE);
    }
}
