<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CTBC\Model;

use Branch8\CTBC\Helper\Config;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Branch8\CTBC\Helper\Log as CtbcLog;

include_once __DIR__ . '/../Helper/ApiLib/POSAPI.php';

class Api
{
    const TX_AUTH = "TX_AUTH"; // 一次付清
    const TX_INSTMT_AUTH = "TX_INSTMT_AUTH"; //網路分期
    const TX_REDEEM_AUTH = "TX_REDEEM_AUTH"; //紅利交易
    private const LOG_CLASS = 'Api';

    /** @var Config */
    private $config;

    /** @var (null|string)[] $server */
    private $server;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime $date */
    private $date;

    /** @var mixed $order */
    private $order;

    /** @var CtbcLog */
    protected CtbcLog $ctbcLog;

    /**
     * Api constructor.
     *
     * @param Config $config CTBC configuration helper.
     * @param DateTime $date Magento datetime helper.
     * @param CtbcLog $ctbcLog CTBC log facade.
     */
    public function __construct(
        Config $config,
        DateTime $date,
        CtbcLog $ctbcLog
    ) {
        $this->config = $config;
        $this->date = $date;
        $this->ctbcLog = $ctbcLog;
        $this->server = [
            'URL' => $this->config->getServerUrl(),
            'MacKey' => $this->config->getServerMacKey(),
            'Timeout' => $this->config->getServerTimeout(),
        ];
    }

    /**
     * Inquiry an order transaction status from CTBC.
     *
     * @param array $payload Request payload.
     * @return array<mixed>|null Response array on success; null on exception.
     */
    public function inquiryOrder(array $payload)
    {
        error_reporting(E_ERROR);
        $payload['TX_ATTRIBUTE'] = $this->txAttribute($payload);

        $request = [
            'TX_ATTRIBUTE' => $payload['TX_ATTRIBUTE'], // 交易型別
            'MERID' => $this->config->getRequestMerid(), //商店編號設定值
            'LID-M' => $payload['LID-M'], //訂單編號
            'XID' => '', //交易識別碼(Optional)
            'PAN' => '', //16碼信用卡號
            'currency' => $this->config->getRequestCurrency(), //交易幣值代號,台幣 901
            'purchAmt' => $payload['purchAmt'], //交易金額
            'RECUR_NUM' => $this->recurNum($payload), //分期期數
            'PRODCODE' => $this->prodCode($payload), //產品代碼(Optional)
        ];

        try {
            $this->ctbcLog->write("[InquiryTransac Request] " . json_encode($request, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);

            $result = InquiryTransac($this->server, $request);
            return $this->documentResult($result, 'inquiry');
        } catch (\Exception $e) {
            $this->ctbcLog->write($e->getMessage(), self::LOG_CLASS);
        }
    }

    /**
     * Cancel an authorization transaction (AuthRevTransac).
     *
     * @param mixed $payload Request payload.
     * @return array<mixed>|null Response array on success; null on exception.
     */
    public function authRevTransacOrder($payload)
    {
        error_reporting(E_ERROR);
        $request = [
            'MERID' => $this->config->getRequestMerid(), //商店編號設定值
            'MID' => $this->config->getRequestMid(),
            'TID' => $this->config->getRequestTid(),
            'XID' => trim($payload['XID']) ?? '', //交易識別碼
            'AuthRRPID' => $payload['AuthRRPID'] ?? '', //SSL 授權交易之代碼
            'currency' => $this->config->getRequestCurrency(), //交易幣值代號,台幣 901
            'orgAmt' => $payload['orgAmt'] ?? '', //原授權金額
            'authnewAmt' => $payload['authnewAmt'] ?? 0, //重新設定授權金額，0 表示取消此筆授權訂單，此為整數型態
            'exponent' => $payload['exponent'] ?? 0, //為幣值指數，新台幣為 0
            'AuthCode' => $payload['AuthCode'] ?? '', //交易授權碼，長度為 6 的字串
            'TermSeq' => $payload['TermSeq'] ?? '', //調閱序號
        ];

        try {
            $this->ctbcLog->write("[AuthRevTransac Request] " . json_encode($request, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);

            $result = AuthRevTransac($this->server, $request);
            return $this->documentResult($result, 'authRevTransacOrder');
        } catch (\Exception $e) {
            $this->ctbcLog->write($e->getMessage(), self::LOG_CLASS);
        }
    }

    /**
     * Cancel capture/settlement (CapRevTransac).
     *
     * @param mixed $payload Request payload.
     * @return array<mixed>|null Response array on success; null on exception.
     */
    public function capRevTransacOrder($payload)
    {
        error_reporting(E_ERROR);
        $request = [
            'MERID' => $this->config->getRequestMerid(), //商店編號設定值
            'MID' => $this->config->getRequestMid(),
            'TID' => $this->config->getRequestTid(),
            'XID' => trim($payload['XID']) ?? '', //交易識別碼
            'AuthRRPID' => $payload['AuthRRPID'] ?? '', //SSL 授權交易之代碼
            'currency' => $this->config->getRequestCurrency(), //交易幣值代號,台幣 901
            'orgAmt' => $payload['orgAmt'] ?? '', //原授權金額
            'exponent' => $payload['exponent'] ?? 0, //為幣值指數，新台幣為 0
            'AuthCode' => $payload['AuthCode'] ?? '', //交易授權碼，長度為 6 的字串
            'TermSeq' => $payload['TermSeq'] ?? '', //調閱序號
            'BatchID' => $payload['BatchID'] ?? '', //請款之批次編號
            'BatchSeq' => $payload['BatchSeq'] ?? '', //請款之批次序號
        ];

        try {
            $this->ctbcLog->write("[CapRevTransac Request] " . json_encode($request, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);
            
            $result = CapRevTransac($this->server, $request);
            return $this->documentResult($result, 'capRevTransacOrder');
        } catch (\Exception $e) {
            $this->ctbcLog->write($e->getMessage(), self::LOG_CLASS);
        }
    }

    /**
     * Perform a refund transaction (CredTransac).
     *
     * @param mixed $payload Request payload.
     * @return array<mixed>|null Response array on success; null on exception.
     */
    public function credTransacOrder($payload)
    {
        error_reporting(E_ERROR);
        $request = [
            'MERID' => $this->config->getRequestMerid(), //商店編號設定值
            'MID' => $this->config->getRequestMid(),
            'TID' => $this->config->getRequestTid(),
            'XID' => trim($payload['XID']) ?? '', //交易識別碼
            'AuthRRPID' => $payload['AuthRRPID'] ?? '', //SSL 授權交易之代碼
            'currency' => $this->config->getRequestCurrency(), //交易幣值代號,台幣 901
            'orgAmt' => $payload['orgAmt'], //原請款金額
            'credAmt' => $payload['credAmt'], //退款金額
            'exponent' => $payload['exponent'] ?? 0, //為幣值指數，新台幣為 0
            'AuthCode' => $payload['AuthCode'] ?? '', //交易授權碼，長度為 6 的字串
            // 'CapBatchID' => $payload['BatchID'] ?? '', // 請款交易批次編號
            // 'CapBatchSeq' => $payload['BatchSeq'] ?? '', // 請款交易批次序號
        ];

        try {
            $this->ctbcLog->write("[CredTransac Request] " . json_encode($request, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);
            $result = CredTransac($this->server, $request);
            return $this->documentResult($result, 'credTransacOrder');
        } catch (\Exception $e) {
            $this->ctbcLog->write($e->getMessage(), self::LOG_CLASS);
        }
    }

     /**
     * Cancel a refund authorization (CredRevTransac).
     *
     * @param mixed $payload Request payload.
     * @return array<mixed>|null Response array on success; null on exception.
     */
    public function credRevTransacOrder($payload)
    {
        error_reporting(E_ERROR);
        $request = [
            'MERID' => $this->config->getRequestMerid(), //商店編號設定值
            'MID' => $this->config->getRequestMid(),
            'TID' => $this->config->getRequestTid(),
            'XID' => trim($payload['XID']) ?? '', //交易識別碼
            'AuthRRPID' => $payload['AuthRRPID'] ?? '', //SSL 授權交易之代碼
            'currency' => $this->config->getRequestCurrency(), //交易幣值代號,台幣 901
            'orgAmt' => $payload['orgAmt'], //原退款金額
            'exponent' => $payload['exponent'] ?? 0, //為幣值指數，新台幣為 0
            'AuthCode' => $payload['AuthCode'] ?? '', //交易授權碼，長度為 6 的字串
            // 'CredBatchID' => $payload['BatchID'] ?? '', // 請款交易批次編號
            // 'CredBatchSeq' => $payload['BatchSeq'] ?? '', // 請款交易批次序號
        ];

        try {
            $this->ctbcLog->write("[CredRevTransac Request] " . json_encode($request, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);

            $result = CredRevTransac($this->server, $request);
            return $this->documentResult($result, 'CredRevTransacOrder');
        } catch (\Exception $e) {
            $this->ctbcLog->write($e->getMessage(), self::LOG_CLASS);
        }
    }

    /**
     * Resolve transaction attribute value for CTBC request.
     *
     * @param mixed $payload Request payload.
     * @return string Transaction attribute value.
     */
    private function txAttribute($payload)
    {
        //如果有設定交易型別那就採用，預設為一次付清
        return isset($payload['TX_ATTRIBUTE']) ? $payload['TX_ATTRIBUTE'] : self::TX_AUTH;
    }

    /**
     * Normalize and log CTBC API response.
     *
     * @param mixed $result Raw result from POSAPI.
     * @param string $apiName API name for log context.
     * @return array<mixed> Normalized response array; empty array on failure.
     */
    public function documentResult(mixed $result, string $apiName): array
    {
        if (!is_array($result)) {
            $errMsg = "----------- Request Fail-------------------" . $apiName;

            $this->ctbcLog->write($errMsg, self::LOG_CLASS);

            if($result) {
                $this->ctbcLog->write($result, self::LOG_CLASS);
            }

            return [];
        } else {
            $infoMsg = [
                "IncrementId" => $this->order->getIncrementId(),
                "Api Response" => $result,

            ];

            $this->ctbcLog->write("[documentResult] " . json_encode($infoMsg, JSON_UNESCAPED_UNICODE), self::LOG_CLASS);

            return $result;
        }

    }

    /**
     * Resolve installment count for installment transactions.
     *
     * @param array $payload Request payload.
     * @return int Installment count.
     */
    private function recurNum(array $payload)
    {
        //當線上分期時，設定此分期付款期數
        switch ($payload['TX_ATTRIBUTE']) {
            case self::TX_INSTMT_AUTH:
                return (int) $payload['RECUR_NUM'];
            default:
                return 0;
        }
    }

    /**
     * Resolve product code for redeem transactions.
     *
     * @param array $payload Request payload.
     * @return string Product code, or empty string if not applicable.
     */
    private function prodCode(array $payload)
    {
        //當紅利交易時，設定產品代碼
        switch ($payload['TX_ATTRIBUTE']) {
            case self::TX_REDEEM_AUTH:
                return (string) $payload['PRODCODE'];
            default:
                return '';
        }
    }

    /**
     * Set current order context for logging.
     *
     * @param mixed $order Order object (parent order detail).
     * @return void
     */
    public function setOrderInfo($order)
    {
        $this->order = $order;
    }

    /**
     * Get current order context.
     *
     * @return mixed
     */
    public function getOrderInfo()
    {
        return $this->order;
    }
}
