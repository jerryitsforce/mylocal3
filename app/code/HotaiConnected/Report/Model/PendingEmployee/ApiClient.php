<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Model\PendingEmployee;

use HotaiConnected\Report\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class ApiClient
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Find members by phone numbers
     *
     * @param array $phoneNumbers Array of phone numbers
     * @return array API response data
     * @throws \Exception
     */
    public function findMembers(array $phoneNumbers): array
    {
        $apiUrl = $this->config->getPendingEmployeeApiUrl();
        $appId = $this->config->getPendingEmployeeAppId();
        $appKey = $this->config->getPendingEmployeeAppKey();

        if (empty($apiUrl) || empty($appId) || empty($appKey)) {
            throw new \Exception('Pending Employee API configuration is incomplete');
        }

        // Build request body
        $accounts = [];
        foreach ($phoneNumbers as $phone) {
            $accounts[] = [
                'countryCode' => '886',
                'mobilePhone' => $phone
            ];
        }

        $requestBody = json_encode(['accounts' => $accounts]);

        $this->logger->info('Pending Employee API Request', [
            'url' => $apiUrl,
            'phone_count' => count($phoneNumbers)
        ]);

        // Set headers
        $this->curl->addHeader('APP_ID', $appId);
        $this->curl->addHeader('AppKey', $appKey);
        $this->curl->addHeader('Content-Type', 'application/json');

        // Set timeout
        $this->curl->setOption(CURLOPT_TIMEOUT, 300);
        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);

        try {
            $this->curl->post($apiUrl, $requestBody);
            $responseBody = $this->curl->getBody();
            $httpStatus = $this->curl->getStatus();

            $this->logger->info('Pending Employee API Response', [
                'http_status' => $httpStatus,
                'response_length' => strlen($responseBody)
            ]);

            if ($httpStatus !== 200) {
                throw new \Exception(sprintf('API returned HTTP status %d', $httpStatus));
            }

            $responseData = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
            }

            // API returns array directly
            if (!is_array($responseData)) {
                throw new \Exception('Unexpected API response format');
            }

            return $responseData;

        } catch (\Exception $e) {
            $this->logger->error('Pending Employee API Error', [
                'error' => $e->getMessage(),
                'phone_count' => count($phoneNumbers)
            ]);
            throw $e;
        }
    }
}
