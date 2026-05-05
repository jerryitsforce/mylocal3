<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData;

use Branch8\MarketplaceProduct\Model\ProductTempData;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData as ProductTempDataResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'temp_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ProductTempData::class, ProductTempDataResource::class);
    }
}

