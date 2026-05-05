<?php

namespace Branch8\TicketApi\Helper\NotifyCancelHandler;

use Branch8\TicketApi\Helper\NotifyCancelHandler\BaseHandler;
use Branch8\TicketApi\Helper\Api;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\Yoxi\Model\YoxiBatchSetting as BatchSetting;
use Branch8\Yoxi\Model\YoxiBatchSettingRepository as BatchSettingRepository;
use Branch8\Yoxi\Model\YoxiTicketRecord as Model;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketApi\Model\Api\Data\NotifyDataFactory as DataFactory;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\App\ResourceConnection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\TicketApi\Helper\Data as DataHelper;

class Yoxi extends BaseHandler
{
    const LOG_FOLDER_NAME = "TicketApi/Api/NotifyCancel/Yoxi";

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

    /** @var DataFactory */
    protected $dataFactory;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    public function __construct(
        Api $apiHelper,
        ModelRepository $modelRepository,
        batchSettingRepository $batchSettingRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataFactory $dataFactory,
        DataHelper $dataHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->apiHelper              = $apiHelper;
        $this->modelRepository        = $modelRepository;
        $this->batchSettingRepository = $batchSettingRepository;
        $this->orderItemRepository    = $orderItemRepository;
        $this->hotaiCoreCommonHelper  = $hotaiCoreCommonHelper;
        $this->dataHelper             = $dataHelper;
        $this->connection             = $resourceConnection->getConnection();

        // Extend from baseHandler, only used in "customer_ticket" table update query.
        $this->virtualProductType = VirtualProductType::TYPE_YOXI_TICKET;

        parent::__construct($dataFactory);
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function handleCancelUpdate(): void
    {
        $serialNo = $this->apiHelper->getSerialNo();
        $model    = $this->modelRepository->getRecordBySerialNumber($serialNo);

        // If ticket is not exist.
        if (!$model) {
            $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
            $this->returnMsg  = Api::RETURN_MESSAGE_NOT_EXIST;
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($serialNo);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");

            return;
        }

        // Ticket exists, so it should have batch setting related to it.
        /** @var BatchSetting $batchSetting */
        $batchSetting = $this->batchSettingRepository->getById($model->getBatchSettingId());

        // If ticket is not sold yet.
        if ($this->checkTicketUnsold($model)) {
            $memoArray = [
                "Title"           => "Notify cancel received but ticket not sold yet.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Use Start Time"  => $model->getUseStartTime(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;
            $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($model));
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($serialNo);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");

            return;
        }

        // Ticket sold, so it should have sales order item related to it.
        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemRepository->get($model->getSalesOrderItemId());

        // If ticket isn't "used" status, return response.
        if (!$this->checkTicketCanBeReturnedToUnused($model)) {
            $memoArray = [
                "Title"           => "Cancel notification received but ticket can't be returned to `unused` status due to it's current status.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Current Status"  => $model->getStatus(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_USED_ALREADY;
            $this->returnMsg  = Api::RETURN_MESSAGE_USED_ALREADY;
            $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($model));
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($model->getSerialNumber());
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($orderItem->getName());
            $this->returnData->setProductNameS("");

            $this->returnData->setSellPrice($this->dataHelper->getSellPriceByOrderItem($orderItem));
            $this->returnData->setSellPoint($this->dataHelper->getSellPointByOrderItem($orderItem));
            $this->returnData->setTotalSellAmount($this->dataHelper->getTotalSellAmountByOrderItem($orderItem));
            $this->returnData->setProductSellPrice($this->dataHelper->getProductSellPriceByOrderItem($orderItem));
            $this->returnData->setOrderAmount($this->dataHelper->getOrderAmountByOrderItem($orderItem));

            return;
        }

        // Check if usedTransactionNo is exist in record.
        if (!$this->checkUsedTransactionNoStoredInRecord($model)) {
            $memoArray = [
                "Title"           => "Notify cancel received but usedTransactionNo not stored in record.",
                "Decrypt Request" => $this->apiHelper->getDecryptArray(),
                "Use Start Time"  => $model->getUseStartTime(),
                "Datetime"        => date("Y-m-d H:i:s"),
                "Timestamp"       => time(),
                "IP"              => $this->apiHelper->getIp(),
            ];
            $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

            $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;
            $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($model));
            $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $this->returnData->setSerialNo($serialNo);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");

            return;
        }

        // If ticket is "used" status then return to "unused" status for ticket table and customer_ticket table.
        $model = $this->updateTicketTableAndCustomerTicketTableInTransaction($model);

        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg  = Api::RETURN_MESSAGE_SUCCESS;
        $this->returnData->setUseStatus($this->getUseStatusByTicketRecord($model));
        $this->returnData->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
        $this->returnData->setSerialNo($model->getSerialNumber());
        $this->returnData->setProductNo($batchSetting->getBatchCode());
        $this->returnData->setProductNameM($orderItem->getName());
        $this->returnData->setProductNameS("");

        $this->returnData->setSellPrice($this->dataHelper->getSellPriceByOrderItem($orderItem));
        $this->returnData->setSellPoint($this->dataHelper->getSellPointByOrderItem($orderItem));
        $this->returnData->setTotalSellAmount($this->dataHelper->getTotalSellAmountByOrderItem($orderItem));
        $this->returnData->setProductSellPrice($this->dataHelper->getProductSellPriceByOrderItem($orderItem));
        $this->returnData->setOrderAmount($this->dataHelper->getOrderAmountByOrderItem($orderItem));

        // update order state, order status and order item flow_status if needed.
        $this->apiHelper->fireEventAfterCancelHandle((int) $orderItem->getOrderId());
    }

    protected function checkTicketUnsold(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_IMPORTED || $status == TicketStatus::STATUS_ALLOCATED;
    }

    protected function checkTicketCanBeReturnedToUnused(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_USED;
    }

    protected function checkUsedTransactionNoStoredInRecord(Model $model): bool
    {
        $usedTransactionNo = $model->getUsedTransactionNo();

        return $usedTransactionNo === $this->apiHelper->getUsedTransactionNo();
    }

    // protected function checkUsedTransactionNoStoredInRecord(Model $model): bool
    // {
    //     $usedTransactionNoString  = $model->getUsedTransactionNo() ?? "";
    //     $usedTransactionNoArray   = explode(",", $usedTransactionNoString);
    //     $requestUsedTransactionNo = $this->apiHelper->getUsedTransactionNo();

    //     return in_array($requestUsedTransactionNo, $usedTransactionNoArray);
    // }

    protected function getUseStatusByTicketRecord(Model $model): string
    {
        $status = $model->getStatus();

        switch ($status) {
            case TicketStatus::STATUS_UNUSED:
                return Api::RETURN_DATA_USE_STATUS_TRUE;

            default:
                return Api::RETURN_DATA_USE_STATUS_FALSE;
        }
    }

    protected function checkTicketAlreadyUsed(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_USED;
    }

    protected function getNewUsedTransactionNoForDb(Model $model, string $removeUsedTransactionNo): null|string
    {
        $usedTransactionNoString = $model->getUsedTransactionNo() ?? "";
        $usedTransactionNoArray  = explode(",", $usedTransactionNoString);
        $resultArray             = array_diff($usedTransactionNoArray, [$removeUsedTransactionNo]);

        return (empty($resultArray)) ? null : implode(",", $resultArray);
    }

    protected function getNewStoreNoForDb(Model $model): null|string
    {
        $removeUsedTransactionNo = $this->apiHelper->getUsedTransactionNo();

        $usedStoreNoArray = json_decode($model->getUsedStoreNo() ?? "", true);

        if (is_null($usedStoreNoArray) || !is_array($usedStoreNoArray)) {
            return null;
        }

        unset($usedStoreNoArray[$removeUsedTransactionNo]);

        return count($usedStoreNoArray) == 0 ? null : json_encode($usedStoreNoArray);
    }

    protected function updateTicketTableAndCustomerTicketTableInTransaction(Model $model): Model
    {
        $this->connection->beginTransaction();

        $usedCountBefore = $model->getUsedCount();
        $usedCountAfter  = $usedCountBefore - 1;

        $statusBefore = $model->getStatus();
        $statusAfter  = ($usedCountAfter == 0) ? TicketStatus::STATUS_UNUSED : TicketStatus::STATUS_USED;

        // $usedTransactionNoBefore = $model->getUsedTransactionNo();
        // $usedTransactionNoAfter  = $this->getNewUsedTransactionNoForDb(
        //     $model,
        //     $this->apiHelper->getUsedTransactionNo()
        // );

        try {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title"                    => "Cancel notification update.",
                    "Decrypt Request"          => $this->apiHelper->getDecryptArray(),
                    "Datetime"                 => date("Y-m-d H:i:s"),
                    "Timestamp"                => time(),
                    "Used count before"        => $usedCountBefore,
                    "Used count after"         => $usedCountAfter,
                    "Status before"            => $statusBefore,
                    "Status after"             => $statusAfter,
                    "IP"                       => $this->apiHelper->getIp(),
                ]
            );

            $model->setStatus($statusAfter);
            // $model->setUsedDate(null);
            // $model->setUsedTransactionNo($usedTransactionNoAfter);
            // $model->setUsedStoreNo($this->getNewStoreNoForDb($model));
            $model->setUsedTransactionNo(null);
            $model->setUsedStoreNo(null);
            $model->setUsedCount($usedCountAfter);
            $model->setMemo($memo);

            $updateData  = [
                Model::STATUS              => $model->getStatus(),
                    // Model::USED_DATE           => $model->getUsedDate(),
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

            $updateData  = [
                CustomerTicket::STATUS => $statusAfter,
            ];
            $whereUpdate = [
                CustomerTicket::TYPE . ' = ?' => $this->virtualProductType,
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
