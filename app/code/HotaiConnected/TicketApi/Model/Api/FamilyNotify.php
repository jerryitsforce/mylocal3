<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use HotaiConnected\TicketApi\Api\FamilyNotifyInterface;
use HotaiConnected\TicketApi\Model\ResourceModel\TicketResource;
use HotaiConnected\TicketApi\Model\ResourceModel\MerchantResource;
use Psr\Log\LoggerInterface;

class FamilyNotify implements FamilyNotifyInterface
{
    private const STATUS_CODE_SUCCESS = '00';
    private const STATUS_CODE_ERROR = '01';
    private const RESPONSE_STATUS = '41';

    /**
     * @var Request 
     */
    private Request $request;

    /**
     * @var Response 
     */
    private Response $response;

    /**
     * @var LoggerInterface 
     */
    private LoggerInterface $logger;

    /**
     * @var TicketResource 
     */
    private TicketResource $ticketResource;

    /**
     * @var MerchantResource 
     */
    private MerchantResource $merchantResource;

    public function __construct(
        Request $request,
        Response $response,
        LoggerInterface $logger,
        TicketResource $ticketResource,
        MerchantResource $merchantResource
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->ticketResource = $ticketResource;
        $this->merchantResource = $merchantResource;
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        date_default_timezone_set('Asia/Taipei');

        try {
            $this->logRequest();

            $requestData = $this->getRequestData();

            // 產生AUTHID
            $cArr = str_split($this->merchantResource->getMerchantId('FAMI')); 
            $AUTHID = "F" . $cArr[0] . "$" . $cArr[1] . "N" . $cArr[2] . "@" . $cArr[3] . "N" . $cArr[4] . "E" . $cArr[5] . "T"; 

            if($requestData['AUTHID'] && $requestData['AUTHID'] !== $AUTHID) {
                $this->sendResponse($requestData['DETAIL'] ?? [], true, 'AUTHID錯誤');
                return;
            }

            $details = $requestData['DETAIL'] ?? [];
            $responseDetails = $this->processDetails($details);
            $this->sendResponse($responseDetails, false);
            return;

        } catch (\Exception $e) {
            $this->logger->critical('[family_notify] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $requestData = $this->getRequestData();
            $this->sendResponse($requestData['DETAIL'] ?? [], true, $e->getMessage());
            return;
        }
    }

    /**
     * Process each detail record
     *
     * @param  array $details
     * @return array
     */
    private function processDetails(array $details): array
    {
        $responseDetails = [];
        foreach ($details as $detail) {
            $result = $this->handleUsedUpdate($detail);
            $responseDetails[] = $this->formatDetailResponse($detail, $result);
        }
        return $responseDetails;
    }

    /**
     * Handle ticket usage update
     *
     * @param  array $detail
     * @return array
     */
    private function handleUsedUpdate(array $detail): array
    {
        try {
            $serialNo = $this->merchantResource->decryptMerchantData($detail['CD_PIN_CODE'], 'FAMI');
    
            if ($serialNo === null) {
                return $this->createErrorResponse('解密失敗');
            }

            if ($this->ticketResource->updateEventTicketToUsed($serialNo, $detail)){
                return $this->createSuccessResponse();
            }

            if ($this->ticketResource->isTransactionExists($detail['TRAN_NO'])) {
                return $this->createSuccessResponse();
            }

            $recordId = $this->ticketResource->getRecordIdBySerialNumber($serialNo);
            if (!$recordId) {
                return $this->createErrorResponse('序號查無資料');
            }

            if ($this->ticketResource->isTicketUsed($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "Notify received but ticket is used already.");
                return $this->createErrorResponse('序號狀態不可使用');
            }

            if ($this->ticketResource->isTicketNotYetAvailable($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "Notify received but ticket unable to use yet.");
                return $this->createErrorResponse('序號還不可使用');
            }

            if ($this->ticketResource->isTicketOverdue($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "Notify received but ticket over due.");
                return $this->createErrorResponse('序號已過期');
            }

            // 更新票券狀態
            $success = $this->ticketResource->updateTicketToUsed($recordId, $serialNo, $detail);

            if(!$success) {
                return $this->createErrorResponse('更新狀態失敗');
            }

            $this->ticketResource->fireEventByRecordId($recordId);

            return $this->createSuccessResponse();

        } catch (\Exception $e) {
            $this->logger->error(
                '[family_notify] Exception: ' . $e->getMessage(), [
                'serial_no' => $serialNo,
                'trace' => $e->getTraceAsString()
                ]
            );
            return $this->createErrorResponse($e->getMessage());
        }
    }

    /**
     * Log request
     */
    protected function logRequest()
    {
        $requestContent = $this->request->getContent();
        $requestData = [
            'headers' => $this->request->getHeaders()->toArray(),
            'body' => json_decode($requestContent, true)
        ];
        
        $this->logger->info(
            '[family_notify] API Request:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => json_encode($requestData, JSON_PRETTY_PRINT)
            ]
        );
    }

    /**
     * Format detail response
     *
     * @param  array $detail
     * @param  array $result
     * @return array
     */
    private function formatDetailResponse(array $detail, array $result): array
    {
        return [
            'TRAN_NO' => $detail['TRAN_NO'],
            'SERIAL_NO' => $detail['SERIAL_NO'],
            'STATUS' => self::RESPONSE_STATUS,
            'REPLY_TIME' => date('YmdHis'),
            'REPLY_CODE' => $result['returnCode'],
            'REPLY_DESC' => $result['returnMsg']
        ];
    }

    /**
     * Create success response
     *
     * @return array
     */
    private function createSuccessResponse(): array
    {
        return [
            'returnCode' => self::STATUS_CODE_SUCCESS,
            'returnMsg' => ''
        ];
    }

    /**
     * Create error response
     *
     * @param  string $message
     * @return array
     */
    private function createErrorResponse(string $message): array
    {
        return [
            'returnCode' => self::STATUS_CODE_ERROR,
            'returnMsg' => $message
        ];
    }

    /**
     * Get request data
     *
     * @return array
     */
    private function getRequestData(): array
    {
        return json_decode($this->request->getContent(), true) ?? [];
    }

    /**
     * Send response
     *
     * @param  array  $responseDetails
     * @param  bool   $isError
     * @param  string $errorMessage
     * @return void
     */
    private function sendResponse(array $responseDetails, bool $isError, string $errorMessage = ''): void
    {
        if ($isError && !empty($responseDetails)) {
            $responseDetails = array_map(
                function ($detail) use ($errorMessage) {
                    return [
                    'TRAN_NO' => $detail['TRAN_NO'],
                    'SERIAL_NO' => $detail['SERIAL_NO'],
                    'STATUS' => self::RESPONSE_STATUS,
                    'REPLY_TIME' => date('YmdHis'),
                    'REPLY_CODE' => self::STATUS_CODE_ERROR,
                    'REPLY_DESC' => $errorMessage
                    ];
                }, $responseDetails
            );
        }

        $this->logger->info(
            '[family_notify] API Response:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'response' => json_encode(['DETAIL' => $responseDetails], JSON_PRETTY_PRINT)
            ]
        );

        $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode(['DETAIL' => $responseDetails]))
            ->sendResponse();
    }
}
