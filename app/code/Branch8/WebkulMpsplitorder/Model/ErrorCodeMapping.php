<?php

declare(strict_types=1);


namespace Branch8\WebkulMpsplitorder\Model;

class ErrorCodeMapping
{
    const ERROR_CODE_SPE01 = 'SPE-0001';

    const ERROR_CODE_SPE02 = 'SPE-0002';

    /** Master Quote 驗證失敗 / Master Quote validation failed */
    const ERROR_CODE_SPE02_01 = 'SPE-0002-01';

    /** Hotai 保留訂單編號為空 / Hotai reserved order ID is empty */
    const ERROR_CODE_SPE02_02 = 'SPE-0002-02';

    /** 子單 orderId 為空 / Sub-order orderId is empty */
    const ERROR_CODE_SPE02_03 = 'SPE-0002-03';

    /** 子單迴圈例外 / Exception while looping through sub-quotes */
    const ERROR_CODE_SPE02_04 = 'SPE-0002-04';

    /** Free 付款方式但金額不為 0 / Free payment method but grand total is not 0 */
    const ERROR_CODE_SPE02_05 = 'SPE-0002-05';

    /** 非 Free 付款方式但總金額 ≤ 0 / Non-free payment but total amount <= 0 */
    const ERROR_CODE_SPE02_06 = 'SPE-0002-06';

    /** 金額不一致驗證失敗 / Grand total mismatch between sub-orders and master quote */
    const ERROR_CODE_SPE02_07 = 'SPE-0002-07';

    /** 連結父子訂單例外 / Exception when linking parent order with child orders */
    const ERROR_CODE_SPE02_08 = 'SPE-0002-08';

    /** 通用例外處理 Exception / Generic catch-all exception */
    const ERROR_CODE_SPE02_09 = 'SPE-0002-09';

    const ERROR_CODE_SPE03 = 'SPE-0003';

    const ERROR_CODE_SPE05 = 'SPE-0005';

    /** SPE-0002 sub-codes share the same user-facing message as SPE-0002 */
    private $spe02SubCodes = [
        self::ERROR_CODE_SPE02_01,
        self::ERROR_CODE_SPE02_02,
        self::ERROR_CODE_SPE02_03,
        self::ERROR_CODE_SPE02_04,
        self::ERROR_CODE_SPE02_05,
        self::ERROR_CODE_SPE02_06,
        self::ERROR_CODE_SPE02_07,
        self::ERROR_CODE_SPE02_08,
        self::ERROR_CODE_SPE02_09,
    ];

    private $errorMessagesMapping = [
        self::ERROR_CODE_SPE01 => 'Order creation failed. Please try again.',
        self::ERROR_CODE_SPE02 => "Sorry, we couldn't process your request. Please try again later.",
        self::ERROR_CODE_SPE03 => 'Insufficient points balance.',
        self::ERROR_CODE_SPE05 => 'There was a problem processing your points. Please try again.'
    ];

    /**
     * @param $code
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getErrorMessage($code, $errorMessage = null, $processMessage = false)
    {
        // SPE-0002 sub-codes: show sub-code in prefix but use SPE-0002 user-facing message
        $isSpe02Sub = in_array($code, $this->spe02SubCodes);

        if ($errorMessage !== null) {
            if ($err = json_decode($errorMessage, true)) {
                $msg = array_values($err);
                $message = $msg[0];
                $code = $this->changeErrorCode($code, $message);
                $message = $this->changeErrorMessage($code, $message, $processMessage);
                return $code . ': ' . __($message)->render();
            }

            $code = $this->changeErrorCode($code, $errorMessage);
            $errorMessage = $this->changeErrorMessage($code, $errorMessage, $processMessage);
            return $code . ': ' . __($errorMessage)->render();
        } else if ($isSpe02Sub) {
            return $code . ':' . __($this->errorMessagesMapping[self::ERROR_CODE_SPE02])->render();
        } else if (isset($this->errorMessagesMapping[$code])) {
            return $code . ':' . __($this->errorMessagesMapping[$code])->render();
        }
        return __($code);
    }

    private function changeErrorCode($code, $errorMessage)
    {
        if ($errorMessage == $this->errorMessagesMapping[self::ERROR_CODE_SPE03]) {
            $code = self::ERROR_CODE_SPE03;
        } else if ($errorMessage == $this->errorMessagesMapping[self::ERROR_CODE_SPE05]) {
            $code = self::ERROR_CODE_SPE05;
        }
        return $code;
    }

    protected function changeErrorMessage($code, $errorMessage, $processMessage)
    {
        $isSpe02Family = ($code == self::ERROR_CODE_SPE02 || in_array($code, $this->spe02SubCodes));
        if ($isSpe02Family && $processMessage) {
            if (!in_array($errorMessage, [
                $this->errorMessagesMapping[self::ERROR_CODE_SPE03],
                $this->errorMessagesMapping[self::ERROR_CODE_SPE05]
            ])) {
                return $this->errorMessagesMapping[self::ERROR_CODE_SPE02];
            }
        }
        return $errorMessage;
    }

    public function getRawErrorMessageByErrorCode(string $code)
    {
        // SPE-0002 sub-codes map to the same message as SPE-0002
        if (in_array($code, $this->spe02SubCodes)) {
            return $this->errorMessagesMapping[self::ERROR_CODE_SPE02];
        }

        if (!isset($this->errorMessagesMapping[$code])) {
            return "Unknown WebkulMpsplitorder error code for mapping: {$code}";
        }

        return $this->errorMessagesMapping[$code];
    }
}
