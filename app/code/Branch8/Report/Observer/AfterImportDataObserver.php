<?php

declare(strict_types=1);

namespace Branch8\Report\Observer;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class AfterImportDataObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::AfterImportDataObserver';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

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
     * @param DataHelper $dataHelper
     * @param ProductFactory $productFactory
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        DataHelper                          $dataHelper,
        ProductFactory                      $productFactory,
        ProductChangeLogInterfaceFactory    $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->productFactory = $productFactory;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $bunch = $observer->getEvent()->getBunch();
        $adapter = $observer->getEvent()->getAdapter();
        if (!$bunch) {
            return;
        }

        foreach ($bunch as $rowData) {
            $productId = $adapter->getNewSku($rowData[ImportProduct::COL_SKU])['entity_id'];

            $user = $this->dataHelper->getUpdatedByUser();
            $serializer = $this->dataHelper->getSerializer();
            $postData = $serializer->serialize($rowData);

            $product = $this->productFactory->create()->load($productId);
            $updateData = $this->dataHelper->adjustProductData($product->getData());

            $productChangeLog = $this->productChangeLogFactory->create();
            $productChangeLog->setProductId((int)$productId)
                ->setAction('import')
                ->setPostData($postData)
                ->setAfterValues($updateData)
                ->setUserType(key($user));
            $userId = reset($user);
            if ($userId) {
                $productChangeLog->setUserId($userId);
            }

            try {
                $this->productChangeLogRepository->save($productChangeLog);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, [
                        'product_id' => $productId,
                        'exception' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}
