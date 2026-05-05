<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitOrder\Model;

use Branch8\SplitOrder\Api\Data\OrderRelationInterface;
use Magento\Framework\Model\AbstractModel;

class OrderRelation extends AbstractModel implements OrderRelationInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\SplitOrder\Model\ResourceModel\OrderRelation::class);
    }

    /**
     * @inheritDoc
     */
    public function getOrderRelationId()
    {
        return $this->getData(self::ORDER_RELATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderRelationId($orderRelationId)
    {
        return $this->setData(self::ORDER_RELATION_ID, $orderRelationId);
    }

    /**
     * @inheritDoc
     */
    public function getParentOrderId()
    {
        return $this->getData(self::PARENT_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setParentOrderId($parentOrderId)
    {
        return $this->setData(self::PARENT_ORDER_ID, $parentOrderId);
    }

     /**
     * @inheritDoc
     */
    public function getChildOrderId()
    {
        return $this->getData(self::CHILD_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setChildOrderId($childOrderId)
    {
        return $this->setData(self::CHILD_ORDER_ID, $childOrderId);
    }
}

