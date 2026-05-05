<?php

namespace Branch8\Prelaunch\Helper;

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_ACTIVE = 'prelaunch/general/is_active';

    const LIMIT_SKU = 'prelaunch/general/limit_sku';

    const EXCL_SKU = 'prelaunch/general/excl_sku';
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $manager;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;

    protected $_getSalableQuantityDataBySku;
    protected $webkulHelper;

    /**
     * @param Context $context
     * @param \Magento\Framework\Module\Manager $manager
     * @param \Magento\Catalog\Model\Product $productobj
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Module\Manager $manager,
        \Magento\Catalog\Model\Product $productobj,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \Webkul\OptionsWithStockAndImages\Helper\Data $webkulHelper
    ){
        parent::__construct($context);
        $this->manager = $manager;
        $this->_product = $productobj;
        $this->_stockRegistry = $stockRegistry;
        $this->_getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->webkulHelper = $webkulHelper;
    }

    /**
     * @return bool
     */
    public function isActive(){
        $moduleActive = $this->manager->isEnabled('Branch8_Prelaunch');
        $configActive = $this->scopeConfig->getValue(self::API_ACTIVE);
        return $moduleActive && $configActive;
    }

    /**
     * @param $sku
     * @return array|string|string[]
     */
    public function parseItemId($sku){
        $expl1 = explode('-', $sku);
        $sku2 = $expl1[0];
        $sku3 = str_replace('HOTAI', '', $sku2);
        return (int)$sku3;
    }

    public function validateLimitSku($sku){
        $limitSkus = (string)$this->scopeConfig->getValue(self::LIMIT_SKU);
        if(trim($limitSkus) == ''){
            $exclSku = (string)$this->scopeConfig->getValue(self::EXCL_SKU);
            $exclSkuArr = explode(',', $exclSku);
            if(in_array($sku, $exclSkuArr)){
                return false;
            }
            return true;
        }
        $skus = explode(',', $limitSkus);
        if(in_array($sku, $skus)){
            return true;
        }
        return false;
    }

    public function setProductQty($sku, $qty, $balanceQty = 0, $isCustomOptionSku = false){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'customlog')){
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/custom.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
        }
        try {
            $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
            /**
             * Calculate qty to update to Magento
             * Qty = API qty + (Magento qty - Salable qty)
             */
            $currentMagentoQty = $stockItem->getQty();
            $salableQtyData = $this->_getSalableQuantityDataBySku->execute($sku);
        }catch(\Exception $e){
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'customlog')){
                $logger->info($e->getMessage());
            }
            return;
        }
        $stockData = null;
        foreach($salableQtyData as $_data){
            if($_data['stock_id'] == Stock::DEFAULT_STOCK_ID){
                $stockData = $_data;
            }
        }
        if(!$stockData){
            return;
        }

        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'customlog')){
            $logger->info('API qty:'.$qty.' - Magento qty:'.$currentMagentoQty.' - salable qty:'.$stockData['qty']);
        }

        if(!$stockData['manage_stock'] || $isCustomOptionSku){
            $newQty = $qty;
        }else{
            $newQty = $qty + ($currentMagentoQty - $stockData['qty']);
        }
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'customlog')){
            $logger->info('New qty:'.$newQty);
            $logger->info('Balance qty :'.$balanceQty);
        }
        /**
         * Some event run before the salable record revert added
         * $balanceQty = qty_canceled
         */
        $newQty -= $balanceQty;

        $stockItem->setQty($newQty);
        $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
    }

    public function customOptionSkuIsSync($productId, $itemOptions, $optionData)
    {
        $sku = null;
        $comb = '';
        foreach ($itemOptions as $key => $value) {
            if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                $comb .= $optionData[$key][$value]."_";
            }
        }
        $comb = trim($comb, "_");
        $variation = $this->webkulHelper->getCombData($productId, $comb);
        if ($variation->getIsSync()) {
            $sku = $variation->getSku();
            return $sku;
        }
        return $sku;
    }
}