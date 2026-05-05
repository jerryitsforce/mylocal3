<?php

namespace Branch8\Marketplace\Model;

use Branch8\HotaiShipping\Helper\Data as HotaiShippingHelper;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\CarrierFactory;

class DisableShipppingMethodProcess{

    protected $resourceConnection;

    protected $_eavAttribute;

    protected $_carrierFactory;

    protected $timeZone;

    protected $hotaiShippingHelper;


    public function __construct(
        ResourceConnection $resourceConnection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        CarrierFactory $carrierFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        HotaiShippingHelper $hotaiShippingHelper
    ){
        $this->resourceConnection = $resourceConnection;
        $this->_eavAttribute = $eavAttribute;
        $this->_carrierFactory = $carrierFactory;
        $this->timeZone = $timeZone;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute(){
        $connection = $this->resourceConnection->getConnection();
        $sql = 'select * from branch8_shipping_method_update_queue where status = 0';
        $result = $connection->query($sql);
        while($row = $result->fetch()){
            $this->removeMethodFromProduct($connection, $row['carrier']);
            $this->removeMethodFromSellerAndNotify($connection, $row['carrier'], $row['store_id'], $row['created_at']);
            //update the cron status
            $sqlUpdateCron = 'update branch8_shipping_method_update_queue set status = 1, updated_at="'.$this->timeZone->date()->format('Y-m-d H:i:s').'" where entity_id = '.$row['entity_id'];
            $connection->query($sqlUpdateCron);
        }
    }

    protected function removeMethodFromProduct($connection, $carrier){
        $shippingMethodAttr = 'shipping_method';
        $attributeId = $this->_eavAttribute
            ->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, $shippingMethodAttr);
        $sql = 'select * from catalog_product_entity_varchar where attribute_id='.$attributeId.' and value <> "" and value IS NOT NULL';
        $result = $connection->query($sql);
        $cnt = 0;
        while($row = $result->fetch()){
            if(strpos($row['value'], $carrier) !== false){
                $cnt ++;
                $updatedCarrier = str_replace($carrier, '', $row['value']);
                $updatedCarrier = trim($updatedCarrier, ',');
                $updatedSql = 'Update catalog_product_entity_varchar set value="'.$updatedCarrier.'" where value_id='.$row['value_id'];
                $connection->query($updatedSql);
                if($cnt == 150){
                    //sleep 200 ms
                    usleep(200000);
                    $cnt = 0;
                }
            }
        }

    }
    protected function removeMethodFromSellerAndNotify($connection, $carrier, $storeId, $notifyDate){
        $shippingMethodMapping = $this->hotaiShippingHelper->mappingAllMethods();
        if(!isset($shippingMethodMapping[$carrier])){
            return;
        }
        $carrier = $shippingMethodMapping[$carrier];

        $carrierObj = $this->_carrierFactory->create($carrier, $storeId);
        $sql = 'select seller_id, shipping_methods from marketplace_userdata where shipping_methods like "%'.$carrier.'%"';
        $result = $connection->query($sql);
        while($row = $result->fetch()){
            $shippingMethods = $row['shipping_methods'];
            $shippingMethods = str_replace($carrier, '', $shippingMethods);
            $updatedCarrier = trim($shippingMethods, ',');
            $sqlUpdate = 'update marketplace_userdata set shipping_methods="' . $updatedCarrier . '" where seller_id="' . $row['seller_id'] . '";';
            $connection->query($sqlUpdate);

            //set notification

            $desc = __('The %1 shipping method is disabled.', $carrierObj->getConfigData('title'));
            $sqlNotification = 'insert into marketplace_custom_notification values(null, '.$row['seller_id'].', "'.$desc.'", "#", 1);';
            $connection->query($sqlNotification);
            $notifyId = $connection->lastInsertId();
            $sqlNotificationList = 'insert into marketplace_notification_list values(null, '.$notifyId.', 0, '.\Webkul\Marketplace\Model\Notification::TYPE_CUSTOM.', "'.$notifyDate.'", NULL)';
            $connection->query($sqlNotificationList);
        }

    }

}
