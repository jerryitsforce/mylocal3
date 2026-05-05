<?php

namespace Branch8\CartItemPosition\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\DB\Ddl\Table;

class AddPositionColumn implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply patch script
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        // Add 'position' column to quote_item table
        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('quote_item'),
            'position',
            [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Cart Item Position',
            ]
        );

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Revert changes (optional, implement if you want to revert the patch)
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->dropColumn(
            $this->moduleDataSetup->getTable('quote_item'),
            'position'
        );
    }

    /**
     * Dependencies (optional, if there are any dependencies between patches)
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Aliases for backwards compatibility (optional)
     */
    public function getAliases()
    {
        return [];
    }
}
