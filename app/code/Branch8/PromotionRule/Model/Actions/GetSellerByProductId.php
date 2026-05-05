<?php

namespace Branch8\PromotionRule\Model\Actions;

use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class GetSellerByProductId
{
    private $productToSeller = [];
    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @param MarketplaceHelper $marketplaceHelper
     */
    public function __construct(MarketplaceHelper $marketplaceHelper)
    {
        $this->marketplaceHelper = $marketplaceHelper;
    }

    /**
     * @param int $productId
     * @return int|mixed
     */
    public function get(int $productId)
    {
        if (isset($this->productToSeller[$productId])) {
            return $this->productToSeller[$productId];
        }
        $this->productToSeller[$productId] = (int)$this->marketplaceHelper->getSellerIdByProductId($productId);
        return $this->productToSeller[$productId];
    }
}
