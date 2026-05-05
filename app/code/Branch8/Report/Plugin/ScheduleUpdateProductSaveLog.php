<?php

declare(strict_types=1);

namespace Branch8\Report\Plugin;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Controller\Adminhtml\Product\Save;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class ScheduleUpdateProductSaveLog
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::ScheduleUpdateProductSaveLog';

    /**
     * @var array
     */
    private array $origProductData = [];

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
     * Hold original product data.
     *
     * @param Save $subject
     *
     * @return void
     */
    public function beforeExecute(Save $subject): void
    {
        $productId = $subject->getRequest()->getParam($subject::ENTITY_IDENTIFIER);
        try {
            $product = $this->productRepository->getById($productId);
            $productLinks = $this->dataHelper->prepareProductLinks($product);
            $this->origProductData = $product->getData();
            $this->origProductData += $productLinks;
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
            }
        }
    }

    /**
     * Log schedule update action on product.
     *
     * @param Save $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterExecute(Save $subject, $result)
    {
        if ($result instanceof Json) {
            try {
                $reflection = new \ReflectionClass($result);
                $property = $reflection->getProperty('json');
                $resultData = $property->getValue($result);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
                return $result;
            }

            $json = $this->dataHelper->getSerializer()->unserialize($resultData);

            if (isset($json['error']) && false === $json['error']) {
                $user = $this->dataHelper->getUpdatedByUser();
                $productId = $subject->getRequest()->getParam($subject::ENTITY_IDENTIFIER);

                $postData = ['stagingData' => $subject->getRequest()->getParam('staging')];
                $postData += $subject->getRequest()->getParam('product');

                $links = $subject->getRequest()->getParam('links');
                if (!empty($links)) {
                    $postData += $links;
                }
                $postData = $this->dataHelper->preparePostData($postData);

                try {
                    $product = $this->productRepository->getById($productId);
                    $productLinks = $this->dataHelper->prepareProductLinks($product);
                    $productData = $product->getData();
                    $productData += $productLinks;
                } catch (\Exception $e) {
                    $productData = [];
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                        $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                    }
                }

                list($beforeData, $afterData) = $this->dataHelper->getCommonOrderedSubset($this->origProductData, $productData);
                $beforeValues = $this->dataHelper->adjustProductData($beforeData);
                $afterValues = $this->dataHelper->adjustProductData($afterData);

                $productChangeLog = $this->productChangeLogFactory->create();
                $productChangeLog->setProductId((int)$productId)
                    ->setAction('scheduleUpdate')
                    ->setPostData($postData)
                    ->setAfterValues($afterValues)
                    ->setUserType(key($user));
                $userId = reset($user);
                if ($userId) {
                    $productChangeLog->setUserId($userId);
                }
                if ($this->origProductData) {
                    $productChangeLog->setBeforeValues($beforeValues);
                }
                try {
                    $this->productChangeLogRepository->save($productChangeLog);
                    $this->registry->unregister('productChangeLogAdded');
                    $this->registry->register('productChangeLogAdded', true);
                } catch (\Exception $e) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')) {
                        $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                    }
                }
            }
        }
        return $result;
    }
}
