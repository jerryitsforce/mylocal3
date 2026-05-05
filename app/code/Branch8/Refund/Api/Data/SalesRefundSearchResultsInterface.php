<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Api\Data;

interface SalesRefundSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get sales_refund list.
     * @return \Branch8\Refund\Api\Data\SalesRefundInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Branch8\Refund\Api\Data\SalesRefundInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

