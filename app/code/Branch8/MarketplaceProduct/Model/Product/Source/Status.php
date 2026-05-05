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
namespace Branch8\MarketplaceProduct\Model\Product\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Webkul\Marketplace\Model\Product;

/**
 * Class Status is used tp get the product available status
 */
class Status implements OptionSourceInterface
{
    /**
     * @var Product
     */
    protected $marketplaceProduct;

    /**
     * Constructor
     *
     * @param Product $marketplaceProduct
     */
    public function __construct(Product $marketplaceProduct)
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
        $options = [];
        foreach ($availableOptions as $key => $value) {
            if ($key == Product::STATUS_DISABLED || $key == Product::STATUS_DENIED) {
                continue;
            }
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
