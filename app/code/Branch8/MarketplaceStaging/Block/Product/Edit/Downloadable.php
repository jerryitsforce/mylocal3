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

namespace Branch8\MarketplaceStaging\Block\Product\Edit;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

class Downloadable extends \Magento\Framework\View\Element\Template
{
    /**
     * @var StagingLocator
     */
    private $locator;

    /**
     * Construct
     *
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
     * Get product
     *
     * @return void
     */
    public function getSellerProduct()
    {
        return $this->locator->getProduct();
    }
}
