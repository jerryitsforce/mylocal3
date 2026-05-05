<?php

namespace Branch8\SingleDeviceLogin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigData
{
    const XSECRECTKEY = 'HOTAIFKE4PGN6C0';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function getForceLogoutUrl()
    {
        return $this->scopeConfig->getValue('single_device_login/general/ws_force_logout_url');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getHost()
    {
        $host = 'buyer_seller_chat/general_settings/host_name';
        $port = 'buyer_seller_chat/general_settings/port_number';
        return $this->getValue($host) . ":" . $this->getValue($port);
    }

    /**
     * @param $path
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId());
    }

}
