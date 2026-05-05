<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class ListBatchCode extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                  $context,
        JsonFactory                                          $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute(){

        $resultJson = $this->resultJsonFactory->create();
        $eventId = $this->getRequest()->getParam('event_id');
        $conn = $this->resourceConnection->getConnection();
        try {
            $select = $conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], [
                    'batch_code', 'total_serial' => 'count(entity_id)', 'start_date', 'end_date',
                    'mapped' => $conn->select()
                        ->from(['ticket_sub_mapped' => 'ticket_event_ticket'], ['cnt' => 'count(entity_id)'])
                        ->where('ticket_sub_mapped.event_id='.$eventId.' and ticket_sub_mapped.batch_code=ticket.batch_code and (ticket_sub_mapped.status='.TicketStatus::STATUS_UNUSED.' OR ticket_sub_mapped.status='.TicketStatus::STATUS_USED.')'),
                    'used' => $conn->select()
                        ->from(['ticket_sub_used' => 'ticket_event_ticket'], ['cnt' => 'count(entity_id)'])
                        ->where('ticket_sub_used.event_id='.$eventId.' and ticket_sub_used.batch_code=ticket.batch_code and ticket_sub_used.status='.TicketStatus::STATUS_USED)
                ])
                ->where('event_id=' . $eventId)
                ->group('batch_code');
            $batchCodes = $conn->fetchAll($select);
            $this->_view->loadLayout();
            $layout = $this->_view->getLayout();
            $contentBlock = $layout->getBlock('event.batch_code.list');
            $contentBlock->setData('batch_code', $batchCodes);
            $contentBlock->setData('event_id', $eventId);
            $html = $contentBlock->toHtml();

            $resultJson->setData(['error' => false, 'html' => $html]);
        }catch(\Exception $e){echo $e->getMessage();
            $resultJson->setData(['error' => true]);
        }
        return $resultJson;
    }
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }

}