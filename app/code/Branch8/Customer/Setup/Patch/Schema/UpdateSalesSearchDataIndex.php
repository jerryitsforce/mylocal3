<?php

namespace Branch8\Customer\Setup\Patch\Schema;

use Branch8\Customer\Model\Indexer\CustomerLatestOrder\IndexStructure as CustomerLatestOrderIndexStructure;
use Branch8\Customer\Model\Indexer\CustomerOrders\IndexStructure as CustomerOrdersIndexStructure;
use Magento\Customer\Model\Customer;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateSalesSearchDataIndex implements SchemaPatchInterface
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

    private IndexerRegistry $indexRegistry;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerLatestOrderIndexStructure $customerLatestOrderIndexStructure
     * @param CustomerOrdersIndexStructure $customerOrdersIndexStructure
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        ModuleDataSetupInterface          $moduleDataSetup,
        CustomerLatestOrderIndexStructure $customerLatestOrderIndexStructure,
        CustomerOrdersIndexStructure      $customerOrdersIndexStructure,
        IndexerRegistry                   $indexerRegistry
    )
    {
        $this->indexRegistry = $indexerRegistry;
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
        if ($connection->isTableExists('customer_orders_index')) {
            $connection->dropTable('customer_orders_index');
            $this->customerOrdersIndexStructure->create('customer_orders_index', [], []);
            $indexer = $this->indexRegistry->get('customer_orders_index');
            $indexer->reindexAll();
        }
        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateCustomerSalesSearchDataIndex::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
