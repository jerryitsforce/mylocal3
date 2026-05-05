<?php

namespace Branch8\EventTicket\Controller\Handle;

use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;

class SetUsed extends Action implements HttpPostActionInterface
{
    /** @var Validator */
    protected $formKeyValidator;

    /** @var CustomerSession */
    protected $customerSession;

    protected $timezone;

    protected $resourceConnection;

    protected $conn;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Validator $formKeyValidator,
        CustomerSession $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->resourceConnection = $resourceConnection;
        $this->conn = $this->resourceConnection->getConnection();
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->returnToHomePage();
        }

        $customer   = $this->customerSession->getCustomer();
        $customerId = $customer->getId();

        if (empty($customerId)) {
            return $this->returnToHomePage();
        }

        $eventId    = $this->getRequest()->getPost('product_id');
        $serialNumber = $this->getRequest()->getPost('serial_number');
        $serialData = $this->getEventTicketSerial($eventId, $serialNumber);
        if(empty($serialData)){
            $this->messageManager->addErrorMessage(__("Ticket no longer exists or has been used."));
            return $this->returnPreviousPage();
        }

        if(!$this->canUseSerial($serialData)){
            $this->messageManager->addErrorMessage(__("Ticket use time error."));
            return $this->returnPreviousPage();
        }
        try{
            $recordId = $serialData['record_id'];
            $this->setUsed($recordId);
        }catch (\Exception $e){
            $this->messageManager->addErrorMessage(__("Can't use this serial. Please try again or call support."));
            return $this->returnPreviousPage();
        }

        return $this->returnPreviousPage();
    }

    protected function getEventTicketSerial($eventId, $serialNumber){
        $conn = $this->conn;
        $serialQuery = $conn->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['record_id' => 'entity_id', 'start_date', 'end_date', 'status'])
            ->joinLeft(['event' => 'ticket_event'], 'ticket.event_id = event.entity_id', [])
            ->where('serial_number = ?', $serialNumber)
            ->where('status = ?', \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED)
            ->where('event_id = ?', $eventId)
            ->where('is_enable = ?', 1);
        return $conn->fetchRow($serialQuery);
    }

    protected function returnPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }

    protected function canUseSerial($serialData)
    {
        $startDate = $serialData['start_date'];
        $endDate = $serialData['end_date'];
        $currentDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        if(
            !(strtotime($startDate) < strtotime($currentDate) && strtotime($currentDate) < strtotime($endDate))
        ){
            return false;
        }
        return true;
    }

    protected function setUsed($recordId)
    {
        try{
            /**
             * Update serial status to used
             * Update transaction no
             * Update store no
             * Update redeemed at
             */
            $this->conn->beginTransaction();
            $usedDate = $this->timezone->date();
            $customerTicketUsedDate = $usedDate->format('Y-m-d H:i:s');
            $redeemed_at = $this->timezone->convertConfigTimeToUtc($usedDate);
            $updateQuery = [
                'redeemed_at' => $redeemed_at,
                'status' => TicketStatus::STATUS_USED,
                'used_transaction_no' => '',
                'used_store_no' => 'Frontend used'
            ];
            $this->conn->update('ticket_event_ticket', $updateQuery, 'entity_id='.$recordId);

            /**
             * Update to customer ticket as index field
             * redeemed_at in customer_ticket have to TW time, not UTC time(this is requirement, to match product ticket)
             */

            $this->conn->update(
                'customer_ticket',
                [
                    'redeemed_at' => $customerTicketUsedDate,
                    'status' => TicketStatus::STATUS_USED,
                    'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
                ],
                'ticket_table_name = "ticket_event_ticket" and ticket_table_record_id='.$recordId
            );

            $this->conn->commit();
        }catch (\Exception $e){
            $this->conn->rollBack();
            throw new \Exception(__("Can't use this serial. Please try again or call support.")->render());
        }
    }
}