<?php

namespace Branch8\TicketApi\Helper\NotifyHandler;

use Branch8\TicketApi\Helper\Api;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSetting as BatchSettingModel;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSettingRepository as BatchSettingRepository;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord as Model;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketApi\Model\Api\Data\NotifyData;
use Branch8\TicketApi\Model\Api\Data\NotifyDataFactory;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Magento\Framework\App\ResourceConnection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;

class GeneralNotify
{
    const LOG_FOLDER_NAME = "TicketApi/Api/NotifyUsed/GeneralNotify";

    /** @var Api */
    protected $apiHelper;

    /** @var ModelRepository */
    protected $modelRepository;

    /** @var BatchSettingRepository */
    protected $batchSettingRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var SellerCollectionFactory */
    protected $sellerCollectionFactory;

    /** @var NotifyDataFactory */
    protected $dataFactory;

    /** @var NotifyData */
    protected $returnData;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    protected $returnCode;
    protected $returnMsg;

    protected $timezone;

    public function __construct(
        Api $apiHelper,
        ModelRepository $modelRepository,
        BatchSettingRepository $batchSettingRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        SellerCollectionFactory $sellerCollectionFactory,
        NotifyDataFactory $dataFactory,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->apiHelper               = $apiHelper;
        $this->modelRepository         = $modelRepository;
        $this->batchSettingRepository  = $batchSettingRepository;
        $this->orderItemRepository     = $orderItemRepository;
        $this->hotaiCoreCommonHelper   = $hotaiCoreCommonHelper;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->dataFactory             = $dataFactory;
        $this->returnData              = $this->dataFactory->create();
        $this->connection              = $resourceConnection->getConnection();
        $this->timezone = $timezone;
    }

    /**
     * @return null|string
     */
    public function getReturnCode(): null|string
    {
        return $this->returnCode;
    }

    /**
     * @return null|string
     */
    public function getReturnMsg(): null|string
    {
        return $this->returnMsg;
    }

    /**
     * @return NotifyData
     */
    public function getReturnData(): NotifyData
    {
        return $this->returnData;
    }

    /**
     * 執行"已使用"更新流程
     * @throws \Exception
     * @return void
     */
    public function handleUsedUpdate(TicketApiMerchant $merchant): void
    {
        $serialNo = $this->apiHelper->getSerialNo();

        $collection = $this->modelRepository->getRecordBySerialNumberAndSellerIdsString(
            $serialNo,
            $merchant->getSellerIds()
        );

        // 確認該序號是否存在
        if ($collection->getSize() == 0) {
            $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
            $this->returnMsg  = Api::RETURN_MESSAGE_NOT_EXIST;
            $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($serialNo);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");

            return;
        }

        // 查到複數紀錄
        if ($collection->getSize() > 1) {
            $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
            $this->returnMsg  = Api::RETURN_MESSAGE_TICKET_CHECK_UNEXPECTED_CONDITION;
            $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($serialNo);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");

            return;
        }

        /** @var Model $model */
        $model = $collection->getFirstItem();

        /** @var BatchSettingModel $batchSetting */
        $batchSetting = $this->batchSettingRepository->getById($model->getBatchSettingId());

        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemRepository->get($model->getSalesOrderItemId());

        // 該序號已被使用
        if ($this->checkTicketAlreadyUsed($model)) {
            $memoArray = [
                "Title"           => "Notify received but ticket is used already.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Current Status"  => $model->getStatus(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_USED_ALREADY;
            $this->returnMsg  = Api::RETURN_MESSAGE_USED_ALREADY;
            $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($model->getSerialNumber());
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($orderItem->getName());
            $this->returnData->setProductNameS("");

            return;
        }

        // 該序號還不可使用
        if ($this->checkIfTicketUnableToUseYet($model)) {
            $memoArray = [
                "Title"           => "Notify received but ticket unable to use yet.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Use Start Time"  => $model->getUseStartTime(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;
            $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
            $this->returnData->setUsedTransactionNo("");
            $this->returnData->setSerialNo($model->getSerialNumber());
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($orderItem->getName());
            $this->returnData->setProductNameS("");

            return;
        }

        // 該序號已過期(使用時已逾序號可用效期)
        if ($this->checkIfTicketOverDue($model)) {
            $memoArray = [
                "Title"           => "Notify received but ticket over due.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Use End Time"    => $model->getUseEndTime(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_OVER_DUE;
            $this->returnMsg  = Api::RETURN_MESSAGE_OVER_DUE;
            $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
            $this->returnData->setUsedTransactionNo("");
            $this->returnData->setSerialNo($model->getSerialNumber());
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($orderItem->getName());
            $this->returnData->setProductNameS("");

            return;
        }

        // 上述檢查都通過, 將序號更新為"已使用"
        $model = $this->updateTicketTableAndCustomerTicketTableInTransaction($model);

        $orderItemActualMoney = $orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount();
        $pointUsed            = $orderItem->getData("row_total_point_used") ?? 0;
        $this->returnCode     = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg      = Api::RETURN_MESSAGE_SUCCESS;
        $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_TRUE);
        $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
        $this->returnData->setSerialNo($model->getSerialNumber());
        $this->returnData->setProductNo($batchSetting->getBatchCode());
        $this->returnData->setProductNameM($orderItem->getName());
        $this->returnData->setProductNameS("");
        $this->returnData->setSellPrice((int) $orderItemActualMoney);
        $this->returnData->setSellPoint((int) $pointUsed);
        $this->returnData->setTotalSellAmount((int) ($orderItem->getRowTotalInclTax() + $pointUsed));
        $this->returnData->setProductSellPrice((int) $orderItem->getPriceInclTax());
        $this->returnData->setOrderAmount((int) $orderItem->getQtyOrdered());

        // update order state, order status and order item flow_status if needed.
        $this->apiHelper->fireEventAfterUseHandle((int) $orderItem->getOrderId());
    }

    /**
     * 判斷票券狀態是否還不允許使用
     * @param \Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord $model
     * @return bool
     */
    protected function checkIfTicketUnableToUseYet(Model $model): bool
    {
        if ($model->getStatus() != TicketStatus::STATUS_UNUSED) {
            return false;
        }

        $useStartTime = $model->getUseStartTime();

        if (empty($useStartTime) || strtotime($useStartTime) < 0) {
            return false;
        }

        return time() < strtotime($useStartTime);
    }

    /**
     * 判斷票券狀態是否過期
     * @param \Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord $model
     * @return bool
     */
    protected function checkIfTicketOverDue(Model $model): bool
    {
        $useEndTime = $model->getUseEndTime();

        if (empty($useEndTime) || strtotime($useEndTime) < 0) {
            return false;
        }

        return time() > strtotime($useEndTime);
    }

    /**
     * 判斷票券狀態是否已處於"已使用"
     * @param \Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord $model
     * @return bool
     */
    protected function checkTicketAlreadyUsed(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_USED;
    }

    protected function updateTicketTableAndCustomerTicketTableInTransaction(Model $model): Model
    {
        $this->connection->beginTransaction();

        try {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title"           => "Notify used update.",
                    "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                    "Datetime"        => date("Y-m-d H:i:s"),
                    "Timestamp"       => time(),
                    "IP"              => $this->apiHelper->getIp(),
                ]
            );
            $model->setStatus(TicketStatus::STATUS_USED);
            $model->setUsedDate(date("Y-m-d H:i:s"));
            $model->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $model->setUsedStoreNo($this->apiHelper->getUsedStoreNo());
            $model->setUsedCount($model->getUsedCount() + 1);
            $model->setMemo($memo);

            // 更新票券專屬表
            $updateData  = [
                Model::STATUS              => $model->getStatus(),
                Model::USED_DATE           => $model->getUsedDate(),
                Model::USED_TRANSACTION_NO => $model->getUsedTransactionNo(),
                Model::USED_STORE_NO       => $model->getUsedStoreNo(),
                Model::USED_COUNT          => $model->getUsedCount(),
                Model::MEMO                => $model->getMemo(),
            ];
            $whereUpdate = [
                Model::RECORD_ID . ' = ?' => $model->getId()
            ];
            $this->connection->update(
                Model::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            // 更新customer_ticket表
            $updateData  = [
                CustomerTicket::STATUS => $model->getStatus(),
                CustomerTicket::REDEEMED_AT => $model->getUsedDate()
            ];
            $whereUpdate = [
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET,
                CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $model->getId()
            ];
            $this->connection->update(
                CustomerTicket::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $model;
    }
}
