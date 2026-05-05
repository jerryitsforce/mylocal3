<?php

namespace Branch8\SellerInformationProductDataExport\Setup\Patch\Data;

use Branch8\SellerInformationProductDataExport\Model\Source\IndexSellerOptions;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ModifyIndexSellerId implements DataPatchInterface
{
    private $_moduleDataSetup;

    private $_eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory          $eavSetupFactory
    )
    {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $this->_moduleDataSetup]);
        $values = [
            'source_model' => IndexSellerOptions::class,
            'frontend_input' => 'select',
            'is_user_defined' => 0,
            'is_filterable' => true,
            'is_filterable_in_search' => true
        ];
        foreach ($values as $key => $value) {
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'index_seller_id',
                $key,
                $value
            );
        }
    }

    public static function getDependencies()
    {
        return [
            CreateIndexSellerIdToIndexLiveSearch::class
        ];
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
