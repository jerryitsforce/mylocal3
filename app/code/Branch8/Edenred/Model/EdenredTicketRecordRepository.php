<?php

namespace Branch8\Edenred\Model;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\Edenred\Api\Data\EdenredTicketRecordSearchResultsInterfaceFactory;
use Branch8\Edenred\Api\EdenredTicketRecordRepositoryInterface;
use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\Edenred\Model\EdenredTicketRecord as EdenredTicketRecordModel;
use Branch8\Edenred\Model\EdenredTicketRecordFactory;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\Collection as EdenredTicketRecordCollection;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\CollectionFactory as EdenredTicketRecordCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order\Item;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class EdenredTicketRecordRepository implements EdenredTicketRecordRepositoryInterface
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var EdenredTicketRecord */
    protected $resource;

    /** @var EdenredTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var EdenredTicketRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var EdenredTicketRecordFactory */
    protected $edenredTicketRecordFactory;

    /** @var CustomerTicketFactory */
    protected $customerTicketFactory;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var Transaction */
    protected $transaction;

    protected $customerCollectionFactory;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        EdenredTicketRecord $resource,
        EdenredTicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        EdenredTicketRecordSearchResultsInterfaceFactory $searchResultsFactory,
        EdenredTicketRecordFactory $edenredTicketRecordFactory,
        CustomerTicketFactory $customerTicketFactory,
        CustomerTicketRepository $customerTicketRepository,
        MarketplaceHelper $marketplaceHelper,
        Transaction $transaction,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->commonHelper               = $commonHelper;
        $this->resource                   = $resource;
        $this->collectionFactory          = $collectionFactory;
        $this->collectionProcessor        = $collectionProcessor;
        $this->searchResultsFactory       = $searchResultsFactory;
        $this->edenredTicketRecordFactory = $edenredTicketRecordFactory;
        $this->customerTicketFactory      = $customerTicketFactory;
        $this->customerTicketRepository   = $customerTicketRepository;
        $this->marketplaceHelper          = $marketplaceHelper;
        $this->transaction                = $transaction;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(
        \Branch8\Edenred\Api\Data\EdenredTicketRecordInterface $record
    ) {
        try {
            /** @var \Branch8\Edenred\Model\EdenredTicketRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the EdenredTicketRecord: %1',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($criteria);
        $searchResult->setItems($collection->getData());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    public function getById(int $id): ?EdenredTicketRecordModel
    {
        /** @var EdenredTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(EdenredTicketRecordModel::RECORD_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 根據sales_order_item_id找到對應的宜睿票券紀錄
     *
     * @param integer $orderItemId
     * @return EdenredTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): EdenredTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(EdenredTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }

    /**
     * 根據sales_order_item_id和票券狀態找到對應的宜睿票券紀錄
     *
     * @param integer $orderItemId
     * @return EdenredTicketRecordCollection
     */
    public function getRecordsByOrderItemIdAndStatus(int $orderItemId, int $edenredTicketRecordStatus): EdenredTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(EdenredTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId)
            ->addFieldToFilter(EdenredTicketRecordModel::STATUS, $edenredTicketRecordStatus);

        $collection->load();

        return $collection;
    }


    /**
     * 根據edenred_voucher_no找到單一宜睿票券紀錄
     *
     * @param string $edenredVoucherNo
     * @return EdenredTicketRecordModel|null
     */
    public function getRecordByVoucherNo(string $edenredVoucherNo): ?EdenredTicketRecordModel
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(EdenredTicketRecordModel::EDENRED_VOUCHER_NO, $edenredVoucherNo);

        /** @var EdenredTicketRecordModel $edenredRecord */
        $edenredRecord = $collection->getFirstItem();

        return ($edenredRecord->getId()) ? $edenredRecord : null;
    }

    /**
     * 將屬於order item的票券資料以transaction的方式整批寫入資料庫中
     *
     * @param array $voucherDataArray
     * @param Item $item
     * @return void
     */
    public function storeVoucherDataInDatabase(array $getMultiVouchersApiResponse, Item $item, $customOwner = NULL): void
    {
        $product          = $item->getProduct();
        $order            = $item->getOrder();
        $sellerId         = $this->marketplaceHelper->getSellerIdByProductId($product->getId());
        $recordArray      = [];
        $voucherDataArray = $getMultiVouchersApiResponse["Vouchers"];

        foreach ($voucherDataArray as $voucher) {
            /** @var \Branch8\Edenred\Model\EdenredTicketRecord $edenredTicketRecord */
            $edenredTicketRecord = $this->edenredTicketRecordFactory->create();

            $decryptVoucherNo = $this->commonHelper->decryptString($voucher["VoucherNo"]);
            if (empty($decryptVoucherNo)) {
                throw new \Exception("VoucherNo is empty after decrypt, full api response: " . json_encode($getMultiVouchersApiResponse));
            }

            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $edenredTicketRecord->getMemo(),
                [
                    "Title"              => "Store new ticket record from Edenred API.",
                    "Issue API response" => $getMultiVouchersApiResponse,
                ]
            );

            // 2024-10-31 local testing
            // found API responsed without VoucherGuid, ShortUrl, ShortUrlAuthCode fields,
            // not sure why but it'll causing exception so use ?? to avoid it.

            // 2024-11-08 about API responsed without VoucherGuid, ShortUrl, ShortUrlAuthCode fields,
            // it's causing by setting typo,
            // backend setting "api_version" should be set to "v2.0" not "V2.0".

            $edenredTicketRecord->setEdenredOrderNumber($product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_ORDER_NUMBER));
            $edenredTicketRecord->setEdenredProductCode($product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_PRODUCT_CODE));
            $edenredTicketRecord->setEdenredMerchantCode($product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_MERCHANT_CODE));
            $edenredTicketRecord->setEdenredClientOrderNumber($getMultiVouchersApiResponse["ClientOrderNumber"]);
            $edenredTicketRecord->setEdenredVoucherNo($decryptVoucherNo);
            $edenredTicketRecord->setEdenredVoucherGuid($voucher["VoucherGuid"] ?? "");
            $edenredTicketRecord->setEdenredGenerateDate($voucher["generateDate"]);
            $edenredTicketRecord->setEdenredExpireStartDate($voucher["expireStartDate"]);
            $edenredTicketRecord->setEdenredExpireEndDate($voucher["expireEndDate"]);
            $edenredTicketRecord->setEdenredShortUrl($voucher["ShortUrl"] ?? "");
            $edenredTicketRecord->setEdenredShortUrlAuthCode($voucher["ShortUrlAuthCode"] ?? "");
            $edenredTicketRecord->setBelongToProductId($product->getId());
            $edenredTicketRecord->setSalesOrderItemId($item->getId());
            $edenredTicketRecord->setStatus(TicketStatus::STATUS_UNUSED);
            $edenredTicketRecord->setMemo($memo);

            $recordArray[] = $edenredTicketRecord;
            $this->transaction->addObject($edenredTicketRecord);
        }

        $this->transaction->save();

        // 分成兩個transaction是因為未創建record記錄前沒辦法直接拿到ID($edenredTicketRecord->getId())
        foreach ($recordArray as $edenredTicketRecord) {
            /** @var CustomerTicket $customerTicket */
            $customerTicket = $this->customerTicketFactory->create();
            $customerTicket->setType(VirtualProductType::TYPE_EDENRED_TICKET);
            $customerTicket->setTicketTableName(EdenredTicketRecordModel::TABLE_NAME);
            $customerTicket->setTicketTableRecordId($edenredTicketRecord->getId());

            $customerId = $order->getCustomerId();
            if ($customOwner) {
                if (isset($customOwner['telephone']) && $customOwner['telephone'] != '') {
                    $customerId = 0;
                    $customerTicket->setTelephone($customOwner['telephone']);
                    $customerTicket->setMemberSeq($customOwner['member_seq']);
                    /** Load customer and set customer if the phone is an account */
                    if (!empty($customOwner['member_seq'])) {
                        $customerCollection = $this->customerCollectionFactory->create()
                            ->addAttributeToFilter('member_seq', $customOwner['member_seq']);
                        $customerCollection->getSelect()->order('entity_id desc')->limit(1);
                        $customer = $customerCollection->getFirstItem();
                        if ($customer->getId()) {
                            $customerId = $customer->getId();
                        }
                    }
                } else {
                    $customerId = (int)$customOwner['customer_id'];
                    $customerTicket->setTelephone(NULL);
                }
            }

            $customerTicket->setCustomerId($customerId);
            $customerTicket->setSalesOrderItemId($item->getId());
            $customerTicket->setBelongToProductId($product->getId());
            $customerTicket->setSellerId($sellerId);
            $customerTicket->setTicketUniqueContent($edenredTicketRecord->getEdenredVoucherNo());
            $customerTicket->setUseStartTime($voucher["expireStartDate"]);
            $customerTicket->setUseEndTime($voucher["expireEndDate"]);
            $customerTicket->setStatus(TicketStatus::STATUS_UNUSED);

            $this->transaction->addObject($customerTicket);
        }

        $this->transaction->save();
    }

    /**
     * 將"已退貨(已取消)"狀態寫入本地宜睿票券資料庫中(edenred_ticket_record)
     * @param EdenredTicketRecordCollection $collection
     * @return void
     */
    public function setCanceledStatusToEdenredTicketRecordsInDb(EdenredTicketRecordCollection $collection, array $apiResponse = []): void
    {
        /** @var EdenredTicketRecordModel $edenredTicketRecord */
        foreach ($collection->getItems() as $edenredTicketRecord) {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $edenredTicketRecord->getMemo(),
                [
                    "Title"               => "Cancel ticket record using Edenred API.",
                    "Cancel API response" => $apiResponse,
                ]
            );

            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

            $edenredTicketRecord->setReturnedDate($taiwanDateObj->format("Y-m-d H:i:s"));
            $edenredTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
            $edenredTicketRecord->setMemo($memo);
            $this->transaction->addObject($edenredTicketRecord);

            $customerTicketRecord = $this->customerTicketRepository->getByTypeAndTicketRecordId(
                VirtualProductType::TYPE_EDENRED_TICKET,
                $edenredTicketRecord->getId()
            );

            if (!is_null($customerTicketRecord)) {
                $customerTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
                $this->transaction->addObject($customerTicketRecord);
            }
        }

        $this->transaction->save();
    }
}
