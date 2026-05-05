<?php

namespace Branch8\TicketApi\Helper\CheckHandler;

use Branch8\TicketApi\Helper\CheckHandler\BaseHandler;
use Branch8\TicketApi\Model\Api\Data\CheckDataFactory as DataFactory;
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
use Branch8\TicketApi\Service\CustomerTicketService;

class OpenHub extends BaseHandler
{
    /** @var DataFactory */
    protected $dataFactory;

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

    /** @var CustomerTicketService */
    protected $customerTicketService;

    public function __construct(
        DataFactory $dataFactory,
        Api $apiHelper,
        ModelRepository $modelRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper,
        OrderItemRepository $orderItemRepository,
        CustomerTicketService $customerTicketService
    ) {
        parent::__construct($dataFactory);
        $this->apiHelper             = $apiHelper;
        $this->modelRepository       = $modelRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->dataHelper            = $dataHelper;
        $this->orderItemRepository   = $orderItemRepository;
        $this->customerTicketService = $customerTicketService;
    }

    public function handleCheckTicket(TicketApiMerchant $merchant): void
    {
        $apiRequestData = $this->apiHelper->getDecryptArray();

        $this->hotaiCoreCommonHelper->writeLog(
            json_encode([
                "Title"           => "OpenHub Check Ticket Request",
                "Request Data"    => $apiRequestData,
                "Merchant"        => $merchant->getData(),
                "Timestamp"       => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_SLASHES),
            "TicketApi/CheckHandler/OpenHub"
        );

        try {
            $serialNo = $this->apiHelper->getSerialNo();
            
            if (empty($serialNo)) {
                $this->setFailedResponse(Api::RETURN_CODE_DEFAULT_FAIL, "Serial number is required");
                return;
            }

            /** @var Model $openHubRecord */
            $openHubRecord = $this->modelRepository->getBySerialNumber($serialNo);

            if (!$openHubRecord || !$openHubRecord->getRecordId()) {
                $this->setNotExistResponse();
                return;
            }

            $orderItem = $this->getOrderItem($openHubRecord);
            $status = $openHubRecord->getStatus();

            switch ($status) {
                case TicketStatus::STATUS_UNUSED:
                    $this->handleUnusedTicket($openHubRecord, $orderItem);
                    break;
                case TicketStatus::STATUS_USED:
                    $this->setUsedResponse($openHubRecord, $orderItem);
                    break;
                case TicketStatus::STATUS_RETURNED:
                    $this->setUsedResponse($openHubRecord, $orderItem, "Ticket has been returned");
                    break;
                default:
                    $this->setFailedResponse(Api::RETURN_CODE_DEFAULT_FAIL, "Invalid ticket status: " . $status);
                    break;
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(
                json_encode([
                    "Title"           => "OpenHub Check Ticket Exception",
                    "Request Data"    => $apiRequestData,
                    "Exception"       => $e->getMessage(),
                    "Timestamp"       => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_SLASHES),
                "TicketApi/CheckHandler/OpenHub"
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

    private function handleUnusedTicket(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        if ($this->checkIfTicketUseable($openHubRecord)) {
            $this->setSuccessResponse($openHubRecord, $orderItem);
        } elseif ($this->checkIfTicketOverDue($openHubRecord)) {
            $this->setOverDueResponse($openHubRecord, $orderItem);
        } else {
            $this->setUnableToUseResponse($openHubRecord, $orderItem);
        }
    }

    private function setSuccessResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->returnCode = Api::RETURN_CODE_SUCCESS;
        $this->returnMsg = Api::RETURN_MESSAGE_SUCCESS;
        
        $this->returnData = $this->createCheckData(
            true,
            SerialNoStatus::USEABLE,
            $openHubRecord,
            $orderItem,
            true // include pricing for success
        );
    }

    private function setNotExistResponse(): void
    {
        $this->setResponse(
            Api::RETURN_CODE_NOT_EXIST,
            Api::RETURN_MESSAGE_NOT_EXIST,
            false,
            SerialNoStatus::NOT_EXIST
        );
    }

    private function setUsedResponse(Model $openHubRecord, ?OrderItem $orderItem, string $message = "Ticket already used"): void
    {
        $this->setResponse(
            Api::RETURN_CODE_USED_ALREADY,
            Api::RETURN_MESSAGE_USED_ALREADY,
            false,
            SerialNoStatus::USED,
            $openHubRecord,
            $orderItem
        );
    }

    private function setOverDueResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->setResponse(
            Api::RETURN_CODE_OVER_DUE,
            Api::RETURN_MESSAGE_OVER_DUE,
            false,
            SerialNoStatus::DATE_INVALID,
            $openHubRecord,
            $orderItem
        );
    }

    private function setUnableToUseResponse(Model $openHubRecord, ?OrderItem $orderItem): void
    {
        $this->setResponse(
            Api::RETURN_CODE_UNABLE_TO_USE_YET,
            Api::RETURN_MESSAGE_UNABLE_TO_USE_YET,
            false,
            SerialNoStatus::DATE_INVALID,
            $openHubRecord,
            $orderItem
        );
    }

    private function setResponse(string $code, string $message, bool $isUsable, string $status, ?Model $record = null, ?OrderItem $orderItem = null): void
    {
        $this->returnCode = $code;
        $this->returnMsg = $message;
        $this->returnData = $this->createCheckData($isUsable, $status, $record, $orderItem, $isUsable);
    }

    private function setFailedResponse(string $code, string $message): void
    {
        $this->setResponse($code, $message, false, SerialNoStatus::NOT_EXIST);
    }

    private function createCheckData(bool $isUsable, string $status, ?Model $record = null, ?OrderItem $orderItem = null, bool $includePricing = false): \Branch8\TicketApi\Model\Api\Data\CheckData
    {
        $data = $this->dataFactory->create();
        $data->setIsUsable($isUsable);
        $data->setSerialNoStatus($status);
        $data->setProductNo("");
        // OpenHub doesn't have direct getUseEndTime(), get from CustomerTicket
        $expiry = "";
        if ($record) {
            $customerTicket = $this->customerTicketService->getCustomerTicketByOpenHubRecord($record);
            $useEndTime = $customerTicket ? $customerTicket->getData('use_end_time') : null;
            $expiry = $useEndTime ? $this->dataHelper->formatExpiry($useEndTime) : "";
        }
        $data->setExpiry($expiry);
        
        if ($orderItem) {
            $data->setProductNameM($this->dataHelper->getProductNameMByOrderItem($orderItem));
            $data->setProductNameS($this->dataHelper->getProductNameSByOrderItem($orderItem));
            
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
        
        if ($includePricing && !$orderItem) {
            $data->setSellPrice("");
            $data->setSellPoint("");
            $data->setTotalSellAmount("");
            $data->setProductSellPrice("");
            $data->setOrderAmount("");
        }
        
        return $data;
    }

    protected function checkIfTicketUseable(Model $model): bool
    {
        return $model->getStatus() == TicketStatus::STATUS_UNUSED && !$this->customerTicketService->checkIfTicketOverDue($model);
    }

    protected function checkIfTicketOverDue(Model $model): bool
    {
        return $this->customerTicketService->checkIfTicketOverDue($model);
    }
}
