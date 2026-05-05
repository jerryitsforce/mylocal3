<?php

namespace Branch8\FamilyBonusPin\Cron;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\FamilyBonusPin\Helper\Common as CommonHelper;
use Branch8\FamilyBonusPin\Model\Config\Source\LogOption;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecordRepository as RecordRepository;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId as SyncQuantityServices;

class UpdateTicketQuantityBySaleTime
{
    const LOG_FOLDER_NAME = 'FamilyBonusPin/Cron/UpdateTicketQuantityBySaleTime';

    private const DEBUG_LOG_OPTION = LogOption::LOG_CRON_UPDATE_TICKET_QUANTITY_BY_SALE_TIME;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ProductCollectionFactory */
    protected $productCollectionFactory;

    /** @var RecordRepository */
    protected $recordRepository;

    /** @var SourceItemInterfaceFactory */
    protected $sourceItemFactory;

    /** @var SourceItemsSaveInterface */
    protected $sourceItemsSaveInterface;

    /** @var SyncQuantityServices */
    protected $syncQuantityServices;

    public function __construct(
        CommonHelper $commonHelper,
        ProductCollectionFactory $productCollectionFactory,
        RecordRepository $recordRepository,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SyncQuantityServices $syncQuantityServices
    ) {
        $this->commonHelper             = $commonHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->recordRepository         = $recordRepository;
        $this->sourceItemFactory        = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->syncQuantityServices     = $syncQuantityServices;
    }

    public function execute()
    {
        try {
            $this->writeLog("FamilyBonusPin UpdateTicketQuantityBySaleTime cron start.");

            $collection = $this->getTargetProductCollection();
            $productIds = $collection->getAllIds();

            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            foreach ($collection->getItems() as $product) {
                $availableCollection = $this->recordRepository->getAvailableForSaleRecordsByProductId($product->getId());
                $availableQuantity   = $availableCollection->getSize();

                $this->updateProductQuantity($product, $availableQuantity);
            }

            $this->syncQuantityServices->syncLivesearchInstockIds($productIds);
            $this->syncQuantityServices->syncNeedToRefillIds($productIds);

            $this->writeLog("FamilyBonusPin UpdateTicketQuantityBySaleTime cron end.");
        } catch (\Exception $e) {
            $this->writeLog("Something went wrong while executing cron, exception message: " . $e->getMessage());
        }
    }

    /**
     * 取得目標票券product collection
     *
     * @return ProductCollection
     */
    private function getTargetProductCollection(): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();

        // product collection必須加上setFlag('has_stock_status_filter', false)
        // 否則只會拿到當前可販售的商品(根據enable狀態, stock狀態)
        $productCollection->addAttributeToSelect(['entity_id', 'sku'])
            ->setFlag('has_stock_status_filter', false)
            ->addAttributeToFilter(
                VirtualProductType::ATTRIBUTE_CODE,
                ['eq', VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET]
            )->load();

        return $productCollection;
    }

    /**
     * 更新產品的庫存
     *
     * @param integer $productId
     * @param integer $quantity
     * @return void
     */
    private function updateProductQuantity(\Magento\Catalog\Api\Data\ProductInterface $product, int $quantity): void
    {
        /** @var SourceItemInterface $sourceItem */
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setSku($product->getSku());
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($quantity ? SourceItemInterface::STATUS_IN_STOCK : SourceItemInterface::STATUS_OUT_OF_STOCK);

        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }

    private function writeLog($message)
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
