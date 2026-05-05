<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_ACCESS_SUB_ORDER_UI = 'parent_order/sub_order_configuration/access';

    const XML_ALLOW_REORDER_OOS_PRODUCT_PATH = 'parent_order/parent_order_configuration/allow_reorder_product_oos';

    const XML_PATH_SHOW_STATUS_PATH = 'parent_order/parent_order_configuration/show_status_frontend';


    const ALLOW_REORDER_OOS_PRODUCT_KEY = 'allow_reorder_oos_product';


    private ScopeConfigInterface $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function accessSubOrderUi()
    {
        return (bool)$this->config->getValue(self::XML_ACCESS_SUB_ORDER_UI);
    }

    /**
     * @return bool
     */
    public function allowReorderOOSProduct()
    {
        return (bool)$this->config->getValue(self::XML_ALLOW_REORDER_OOS_PRODUCT_PATH);
    }

    /**
     * @return bool
     */
    public function showStatus()
    {
        return (bool)$this->config->getValue(self::XML_PATH_SHOW_STATUS_PATH);
    }
}
