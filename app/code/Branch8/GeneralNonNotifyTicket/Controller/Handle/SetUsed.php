<?php

namespace Branch8\GeneralNonNotifyTicket\Controller\Handle;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Model\Session as CustomerSession;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecordRepository;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;

class SetUsed extends Action implements HttpPostActionInterface
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var Validator */
    protected $formKeyValidator;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var GeneralNonNotifyTicketRecordRepository */
    protected $generalNonNotifyTicketRecordRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var EventManager */
    protected $eventManager;
    
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone */
    protected $timezone;


    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        Validator $formKeyValidator,
        CustomerSession $customerSession,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        GeneralNonNotifyTicketRecordRepository $generalNonNotifyTicketRecordRepository,
        ResourceConnection $resourceConnection,
        OrderItemRepository $orderItemRepository,
        EventManager $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->hotaiCoreCommonHelper                  = $hotaiCoreCommonHelper;
        $this->formKeyValidator                       = $formKeyValidator;
        $this->customerSession                        = $customerSession;
        $this->customerTicketCollectionFactory        = $customerTicketCollectionFactory;
        $this->generalNonNotifyTicketRecordRepository = $generalNonNotifyTicketRecordRepository;
        $this->connection                             = $resourceConnection->getConnection();
        $this->orderItemRepository                    = $orderItemRepository;
        $this->eventManager                           = $eventManager;
        $this->timezone = $timezone;
        return parent::__construct($context);
    }

    /**
     * View page action
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->returnToHomePage();
        }

        $productId    = $this->getRequest()->getPost('product_id');
        $serialNumber = $this->getRequest()->getPost('serial_number');

        if($this->customerSession->isLoggedIn()){
            $customer   = $this->customerSession->getCustomer();
            $customerId = $customer->getId();
    
            if (empty($customerId)) {
                return $this->returnToHomePage();
            }
            $customerTicketRecord = $this->getCustomerTicketRecord((int) $customerId, (int) $productId, $serialNumber);
        }else{
            $guestGiftBoxSession = $this->customerSession->getGuestGiftBoxSession();
            if(!$guestGiftBoxSession){
                // $this->messageManager->addErrorMessage(__('Invalid Request'));
                return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
            }
            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            $sessionEndTime = $guestGiftBoxSession['created_at'];
            if(strtotime($currentTime) > strtotime($sessionEndTime)){
                $this->messageManager->addErrorMessage(__('Your session has expired.'));
                return $this->_redirect('gift-order/giftBox/giftForm')->sendResponse();
            }
            $telephone = $guestGiftBoxSession['phone_number'];
            $customerTicketRecord = $this->getCustomerTicketRecordByPhone($telephone, (int) $productId, $serialNumber);

        }
        

        

        
        if (empty($customerTicketRecord)) {
            $this->messageManager->addErrorMessage(__("Ticket no longer exists or has been used."));
            return $this->returnPreviousPage();
        }

        $moduleRecord = $this->getModuleRecord($customerTicketRecord);
        if (empty($moduleRecord)) {
            $this->messageManager->addErrorMessage(__("Ticket no longer exists or has been used."));
            return $this->returnPreviousPage();
        }

        if (!$this->checkUseTime($moduleRecord)) {
            $this->messageManager->addErrorMessage(__("Ticket use time error."));
            return $this->returnPreviousPage();
        }

        $this->setUsedStatus($moduleRecord, $customerTicketRecord);

        $orderItem = $this->orderItemRepository->get((int) $customerTicketRecord->getSalesOrderItemId());
        $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_USE_API_HANDLE, [
            "orderId" => $orderItem->getOrderId()
        ]);

        return $this->returnPreviousPage();
    }

    protected function getCustomerTicketRecord(int $customerId, int $productId, string $serialNumber): null|CustomerTicket
    {
        $collection = $this->customerTicketCollectionFactory->create();
        $collection
            ->addFieldToFilter(CustomerTicket::TYPE, \Branch8\HotaiCore\Model\Product\VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET)
            ->addFieldToFilter(CustomerTicket::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(CustomerTicket::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(CustomerTicket::TICKET_UNIQUE_CONTENT, $serialNumber)
            ->addFieldToFilter(CustomerTicket::STATUS, \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED);

        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    public function getCustomerTicketRecordByPhone($telephone, int $productId, string $serialNumber): null|CustomerTicket
    {
        $collection = $this->customerTicketCollectionFactory->create();
        $collection
            ->addFieldToFilter(CustomerTicket::TYPE, \Branch8\HotaiCore\Model\Product\VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET)
            ->addFieldToFilter('telephone', $telephone)
            ->addFieldToFilter(CustomerTicket::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(CustomerTicket::TICKET_UNIQUE_CONTENT, $serialNumber)
            ->addFieldToFilter(CustomerTicket::STATUS, \Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED);

        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    protected function getModuleRecord(CustomerTicket $customerTicket): null|GeneralNonNotifyTicketRecord
    {
        $moduleRecord = $this->generalNonNotifyTicketRecordRepository->getByIdWithBatchingData($customerTicket->getTicketTableRecordId());

        return $moduleRecord;
    }

    protected function checkUseTime(GeneralNonNotifyTicketRecord $record): bool
    {
        $currentTimestamp = time();
        $startTimestamp   = strtotime($record->getUseStartTime() . " Asia/Taipei");
        $endTimestamp     = strtotime($record->getUseEndTime() . " Asia/Taipei");

        return ($startTimestamp <= $currentTimestamp) && ($currentTimestamp <= $endTimestamp);
    }

    protected function setUsedStatus(GeneralNonNotifyTicketRecord $moduleRecord, CustomerTicket $customerTicket): void
    {
        $this->connection->beginTransaction();

        $beforeStatus = $moduleRecord->getStatus();
        $afterStatus  = \Branch8\HotaiCore\Model\Ticket\Status::STATUS_USED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $moduleRecord->getData(GeneralNonNotifyTicketRecord::MEMO),
            [
                "Timestamp"    => time(),
                "Datetime(+8)" => $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject()->format("Y-m-d H:i:s"),
                "Title"        => "Set used status from frontend.",
                "Message"      => "Set GeneralNonNotifyTicket status to used, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        try {
            $updateData  = [
                GeneralNonNotifyTicketRecord::STATUS     => $afterStatus,
                GeneralNonNotifyTicketRecord::USED_DATE  => $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject()->format("Y-m-d H:i:s"),
                GeneralNonNotifyTicketRecord::USED_COUNT => $moduleRecord->getUsedCount() + 1,
                GeneralNonNotifyTicketRecord::MEMO       => $memoMessage,
            ];
            $whereUpdate = [
                GeneralNonNotifyTicketRecord::RECORD_ID . ' = ?' => $customerTicket->getTicketTableRecordId()
            ];
            $this->connection->update(
                GeneralNonNotifyTicketRecord::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            $updateDataCustomerTicket = [
                CustomerTicket::STATUS      => $afterStatus,
                CustomerTicket::REDEEMED_AT => $updateData[GeneralNonNotifyTicketRecord::USED_DATE]
            ];
            $whereUpdate              = [
                CustomerTicket::TYPE . ' = ?' => \Branch8\HotaiCore\Model\Product\VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET,
                CustomerTicket::RECORD_ID . ' = ?' => $customerTicket->getId()
            ];
            $this->connection->update(
                CustomerTicket::TABLE_NAME,
                $updateDataCustomerTicket,
                $whereUpdate
            );

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    protected function returnToHomePage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_url->getUrl(""));

        return $resultRedirect;
    }
    protected function returnPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}