<?php

namespace Branch8\TicketApi\Helper\NotifyHandler;

use Branch8\TicketApi\Helper\NotifyHandler\BaseHandler;
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

    public function handleUsedUpdate(TicketApiMerchant $merchant): void
    {
        $apiRequestData = $this->apiHelper->getDecryptArray();

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
            
            if (!$this->canUseTicket($openHubRecord, $orderItem, $transactionNo)) {
                return; // Response already set in validation methods
            }

            // Update ticket status and set success response
            $openHubRecord = $this->updateTicketTableAndCustomerTicketTableInTransaction($openHubRecord, $transactionNo, $apiRequestData);
            $this->setSuccessResponse($openHubRecord, $orderItem, $transactionNo);

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(
                json_encode([
                    "Title"           => "OpenHub Notify Used Exception",
                    "Request Data"    => $apiRequestData,
                    "Exception"       => $e->getMessage(),
                    "Timestamp"       => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_SLASHES),
                "TicketApi/NotifyHandler/OpenHub"
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

    private function canUseTicket(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): bool
    {
        $status = $openHubRecord->getStatus();

        if ($status == TicketStatus::STATUS_USED) {
            $this->setUsedAlreadyResponse($openHubRecord, $orderItem, $transactionNo);
            return false;
        }

        if ($status == TicketStatus::STATUS_RETURNED) {
            $this->setUsedAlreadyResponse($openHubRecord, $orderItem, $transactionNo, "Ticket has been returned");
            return false;
        }

        if ($this->customerTicketService->checkIfTicketUnableToUseYet($openHubRecord)) {
            $this->setUnableToUseResponse($openHubRecord, $orderItem);
            return false;
        }

        if ($this->customerTicketService->checkIfTicketOverDue($openHubRecord)) {
            $this->setOverDueResponse($openHubRecord, $orderItem);
            return false;
        }

        return true;
    }

    private function setSuccessResponse(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo): void
    {
        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg = Api::RETURN_MESSAGE_SUCCESS;
        
        $this->returnData = $this->createNotifyData(
            Api::RETURN_DATA_USE_STATUS_TRUE,
            $transactionNo,
            $openHubRecord->getSerialNumber(),
            $orderItem,
            true
        );
        
        if ($orderItem) {
            $this->apiHelper->fireEventAfterUseHandle((int)$orderItem->getOrderId());
        }
    }

    private function setNotExistResponse(string $serialNo): void
    {
        $this->setResponse(
            Api::RETURN_CODE_NOT_EXIST,
            Api::RETURN_MESSAGE_NOT_EXIST,
            Api::RETURN_DATA_USE_STATUS_FALSE,
            $this->apiHelper->getUsedTransactionNo(),
            $serialNo
        );
    }

    private function setUsedAlreadyResponse(Model $openHubRecord, ?OrderItem $orderItem, string $transactionNo, string $message = "Ticket already used"): void
    {
        $this->setResponse(
            Api::RETURN_CODE_USED_ALREADY,
            Api::RETURN_MESSAGE_USED_ALREADY,
            Api::RETURN_DATA_USE_STATUS_FALSE,
            $transactionNo,
            $openHubRecord->getSerialNumber(),
            $orderItem
        );
        
        $this->logAttempt($message, $openHubRecord);
    }

    private function setUnableToUseResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->setResponse(
            Api::RETURN_CODE_UNABLE_TO_USE_YET,
            Api::RETURN_MESSAGE_UNABLE_TO_USE_YET,
            Api::RETURN_DATA_USE_STATUS_FALSE,
            "",
            $openHubRecord->getSerialNumber(),
            $orderItem
        );
    }

    private function setOverDueResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->setResponse(
            Api::RETURN_CODE_OVER_DUE,
            Api::RETURN_MESSAGE_OVER_DUE,
            Api::RETURN_DATA_USE_STATUS_FALSE,
            "",
            $openHubRecord->getSerialNumber(),
            $orderItem
        );
    }

    private function setResponse(string $code, string $message, string $useStatus, string $transactionNo, string $serialNo, ?OrderItem $orderItem = null): void
    {
        $this->returnCode = $code;
        $this->returnMsg = $message;
        $this->returnData = $this->createNotifyData($useStatus, $transactionNo, $serialNo, $orderItem, $useStatus === Api::RETURN_DATA_USE_STATUS_TRUE);
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
        ]), "TicketApi/NotifyHandler/OpenHub");
    }

    private function createNotifyData(string $useStatus, string $transactionNo, string $serialNo, ?OrderItem $orderItem = null, bool $includePricing = false): \Branch8\TicketApi\Model\Api\Data\NotifyData
    {
        $data = $this->dataFactory->create();
        $data->setUseStatus($useStatus);
        $data->setUsedTransactionNo($transactionNo);
        $data->setSerialNo($serialNo);
        $data->setProductNo("");
        
        if ($orderItem) {
            $data->setProductNameM($orderItem->getName());
            $data->setProductNameS("");
            
            if ($includePricing) {
                $orderItemActualMoney = $orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount();
                $pointUsed = $orderItem->getData("row_total_point_used") ?? 0;
                
                $data->setSellPrice((string)(int)$orderItemActualMoney);
                $data->setSellPoint((string)(int)$pointUsed);
                $data->setTotalSellAmount((string)(int)($orderItem->getRowTotalInclTax() + $pointUsed));
                $data->setProductSellPrice((string)(int)$orderItem->getPriceInclTax());
                $data->setOrderAmount((string)(int)$orderItem->getQtyOrdered());
            }
        } else {
            $data->setProductNameM("");
            $data->setProductNameS("");
        }
        
        return $data;
    }


    protected function updateTicketTableAndCustomerTicketTableInTransaction(Model $model, string $transactionNo, array $apiRequestData): Model
    {
        $this->connection->beginTransaction();
        
        try {
            // Prepare memo
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title" => "Notify used update.",
                    "Decrypt Request" => $apiRequestData,
                    "Datetime" => date("Y-m-d H:i:s"),
                    "Timestamp" => time(),
                    "IP" => $this->apiHelper->getIp(),
                ]
            );
            
            // Update OpenHub record using direct SQL (like GeneralNotify)
            $updateData = [
                Model::STATUS => TicketStatus::STATUS_USED,
                Model::MEMO => $memo,
                Model::USED_DATE => date("Y-m-d H:i:s"), // 主動通知串接的TradeTime
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
            $model->setStatus(TicketStatus::STATUS_USED);
            $model->setMemo($memo);
            
            // Update customer_ticket table using service
            $this->customerTicketService->updateCustomerTicketToUsed($model);
            
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
        $data->setUseStatus(Api::RETURN_DATA_USE_STATUS_FALSE);
        $data->setUsedTransactionNo("");
        $data->setSerialNo("");
        $data->setProductNo("");
        $data->setProductNameM("");
        $data->setProductNameS("");
        
        $this->returnData = $data;
    }

}
