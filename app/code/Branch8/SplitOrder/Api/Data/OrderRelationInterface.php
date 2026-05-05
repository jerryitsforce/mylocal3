<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitOrder\Api\Data;

interface OrderRelationInterface
{

    const PARENT_ORDER_ID = 'parent_order_id';
    const CHILD_ORDER_ID = 'child_order_id';
    const ORDER_RELATION_ID = 'order_relation_id';

    /**
     * Get order_relation_id
     * @return string|null
     */
    public function getOrderRelationId();

    /**
     * Set order_relation_id
     * @param string $orderRelationId
     * @return \Branch8\SplitOrder\OrderRelation\Api\Data\OrderRelationInterface
     */
    public function setOrderRelationId($orderRelationId);

    /**
     * Get parent_order_id
     * @return string|null
     */
    public function getParentOrderId();

    /**
     * Set parent_order_id
     * @param string $parentOrderId
     * @return \Branch8\SplitOrder\OrderRelation\Api\Data\OrderRelationInterface
     */
    public function setParentOrderId($parentOrderId);
}

