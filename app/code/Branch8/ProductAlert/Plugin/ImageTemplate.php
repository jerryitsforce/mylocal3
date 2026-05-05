<?php

namespace Branch8\ProductAlert\Plugin;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;

class ImageTemplate
{
    /**
     * @param ImageFactory $subject
     * @param Image $result
     * @param Product $product
     * @param string $imageId
     * @param array|null $attributes
     * @return Image
     */
    public function afterCreate(
        ImageFactory $subject,
        Image $result,
        Product $product,
        string $imageId,
        array $attributes = null
    ): Image {
        $result->setTemplate('Branch8_ProductAlert::product/image_with_borders.phtml');
        $result->setData('eighteen_product', $product->getData('eighteen_product'));
        return $result;
    }
}
