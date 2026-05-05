<?php

namespace Branch8\MarketplaceParentOrderRetryCancel\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_ENABLE = 'branch8_sales/auto_retry_cancel_parent_order/enable';

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return true
     */
    public function enable()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE,
        );
    }
}
