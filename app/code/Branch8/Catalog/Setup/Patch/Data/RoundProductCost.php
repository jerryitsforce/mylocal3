<?php

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RoundProductCost implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();

        $eavAttrTable = $this->moduleDataSetup->getTable('eav_attribute');
        $decimalTable = $this->moduleDataSetup->getTable('catalog_product_entity_decimal');

        $logFile = BP . '/var/log/round_product_cost.log';
        $logger = new \Monolog\Logger('round_product_cost');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::INFO));

        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from($eavAttrTable, ['attribute_id'])
                ->where('attribute_code = ?', 'cost')
                ->where('entity_type_id = ?', 4)
        );

        if (!$attributeId) {
            $logger->error('Attribute "cost" not found.');
            return;
        }

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($decimalTable, ['value_id', 'row_id', 'value'])
                ->where('attribute_id = ?', $attributeId)
                ->where('value != FLOOR(value)')
        );

        foreach ($rows as $row) {
            $original = $row['value'];
            $rounded = round((float)$original);

            $connection->update(
                $decimalTable,
                ['value' => $rounded],
                ['value_id = ?' => $row['value_id']]
            );
            $logger->info(sprintf(
                'Rounded cost for product with row_id %s: %s → %s',
                $row['row_id'],
                $original,
                $rounded
            ));
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
