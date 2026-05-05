<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class CommissionPercent implements ObserverInterface{
    /**
     * @var \Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo
     */
    protected $getQuoteItemCombo;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConn;

    protected $webkulHelper;

    protected $quoteRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param \Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo $getQuoteItemCombo
     * @param \Magento\Framework\App\ResourceConnection $resourceConn
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $webkulHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        \Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo $getQuoteItemCombo,
        \Magento\Framework\App\ResourceConnection $resourceConn,
        \Webkul\OptionsWithStockAndImages\Helper\Data  $webkulHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ){
        $this->getQuoteItemCombo = $getQuoteItemCombo;
        $this->resourceConn  = $resourceConn;
        $this->webkulHelper = $webkulHelper;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        if($order->getData('is_flagship_store_process_order')){
            return;
        }
        $orderItems = $order->getItemsCollection();
        $connection = $this->resourceConn->getConnection();
        $quote = $observer->getEvent()->getData('subQuote');
        foreach ($orderItems as $orderItem) {
            $productOptions = $orderItem->getProductOptions();
            $quoteItemId = $orderItem->getQuoteItemId();
            $quoteItem = $quote->getItemById($quoteItemId);
            $product = $this->productCollectionFactory->create()
                ->addAttributeToSelect('commission_percent')
                ->addFieldToFilter('entity_id', $quoteItem->getProductId())
                ->getFirstItem();
            $productCommission = (float)$product->getCommissionPercent();
            if(isset($productOptions['options'])){
                $comb = $this->getQuoteItemCombo->get($quoteItem);
                $productRowId = $product->getRowId();
                $variation = $this->webkulHelper->getCombData($productRowId, $comb);
                if($variation->getId()){
                    $productCommission = (float)$variation->getCommissionPercent();
                }
            }
            
            $sql = 'update sales_order_item set commission_percent = '.$productCommission.' where item_id='.$orderItem->getId();
            $connection->query($sql);
        }
    }
    

}