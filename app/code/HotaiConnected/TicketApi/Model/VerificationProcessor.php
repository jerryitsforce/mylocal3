<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model;

use HotaiConnected\TicketApi\Model\ResourceModel\TicketResource;
use HotaiConnected\TicketApi\Model\ResourceModel\MerchantResource;
use Psr\Log\LoggerInterface;

class VerificationProcessor
{
    const LOG_PREFIX = '[family_notify_ftp]';

    const STATUS_CODE_SUCCESS = '00';
    const STATUS_CODE_ERROR = '01';
    const STATUS_CODE_ALREADY_USED = '02';

    /**
     * @var TicketResource
     */
    private TicketResource $ticketResource;

    /**
     * @var MerchantResource
     */
    private MerchantResource $merchantResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param TicketResource $ticketResource
     * @param MerchantResource $merchantResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        TicketResource $ticketResource,
        MerchantResource $merchantResource,
        LoggerInterface $logger
    ) {
        $this->ticketResource = $ticketResource;
        $this->merchantResource = $merchantResource;
        $this->logger = $logger;
    }

    /**
     * Process verification data from TXT file
     *
     * @param array $txtData Array of serial numbers
     * @return array
     */
    public function processVerificationData(array $txtData): array
    {
        $results = [
            'total' => count($txtData),
            'success' => 0,
            'failed' => 0,
            'already_used' => 0,
            'success_serials' => [],
            'failed_serials' => [],
            'already_used_serials' => []
        ];

        foreach ($txtData as $serialNumber) {
            $result = $this->handleSingleVerification($serialNumber);

            if ($result['returnCode'] === self::STATUS_CODE_SUCCESS) {
                $results['success']++;
                $results['success_serials'][] = $serialNumber;
            } elseif ($result['returnCode'] === self::STATUS_CODE_ALREADY_USED) {
                $results['already_used']++;
                $results['already_used_serials'][] = $serialNumber;
            } else {
                $results['failed']++;
                $results['failed_serials'][] = $serialNumber;
            }
        }

        return $results;
    }

    /**
     * Handle single verification record
     * Reuses logic from FamilyNotify::handleUsedUpdate()
     *
     * @param string $serialNumber
     * @return array
     */
    private function handleSingleVerification(string $serialNumber): array
    {
        try {
            if (empty($serialNumber)) {
                return $this->createErrorResponse('Serial number is empty');
            }

            // Prepare detail array for TicketResource methods
            $detail = [
                'TRAN_NO' => '',
                'STORE_CODE' => ''
            ];

            // Check if this is an event ticket
            if ($this->ticketResource->updateEventTicketToUsed($serialNumber, $detail)) {
                return $this->createSuccessResponse();
            }

            // Get record ID by serial number
            $recordId = $this->ticketResource->getRecordIdBySerialNumber($serialNumber);
            if (!$recordId) {
                return $this->createErrorResponse('序號查無資料');
            }

            // Check if ticket is already used
            if ($this->ticketResource->isTicketUsed($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "FTP verification received but ticket is used already.");
                return [
                    'returnCode' => self::STATUS_CODE_ALREADY_USED,
                    'returnMsg' => '序號已使用'
                ];
            }

            // Check if ticket is not yet available
            if ($this->ticketResource->isTicketNotYetAvailable($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "FTP verification received but ticket unable to use yet.");
                return $this->createErrorResponse('序號還不可使用');
            }

            // Check if ticket is overdue
            if ($this->ticketResource->isTicketOverdue($recordId)) {
                $this->ticketResource->logTicketStatus($recordId, "FTP verification received but ticket over due.");
                return $this->createErrorResponse('序號已過期');
            }

            // Update ticket to used status
            $success = $this->ticketResource->updateTicketToUsed($recordId, $serialNumber, $detail);

            if (!$success) {
                return $this->createErrorResponse('更新狀態失敗');
            }

            // Fire event after successful update
            $this->ticketResource->fireEventByRecordId($recordId);

            return $this->createSuccessResponse();

        } catch (\Exception $e) {
            $this->logger->error(
                self::LOG_PREFIX . ' Exception during verification: ' . $e->getMessage(),
                [
                    'serial_no' => $serialNumber,
                    'trace' => $e->getTraceAsString()
                ]
            );
            return $this->createErrorResponse($e->getMessage());
        }
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
            'returnMsg' => 'Success'
        ];
    }

    /**
     * Create error response
     *
     * @param string $message
     * @return array
     */
    private function createErrorResponse(string $message): array
    {
        return [
            'returnCode' => self::STATUS_CODE_ERROR,
            'returnMsg' => $message
        ];
    }
}
