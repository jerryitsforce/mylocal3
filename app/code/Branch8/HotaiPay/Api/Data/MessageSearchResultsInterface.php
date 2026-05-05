<?php
/**
 * Copyright © dev@branch8 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Api\Data;

interface MessageSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Message list.
     * @return \Branch8\HotaiPay\Api\Data\MessageInterface[]
     */
    public function getItems();

    /**
     * Set type list.
     * @param \Branch8\HotaiPay\Api\Data\MessageInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

