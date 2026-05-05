<?php

namespace Branch8\Every8D\Services;

use Branch8\Every8D\Helper\E8dScopeConfig;
use Branch8\Every8D\Helper\GeneralHelper;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

/**
 * 台灣簡訊 API 服務
 */
class E8dSmsApiService
{
    private string $sendSMSUrl;
    private string $getCreditUrl;
    private string $batchID;
    private string $credit;
    private string $processMsg;
    private string $sendMMSUrl;
    private string $error;
    private readonly string $smsUser;
    private readonly string $smsPassword;
    private GeneralHelper $_loggerInterface;

    public function __construct(
        GeneralHelper $loggerInterface,
        E8dScopeConfig $e8dScopeConfig,
    ) {
        $smsHost                = $e8dScopeConfig->getE8dScopeConfig(E8dScopeConfig::E8D_CONFIG_SERVICE_URL);
        $this->smsUser          = $e8dScopeConfig->getE8dScopeConfig(E8dScopeConfig::E8D_CONFIG_SMS_USER);
        $this->smsPassword      = $e8dScopeConfig->getE8dScopeConfig(E8dScopeConfig::E8D_CONFIG_SMS_PASSWORD);
        $this->sendSMSUrl       = "https://" . $smsHost . "/API21/HTTP/sendSMS.ashx";
        $this->sendMMSUrl       = "https://" . $smsHost . "/API21/HTTP/MMS/sendMMS.ashx";
        $this->getCreditUrl     = "https://" . $smsHost . "/API21/HTTP/getCredit.ashx";
        $this->batchID          = "";
        $this->credit           = 0.0;
        $this->processMsg       = "";
        $this->_loggerInterface = $loggerInterface;
    }

    /**
     *  取得帳號餘額
     * @return bool 是否成功獲取餘額
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function getCredit(): bool
    {
        $success        = false;
        $postArr["UID"] = $this->smsUser;
        $postArr["PWD"] = $this->smsPassword;
        $resultString   = $this->httpPost($this->getCreditUrl, $postArr);
        if ($resultString[0] === "-") {
            $this->processMsg = $resultString;
        } else {
            $success      = true;
            $this->credit = $resultString;
        }
        return $success;
    }

    /**
     *  傳送簡訊
     * @param string $subject 簡訊主旨
     * @param string $content 簡訊內容
     * @param string $phone 手機號碼
     * @param string $sendTime 預定發送時間
     * @return array 發送結果 ['status' => bool, 'message' => string]
     * @return array
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function sendSMS(string $subject, string $content, string $phone, string $sendTime = ''): array
    {
        $status = false;

        $postArr["UID"]  = $this->smsUser;
        $postArr["PWD"]  = $this->smsPassword;
        $postArr["SB"]   = $subject;
        $postArr["MSG"]  = $content;
        $postArr["DEST"] = $phone;
        $postArr["ST"]   = $sendTime;

        $resultString = $this->httpPost($this->sendSMSUrl, $postArr);

        if ($resultString[0] === "-") {
            $this->processMsg = $resultString;
        } else {
            $status   = true;
            $strArray = explode(",", $resultString);
            $this->_loggerInterface->writeLog('SMS send result: '. $resultString);
            $this->credit  = $strArray[0];
            $this->batchID = $strArray[4] ?? '';
        }

        return [
            'status'  => $status,
            'message' => $status ? $this->batchID : $this->error
        ];
    }

    /**
     * 傳送多媒體簡訊(MMS)
     */
    public function sendMMS($userID, $password, $subject, $content, $attachment, $type, $mobile, $sendTime): bool
    {
        $success               = false;
        $postArr["UID"]        = $userID;
        $postArr["PWD"]        = $password;
        $postArr["SB"]         = $subject;
        $postArr["MSG"]        = $content;
        $postArr["DEST"]       = $mobile;
        $postArr["ST"]         = $sendTime;
        $postArr["TYPE"]       = $type;
        $postArr["ATTACHMENT"] = $attachment;
        $resultString          = $this->httpPost($this->sendMMSUrl, $postArr);
        if ($resultString[0] === "-") {
            $this->processMsg = $resultString;
        } else {
            $success       = true;
            $strArray      = explode(",", $resultString);
            $this->credit  = $strArray[0];
            $this->batchID = $strArray[4];
        }
        return $success;
    }

    /**
     * @param $url
     * @param $postArray
     * @return string
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function httpPost($url, $postArray): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postArray));
        $res = curl_exec($curl);
        curl_close($curl);

        if ($res === false) {
            $this->_loggerInterface->writeLog('SMSHttp: result: false');
            $this->error = curl_error($curl);
        }

        $strArray      = explode("\r\n\r\n", $res);
        $response      = explode(',', $strArray[1]);
        $this->credit  = $response[0];//剩餘點數
        $this->batchID = $response[4];//識別碼

        $idx = count($strArray) - 1;

        return $strArray[$idx];
    }
}
