<?php

namespace Branch8\MarketplaceSubAccount\Plugin\Model\ResourceModel\SubAccount\FrontGrid;

class Collection
{
    public function around_renderFiltersBefore($subject, $process)
    {
        $joinTable = $subject->getTable('customer_grid_flat');
        $subject->getSelect()->join(
            $joinTable.' as cgf',
            'main_table.customer_id = cgf.entity_id',
            [
                'name' => 'name',
                'email' => 'email',
                'customer_created_at' => 'created_at'
            ]
        );
        $sellerId = $subject->helperData->getCustomerId();
        $currentSubAccount = $subject->helperData->getCurrentSubAccount();
        if ($currentSubAccount->getId()) {
            $sellerId = $currentSubAccount->getSellerId();
        }
        $subject->getSelect()->where("main_table.seller_id = ".$sellerId);
    }
}