<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\Notification\Recipient;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
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
     * @var Filter
     */
    private Filter $filter;


    private \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory $collectionFactory;

    /**
     * @param Action\Context $context
     * @param LoggerInterface $logger
     * @param Filter $filter
     * @param \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Action\Context                                                                       $context,
        LoggerInterface                                                                      $logger,
        Filter                                                                               $filter,
        \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory $collectionFactory,
        ResourceConnection                                                                   $resourceConnection
    )
    {
        parent::__construct($context);
        $this->logger = $logger;
        $this->filter = $filter;
        $this->connection = $resourceConnection->getConnection();
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $notificationId = (int)$this->getRequest()->getParam('notification_id');
        $table = $this->connection->getTableName('magenest_customer_notification');
        try {
            $collection = $this->filter->getCollection(
                $this->collectionFactory->create()->addFieldToFilter('notification_id', $notificationId)
            );
            $batchSize = 10000;
            $ids = $collection->load()->getAllIds();
            $batches = array_chunk($ids, $batchSize);
            $deleted = 0;
            foreach ($batches as $batch) {
                $deleted += $this->connection->delete($table, ['entity_id IN (?)' => (array)$batch,
                    'notification_id = ?' => $notificationId]);
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $deleted));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
    }
}
