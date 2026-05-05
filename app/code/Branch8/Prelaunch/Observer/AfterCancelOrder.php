<?php

namespace Branch8\Prelaunch\Observer;

use Magento\Framework\Event\ObserverInterface;

class AfterCancelOrder implements ObserverInterface
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
     * @var Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     * @param Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection,
        \Branch8\Prelaunch\Helper\Data $prelaunchHelperData,
        \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->quoteItemCollection = $quoteItemCollection;
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->prelaunchHelperApi = $prelaunchHelperApi;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $observer
     * @return void
     */
    public function execute($observer){

        if(!$this->prelaunchHelperData->isActive()){
            return;
        }

        $order = $observer->getEvent()->getOrder();
        $orderItems = $order->getItemsCollection([], true);
        $productList = [];
        $skuList = [];
        foreach ($orderItems as $_item){
            if(!$this->prelaunchHelperData->validateLimitSku($_item->getSku())){
                continue;
            }

            $product = $_item->getProduct();
            $productExtAttribute = $product->getExtensionAttributes();
            $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
            if(!$isManageStock){
                continue;
            }

            $itemId = $this->prelaunchHelperData->parseItemId($_item->getSku());
            $itemQty = (int)$_item->getQtyCanceled() ? (int)$_item->getQtyCanceled() : (int)$_item->getQtyOrdered();
            $productList[] = [
                'qty' => $itemQty,
                'productItemId' => $itemId
            ];
            $skuList[$itemId] = ['sku' => $_item->getSku(), 'qty' => $itemQty];
        }

        if(!count($productList)){
            return;
        }

        //get parent order
//        $conn = $this->resourceConnection->getConnection();
//        $selectParent = $conn->select()
//            ->from(['prc' => 'sales_parent_order_children'], [])
//            ->joinleft(['pro' => 'sales_parent_order_detail'], 'prc.parent_id=pro.parent_id', ['increment_id'])
//            ->where('prc.children_id='.$order->getId());
//        $parentOrderId = $conn->fetchOne($selectParent);
        $requestData = [
            'orderId' => $order->getData('increment_id'),
            'orderNo' => $order->getData('increment_id'),
            'syncProductList' => $productList,
            'isCancel' => true
        ];
        $response = $this->prelaunchHelperApi->stockAPI($requestData);
        if($response['success'] != 1){
            return;
        }
        try{
            $responseParsed = json_decode($response['response'], true);
            if(!$responseParsed || !$responseParsed['isSuccess']){
                return;
            }
            $dataList = $responseParsed['dataList'];
            foreach($dataList as $_item){
                if(!$_item['isSuccess']){
                    continue;
                }
                $remainQty = $_item['remainingQty'];
                $sku = $skuList[$_item['productItemId']]['sku'];
                $this->prelaunchHelperData->setProductQty($sku, $remainQty, /*$skuList[$_item['productItemId']]['qty']*/);
            }
        }catch (\Exception $e) {

        }
    }
}