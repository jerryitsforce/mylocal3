<?php
declare(strict_types=1);

/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       24/04/2026
 */

namespace Branch8\ShippingSubsidy\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigData
{
    private const XML_PATH_ENABLED = 'shipping_subsidy/general/enabled';
    private const XML_PATH_SUBSIDY_MODE = 'shipping_subsidy/general/subsidy_mode';
    private const XML_PATH_HOME_DELIVERY_SUBSIDY = 'shipping_subsidy/general/home_delivery_subsidy';
    private const XML_PATH_STORE_PICKUP_SUBSIDY = 'shipping_subsidy/general/store_pickup_subsidy';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getSubsidyMode($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SUBSIDY_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|string|null $storeId
     * @return float
     */
    public function getHomeDeliverySubsidy($storeId = null): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_HOME_DELIVERY_SUBSIDY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|string|null $storeId
     * @return float
     */
    public function getStorePickupSubsidy($storeId = null): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_STORE_PICKUP_SUBSIDY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|string|null $storeId
     * @return array
     */
    public function getDefaultConfig($storeId = null): array
    {
        return [
            'shipping_subsidy_enabled' => $this->isEnabled($storeId),
            'shipping_subsidy_mode' => $this->getSubsidyMode($storeId),
            'shipping_subsidy_home_delivery' => $this->getHomeDeliverySubsidy($storeId),
            'shipping_subsidy_store_pickup' => $this->getStorePickupSubsidy($storeId)
        ];
    }
}
