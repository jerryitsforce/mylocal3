<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class GetProductLogEntryByProductId
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(ResourceConnection $resourceConnection,LoggerInterface $logger)
    {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Retrieve product log entry by product ID.
     *
     * @param int $productId
     * @param bool $isProcessed
     *
     * @return array
     */
    public function execute(int $productId, bool $isProcessed = false): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $connection->getTableName('catalog_product_entity')],
                ['sku', 'row_id']
            )
            ->where('entity_id = ?', $productId);

        $additionalConditions = $isProcessed
            ? '`at_log`.`status` != 0'
            : '`at_log`.`status` = 0';
        $select->joinLeft(
            ['at_log' => $connection->getTableName('marketplace_product_version')],
            '`at_log`.`product_id` = `main_table`.`entity_id` AND ' . $additionalConditions
        );

        $attributeId = $connection->select()
            ->from($connection->getTableName('eav_attribute'), ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', '4');

        $select->joinLeft(
            ['at_name' => $connection->getTableName('catalog_product_entity_varchar')],
            '`at_name`.`row_id` = `main_table`.`row_id` AND' .
            '`at_name`.`store_id` = 0 AND `at_name`.`attribute_id` = ' . $connection->fetchOne($attributeId),
            ['product_name' => 'value']
        );

        $select->order('at_log.id DESC')->limit(1);

        $data = $connection->fetchRow($select);
        $this->logger->debug('GetProductLogEntryByProductId');
        $this->logger->debug($select->__toString());
        $this->logger->debug(json_encode($data));
        return (array)$data ?: [];
    }

    /**
     * Retrieve product log entry by product ID.
     *
     * @param int $productId
     * @param bool $isProcessed
     *
     * @return array
     */
    public function executeAll(int $mpProductId, bool $isProcessed = false, $filterKey = 'main_table.mageproduct_id'): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $connection->getTableName('marketplace_product')],
                ['entity_id', 'mageproduct_id']
            )->where($filterKey . ' = ?', $mpProductId);

        $additionalConditions = $isProcessed
            ? '`at_log`.`status` != 0'
            : '`at_log`.`status` = 0';
        $select->joinLeft(
            ['at_log' => $connection->getTableName('marketplace_product_version')],
            '`at_log`.`product_id` = `main_table`.`mageproduct_id` AND ' . $additionalConditions
        );

        $attributeId = $connection->select()
            ->from($connection->getTableName('eav_attribute'), ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', '4');

        $select->joinLeft(
            ['at_name' => $connection->getTableName('catalog_product_entity_varchar')],
            '`at_name`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_name`.`store_id` = 0 AND `at_name`.`attribute_id` = ' . $connection->fetchOne($attributeId),
            ['product_name' => 'value']
        );

        $select->order('at_log.id DESC');

        $data = $connection->fetchAll($select);
        return (array)$data ?: [];
    }
}
