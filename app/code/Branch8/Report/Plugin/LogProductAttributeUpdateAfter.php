<?php

declare(strict_types=1);

namespace Branch8\Report\Plugin;

use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Branch8\Report\Model\ProductLogContext;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Psr\Log\LoggerInterface;

class LogProductAttributeUpdateAfter
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::LogProductAttributeUpdateAfter';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var ProductLogContext
     */
    private ProductLogContext $productLogContext;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param DataHelper $dataHelper
     * @param ProductLogContext $productLogContext
     * @param ProductRepositoryInterface $productRepository
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        DataHelper                          $dataHelper,
        ProductLogContext                   $productLogContext,
        ProductRepositoryInterface          $productRepository,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->productLogContext = $productLogContext;
        $this->productRepository = $productRepository;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * Reindex on product attribute mass change.
     *
     * @param ProductAction $subject
     * @param ProductAction $action
     * @param array $productIds
     * @param array $attrData
     * @param int $storeId
     *
     * @return ProductAction
     */
    public function afterUpdateAttributes(ProductAction $subject, ProductAction $action, $productIds, $attrData, $storeId): ProductAction
    {
        foreach ($productIds as $productId) {
            try {
                $log = $this->productLogContext->get((int)$productId);
                if ($log && empty($log->getAfterValues())) {
                    $product = $this->productRepository->getById($productId, false, null, true);
                    foreach ($attrData as $attributeCode => $value) {
                        $product->setData($attributeCode, $value);
                    }
                    $productAfterData = $product->getData();
                    $links = $product->getProductLinks();
                    if (empty($links)) {
                        $productLinks = ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []];
                    } else {
                        $productLinks = $this->dataHelper->prepareProductLinks($product, $links);
                    }
                    $productAfterData += $productLinks;
                    $afterValues = $this->dataHelper->adjustProductData($productAfterData);
                    $log->setAfterValues($afterValues);
                    $this->productChangeLogRepository->save($log);
                }
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
                continue;
            }
        }
        $this->productLogContext->clear();

        return $action;
    }
}
