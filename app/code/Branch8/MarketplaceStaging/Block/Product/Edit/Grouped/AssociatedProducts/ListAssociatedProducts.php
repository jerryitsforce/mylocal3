<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Products in grouped grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Branch8\MarketplaceStaging\Block\Product\Edit\Grouped\AssociatedProducts;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

/**
 * @api
 * @since 100.0.2
 */
class ListAssociatedProducts extends \Magento\Framework\View\Element\Template
{
    /**
     * @var StagingLocator
     */
    private $locator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param StagingLocator $locator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StagingLocator $locator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceCurrency = $priceCurrency;
        $this->locator = $locator;
    }

    /**
     * Retrieve grouped products
     *
     * @return array
     */
    public function getAssociatedProducts()
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $this->locator->getProduct();
        $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
        $products = [];

        foreach ($associatedProducts as $product) {
            $products[] = [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $this->priceCurrency->format($product->getPrice(), false),
                'qty' => $product->getQty(),
                'position' => $product->getPosition(),
            ];
        }
        return $products;
    }
}
