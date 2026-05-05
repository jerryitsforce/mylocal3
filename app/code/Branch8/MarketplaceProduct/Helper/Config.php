<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**#@+
     * XML path constants.
     */
    public const XML_PATH_HIDE_ENABLE_PRODUCT_MASSACTION = 'branch8_catalog/massaction_settings/hide_enable_product';
    public const XML_PATH_HIDE_DISABLE_PRODUCT_MASSACTION = 'branch8_catalog/massaction_settings/hide_disable_product';

    const XML_PATH_IS_ACTIVE_NEGATIVE_GROSS_PROFIT = 'marketplace/branch8_product_approval/enable_negative_gross_profit';
    /**#@-*/

    /**
     * Check if enable product mass action is hidden.
     *
     * @return bool
     */
    public function isHideEnableProductMassAction(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_HIDE_ENABLE_PRODUCT_MASSACTION);
    }

    /**
     * Check if disable product mass action is hidden.
     *
     * @return bool
     */
    public function isHideDisableProductMassAction(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_HIDE_DISABLE_PRODUCT_MASSACTION);
    }

    public function isAllowNegativeGrossProfit(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IS_ACTIVE_NEGATIVE_GROSS_PROFIT);
    }
}
