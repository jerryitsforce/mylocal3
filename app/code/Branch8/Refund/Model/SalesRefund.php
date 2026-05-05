<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Model;

use Branch8\Refund\Api\Data\SalesRefundInterface;
use Magento\Framework\Model\AbstractModel;

class SalesRefund extends AbstractModel implements SalesRefundInterface
{

    const TYPE_CANCEL = 1;
    const TYPE_REFUND = 2;
    
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\Refund\Model\ResourceModel\SalesRefund::class);
    }

    /**
     * @inheritDoc
     */
    public function getSalesRefundId()
    {
        return $this->getData(self::SALES_REFUND_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSalesRefundId($salesRefundId)
    {
        return $this->setData(self::SALES_REFUND_ID, $salesRefundId);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }
}

