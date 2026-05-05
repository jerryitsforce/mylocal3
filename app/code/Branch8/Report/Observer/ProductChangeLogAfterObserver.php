<?php

declare(strict_types=1);

namespace Branch8\Report\Observer;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as DataHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class ProductChangeLogAfterObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Report::ProductChangeLogObserver';

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
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        Registry                            $registry,
        DataHelper                          $dataHelper,
        ProductChangeLogInterfaceFactory    $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->logger = $logger;
        $this->registry = $registry;
        $this->dataHelper = $dataHelper;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if ($this->registry->registry('productChangeLogAdded')) {
            return;
        }
        $requestModuleName = $this->dataHelper->getRequest()->getModuleName();
        if (in_array($requestModuleName, ['catalogstaging', 'marketplacestaging'])) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getData('product');
        if ($product->getOrigData('entity_id')) {
            $productId = (int)$product->getId();

            $action = $this->getAction();
            $user = $this->dataHelper->getUpdatedByUser();
            $productPostData = (array)$this->dataHelper->getRequest()->getParam('product');
            if (!empty($productPostData)) {
                $links = $this->dataHelper->getRequest()->getParam('links');
                if (!empty($links)) {
                    $productPostData += $links;
                }
                $productPostData = $this->dataHelper->preparePostData($productPostData);
            } else {
                $productPostData = $this->dataHelper->getRequest()->getContent();
            }

            $productBeforeData = $product->getOrigData();
            $oldProductLink = ProductChangeLogBeforeObserver::getData($productId, ProductChangeLogBeforeObserver::PRODUCT_LINKS);
            $productBeforeData += $oldProductLink;

            $productAfterData = $product->getData();
            $links = $product->getProductLinks();
            if (empty($links)) {
                $productLinks = ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []];
            } else {
                $productLinks = $this->dataHelper->prepareProductLinks($product, $links);
            }
            $productAfterData += $productLinks;
            $imageIds = $product->getMediaGalleryImages()->getAllIds();
            if (isset($productAfterData['media_gallery']['images'])) {
                $productAfterData['media_gallery']['images'] = array_filter(
                    $productAfterData['media_gallery']['images'],
                    fn($image) => in_array($image['value_id'] ?? null, $imageIds)
                );
            }
            if (isset($productAfterData['quantity_and_stock_status']) && !is_array($productAfterData['quantity_and_stock_status'])) {
                $stockItem = $product->getExtensionAttributes()->getStockItem();
                if ($stockItem) {
                    $productAfterData['quantity_and_stock_status'] = [
                        'is_in_stock' => $stockItem->getIsInStock(),
                        'qty' => $stockItem->getQty()
                    ];
                }
            }

            list($beforeData, $afterData) = $this->dataHelper->getCommonOrderedSubset($productBeforeData, $productAfterData);
            $optionVariations = ProductChangeLogBeforeObserver::getData($productId, ProductChangeLogBeforeObserver::OPTION_VARIATIONS);
            if (!empty($optionVariations)) {
                $beforeData['option_variations'] = $optionVariations;
                ProductChangeLogBeforeObserver::clearData($productId, ProductChangeLogBeforeObserver::OPTION_VARIATIONS);
            }
            $beforeValues = $this->dataHelper->adjustProductData($beforeData);
            $afterValues = $this->dataHelper->adjustProductData($afterData);

            $productChangeLog = $this->productChangeLogFactory->create();
            $productChangeLog->setProductId($productId)
                ->setAction($action)
                ->setPostData((string)$productPostData)
                ->setBeforeValues($beforeValues)
                ->setAfterValues($afterValues)
                ->setUserType(key($user));
            $userId = reset($user);
            if ($userId) {
                $productChangeLog->setUserId($userId);
            }

            try {
                $this->productChangeLogRepository->save($productChangeLog);
                $this->registry->unregister('productChangeLogAdded');
                $this->registry->register('productChangeLogAdded', true);
                ProductChangeLogBeforeObserver::clearData($productId, ProductChangeLogBeforeObserver::PRODUCT_LINKS);
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

    /**
     * Returns action.
     *
     * @return string
     */
    private function getAction(): string
    {
        $request = $this->dataHelper->getRequest();
        if ($request->getFrontName() === 'rest' || $request->getFrontName() === 'soap') {
            $path = $request->getPathInfo();
            $method = $request->getMethod();
            $path = $this->normalizePath(urldecode($path));
            return "API: {$method} {$path}";
        }
        $requestAction = $request->getActionName();
        if (in_array($requestAction, ['approve', 'massApprove'])) {
            return 'approve';
        }
        return 'save';
    }

    /**
     * Normalize a REST API path.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $segments = explode('/', trim(urldecode($path), '/'));
        $normalized = [];

        $isRest = false;
        $started = false;
        foreach ($segments as $index => $segment) {
            if ($segment === 'rest') {
                $isRest = true;
            }

            if (!$isRest) {
                $normalized[] = $segment;
                continue;
            }

            if ($segment === 'V1' || $segment === 'V2') {
                $normalized[] = $segment;
                $started = true;
                continue;
            }

            if (!$started) {
                $normalized[] = $segment;
                continue;
            }

            $positionAfterV1 = $index - array_search('V1', $segments) - 1;
            if ($positionAfterV1 % 2 === 0) {
                $normalized[] = $segment;
            } else {
                $normalized[] = ':param';
            }
        }

        return '/' . implode('/', $normalized);
    }
}
