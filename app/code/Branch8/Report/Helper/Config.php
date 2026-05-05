<?php

declare(strict_types=1);

namespace Branch8\Report\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**#@+
     * XML path constants.
     */
    public const XML_PATH_PRODUCT_LOG_CLEANER_DAYS_TO_KEEP = 'branch8_catalog/product_change_log_cleaner/days_to_keep';
    /**#@-*/

    /**
     * Get the number of days to keep product change history records.
     *
     * @return int
     */
    public function getDaysToKeep(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_PRODUCT_LOG_CLEANER_DAYS_TO_KEEP);
    }
}
