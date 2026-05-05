<?php

namespace Branch8\TicketApi\Helper\NotifyHandler;

use Branch8\TicketApi\Helper\Api;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\Yoxi\Model\YoxiBatchSetting;
use Branch8\Yoxi\Model\YoxiBatchSettingRepository;
use Branch8\Yoxi\Model\YoxiTicketRecord as Model;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketApi\Model\Api\Data\NotifyData;
use Branch8\TicketApi\Model\Api\Data\NotifyDataFactory;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\App\ResourceConnection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class Yoxi
{
    const LOG_FOLDER_NAME = "TicketApi/Api/NotifyUsed/Yoxi";

    /** @var Api */
    protected $apiHelper;

    /** @var ModelRepository */
    protected $modelRepository;

    /** @var YoxiBatchSettingRepository */
    protected $yoxiBatchSettingRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var NotifyDataFactory */
    protected $dataFactory;

    /** @var NotifyData */
    protected $returnData;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    protected $returnCode;
    protected $returnMsg;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Api $apiHelper
     * @param ModelRepository $modelRepository
     * @param YoxiBatchSettingRepository $yoxiBatchSettingRepository
     * @param OrderItemRepository $orderItemRepository
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     * @param NotifyDataFactory $dataFactory
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        Api $apiHelper,
        ModelRepository $modelRepository,
        YoxiBatchSettingRepository $yoxiBatchSettingRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        NotifyDataFactory $dataFactory,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->apiHelper                  = $apiHelper;
        $this->modelRepository            = $modelRepository;
        $this->yoxiBatchSettingRepository = $yoxiBatchSettingRepository;
        $this->orderItemRepository        = $orderItemRepository;
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->dataFactory                = $dataFactory;
        $this->returnData                 = $this->dataFactory->create();
        $this->connection                 = $resourceConnection->getConnection();
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
    public function handleUsedUpdate(): void
    {
        $serialNo = $this->apiHelper->getSerialNo();
        $model    = $this->modelRepository->getRecordBySerialNumber($serialNo);

        // 確認該序號是否存在
        if (!$model) {
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

        /** @var YoxiBatchSetting $batchSetting */
        $batchSetting = $this->yoxiBatchSettingRepository->getById($model->getBatchSettingId());

        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemRepository->get($model->getSalesOrderItemId());

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

        // 確認傳進來的usedTransactionNo是否有被記錄
        // YOXI專屬檢查, 因為可能會一個序號被使用複數次 => 不會了, 只會使用一次
        // if ($this->checkUsedTransactionNoUsedAlready($model)) {
        //     $memoArray = [
        //         "Title"                    => "Notify received but usedTransactionNo is stored.",
        //         "Decrypt Request"          => $this->apiHelper->getDecryptArray(),
        //         "Datetime"                 => date("Y-m-d H:i:s"),
        //         "Timestamp"                => time(),
        //         "IP"                       => $this->apiHelper->getIp(),
        //         "CurrentUsedTransactionNo" => $model->getUsedTransactionNo(),
        //     ];
        //     $this->hotaiCoreCommonHelper->writeLog(json_encode($memoArray), self::LOG_FOLDER_NAME);

        //     $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
        //     $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;
        //     $this->returnData->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
        //     $this->returnData->setUsedTransactionNo("");
        //     $this->returnData->setSerialNo($model->getSerialNumber());
        //     $this->returnData->setProductNo($batchSetting->getBatchCode());
        //     $this->returnData->setProductNameM($orderItem->getName());
        //     $this->returnData->setProductNameS("");

        //     return;
        // }

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
     * @param \Branch8\Yoxi\Model\YoxiTicketRecord $model
     * @return bool
     */
    protected function checkIfTicketUnableToUseYet(Model $model): bool
    {
        if ($model->getStatus() != TicketStatus::STATUS_UNUSED && $model->getStatus() != TicketStatus::STATUS_USED) {
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
     * @param \Branch8\Yoxi\Model\YoxiTicketRecord $model
     * @return bool
     */
    protected function checkIfTicketOverDue(Model $model): bool
    {
        if ($model->getStatus() != TicketStatus::STATUS_UNUSED && $model->getStatus() != TicketStatus::STATUS_USED) {
            return false;
        }

        $useEndTime = $model->getUseEndTime();

        if (empty($useEndTime) || strtotime($useEndTime) < 0) {
            return false;
        }

        return time() > strtotime($useEndTime);
    }

    protected function checkUsedTransactionNoUsedAlready(Model $model): bool
    {
        $usedTransactionNoString  = $model->getUsedTransactionNo() ?? "";
        $usedTransactionNoArray   = explode(",", $usedTransactionNoString);
        $requestUsedTransactionNo = $this->apiHelper->getUsedTransactionNo();

        return in_array($requestUsedTransactionNo, $usedTransactionNoArray);
    }

    protected function getNewUsedTransactionNoForDb(Model $model): string
    {
        $usedTransactionNoString = $model->getUsedTransactionNo();
        $newUsedTransactionNo    = $this->apiHelper->getUsedTransactionNo();

        if (empty($usedTransactionNoString)) {
            return $newUsedTransactionNo;
        }

        $usedTransactionNoArray   = explode(",", $usedTransactionNoString);
        $usedTransactionNoArray[] = $newUsedTransactionNo;

        return implode(",", $usedTransactionNoArray);
    }

    protected function getNewUsedStoreNoForDb(Model $model): string
    {
        $newUsedTransactionNo = $this->apiHelper->getUsedTransactionNo();
        $newUsedStoreNo       = $this->apiHelper->getUsedStoreNo();
        $inputContentArray    = [
            "usedTransactionNo" => $newUsedTransactionNo,
            "usedStoreNo"       => $newUsedStoreNo,
            "time"              => time(),
            "datetime"          => date("Y-m-d H:i:s"),
        ];

        $usedStoreNoArray = json_decode($model->getUsedStoreNo() ?? "", true);

        if (is_null($usedStoreNoArray) || !is_array($usedStoreNoArray)) {
            $inputArray                        = [];
            $inputArray[$newUsedTransactionNo] = $inputContentArray;

            return json_encode($inputArray);
        }

        $usedStoreNoArray[$newUsedTransactionNo] = $inputContentArray;

        return json_encode($usedStoreNoArray);
    }

    /**
     * 判斷票券狀態是否已處於"已使用"
     * @param \Branch8\Yoxi\Model\YoxiTicketRecord $model
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

        $usedCountBefore = $model->getUsedCount();
        $usedCountAfter  = $usedCountBefore + 1;

        try {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title"             => "Notify used update.",
                    "Decrypt Request"   => $this->apiHelper->getDecryptArray(),
                    "Datetime"          => date("Y-m-d H:i:s"),
                    "Timestamp"         => time(),
                    "Used count before" => $usedCountBefore,
                    "Used count after"  => $usedCountAfter,
                    "IP"                => $this->apiHelper->getIp(),
                ]
            );
            $model->setStatus(TicketStatus::STATUS_USED);
            $model->setUsedDate(date("Y-m-d H:i:s"));
            // $model->setUsedTransactionNo($this->getNewUsedTransactionNoForDb($model));
            // $model->setUsedStoreNo($this->getNewUsedStoreNoForDb($model));
            $model->setUsedTransactionNo($this->apiHelper->getUsedTransactionNo());
            $model->setUsedStoreNo($this->apiHelper->getUsedStoreNo());
            $model->setUsedCount($usedCountAfter);
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
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_YOXI_TICKET,
                CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $model->getId()
            ];
            /**
             * Index used_date to customer_ticket.redeemed_at
             */



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
