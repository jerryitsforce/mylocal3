<?php

namespace HotaiConnected\Qware\Observer;

use HotaiConnected\Qware\Helper\Common as QwareCommon;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Model\VersionManager;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Psr\Log\LoggerInterface;

class AutoCreateStaging implements ObserverInterface
{
    /** @var array */
    private static $processingProducts = [];

    /** @var UpdateRepositoryInterface */
    protected $updateRepository;

    /** @var UpdateInterfaceFactory */
    protected $updateFactory;

    /** @var VersionManager */
    protected $versionManager;

    /** @var ProductStagingInterface */
    protected $productStaging;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        UpdateInterfaceFactory $updateFactory,
        VersionManager $versionManager,
        ProductStagingInterface $productStaging,
        LoggerInterface $logger
    ) {
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
        $this->versionManager = $versionManager;
        $this->productStaging = $productStaging;
        $this->logger = $logger;
    }

    /**
     * 當產品保存時，自動創建 Qware 排程
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Product $product */
            $product = $observer->getEvent()->getProduct();
            $productId = $product->getId();

            // 防止遞迴執行
            if (isset(self::$processingProducts[$productId])) {
                return;
            }

            self::$processingProducts[$productId] = true;

            // 檢查是否為 Qware 產品
            if (!$this->isQwareProduct($product)) {
                unset(self::$processingProducts[$productId]);
                return;
            }

            $startDate = $product->getData(QwareCommon::ATTRIBUTE_CODE_QWARE_SALE_START);
            $endDate = $product->getData(QwareCommon::ATTRIBUTE_CODE_QWARE_SALE_END);

            // 檢查是否有設定排程時間
            if (empty($startDate) || empty($endDate)) {
                unset(self::$processingProducts[$productId]);
                return;
            }

            // 驗證排程時間
            if (!$this->validateStagingDates($startDate, $endDate)) {
                $this->logger->warning('[Qware AutoCreateStaging] Invalid staging dates provided', [
                    'product_id' => $product->getId(),
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                unset(self::$processingProducts[$productId]);
                return;
            }

            // 記錄原始 row_id
            $originalProductRowId = $product->getRowId();
            
            // 創建排程
            $this->createProductStaging($product, $startDate, $endDate);

            // 記錄排程後的 row_id
            $stagingProductRowId = $product->getRowId();
            
            $this->logger->info('[Qware AutoCreateStaging] Successfully created staging schedule', [
                'product_id' => $product->getId(),
                'original_product_row_id' => $originalProductRowId,
                'staging_product_row_id' => $stagingProductRowId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[Qware AutoCreateStaging] Failed to create staging schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // 確保清理處理狀態
            if (isset($productId)) {
                unset(self::$processingProducts[$productId]);
            }
        }
    }

    /**
     * 檢查是否為 Qware 產品
     *
     * @param Product $product
     * @return bool
     */
    protected function isQwareProduct(Product $product): bool
    {
        $qwareGuid = $product->getData(QwareCommon::ATTRIBUTE_CODE_QWARE_GUID);
        return !empty($qwareGuid);
    }

    /**
     * 驗證排程日期
     *
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    protected function validateStagingDates(string $startDate, string $endDate): bool
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $now = new \DateTime();

            // 檢查開始日期是否在結束日期之前
            if ($start >= $end) {
                return false;
            }

            // 檢查日期是否在未來（可選驗證，取決於業務需求）
            // if ($start <= $now) {
            //     return false;
            // }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 創建產品排程
     *
     * @param Product $product
     * @param string $startTime
     * @param string $endTime
     * @return void
     */
    protected function createProductStaging(Product $product, string $startTime, string $endTime): void
    {
        try {
            // 解析時間並減去16小時
            $startDateTime = new \DateTime($startTime);
            $endDateTime = new \DateTime($endTime);
            
            // 時間減去16小時以調整時區
            $startDateTime->sub(new \DateInterval('PT16H'));
            $endDateTime->sub(new \DateInterval('PT16H'));
            
            $adjustedStartTime = $startDateTime->format('Y-m-d H:i:s');
            $adjustedEndTime = $endDateTime->format('Y-m-d H:i:s');

            // 創建排程更新
            $update = $this->updateFactory->create();
            $update->setName("Qware Auto Staging - Product #{$product->getId()}");
            $update->setDescription("Auto-generated staging schedule for Qware product");
            $update->setStartTime($adjustedStartTime);
            $update->setEndTime($adjustedEndTime);
            
        } catch (\Exception $e) {
            $this->logger->error('[Qware AutoCreateStaging] Failed to adjust time', [
                'product_id' => $product->getId(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // 保存排程
        $savedUpdate = $this->updateRepository->save($update);

        // 版本管理 - 確保恢復原始版本
        $originalVersionId = $this->versionManager->getCurrentVersion()->getId();
        
        try {
            // 設定當前版本
            $this->logger->debug('[Qware AutoCreateStaging] Setting version for staging', [
                'product_id' => $product->getId(),
                'update_id' => $savedUpdate->getId(),
                'original_version_id' => $originalVersionId
            ]);
            $this->versionManager->setCurrentVersionId($savedUpdate->getId());

            // 設定產品狀態
            $this->logger->debug('[Qware AutoCreateStaging] Setting product status to enabled', [
                'product_id' => $product->getId(),
                'status' => 'enabled'
            ]);
            $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            // 將產品添加到排程
            $this->logger->debug('[Qware AutoCreateStaging] Scheduling product', [
                'product_id' => $product->getId(),
                'update_id' => $savedUpdate->getId()
            ]);
            $this->productStaging->schedule($product, $savedUpdate->getId());
            
        } catch (\Exception $e) {
            $this->logger->error('[Qware AutoCreateStaging] Failed to schedule product', [
                'product_id' => $product->getId(),
                'update_id' => $savedUpdate->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // 恢復原始版本
            try {
                $this->versionManager->setCurrentVersionId($originalVersionId);
            } catch (\Exception $e) {
                $this->logger->error('[Qware AutoCreateStaging] Failed to restore version', [
                    'error' => $e->getMessage(),
                    'original_version_id' => $originalVersionId
                ]);
            }
        }
    }
}