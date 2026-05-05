<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\Notification\Recipient;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;

class DeleteAll extends Action  implements HttpPostActionInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context            $context,
        LoggerInterface    $logger,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Notification ID is missing.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
        }

        try {
            $tableName = $this->connection->getTableName('magenest_customer_notification');
            $this->connection->delete($tableName, ['notification_id = ?' => $id]);
            $this->messageManager->addSuccessMessage(__('All recipients for notification ID %1 have been deleted.', $id));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/notification/history', ['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magenest_NotificationBox::notification');
    }
}
