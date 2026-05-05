<?php

declare(strict_types=1);

namespace PHPCuong\Faq\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        if(version_compare($context->getVersion(), '2.1.7.2', '<')) {
            $tableName = $setup->getTable('phpcuong_faq_category_id');
            if ($connection->isTableExists($tableName)) {
                $connection->addIndex(
                    $tableName,
                    $setup->getIdxName($tableName, ['faq_id', 'category_id'], AdapterInterface::INDEX_TYPE_PRIMARY),
                    ['faq_id', 'category_id'],
                    AdapterInterface::INDEX_TYPE_PRIMARY
                );
            }

            $tableName = $setup->getTable('phpcuong_faq_category_store');
            if ($connection->isTableExists($tableName)) {
                $connection->addIndex(
                    $tableName,
                    $setup->getIdxName($tableName, ['category_id', 'store_id'], AdapterInterface::INDEX_TYPE_PRIMARY),
                    ['category_id', 'store_id'],
                    AdapterInterface::INDEX_TYPE_PRIMARY
                );
            }

            $tableName = $setup->getTable('phpcuong_faq_store');
            if ($connection->isTableExists($tableName)) {
                $connection->addIndex(
                    $tableName,
                    $setup->getIdxName($tableName, ['faq_id', 'store_id'], AdapterInterface::INDEX_TYPE_PRIMARY),
                    ['faq_id', 'store_id'],
                    AdapterInterface::INDEX_TYPE_PRIMARY
                );
            }
        }

        $setup->endSetup();
    }
}
