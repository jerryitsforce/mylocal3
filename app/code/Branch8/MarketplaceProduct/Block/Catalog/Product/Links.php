<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Catalog\Product;

/**
 * Downloadable product links block for preview.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Links extends \Magento\Downloadable\Block\Catalog\Product\Links
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_downloadable_product_link' . $this->getProduct()->getId());
        parent::_construct();
    }
}
