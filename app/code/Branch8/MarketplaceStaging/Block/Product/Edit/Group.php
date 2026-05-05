<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpGroupedProduct
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\MarketplaceStaging\Block\Product\Edit;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\GroupedProduct\Block\Adminhtml\Product\Composite\Fieldset\Grouped;

/**
 * DisplayProduct Class MpGroupedProduct
 */
class Group extends Grouped
{
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var StagingLocator
     */
    protected $locator;

    /**
     * Initialize constructor
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param StagingLocator $locator
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        StagingLocator $locator,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $pricingHelper,
            $data
        );
        $this->pricingHelper = $pricingHelper;
        $this->locator = $locator;
    }

    /**
     * Retrieve array of associated products
     *
     * @return array
     */
    public function getAssociatedProducts()
    {
        $productId = $this->getRequest()->getParam('id');
        $productIds = [];
        $i = 0;
        if ($productId) {
            $product = $this->getProduct();
            $result = $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($result as $item) {
                $productIds[$i] = $item->getId();
                $i++;
            }
        }
        return $productIds;
    }

    /**
     * Retreive Product Type
     *
     * @return string
     */
    public function getProductType()
    {
        $productId = $this->getRequest()->getParam('id');
        if ($productId) {
            $product = $this->getProduct();
            $productType = $product->getTypeId();
        } else {
            $productType = $this->getRequest()->getParam('type');
        }
        return $productType;
    }

    /**
     * Retrieve product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->locator->getProduct();
    }
}
