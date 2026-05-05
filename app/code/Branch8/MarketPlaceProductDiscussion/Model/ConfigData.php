<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigData
{
    const PREFIX_PATH = 'product_discussion/general/';

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
     * @param $key
     * @param $prefix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValue($key, $prefix = null)
    {
        $prefix = $prefix ?: self::PREFIX_PATH;
        $path = $prefix . $key;
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId());
    }
}
