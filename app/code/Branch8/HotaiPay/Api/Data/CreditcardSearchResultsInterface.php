<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Api\Data;

interface CreditcardSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get creditcard list.
     * @return \Branch8\HotaiPay\Api\Data\CreditcardInterface[]
     */
    public function getItems();

    /**
     * Set is_main list.
     * @param \Branch8\HotaiPay\Api\Data\CreditcardInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

