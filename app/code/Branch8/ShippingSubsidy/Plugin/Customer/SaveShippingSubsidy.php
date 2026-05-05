<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Plugin\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\ShippingSubsidy\Api\Data\ShippingSubsidyInterface;

class SaveShippingSubsidy
{
    /**
     * @param RequestInterface $request
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $extensionAttributes = $result->getExtensionAttributes();
        $subsidy = $extensionAttributes?->getShippingSubsidy();

        if ($subsidy) {
            $this->saveFromExtensionAttribute($result->getId(), $subsidy);
            return $result;
        }
        $shippingSubsidy = $this->request->getParam('customer')['extension_attributes']['shipping_subsidy'] ?? null;
        if (!$shippingSubsidy) {
            return $result;
        }
        $params = [
            'shipping_subsidy_enabled' => $shippingSubsidy['shipping_subsidy_enabled_group']['shipping_subsidy_enabled'],
            'use_default_shipping_subsidy_enabled' => $shippingSubsidy['shipping_subsidy_enabled_group']['use_default_shipping_subsidy_enabled'],
            'shipping_subsidy_mode' => $shippingSubsidy['shipping_subsidy_mode_group']['shipping_subsidy_mode'],
            'use_default_subsidy_mode' => $shippingSubsidy['shipping_subsidy_mode_group']['use_default_subsidy_mode'],
            'shipping_subsidy_home_delivery' => $shippingSubsidy['shipping_subsidy_home_delivery_group']['shipping_subsidy_home_delivery'],
            'use_default_home_delivery' => $shippingSubsidy['shipping_subsidy_home_delivery_group']['use_default_home_delivery'],
            'shipping_subsidy_store_pickup' => $shippingSubsidy['shipping_subsidy_store_pickup_group']['shipping_subsidy_store_pickup'],
            'use_default_store_pickup' => $shippingSubsidy['shipping_subsidy_store_pickup_group']['use_default_store_pickup']
        ];

        $hasData = false;
        foreach ($params as $value) {
            if ($value !== null) {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            return $result;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('marketplace_userdata');

        $data = [];
        if ($params['shipping_subsidy_enabled'] !== null) {
            $data['shipping_subsidy_enabled'] = (int)$params['shipping_subsidy_enabled'];
        }
        if ($params['use_default_shipping_subsidy_enabled'] !== null) {
            $data['use_default_shipping_subsidy_enabled'] = (int)$params['use_default_shipping_subsidy_enabled'];
        }
        if ($params['shipping_subsidy_mode'] !== null) {
            $data['shipping_subsidy_mode'] = $params['shipping_subsidy_mode'];
        }
        if ($params['use_default_subsidy_mode'] !== null) {
            $data['use_default_subsidy_mode'] = (int)$params['use_default_subsidy_mode'];
        }
        if ($params['shipping_subsidy_home_delivery'] !== null) {
            $data['shipping_subsidy_home_delivery'] = $params['shipping_subsidy_home_delivery'] !== '' ? (float)$params['shipping_subsidy_home_delivery'] : null;
        }
        if ($params['use_default_home_delivery'] !== null) {
            $data['use_default_home_delivery'] = (int)$params['use_default_home_delivery'];
        }
        if ($params['shipping_subsidy_store_pickup'] !== null) {
            $data['shipping_subsidy_store_pickup'] = $params['shipping_subsidy_store_pickup'] !== '' ? (float)$params['shipping_subsidy_store_pickup'] : null;
        }
        if ($params['use_default_store_pickup'] !== null) {
            $data['use_default_store_pickup'] = (int)$params['use_default_store_pickup'];
        }

        if (!empty($data)) {
            $connection->update($tableName, $data, ['seller_id = ?' => $result->getId()]);
        }

        return $result;
    }

    /**
     * @param int $customerId
     * @param ShippingSubsidyInterface $subsidy
     * @return void
     */
    private function saveFromExtensionAttribute(int $customerId, ShippingSubsidyInterface $subsidy): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('marketplace_userdata');

        $data = [
            'shipping_subsidy_enabled' => (int)$subsidy->getEnabled(),
            'shipping_subsidy_mode' => $subsidy->getMode(),
            'shipping_subsidy_home_delivery' => $subsidy->getHomeDelivery(),
            'shipping_subsidy_store_pickup' => $subsidy->getStorePickup()
        ];

        $connection->update($tableName, $data, ['seller_id = ?' => $customerId]);
    }
}
