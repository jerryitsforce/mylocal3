<?php

declare(strict_types=1);

namespace Branch8\Report\Plugin;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Psr\Log\LoggerInterface;

class LogStockStatusChange
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::LogStockStatusChange';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductChangeLogInterfaceFactory
     */
    private ProductChangeLogInterfaceFactory $productChangeLogFactory;

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param DataHelper $dataHelper
     * @param ProductRepositoryInterface $productRepository
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        Registry                            $registry,
        DataHelper                          $dataHelper,
        ProductRepositoryInterface          $productRepository,
        ProductChangeLogInterfaceFactory    $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->registry = $registry;
        $this->dataHelper = $dataHelper;
        $this->productRepository = $productRepository;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * Log stock status for product.
     *
     * @param DecrementSourceItemQty $subject
     * @param array $sourceItemDecrementData
     *
     * @return array
     */
    public function beforeExecute(DecrementSourceItemQty $subject, array $sourceItemDecrementData): array
    {
        if (!count($sourceItemDecrementData)
            || $this->registry->registry('productChangeLogAdded')
            || $this->dataHelper->getRequest()->getFullActionName() === 'catalog_product_save'
        ) {
            return [$sourceItemDecrementData];
        }

        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        /** @var SourceItem $sourceItem */
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            try {
                $product = $this->productRepository->get($sku);
                $oldStatus = $product->getQuantityAndStockStatus()['is_in_stock'] ?? null;
            } catch (\Exception $e) {
                $product = $oldStatus = null;
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
            }
            $newStatus = $sourceItem->getStatus();
            if ($product && $oldStatus != $newStatus) {
                $productData = $afterProductData = $product->getData();
                $afterProductData['quantity_and_stock_status']['is_in_stock'] = $newStatus;
                $afterProductData['quantity_and_stock_status']['qty'] = (int)$sourceItem->getQuantity();

                list($beforeData, $afterData) = $this->dataHelper->getCommonOrderedSubset($productData, $afterProductData);
                $beforeValues = $this->dataHelper->adjustProductData($beforeData);
                $afterValues = $this->dataHelper->adjustProductData($afterData);

                $productChangeLog = $this->productChangeLogFactory->create();
                $productChangeLog->setProductId((int)$product->getId())
                    ->setAction('stockStatusUpdate')
                    ->setBeforeValues($beforeValues)
                    ->setAfterValues($afterValues)
                    ->setUserType(UserType::TYPE_SYSTEM);
                try {
                    $this->productChangeLogRepository->save($productChangeLog);
                } catch (\Exception $e) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                        $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                    }
                }
            }
        }
        return [$sourceItemDecrementData];
    }
}
