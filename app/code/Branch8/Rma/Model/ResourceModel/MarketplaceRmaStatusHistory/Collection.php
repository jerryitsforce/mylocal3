<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Model\ResourceModel\MarketplaceRmaStatusHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\Rma\Model\MarketplaceRmaStatusHistory::class,
            \Branch8\Rma\Model\ResourceModel\MarketplaceRmaStatusHistory::class
        );
    }
}

