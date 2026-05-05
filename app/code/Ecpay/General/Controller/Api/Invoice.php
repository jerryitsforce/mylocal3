<?php

namespace Ecpay\General\Controller\Api;

use AllowDynamicProperties;
use Ecpay\Sdk\Exceptions\RtnException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;

use Ecpay\Sdk\Factories\Factory;
use Zend_Log_Exception;

#[AllowDynamicProperties] class Invoice implements \Ecpay\General\Api\InvoiceInterface
{

    protected $_loggerInterface;
    protected $_urlInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    protected $_encryptionsHelper;
    protected $_generalHelper;

    public function __construct(
        GeneralHelper $loggerInterface,
        UrlInterface $urlInterface,
        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,
        GeneralHelper $generalHelper
    ) {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;
        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;

        $this->_generalHelper = $generalHelper;
    }

    public function getInvoiceUrl($orderId, $protectCode, $isApi = true)
    {
        $responseArray = [
            'code' => '0000',
            'msg' => '這是列印發票API',
            'data' => '',
        ];

        if ($isApi) {
            // 解密訂單編號
            $enctyOrderId = str_replace(' ', '+', $orderId);
            $orderId = $this->_encryptionsService->decrypt($enctyOrderId);
            $orderId = (int) $orderId;
            $this->_loggerInterface->writeLog('getInvoiceTag enctyOrderId:' . print_r($enctyOrderId, true));
        }

        $this->_loggerInterface->writeLog('getInvoiceTag orderId:' . print_r($orderId, true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->writeLog('getInvoiceTag protectCodeFromOrder:' . print_r($protectCodeFromOrder, true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {
            $this->_loggerInterface->writeLog('getInvoiceTag protect_code驗證錯誤:' . print_r($protectCode, true));

            // 轉為JSON格式
            $responseArray = [
                'code' => '0001',
                'msg' => __('code_0001'),
                'data' => '',
            ];

            return $responseArray;
        }

        // 發票欄位資訊
        $ecpayInvoicePrint = $this->_orderService->getEcpayInvoicePrint($orderId);
        $ecpayInvoiceNumber = $this->_orderService->getEcpayInvoiceNumber($orderId);
        $ecpayInvoiceDate = $this->_orderService->getEcpayInvoiceDate($orderId);

        if ($ecpayInvoicePrint != 1 || empty($ecpayInvoiceNumber || empty($ecpayInvoiceDate))) {
            $responseArray = [
                'code' => '0002',
                'msg' => __('code_0002'),
                'data' => '',
            ];

            return $responseArray;
        }

        // 取得發票會員資訊
        $accountInfo = $this->_invoiceService->getAccountInfo();

        // 存在發票號碼

        $factory = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv' => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->_invoiceService->getApiUrl('invoice_print');
        $this->_loggerInterface->writeLog('invalidInvoice apiUrl:' . print_r($apiUrl, true));

        // 組合送綠界格式
        $data = [
            'MerchantID' => $accountInfo['MerchantId'],
            'InvoiceNo' => $ecpayInvoiceNumber,
            'InvoiceDate' => $ecpayInvoiceDate,
            'PrintStyle' => 1,
            'IsShowingDetail' => 1,
        ];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '3.0.0',
            ],

            'Data' => $data,
        ];

        $response = $postService->post($input, $apiUrl);

        $this->_loggerInterface->writeLog('invalidInvoice input:' . print_r($input, true));
        $this->_loggerInterface->writeLog('invalidInvoice response:' . print_r($response, true));

        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
            $ecpayInvoicePrintViews = $this->_orderService->getEcpayInvoicePrintViews($orderId);
            $ecpayInvoicePrintViews = (int) $ecpayInvoicePrintViews + 1;

            // 成功的話次數+1
            $this->_orderService->setOrderData($orderId, 'ecpay_invoice_print_views', $ecpayInvoicePrintViews);

            // 回傳成功狀態到前端
            // 轉為JSON格式
            $responseArray = [
                'code' => '0999',
                'msg' => __('code_0999'),
                'data' => $response['Data']['InvoiceHtml'],
            ];

            return $responseArray;
        }

        // 回傳資料寫入備註
        $comment = '(' . $response['Data']['RtnCode'] . ')' . $response['Data']['RtnMsg'];
        $status = false;
        $isVisibleOnFront = false;

        $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

        // 轉為JSON格式
        $responseArray = [
            'code' => '1006',
            'msg' => __('code_1006'),
            'data' => '',
        ];

        return $responseArray;
    }

    /**
     * @param string $unifiedBusinessNo
     * @return void
     * @throws Exception
     * @throws RtnException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function checkBusinessNumber(string $unifiedBusinessNo)
    {
        // 統編格式驗證
        if (strlen($unifiedBusinessNo) === 7) {
            $unifiedBusinessNo = str_pad($unifiedBusinessNo, 8, "0", STR_PAD_LEFT);
        }

        // 正則表達式驗證：必須為8位數字
        if (!preg_match('/^\d{8}$/', $unifiedBusinessNo)) {
            throw new Exception(__('code_1008'), '1008');
        }

        $eachChar = str_split($unifiedBusinessNo);
        $weight = [1, 2, 1, 2, 1, 2, 4, 1];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            // 轉換字元為整數
            $idArray = (int) $eachChar[$i];
            $p = $idArray * $weight[$i];
            $s = (int)($p / 10) + ($p % 10);
            // 如果 s 等於 10，則設為 0
            $s = ($s === 10) ? 0 : $s;
            $sum += $s;
        }

        // 當 s[6]==7 時，計算 sum 時可能會有兩種情況（0 或 10），
        // 如果使用 0 和 10 取餘數，兩者都是 0；如果使用 1 取餘數為 0，可以反推餘數應該是 9
        $checkNumber = 5;
        $isLegal = ($sum % $checkNumber === 0) || (($sum + 1) % $checkNumber === 0 && (int) $eachChar[6] === 7);

        if (!$isLegal) {
            throw new Exception(__('code_1009'), '1009');
        }

        /**
         * 以下為呼叫綠界API驗證統編名稱，因綠界API可能會有延遲問題，故註解，以正則表達為主
         */
//        // 取得發票會員資訊
//        $accountInfo = $this->_invoiceService->getAccountInfo();
//
//        $factory = new Factory([
//            'hashKey' => $accountInfo['HashKey'],
//            'hashIv' => $accountInfo['HashIv'],
//        ]);
//
//        $postService = $factory->create('PostWithAesJsonResponseService');
//
//        // 取出 URL
//        $apiUrl = $this->_invoiceService->getApiUrl('get_company_name_by_tax_id');
//        $this->_loggerInterface->writeLog('getCompanyNameByTaxID apiUrl:' . print_r($apiUrl, true));
//
//        // 組合送綠界格式
//        $data = [
//            'MerchantID' => $accountInfo['MerchantId'],
//            'UnifiedBusinessNo' => $unifiedBusinessNo,
//        ];
//
//        $input = [
//            'MerchantID' => $accountInfo['MerchantId'],
//            'RqHeader' => [
//                    'Timestamp' => time(),
//                    'Revision' => '3.0.0',
//                ],
//            'Data' => $data,
//        ];
//
//        $response = $postService->post($input, $apiUrl);
//
//        $this->_loggerInterface->writeLog('getCompanyNameByTaxID input:' . print_r($input, true));
//        $this->_loggerInterface->writeLog('getCompanyNameByTaxID response:' . print_r($response, true));
//
//        // 呼叫財政部API失敗
//        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
//            $this->_loggerInterface->writeLog(__('code_1901'));
//            throw new Exception(__('code_1901'), '1901');
//        }
//
//        // 統編驗證失敗
//        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1) {
//            throw new Exception(__('code_1009'), '1009');
//        }
//
//        // 轉為JSON格式
//        $responseArray = [
//            'code' => '0999',
//            'msg' => __('code_0999'),
//            'data' => $response['Data']['CompanyName'],
//        ];

        // 轉為JSON格式
        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => '',
        ];

        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function checkBarcode($barcode)
    {
        // 手機條碼格式驗證
        if (!preg_match('/^\/{1}[0-9a-zA-Z+-.]{7}$/', $barcode)) {
            throw new Exception(__('code_1008'), '1008');
        }

        // 取得發票會員資訊
        $accountInfo = $this->_invoiceService->getAccountInfo();

        $factory = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv' => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->_invoiceService->getApiUrl('check_barcode');
        $this->_loggerInterface->writeLog('checkBarcode apiUrl:' . print_r($apiUrl, true));

        // 組合送綠界格式
        $data = [
            'MerchantID' => $accountInfo['MerchantId'],
            'BarCode' => $barcode,
        ];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '3.0.0',
            ],
            'Data' => $data,
        ];

        $response = $postService->post($input, $apiUrl);

        $this->_loggerInterface->writeLog('checkBarcode input:' . print_r($input, true));
        $this->_loggerInterface->writeLog('checkBarcode response:' . print_r($response, true));

        // 呼叫財政部API失敗
        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
            $this->_loggerInterface->writeLog(__('code_1901'));
            throw new Exception(__('code_1901'), '1901');
        }

        // 手機條碼驗證失敗
        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
            throw new Exception(__('code_1009'), '1009');
        }

        // 轉為JSON格式
        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => '',
        ];

        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function checkLoveCode($loveCode)
    {
        // 捐贈碼格式驗證
        if (!preg_match('/^([xX]{1}[0-9]{2,6}|[0-9]{3,7})$/', $loveCode)) {
            throw new Exception(__('code_1010'), '1010');
        }

        // 取得發票會員資訊
        $accountInfo = $this->_invoiceService->getAccountInfo();

        $factory = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv' => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->_invoiceService->getApiUrl('check_love_code');
        $this->_loggerInterface->writeLog('checkLoveCode apiUrl:' . print_r($apiUrl, true));

        // 組合送綠界格式
        $data = [
            'MerchantID' => $accountInfo['MerchantId'],
            'LoveCode' => $loveCode,
        ];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '3.0.0',
            ],
            'Data' => $data,
        ];

        $response = $postService->post($input, $apiUrl);

        $this->_loggerInterface->writeLog('checkLoveCode input:' . print_r($input, true));
        $this->_loggerInterface->writeLog('checkLoveCode response:' . print_r($response, true));

        // 呼叫財政部API失敗
        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 9000001) {
            $this->_loggerInterface->writeLog(__('code_1901'));
            throw new Exception(__('code_1901'), '1901');
        }

        // 捐贈碼驗證失敗
        if (!isset($response['Data']['RtnCode']) || $response['Data']['RtnCode'] != 1 || $response['Data']['IsExist'] == 'N') {
            throw new Exception(__('code_1011'), '1011');
        }

        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => '',
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }


    /**
     * {@inheritdoc}
     */
    public function checkCitizenDigitalCertificate($carrierNumber)
    {
        // 自然人憑證格式驗證
        if (!preg_match('/^[a-zA-Z]{2}\d{14}$/', $carrierNumber)) {
            throw new Exception(__('code_1012'), '1012');
        }

        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => '',
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function createInvoice($orderId, $protectCode)
    {
        $responseArray = [
            'code' => '0000',
            'msg' => '這是開立發票API',
            'data' => '',
        ];

        $enctyOrderId = str_replace(' ', '+', $orderId);
        $orderId = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId;

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->writeLog('createInvoice protectCodeFromOrder:' . print_r($protectCodeFromOrder, true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {
            $this->_loggerInterface->writeLog('createInvoice protect_code驗證錯誤:' . print_r($protectCode, true));

            // 轉為JSON格式
            $responseArray = [
                'code' => '0001',
                'msg' => __('code_0001'),
                'data' => '',
            ];
        } else {
            $responseArray = $this->_invoiceService->invoiceIssue($orderId);
        }
        $this->_loggerInterface->writeLog('createInvoice responseArray:' . print_r($responseArray, true));

        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidInvoice($orderId, $protectCode)
    {
        $responseArray = [
            'code' => '0000',
            'msg' => '這是作廢發票API',
            'data' => '',
        ];

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId);
        $orderId = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId;

        $this->_loggerInterface->writeLog('invalidInvoice enctyOrderId:' . print_r($enctyOrderId, true));
        $this->_loggerInterface->writeLog('invalidInvoice orderId:' . print_r($orderId, true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->writeLog(
            'invalidInvoice protectCodeFromOrder:' . print_r($protectCodeFromOrder, true)
        );

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {
            $this->_loggerInterface->writeLog('invalidInvoice protect_code驗證錯誤:' . print_r($protectCode, true));

            // 轉為JSON格式
            $responseArray = [
                'code' => '0001',
                'msg' => __('code_0001'),
                'data' => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            $this->response = json_encode($responseArray);
            print_r($this->response, false);
            die();
        }

        // 判斷是否有發票要作廢
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        $this->_loggerInterface->writeLog('invalidInvoice ecpayInvoiceTag:' . print_r($ecpayInvoiceTag, true));

        if ($ecpayInvoiceTag == 1) {
            $responseArray = $this->_invoiceService->invalidInvoice($orderId);
        } else {
            // 轉為JSON格式
            $responseArray = [
                'code' => '1005',
                'msg' => __('code_1005'),
                'data' => '',
            ];

            header("Content-Type: application/json; charset=utf-8");

        }

        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function getInvoiceTag($orderId, $protectCode)
    {
        $responseArray = [
            'code' => '0000',
            'msg' => '這是查詢發票開立標籤API',
            'data' => '',
        ];

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $orderId);
        $orderId = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId;

        $this->_loggerInterface->writeLog('getInvoiceTag enctyOrderId:' . print_r($enctyOrderId, true));
        $this->_loggerInterface->writeLog('getInvoiceTag orderId:' . print_r($orderId, true));

        // 取出訂單protect_code資訊
        $protectCodeFromOrder = $this->_orderService->getProtectCode($orderId);
        $this->_loggerInterface->writeLog('getInvoiceTag protectCodeFromOrder:' . print_r($protectCodeFromOrder, true));

        // 驗證訂單欄位protect_code 是否正確
        if ($protectCodeFromOrder != $protectCode) {
            $this->_loggerInterface->writeLog('getInvoiceTag protect_code驗證錯誤:' . print_r($protectCode, true));

            // 轉為JSON格式
            $responseArray = [
                'code' => '0001',
                'msg' => __('code_0001'),
                'data' => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            $this->response = json_encode($responseArray);
            print_r($this->response, false);
            die();
        }

        // 取出發票開立標籤
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        $this->_loggerInterface->writeLog('getInvoiceTag ecpayInvoiceTag:' . print_r($ecpayInvoiceTag, true));

        // 轉為JSON格式
        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => $ecpayInvoiceTag,
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }

    /**
     * {@inheritdoc}
     */
    public function getInvoiceMainConfig()
    {
        $responseArray = [
            'code' => '0000',
            'msg' => '這是查詢發票模組啟用API',
            'data' => '',
        ];

        // 判斷發票模組是否啟動
        $ecpayEnableInvoice = $this->_mainService->getMainConfig('ecpay_enabled_invoice');
        $this->_loggerInterface->writeLog('createInvoice ecpayEnableInvoice:' . print_r($ecpayEnableInvoice, true));

        // 轉為JSON格式
        $responseArray = [
            'code' => '0999',
            'msg' => __('code_0999'),
            'data' => $ecpayEnableInvoice,
        ];

        // 轉為JSON格式
        header("Content-Type: application/json; charset=utf-8");
        $this->response = json_encode($responseArray);
        print_r($this->response, false);
        die();
    }
}
