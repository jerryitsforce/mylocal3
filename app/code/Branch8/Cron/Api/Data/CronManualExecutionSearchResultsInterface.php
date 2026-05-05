<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Api\Data;

interface CronManualExecutionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get cron_manual_execution list.
     * @return \Branch8\Cron\Api\Data\CronManualExecutionInterface[]
     */
    public function getItems();

    /**
     * Set job list.
     * @param \Branch8\Cron\Api\Data\CronManualExecutionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

