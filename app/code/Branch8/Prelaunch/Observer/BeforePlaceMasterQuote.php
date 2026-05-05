<?php

namespace Branch8\Prelaunch\Observer;

use Magento\Framework\Event\ObserverInterface;

class BeforePlaceMasterQuote implements ObserverInterface
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

    protected $customOptionHelper;



    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollection,
        \Branch8\Prelaunch\Helper\Data $prelaunchHelperData,
        \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi,
        \Webkul\OptionsWithStockAndImages\Helper\Data $customOptionHelper
    ){
        $this->quoteItemCollection = $quoteItemCollection;
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->prelaunchHelperApi = $prelaunchHelperApi;
        $this->customOptionHelper = $customOptionHelper;
    }

    /**
     * @param $observer
     * @return void
     * @throws \Exception
     */
    public function execute($observer){
        if(!$this->prelaunchHelperData->isActive()){
            return;
        }
        $order = $observer->getEvent()->getData('order');
        $masterQuote = $observer->getEvent()->getData('quote');

        $cols = $order->getItems();
        $productList = [];
        $cartSkus = [];

        foreach($cols as $_item){

            $product = $_item->getProduct();
            $sku = $product->getSku();

            $productExtAttribute = $product->getExtensionAttributes();
            $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
            if(!$isManageStock){
                continue;
            }

            if(!$this->prelaunchHelperData->validateLimitSku($sku)){
                continue;
            }

            $oldSystemId = $this->prelaunchHelperData->parseItemId($sku);
            $productList[] = [
                'qty' => (int)$_item->getQtyOrdered(),
                'productItemId' => $oldSystemId
            ];
            $cartSkus[$oldSystemId] = ['sku' => $sku, 'qty' => (int)$_item->getQty()];
        }

        if(!count($productList)){
            return;
        }

        $requestData = [
            'orderId' => $order->getData('increment_id'),
            'orderNo' => $order->getData('increment_id'),
            'syncProductList' => $productList,
            'isCancel' => false
        ];
        $response = $this->prelaunchHelperApi->stockAPI($requestData);

        if($response['success'] == -1){
            return;
        }
        if($response['success'] == 0){
            throw new \Exception(__('System Error')->render());
        }
        try{
            $responseParsed = json_decode($response['response'], true);
        }catch (\Exception $e) {
            throw new \Exception(__('Error on parse data from System')->render());
        }

        if(!$responseParsed || !$responseParsed['isSuccess']){
            throw new \Exception(__("System don't allow add to cart this product")->render());
        }
        $dataList = $responseParsed['dataList'];
        foreach($dataList as $_item){
            if(!$_item['isSuccess']){
                throw new \Exception(__("System don't allow add to cart this product"));
            }
        }

        //update stock to Magento
        foreach($dataList as $_data){
            $itemId = $_data['productItemId'];
            if(isset($cartSkus[$itemId])){
                $itemQty = $_data['remainingQty'] + $cartSkus[$itemId]['qty'];//need to + cart qty because old system has deducted
                $this->prelaunchHelperData->setProductQty($cartSkus[$itemId]['sku'], $itemQty);
            }
        }
    }

}