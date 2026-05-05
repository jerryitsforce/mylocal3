<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Plugin\Magento\ConfigurableProduct\Model\ResourceModel\Attribute;

use Magento\Catalog\Model\ResourceModel\Product\Website as ProductWebsiteResource;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Plugin\Model\ResourceModel\Attribute\ScopedOptionSelectBuilder as CoreScopedOptionSelectBuilder;

/**
 * Plugin for OptionSelectBuilderInterface to filter by website assignments.
 */
class ScopedOptionSelectBuilder extends CoreScopedOptionSelectBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductWebsiteResource
     */
    private $productWebsiteResource;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductWebsiteResource $productWebsiteResource
     * @param RequestInterface $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductWebsiteResource $productWebsiteResource,
        RequestInterface $request
    ) {
        $this->storeManager = $storeManager;
        $this->productWebsiteResource = $productWebsiteResource;
        $this->request = $request;
        parent::__construct($storeManager, $productWebsiteResource);
    }

    /**
     * Add website filter to select.
     *
     * @param OptionSelectBuilderInterface $subject
     * @param Select $select
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSelect(OptionSelectBuilderInterface $subject, Select $select)
    {
        $controller = $this->request->getRouteName();
        if ($controller == 'marketplacestaging') {
            return $select;
        }

        return parent::afterGetSelect($subject, $select);
    }
}
