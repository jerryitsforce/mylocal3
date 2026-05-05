<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_SHOW_STATUS = 'parent_order/parent_order_configuration/show_status_admin';
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
    public function showStatus()
    {
        return (bool)$this->config->getValue(self::XML_PATH_SHOW_STATUS);
    }
}
