<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Every8D\Api\Data;

interface SmsLogSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get SmsLog list.
     * @return \Branch8\Every8D\Api\Data\SmsLogInterface[]
     */
    public function getItems();

    /**
     * Set phone list.
     * @param \Branch8\Every8D\Api\Data\SmsLogInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

