<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue\CollectionFactory as CustomerTicketOverDue;
use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Ticket extends AbstractHelper
{

    const PAGE_SIZE = 10;
    const TAB_PARAM = 't';
    const CUR_PAGE_PARAM = 'p';

    const TABS = [
        0 => 'tab-unused',
        1 => 'tab-used',
        2 => 'tab-overdue'
    ];

    protected $customerSession;

    /**
     * @var CustomerTicket
     */
    protected $ticketCollectionFactory;

    /**
     * @var CustomerTicketOverDue
     */
    protected $overDueTicketCollectionFactory;

    protected DateTime $dateTime;

    protected $timezone;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerTicketOverDue $overDueTicketCollectionFactory,
        CustomerTicket $ticketCollectionFactory,
        DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->customerSession = $customerSession;
        $this->overDueTicketCollectionFactory = $overDueTicketCollectionFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->dateTime = $dateTime;
        parent::__construct($context);
        $this->timezone = $timezone;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    public function getMemberSeq()
    {
        if(!$this->customerSession->getCustomer()->getDataModel()->getCustomAttribute('member_seq')) {
            return '';
        }
        return $this->customerSession->getCustomer()->getDataModel()->getCustomAttribute('member_seq')->getValue();
    }
    /**
     * TODO: real data
     * MOCK DATA
     */
    public function getTicketsCollection($status, $curPage)
    {
        $collection = $this->ticketCollectionFactory->create();
        $select = $collection->getSelect();
        $select->joinLeft(['ticket' => 'ticket_event_ticket'], 'ticket.entity_id = main_table.ticket_table_record_id and ticket_table_name="ticket_event_ticket"', ['ticket_status' => 'status', 'start_date', 'end_date'])
            ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url', 'seller_id', 'name', 'ticket_type', 'description']);
        if((string)$this->getMemberSeq() != ''){
            $collection->addFieldToFilter(['customer_id', 'member_seq'], [['eq' => $this->getCustomerId()], ['eq' => $this->getMemberSeq()]]);

        }else{
            $collection->addFieldToFilter('customer_id', $this->getCustomerId());
        }
        $collection->addFieldToFilter('main_table.status', ['eq' => $status]);
        if($status == Status::STATUS_UNUSED) {
            $collection->addFieldToFilter(['event.is_enable', 'event.is_enable'], [['null' => true], ['eq' => 1]]);
            $collection->getSelect()->where('main_table.use_end_time IS NULL OR main_table.use_end_time > "'. $this->timezone->date()->format('Y-m-d H:i:s').'"');
        }
        if($status == Status::STATUS_OVER_DUE){
            $collection->getSelect()->where('main_table.use_end_time <= "'. $this->timezone->date()->format('Y-m-d H:i:s').'"');
            $collection->addFieldToFilter('main_table.status', ['nin' => [Status::STATUS_USED]]);
        }
        $collection->setPageSize(self::PAGE_SIZE)
            ->setCurPage($curPage)
            ->setOrder('created_at', 'DESC');//echo $collection->getSelect();die;
        return $collection;
    }
    public function getTicketsCollectionOverdue($curPage)
    {
        $collection = $this->ticketCollectionFactory->create();
        $select = $collection->getSelect();
        $select->joinLeft(['ticket' => 'ticket_event_ticket'], 'ticket.entity_id = main_table.ticket_table_record_id and ticket_table_name="ticket_event_ticket"', [])
            ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url', 'seller_id', 'name', 'ticket_type', 'description']);
        if((string)$this->getMemberSeq() != ''){
            $collection->addFieldToFilter(['customer_id', 'member_seq'], [['eq' => $this->getCustomerId()], ['eq' => $this->getMemberSeq()]]);
        }else{
            $collection->addFieldToFilter('customer_id', $this->getCustomerId());
        }
        $collection->addFieldToFilter('main_table.status', ['nin' => [Status::STATUS_USED]]);
        $collection->getSelect()->where('main_table.use_end_time <= "'.$this->timezone->convertConfigTimeToUtc($this->timezone->date()).'"');
        $collection->setPageSize(self::PAGE_SIZE)
            ->setCurPage($curPage)
            ->setOrder('created_at', 'DESC');
        return $collection;
    }

    public function getTicketsCollectionCount($status){
        $collection = $this->getTicketsCollectionByStatus($status);
        return $collection->getSize();
    }

    public function getTicketsCollectionCountOverdue(){
        $collection = $this->ticketCollectionFactory->create();
        $select = $collection->getSelect();
        // $select->joinLeft(['ticket' => 'ticket_event_ticket'], 'ticket.entity_id = main_table.ticket_table_record_id and ticket_table_name="ticket_event_ticket"', ['ticket_status' => 'status'])
        //     ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url', 'seller_id', 'name', 'ticket_type']);
        if((string)$this->getMemberSeq() != ''){
            $collection->addFieldToFilter(['customer_id', 'member_seq'], [['eq' => $this->getCustomerId()], ['eq' => $this->getMemberSeq()]]);
        }else{
            $collection->addFieldToFilter('customer_id', $this->getCustomerId());
        }
        $collection->addFieldToFilter('main_table.status', ['nin' => [Status::STATUS_USED]]);
        $collection->getSelect()->where('main_table.use_end_time <= "'. $this->timezone->convertConfigTimeToUtc($this->timezone->date()).'"');

        return $collection->getSize();
    }


    public function getTicketsCollectionByStatus($status) {
        $collection = $this->ticketCollectionFactory->create();
        $collection->getSelect()
            ->joinLeft(['ticket' => 'ticket_event_ticket'], 'ticket.entity_id = main_table.ticket_table_record_id and ticket_table_name="ticket_event_ticket"', ['ticket_status' => 'status'])
            ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id', ['image_url', 'seller_id', 'name', 'ticket_type']);
        if((string)$this->getMemberSeq() != ''){
            $collection->addFieldToFilter(['customer_id', 'member_seq'], [['eq' => $this->getCustomerId()], ['eq' => $this->getMemberSeq()]]);
        }else{
            $collection->addFieldToFilter('customer_id', $this->getCustomerId());
        }
        $collection->addFieldToFilter('main_table.status', $status);
        if($status == Status::STATUS_UNUSED) {
            $collection->addFieldToFilter(['event.is_enable', 'event.is_enable'], [['null' => true], ['eq' => 1]]);
            $collection->getSelect()->where('main_table.use_end_time IS NULL OR main_table.use_end_time > "'. $this->timezone->date()->format('Y-m-d H:i:s').'"');
        }
        if($status == Status::STATUS_OVER_DUE){
            $collection->getSelect()->where('main_table.use_end_time <= "'. $this->timezone->date()->format('Y-m-d H:i:s').'"');
            $collection->addFieldToFilter('main_table.status', ['nin' => [Status::STATUS_USED]]);
        }
        return $collection;
    }

    public function countExpiringUnusedTickets(int $withinXDay = 30)
    {
        $collection = $this->getTicketsCollectionByStatus(Status::STATUS_UNUSED);
        $currentDateTime = $this->timezone->date();
        $currentDate = $currentDateTime->format('Y-m-d H:i:s');
        $thirtyDaysLater = $currentDateTime->add(new \DateInterval('PT'.$withinXDay.'H'))->format('Y-m-d H:i:s');


        $collection->getSelect()->where(
            sprintf("(
                    (
                        main_table.ticket_table_name <> 'ticket_event_ticket'
                        AND
                        (`main_table`.`use_end_time` >= '%s' AND `main_table`.`use_end_time` <= '%s')
                    )
                    OR
                    (
                        main_table.ticket_table_name = 'ticket_event_ticket'
                        AND
                        (`ticket`.`end_date` >= '%s' AND `ticket`.`end_date` <= '%s')
                    )
                )",
                $currentDate,
                $thirtyDaysLater,
                $currentDate,
                $thirtyDaysLater,
            )

        );

        return $collection->getSize();
    }

    /**
     * TODO: real data
     * MOCK DATA
     */
    public function getUsedTicketCollection($request)
    {
        $curPage = $this->getCurPage($request, self::TABS[1]);
        return $this->getTicketsCollection(Status::STATUS_USED, $curPage);
    }

    /**
     * TODO: real data
     * MOCK DATA
     */
    public function getUnusedTicketCollection($request)
    {
        $curPage = $this->getCurPage($request, self::TABS[0]);
        return $this->getTicketsCollection(Status::STATUS_UNUSED, $curPage);
    }

    /**
     * TODO: real data
     * MOCK DATA
     */
    public function getOverDueTicketCollection($request)
    {
        $curPage = $this->getCurPage($request, self::TABS[2]);
        return $this->getTicketsCollectionOverdue($curPage);
    }

    public function getOverDueTicketCollectionCount()
    {
        return $this->getTicketsCollectionCountOverdue();
    }

    public function getActiveTab($request)
    {
        return $request->getParam(self::TAB_PARAM) ?? self::TABS[0];
    }

    public function getCurPage($request, $tabId)
    {
        $result = "1";
        if ($this->getActiveTab($request) === $tabId) {
            $result = $request->getParam(self::CUR_PAGE_PARAM, $result);
            $result = $this->sanitizeInput($result);
            $result = $result ? (int)$result : 1;
        }
        return max($result, 1);
    }

    /**
     * Sanitize the input to prevent XSS and SQL Injection
     *
     * @param mixed $input
     * @return mixed
     */
    protected function sanitizeInput($input)
    {
        if (is_array($input)) {
            foreach ($input as &$value) {
                $value = $this->sanitizeInput($value);
            }
            return $input;
        }

        // Trim and remove HTML special characters
        if(!empty($input)) {
            $input = trim($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $input = filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
            $input = htmlspecialchars($input);
        }

        return $input;
    }

    public function isGiftEventTicket($data){
        if(isset($data['ticket_table_name']) && $data['ticket_table_name'] == 'ticket_event_ticket'){
            return true;
        }
        return false;
    }

    public function getGiftTicket($recordId)
    {
        $connection = $this->ticketCollectionFactory->create()->getConnection();
        $select = $connection->select()
            ->from(['ticket' => 'ticket_event_ticket'], ['ticket_id' => 'entity_id', 'ticket_status' => 'status', 'serial_number', 'use_start_time' => 'start_date', 'use_end_time' => 'end_date'])
            ->joinLeft(['event' => 'ticket_event'], 'event.entity_id = ticket.event_id',
                [
                    'image_url', 'seller_id', 'name', 'ticket_type',
                    'display_type', 'barcode_type', 'description',
                    'is_offline_operation', 'exchange_url', 'event_id' => 'entity_id',
                    'display_serial_number', 'exchange_hint'
                ])
            ->where('ticket.entity_id = '.$recordId)
            ->where('event.is_enable = 1');

        return $connection->fetchRow($select);
    }
}
