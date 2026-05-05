<?php

namespace Branch8\MagentoAdvancedSalesRule\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddFilterTextHashAndFilterSkus implements DataPatchInterface
{
    private $_moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->_moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $connection = $this->_moduleDataSetup->getConnection();
        $prefix = "product:attribute:sku:";
        $table = $this->_moduleDataSetup->getTable('magento_salesrule_filter');
        $columns = [
            'rule_filter_id',
            'rule_id',
            'filter_text',
            'filter_skus',
            'filter_text_hash',
            'filter_text_generator_class'
        ];
        $query = $connection->select()->from($table, $columns);
        $rows = $connection->fetchAll($query);
        $insertData = [];
        foreach ($rows as $row) {
            $skus = '';
            if ($row['filter_text_generator_class'] === 'Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute'
                && str_contains($row['filter_text'], $prefix)
            ) {
                $skuString = str_replace($prefix, "", $row['filter_text']);
                $skus = explode(",", $skuString);
            }
            $insertData[] = [
                'rule_filter_id' => $row['rule_filter_id'],
                'filter_text_hash' => hash('sha256', $row['filter_text']),
                'filter_skus' => $skus ? join(',', $skus) : ''
            ];
        }
        if ($insertData) {
            $connection->insertOnDuplicate($table, $insertData, ['filter_text_hash', 'filter_skus']);
        }
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
