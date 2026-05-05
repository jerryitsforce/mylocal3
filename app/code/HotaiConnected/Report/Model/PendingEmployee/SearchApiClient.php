<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Model\PendingEmployee;

use HotaiConnected\Report\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class SearchApiClient
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
     * Search members by One IDs
     *
     * @param array $memberIds Array of One IDs (memberIds)
     * @return array Array of member data with memberId, countryCode, mobilePhone
     * @throws \Exception
     */
    public function searchMembers(array $memberIds): array
    {
        $apiUrl = $this->config->getPendingEmployeeSearchApiUrl();
        $appId = $this->config->getPendingEmployeeAppId();
        $appKey = $this->config->getPendingEmployeeAppKey();

        if (empty($apiUrl) || empty($appId) || empty($appKey)) {
            throw new \Exception('Search API configuration is incomplete. Please check admin settings.');
        }

        $this->logger->info('SearchApiClient: Calling search-member API', [
            'url' => $apiUrl,
            'member_ids_count' => count($memberIds)
        ]);

        $payload = json_encode(['memberIds' => $memberIds]);

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'APP_ID: ' . $appId,
            'AppKey: ' . $appKey
        ]);
        $this->curl->post($apiUrl, $payload);

        $response = $this->curl->getBody();
        $statusCode = $this->curl->getStatus();

        $this->logger->info('SearchApiClient: API response', [
            'status_code' => $statusCode,
            'response_length' => strlen($response)
        ]);

        if ($statusCode !== 200) {
            $this->logger->error('SearchApiClient: API call failed', [
                'status_code' => $statusCode,
                'response' => $response
            ]);
            throw new \Exception('Search API call failed with status code: ' . $statusCode);
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('SearchApiClient: Failed to parse API response', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500)
            ]);
            throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
        }

        return $result ?: [];
    }
}
