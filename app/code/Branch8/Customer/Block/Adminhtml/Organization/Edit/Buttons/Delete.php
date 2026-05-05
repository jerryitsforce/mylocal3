<?php

namespace Branch8\Customer\Block\Adminhtml\Organization\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete extends Generic implements ButtonProviderInterface{

    public function getButtonData(){
        return [
            'label' => __('Delete'),
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this organization?') . '\', \'' . $this->getDeleteUrl() . '\')',
            'class' => 'delete',
            'sort_order' => 20
        ];
    }

    public function getDeleteUrl(){
        return $this->getUrl('*/*/delete', ['id' => $this->getEntityId()]);
    }
}