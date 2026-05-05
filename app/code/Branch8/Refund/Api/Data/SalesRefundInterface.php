<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Api\Data;

interface SalesRefundInterface
{

    const ENTITY_ID = 'entity_id';
    const SALES_REFUND_ID = 'sales_refund_id';

    /**
     * Get sales_refund_id
     * @return string|null
     */
    public function getSalesRefundId();

    /**
     * Set sales_refund_id
     * @param string $salesRefundId
     * @return \Branch8\Refund\SalesRefund\Api\Data\SalesRefundInterface
     */
    public function setSalesRefundId($salesRefundId);

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Branch8\Refund\SalesRefund\Api\Data\SalesRefundInterface
     */
    public function setEntityId($entityId);
}

