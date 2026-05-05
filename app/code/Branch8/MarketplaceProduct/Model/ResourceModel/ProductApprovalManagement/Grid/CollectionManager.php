<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;

class CollectionManager extends \Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid\Collection{

    /**
     * @inheritdoc
     *
     * @throws \Zend_Db_Select_Exception
     */
    protected function _renderFiltersBefore(): void
    {
        parent::_renderFiltersBefore();
        $select = $this->getSelect();
        $select->where('(at_product_version.commission_percent < 0 OR at_product_version.is_variation_commission_rate_negative=1)')
            ->where('at_product_version.approval_flow_status = ?', ApprovalFlowStatus::MANAGER_PENDING_APPROVAL);
    }

}