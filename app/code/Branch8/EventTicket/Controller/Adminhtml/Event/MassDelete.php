<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
class MassDelete extends Action
{
    protected $connection;

    protected $filter;

    protected $ticketEventCollectionFactory;
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Branch8\EventTicket\Model\ResourceModel\TicketEvent\CollectionFactory $ticketEventCollectionFactory
    ){
        parent::__construct($context);
        $this->connection = $resource->getConnection();
        $this->filter = $filter;
        $this->ticketEventCollectionFactory = $ticketEventCollectionFactory;
    }

    public function execute(){
        $collection = $this->filter->getCollection($this->ticketEventCollectionFactory->create());
        foreach ($collection as $model) {
            /**
             * Get all serial to check mapping
             */
            $eventId = $model->getId();
            $serialQuery = $this->connection->select()
                ->from(['ticket' => 'ticket_event_ticket'], [])
                ->columns(['cnt' => 'count(*)'])
                ->where('event_id='.$eventId.' and status in('.TicketStatus::STATUS_UNUSED.', '.TicketStatus::STATUS_USED.')');
            $cnt = $this->connection->fetchOne($serialQuery);
            if($cnt > 0){
                $this->messageManager->addErrorMessage(__("Event ID %1 can't be deleted. The serial numbers are mapped.", $eventId));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/listEvent');
            }else {
                try {
                    $this->connection->beginTransaction();
                    $this->connection->delete('customer_ticket', 'ticket_table_name="ticket_event_ticket" and ticket_table_record_id in (select entity_id from ticket_event_ticket where event_id='.$eventId.')');
                    $this->connection->delete('ticket_event', 'entity_id='.$eventId);
                    $this->connection->commit();
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__("Event ID %1 can't be deleted."));
                    return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/listEvent');
                }
            }

        }
        $this->messageManager->addSuccessMessage(__('Delete successfully'));
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/listEvent');
    }
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }
}