<?php

namespace Branch8\TicketApi\Service;

use Magento\Framework\App\ResourceConnection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord;

class CustomerTicketService
{
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $connection;

    /** @var CustomerTicketFactory */
    private $customerTicketFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerTicketFactory $customerTicketFactory
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->customerTicketFactory = $customerTicketFactory;
    }

    /**
     * Get CustomerTicket record by OpenHub record
     * 
     * @param OpenHubTicketRecord $openHubRecord
     * @return CustomerTicket|null
     */
    public function getCustomerTicketByOpenHubRecord(OpenHubTicketRecord $openHubRecord): ?CustomerTicket
    {
        $select = $this->connection->select()
            ->from(CustomerTicket::TABLE_NAME)
            ->where(CustomerTicket::TYPE . ' = ?', VirtualProductType::TYPE_OPENHUB_TICKET)
            ->where(CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?', $openHubRecord->getRecordId())
            ->limit(1);
            
        $customerTicketData = $this->connection->fetchRow($select);
        
        if (!$customerTicketData) {
            return null;
        }
        
        $customerTicket = $this->customerTicketFactory->create();
        $customerTicket->setData($customerTicketData);
        
        return $customerTicket;
    }

    /**
     * Update CustomerTicket status to used
     * 
     * @param OpenHubTicketRecord $openHubRecord
     * @return void
     */
    public function updateCustomerTicketToUsed(OpenHubTicketRecord $openHubRecord): void
    {
        $updateData = [
            CustomerTicket::STATUS => \Branch8\HotaiCore\Model\Ticket\Status::STATUS_USED,
            CustomerTicket::REDEEMED_AT => date("Y-m-d H:i:s")
        ];
        $whereUpdate = [
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_OPENHUB_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $openHubRecord->getRecordId()
        ];
        
        $this->connection->update(
            CustomerTicket::TABLE_NAME,
            $updateData,
            $whereUpdate
        );
    }

    /**
     * Update CustomerTicket status to unused (for cancel operations)
     * 
     * @param OpenHubTicketRecord $openHubRecord
     * @return void
     */
    public function updateCustomerTicketToUnused(OpenHubTicketRecord $openHubRecord): void
    {
        $updateData = [
            CustomerTicket::STATUS => \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED,
            CustomerTicket::REDEEMED_AT => null
        ];
        $whereUpdate = [
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_OPENHUB_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $openHubRecord->getRecordId()
        ];
        
        $this->connection->update(
            CustomerTicket::TABLE_NAME,
            $updateData,
            $whereUpdate
        );
    }

    /**
     * Check if ticket is unable to use yet (start time not reached)
     * 
     * @param OpenHubTicketRecord $model
     * @return bool
     */
    public function checkIfTicketUnableToUseYet(OpenHubTicketRecord $model): bool
    {
        $customerTicket = $this->getCustomerTicketByOpenHubRecord($model);
        if (!$customerTicket) {
            return false;
        }
        
        $useStartTime = $customerTicket->getData('use_start_time');
        return $model->getStatus() == \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED && 
               !empty($useStartTime) && 
               strtotime($useStartTime) > 0 && 
               time() < strtotime($useStartTime);
    }

    /**
     * Check if ticket is overdue (end time passed)
     * 
     * @param OpenHubTicketRecord $model
     * @return bool
     */
    public function checkIfTicketOverDue(OpenHubTicketRecord $model): bool
    {
        $customerTicket = $this->getCustomerTicketByOpenHubRecord($model);
        if (!$customerTicket) {
            return false;
        }
        
        $useEndTime = $customerTicket->getData('use_end_time');
        return !empty($useEndTime) && strtotime($useEndTime) > 0 && time() > strtotime($useEndTime);
    }
}