<?php

namespace Branch8\ProductAlert\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixSaaSCatalogDataExporterIds implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();

        // 1. Clean up invalid source_entity_id in cde_product_attributes_feed
        $feedTable = $this->moduleDataSetup->getTable('cde_product_attributes_feed');
        if ($connection->isTableExists($feedTable)) {
            $connection->delete($feedTable, 'source_entity_id > 65535');
        }

        // 2. Clear index batches to force fresh indexing without collisions
        $indexTable = $this->moduleDataSetup->getTable('cde_product_attributes_feed_index_batches');
        if ($connection->isTableExists($indexTable)) {
            $connection->delete($indexTable);
        }

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
