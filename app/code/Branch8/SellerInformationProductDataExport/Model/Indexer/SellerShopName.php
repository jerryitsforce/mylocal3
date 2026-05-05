<?php
declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId;

class SellerShopName implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var SyncSellerIdToIndexSellerId
     */
    private $syncService;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param SyncSellerIdToIndexSellerId $syncService
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        SyncSellerIdToIndexSellerId $syncService,
        ResourceConnection $resourceConnection
    ) {
        $this->syncService = $syncService;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        // IDs from mview are marketplace_userdata.entity_id
        $sellerIds = $this->getSellerIdsByUserdataIds($ids);
        if (empty($sellerIds)) {
            return;
        }
        $productIds = $this->getProductIdsBySellerIds($sellerIds);
        if (empty($productIds)) {
            return;
        }
        $this->syncService->syncShopNameIds($productIds);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $this->syncService->syncShopNameAll();
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->syncService->syncShopNameIds($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->syncService->syncShopNameIds([$id]);
    }

    /**
     * Get seller IDs by userdata entity IDs
     *
     * @param array $ids
     * @return array
     */
    private function getSellerIdsByUserdataIds(array $ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('marketplace_userdata'), 'seller_id')
            ->where('entity_id IN (?)', $ids);
        return $connection->fetchCol($select);
    }

    /**
     * Get product IDs by seller IDs
     *
     * @param array $sellerIds
     * @return array
     */
    private function getProductIdsBySellerIds(array $sellerIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('marketplace_product'), 'mageproduct_id') // mageproduct_id is entity_id
            ->where('seller_id IN (?)', $sellerIds);
        return $connection->fetchCol($select);
    }
}
