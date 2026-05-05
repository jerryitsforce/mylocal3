<?php
namespace HotaiConnected\FetSms\Model;

use HotaiConnected\FetSms\Api\SmsSenderInterface;
use HotaiConnected\FetSms\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class SmsSender implements SmsSenderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Config $config
     * @param ResourceConnection $resource
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        ResourceConnection $resource,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->resource = $resource;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $phone, string $content, string $callerName): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('fet_sms_smslog');
        $taipeiTime = $this->getTaipeiTime();
        $base64Content = base64_encode($content);

        // 1. 打簡訊前：新增 DB 紀錄
        $logData = [
            'module_name'  => $callerName,
            'phone'        => $phone,
            'content'      => $base64Content,
            'created_at'   => $taipeiTime,
            'responseCode' => '00000', // 初始預設值
        ];

        try {
            $connection->insert($tableName, $logData);
            $logId = $connection->lastInsertId();
        } catch (\Exception $e) {
            $this->logger->error('[FET_SMS] Log insert failed: ' . $e->getMessage());
            $logId = null;
        }

        // 2. 準備 API 請求
        $apiDomain = rtrim((string)$this->config->getApiDomain(), '/');
        $apiPath = (string)$this->config->getApiPath();
        $url = $apiDomain . $apiPath;

        $xmlRequest = $this->buildXmlRequest($phone, $base64Content);
        
        $result = [
            'success' => false,
            'code'    => '',
            'text'    => '',
            'msgId'   => ''
        ];

        try {
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->curl->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]);

            // 傳送資料
            $this->curl->post($url, ['xml' => $xmlRequest]);
            $responseBody = $this->curl->getBody();

            // 解析回應 XML
            $xmlResponse = @simplexml_load_string($responseBody);
            if ($xmlResponse) {
                $result['code']  = (string)$xmlResponse->ResultCode;
                $result['text']  = (string)$xmlResponse->ResultText;
                $result['msgId'] = (string)$xmlResponse->MessageId;
                $result['success'] = ($result['code'] === '00000');
            } else {
                $result['text'] = 'Failed to parse API response: ' . $responseBody;
            }

        } catch (\Exception $e) {
            $result['text'] = 'Curl Error: ' . $e->getMessage();
            $this->logger->error('[FET_SMS] API Request error: ' . $e->getMessage());
        }

        // 3. 打簡訊後：更新 DB 紀錄
        if ($logId) {
            try {
                $updateData = [
                    'responseCode' => $result['code'],
                    'responseText' => $result['text'],
                    'messageId'    => $result['msgId'],
                    'updated_at'   => $this->getTaipeiTime(),
                ];
                $connection->update($tableName, $updateData, ['smslog_id = ?' => $logId]);
            } catch (\Exception $e) {
                $this->logger->error('[FET_SMS] Log update failed: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * 建立請求 XML
     */
    protected function buildXmlRequest(string $phone, string $base64Content): string
    {
        $sysId = $this->config->getSysId();
        $srcAddress = $this->config->getSrcAddress();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<SmssubmitReq>' . "\n";
        $xml .= '    <SysId>' . $sysId . '</SysId>' . "\n";
        $xml .= '    <SrcAddress>' . $srcAddress . '</SrcAddress>' . "\n";
        $xml .= '    <DestAddress>' . $phone . '</DestAddress>' . "\n";
        $xml .= '    <SmsBody>' . $base64Content . '</SmsBody>' . "\n";
        $xml .= '    <DrFlag>false</DrFlag>' . "\n";
        $xml .= '    <FirstFailFlag>false</FirstFailFlag>' . "\n";
        $xml .= '</SmssubmitReq>';

        return $xml;
    }

    /**
     * 取得台北時間 (UTC+8)
     */
    protected function getTaipeiTime(): string
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        return $dateTime->format('Y-m-d H:i:s');
    }
}
