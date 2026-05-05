<?php

namespace Branch8\OptionsWithStockAndImages\Observer;

class VariationSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /**
     * Constructor
     *
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->salable = $salable;
        $this->logger = $logger;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $data = $observer->getEvent()->getData();
            $variation = $data['data_object'];
            if(($variation->getData('sku') != '') 
                && ($variation->getData('stock') != '')
                && ($variation->getData('is_sync') != '')
                && (int)$variation->getData('is_sync') == 1
            ){
                $qty = $this->salable->getQtyBySku($variation->getData('sku'));
                $variation->setData('stock', $qty);
            }
            
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
