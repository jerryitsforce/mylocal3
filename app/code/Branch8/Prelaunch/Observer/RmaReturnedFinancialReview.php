<?php

namespace Branch8\Prelaunch\Observer;

use Branch8\Prelaunch\Magento;
use Magento\Framework\Event\ObserverInterface;

class RmaReturnedFinancialReview implements ObserverInterface
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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection,
        \Branch8\Prelaunch\Helper\Data $prelaunchHelperData,
        \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->quoteItemCollection = $quoteItemCollection;
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->prelaunchHelperApi = $prelaunchHelperApi;
        $this->resourceConnection = $resourceConnection;
        $this->productFactory = $productFactory;
    }

    public function execute($observer){

        if(!$this->prelaunchHelperData->isActive()){
            return;
        }
        $creditMemo = $observer->getEvent()->getData('creditmemo');
        $rmaDetail =  $observer->getEvent()->getData('rmaDetail');
        if(!$rmaDetail->getReturnToStock()){
            return;
        }
        $creditMemeItems = $creditMemo->getItems();

        $productList = [];
        $skuList = [];
        $orderId = $creditMemo->getData('order_id');
        foreach($creditMemeItems as $_crItem){
            /**
             * back_to_stock is set to every item
             * One item 1 mean all items are 1
             * One item 0 mean all items are 0
             */
            /**
             * Triple company invoice refund does not pass this condition, it will go to app/code/Branch8/Rma/Model/Actions/ApproveFinance.php: 187
             */
            $sku = $_crItem->getSku();
            if(!$this->prelaunchHelperData->validateLimitSku($sku)){
                continue;
            }
            $productId = $_crItem->getProductId();

            $product = $this->productFactory->create()->load($productId);
            $productExtAttribute = $product->getExtensionAttributes();
            $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
            if(!$isManageStock){
                continue;
            }

            $itemId = $this->prelaunchHelperData->parseItemId($sku);
            $productList[] = [
                'qty' => (int)$_crItem->getQty(),
                'productItemId' => $itemId
            ];
            $skuList[$itemId] = ['sku' => $_crItem->getSku(), 'qty' => (int)$_crItem->getQty()];
        }

        if(!count($productList)){
            return;
        }

        $conn = $this->resourceConnection->getConnection();
        $select = $conn->select()
            ->from(['sales_order' => 'sales_order'], ['increment_id'])
            ->where('entity_id = '.$orderId)
            ->limit(1);
        $orderIncrementId = $conn->fetchOne($select);

        $requestData = [
            'orderId' => $orderIncrementId,
            'orderNo' => $orderIncrementId,
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
                $this->prelaunchHelperData->setProductQty($sku, $remainQty);
            }
        }catch (\Exception $e) {

        }
    }
}