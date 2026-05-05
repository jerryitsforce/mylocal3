<?php
declare(strict_types=1);

namespace Branch8\Sales\Rewrite\Magento\Sales\Block\Adminhtml\Order;

use Branch8\Sales\ViewModel\StatusResolver;

class View extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * @inheritDoc
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        $onclick = "submitAndReloadAreas($('order_history_block').parentNode, '" . $this->getSubmitUrl() . "')";
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Submit Comment'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );
        $this->setChild('submit_button_custom', $button);
        $order = $this->getOrder();
        $canShipping = in_array($order->getStatus(), StatusResolver::shippingStatues());
        if (!$canShipping) {
            $this->removeButton('order_ship');
        }

        // if ($order->getStatus() === \Branch8\HotaiCore\Model\Order\Status::STATUS_PENDING_PAYMENT) {
            $this->removeButton('order_cancel');
        // }
        return $this;
    }
}
