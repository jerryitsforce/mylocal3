<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\Notification;

use Magento\Backend\App\Action;

class History extends Action
{
    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend(__('Send History of Notification #%1', $id));
        $this->_view->renderLayout();
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magenest_NotificationBox::notification');
    }
}
