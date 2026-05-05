<?php
namespace Branch8\Sales\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class HtmlentitiesHistory implements DataPatchInterface
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
        $sqlLt = "update sales_order_status_history set comment=REPLACE(comment, '<', '&lt;');";
        $connection->query($sqlLt);
        $sqlGt = "update sales_order_status_history set comment=REPLACE(comment, '>', '&gt;');";
        $connection->query($sqlGt);

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
