<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Model\ResourceModel\ProductVersionData;

use Branch8\MarketplaceStaging\Model\ProductVersionData;
use Branch8\MarketplaceStaging\Model\ResourceModel\ProductVersionData as ProductVersionDataResource;
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
        $this->_init(ProductVersionData::class, ProductVersionDataResource::class);
    }
}

