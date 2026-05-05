<?php

declare(strict_types=1);

namespace Branch8\PromotionRule\Ui\Component\Rule\Form\Seller;

use Magento\Framework\Data\OptionSourceInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

/**
 * Options tree for "Categories" field
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    private array $sellerOptions = [];

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * Constructor.
     *
     * @param MarketplaceHelper $marketplaceHelper
     */
    public function __construct(MarketplaceHelper $marketplaceHelper)
    {
        $this->marketplaceHelper = $marketplaceHelper;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return $this->getSellers();
    }

    /**
     * Retrieve seller list.
     *
     * @return array
     */
    private function getSellers(): array
    {
        if (empty($this->sellerOptions)) {
            $options = $this->marketplaceHelper->getSellerList();
            $this->sellerOptions = array_slice($options, 1);
        }

        return $this->sellerOptions;
    }
}
