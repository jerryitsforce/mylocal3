<?php
declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId;

class LivesearchStockStatus implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var SyncSellerIdToIndexSellerId
     */
    private $syncService;

    /**
     * @param SyncSellerIdToIndexSellerId $syncService
     */
    public function __construct(
        SyncSellerIdToIndexSellerId $syncService
    ) {
        $this->syncService = $syncService;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->executeList($ids);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $this->syncService->syncLivesearchInstockAll();
        $this->syncService->syncLivesearchCategoriesAll();
        $this->syncService->syncNeedToRefillAll();
        $this->syncService->syncIndexStockStatusAll();
    }

    /**
     * Execute partial indexation by list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        if (empty($ids)) {
            return;
        }
        $this->syncService->syncLivesearchInstockIds($ids);
        $this->syncService->syncLivesearchCategoriesIds($ids);
        $this->syncService->syncNeedToRefillIds($ids);
        $this->syncService->syncIndexStockStatusIds($ids);
    }

    /**
     * Execute partial indexation by id
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeList([$id]);
    }
}
