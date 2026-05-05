<?php

namespace Branch8\OptionsWithStockAndImages\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 *
 */
class AddMageProductId implements DataPatchInterface
{
    private $_moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->_moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        /**
         * @var $connection \Magento\Framework\DB\Adapter\AdapterInterface
         */
        $connection = $this->_moduleDataSetup->getConnection();
        $productTable = $connection->getTableName('catalog_product_entity');
        $variantionTable = $connection->getTableName('wk_osi_variations');
        $sql = "UPDATE $variantionTable as v";
        $sql .= " JOIN $productTable as e ";
        $sql .= " ON v.product_id = e.row_id";
        $sql .= " SET v.mageproduct_id = e.entity_id";
        $connection->query($sql);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.0.0';
    }
}
