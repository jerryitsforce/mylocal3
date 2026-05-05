<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;

class SellerSubsidy
{
    private const XML_PATH_ENABLED = 'shipping_subsidy/general/enabled';
    private const XML_PATH_SUBSIDY_MODE = 'shipping_subsidy/general/subsidy_mode';
    private const XML_PATH_HOME_DELIVERY = 'shipping_subsidy/general/home_delivery_subsidy';
    private const XML_PATH_STORE_PICKUP = 'shipping_subsidy/general/store_pickup_subsidy';

    private $sellerSubsidyConfig=[];
    private $sellerData = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param int $sellerId
     * @param string $shippingMethod
     * @param int|null $storeId
     * @return array
     */
    public function getSellerSubsidyConfig(int $sellerId, string $shippingMethod, ?int $storeId = null): array
    {
        $key = $sellerId . '_' . $shippingMethod;
        if (isset($this->sellerSubsidyConfig[$key])) {
            return $this->sellerSubsidyConfig[$key];
        }
        $this->sellerSubsidyConfig[$key] = [
            'enabled' => $this->isSellerSubsidyEnabled($sellerId, $storeId),
            'mode' => $this->getSubsidyMode($sellerId, $storeId),
            'value' => $this->getSubsidyValue($sellerId, $shippingMethod, $storeId),
        ];
        return $this->sellerSubsidyConfig[$key];
    }
    /**
     * Check if shipping subsidy is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Calculate shipping subsidy split between platform and seller
     *
     * @param int $sellerId
     * @param string $shippingMethod
     * @param float $shippingAmount
     * @param int|null $storeId
     * @return array
     */
    public function calculateSubsidy(int $sellerId, string $shippingMethod, float $shippingAmount, ?int $storeId = null): array
    {
        if (!$this->isSellerSubsidyEnabled($sellerId, $storeId)) {
            return [
                'platform_amount' => $shippingAmount,
                'seller_amount' => 0,
                'config' => null
            ];
        }

        $config = $this->getSellerSubsidyConfig($sellerId, $shippingMethod, $storeId);
        $mode = $config['mode'];
        $value = $config['value'];

        if ($mode === 'fixed') {
            $sellerAmount = min($value, $shippingAmount);
            $platformAmount = $shippingAmount - $sellerAmount;
        } else {
            $platformAmount = ($shippingAmount * (100 - $value)) / 100;
        }

        return [
            'platform_amount' => $platformAmount,
            'seller_amount' => $shippingAmount - $platformAmount,
            'config' => $config
        ];
    }

    /**
     * Check if seller has custom subsidy enabled
     *
     * @param int $sellerId
     * @return bool
     */
    private function isSellerSubsidyEnabled(int $sellerId, ?int $storeId = null): bool
    {
        $data = $this->getSellerData($sellerId);

        if ($data && !empty($data['use_default_shipping_subsidy_enabled'])) {
            return $this->isEnabled($storeId);
        }

        if ($data && isset($data['shipping_subsidy_enabled'])) {
            return (bool)$data['shipping_subsidy_enabled'];
        }

        return $this->isEnabled($storeId);
    }

    /**
     * Get subsidy mode for seller (percent or fixed)
     *
     * @param int $sellerId
     * @param int|null $storeId
     * @return string
     */
    private function getSubsidyMode(int $sellerId, ?int $storeId = null): string
    {
        $data = $this->getSellerData($sellerId);

        if ($data && !empty($data['use_default_subsidy_mode'])) {
            return (string)$this->scopeConfig->getValue(self::XML_PATH_SUBSIDY_MODE, ScopeInterface::SCOPE_STORE, $storeId) ?: 'percent';
        }

        if ($data && !empty($data['shipping_subsidy_mode'])) {
            return $data['shipping_subsidy_mode'];
        }

        return (string)$this->scopeConfig->getValue(self::XML_PATH_SUBSIDY_MODE, ScopeInterface::SCOPE_STORE, $storeId) ?: 'percent';
    }

    /**
     * @param int $sellerId
     * @return array
     */
    private function getSellerData(int $sellerId): array
    {
        if (isset($this->sellerData[$sellerId])) {
            return $this->sellerData[$sellerId];
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('marketplace_userdata');

        $select = $connection->select()
            ->from($tableName, [
                'shipping_subsidy_enabled',
                'use_default_shipping_subsidy_enabled',
                'shipping_subsidy_mode',
                'use_default_subsidy_mode',
                'shipping_subsidy_home_delivery',
                'use_default_home_delivery',
                'shipping_subsidy_store_pickup',
                'use_default_store_pickup'
            ])
            ->where('seller_id = ?', $sellerId);

        $this->sellerData[$sellerId] = $connection->fetchRow($select) ?: [];
        return $this->sellerData[$sellerId];
    }

    /**
     * @param int $sellerId
     * @param string $shippingMethod
     * @param int|null $storeId
     * @return float
     */
    private function getSubsidyValue(int $sellerId, string $shippingMethod, ?int $storeId = null): float
    {
        $field = $this->getFieldByShippingMethod($shippingMethod);
        if (!$field) {
            return 0.0;
        }

        $useDefaultField = $field === 'shipping_subsidy_home_delivery' ? 'use_default_home_delivery' : 'use_default_store_pickup';

        $data = $this->getSellerData($sellerId);

        if ($data && !empty($data[$useDefaultField])) {
            return $this->getDefaultSubsidy($shippingMethod, $storeId);
        }

        if ($data && isset($data[$field]) && $data[$field] !== null) {
            return (float)$data[$field];
        }

        return $this->getDefaultSubsidy($shippingMethod, $storeId);
    }

    /**
     * @param string $shippingMethod
     * @return string|null
     */
    private function getFieldByShippingMethod(string $shippingMethod): ?string
    {
        if (stripos($shippingMethod, 'home') !== false || stripos($shippingMethod, 'delivery') !== false) {
            return 'shipping_subsidy_home_delivery';
        }
        if (stripos($shippingMethod, 'pickup') !== false || stripos($shippingMethod, 'store') !== false) {
            return 'shipping_subsidy_store_pickup';
        }
        return null;
    }

    /**
     * @param string $shippingMethod
     * @param int|null $storeId
     * @return float
     */
    private function getDefaultSubsidy(string $shippingMethod, ?int $storeId = null): float
    {
        if (stripos($shippingMethod, 'home') !== false || stripos($shippingMethod, 'delivery') !== false) {
            return (float)$this->scopeConfig->getValue(self::XML_PATH_HOME_DELIVERY, ScopeInterface::SCOPE_STORE, $storeId);
        }
        if (stripos($shippingMethod, 'pickup') !== false || stripos($shippingMethod, 'store') !== false) {
            return (float)$this->scopeConfig->getValue(self::XML_PATH_STORE_PICKUP, ScopeInterface::SCOPE_STORE, $storeId);
        }
        return 0.0;
    }
}
