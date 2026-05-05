<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Catalog\Product;

/**
 * Downloadable product samples block for preview.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Samples extends \Magento\Downloadable\Block\Catalog\Product\Samples
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_downloadable_product_sample' . $this->getProduct()->getId());
        parent::_construct();
    }
}
