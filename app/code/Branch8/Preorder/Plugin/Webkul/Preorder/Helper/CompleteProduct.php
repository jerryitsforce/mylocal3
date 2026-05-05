<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class CompleteProduct
{
    protected $_productFactory;

    protected $scopeConfig;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $product,
        ScopeConfigInterface $scopeConfig
    ){
        $this->_productFactory = $product;
        $this->scopeConfig = $scopeConfig;
    }
    public function aroundCreatePreOrderProduct($subject)
    {
        $preorderProductId = $subject->getPreorderCompleteProductId();
        $attributeSetId = $this->_productFactory->create()->getDefaultAttributeSetId();
        if ($preorderProductId == 0 || $preorderProductId == '') {
            try {
                $websiteIds = $subject->getWebsiteIds();
                $stockData = [
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 0,
                    'is_in_stock' => 1,
                    'qty' => 999999999,
                ];
                $preorderProduct = $this->_productFactory->create();
                $completeProductSku = $this->scopeConfig->getValue('mppreorder/general_setting/preorder_conplete_product_sku');
                if(empty($completeProductSku)){
                    return;
                }
                $preorderProduct->setSku($completeProductSku);
                $preorderProduct->setName('Complete PreOrder');
                $preorderProduct->setAttributeSetId($attributeSetId);
                $preorderProduct->setCategoryIds([2]);
                $preorderProduct->setWebsiteIds($websiteIds);
                $preorderProduct->setStatus(1);
                $preorderProduct->setVisibility(1);
                $preorderProduct->setTaxClassId(0);
                $preorderProduct->setTypeId('virtual');
                $preorderProduct->setPrice(0);
                $preorderProduct->setStockData($stockData);
                $preorderProduct->save();
                $subject->addImage($preorderProduct);
                $subject->setCustomOption($preorderProduct);
            } catch (\Exception $e) {
                $e->getMessage();
            }
        }
    }
}