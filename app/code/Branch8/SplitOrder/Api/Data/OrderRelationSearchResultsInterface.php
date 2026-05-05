<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitOrder\Api\Data;

interface OrderRelationSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get order_relation list.
     * @return \Branch8\SplitOrder\Api\Data\OrderRelationInterface[]
     */
    public function getItems();

    /**
     * Set parent_order_id list.
     * @param \Branch8\SplitOrder\Api\Data\OrderRelationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

