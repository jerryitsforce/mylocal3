<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Sales\Plugin\Elgentos\InventoryLog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Magento\Framework\Exception\NoSuchEntityException;

class RefundOrderInventoryObserver
{
    /**
     * @var StockConfigurationInterface
     */
    public $stockConfiguration;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    public $stockRegistryInterface;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepositoryInterface;

    /**
     * @var \Elgentos\InventoryLog\Api\MovementRepositoryInterface
     */
    public $movementRepository;

    /**
     * @var InventoryLogHelper
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    public $productMetadata;

    /**
     * @var Salable
     */
    protected $salable;

    /**
     * RefundOrderInventoryObserver constructor.
     * @param StockConfigurationInterface $stockConfiguration
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository
     * @param InventoryLogHelper $helper
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        InventoryLogHelper $helper,
        Salable $salable
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->movementRepository = $movementRepository;
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->salable = $salable;
    }

    public function aroundExecute(
        \Elgentos\InventoryLog\Observer\RefundOrderInventoryObserver $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($this->helper->isModuleEnabled()) {
            /* @var $creditmemo \Magento\Sales\Model\Order\Creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $itemsToUpdate = [];
            foreach ($creditmemo->getAllItems() as $item) {
                $productId = $item->getProductId();
                try {
                    $product = $this->productRepositoryInterface->getById($productId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                $productType = $product->getTypeId();
                $orderItem = $item->getOrderItem();
                $qty = $item->getQty();

                if (($item->getBackToStock() && $qty)) {
                    if ($qty && $productType == ProductType::TYPE_SIMPLE) {
                        $stockItem = $this->stockRegistryInterface->getStockItem($item->getProductId());
                        if ($this->productMetadata->getVersion() == '2.2.4') {
                            $oldQty = $stockItem->getQty();
                            $stockItem->setOldQty($oldQty);
                        } else {
                            if((int)$orderItem->getQtyShipped() > 0){
                                // Only add log restock qty when the order has been shipped 
                                $oldQty = $stockItem->getQty();
                                $newQty = $stockItem->getQty() + $qty;
                            } else {
                                $oldQty = $stockItem->getQty() - $qty;
                                $newQty = $stockItem->getQty();
                            }
                            $stockItem->setOldQty($oldQty);
                            $stockItem->setQty($newQty);
                        }
                        $message =  __('Product restocked after credit memo creation (%1)', $this->salable->getMessageLog($orderItem,$creditmemo->getOrder()));
                        $this->movementRepository->insertStockMovement($stockItem, $message, 0, $qty);
                        $this->helper->unRegisterAllData();
                    }
                }
            }
        }
    }
}
