<?php

namespace Branch8\Quote\Observer;

use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Event\ObserverInterface;

class QtySetAfter implements ObserverInterface{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * @param $observer
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute($observer){
        $item = $observer->getEvent()->getItem();
        if(!$item->getQuoteId()){
            return;
        }
        //just add for first time, do nothing
        // if(!$item->getId()){
        //     return;
        // }
        if(/** Not change*/(int)$item->getOrigData('qty') == (int)$item->getQty() && (int)$item->getOrigData('qty') != 0){
            return;
        }
        $_productItem = $item->getProduct();

        //is this virtual? so no need to checkout shipping method
        if(
            ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL) ||
            $item->getProductType() == Type::TYPE_DOWNLOADABLE ||
            (
                $_productItem->getTypeId() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD &&
                $_productItem->getGiftcardType() == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL
            )
        ){
            $item->setAvailableToCheckout(1);
            if($item->getId()){
                $currentItemAvailableCheckout = 'update quote_item set available_to_checkout=1 where item_id='.$item->getId();
                $this->_resourceConnection->getConnection()->query($currentItemAvailableCheckout);
            }
            
            return;
        }
        
        $intersectMethods = $this->getShippingMethod($item->getProductId());

        $connection = $this->_resourceConnection->getConnection();
        $queryOtherItems = $connection->select()->from(
            ['e' => 'quote_item'],
            ['item_id', 'product_type', 'product_id']
        );
        if($item->getId()){
            $queryOtherItems->where('item_id <> '.$item->getId());
        }
            
        $queryOtherItems->where('available_to_checkout = 1')
            ->where('quote_id = '.$item->getQuoteId());
        $result = $connection->query($queryOtherItems);
        $itemChecked = [];
        while($rowItem = $result->fetch()){

            /**
             * Ignore for virtual product
             */
            if(
                $rowItem['product_type'] == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
                $rowItem['product_type'] == Type::TYPE_DOWNLOADABLE
            ){
                continue;
            }
            if( $rowItem['product_type'] == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD){
                $giftcardType = $this->getGiftcardType($rowItem['product_id']);
                if($giftcardType == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL) {
                    continue;
                }
            }

            $itemChecked[] = $rowItem['item_id'];

            $_productItemShippingMethods = $this->getShippingMethod($rowItem['product_id']);
            $intersectMethods = array_intersect($intersectMethods, $_productItemShippingMethods);
        }
        
        //if the shipping method of this item match other checked item, checked this item
        if(count($intersectMethods)){
            if($item->getId()){
                $item->setAvailableToCheckout(1);
                $currentItemAvailableCheckout1 = 'update quote_item set available_to_checkout=1 where item_id='.$item->getId();
                $this->_resourceConnection->getConnection()->query($currentItemAvailableCheckout1);
            }else{
                /** Default available_to_checkout = 1, not need to add code */
            }
            
        }else{
            if($item->getId()){
                /**
                 * If the shipping method of this item not match other items
                 * Uncheck other items, checked this item
                 */
                if(count($itemChecked)) {
                    $sql = 'update quote_item set available_to_checkout=0 where item_id in(' . implode(',', $itemChecked) . ')';
                    $this->_resourceConnection->getConnection()->query($sql);
                }
                $item->setAvailableToCheckout(1);
                $currentItemAvailableCheckout1 = 'update quote_item set available_to_checkout=1 where item_id='.$item->getId();
                $this->_resourceConnection->getConnection()->query($currentItemAvailableCheckout1);
            }else{
                $item->setData('available_to_checkout', 0);
            }
        }
    }

    /**
     * @param $productId
     * @return string[]
     */
    protected function getShippingMethod($productId){
        $connection = $this->_resourceConnection->getConnection();
        $catalogProductTable = 'catalog_product_entity';
        $catalogVarchar = 'catalog_product_entity_varchar';
        $eavTable = 'eav_attribute';
        $shippingAttr = 'shipping_method';

        $select = $connection->select()->from(
            ['e' => $catalogProductTable],
            []
        )->joinLeft(
            ['ev' => $catalogVarchar],
            "e.row_id = ev.row_id AND ev.store_id = 0",/*shipping method is global scope*/
            ['shipping_method' => 'ev.value']
        )->joinLeft(
            ['ea' => $eavTable],
            "ev.attribute_id = ea.attribute_id",
            []
        )->where(
            'ea.attribute_code = :attribute_code'
        )->where(
            'e.entity_id = :entity_id'
        );
        $bind = [
            'attribute_code' => $shippingAttr,
            'entity_id' => $productId,
        ];
        $shippingMethod = $connection->fetchOne($select, $bind);
        $shippingMethodArr =  explode(',', (string)$shippingMethod);
        return $shippingMethodArr;
    }

    /**
     * @param $productId
     * @return string
     */
    protected function getGiftcardType($productId){
        $connection = $this->_resourceConnection->getConnection();
        $catalogProductTable = 'catalog_product_entity';
        $eavTable = 'eav_attribute';
        $giftcardTypeAttr = 'giftcart_type';
        $catalogInt = 'catalog_product_entity_int';

        $select = $connection->select()->from(
            ['e' => $catalogProductTable],
            []
        )->joinLeft(
            ['ei' => $catalogInt],
            "e.row_id = ei.row_id AND ei.store_id = 0",
            ['giftcard_type' => 'ei.value']
        )->joinLeft(
            ['ea' => $eavTable],
            "ev.attribute_id = ea.attribute_id",
            []
        )->where(
            'ea.attribute_code = :attribute_code'
        )->where(
            'e.entity_id = :entity_id'
        );
        $bind = [
            'attribute_code' => $giftcardTypeAttr,
            'entity_id' => $productId,
        ];
        $giftcartType = $connection->fetchOne($select, $bind);
        return $giftcartType;
    }
}
