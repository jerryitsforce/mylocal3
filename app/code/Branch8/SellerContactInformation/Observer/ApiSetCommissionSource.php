<?php
namespace Branch8\SellerContactInformation\Observer;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;

class ApiSetCommissionSource implements \Magento\Framework\Event\ObserverInterface
{
    protected $mpHelper;

    public function __construct(
        MarketplaceStagingHelper $mpHelper
    )
    {
        $this->mpHelper = $mpHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $extensionAttribute = $product->getExtensionAttributes();
        $sellerIdFromExtAttr = (int)$extensionAttribute->getSellerId();
        if(!$sellerIdFromExtAttr){
            return ;
        }
        /** Get seller commission */
        $sellerCommissionData = $this->mpHelper->getCommisionRates($sellerIdFromExtAttr);
        $sellerCommissionRate = $sellerCommissionData['commission_rate'];
        $costSetting = $product->getCostSetting();
        if($costSetting == \Branch8\Catalog\Model\Source\CostSetting::MANUALLY){
            return;
        }
        $commissionPercent = $product->getCommissionPercent();

        if($commissionPercent == $sellerCommissionRate && $sellerCommissionData['active_contract']){
            $commissionSource = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::ACTIVE_PERIOD;
        }else if($product['commission_percent'] == $sellerCommissionData['default_commission_rate']){
            $commissionSource = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::DEFAULT_SETTING;
        }else{
            $commissionSource = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT;
        }
        $product->setCommissionSource($commissionSource);
    }
}