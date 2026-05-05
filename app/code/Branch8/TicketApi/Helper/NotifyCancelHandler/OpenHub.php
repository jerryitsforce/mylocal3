<?php

namespace Branch8\TicketApi\Helper\NotifyCancelHandler;

use Branch8\TicketApi\Helper\NotifyCancelHandler\BaseHandler;
use Branch8\TicketApi\Model\Api\Data\NotifyDataFactory as DataFactory;
use Branch8\TicketApi\Helper\Api;
use Branch8\TicketApi\Model\TicketApiMerchant;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord as Model;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketApi\Helper\CheckHandler\Const\SerialNoStatus;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\TicketApi\Helper\Data as DataHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\App\ResourceConnection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\TicketApi\Service\CustomerTicketService;

class OpenHub extends BaseHandler
{
    /** @var Api */
    protected $apiHelper;

    /** @var ModelRepository */
    protected $modelRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /** @var CustomerTicketService */
    protected $customerTicketService;

    public function __construct(
        DataFactory $dataFactory,
        Api $apiHelper,
        ModelRepository $modelRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper,
        OrderItemRepository $orderItemRepository,
        ResourceConnection $resourceConnection,
        CustomerTicketService $customerTicketService
    ) {
        parent::__construct($dataFactory);
        $this->apiHelper             = $apiHelper;
        $this->modelRepository       = $modelRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->dataHelper            = $dataHelper;
        $this->orderItemRepository   = $orderItemRepository;
        $this->connection            = $resourceConnection->getConnection();
        $this->customerTicketService = $customerTicketService;
    }

    public function handleCancelUpdate(TicketApiMerchant $merchant): void
    {
        $apiRequestData = $this->apiHelper->getDecryptArray();

        $this->hotaiCoreCommonHelper->writeLog(
            json_encode([
                "Title"           => "OpenHub Notify Cancel Request",
                "Request Data"    => $apiRequestData,
                "Merchant"        => $merchant->getData(),
                "Timestamp"       => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_SLASHES),
            "TicketApi/NotifyCancelHandler/OpenHub"
        );

        try {
            $serialNo = $this->apiHelper->getSerialNo();
            $transactionNo = $this->apiHelper->getUsedTransactionNo();
            
            if (empty($serialNo)) {
                $this->setNotExistResponse($serialNo);
                return;
            }

            if (empty($transactionNo)) {
                $this->setNotExistResponse($serialNo);
                return;
            }

            /** @var Model $openHubRecord */
            $openHubRecord = $this->modelRepository->getBySerialNumber($serialNo);

            if (!$openHubRecord || !$openHubRecord->getRecordId()) {
                $this->setNotExistResponse($serialNo);
                return;
            }

            $orderItem = $this->getOrderItem($openHubRecord);
            
            if (!$this->canCancelTicket($openHubRecord, $orderItem, $transactionNo)) {
                return; // Response already set in validation methods
            }

            // Update ticket status and set success response
            $openHubRecord = $this->updateTicketTableAndCustomerTicketTableInTransaction($openHubRecord, $transactionNo, $apiRequestData);
            $this->setSuccessResponse($openHubRecord, $orderItem, $transactionNo);

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(
                json_encode([
                    "Title"           => "OpenHub Notify Cancel Exception",
                    "Request Data"    => $apiRequestData,
                    "Exception"       => $e->getMessage(),
                    "Timestamp"       => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_SLASHES),
                "TicketApi/NotifyCancelHandler/OpenHub"
            );

            $this->setFailedResponse(Api::RETURN_CODE_DEFAULT_FAIL, $e->getMessage());
        }
    }

    private function getOrderItem(Model $openHubRecord): ?OrderItem
    {
        return $openHubRecord->getSalesOrderItemId() 
            ? $this->orderItemRepository->get($openHubRecord->getSalesOrderItemId()) 
            : null;
    }

    private function canCancelTicket(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): bool
    {
        $status = $openHubRecord->getStatus();

        if ($status === TicketStatus::STATUS_RETURNED) {
            $this->setCancelledAlreadyResponse($openHubRecord, $orderItem, $transactionNo);
            return false;
        }

        if (!$this->checkTicketCanBeReturnedToUnused($openHubRecord)) {
            $this->setCannotCancelResponse($openHubRecord, $orderItem, $transactionNo);
            return false;
        }

        if (!$this->checkUsedTransactionNoStoredInRecord($openHubRecord)) {
            $this->setTransactionNoMismatchResponse($openHubRecord, $orderItem);
            return false;
        }

        return true;
    }

    private function setSuccessResponse(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): void
    {
        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg = Api::RETURN_MESSAGE_SUCCESS;
        
        $this->returnData = $this->createNotifyData(
            $this->getUseStatusByTicketRecord($openHubRecord),
            $transactionNo,
            $openHubRecord->getSerialNumber(),
            $orderItem,
            true
        );
        
        if ($orderItem) {
            $this->apiHelper->fireEventAfterCancelHandle((int)$orderItem->getOrderId());
        }
    }

    private function setNotExistResponse(string $serialNo): void
    {
        $this->setResponse(
            Api::RETURN_CODE_NOT_EXIST,
            Api::RETURN_MESSAGE_NOT_EXIST,
            null,
            $this->apiHelper->getUsedTransactionNo(),
            $serialNo
        );
    }

    private function setCancelledAlreadyResponse(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): void
    {
        $this->setResponse(
            Api::RETURN_CODE_USED_ALREADY,
            Api::RETURN_MESSAGE_USED_ALREADY,
            $this->getUseStatusByTicketRecord($openHubRecord),
            $transactionNo,
            $openHubRecord->getSerialNumber(),
            $orderItem,
            true
        );
    }

    private function setCannotCancelResponse(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): void
    {
        $this->setResponse(
            Api::RETURN_CODE_USED_ALREADY,
            Api::RETURN_MESSAGE_USED_ALREADY,
            $this->getUseStatusByTicketRecord($openHubRecord),
            $transactionNo,
            $openHubRecord->getSerialNumber(),
            $orderItem,
            true
        );
        
        $this->logAttempt(
            "Cancel notification received but ticket can't be returned to unused status due to its current status.",
            $openHubRecord
        );
    }

    private function setTransactionNoMismatchResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->setResponse(
            Api::RETURN_CODE_UNABLE_TO_USE_YET,
            Api::RETURN_MESSAGE_UNABLE_TO_USE_YET,
            $this->getUseStatusByTicketRecord($openHubRecord),
            $this->apiHelper->getUsedTransactionNo(),
            $openHubRecord->getSerialNumber()
        );
        
        $this->logAttempt(
            "Notify cancel received but usedTransactionNo not stored in record.",
            $openHubRecord
        );
    }

    private function setResponse(string $code, string $message, ?string $useStatus, string $transactionNo, string $serialNo, ?OrderItem $orderItem = null, bool $includePricing = false): void
    {
        $this->returnCode = $code;
        $this->returnMsg = $message;
        $this->returnData = $this->createNotifyData($useStatus, $transactionNo, $serialNo, $orderItem, $includePricing);
    }

    private function logAttempt(string $message, Model $openHubRecord): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => $message,
            "Decrypt Request" => $this->apiHelper->getDecryptArray(),
            "Current Status" => $openHubRecord->getStatus(),
            "Datetime" => date("Y-m-d H:i:s"),
            "Timestamp" => time(),
            "IP" => $this->apiHelper->getIp(),
        ]), "TicketApi/NotifyCancelHandler/OpenHub");
    }

    private function createNotifyData(?string $useStatus, string $transactionNo, string $serialNo, ?OrderItem $orderItem = null, bool $includePricing = false): \Branch8\TicketApi\Model\Api\Data\NotifyData
    {
        $data = $this->dataFactory->create();
        if ($useStatus !== null) {
            $data->setUseStatus($useStatus);
        }
        $data->setUsedTransactionNo($transactionNo);
        $data->setSerialNo($serialNo);
        $data->setProductNo("");
        
        if ($orderItem) {
            $data->setProductNameM($orderItem->getName());
            $data->setProductNameS("");
            
            if ($includePricing) {
                $data->setSellPrice($this->dataHelper->getSellPriceByOrderItem($orderItem));
                $data->setSellPoint($this->dataHelper->getSellPointByOrderItem($orderItem));
                $data->setTotalSellAmount($this->dataHelper->getTotalSellAmountByOrderItem($orderItem));
                $data->setProductSellPrice($this->dataHelper->getProductSellPriceByOrderItem($orderItem));
                $data->setOrderAmount($this->dataHelper->getOrderAmountByOrderItem($orderItem));
            }
        } else {
            $data->setProductNameM("");
            $data->setProductNameS("");
        }
        
        return $data;
    }

    protected function checkTicketCanBeReturnedToUnused(Model $model): bool
    {
        return $model->getStatus() == TicketStatus::STATUS_USED;
    }

    protected function getUseStatusByTicketRecord(Model $model): string
    {
        return $model->getStatus() == TicketStatus::STATUS_UNUSED 
            ? Api::RETURN_DATA_USE_STATUS_TRUE 
            : Api::RETURN_DATA_USE_STATUS_FALSE;
    }

    protected function checkUsedTransactionNoStoredInRecord(Model $model): bool
    {
        return $model->getTransactionNo() === $this->apiHelper->getUsedTransactionNo();
    }

    protected function updateTicketTableAndCustomerTicketTableInTransaction(Model $model, string $transactionNo, array $apiRequestData): Model
    {
        $this->connection->beginTransaction();
        
        try {
            // Prepare memo
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title" => "Cancel notification update.",
                    "Decrypt Request" => $apiRequestData,
                    "Datetime" => date("Y-m-d H:i:s"),
                    "Timestamp" => time(),
                    "IP" => $this->apiHelper->getIp(),
                ]
            );
            
            // Update OpenHub record - return to unused status (using direct SQL like core logic)
            $updateData = [
                Model::STATUS => TicketStatus::STATUS_UNUSED,
                Model::MEMO => isset($apiRequestData['memo']) ? $apiRequestData['memo'] : $memo,
                Model::UPDATED_AT => date("Y-m-d H:i:s")
            ];
            $whereUpdate = [
                Model::RECORD_ID . ' = ?' => $model->getRecordId()
            ];
            $this->connection->update(
                Model::TABLE_NAME,
                $updateData,
                $whereUpdate
            );
            
            // Update the model object to reflect changes
            $model->setStatus(TicketStatus::STATUS_UNUSED);
            $model->setMemo(isset($apiRequestData['memo']) ? $apiRequestData['memo'] : $memo);
            
            // Update customer_ticket table using service
            $this->customerTicketService->updateCustomerTicketToUnused($model);
            
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
        
        return $model;
    }

    private function setFailedResponse(string $code, string $message): void
    {
        $this->returnCode = $code;
        $this->returnMsg = $message;
        
        $data = $this->dataFactory->create();
        $this->returnData = $data;
    }

}
