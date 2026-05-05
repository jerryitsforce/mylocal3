<?php

namespace Branch8\Checkout\Observer;
use Magento\Framework\App\ResourceConnection;
class AddProductSpecilPrice implements \Magento\Framework\Event\ObserverInterface{

    protected $resourceConnection;
    public function __construct(
        ResourceConnection $resourceConnection
    ){
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Phase 1: apply for simple, virtual
     * @param $observer
     * @return void
     */
    public function execute($observer){

        $order = $observer->getEvent()->getOrder();
        $connection = $this->resourceConnection->getConnection();

        $sqlItem = 'select item_id, product_id from sales_order_item where order_id = '.$order->getId();
        $queryItem = $connection->fetchAll($sqlItem);

        $tbProduct = $connection->getTableName('catalog_product_entity');
        $tbEavAtt = $connection->getTableName('eav_attribute');
        $tbProductDec = $connection->getTableName('catalog_product_entity_decimal');

        foreach($queryItem as $_item){
            $productId = $_item['product_id'];
            $itemId = $_item['item_id'];
            $productSelect = $connection->select()
                ->from(['e' => $tbProduct], [])
                ->joinLeft(['dec' => $tbProductDec], 'e.row_id = dec.row_id and dec.store_id=:store_id', ['value'])
                ->joinLeft(['eav' => $tbEavAtt], 'eav.attribute_id = dec.attribute_id', [])
                ->where('attribute_code=:attribute_code')
                ->where('e.entity_id=:product_id');
            $bind = ['attribute_code' => 'special_price', 'product_id' => $productId, 'store_id' => 0];/*special price is global*/
            $specialPrice = $connection->fetchOne($productSelect, $bind);
            
            $sqlUpdateItem = 'update sales_order_item set special_price="'.$specialPrice.'" where item_id='.$itemId;
            $connection->query($sqlUpdateItem);
        }

    }


}