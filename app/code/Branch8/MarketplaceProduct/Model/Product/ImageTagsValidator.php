<?php

namespace Branch8\MarketplaceProduct\Model\Product;

use Branch8\Catalog\Model\ConfigData;
use Branch8\Catalog\Model\Product\Action\ValidateImageTags;
use Branch8\MarketplaceProduct\Model\ValidatorInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

class ImageTagsValidator implements ValidatorInterface
{
    private ConfigData $configData;

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
     * @param RequestInterface $request
     * @param \Magento\Framework\DataObject $response
     * @return void
     * @throws LocalizedException
     */
    public function validate(Product $product, RequestInterface $request, \Magento\Framework\DataObject $response)
    {
        if (!$this->configData->enableValidateImageTags()) {
            return;
        }
        $tagsNeedValidate = $this->configData->getImageTagsNeedToValidate();
        $tagErros = [];
        if (!count($product->getMediaGalleryImages())) {
            return;
        }
        foreach ($tagsNeedValidate as $code => $tag) {
            if ($product->getData($code) == '' || $product->getData($code) === 'no_selection') {
                $tagErros[$code] = $tag;
            }
        }
        if ($tagErros) {
            $message = __('Please select all required image tags:(%1)', join(', ', $tagErros));
            $response->setError(
                true
            )->setMessage(
                $message
            )->setAttributes(
                $tagErros
            )->setCode(ValidateImageTags::ERR_CODE);
        }
    }
}
