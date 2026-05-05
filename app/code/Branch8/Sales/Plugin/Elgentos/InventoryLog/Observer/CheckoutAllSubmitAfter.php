<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Sales\Plugin\Elgentos\InventoryLog\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Magento\Catalog\Model\Product\Type as ProductType;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

class CheckoutAllSubmitAfter
{

    /**
     * @var InventoryLogHelper
     */
    public $helper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    
    /**
     * @var \Elgentos\InventoryLog\Model\MovementFactory
     */
    private $movementFactory;
    
    /**
     * @var \Elgentos\InventoryLog\Api\MovementRepositoryInterface
     */
    private $movementRepository;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistryInterface;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepositoryInterface;

    /**
     * @var Salable
     */
    protected $salable;

    /**
     * CheckoutAllSubmitAfter constructor.
     * @param InventoryLogHelper $helper
     * @param \Magento\Framework\Registry $registry
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Elgentos\InventoryLog\Model\MovementFactory|null $movementFactory
     * @param \Elgentos\InventoryLog\Api\MovementRepositoryInterface|null $movementRepository
     */
    public function __construct(
        InventoryLogHelper $helper,
        \Magento\Framework\Registry $registry,
        ProductRepositoryInterface $productRepositoryInterface,
        Salable $salable,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Elgentos\InventoryLog\Model\MovementFactory $movementFactory = null,
        \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository = null
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->movementFactory = $movementFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Elgentos\InventoryLog\Model\MovementFactory::class);
        $this->movementRepository = $movementRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Elgentos\InventoryLog\Api\MovementRepositoryInterface::class);

        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->salable = $salable;
    }

    public function aroundExecute(
        \Elgentos\InventoryLog\Observer\CheckoutAllSubmitAfter $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($this->helper->isModuleEnabled()) {
            if ($observer->getEvent()->hasOrders()) {
                $orders = $observer->getEvent()->getOrders();
            } else {
                $orders = [$observer->getEvent()->getOrder()];
            }
            $stockItems = [];
            foreach ($orders as $order) {
                if ($order) {
                    foreach ($order->getAllItems() as $orderItem) {
                        /** @var Mage_Sales_Model_Order_Item $orderItem */
                        $productId = $orderItem->getProductId();
                        $product = $this->productRepositoryInterface->getById($productId);
                        $productType = $product->getTypeId();
                        if ($orderItem->getQtyOrdered() && $productType == ProductType::TYPE_SIMPLE) {
                            $stockItem = $this->stockRegistryInterface->getStockItem($orderItem->getProductId());
                            $oldQty = $stockItem->getQty() + $orderItem->getQtyOrdered();
                            $stockItem->setOldQty($oldQty);
                            $message =  __('Product ordered (%1)', $this->salable->getMessageLog($orderItem,$order));
                            if (!isset($stockItems[$stockItem->getId()])) {
                                $stockItems[] = [
                                    'item' => $stockItem,
                                    'orders' => [$order->getIncrementId()],
                                    'msg' => $message
                                ];
                            }
                        }
                    }

                    if (!empty($stockItems)) {
                        foreach ($stockItems as $data) {
                            $this->movementRepository->insertStockMovement(
                                $data['item'],
                                $data['msg']
                            );
                        }
                        $this->helper->unRegisterAllData();
                    }
                }
            }
        }
    }
}
