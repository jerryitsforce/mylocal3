<?php
declare(strict_types=1);

namespace Branch8\HelpDeskNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Dismiss extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->authSession = $authSession;
    }

    /**
     * Dismiss the notification for the current session
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->authSession->setShownTicketPopup(true);
        
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_HelpDeskNotification::notification');
    }
}
