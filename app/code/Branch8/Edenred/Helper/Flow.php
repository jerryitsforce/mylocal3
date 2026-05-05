<?php

namespace Branch8\Edenred\Helper;

use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Model\EdenredTicketRecord;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\CollectionFactory;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\Collection;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class Flow
{
    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    public function __construct(
        ApiHelper $apiHelper,
        CollectionFactory $collectionFactory,
        EdenredTicketRecordRepository $edenredTicketRecordRepository
    ) {
        $this->apiHelper                     = $apiHelper;
        $this->collectionFactory             = $collectionFactory;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
    }

    public function cancelTickets(int $orderItemId): void
    {
        $voucherNoArray = [];
        $collection     = $this->getCollection($orderItemId);

        /** @var EdenredTicketRecord $record */
        foreach ($collection->getItems() as $record) {
            $voucherNoArray[] = $record->getEdenredVoucherNo();
        }

        $apiResponse = $this->apiHelper->requestApiCancelMultiVouchersWithCustomVoucherNoArray($orderItemId, $voucherNoArray);

        $this->edenredTicketRecordRepository->setCanceledStatusToEdenredTicketRecordsInDb($collection, $apiResponse);
    }

    protected function getCollection(int $orderItemId): Collection
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            EdenredTicketRecord::SALES_ORDER_ITEM_ID,
            (string) $orderItemId
        );
        $collection->addFieldToFilter(
            EdenredTicketRecord::STATUS,
            TicketStatus::STATUS_UNUSED
        );

        $collection->load();

        return $collection;
    }
}
