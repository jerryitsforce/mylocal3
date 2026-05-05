<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAllowProductOOSInCart\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_ALLOW_REORDER_OOS_PRODUCT_PATH = 'parent_order/parent_order_configuration/allow_reorder_product_oos';

    const ALLOW_REORDER_OOS_PRODUCT_KEY = 'allow_reorder_oos_product';
    private ScopeConfigInterface $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function allowReorderOOSProduct()
    {
        return (bool)$this->config->getValue(self::XML_ALLOW_REORDER_OOS_PRODUCT_PATH);
    }
}
