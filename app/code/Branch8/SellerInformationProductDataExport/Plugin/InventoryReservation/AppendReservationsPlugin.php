<?php

declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Plugin\InventoryReservation;

use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\App\ResourceConnection;

class AppendReservationsPlugin
{
    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ProductResource $productResource,
        IndexerRegistry $indexerRegistry,
        ResourceConnection $resourceConnection
    ) {
        $this->productResource = $productResource;
        $this->indexerRegistry = $indexerRegistry;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param AppendReservationsInterface $subject
     * @param void $result
     * @param array $reservations
     * @return void
     */
    public function afterExecute(AppendReservationsInterface $subject, $result, array $reservations)
    {
        if (empty($reservations)) {
            return $result;
        }

        $skus = [];
        foreach ($reservations as $reservation) {
            if ($reservation->getSku()) {
                $skus[] = $reservation->getSku();
            }
        }

        $skus = array_unique($skus);
        $productIds = [];

        if (!empty($skus)) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $entityTable = $this->resourceConnection->getTableName('catalog_product_entity');
                $select = $connection->select()
                    ->from($entityTable, ['entity_id'])
                    ->where('sku IN (?)', $skus);
                $productIds = $connection->fetchCol($select);
                $productIds = array_map('intval', $productIds);
            } catch (\Exception $e) {}
        }

        if (!empty($productIds)) {
            try {
                $indexer = $this->indexerRegistry->get('branch8_livesearch_stock_status');
                
                if ($indexer->isScheduled()) {
                    $view = $indexer->getView();
                    if ($view) {
                        $changelog = $view->getChangelog();
                        if ($changelog) {
                            $tableName = $changelog->getName();
                            $connection = $this->resourceConnection->getConnection();
                            $data = [];
                            foreach ($productIds as $id) {
                                $data[] = ['entity_id' => $id];
                            }
                            foreach (array_chunk($data, 1000) as $chunk) {
                                $connection->insertMultiple($tableName, $chunk);
                            }
                        }
                    }
                } else {
                    $indexer->reindexList($productIds);
                }
            } catch (\Exception $e) {}
        }

        return $result;
    }
}
