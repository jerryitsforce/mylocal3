<?php

namespace Branch8\Prelaunch\Plugin;

use Branch8\Prelaunch\Magento;

class RmaRefunded
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

    public function afterSetStatus($subject, $result, $item, $status){
        if(!$this->prelaunchHelperData->isActive()){
            return;
        }
        if(!$status){
            return;
        }
        $orderItemId = $item->geItemId();
        $conn = $this->resourceConnection->getConnection();
        $sql = $conn->select()
            ->from(['oi' => 'sales_order_item'], ['sku', 'order_id'])
            ->where('item_id='.$orderItemId);
        $itemDataFetch = $conn->fetchRow($sql);
        if(!isset($itemDataFetch[0])){
            return;
        }
        $itemData = $itemDataFetch[0];

        $sku = $itemData['sku'];
        if(!$this->prelaunchHelperData->validateLimitSku($sku)){
            return;
        }

        $productList = [];
        $productList[] = [
            'qty' => (int)$item->getQty(),
            'productItemId' => $this->prelaunchHelperData->parseItemId($sku)
        ];

        $orderId = $itemData['order_id'];
        $conn = $this->resourceConnection->getConnection();
        $select = $conn->select()
            ->from(['prc' => 'sales_parent_order_children'], [])
            ->joinLeft(['spl_o' => 'marketplace_mpsplitorder'], 'prc.parent_id=plt_o.index_id', ['hotai_reserved_order_id'])
            ->where('prc.children_id = '.$orderId)
            ->limit(1);
        $parentOrderIncrementId = $conn->fetchOne($select);


        $requestData = [
            'orderId' => $parentOrderIncrementId,
            'orderNo' => $parentOrderIncrementId,
            'syncProductList' => $productList,
            'isCancel' => true
        ];
        $this->prelaunchHelperApi->stockAPI($requestData);
    }
}