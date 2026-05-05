<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Model;

use Magento\Backend\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigData
{
    const XML_PATH_ENABLE = 'wishlist_stock_alert/general/enabled';

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $field
     * @param $scopeValue
     * @param string $scopeType
     * @return mixed
     */
    public function getConfigValue(string $field, $scopeValue = null, string $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $path = 'wishlist_stock_alert/general/' . $field;
        return $this->scopeConfig->getValue($path, $scopeType, $scopeValue);
    }


    /**
     * @return bool
     */
    public function enabled()
    {
        return (bool)$this->getConfigValue('enabled');
    }
}
