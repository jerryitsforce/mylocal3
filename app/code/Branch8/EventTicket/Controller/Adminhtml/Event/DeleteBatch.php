<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class DeleteBatch extends Action
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
        $batchCode = $this->getRequest()->getParam('batch_code');
        $conn = $this->resourceConnection->getConnection();
        try {
            $conn->beginTransaction();
            $sqlSerialIds = $conn->select()
                ->from(['ticket' => 'ticket_event_ticket'], ['entity_id'])
                ->where('event_id='.$eventId.' and batch_code="'.$batchCode.'"');
            $serialIds = $conn->fetchCol($sqlSerialIds);
            $batchSize = 10000;
            $numBatch = ceil(count($serialIds)/ $batchSize);
            $splitSerialArr = array_chunk($serialIds, $numBatch);
            $deletedCnt = 0;
            foreach($splitSerialArr as $_arr){
                $customerTicketCheckQuery = 'select ticket_table_record_id from customer_ticket 
                    where ticket_table_name="ticket_event_ticket" and batch_code="'.$batchCode.'"  
                    and ticket_table_record_id in('.implode(',', $_arr).') 
                    and (member_seq is null and telephone is null and (customer_id=0 OR customer_id is NULL))
                    ';
                $validRecordIds = $conn->fetchCol($customerTicketCheckQuery);
                if(!empty($validRecordIds)){
                    $conn->delete('customer_ticket', 'ticket_table_name="ticket_event_ticket" and batch_code="'.$batchCode.'" and ticket_table_record_id in('.implode(',', $validRecordIds).')');
                    $deletedCnt += $conn->delete('ticket_event_ticket', 'entity_id in('.implode(',', $validRecordIds).')');
                }
            }

            $conn->commit();

            $resultJson->setData(['error' => false, 'deleted_count' => $deletedCnt]);

        }catch(\Exception $e){
            $conn->rollBack();
            $resultJson->setData(['error' => true]);
        }
        return $resultJson;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }

}