<?php

namespace Branch8\MarketplaceProduct\Model\Config\Source;

class ApprovalFlowStatus implements \Magento\Framework\Option\ArrayInterface
{
    const DISTRIBUTOR_PENDING_APPROVAL = 1;

    const CURATOR_PENDING_APPROVAL = 2;

    const MANAGER_PENDING_APPROVAL = 3;

    const APPROVAL_GRANTED_FINAL_APPROVE = 4;

    const APPROVAL_REJECTED = 5;
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DISTRIBUTOR_PENDING_APPROVAL, 'label' => __('Dealer Pending Approval')],
            ['value' => self::CURATOR_PENDING_APPROVAL, 'label' => __('Curator Pending Approval')],
            ['value' => self::MANAGER_PENDING_APPROVAL, 'label' => __('Manager Pending Approval')],
            ['value' => self::APPROVAL_GRANTED_FINAL_APPROVE, 'label' => __('Approval Granted Final Approve')],
            ['value' => self::APPROVAL_REJECTED, 'label' => __('Approval Rejected')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::DISTRIBUTOR_PENDING_APPROVAL => __('Dealer Pending Approval'),
            self::CURATOR_PENDING_APPROVAL => __('Curator Pending Approval'),
            self::MANAGER_PENDING_APPROVAL => __('Manager Pending Approval'),
            self::APPROVAL_GRANTED_FINAL_APPROVE => __('Approval Granted Final Approve'),
            self::APPROVAL_REJECTED => __('Approval Rejected')
        ];
    }

}