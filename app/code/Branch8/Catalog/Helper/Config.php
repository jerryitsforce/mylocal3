<?php

declare(strict_types=1);

namespace Branch8\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**#@+
     * XML path constants.
     */
    public const XML_PATH_HIDE_DELETE_PRODUCT_MASSACTION = 'branch8_catalog/massaction_settings/hide_delete_product';
    public const XML_PATH_PRODUCT_LOG_CLEANER_MONTHS_TO_KEEP = 'branch8_catalog/product_status_change_log_cleaner/months_to_keep';
    /**#@-*/

    /**
     * Check if delete product mass action is hidden.
     *
     * @return bool
     */
    public function isHideDeleteProductMassAction(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_HIDE_DELETE_PRODUCT_MASSACTION);
    }

    /**
     * Get the number of months to keep product change history records.
     *
     * @return int
     */
    public function getMonthsToKeep(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_PRODUCT_LOG_CLEANER_MONTHS_TO_KEEP);
    }
}
