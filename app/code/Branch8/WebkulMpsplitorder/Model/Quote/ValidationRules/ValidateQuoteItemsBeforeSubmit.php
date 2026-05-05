<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\Quote\ValidationRules;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\CheckItemsQuantity;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;

/**
 * @property IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
 * @inheritdoc
 */
class ValidateQuoteItemsBeforeSubmit implements QuoteValidationRuleInterface
{
    /**
     * @var string
     */
    private $generalMessage;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;
    private \Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsAnySourceItemInStockCondition $anySourceItemInStockCondition;
    private GetSkusByProductIdsInterface $getSkusByProductIds;
    private GetProductTypesBySkusInterface $getProductTypesBySkus;
    private IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType;
    private ItemToSellInterfaceFactory $itemToSellFactory;
    private CheckItemsQuantity $checkItemsQuantity;
    private StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param \Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsAnySourceItemInStockCondition $anySourceItemInStockCondition
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param CheckItemsQuantity $checkItemsQuantity
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param string $generalMessage
     */
    public function __construct(
        ValidationResultFactory                                                                                $validationResultFactory,
        \Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsAnySourceItemInStockCondition $anySourceItemInStockCondition,
        GetSkusByProductIdsInterface                                                                           $getSkusByProductIds,
        GetProductTypesBySkusInterface                                                                         $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface                                                   $isSourceItemManagementAllowedForProductType,
        ItemToSellInterfaceFactory                                                                             $itemsToSellFactory,
        CheckItemsQuantity                                                                                     $checkItemsQuantity,
        StockByWebsiteIdResolverInterface                                                                      $stockByWebsiteIdResolver,
        string                                                                                                 $generalMessage = ''
    )
    {
        $this->validationResultFactory = $validationResultFactory;
        $this->generalMessage = $generalMessage;
        $this->anySourceItemInStockCondition = $anySourceItemInStockCondition;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->itemToSellFactory = $itemsToSellFactory;
        $this->checkItemsQuantity = $checkItemsQuantity;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
    }

    /**
     * @inheritdoc
     */
    public function validate(Quote $quote): array
    {
        if (!$quote->getIsMaster()) {
            return [$this->validationResultFactory->create(['errors' => []])];
        }
        $validationErrors = [];
        $itemsById = $itemsBySku = [];
        foreach ($quote->getAllItems() as $item) {
            if ($item->getChildren()) {
                continue;
            }
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $itemsById[$item->getProductId()] += $item->getQty();
        }
        $productSkus = $this->getSkusByProductIds->execute(array_keys($itemsById));
        $productTypes = $this->getProductTypesBySkus->execute($productSkus);

        foreach ($productSkus as $productId => $sku) {
            if (false === $this->isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                continue;
            }
            $itemsBySku[$sku] = (float)$itemsById[$productId];
        }
        $websiteId = (int)$quote->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
        try {
            $this->checkItemsQuantity->execute($itemsBySku, $stockId);
        } catch (LocalizedException $exception) {
            $validationErrors = [__('Something went wrong,please check and remove invalid item %1', $exception->getMessage())];
        }
        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }
}
