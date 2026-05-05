<?php
namespace Branch8\InventoryLog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Magento\Framework\Exception\NoSuchEntityException;

class RefundOrderInventoryObserver implements ObserverInterface
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
     * RefundOrderInventoryObserver constructor.
     * @param StockConfigurationInterface $stockConfiguration
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param InventoryLogHelper $helper
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Elgentos\InventoryLog\Api\MovementRepositoryInterface $movementRepository,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        InventoryLogHelper $helper
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->movementRepository = $movementRepository;
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->isModuleEnabled()) {
            /* @var $creditmemo \Magento\Sales\Model\Order\Creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();
            foreach ($creditmemo->getAllItems() as $item) {
                $productId = $item->getProductId();
                try {
                    $product = $this->productRepositoryInterface->getById($productId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                $productType = $product->getTypeId();
                $qty = $item->getQty();

                if (($item->getBackToStock() && $qty)) {
                    // MODIFICATION: Check for Virtual product type
                    if ($qty && $productType == ProductType::TYPE_VIRTUAL) {
                        $stockItem = $this->stockRegistryInterface->getStockItem($item->getProductId());
                        
                        // For Refund, the Stock is actually increased in DB.
                        // So we use standard logic (Real DB values).
                        if ($this->productMetadata->getVersion() == '2.2.4') {
                            $oldQty = $stockItem->getQty();
                            $stockItem->setOldQty($oldQty);
                        } else {
                            $oldQty = $stockItem->getQty() - $qty;
                            $stockItem->setOldQty($oldQty);
                        }

                        $msg = __('Product restocked after credit memo creation (credit memo: %s)');
                        $message = sprintf(
                            $msg,
                            $creditmemo->getId()
                        );
                        $this->movementRepository->insertStockMovement($stockItem, $message, 0, $qty);
                    }
                }
            }
        }
    }
}
