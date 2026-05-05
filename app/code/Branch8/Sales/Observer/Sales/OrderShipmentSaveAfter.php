<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Sales\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Elgentos\InventoryLog\Api\MovementRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Branch8\Rma\Model\Rma\Status as RmaStatus;

class OrderShipmentSaveAfter implements ObserverInterface
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var InventoryLogHelper
     */
    public $inventoryLogHelper;

    /**
     * @var \Elgentos\InventoryLog\Api\MovementRepositoryInterface
     */
    private $movementRepository;

    /**
     * @var GetItemsToDeductFromShipment
     */
    private $getItemsToDeductFromShipment;

    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var Salable
     */
    protected $salable;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param MovementRepositoryInterface $movementRepository
     * @param GetItemsToDeductFromShipment $getItemsToDeductFromShipment
     * @param InventoryLogHelper $inventoryLogHelper
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        MovementRepositoryInterface $movementRepository,
        GetItemsToDeductFromShipment $getItemsToDeductFromShipment,
        InventoryLogHelper $inventoryLogHelper,
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        Salable $salable
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->inventoryLogHelper = $inventoryLogHelper;
        $this->movementRepository = $movementRepository;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->salable = $salable;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment->getOrigData('entity_id')) {
            return;
        }
        
        $order = $shipment->getOrder();
        $message = __('Product deducted by Shipment (order: %1)', $order->getIncrementId());
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $shipmentItem->setShipment($shipment);
            $orderItem = $shipmentItem->getOrderItem();
            if (null === $orderItem) {
                continue;
            }

            // Check and update rma_status: if 0 or 1, change 0 to 1
            $rmaStatus = $orderItem->getRmaStatus();
            if ($rmaStatus === (string)RmaStatus::RETURN_OR_EXCHANGE_NOT_AVALIABLE) {
                $orderItem->setRmaStatus(RmaStatus::RETURN_OR_EXCHANGE_AVALIABLE);
                $orderItem->save();
            }
            
            $message = __('Product deducted by Shipment (%1)', $this->salable->getMessageLog($orderItem,$order));
            
            if(!empty($orderItem->getData('spec_title'))){
                $productRowId = $orderItem->getProduct()->getRowId();
                $variation = $this->salable->getVariation($productRowId, $orderItem->getData('spec_title'));
                if ($variation->getId()) {
                    $qtyToShip = $variation->getReadyToShipQty() - $shipmentItem->getQty();
                    if ($qtyToShip < 0) {
                        $qtyToShip = 0;
                    }
                    $variation->setReadyToShipQty($qtyToShip);
                    $variation->save();

                    if ($variation->getIsSync()) {
                        $stockItemVariation = $this->stockRegistry->getStockItemBySku($variation->getSku());
                        $oldQty = (int)$stockItemVariation->getQty() + $shipmentItem->getQty();
                        $qty = (int)$stockItemVariation->getQty();
                        $this->saveStockMovementLog($stockItemVariation, $oldQty, $qty, $message);
                    }
                }
            }

            // Change new flow to write log for each item
            $stockItem = $this->stockRegistry->getStockItem($orderItem->getProduct()->getId());
            $qty = (int)$stockItem->getQty() - $shipmentItem->getQty();
            $this->saveStockMovementLog($stockItem, $stockItem->getQty(), $qty, $message);
            $this->salable->syncNeedToRefill([$orderItem->getProduct()->getId()]);
        }
    }

    private function saveStockMovementLog($stockItem, $oldQty, $newQty,  $message){
        if($this->inventoryLogHelper->isModuleEnabled()){
            $stockItem->setOldQty((int)$oldQty);
            $stockItem->setQty((int)$newQty);
            $this->movementRepository->insertStockMovement(
                $stockItem,
                $message
            );
            $this->inventoryLogHelper->unRegisterAllData();
        }
    }
}