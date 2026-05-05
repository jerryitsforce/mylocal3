<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion;

use Branch8\MarketplaceProduct\Model\ProductVersion;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion as ProductVersionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ProductVersion::class, ProductVersionResource::class);
    }
}

