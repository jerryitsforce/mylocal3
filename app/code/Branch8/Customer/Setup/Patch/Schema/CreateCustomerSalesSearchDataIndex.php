<?php

namespace Branch8\Customer\Setup\Patch\Schema;

use Branch8\Customer\Model\Indexer\CustomerLatestOrder\IndexStructure as CustomerLatestOrderIndexStructure;
use Branch8\Customer\Model\Indexer\CustomerOrders\IndexStructure as CustomerOrdersIndexStructure;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CreateCustomerSalesSearchDataIndex implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var CustomerLatestOrderIndexStructure
     */
    private CustomerLatestOrderIndexStructure $customerLatestOrderIndexStructure;
    /**
     * @var CustomerOrdersIndexStructure
     */
    private CustomerOrdersIndexStructure $customerOrdersIndexStructure;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerLatestOrderIndexStructure $customerLatestOrderIndexStructure
     * @param CustomerOrdersIndexStructure $customerOrdersIndexStructure
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerLatestOrderIndexStructure $customerLatestOrderIndexStructure,
        CustomerOrdersIndexStructure $customerOrdersIndexStructure,
    )
    {
        $this->customerLatestOrderIndexStructure = $customerLatestOrderIndexStructure;
        $this->customerOrdersIndexStructure = $customerOrdersIndexStructure;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        if (!$connection->isTableExists('customer_orders_latest_index')) {
            $this->customerLatestOrderIndexStructure->create('customer_orders_latest_index', [], []);
        }
        if (!$connection->isTableExists('customer_orders_index')) {
            $this->customerOrdersIndexStructure->create('customer_orders_index', [], []);
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
