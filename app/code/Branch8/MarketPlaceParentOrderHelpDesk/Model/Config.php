<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_DISABLE_SUB_ORDERS_ACCESS = 'parent_order/sub_order_configuration/access';
    public ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isDisableNativeOrderUi()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_DISABLE_SUB_ORDERS_ACCESS) === false;
    }
}
