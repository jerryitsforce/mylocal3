<?php

namespace HotaiConnected\OpenHub\Helper;

use HotaiConnected\OpenHub\Helper\Api as ApiHelper;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\CollectionFactory;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\Collection;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecordRepository;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Psr\Log\LoggerInterface;

class Flow
{
    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var OpenHubTicketRecordRepository */
    protected $openHubTicketRecordRepository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    public function __construct(
        ApiHelper $apiHelper,
        CollectionFactory $collectionFactory,
        OpenHubTicketRecordRepository $openHubTicketRecordRepository,
        LoggerInterface $logger,
        CustomerTicketRepository $customerTicketRepository
    ) {
        $this->apiHelper                        = $apiHelper;
        $this->collectionFactory                = $collectionFactory;
        $this->openHubTicketRecordRepository    = $openHubTicketRecordRepository;
        $this->logger                           = $logger;
        $this->customerTicketRepository         = $customerTicketRepository;
    }

    /**
     * 取消/作廢票券
     *
     * @param int $orderItemId
     * @return void
     * @throws \Exception
     */
    public function cancelTickets(int $orderItemId): void
    {
        $this->logger->info('[openhub_flow] Starting ticket cancellation process', [
            'order_item_id' => $orderItemId
        ]);

        // 使用現有的退貨方法進行票券作廢
        $this->openHubTicketRecordRepository->refundTicketsByOrderItemId($orderItemId);

        $this->logger->info('[openhub_flow] Ticket cancellation completed', [
            'order_item_id' => $orderItemId
        ]);
    }

    /**
     * 退貨流程 - 使用現有的 OpenHub 退貨實作
     *
     * @param int $orderItemId
     * @return void
     * @throws \Exception
     */
    public function returnTickets(int $orderItemId): void
    {
        $this->logger->info('[openhub_flow] Starting ticket return process', [
            'order_item_id' => $orderItemId
        ]);

        // 直接使用現有的 OpenHub 退貨方法
        $this->openHubTicketRecordRepository->refundTicketsByOrderItemId($orderItemId);

        $this->logger->info('[openhub_flow] Ticket return process completed', [
            'order_item_id' => $orderItemId
        ]);
    }

    /**
     * 取得可取消的票券集合
     *
     * @param int $orderItemId
     * @return Collection
     */
    protected function getCollection(int $orderItemId): Collection
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            OpenHubTicketRecord::SALES_ORDER_ITEM_ID,
            (string) $orderItemId
        );
        
        // 只有未使用狀態的票券才能作廢
        $collection->addFieldToFilter(
            OpenHubTicketRecord::STATUS,
            TicketStatus::STATUS_UNUSED
        );

        $collection->load();

        return $collection;
    }

    /**
     * 取得可退貨的票券集合
     *
     * @param int $orderItemId
     * @return Collection
     */
    protected function getReturnableTicketsCollection(int $orderItemId): Collection
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            OpenHubTicketRecord::SALES_ORDER_ITEM_ID,
            (string) $orderItemId
        );
        
        // 可退貨的票券狀態：未使用或已發送
        $collection->addFieldToFilter(
            OpenHubTicketRecord::STATUS,
            ['in' => [TicketStatus::STATUS_UNUSED, TicketStatus::STATUS_SENT]]
        );

        $collection->load();

        return $collection;
    }
}