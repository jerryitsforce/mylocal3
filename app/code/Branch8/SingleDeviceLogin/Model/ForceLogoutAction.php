<?php

namespace Branch8\SingleDeviceLogin\Model;

use Branch8\SingleDeviceLogin\Helper\Logger as CustomLogger;
use Magento\Framework\HTTP\Header;
class ForceLogoutAction
{
    private \Branch8\SingleDeviceLogin\Model\ConfigData $configData;
    private \Magento\Framework\HTTP\Client\Curl $curl;
    private CustomLogger $logger;
    private Header $header;

    /**
     * @param ConfigData $configData
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param CustomLogger $logger
     * @param Header $header
     */
    public function __construct(
        \Branch8\SingleDeviceLogin\Model\ConfigData $configData,
        \Magento\Framework\HTTP\Client\Curl         $curl,
        CustomLogger                                $logger,
        Header                                      $header
    )
    {
        $this->logger = $logger;
        $this->curl = $curl;
        $this->configData = $configData;
        $this->header = $header;
    }

    /**
     * @return bool
     */
    private function isHotaiApp(): bool
    {
        return stristr($this->header->getHttpUserAgent(), 'HotaiApp') !== false;
    }
    /**
     * @param $id
     * @param $newToken
     * @param $deviceType
     * @param $force
     * @return array
     */
    public function execute($id, $newToken, $deviceType, $force = false)
    {
        $url = $this->configData->getForceLogoutUrl();
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-KEY' => ConfigData::XSECRECTKEY
        ];
        $this->curl->setHeaders($headers);
        $deviceType = $this->isHotaiApp() ? 'app' : 'web';
        $data = [
            'user_id' => $id,
            'token' => $newToken,
            'forced' => $force,
            'device_type' => $deviceType
        ];
        $this->logger->info(
            'Branch8\SingleDeviceLogin\Observer\ForceAnotherSessionLogout::BEGIN' . print_r($data, true)
        );
        // Optional config
        $this->curl->setOption(CURLOPT_TIMEOUT, 30);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        try {
            $this->curl->post($url, json_encode($data));
            $status = $this->curl->getStatus();
            $body = $this->curl->getBody();
            $bodyDecode = json_decode($body, true);
            $result = [
                'status' => $this->curl->getStatus(),
                'body' => $bodyDecode,
            ];
            $this->logger->info(
                'Branch8\SingleDeviceLogin\Observer\ForceAnotherSessionLogout::END' . print_r($result, true)
            );
        } catch (\Exception $e) {
            $this->logger->critical('ERROR WHEN EXECUTE FORCE LOGOUT');
            $this->logger->critical('Branch8\SingleDeviceLogin\Model\ForceLogoutAction::' . $e->getMessage());
            return [
                'status' => false,
                'body' => ''
            ];
        }
        return [
            'status' => $status,
            'body' => $result
        ];
    }
}
