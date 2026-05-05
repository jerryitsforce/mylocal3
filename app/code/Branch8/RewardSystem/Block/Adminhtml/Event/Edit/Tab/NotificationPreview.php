<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

class NotificationPreview extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('event/edit/tab/notification_preview.phtml');
    }
}
