<?php

namespace Branch8\Prelaunch\Observer;

use Magento\Framework\Event\ObserverInterface;

class BeforeAddToCart implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Branch8\Prelaunch\Helper\Data
     */
    protected $prelaunchHelperData;
    /**
     * @var \Branch8\Prelaunch\Helper\Api
     */
    protected $prelaunchHelperApi;
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $customOptionHelper;

    /**
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $customOptionHelper
     */
    public function __construct(
        \Branch8\Prelaunch\Helper\Data $prelaunchHelperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi,
        \Webkul\OptionsWithStockAndImages\Helper\Data $customOptionHelper,
    ){
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->scopeConfig = $scopeConfig;
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

        $product = $observer->getEvent()->getProduct();
        $sku = $product->getSku();

        $productExtAttribute = $product->getExtensionAttributes();
        $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
        /**
         * not manage stock <=> this is parent of custom option
         * validate for custom option
         * Get SKU of current custom option
         */
        if(!$isManageStock){
            $sku = '';
            $productId = $product->getRowId();
            $inforAdd = $observer->getEvent()->getData('info');
            if(isset($inforAdd['options'])) {
                $customOptions = $inforAdd['options'];
                $optionData = $this->customOptionHelper->getOptionData($product->getId());
                $customOptionSku = $this->prelaunchHelperData->customOptionSkuIsSync($productId, $customOptions, $optionData);
                if($customOptionSku){
                    $sku = $customOptionSku;
                }
            }

        }

        if($sku == ''){
            return;
        }

        if(!$this->prelaunchHelperData->validateLimitSku($sku)){
            return;
        }

        /**
         * case simple product not have custom option
         */
        $itemId = $this->prelaunchHelperData->parseItemId($sku);

        $requestData = [
            'orderId' => null,
            'orderNo' => null,
            'syncProductList' => [
                [
                    'qty' => 0,
                    'productItemId' => $itemId
                ]
            ],
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
            if(!$responseParsed){
                throw new \Exception(__("System don't allow add to cart this product")->render());
            }
            $dataList = $responseParsed['dataList'];
            foreach($dataList as $_item){
                if($itemId == $_item['productItemId'] && !$_item['isSuccess']){
                    throw new \Exception(__("System don't allow add to cart this product"));
                }
                $remainQty = $_item['remainingQty'];

                //update stock to Magento
                $this->prelaunchHelperData->setProductQty($sku, $remainQty);

                $addCartInfor = $observer->getEvent()->getData('info');
                if(isset($addCartInfor['qty'])){
                    $qtyAdd = $addCartInfor['qty'];
                }else{
                    $qtyAdd = 1;
                }

                if((int)$qtyAdd > $remainQty){
                    throw new \Exception(__("The requested qty exceeds the maximum qty allowed in shopping cart"));
                }
            }
        }catch (\Exception $e){
            throw new \Exception(__('Error on parse data from System')->render());
        }

    }



}