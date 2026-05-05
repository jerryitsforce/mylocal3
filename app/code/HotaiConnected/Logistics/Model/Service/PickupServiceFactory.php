<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Service;

use HotaiConnected\Logistics\Model\Config\LogisticsCompany;
use Magento\Framework\ObjectManagerInterface;

/**
 * Pickup Service Factory
 *
 * Creates the appropriate pickup service based on logistics company
 */
class PickupServiceFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $services;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $services
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $services = []
    ) {
        $this->objectManager = $objectManager;
        $this->services = $services;
    }

    /**
     * Create pickup service instance for specific carrier
     *
     * @param string $carrier Carrier name (e.g., '新竹物流')
     * @return mixed
     * @throws \Exception
     */
    public function create($carrier)
    {
        // Map carrier name to service class
        $serviceMap = [
            LogisticsCompany::COMPANY_HCT_NAME => HctPickupService::class,
            '新竹物流' => HctPickupService::class,
            // Add more carriers here
            // LogisticsCompany::COMPANY_SF_NAME => SfPickupService::class,
            // LogisticsCompany::COMPANY_TCAT_NAME => TcatPickupService::class,
        ];

        // Allow custom services from DI configuration
        if (isset($this->services[$carrier])) {
            $serviceClass = $this->services[$carrier];
        } elseif (isset($serviceMap[$carrier])) {
            $serviceClass = $serviceMap[$carrier];
        } else {
            throw new \Exception(__('不支援的物流商: %1', $carrier));
        }

        return $this->objectManager->create($serviceClass);
    }
}
