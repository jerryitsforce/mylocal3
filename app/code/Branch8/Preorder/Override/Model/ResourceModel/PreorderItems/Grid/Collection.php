<?php

namespace Branch8\Preorder\Override\Model\ResourceModel\PreorderItems\Grid;

class Collection extends \Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Grid\Collection{

    protected function _renderFiltersBefore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $eavAttribute = $objectManager->get(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute::class
        );

        $edition = $this->productMetadata->getEdition();
        if ($edition == 'Enterprise') {
            $salesOrder = $this->getTable('sales_order_grid');
            $proAttrId = $eavAttribute->getIdByCode("catalog_product", "name");
            $catalogProductEntityVarchar = $this->getTable('catalog_product_entity_varchar');
            /**
             * Core ext make issue if join condition product_id = row_id
             */
//            $this->getSelect()->join(
//                $catalogProductEntityVarchar.' as cpev',
//                'main_table.product_id = cpev.row_id',
//                ["product_name" => "value", "mage_store_id" => "store_id"]
//            )->where("cpev.store_id = 0 AND cpev.attribute_id = ".$proAttrId);

            $this->getSelect()->join(
                $salesOrder.' as so',
                'main_table.order_id = so.entity_id',
                [
                    "billing_name" => "billing_name",
                    "shipping_name" => "shipping_name",
                    "created_at" => "created_at",
                    "increment_id" => "increment_id",
                    "base_grand_total" => "base_grand_total",
                    "grand_total" => "grand_total"
                ]
            );
        } else {
            $salesOrder = $this->getTable('sales_order_grid');
            $proAttrId = $eavAttribute->getIdByCode("catalog_product", "name");
            $catalogProductEntityVarchar = $this->getTable('catalog_product_entity_varchar');

            $this->getSelect()->join(
                $catalogProductEntityVarchar.' as cpev',
                'main_table.product_id = cpev.entity_id',
                ["product_name" => "value", "mage_store_id" => "store_id"]
            )->where("cpev.store_id = 0 AND cpev.attribute_id = ".$proAttrId);

            $this->getSelect()->join(
                $salesOrder.' as so',
                'main_table.order_id = so.entity_id',
                [
                    "billing_name" => "billing_name",
                    "shipping_name" => "shipping_name",
                    "created_at" => "created_at",
                    "increment_id" => "increment_id",
                    "base_grand_total" => "base_grand_total",
                    "grand_total" => "grand_total"
                ]
            );
        }
//        parent::_renderFiltersBefore();
    }

}