<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Plugin\Magento\CatalogInventory\Helper;

class Stock
{
    public const ALLOWED_ACTIONS = [
        'marketplace_product_add',
        'marketplace_product_edit',
        'marketplace_mui_index_render',
        'marketplacestaging_mui_render_handle'
    ];
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected \Magento\Framework\App\RequestInterface $request;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function beforeAddIsInStockFilterToCollection(\Magento\CatalogInventory\Helper\Stock $subject, $collection)
    {
        $fullActionName = $this->request->getFullActionName();
        if (in_array($fullActionName, static::ALLOWED_ACTIONS)) {
            $collection->setFlag('has_stock_status_filter',true);
        }
        return [$collection];
    }
}
