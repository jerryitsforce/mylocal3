<?php

namespace Branch8\Spin2Win\Rewrite\Block\Adminhtml\Spin;

class Edit extends \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit
{
    protected function _prepareLayout()
    {
        $message = __('Do you want to publish the event?');
        $spinInfo = $this->_coreRegistry->registry('spininfo');
        $spinId = $spinInfo->getId();
        $this->buttonList->add(
            'publish',
            [
                'label' => __('Publish'),
                'class' => 'publish',
                'onclick' => "confirmSetLocation('{$message}', '{$this->_getPublishUrl($spinId)}')",
            ],
            10
        );

        return parent::_prepareLayout();
    }
    protected function _getPublishUrl($spinId)
    {
        return $this->getUrl('spintowin/manage/publish', ['spin_id' => $spinId, '_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}
