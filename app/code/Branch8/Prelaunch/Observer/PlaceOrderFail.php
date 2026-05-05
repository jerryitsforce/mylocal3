<?php

namespace Branch8\Prelaunch\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlaceOrderFail implements ObserverInterface
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    protected $quoteItemCollection;
    /**
     * @var \Branch8\Prelaunch\Helper\Data
     */
    protected $prelaunchHelperData;
    /**
     * @var \Branch8\Prelaunch\Helper\Api
     */
    protected $prelaunchHelperApi;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection,
        \Branch8\Prelaunch\Helper\Data $prelaunchHelperData,
        \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
    ){
        $this->quoteItemCollection = $quoteItemCollection;
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->prelaunchHelperApi = $prelaunchHelperApi;
    }

    /**
     * @param $observer
     * @return void
     */
    public function execute($observer)
    {
//        if(!$this->prelaunchHelperData->isActive()){
//            return;
//        }
//        $event = $observer->getEvent();
//        $masterQuote = $event->getData('master_quote');
//        $parentOrder = $event->getData('parent_order');
//
//        $cols = $this->quoteItemCollection->create()
//            ->addFieldToSelect('item_id')
//            ->addFieldToSelect('sku')
//            ->addFieldToSelect('qty')
//            ->addFieldToFilter('quote_id', $masterQuote->getId());
//
//        $productList = [];
//        $skuList = [];
//        foreach($cols as $_item){
//            if(!$this->prelaunchHelperData->validateLimitSku($_item->getSku())){
//                continue;
//            }
//            $itemId = $this->prelaunchHelperData->parseItemId($_item->getSku());
//            $productList[] = [
//                'qty' => (int)$_item->getQty(),
//                'productItemId' => $itemId
//            ];
//            $skuList[$itemId] = $_item->getSku();
//        }
//
//        if(!count($productList)){
//            return;
//        }
//
//        $requestData = [
//            'orderId' => $parentOrder->getData('hotai_reserved_order_id'),
//            'orderNo' => $parentOrder->getData('hotai_reserved_order_id'),
//            'syncProductList' => $productList,
//            'isCancel' => true
//        ];
//        $response = $this->prelaunchHelperApi->stockAPI($requestData);
//        if($response['success'] != 1){
//            return;
//        }
//        try{
//            $responseParsed = json_decode($response['response'], true);
//            if(!$responseParsed['isSuccess']){
//                return;
//            }
//            $dataList = $responseParsed['dataList'];
//            foreach($dataList as $_item){
//                if(!$_item['isSuccess']){
//                    continue;
//                }
//                $remainQty = $_item['remainingQty'];
//                $sku = $skuList[$_item['productItemId']];
//                $this->prelaunchHelperData->setProductQty($sku, $remainQty);
//            }
//        }catch (\Exception $e) {
//
//        }
    }
}