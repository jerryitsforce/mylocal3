<?php

namespace Branch8\MarketplaceParentOrderRetryCancel\Model;

use Magento\Framework\Model\AbstractExtensibleModel;

class FailedRecord extends AbstractExtensibleModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord::class
        );
    }
}
