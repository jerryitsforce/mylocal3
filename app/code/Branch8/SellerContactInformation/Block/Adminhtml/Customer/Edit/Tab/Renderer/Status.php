<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Renderer;

use Branch8\SellerContactInformation\Model\Config\Source\ContractStatus;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row){
        $isActive = $row->getIsActive();
        $status = __('N/A');
        if($isActive == ContractStatus::STATUS_PENDING){
            $status = __('Pending');
        }else if($isActive == ContractStatus::STATUS_ACTIVE){
            $status = __('Active');
        }else if($isActive == ContractStatus::STATUS_EXPIRED){
            $status = __('Expired');
        }
        
        return $status->__toString();
    }
}