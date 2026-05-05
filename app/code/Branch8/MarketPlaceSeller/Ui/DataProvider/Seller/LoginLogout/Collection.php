<?php

namespace Branch8\MarketPlaceSeller\Ui\DataProvider\Seller\LoginLogout;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{

    protected function _initSelect()
    {

//        $this->addFilterToMap('email', 'customer_email');
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['ce' => 'customer_entity'],
            'main_table.customer_id = ce.entity_id',
            ['email']
            )
            ->where('ce.platform="seller"')
            ->order('log_id desc');
//echo $this->getSelect();die;
        return $this;
    }
}