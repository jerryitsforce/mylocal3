<?php

namespace Branch8\Catalog\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\SaleperpartnerFactory;
use Webkul\Marketplace\Model\SaleslistFactory;

class TrackingPriceCostChange implements ObserverInterface{

    protected $timezone;

    protected $request;

    protected $_connection;

    protected $messageManager;

    protected $marketPlaceDataHelper;

    protected $saleperPartner;
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        RequestInterface $requestInterface,
        ResourceConnection $connection,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        MarketplaceHelper $marketplaceHelper,
        SaleperpartnerFactory $saleperPartnerFactory
    ){
        $this->timezone = $timezone;
        $this->request = $requestInterface;
        $this->_connection = $connection;
        $this->messageManager = $messageManager;
        $this->marketPlaceDataHelper = $marketplaceHelper;
        $this->saleperPartner = $saleperPartnerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $model = $observer->getEvent()->getData('product');
        if(in_array($model->getTypeId(),
            [\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE, \Magento\Bundle\Model\Product\Type::TYPE_CODE,
                \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE, \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD])
        ){
            return;
        }

        $productId = $model->getId();
        $sku = $model->getSku();
        $oldPrice = $model->getOrigData('price');
        $newPrice = $model->getPrice();
        $oldCost = $model->getOrigData('cost');
        $newCost = $model->getCost();
        $isPriceChange = false;
        if($oldPrice != $newPrice) {
            $strPrice = (float)$oldPrice.','.(float)$newPrice;
            $isPriceChange = true;
        }else{
            $strPrice = 'NULL, NULL';
        }
        $isCostChange = false;
        if($oldCost != $newCost) {
            $strCost = (float)$oldCost.','.(float)$newCost;
            $isCostChange = true;
        }else{
            $strCost = 'NULL, NULL';
        }

        //history change price/ cost
        if($isCostChange || $isPriceChange) {
            try{
                $action = $this->request->getFullActionName();
            }catch (\Exception $e){
                $action = 'N/A';
            }
            //Website time, not UTC
            $createdAt = $this->timezone->date()->format('Y-m-d H:i:s');

            $strValue = 'NULL, '.$productId.', "'.$sku.'", '.$strCost.', '.$strPrice.', "'.$action.'", "'.$createdAt.'"';
            $sqlInsert = 'insert into b8_tracking_price_cost_change values('.$strValue.')';
            $this->_connection->getConnection()->query($sqlInsert);
        }
    }

}