<?php
namespace Branch8\Sales\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateIsVirtual implements DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;

    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        
        $connection = $this->moduleDataSetup->getConnection();
        $sqlLt = "UPDATE sales_order_grid JOIN sales_order ON sales_order_grid.entity_id = sales_order.entity_id SET sales_order_grid.is_virtual = sales_order.is_virtual;";
        $connection->query($sqlLt);

        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
