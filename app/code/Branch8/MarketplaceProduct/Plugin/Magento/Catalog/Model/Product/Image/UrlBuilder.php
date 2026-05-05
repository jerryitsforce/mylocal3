<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceProduct\Plugin\Magento\Catalog\Model\Product\Image;

/**
 * Product tax class source model.
 */
class UrlBuilder
{
    /**
     * Build image url using base path and params
     *
     * @param $subject
     * @param $result
     * @param string $baseFilePath
     * @param string $imageDisplayArea
     * @return string
     */
    public function afterGetUrl($subject, $result, string $baseFilePath, string $imageDisplayArea)
    {
        if ($result && strrpos((string)$result, '.tmp') !== false) {
            $result = str_replace('.tmp', '', $result);
            $result = str_replace('media/catalog/product', 'media/tmp/catalog/product', $result);
        }
        return $result;
    }
}
