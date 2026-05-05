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

namespace Branch8\MarketplaceStaging\Block\Product\Edit\Variations;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

/**
 * Marketplace catalog super product configurable.
 */
class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var StagingLocator
     */
    private $locator;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param StagingLocator                                   $locator
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StagingLocator $locator,
        array $data = []
    ) {
        $this->locator = $locator;
        parent::__construct($context, $data);
    }

    /**
     * Get prodcuts
     *
     * @return void
     */
    public function getSellerProduct()
    {
        return $this->locator->getProduct();
    }
}
