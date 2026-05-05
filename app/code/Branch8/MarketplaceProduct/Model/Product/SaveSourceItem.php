<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Branch8\Report\Helper\Data as ReportHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Framework\App\Request\Http as HttpRequest;

class SaveSourceItem
{
    /**
     * @var SourceItemsProcessorInterface
     */
    private SourceItemsProcessorInterface $sourceItemsProcessor;

    /**
     * @var DefaultSourceProviderInterface
     */
    private DefaultSourceProviderInterface $defaultSourceProvider;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private SourceItemRepositoryInterface $sourceItemRepository;

    /**
     * @var Salable
     */
    private Salable $salable;

    /**
     * @var ReportHelper
     */
    private ReportHelper $reportHelper;

    /**
     * @var HttpRequest
     */
    private HttpRequest $httpRequest;
    
    /**
     * SaveSourceItem constructor.
     *
     * @param SourceItemsProcessorInterface $sourceItemsProcessor
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param HttpRequest $httpRequest
     */
    public function __construct(
        SourceItemsProcessorInterface  $sourceItemsProcessor,
        DefaultSourceProviderInterface $defaultSourceProvider,
        SearchCriteriaBuilderFactory   $searchCriteriaBuilderFactory,
        Salable                        $salable,
        ReportHelper                   $reportHelper,
        SourceItemRepositoryInterface  $sourceItemRepository,
        HttpRequest                    $httpRequest
    ) {
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->salable = $salable;
        $this->reportHelper = $reportHelper;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Apply changed data to product.
     *
     * @param ProductInterface $product
     * @param array $singleSourceData
     *
     * @return void
     *
     * @throws InputException
     */
    public function execute(ProductInterface $product, array $singleSourceData): void
    {
        $sku = $product->getSku();
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $qty = $singleSourceData['qty'] ?? (empty($stockItem) ? 0 : $stockItem->getQty());
        $isInStock = $singleSourceData['is_in_stock'] ?? (empty($stockItem) ? 1 : (int)$stockItem->getIsInStock());
        $defaultSourceData = [
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceItemInterface::QUANTITY => $qty,
            SourceItemInterface::STATUS => $isInStock,
        ];
        $sourceItems = $this->getSourceItemsWithoutDefault($sku);
        $sourceItems[] = $defaultSourceData;
        $this->sourceItemsProcessor->execute($sku, $sourceItems);

        // Save inventory log
        $user = $this->reportHelper->getUpdatedByUser();
        $userId = reset($user);
        $mess = '';
        if (empty($userId)) {
            $mess = __('Stock updated by system');
        } else {
            $type = key($user);
            $action = $this->httpRequest->getFullActionName();
            if ($type === UserType::TYPE_ADMIN) {
                $mess = __('Stock updated via admin mass upload');
            } elseif ($type === UserType::TYPE_SELLER) {
                if($action == 'mpmassupload_product_run'){
                    $mess = __('Stock updated via seller mass upload');
                } else {
                    $mess = __('Stock updated manually on seller');
                }
            }
        }
        
        $stockItem->setIsInStock($isInStock);
        $this->salable->saveStockMovementLog($stockItem, $stockItem->getQty(), $qty, $mess);

        $product->setQuantityAndStockStatus($singleSourceData);
    }

    /**
     * Get Source Items Data without Default Source by SKU.
     *
     * @param string $sku
     *
     * @return array
     */
    private function getSourceItemsWithoutDefault(string $sku): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode(), 'neq')
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $sourceItemData = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemData[] = [
                    SourceItemInterface::SKU => $sourceItem->getSku(),
                    SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                    SourceItemInterface::STATUS => $sourceItem->getStatus(),
                ];
            }
        }
        return $sourceItemData;
    }
}
