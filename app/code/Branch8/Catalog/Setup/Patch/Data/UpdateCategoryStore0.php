<?php
namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateCategoryStore0 implements DataPatchInterface
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
        $sqlVarchar = "delete from catalog_category_entity_varchar where store_id=1";
        $connection->query($sqlVarchar);
        $sqlText = "delete from catalog_category_entity_text where store_id=1";
        $connection->query($sqlText);
        $sqlInt = "delete from catalog_category_entity_int where store_id=1";
        $connection->query($sqlInt);
        $sqlDecimal = "delete from catalog_category_entity_decimal where store_id=1";
        $connection->query($sqlDecimal);
        $sqlDatetime = "delete from catalog_category_entity_datetime where store_id=1";
        $connection->query($sqlDatetime);

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
