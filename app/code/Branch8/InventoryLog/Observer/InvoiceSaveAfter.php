<?php
namespace Branch8\InventoryLog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Api\ProductRepositoryInterface;

class InvoiceSaveAfter implements ObserverInterface
{
    /**
     * @var InventoryLogHelper
     */
    public $helper;

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
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    public $productMetadata;

    /**
     * InvoiceSaveAfter constructor.
     * @param InventoryLogHelper $helper
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        InventoryLogHelper $helper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->helper = $helper;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->movementRepository = $movementRepository;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Insert inventory log for stock item when invoice is saved (Virtual Products)
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        if ($this->helper->isModuleEnabled()) {
            $invoice = $observer->getEvent()->getInvoice();
            
            $items = $invoice->getItems();
            if (!$items) {
                return;
            }

            foreach ($items as $item) {
                if (!$item->getProductId()) {
                    continue;
                }
                if ($item->getQty() <= 0) {
                    continue;
                }

                try {
                    $product = $this->productRepositoryInterface->getById($item->getProductId());
                    $productType = $product->getTypeId();

                    // ONLY process Virtual Products
                    if ($productType == ProductType::TYPE_VIRTUAL) {
                        $stockItem = $this->stockRegistryInterface->getStockItem($item->getProductId());
                        $qty = $item->getQty();
                        
                        // Real Stock Deduction happens here.
                        if ($this->productMetadata->getVersion() == '2.2.4') {
                             $oldQty = $stockItem->getQty();
                             $stockItem->setOldQty($oldQty);
                        } else {
                             $oldQty = $stockItem->getQty() + $qty;
                             $stockItem->setOldQty($oldQty);
                        }

                        $msg = __('Product invoiced (invoice: %1)', $invoice->getIncrementId());
                        
                        $this->movementRepository->insertStockMovement(
                            $stockItem,
                            $msg
                        );
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
}
