<?php

declare(strict_types=1);

namespace HotaiConnected\Cart\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class CvsStoreService
{
    /**
     * Whitelist of CVS fields returned by ECPay map callback.
     */
    const CVS_FIELD_WHITELIST = [
        'storeid',
        'storename',
        'address',
        'servicetype',
        'outside',
        'ship',
    ];

    /**
     * Query parameter prefix to avoid collision with other params.
     */
    const QUERY_PREFIX = 'cvs_';

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Extract whitelisted CVS fields from POST data, save to session.
     *
     * @param array $postData Raw POST from ECPay callback
     * @param string $type "checkout" or "address"
     * @return array Filtered CVS data
     */
    public function extractAndSave(array $postData, string $type): array
    {
        $filtered = [];
        foreach (self::CVS_FIELD_WHITELIST as $key) {
            if (isset($postData[$key]) && (string)$postData[$key] !== '') {
                $filtered[$key] = (string)$postData[$key];
            }
        }

        $json = json_encode($postData, JSON_UNESCAPED_UNICODE);

        if ($type === 'checkout') {
            $this->customerSession->setStoreCheckoutData($json);
        } else {
            $this->customerSession->setStoreData($json);
        }

        $this->logger->info('[CvsStoreService] Saved CVS data', [
            'type' => $type,
            'storeid' => $filtered['storeid'] ?? '',
            'storename' => $filtered['storename'] ?? '',
        ]);

        return $filtered;
    }

    /**
     * Build redirect URL with CVS fields as query parameters.
     *
     * @param string $baseUrl
     * @param array $filteredData
     * @param string $hash Hash fragment to append (e.g. "#/checkout")
     * @return string
     */
    public function buildRedirectUrl(string $baseUrl, array $filteredData, string $hash = ''): string
    {
        if (empty($filteredData)) {
            return $baseUrl . $hash;
        }

        $params = [];
        foreach ($filteredData as $key => $value) {
            $params[self::QUERY_PREFIX . $key] = $value;
        }

        $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
        return $baseUrl . $separator . http_build_query($params) . $hash;
    }

    /**
     * Read current CVS store data from session.
     *
     * @param string $type "checkout" or "address"
     * @return array|null
     */
    public function getCurrentStore(string $type): ?array
    {
        $json = ($type === 'checkout')
            ? $this->customerSession->getStoreCheckoutData()
            : $this->customerSession->getStoreData();

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Clear CVS store data from session (both keys).
     */
    public function clear(): void
    {
        $this->customerSession->setStoreCheckoutData(null);
        $this->customerSession->setStoreData(null);
    }

    /**
     * Validate that redirect URL belongs to the same store domain.
     *
     * @param string $url
     * @param string $baseUrl
     * @return bool
     */
    public function isAllowedRedirectUrl(string $url, string $baseUrl): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);

        if (!$urlHost || !$baseHost) {
            return false;
        }

        return $urlHost === $baseHost;
    }
}
