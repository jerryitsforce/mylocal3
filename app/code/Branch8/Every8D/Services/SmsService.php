<?php

namespace Branch8\Every8D\Services;

use Branch8\Every8D\Api\Data\SmsLogInterface;
use Branch8\Every8D\Api\SmsLogRepositoryInterface;
use Branch8\Every8D\Exceptions\SmsException;
use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Zend_Log_Exception;

class SmsService
{
    public function __construct(
        protected RemoteAddress $remoteAddress,
        protected E8dSmsApiService $e8dSmsApiService,
        protected SmsLogInterface           $smsLogsInterface,
        protected SmsLogRepositoryInterface $smsLogsRepository
    ) {}

    /**
     *  取得帳號餘額
     * @return bool 是否成功獲取餘額
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function getCredit(): bool
    {
        return $this->e8dSmsApiService->getCredit();
    }

    /**
     *  發送簡訊 (整合驗證碼生成和發送)
     * @param string $phone
     * @param string $subject
     * @param string $content
     * @return array
     */
    public function send(string $phone, string $subject, string $content): array
    {
        $response = [
            'status'  => false,
            'message' => ''
        ];

        try {
            // send sms
            $response = $this->e8dSmsApiService->sendSMS($subject, $content, $phone);
            $this->logSms($phone, $content, $response);
        } catch (Exception $e) {
            SmsException::error($e);
        }

        return $response;
    }

    /**
     * 記錄簡訊發送日誌
     *
     * @param string $phone 手機號碼
     * @param string $content 簡訊內容
     * @param array $response 發送結果
     * @return void
     */
    private function logSms(string $phone, string $content, array $response): void
    {
        try {
            $this->smsLogsRepository->save(
                $this->smsLogsInterface
                    ->setPhone($phone)
                    ->setContent($content)
                    ->setResponse($response)
                    ->setIp($this->remoteAddress->getRemoteAddress())
            );
        } catch (Exception $e) {
            SmsException::error($e);
        }
    }
}
