<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Plugin\Customer\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

class DataProviderPlugin
{
    private $cached = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly RequestInterface $request,
        private readonly \Branch8\ShippingSubsidy\Model\ConfigData $configData
    )
    {
    }

    /**
     * @param $subject
     * @param array $result
     * @return array
     */
    public function afterGetData($subject, array $result): array
    {
        if (empty($result) || (int)$this->request->getParam('seller_panel') !== 1) {
            return $result;
        }

        foreach ($result as $customerId => &$customerData) {
            $subsidyData = $this->getSubsidyData((int)$customerId);
            if ($subsidyData) {
                $customerData['customer']['extension_attributes']['shipping_subsidy'] = array_merge(
                    $customerData['customer']['shipping_subsidy']['extension_attributes'] ?? [],
                    $subsidyData
                );
            }
        }

        return $result;
    }
    /**
     * @param int $customerId
     * @return array
     */
    private function getSubsidyData(int $customerId): array
    {
        if (isset($this->cached[$customerId])) {
            return $this->cached[$customerId];
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
            ->where('seller_id = ?', $customerId);

        $data = $connection->fetchRow($select);
        $data['default_config'] = $this->configData->getDefaultConfig();

        $data['shipping_subsidy_enabled_group']['shipping_subsidy_enabled'] = $data['shipping_subsidy_enabled'];
        $data['shipping_subsidy_enabled_group']['use_default_shipping_subsidy_enabled'] = $data['use_default_shipping_subsidy_enabled'];
        $data['shipping_subsidy_enabled_group']['default_value'] = $data['default_config']['shipping_subsidy_enabled'] ? 1 : 0;

        $data['shipping_subsidy_mode_group']['shipping_subsidy_mode'] = $data['shipping_subsidy_mode'];
        $data['shipping_subsidy_mode_group']['use_default_subsidy_mode'] = $data['use_default_subsidy_mode'];
        $data['shipping_subsidy_mode_group']['default_value'] = $data['default_config']['shipping_subsidy_mode'];

        $data['shipping_subsidy_home_delivery_group']['shipping_subsidy_home_delivery'] = $data['shipping_subsidy_home_delivery'];
        $data['shipping_subsidy_home_delivery_group']['use_default_home_delivery'] = $data['use_default_home_delivery'];
        $data['shipping_subsidy_home_delivery_group']['default_value'] = $data['default_config']['shipping_subsidy_home_delivery'];

        $data['shipping_subsidy_store_pickup_group']['shipping_subsidy_store_pickup'] = $data['shipping_subsidy_store_pickup'];
        $data['shipping_subsidy_store_pickup_group']['use_default_store_pickup'] = $data['use_default_store_pickup'];
        $data['shipping_subsidy_store_pickup_group']['default_value'] = $data['default_config']['shipping_subsidy_store_pickup'];
        $this->cached[$customerId] = $data ?: [];
        return $this->cached[$customerId];
    }
}
