<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Pricing;

/**
 * Catalog price render.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Render extends \Magento\Catalog\Pricing\Render
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_product_pricing_render' . $this->getProductId());
        parent::_construct();
    }

    /**
     * Get product ID.
     *
     * @return int
     */
    private function getProductId(): int
    {
        return (int)$this->getRequest()->getParam('id');
    }
}
