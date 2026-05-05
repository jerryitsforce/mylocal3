<?php

namespace Branch8\OptionsWithStockIndexer\Setup\Patch\Schema;

use Branch8\OptionsWithStockIndexer\Model\Indexer\Stock\IndexStructure;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CreateTable implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var IndexStructure
     */
    private IndexStructure $structure;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param IndexStructure $structure
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        IndexStructure           $structure
    )
    {
        $this->structure = $structure;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        if (!$connection->isTableExists('branch8_options_stock_index')) {
            $this->structure->create('branch8_options_stock_index', [], []);
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
