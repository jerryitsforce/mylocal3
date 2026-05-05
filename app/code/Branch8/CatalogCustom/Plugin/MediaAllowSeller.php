<?php

namespace Branch8\CatalogCustom\Plugin;

use Magento\Catalog\Model\Product;
use Webkul\Marketplace\Block\Product\Helper\Form\Gallery\Content;

class MediaAllowSeller
{
    /**
     * @var Product
     */
    protected Product $_product;

    protected array|null $cachedMediaAttributes = null;

    /**
     * Constructor
     *
     * @param Product $product
     */
    public function __construct(
        Product $product
    ) {
        $this->_product = $product;
    }

    /**
     * Get media attribute
     *
     * @return array
     */
    public function aroundGetProductMediaAttributes(Content $subject, callable $proceed)
    {
        if (!is_null($this->cachedMediaAttributes)) {
            return $this->cachedMediaAttributes;
        }
        $mediaAttributes = [];
        $allowedMediaAttributes = $subject->getAllowedMediaAttributes();
        $productMediaAttributes = [];
        foreach ($this->_product->getAttributes() as $attribute) {
            if ($attribute->getFrontend()->getInputType() == 'media_image') {
                $productMediaAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        foreach ($productMediaAttributes as $attribute) {
            if (in_array($attribute->getAttributeCode(), $allowedMediaAttributes)) {
                $mediaAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $this->cachedMediaAttributes = $mediaAttributes;
    }

    /**
     * @param Content $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllowedMediaAttributes(Content $subject, array $result): array
    {
        $result[] = 'dpa_image';
        return $result;
    }
}
