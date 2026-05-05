<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\MarketplaceStaging\Model\Product\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status is used tp get the product available status
 */
class Status implements OptionSourceInterface
{
    /**
     * @var \Webkul\Marketplace\Model\Product
     */
    protected $marketplaceProduct;

    /**
     * Constructor
     *
     * @param \Webkul\Marketplace\Model\Product $marketplaceProduct
     */
    public function __construct(\Webkul\Marketplace\Model\Product $marketplaceProduct)
    {
        $this->marketplaceProduct = $marketplaceProduct;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->marketplaceProduct->getAvailableStatuses();
        unset($availableOptions[\Webkul\Marketplace\Model\Product::STATUS_DENIED]);
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
