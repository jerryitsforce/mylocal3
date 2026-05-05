<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Grid Collection for Logistics Settings
 */
class Collection extends SearchResult
{
    /**
     * Override _initSelect to add join with marketplace_userdata
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        // Join marketplace_userdata table to get seller information
        $this->getSelect()->joinLeft(
            ['seller' => $this->getTable('marketplace_userdata')],
            'main_table.seller_id = seller.seller_id',
            [
                'seller_name' => new \Zend_Db_Expr(
                    'COALESCE(seller.shop_title, seller.company_name, CONCAT("Seller #", seller.seller_id))'
                ),
                'seller_code' => 'seller.seller_code'
            ]
        );

        return $this;
    }
}
