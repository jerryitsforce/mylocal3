<?php

namespace Branch8\MarketplaceProduct\Rewrite\Model\ResourceModel\Product\FrontGrid;

class Collection extends \Webkul\Marketplace\Model\ResourceModel\Product\FrontGrid\Collection
{
    protected $_filtered = [];

    /**
     * @inheritDoc
     */
    protected function _initSelect(): void
    {
        $this->addFilterToMap("is_hidden", "cpeih.value");
        $this->addFilterToMap("seller_shop_name", "cpesn.value");
        $this->addFilterToMap("preorder_mode", "cpepm.value");
        $this->addFilterToMap("long_time_ship", "cpelts.value");
        $this->addFilterToMap("limit_purchased_enable", "cpelpe.value");
        $this->addFilterToMap("url_key", "cpeuk.value");
        $this->addFilterToMap("special_price", "cpedsp.value");
        $this->addFilterToMap("brand", "cpebrand.value");
        $this->addFilterToMap("commission_percent", "cpedcr.value");
        $this->addFilterToMap("first_enabled_date", "cpedt.value");
        $this->addFilterToMap("cost", "cpedc.value");
        $this->addFilterToMap('entity_id', 'cpe.entity_id');
        $this->addFilterToMap("updated_at", "cpe.updated_at");
        $this->addFilterToMap("user_updated", "cpeuu.value");
        $this->addFilterToMap("need_to_refill", "cpen2r.value");
        parent::_initSelect();
    }

    /**
     * @inheritDoc
     */
    protected function _renderFiltersBefore(): void
    {
        $fields = $this->_filtered;
        $sellerId = $this->mpHelper->getCustomerId();
        if (!$sellerId) {
            $sellerId = 0;
        }
        $storeId = 1;
        $eavAttribute = $this->attribute;
        $proIsHiddenAttrId = $eavAttribute->getIdByCode("catalog_product", "is_hidden");
        $proLimitPurchasedEnableAttrId = $eavAttribute->getIdByCode("catalog_product", "limit_purchased_enable");
        $proBrandAttrId = $eavAttribute->getIdByCode("catalog_product", "brand");
        $prodNeedToRefillAttrId = $eavAttribute->getIdByCode("catalog_product", "need_to_refill");

        $connection = $this->getConnection();

        $catalogProductEntityDecimal = $this->getTable('catalog_product_entity_decimal');
        $catalogProductEntityDatetime = $this->getTable('catalog_product_entity_datetime');
        $catalogProductEntityInt = $this->getTable('catalog_product_entity_int');
        $catalogProductEntity = $this->getTable('catalog_product_entity');
        $catalogInventoryStockItem = $this->getTable('cataloginventory_stock_item');

        $catalogProductEntityVarchar = $this->getTable('catalog_product_entity_varchar');
        $this->getSelect()->join(
            $catalogProductEntity . ' as cpe',
            'main_table.mage_pro_row_id = cpe.row_id',
            ['entity_id', 'attribute_set_id', 'type_id', 'sku', 'updated_at', 'cpe.created_at']
        )->where("main_table.seller_id = " . $sellerId);
        if (in_array('name', $fields)) {
            $proAttrId = $eavAttribute->getIdByCode("catalog_product", "name");
            $this->getSelect()->join(
                $catalogProductEntityVarchar . ' as cpev',
                'main_table.mage_pro_row_id = cpev.row_id',
                ["name" => "value"]
            )->where(
                "cpev.store_id = ?  AND  cpev.attribute_id = " . $proAttrId,
                $connection->getIfNullSql('cpev.store_id=' . $storeId, 'cpev.store_id=0')
            );
        }

        if (in_array('product_status', $fields)) {
            $proStatusAttrId = $eavAttribute->getIdByCode("catalog_product", "status");
            $this->getSelect()->join(
                $catalogProductEntityInt . ' as cpei',
                'main_table.mage_pro_row_id = cpei.row_id',
                ["product_status" => "value"]
            )->where(
                'cpei.store_id = ?  AND cpei.attribute_id = ' . $proStatusAttrId,
                $connection->getIfNullSql('cpei.store_id=' . $storeId, 'cpei.store_id=0')
            );
        }
        /**
         * Add filter is_hidden
         */
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpeih',
            'main_table.mage_pro_row_id = cpeih.row_id',
            ["is_hidden" => "value"]
        )->where(
            'cpeih.store_id = ?  AND cpeih.attribute_id = ' . $proIsHiddenAttrId,
            $connection->getIfNullSql('cpeih.store_id=' . $storeId, 'cpeih.store_id=0')
        );

        // seller_shop_name
        if (in_array('seller_shop_name', $fields)) {
            $proSellerShopNameAttrId = $eavAttribute->getIdByCode("catalog_product", "seller_shop_name");
            $this->getSelect()->joinLeft(
                $catalogProductEntityVarchar . ' as cpesn',
                'main_table.mage_pro_row_id = cpesn.row_id and cpesn.store_id = 0 AND cpesn.attribute_id = ' . $proSellerShopNameAttrId,
                ["seller_shop_name" => "value"]
            );
        }

        // user_updated
        if (in_array('user_updated', $fields)) {
            $adminUserUpdatedAttrId = $eavAttribute->getIdByCode("catalog_product", "admin_user_updated");
            $this->getSelect()->joinLeft(
                $catalogProductEntityVarchar . ' as cpeuu',
                'main_table.mage_pro_row_id = cpeuu.row_id and cpeuu.store_id = 0 AND cpeuu.attribute_id = ' . $adminUserUpdatedAttrId,
                ["user_updated" => "value"]
            );
        }

        // preorder_mode
        $proPreorderModeAttrId = $eavAttribute->getIdByCode("catalog_product", "preorder_mode");
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpepm',
            'main_table.mage_pro_row_id = cpepm.row_id and cpepm.store_id = 0 AND cpepm.attribute_id = ' . $proPreorderModeAttrId,
            ["preorder_mode" => "value"]
        );

        /**
         * Add filter long_time_ship
         */
        $proLongTimeShipAttrId = $eavAttribute->getIdByCode("catalog_product", "long_time_ship");
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpelts',
            'main_table.mage_pro_row_id = cpelts.row_id and cpelts.store_id = 0 AND cpelts.attribute_id = ' . $proLongTimeShipAttrId,
            ["long_time_ship" => "value"]
        );

        // url_key
        if (in_array('url_key', $fields)) {
            $proUrlKeyAttrId = $eavAttribute->getIdByCode("catalog_product", "url_key");
            $this->getSelect()->joinLeft(
                $catalogProductEntityVarchar . ' as cpeuk',
                'main_table.mage_pro_row_id = cpeuk.row_id and cpeuk.store_id = 0 AND cpeuk.attribute_id = ' . $proUrlKeyAttrId,
                ["url_key" => "value"]
            );
        }

        /**
         * Add filter limit_purchased_enable
         */
        $proLimitPurchasedEnableAttrId = $eavAttribute->getIdByCode("catalog_product", "limit_purchased_enable");
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpelpe',
            'main_table.mage_pro_row_id = cpelpe.row_id and cpelpe.store_id = 0 AND cpelpe.attribute_id = ' . $proLimitPurchasedEnableAttrId,
            ["limit_purchased_enable" => "value"]
        );

        /**
         * Add filter brand
         */
        $proBrandAttrId = $eavAttribute->getIdByCode("catalog_product", "brand");
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpebrand',
            'main_table.mage_pro_row_id = cpebrand.row_id and cpebrand.store_id = 0 AND cpebrand.attribute_id = ' . $proBrandAttrId,
            ["brand" => "value"]
        );

        // echo $this->getSelect()->group('mage_pro_row_id');die;
        $proVisibilityAttrId = $eavAttribute->getIdByCode("catalog_product", "visibility");
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpai',
            'main_table.mage_pro_row_id = cpai.row_id',
            ["visibility" => "value"]
        )->where(
            "cpai.store_id = ? AND cpai.attribute_id = " . $proVisibilityAttrId,
            $connection->getIfNullSql('cpai.store_id=' . $storeId, 'cpai.store_id=0')
        );

        $proPriceAttrId = $eavAttribute->getIdByCode("catalog_product", "price");
        $this->getSelect()->joinLeft(
            $catalogProductEntityDecimal . ' as cped',
            'main_table.mage_pro_row_id = cped.row_id and cped.store_id = 0 AND cped.attribute_id = ' . $proPriceAttrId,
            ["price" => "value"]
        );
        $this->addFilterToMap("price", "cped.value");

        // special_price
        $proSpecialPriceAttrId = $eavAttribute->getIdByCode("catalog_product", "special_price");
        $this->getSelect()->joinLeft(
            $catalogProductEntityDecimal . ' as cpedsp',
            'main_table.mage_pro_row_id = cpedsp.row_id and cpedsp.store_id = 0 AND cpedsp.attribute_id = ' . $proSpecialPriceAttrId,
            ["special_price" => "value"]
        );

        // cost
        $proCostAttrId = $eavAttribute->getIdByCode("catalog_product", "cost");
        $this->getSelect()->joinLeft(
            $catalogProductEntityDecimal . ' as cpedc',
            'main_table.mage_pro_row_id = cpedc.row_id and cpedc.store_id = 0 AND cpedc.attribute_id = ' . $proCostAttrId,
            ["cost" => "value"]
        );

        // commission_rate
        $proCommisionRateAttrId = $eavAttribute->getIdByCode("catalog_product", "commission_percent");
        $this->getSelect()->joinLeft(
            $catalogProductEntityDecimal . ' as cpedcr',
            'main_table.mage_pro_row_id = cpedcr.row_id and cpedcr.store_id = 0 AND cpedcr.attribute_id = ' . $proCommisionRateAttrId,
            ["commission_percent" => "value"]
        );

        // first enabled date
        $firstEnabledDateAttr = $eavAttribute->getIdByCode("catalog_product", "first_enabled_date");
        $this->getSelect()->joinLeft(
            $catalogProductEntityDatetime . ' as cpedt',
            'main_table.mage_pro_row_id = cpedt.row_id and cpedt.store_id = 0 AND cpedt.attribute_id = ' . $firstEnabledDateAttr,
            ["first_enabled_date" => "value"]
        );

        // Add need_to_refill attribute
        $this->getSelect()->joinLeft(
            $catalogProductEntityInt . ' as cpen2r',
            'main_table.mage_pro_row_id = cpen2r.row_id and cpen2r.store_id = 0 AND cpen2r.attribute_id = ' . $prodNeedToRefillAttrId,
            ["need_to_refill" => "value"]
        );

        $this->getSelect()->join(
            $catalogInventoryStockItem . ' as csi',
            'main_table.mageproduct_id = csi.product_id',
            ["qty" => "qty"]
        )->where("csi.website_id = 0 OR csi.website_id = 1");
        $flagsTable = $this->getTable('marketplace_productflags');
        $this->getSelect()->joinLeft(
            $flagsTable . ' as flagTable',
            'main_table.mage_pro_row_id = flagTable.product_id',
            [
                'flagcount' => 'count(flagTable.entity_id)'
            ]
        );

        $this->getSelect()->group('mage_pro_row_id');
//        parent::_renderFiltersBefore();
    }

    public function addFieldToFilter($field, $condition = null)
    {
        $this->_filtered[] = $field;
        if($field==='updated_at'){
            $field='cpe.updated_at';
        }
        return parent::addFieldToFilter($field, $condition); // TODO: Change the autogenerated stub
    }

    /**
     * Add select order
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::setOrder($field, $direction);
    }

    /**
     * Sets order and direction.
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::addOrder($field, $direction);
    }

    /**
     * Add select order to the beginning
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function unshiftOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::unshiftOrder($field, $direction);
    }
}
