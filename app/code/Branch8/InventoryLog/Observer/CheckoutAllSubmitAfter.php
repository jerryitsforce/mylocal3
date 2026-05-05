<?php
namespace Branch8\InventoryLog\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Magento\Catalog\Model\Product\Type as ProductType;

class CheckoutAllSubmitAfter implements ObserverInterface
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
     * CheckoutAllSubmitAfter constructor.
     * @param InventoryLogHelper $helper
     * @param \Magento\Framework\Registry $registry
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Elgentos\InventoryLog\Model\MovementFactory $movementFactory
     * @param \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository
     */
    public function __construct(
        InventoryLogHelper $helper,
        \Magento\Framework\Registry $registry,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Elgentos\InventoryLog\Model\MovementFactory $movementFactory,
        \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->movementFactory = $movementFactory;
        $this->movementRepository = $movementRepository;
        $this->productRepositoryInterface = $productRepositoryInterface;
    }

    /**
     * Insert inventory log for stock item
     * @param EventObserver $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
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
                        $productId = $orderItem->getProductId();
                        try {
                            $product = $this->productRepositoryInterface->getById($productId);
                            $productType = $product->getTypeId();
                            // MODIFICATION: Check for Virtual product type
                            if ($orderItem->getQtyOrdered() && $productType == ProductType::TYPE_VIRTUAL) {
                                $stockItem = $this->stockRegistryInterface->getStockItem($orderItem->getProductId());
                                
                                // CLONE stock item to ensure safety. We modify data on the clone only.
                                $fakeStockItem = clone $stockItem;

                                $realQty = $fakeStockItem->getQty();
                                $qtyOrdered = $orderItem->getQtyOrdered();
                                
                                // Set "Fake" Old Qty (The qty before this order)
                                $fakeStockItem->setOldQty($realQty);
                                
                                // Set "Fake" New Qty (The qty after this order)
                                $fakeStockItem->setQty($realQty - $qtyOrdered);

                                if (!isset($stockItems[$stockItem->getId()])) {
                                    $stockItems[$stockItem->getId()] = [
                                        'item' => $fakeStockItem,
                                        'orders' => [$order->getIncrementId()],
                                    ];
                                } else {
                                    $stockItems[$stockItem->getId()]['orders'][] = $order->getIncrementId();
                                    // Aggregate qty deduction if multiple lines/orders for same item
                                    $currentFakeQty = $stockItems[$stockItem->getId()]['item']->getQty();
                                    $stockItems[$stockItem->getId()]['item']->setQty($currentFakeQty - $qtyOrdered);
                                }
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    if (!empty($stockItems)) {
                        foreach ($stockItems as $data) {
                            $this->movementRepository->insertStockMovement(
                                $data['item'],
                                __('Product ordered (order%1: %2)', count($data['orders']) > 1 ? 's' : '', implode(', ', $data['orders']))
                            );
                        }
                    }
                }
            }
        }
    }
}
