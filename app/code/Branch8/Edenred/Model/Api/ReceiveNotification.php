<?php

declare(strict_types=1);

namespace Branch8\Edenred\Model\Api;

use Branch8\Edenred\Model\EdenredTicketRecord as Record;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\Edenred\Api\ReceiveNotificationInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Webapi\Rest\Request;
use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Helper\Common as CommonHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Branch8\Edenred\Api\EdenredTicketRecordRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;

class ReceiveNotification implements ReceiveNotificationInterface
{
    const LOG_FOLDER_NAME = "Edenred/Api/ReceiveNotification";
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_RECEIVE_NOTIFICATION;

    const EXPECTED_BODY_PARAMS = [
        "ClientOrderNumber",
        "VoucherNo",
        "MerchantCode",
        "Status",
        "ActionDate",
    ];

    const RETURN_CODE_SUCCESS                    = "RC00";
    const RETURN_CODE_DEFAULT_FAIL               = "RC99";
    const RETURN_CODE_VOUCHER_NUMBER_ERROR       = "RC30";
    const RETURN_CODE_JSON_FORMAT_ERROR          = "RC98";
    const RETURN_CODE_HEADER_CONSUMER_CODE_ERROR = "RC100";

    const RECEIVE_STATUS_REDEEM_FINISH     = "001"; // 要將票券改為已使用
    const RECEIVE_STATUS_REVERSE_REDEMTION = "002"; // 通常是有錯誤才會傳, 要將票券改回未使用
    const RECEIVE_STATUS_REDEEM_PARTIAL    = "003"; // 目前不應該接收到這種狀態
    const VALID_RECEIVE_STATUS             = [
        "001",
        "002",
        // "003",
    ];

    /** @var Request */
    protected $request;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var EdenredTicketRecordRepositoryInterface */
    protected $edenredTicketRecordRepository;

    /** @var RemoteAddress */
    protected $remoteAddress;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var AdapterInterface */
    protected $connection;

    /** @var Response */
    protected $response;

    /** @var EventManager */
    protected $eventManager;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    protected $allowIpArray;
    protected $configConsumerCode;
    protected $virtualProductType;

    public function __construct(
        Request $request,
        ScopeConfigInterface $scopeConfig,
        CommonHelper $commonHelper,
        ApiHelper $apiHelper,
        EdenredTicketRecordRepositoryInterface $edenredTicketRecordRepository,
        RemoteAddress $remoteAddress,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ResourceConnection $resourceConnection,
        Response $response,
        EventManager $eventManager,
        OrderItemRepository $orderItemRepository
    ) {
        $this->request                       = $request;
        $this->scopeConfig                   = $scopeConfig;
        $this->commonHelper                  = $commonHelper;
        $this->apiHelper                     = $apiHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->remoteAddress                 = $remoteAddress;
        $this->hotaiCoreCommonHelper         = $hotaiCoreCommonHelper;
        $this->connection                    = $resourceConnection->getConnection();
        $this->response                      = $response;
        $this->eventManager                  = $eventManager;
        $this->orderItemRepository           = $orderItemRepository;

        $this->virtualProductType = VirtualProductType::TYPE_EDENRED_TICKET;
    }

    public function receiveNotification()
    {
        date_default_timezone_set("Asia/Taipei");

        try {
            if (!$this->checkIp()) {
                $returnMessage = "IP check fail.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => "Allow IP now: " . json_encode($this->getAllowIpArray())
                    ]
                );
            }

            if (!$this->checkBodyParams()) {
                $returnMessage = "Body params error.";

                $expectedBodyParams = implode(",", self::EXPECTED_BODY_PARAMS);
                $logMEssage         = "Expected body params: {$expectedBodyParams}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_JSON_FORMAT_ERROR,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMEssage
                    ]
                );
            }

            if (!$this->checkConsumerCode()) {
                $returnMessage = "Consumer code error.";

                $logMEssage = "Consumer code from request(encrypted): {$this->getConsumerCodeFromRequest()}, ";
                $logMEssage .= "consumer code from request(decrypted): {$this->commonHelper->decryptString($this->getConsumerCodeFromRequest())}, ";
                $logMEssage .= "consumer code in config: {$this->getConsumerCodeFromConfig()}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_HEADER_CONSUMER_CODE_ERROR,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMEssage
                    ]
                );
            }

            $encryptedVoucherNo = $this->getEncryptedVoucherNoFromRequest();
            $deryptedVoucherNo  = $this->commonHelper->decryptString($encryptedVoucherNo);
            if (!$deryptedVoucherNo) {
                $returnMessage = "Voucher number decrypt error.";

                $logMessage = "Voucher number before decrypt: {$encryptedVoucherNo}, ";
                $logMessage .= "after decrypt: {$deryptedVoucherNo}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_VOUCHER_NUMBER_ERROR,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            $edenredTicketRecord = $this->edenredTicketRecordRepository->getRecordByVoucherNo($deryptedVoucherNo);
            if (!$edenredTicketRecord) {
                $returnMessage = "Can't find related record by voucher number.";

                $logMessage = "Voucher number before decrypt: {$encryptedVoucherNo}, ";
                $logMessage .= "after decrypt: {$deryptedVoucherNo}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_VOUCHER_NUMBER_ERROR,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            $status = $this->getStatusFromRequest();
            if (!$this->checkStatus($status)) {
                $returnMessage = "Status from request is not valid.";

                $logMessage = "Receive status: {$status}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            if (!$this->checkUseTimeForSetUsed($edenredTicketRecord)) {
                $returnMessage = "ActionDate validation isn't pass.";

                $useTime    = $this->request->getBodyParams()["ActionDate"];
                $logMessage = "ActionDate in request: {$useTime}, ";
                $logMessage .= "expire start date for record: {$edenredTicketRecord->getEdenredExpireStartDate()}, ";
                $logMessage .= "expire end date for record: {$edenredTicketRecord->getEdenredExpireEndDate()}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            if (!$this->checkRecordStatusForSetUsed($edenredTicketRecord)) {
                $returnMessage = "Record status isn't correct for current request.";

                $statusRequest = $this->request->getBodyParams()["Status"];
                $logMessage    = "Status in request: {$statusRequest}, ";
                $logMessage .= "status in record: {$edenredTicketRecord->getStatus()}, ";
                $logMessage .= "record ID: {$edenredTicketRecord->getId()}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            if (!$this->checkRecordStatusForResetUnused($edenredTicketRecord)) {
                $returnMessage = "Record status isn't correct for current request.";

                $statusRequest = $this->request->getBodyParams()["Status"];
                $logMessage    = "Status in request: {$statusRequest}, ";
                $logMessage .= "status in record: {$edenredTicketRecord->getStatus()}, ";
                $logMessage .= "record ID: {$edenredTicketRecord->getId()}.";

                return $this->sendResponse(
                    [
                        "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                        "returnMessage" => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Message"        => $logMessage
                    ]
                );
            }

            $this->updateFlowByStatus($edenredTicketRecord, $status);

            // Success return.
            return $this->sendResponse(
                [
                    "returnCode"    => self::RETURN_CODE_SUCCESS,
                    "returnMessage" => "Success."
                ]
            );
        } catch (\Exception $e) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Title"             => "Exception",
                "Receive header"    => $this->request->getHeaders()->toArray(),
                "Receive body"      => $this->request->getBodyParams(),
                "Exception message" => $e->getMessage()
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            return $this->sendResponse([
                "returnCode"    => self::RETURN_CODE_DEFAULT_FAIL,
                "returnMessage" => $e->getMessage()
            ]);
        }
    }

    protected function getAllowIpArray(): array
    {
        if (!is_null($this->allowIpArray)) {
            return $this->allowIpArray;
        }

        $rawIpString     = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_NOTIFICATION_ALLOWED_IPS) ?? "";
        $trimmedIpString = str_replace(' ', '', $rawIpString);

        if (empty($trimmedIpString)) {
            return [];
        }

        $this->allowIpArray = explode(",", $trimmedIpString);

        return $this->allowIpArray;
    }

    /**
     * 檢查通知請求的IP是否在白名單內
     * (若白名單為空則跳過檢查)
     * @return boolean
     */
    protected function checkIp(): bool
    {
        $allowIpArray = $this->getAllowIpArray();

        foreach ($allowIpArray as $allowIp) {
            if ($allowIp == $this->request->getClientIp()) {
                return true;
            }
        }

        return false;
    }

    protected function checkBodyParams(): bool
    {
        $bodyParams = $this->request->getBodyParams();

        foreach (self::EXPECTED_BODY_PARAMS as $paramName) {
            if (!isset($bodyParams[$paramName])) {
                return false;
            }
        }

        return true;
    }

    protected function getConsumerCodeFromConfig(): null|string
    {
        if (!is_null($this->configConsumerCode)) {
            return $this->configConsumerCode;
        }

        $this->configConsumerCode = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_CONSUMER_CODE);

        return $this->configConsumerCode;
    }

    protected function getConsumerCodeFromRequest(): string
    {
        $consumerCode = $this->request->getHeader("ConsumerCode");

        return empty($consumerCode) ? "" : $consumerCode;
    }

    /**
     * 判斷接收到的consumerCode是否和設定中的一致
     * @return boolean
     */
    protected function checkConsumerCode(): bool
    {
        $consumerCode          = $this->getConsumerCodeFromConfig();
        $encryptedConsumerCode = $this->getConsumerCodeFromRequest();

        if (empty($encryptedConsumerCode)) {
            return false;
        }

        $decryptedConsumerCode = $this->commonHelper->decryptString($encryptedConsumerCode);

        return $consumerCode == $decryptedConsumerCode;
    }

    /**
     * 取得接收到的VoucherNo(內容被加密過)
     *
     * @return string
     */
    protected function getEncryptedVoucherNoFromRequest(): string
    {
        $body = $this->request->getBodyParams();

        return $body["VoucherNo"] ?? "";
    }

    /**
     * 取得接收到的Status
     *
     * @return string
     */
    protected function getStatusFromRequest(): string
    {
        $body = $this->request->getBodyParams();

        return $body["Status"] ?? "";
    }

    /**
     * 檢查接收到的Status內容是否合法
     *
     * @param string $status
     * @return boolean
     */
    protected function checkStatus(string $status): bool
    {
        return in_array($status, self::VALID_RECEIVE_STATUS);
    }

    /**
     * 根據接收到的Status對宜睿票券資料庫(edenred_ticket_record)紀錄進行更新
     * @param Record $edenredTicketRecord
     * @param string $status
     * @return void
     */
    protected function updateFlowByStatus(Record $edenredTicketRecord, string $status): void
    {
        switch ($status) {
            case self::RECEIVE_STATUS_REDEEM_FINISH:
                $this->setRecordToUsedFlow($edenredTicketRecord);
                $this->fireEventAfterUseHandle($edenredTicketRecord);
                break;

            case self::RECEIVE_STATUS_REVERSE_REDEMTION:
                $this->resetRecordToUnusedFlow($edenredTicketRecord);
                $this->fireEVentAfterCancelHandle($edenredTicketRecord);
                break;

            case self::RECEIVE_STATUS_REDEEM_PARTIAL:
            default:
                throw new \Exception("Status not match valid status.");
        }

        $this->edenredTicketRecordRepository->save($edenredTicketRecord);
    }

    protected function checkUseTimeForSetUsed(Record $model): bool
    {
        $status = $this->request->getBodyParams()["Status"];
        if ($status != self::RECEIVE_STATUS_REDEEM_FINISH) {
            return true;
        }

        $useTime      = strtotime($this->request->getBodyParams()["ActionDate"]);
        $useStartTime = strtotime($model->getEdenredExpireStartDate());
        $useEndTime   = strtotime($model->getEdenredExpireEndDate());

        return ($useStartTime <= $useTime) && ($useTime <= $useEndTime);
    }

    protected function checkRecordStatusForSetUsed(Record $model): bool
    {
        $status = $this->request->getBodyParams()["Status"];
        if ($status != self::RECEIVE_STATUS_REDEEM_FINISH) {
            return true;
        }

        return $model->getStatus() == TicketStatus::STATUS_UNUSED;
    }

    protected function checkRecordStatusForResetUnused(Record $model): bool
    {
        $status = $this->request->getBodyParams()["Status"];
        if ($status != self::RECEIVE_STATUS_REVERSE_REDEMTION) {
            return true;
        }

        return $model->getStatus() == TicketStatus::STATUS_USED;
    }

    protected function setRecordToUsedFlow(Record $model): Record
    {
        $this->connection->beginTransaction();

        try {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title"          => "ReceiveNotification class for Edenred only used update.",
                    "IP"             => $this->request->getClientIp(),
                    "Receive header" => $this->request->getHeaders()->toArray(),
                    "Receive body"   => $this->request->getBodyParams(),
                    "Datetime"       => date("Y-m-d H:i:s"),
                    "Timestamp"      => time(),
                ]
            );
            $model->setStatus(TicketStatus::STATUS_USED);
            $model->setUsedDate($this->request->getBodyParams()["ActionDate"] ?? date("Y-m-d H:i:s"));
            $model->setUsedTransactionNo($this->request->getBodyParams()["ClientOrderNumber"] ?? "");
            $model->setMemo($memo);

            // update edenred_ticket_record
            $updateData  = [
                Record::STATUS              => $model->getStatus(),
                Record::USED_DATE           => $model->getUsedDate(),
                Record::USED_TRANSACTION_NO => $model->getUsedTransactionNo(),
                Record::MEMO                => $model->getMemo(),
            ];
            $whereUpdate = [
                Record::RECORD_ID . ' = ?' => $model->getId()
            ];
            $this->connection->update(
                Record::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            // update customer_ticket
            $updateData  = [
                CustomerTicket::STATUS      => $model->getStatus(),
                CustomerTicket::REDEEMED_AT => $model->getUsedDate(),
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

    protected function resetRecordToUnusedFlow(Record $model): Record
    {
        $this->connection->beginTransaction();

        try {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $model->getMemo(),
                [
                    "Title"          => "ReceiveNotification class for Edenred only reset to unused update.",
                    "IP"             => $this->request->getClientIp(),
                    "Receive header" => $this->request->getHeaders()->toArray(),
                    "Receive body"   => $this->request->getBodyParams(),
                    "Datetime"       => date("Y-m-d H:i:s"),
                    "Timestamp"      => time(),
                ]
            );

            $model->setStatus(TicketStatus::STATUS_UNUSED);
            $model->setUsedDate(null);
            $model->setUsedTransactionNo(null);
            $model->setMemo($memo);

            // update edenred_ticket_record
            $updateData  = [
                Record::STATUS              => $model->getStatus(),
                Record::USED_DATE           => $model->getUsedDate(),
                Record::USED_TRANSACTION_NO => $model->getUsedTransactionNo(),
                Record::MEMO                => $model->getMemo(),
            ];
            $whereUpdate = [
                Record::RECORD_ID . ' = ?' => $model->getId()
            ];
            $this->connection->update(
                Record::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            // update customer_ticket
            $updateData  = [
                CustomerTicket::STATUS      => $model->getStatus(),
                CustomerTicket::REDEEMED_AT => $model->getUsedDate(),
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

    protected function sendResponse(array $responseData, array $logData = null): void
    {
        if (!is_null($logData)) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode($logData, JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME,
                self::LOG_OPTION_VALUE
            );
        }

        $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($responseData))
            ->sendResponse();
    }

    protected function fireEventAfterUseHandle(Record $edenredTicketRecord)
    {
        $orderItemId = $edenredTicketRecord->getSalesOrderItemId();
        $orderItem   = $this->orderItemRepository->get((int) $orderItemId);

        $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_USE_API_HANDLE, [
            "orderId" => $orderItem->getOrderId()
        ]);
    }

    protected function fireEVentAfterCancelHandle(Record $edenredTicketRecord)
    {
        $orderItemId = $edenredTicketRecord->getSalesOrderItemId();
        $orderItem   = $this->orderItemRepository->get((int) $orderItemId);

        $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_CANCEL_API_HANDLE, [
            "orderId" => $orderItem->getOrderId()
        ]);
    }
}
