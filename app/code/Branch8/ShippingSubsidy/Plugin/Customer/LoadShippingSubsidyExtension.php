<?php
declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Plugin\Customer;

use Branch8\ShippingSubsidy\Api\Data\ShippingSubsidyInterface;
use Branch8\ShippingSubsidy\Api\Data\ShippingSubsidyInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Framework\App\ResourceConnection;

class LoadShippingSubsidyExtension
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ShippingSubsidyInterfaceFactory $subsidyFactory,
        private readonly CustomerExtensionFactory $extensionFactory
    ) {
    }

    public function afterGet(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        return $this->loadSubsidyData($result);
    }

    public function afterGetById(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        return $this->loadSubsidyData($result);
    }

    private function loadSubsidyData(CustomerInterface $customer): CustomerInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('marketplace_userdata');

        $select = $connection->select()
            ->from($tableName, [
                'shipping_subsidy_enabled',
                'shipping_subsidy_mode',
                'shipping_subsidy_home_delivery',
                'shipping_subsidy_store_pickup'
            ])
            ->where('seller_id = ?', $customer->getId());

        $data = $connection->fetchRow($select);

        if ($data) {
            $subsidy = $this->subsidyFactory->create();
            $subsidy->setEnabled((bool)$data['shipping_subsidy_enabled']);
            $subsidy->setMode($data['shipping_subsidy_mode']);
            $subsidy->setHomeDelivery($data['shipping_subsidy_home_delivery'] ? (float)$data['shipping_subsidy_home_delivery'] : null);
            $subsidy->setStorePickup($data['shipping_subsidy_store_pickup'] ? (float)$data['shipping_subsidy_store_pickup'] : null);

            $extensionAttributes = $customer->getExtensionAttributes() ?? $this->extensionFactory->create();
            $extensionAttributes->setShippingSubsidy($subsidy);
            $customer->setExtensionAttributes($extensionAttributes);
        }

        return $customer;
    }
}
