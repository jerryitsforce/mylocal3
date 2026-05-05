<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid;

class CollectionStaff extends \Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid\Collection{

    /**
     * @inheritdoc
     *
     * @throws \Zend_Db_Select_Exception
     */
    protected function _renderFiltersBefore(): void
    {
        parent::_renderFiltersBefore();
        $select = $this->getSelect();
    }

}