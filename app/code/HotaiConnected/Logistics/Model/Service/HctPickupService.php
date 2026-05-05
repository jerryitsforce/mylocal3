<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Service;

use Psr\Log\LoggerInterface;

/**
 * Hsinchu Logistics (HCT) Pickup Service
 */
class HctPickupService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * HCT SOAP Service Endpoint
     */
    const WSDL_URL = 'https://Hctrt.hct.com.tw/EDI_WebService2/Service1.asmx?WSDL';

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Request pickup number from HCT API
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $orderItems
     * @param \HotaiConnected\Logistics\Model\LogisticsSettings $settings
     * @return array
     * @throws \Exception
     */
    public function requestPickup($order, array $orderItems, $settings)
    {
        // Get auth data
        $authData = json_decode($settings->getAuthData(), true);
        if (!isset($authData['account']) || !isset($authData['password'])) {
            throw new \Exception(__('新竹物流認證資料不完整'));
        }

        // Prepare request data
        $requestData = $this->prepareRequestData($order, $orderItems);
        $jsonData = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        // Log request
        $this->logger->info('HCT Pickup Request', [
            'order_id' => $order->getIncrementId(),
            'company' => $authData['account'],
            'request_data' => $requestData,
            'request_json' => $jsonData
        ]);

        try {
            // Create SOAP client with options for large responses (image data)
            $soapClient = new \SoapClient(self::WSDL_URL, [
                'trace' => 1,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 60,
                'default_socket_timeout' => 60,
                'keep_alive' => false,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                'stream_context' => stream_context_create([
                    'http' => [
                        'timeout' => 60,
                        'protocol_version' => 1.1,
                    ],
                    'ssl' => [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ]
                ])
            ]);

            // Call TransData_Json method with named parameters (required by WSDL)
            $response = $soapClient->TransData_Json([
                'company' => $authData['account'],
                'password' => $authData['password'],
                'json' => $jsonData
            ]);

            // Get response result
            $responseData = $response->TransData_JsonResult ?? null;

            if (!$responseData) {
                throw new \Exception(__('新竹物流 API 回應為空'));
            }

            // Parse JSON response
            $responseArray = json_decode($responseData, true);

            // Create log-friendly version (replace image strings with '-')
            $logResponseArray = $this->sanitizeImageDataForLog($responseArray);

            // Log parsed response (without large image strings)
            $this->logger->info('HCT Pickup Response', [
                'order_id' => $order->getIncrementId(),
                'response_data' => $logResponseArray
            ]);

            if (!$responseArray) {
                throw new \Exception(__('新竹物流 API 回應格式錯誤: %1', $responseData));
            }

            // Parse response
            return $this->parseResponse($responseArray, $orderItems);

        } catch (\SoapFault $e) {
            $this->logger->error('HCT SOAP Error', [
                'order_id' => $order->getIncrementId(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'request_json' => $jsonData ?? null,
                'last_request' => isset($soapClient) ? $soapClient->__getLastRequest() : null,
                'last_response' => isset($soapClient) ? $soapClient->__getLastResponse() : null
            ]);
            throw new \Exception(__('新竹物流 SOAP 呼叫失敗: %1', $e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error('HCT API Error', [
                'order_id' => $order->getIncrementId(),
                'error_message' => $e->getMessage(),
                'request_json' => $jsonData ?? null
            ]);
            throw new \Exception(__('新竹物流 API 呼叫失敗: %1', $e->getMessage()));
        }
    }

    /**
     * Prepare request data for HCT API
     *
     * HCT API 格式:
     * [
     *   {
     *     "epino": "A00001",      // 訂單編號 (必要)
     *     "ercsig": "Mary",       // 收貨人名稱 (必要)
     *     "ertel1": "0911123456", // 收貨人電話 (必要)
     *     "eraddr": "台中市...",   // 收貨人地址 (必要)
     *     "ejamt": "1",           // 件數 (必要)
     *     "eqamt": "10"           // 重量 (必要)
     *   }
     * ]
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $orderItems
     * @return array
     */
    protected function prepareRequestData($order, array $orderItems)
    {
        $shippingAddress = $order->getShippingAddress();
        $items = [];

        foreach ($orderItems as $orderItem) {
            // 計算總重量 (如果沒有重量則預設為 1)
            $weight = $orderItem->getWeight() ?: 1;
            $totalWeight = $weight * (int)$orderItem->getQtyOrdered();

            $items[] = [
                'epino' => (string)$orderItem->getId(),                          // Sales Order Item ID
                'ercsig' => mb_substr($shippingAddress->getName(), 0, 40),       // 收貨人名稱 (最多40字元)
                'ertel1' => substr($shippingAddress->getTelephone(), 0, 15),     // 電話 (最多15字元)
                'eraddr' => mb_substr(implode('', $shippingAddress->getStreet()), 0, 100), // 地址 (最多100字元)
                'ejamt' => (string)(int)$orderItem->getQtyOrdered(),             // 件數 (最多4字元)
                'eqamt' => (string)min((int)$totalWeight, 99999)                 // 重量 (最多5字元)
            ];
        }

        return $items;
    }

    /**
     * Parse HCT API response
     *
     * Response format:
     * [
     *   {
     *     "Num": "1",
     *     "success": "Y",
     *     "edelno": "0000000001",
     *     "epino": "A00001",
     *     "erstno": "4004",
     *     "eqamt": "10",
     *     "image": "base64_string",
     *     "ErrMsg": null
     *   }
     * ]
     *
     * @param array $responseData
     * @param array $orderItems
     * @return array
     */
    protected function parseResponse($responseData, $orderItems)
    {
        $result = [];

        foreach ($responseData as $index => $item) {
            // Get corresponding order item
            $orderItem = isset($orderItems[$index]) ? $orderItems[$index] : null;

            if (!$orderItem) {
                $this->logger->warning('No matching order item for HCT response index: ' . $index);
                continue;
            }

            $status = 'pending';
            if (isset($item['success'])) {
                switch ($item['success']) {
                    case 'Y':
                        $status = 'success';
                        break;
                    case 'R':
                        $status = 'modified';
                        break;
                    case 'N':
                        $status = 'failed';
                        break;
                }
            }

            // Prepare status as JSON
            $statusData = [
                'code' => $status,
                'original_code' => $item['success'] ?? null,
                'message' => $item['ErrMsg'] ?? null
            ];

            $result[] = [
                'order_item_id' => $orderItem->getId(),
                'logistics_company_id' => '1', // HCT company ID
                'waybill_number' => $item['edelno'] ?? null,
                'tracking_number' => $item['epino'] ?? null,
                'image' => $item['image'] ?? null, // Keep full image data here
                'status' => json_encode($statusData),
                'memo' => $this->sanitizeImageDataForMemo($item) // Only raw_response with image replaced
            ];
        }

        return $result;
    }

    /**
     * Replace image strings with '-' for logging to save space
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeImageDataForLog($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeImageDataForLog($value);
            } elseif ($key === 'image' && is_string($value) && strlen($value) > 100) {
                // Replace long image strings with '-'
                $sanitized[$key] = '-';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Replace image strings with '-' for memo to save database space
     *
     * @param array $item
     * @return array
     */
    protected function sanitizeImageDataForMemo($item)
    {
        $sanitized = $item;

        // Replace image with '-' in memo (actual image is stored in separate column)
        if (isset($sanitized['image']) && is_string($sanitized['image']) && strlen($sanitized['image']) > 100) {
            $sanitized['image'] = '-';
        }

        return $sanitized;
    }
}
