<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Renderer;
use Branch8\SellerContactInformation\Model\Config\Source\ContractStatus;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row){
        $isActive = $row->getIsActive();
        $actionHtml = '';
        if($isActive == ContractStatus::STATUS_ACTIVE){
            $actionHtml = '<a href="'.$this->_urlBuilder->getUrl('marketplace/contractFile/edit', ['cid' => $row['entity_id'], 'seller_id' => $row['seller_id']]).'">'.__('Edit').'</a>';
        }else if($isActive == ContractStatus::STATUS_PENDING){
            $actionHtml = '<a href="'.$this->_urlBuilder->getUrl('marketplace/contractFile/edit', ['cid' => $row['entity_id'], 'seller_id' => $row['seller_id']]).'">'.__('Edit').'</a> / '.
                '<a onClick="return !confirm(\''.__('Are you sure you want to delete the selected contract?\'').') ? false : window.location.href=\''.$this->_urlBuilder->getUrl('marketplace/contractFile/delete', ['seller_id' => $row['seller_id'], 'entity_id' => $row['entity_id']]).'\';" href="javascript:;">'.__('Delete').'</a>';
        }else if($isActive == ContractStatus::STATUS_EXPIRED){
            $actionHtml = '';
        }

        return $actionHtml;
    }
}
