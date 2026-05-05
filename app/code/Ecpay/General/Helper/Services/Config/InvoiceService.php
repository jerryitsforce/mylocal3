<?php

namespace Ecpay\General\Helper\Services\Config;

use Branch8\HotaiCore\Helper\BaseEncodeDecode;
use Carbon\Carbon;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\HotaiOrderItemInvoiceLogs;
use Ecpay\Sdk\Exceptions\RtnException;
use Exception;
use JsonException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Ecpay\Invoice\Api\HotaiOrderInvoiceLogsRepositoryInterface;
use Ecpay\Invoice\Api\HotaiOrderItemInvoiceLogsRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Framework\UrlInterface;
use Ecpay\General\Model\EcpayInvoice;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\Sdk\Factories\Factory;
use Throwable;
use Zend_Log_Exception;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class InvoiceService extends AbstractHelper
{
    const ECPAY_INVOICE_BEFORE_ISSUE_FOR_NO_MONEY = "ecpay_invoice_before_issue_for_no_money";

    protected                                              $_urlInterface;
    protected                                              $_mainService;
    protected                                              $_orderService;
    protected                                              $_logisticService;
    protected                                              $itemFactory;
    protected                                              $_loggerInterface;
    protected HotaiOrderInvoiceLogsInterface               $hotaiOrderInvoiceLogsInterface;
    protected HotaiOrderInvoiceLogsRepositoryInterface     $hotaiOrderInvoiceLogsRepository;
    protected HotaiOrderItemInvoiceLogsInterface           $hotaiOrderItemInvoiceLogsInterface;
    protected HotaiOrderItemInvoiceLogsRepositoryInterface $hotaiOrderItemInvoiceLogsRepository;
    protected EventManagerInterface                        $eventManager;
    protected BaseEncodeDecode                             $baseEncodeDecode;
    protected ResourceConnection                           $resourceConnection;

    public function __construct(
        Context $context,
        UrlInterface $urlInterface,
        MainService $mainService,
        OrderService $orderService,
        LogisticService $logisticService,
        ItemFactory $itemFactory,
        GeneralHelper $loggerInterface,
        HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogsInterface,
        HotaiOrderInvoiceLogsRepositoryInterface $hotaiOrderInvoiceLogsRepository,
        HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogsInterface,
        HotaiOrderItemInvoiceLogsRepositoryInterface $hotaiOrderItemInvoiceLogsRepository,
        EventManagerInterface $eventManager,
        BaseEncodeDecode $baseEncodeDecode,
        ResourceConnection $resourceConnection,
    ) {
        $this->_urlInterface                       = $urlInterface;
        $this->_mainService                        = $mainService;
        $this->_orderService                       = $orderService;
        $this->_logisticService                    = $logisticService;
        $this->itemFactory                         = $itemFactory;
        $this->_loggerInterface                    = $loggerInterface;
        $this->hotaiOrderInvoiceLogsInterface      = $hotaiOrderInvoiceLogsInterface;
        $this->hotaiOrderInvoiceLogsRepository     = $hotaiOrderInvoiceLogsRepository;
        $this->hotaiOrderItemInvoiceLogsInterface  = $hotaiOrderItemInvoiceLogsInterface;
        $this->hotaiOrderItemInvoiceLogsRepository = $hotaiOrderItemInvoiceLogsRepository;
        $this->eventManager                        = $eventManager;
        $this->baseEncodeDecode                    = $baseEncodeDecode;
        $this->resourceConnection                  = $resourceConnection;

        parent::__construct($context);
    }

    /**
     * @return mixed
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function getInvoiceStage()
    {
        // 取出是否為測試模式
        $invoiceStage = $this->_mainService->getInvoiceConfig('enabled_invoice_stage');
        $this->writeLog('getInvoiceAccountInfo invoiceStage:' . print_r($invoiceStage, true));
        return $invoiceStage;
    }

    /**
     * 取出帳號KEY IV
     * @return array
     */
    public function getAccountInfo()
    {
        if ($this->getInvoiceStage() == 1) {
            // 取出 KEY IV MID (測試模式)
            $accountInfo = [
                'MerchantId' => '2000132',
                'HashKey'    => 'ejCk326UnaZWKisg',
                'HashIv'     => 'q9jcZX8Ib9LM8wYk',
            ];
            $this->writeLog('InvoiceService accountInfo:' . print_r($accountInfo, true));
        } else {
            // 取出 KEY IV MID (正式模式)
            $invoiceMerchantId = $this->_mainService->getInvoiceConfig('invoice_mid');
            $invoiceHashKey    = $this->_mainService->getInvoiceConfig('invoice_hashkey');
            $invoiceHashIv     = $this->_mainService->getInvoiceConfig('invoice_hashiv');

            $this->writeLog('InvoiceService invoiceMerchantId:' . print_r($invoiceMerchantId, true));
            $this->writeLog('InvoiceService invoiceHashKey:' . print_r($invoiceHashKey, true));
            $this->writeLog('InvoiceService invoiceHashIv:' . print_r($invoiceHashIv, true));

            $accountInfo = [
                'MerchantId' => $invoiceMerchantId,
                'HashKey'    => $invoiceHashKey,
                'HashIv'     => $invoiceHashIv,
            ];
        }

        return $accountInfo;
    }

    /**
     * 取出API介接網址
     * @param string $action
     * @return string  $url
     */
    public function getApiUrl($action = 'issue')
    {
        if ($this->getInvoiceStage() == 1) {
            $url = match ($action) {
                'check_love_code' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode',
                'check_barcode' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode',
                'get_issue' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/GetIssue',
                'issue' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue',
                'delay_issue' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue',
                'invalid' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid',
                'cancel_delay_issue' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue',
                'get_company_name_by_tax_id' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/GetCompanyNameByTaxID',
                'invoice_print' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/invoicePrint',
                'allowance' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Allowance',
                'allowance_by_collegiate' => 'https://einvoice.ecpay.com.tw/B2CInvoice/AllowanceByCollegiate',
                default => '',
            };
        } else {
            $url = match ($action) {
                'check_love_code' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode',
                'check_barcode' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode',
                'get_issue' => 'https://einvoice.ecpay.com.tw/B2CInvoice/GetIssue',
                'issue' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue',
                'delay_issue' => 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue',
                'invalid' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid',
                'cancel_delay_issue' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue',
                'get_company_name_by_tax_id' => 'https://einvoice.ecpay.com.tw/B2CInvoice/GetCompanyNameByTaxID',
                'invoice_print' => 'https://einvoice.ecpay.com.tw/B2CInvoice/invoicePrint',
                'allowance' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Allowance',
                'allowance_by_collegiate' => 'https://einvoice.ecpay.com.tw/B2CInvoice/AllowanceByCollegiate',
                default => '',
            };
        }

        return $url;
    }

    /**
     * 取得發票開立類別名稱對應表
     * @return array
     */
    public function getInvoiceTypeTable(): array
    {
        return [
            EcpayInvoice::ECPAY_INVOICE_TYPE_P => '個人',
            EcpayInvoice::ECPAY_INVOICE_TYPE_C => '公司',
            EcpayInvoice::ECPAY_INVOICE_TYPE_D => '捐贈',
        ];
    }

    /**
     * 開立發票
     * @param $orderId
     * @return array
     * @throws FileSystemException
     * @throws JsonException
     * @throws Zend_Log_Exception
     * @throws LocalizedException
     */
    public function invoiceIssue($orderId)
    {
        // 判斷發票模組是否啟動
        $ecpayEnableInvoice = $this->_mainService->isInvoiceModuleEnable();
        if ($ecpayEnableInvoice == 0) {
            return [
                'code' => '1003',
                'msg'  => __('code_1003'),
                'data' => '',
            ];
        }

        // 判斷是否已經開立過發票
        $ecpayInvoiceTag = $this->_orderService->getEcpayInvoiceTag($orderId);
        if ($ecpayInvoiceTag != 0) {
            return [
                'code' => '1002',
                'msg'  => __('code_1002'),
                'data' => '',
            ];
        }

        /** 如果grand total = 0, return and set message */
        $saleAmount = $this->_orderService->getGrandTotal($orderId);
        $saleAmount = $this->transferToTwTax($saleAmount);
        $invoiceItems = $this->getInvoiceItems($orderId);

        // 取得訂單使用點數
        $pointUsedTotal = $invoiceItems['item_total_point_use'];
        $this->_orderService->setOrderCommentForBack($orderId, '開立發票中...' . $this->_orderService->getOrderState($orderId) . ' ' . $this->_orderService->getOrderStatus($orderId));
        if ($saleAmount == 0) {
            $this->eventManager->dispatch(self::ECPAY_INVOICE_BEFORE_ISSUE_FOR_NO_MONEY, [
                "orderId" => $orderId,
            ]);

            $status = $this->freeOrderInvoice($orderId, $saleAmount, $invoiceItems, $pointUsedTotal);

            if ($status) {
                return [
                    'code' => '1005',
                    'msg'  => __('code_1005'),
                    'data' => '',
                ];
            }

            return [
                'code' => '1014',
                'msg'  => __('code_1014'),
                'data' => '',
            ];
        }

        $merchantTradeNo = $this->_orderService->getIncrementId($orderId);

        // 發票欄位資訊
        $ecpayInvoiceCarruerType        = $this->_orderService->getEcpayInvoiceCarruerType($orderId);
        $ecpayInvoiceType               = $this->_orderService->getecpayInvoiceType($orderId);
        $ecpayInvoiceType               = (empty($ecpayInvoiceType)) ? EcpayInvoice::ECPAY_INVOICE_TYPE_P : $ecpayInvoiceType;
        $ecpayInvoiceCarruerNum         = $this->_orderService->getEcpayInvoiceCarruerNum($orderId);
        $ecpayInvoiceLoveCode           = $this->_orderService->getEcpayInvoiceLoveCode($orderId);
        $ecpayInvoiceCustomerIdentifier = $this->_orderService->getEcpayInvoiceCustomerIdentifier($orderId);
        $ecpayInvoiceCustomerCompany    = $this->_orderService->getEcpayInvoiceCustomerCompany($orderId);

        // 取得帳單收件人資訊
        $billingName      = $this->_orderService->getBillingName($orderId);
        $billingTelephone = $this->_orderService->getBillingTelephone($orderId);
        $billingEmail     = $this->_orderService->getBillingEmail($orderId);

        $data = [
            'RelateNumber'  => $merchantTradeNo,
            'CustomerID'    => '',
            'CustomerName'  => $billingName,
            'CustomerAddr'  => $this->getBillingAddress($orderId),
            'CustomerPhone' => $billingTelephone,
            'CustomerEmail' => $billingEmail,
            'Print'         => '0',
            'Donation'      => '0',
            'LoveCode'      => '',
            'CarrierType'   => '',
            'CarrierNum'    => '',
            'TaxType'       => $invoiceItems['tax_type'],
            'SalesAmount'   => $saleAmount,
            'Items'         => $invoiceItems['items'],
            'InvType'       => '07'
        ];

        switch ($ecpayInvoiceType) {
            case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                $this->writeLog('InvoiceService ecpayInvoiceType:個人');

                switch ($ecpayInvoiceCarruerType) {
                    case '1':
                        $data['CarrierType'] = '1';
                        break;

                    case '2':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    case '3':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    default:
                        $data['CarrierType'] = '1';
                        $data['Print'] = '0';
                        break;
                }

                break;

            case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                $this->writeLog('InvoiceService ecpayInvoiceType:公司');

                switch ($ecpayInvoiceCarruerType) {
                    case '1':
                        $data['CarrierType'] = '1';
                        break;

                    case '2':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    case '3':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    default:
                        $data['Print'] = '1';
                }

                $data['CustomerIdentifier'] = $ecpayInvoiceCustomerIdentifier;
                $data['CustomerName'] = $ecpayInvoiceCustomerCompany;

                break;

            case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                $this->writeLog('InvoiceService ecpayInvoiceType:捐贈');
                $data['Donation'] = '1';
                $data['LoveCode'] = $ecpayInvoiceLoveCode;

                break;
        }

        // 開始開立發票
        try {
            $response = $this->postInvoiceIssue('issue', $data);
            if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
                $this->saveInvoiceData(
                    $orderId,
                    $response['Data']['InvoiceNo'],
                    $response['Data']['InvoiceDate'],
                    $response['Data']['RandomNumber'],
                    $merchantTradeNo,
                    $data['Print']
                );

                // 回傳資料寫入備註
                $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo'] . '，隨機碼：' . $response['Data']['RandomNumber'] . '交易單號：' . $merchantTradeNo;
                $this->_orderService->setOrderCommentForBack($orderId, $comment);

                if (isset($response['Data']['InvoiceNo'])) {
                    try {
                        $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                            $orderId,
                            $response['Data']['InvoiceNo'],
                            $response['Data']['InvoiceNo'],
                            HotaiOrderInvoiceLogs::CREATED,
                            $pointUsedTotal,
                            $saleAmount
                        );

                        if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog)) {
                            $this->saveOrderItemInvoiceLogs(
                                $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                                , $invoiceItems['item_logs']
                            );
                        }
                        $this->_orderService->setOrderData($orderId, 'hotai_order_invoice_log_id', $orderInvoiceLog->getHotaiOrderInvoiceLogsId());
                    } catch (Throwable $t) {
                        $this->writeLog('saveOrderInvoiceLogs error:' . $t->getMessage());
                    }
                }

                $this->_orderService->setOrderCommentForBack($orderId, '開立發票結束...' . $this->_orderService->getOrderState($orderId) . ' ' . $this->_orderService->getOrderStatus($orderId));

                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => json_encode([], JSON_THROW_ON_ERROR)
                ];
            }

            // 如果自訂編號重複，代表已經開立過
            if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 5070357) {
                $response = $this->postInvoiceIssue('get_issue', [
                    'RelateNumber' => $merchantTradeNo,
                ]);

                $this->saveInvoiceData(
                    $orderId,
                    $response['Data']['IIS_Number'],
                    $response['Data']['IIS_Create_Date'],
                    $response['Data']['IIS_Random_Number'],
                    $merchantTradeNo,
                    $data['Print']
                );

                $ecpayInvoiceNumber = $this->_orderService->getEcpayInvoiceNumber($orderId);

                try {
                    $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                        $orderId,
                        $ecpayInvoiceNumber,
                        $ecpayInvoiceNumber,
                        HotaiOrderInvoiceLogs::CREATED,
                        $pointUsedTotal,
                        $saleAmount
                    );

                    if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog)) {
                        $this->saveOrderItemInvoiceLogs(
                            $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                            , $invoiceItems['item_logs']
                        );
                    }
                    $this->_orderService->setOrderData($orderId, 'hotai_order_invoice_log_id', $orderInvoiceLog->getHotaiOrderInvoiceLogsId());
                } catch (Throwable $t) {
                    $this->writeLog('saveOrderInvoiceLogs error:' . $t->getMessage());
                }

                $comment = '開立成功(5070357)' . json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $this->_orderService->setOrderCommentForBack($orderId, $comment);

                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => $comment
                ];
            }

            $comment = '開立失敗' . json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $this->writeLog('一般開立發票失敗:' . $comment);
            $this->_orderService->setOrderCommentForBack($orderId, $comment);

            return [
                'code' => '1004',
                'msg'  => __('code_1004'),
                'data' => $comment
            ];
        } catch (Exception $e) {
            // 回傳資料寫入備註
            $comment = '開立發票失敗，' . $e->getMessage();
            $this->writeLog('開立發票失敗:' . $comment);

            $this->_orderService->setOrderCommentForBack($orderId, $comment);

            return [
                'code' => '1004',
                'msg'  => __('code_1004'),
                'data' => json_encode($comment, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    /**
     * @param $orderId
     * @param $createData
     * @param $shippingIncTax
     * @param $baseAdjustment
     * @param $discountAmount
     * @param $invoiceCount
     * @param $orderItemIds
     * @return array
     * @throws FileSystemException
     * @throws JsonException
     * @throws Zend_Log_Exception
     * @throws LocalizedException
     */
    public function reInvoiceIssue($orderId, $createData, $shippingIncTax, $baseAdjustment, $discountAmount, $invoiceCount, $orderItemIds)
    {
        // 判斷發票模組是否啟動
        $ecpayEnableInvoice = $this->_mainService->isInvoiceModuleEnable();
        if ($ecpayEnableInvoice == 0) {
            return [
                'code' => '1003',
                'msg'  => __('code_1003'),
                'data' => '',
            ];
        }

        /** 如果grand total = 0, return and set message */
        $originalSaleAmount = $this->_orderService->getGrandTotal($orderId);

        $saleAmount = $originalSaleAmount - $createData['credit_grand_total'];

        $saleAmount = $this->transferToTwTax($saleAmount);
        $invoiceItems = $this->getInvoiceItems($orderId, $shippingIncTax, $baseAdjustment, $discountAmount, $orderItemIds);

        if(count($invoiceItems['item_logs']) === 0) {
            // 回傳資料寫入備註
            $comment = '重開發票：無品項需重開，完成';
            $this->_orderService->setOrderCommentForBack($orderId, $comment);

            // 回傳成功狀態到前端
            // 轉為JSON格式
            return [
                'code' => '0999',
                'msg'  => __('code_0999'),
                'data' => '',
            ];
        }

        // 取得訂單使用點數
        $pointUsedTotal = $invoiceItems['item_total_point_use'];

        if ($saleAmount == 0) {
            $status = $this->freeOrderInvoice($orderId, $saleAmount, $invoiceItems, $pointUsedTotal, $invoiceCount);

            if ($status) {
                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => '',
                ];
            }

            return [
                'code' => '1014',
                'msg'  => __('code_1014'),
                'data' => '',
            ];
        }

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getIncrementId($orderId);
        $merchantTradeNo .= 'N' . sprintf('%02d', $invoiceCount);

        // 發票欄位資訊
        $ecpayInvoiceCarruerType        = $this->_orderService->getEcpayInvoiceCarruerType($orderId);
        $ecpayInvoiceType               = $this->_orderService->getecpayInvoiceType($orderId);
        $ecpayInvoiceType               = (empty($ecpayInvoiceType)) ? EcpayInvoice::ECPAY_INVOICE_TYPE_P : $ecpayInvoiceType;
        $ecpayInvoiceCarruerNum         = $this->_orderService->getEcpayInvoiceCarruerNum($orderId);
        $ecpayInvoiceLoveCode           = $this->_orderService->getEcpayInvoiceLoveCode($orderId);
        $ecpayInvoiceCustomerIdentifier = $this->_orderService->getEcpayInvoiceCustomerIdentifier($orderId);
        $ecpayInvoiceCustomerCompany    = $this->_orderService->getEcpayInvoiceCustomerCompany($orderId);

        // 取得帳單收件人資訊
        $billingName      = $this->_orderService->getBillingName($orderId);
        $billingTelephone = $this->_orderService->getBillingTelephone($orderId);
        $billingEmail     = $this->_orderService->getBillingEmail($orderId);

        // 判斷是否為自動開立模式，寫入備註
        $commentMessage = 'RMA 逆流程';

        $data = [
            'RelateNumber'  => $merchantTradeNo,
            'CustomerID'    => '',
            'CustomerName'  => $billingName,
            'CustomerAddr'  => $this->getBillingAddress($orderId),
            'CustomerPhone' => $billingTelephone,
            'CustomerEmail' => $billingEmail,
            'Print'         => '0',
            'Donation'      => '0',
            'LoveCode'      => '',
            'CarrierType'   => '',
            'CarrierNum'    => '',
            'TaxType'       => $invoiceItems['tax_type'],
            'SalesAmount'   => $saleAmount,
            'Items'         => $invoiceItems['items'],
            'InvType'       => '07'
        ];

        switch ($ecpayInvoiceType) {
            case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                $this->writeLog('InvoiceService ecpayInvoiceType:個人');

                switch ($ecpayInvoiceCarruerType) {
                    case '1':
                        $data['CarrierType'] = '1';
                        break;

                    case '2':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    case '3':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    default:
                        $data['CarrierType'] = '1';
                        $data['Print'] = '0';
                        break;
                }

                break;

            case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                $this->writeLog('InvoiceService ecpayInvoiceType:公司');

                switch ($ecpayInvoiceCarruerType) {
                    case '1':
                        $data['CarrierType'] = '1';
                        break;

                    case '2':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    case '3':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $ecpayInvoiceCarruerNum;
                        break;

                    default:
                        $data['Print'] = '1';
                }

                $data['CustomerIdentifier'] = $ecpayInvoiceCustomerIdentifier;
                $data['CustomerName'] = $ecpayInvoiceCustomerCompany;

                break;

            case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                $this->writeLog('InvoiceService ecpayInvoiceType:捐贈');
                $data['Donation'] = '1';
                $data['LoveCode'] = $ecpayInvoiceLoveCode;

                break;
        }

        // 開始開立發票
        try {
            $response = $this->postInvoiceIssue('issue', $data);
            if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
                $this->saveInvoiceData(
                    $orderId, $response['Data']['InvoiceNo'],
                    $response['Data']['InvoiceDate'],
                    $response['Data']['RandomNumber'],
                    $merchantTradeNo,
                    $data['Print']
                );

                // 回傳資料寫入備註
                $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo'] . '，隨機碼：' . $response['Data']['RandomNumber'] . '交易單號：' . $merchantTradeNo . $commentMessage;
                $this->_orderService->setOrderCommentForBack($orderId, $comment);

                if (isset($response['Data']['InvoiceNo'])) {
                    try {
                        $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                            $orderId,
                            $response['Data']['InvoiceNo'],
                            $response['Data']['InvoiceNo'],
                            HotaiOrderInvoiceLogs::CREATED,
                            $pointUsedTotal,
                            $saleAmount,
                            0,
                            $invoiceCount
                        );

                        if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog)) {
                            $this->saveOrderItemInvoiceLogs(
                                $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                                , $invoiceItems['item_logs']
                            );
                        }
                        $this->_orderService->setOrderData($orderId, 'hotai_order_invoice_log_id', $orderInvoiceLog->getHotaiOrderInvoiceLogsId());
                    } catch (Throwable $t) {
                        $this->writeLog('saveOrderInvoiceLogs error:' . $t->getMessage());
                    }
                }

                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => json_encode([], JSON_THROW_ON_ERROR)
                ];
            }

            // 如果自訂編號重複，代表已經開立過
            if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 5070357) {
                $response = $this->postInvoiceIssue('get_issue', [
                    'RelateNumber' => $merchantTradeNo,
                ]);

                $this->saveInvoiceData(
                    $orderId,
                    $response['Data']['IIS_Number'],
                    $response['Data']['IIS_Create_Date'],
                    $response['Data']['IIS_Random_Number'],
                    $merchantTradeNo,
                    $data['Print']
                );

                $ecpayInvoiceNumber    = $this->_orderService->getEcpayInvoiceNumber($orderId);

                try {
                    $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                        $orderId,
                        $ecpayInvoiceNumber,
                        $ecpayInvoiceNumber,
                        HotaiOrderInvoiceLogs::CREATED,
                        $pointUsedTotal,
                        $saleAmount
                    );

                    if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog)) {
                        $this->saveOrderItemInvoiceLogs(
                            $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                            , $invoiceItems['item_logs']
                        );
                    }
                    $this->_orderService->setOrderData($orderId, 'hotai_order_invoice_log_id', $orderInvoiceLog->getHotaiOrderInvoiceLogsId());
                } catch (Throwable $t) {
                    $this->writeLog('saveOrderInvoiceLogs error:' . $t->getMessage());
                }

                $comment = '開立成功(5070357)' . json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . $commentMessage;
                $this->_orderService->setOrderCommentForBack($orderId, $comment);

                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => $comment
                ];
            }

            $comment = '開立失敗' . json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . $commentMessage;
            $this->writeLog('一般開立發票失敗:' . $comment);
            $this->_orderService->setOrderCommentForBack($orderId, $comment);

            return [
                'code' => '1004',
                'msg'  => __('code_1004'),
                'data' => $comment
            ];
        } catch (Exception $e) {
            // 回傳資料寫入備註
            $comment = '開立發票失敗，' . $e->getMessage();
            $this->writeLog('開立發票失敗:' . $comment);

            $this->_orderService->setOrderCommentForBack($orderId, $comment);

            return [
                'code' => '1004',
                'msg'  => __('code_1004'),
                'data' => json_encode($comment, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    /**
     * @throws FileSystemException
     * @throws RtnException
     * @throws Zend_Log_Exception
     */
    public function invalidInvoice($orderId)
    {
        $orderId = (int) $orderId;
        $this->writeLog('invalidInvoice orderId:' . print_r($orderId, true));

        // 發票欄位資訊
        $ecpayInvoiceNumber    = $this->_orderService->getEcpayInvoiceNumber($orderId);
        $ecpayInvoiceDate      = $this->_orderService->getEcpayInvoiceDate($orderId);
        $ecpayInvoiceIssueType = $this->_orderService->getEcpayInvoiceIssueType($orderId);  // 取出開立方式 1.一般開立 2.延遲開立

        $this->writeLog('invalidInvoice ecpayInvoiceNumber:' . print_r($ecpayInvoiceNumber, true));
        $this->writeLog('invalidInvoice ecpayInvoiceDate:' . print_r($ecpayInvoiceDate, true));
        $this->writeLog('invalidInvoice ecpayInvoiceIssueType:' . print_r($ecpayInvoiceIssueType, true));

        $saleAmount      = $this->_orderService->getGrandTotal($orderId);
        $invoiceItems    = $this->getInvoiceItems($orderId);

        if (empty($ecpayInvoiceNumber)) {
            if ($saleAmount == 0)
            {
                // 回傳資料寫入備註
                $comment = '發票號碼：無，0元發票作廢';

                $this->_orderService->setOrderCommentForBack($orderId, $comment);
                $this->setInvalidOrder($orderId, $saleAmount, '', 'invalid', $invoiceItems);

                // 回傳成功狀態到前端
                // 轉為JSON格式
                $responseArray = [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => '',
                ];

                header("Content-Type: application/json; charset=utf-8");
                return $responseArray;
            }
            // 轉為JSON格式
            $responseArray = [
                'code' => '1005',
                'msg'  => __('code_1005'),
                'data' => '',
            ];
            header("Content-Type: application/json; charset=utf-8");
            return $responseArray;
        }

        // 存在發票號碼
        // 組合送綠界格式
        $data = [
            'InvoiceNo'   => $ecpayInvoiceNumber,
            'InvoiceDate' => $ecpayInvoiceDate,
            'Reason'      => '作廢發票',
        ];

        $response = $this->postInvoiceIssue('invalid', $data);

        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
            // 回傳資料寫入備註
            $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo'];

            $this->_orderService->setOrderCommentForBack($orderId, $comment);
            $this->setInvalidOrder($orderId, $saleAmount, $ecpayInvoiceNumber, 'invalid', $invoiceItems);

            // 回傳成功狀態到前端
            // 轉為JSON格式
            $responseArray = [
                'code' => '0999',
                'msg'  => __('code_0999'),
                'data' => '',
            ];

            header("Content-Type: application/json; charset=utf-8");
            return $responseArray;
        }

        // 回傳資料寫入備註
        $comment = '(' . $response['Data']['RtnCode'] . ')' . $response['Data']['RtnMsg'];

        $this->_orderService->setOrderCommentForBack($orderId, $comment);

        // 轉為JSON格式
        $responseArray = [
            'code' => '1006',
            'msg'  => __('code_1006'),
            'data' => '',
        ];

        header("Content-Type: application/json; charset=utf-8");
        return $responseArray;
    }

    /**
     * @throws FileSystemException
     * @throws RtnException
     * @throws Zend_Log_Exception
     */
    public function invalidInvoiceForRma($orderId, $orderItemIds, $invoiceCount, $type, $grandTotal): array
    {
        if ($type === 'allowance') {
            $invoiceStatus = HotaiOrderInvoiceLogs::DISCOUNT;
        } else {
            $invoiceStatus = HotaiOrderInvoiceLogs::INVALID;
        }

        $orderId = (int) $orderId;

        $this->writeLog('invalidInvoice orderId:' . print_r($orderId, true));

        // 發票欄位資訊
        $ecpayInvoiceNumber    = $this->_orderService->getEcpayInvoiceNumber($orderId);
        $ecpayInvoiceDate      = $this->_orderService->getEcpayInvoiceDate($orderId);
        // 取出開立方式 1.一般開立 2.延遲開立
        $ecpayInvoiceIssueType = $this->_orderService->getEcpayInvoiceIssueType($orderId);

        $this->writeLog('invalidInvoice ecpayInvoiceNumber:' . print_r($ecpayInvoiceNumber, true));
        $this->writeLog('invalidInvoice ecpayInvoiceDate:' . print_r($ecpayInvoiceDate, true));
        $this->writeLog('invalidInvoice ecpayInvoiceIssueType:' . print_r($ecpayInvoiceIssueType, true));

        $saleAmount      = $this->_orderService->getGrandTotal($orderId);
        if ($invoiceCount != 1) {
            $saleAmount -= $grandTotal;
        }

        $invoiceItems    = $this->getInvoiceItems($orderId, orderItemIds: $orderItemIds, filter: false);

        if (empty($ecpayInvoiceNumber)) {
            if ($saleAmount == 0) {
                $comment = '發票號碼：無，0元發票作廢';

                $this->_orderService->setOrderCommentForBack($orderId, $comment);
                $this->setInvalidOrder($orderId, $saleAmount, '', $invoiceItems, $invoiceStatus, $invoiceCount);

                // 回傳成功狀態到前端
                // 轉為JSON格式
                return [
                    'code' => '0999',
                    'msg'  => __('code_0999'),
                    'data' => '',
                ];
            }

            return [
                'code' => '1005',
                'msg'  => __('code_1005'),
                'data' => '',
            ];
        }

        if ($type === 'allowance') {
            $ecpayInvoiceType = $this->_orderService->getecpayInvoiceType($orderId);
            $ecpayInvoiceType = (empty($ecpayInvoiceType)) ? EcpayInvoice::ECPAY_INVOICE_TYPE_P : $ecpayInvoiceType;

            $customerName = $this->_orderService->getBillingName($orderId);

            if ($ecpayInvoiceType === EcpayInvoice::ECPAY_INVOICE_TYPE_C) {
                $customerName = $this->_orderService->getEcpayInvoiceCustomerCompany($orderId);
            }

            $response = $this->postInvoiceIssue('allowance', [
                'InvoiceNo'        => $ecpayInvoiceNumber,
                'InvoiceDate'      => $ecpayInvoiceDate,
                // S：簡訊  E：電子郵件  A：皆通知時  N：皆不通知
                'AllowanceNotify'  => 'N',
                'CustomerName'     => $customerName,
                'AllowanceAmount'  => (int)$saleAmount,
                'Items'            => $invoiceItems['items'],
            ]);
        } else {
            $response = $this->postInvoiceIssue('invalid', [
                'InvoiceNo'   => $ecpayInvoiceNumber,
                'InvoiceDate' => $ecpayInvoiceDate,
                'Reason'      => '作廢發票',
            ]);
        }

        if (isset($response['Data']['RtnCode']) && $response['Data']['RtnCode'] == 1) {
            if ($type === 'allowance') {
                $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['IA_Invoice_No'];
            } else {
                // 回傳資料寫入備註
                $comment = $response['Data']['RtnMsg'] . '，發票號碼：' . $response['Data']['InvoiceNo'];
            }

            $this->_orderService->setOrderCommentForBack($orderId, $comment);
            $this->setInvalidOrder($orderId, $saleAmount, $ecpayInvoiceNumber, $invoiceItems, $invoiceStatus, $invoiceCount);

            // 轉為JSON格式
            return [
                'code' => '0999',
                'msg'  => __('code_0999'),
                'data' => '',
            ];
        }

        // 回傳資料寫入備註
        $comment = '(' . $response['Data']['RtnCode'] . ')' . $response['Data']['RtnMsg'];

        $this->_orderService->setOrderCommentForBack($orderId, $comment);

        //B2C作廢發票 該發票已被作廢過
        if ($response['Data']['RtnCode'] === 5070453) {
            return [
                'code' => '0999',
                'msg'  => __('code_0999'),
                'data' => '',
            ];
        }

        // 轉為JSON格式
        return [
            'code' => '1006',
            'msg'  => __('code_1006'),
            'data' => '',
        ];
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $this->_loggerInterface->writeLog($message, 'invoice_service');
    }

    /**
     * @param $type
     * @param $data
     * @return array
     * @throws FileSystemException
     * @throws RtnException
     * @throws Zend_Log_Exception
     */
    private function postInvoiceIssue($type, $data)
    {
        $accountInfo = $this->getAccountInfo();
        $factory     = new Factory([
            'hashKey' => $accountInfo['HashKey'],
            'hashIv'  => $accountInfo['HashIv'],
        ]);

        $postService = $factory->create('PostWithAesJsonResponseService');

        // 取出 URL
        $apiUrl = $this->getApiUrl($type);
        $this->writeLog('InvoiceService apiUrl:' . print_r($apiUrl, true));

        $data['MerchantID'] = $accountInfo['MerchantId'];

        $input = [
            'MerchantID' => $accountInfo['MerchantId'],
            'RqHeader'   => [
                'Timestamp' => time(),
                'Revision'  => '3.0.0',
            ],
            'Data'       => $data,
        ];

        $this->writeLog('InvoiceService '.$type.' input:' . print_r($input, true));
        $response = $postService->post($input, $apiUrl);
        $this->writeLog('InvoiceService '.$type.' response:' . print_r($response, true));

        return $response;
    }

    /**
     * @param $orderId
     * @param int $shippingIncTax
     * @param int $baseAdjustment
     * @param int $discountAmount
     * @param array $orderItemIds
     * @param bool $filter
     * @return array
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function getInvoiceItems($orderId, int $shippingIncTax = 0, int $baseAdjustment = 0, int $discountAmount = 0, array $orderItemIds = [], bool $filter = true): array
    {
        /** 拿訂單商品 */
        $item           = $this->itemFactory->create()
            ->getCollection()->addFieldToFilter('order_id', ['in' => $orderId]);
        $mixTax         = [];
        $totalUsePoint  = $totalDiscountAmount = $fee = 0;
        $baseAdjustment = ($baseAdjustment < 0) ? $baseAdjustment * -1 : $baseAdjustment;
        $discountAmount = ($discountAmount < 0) ? $discountAmount * -1 : $discountAmount;

        foreach ($item as $_item) {
            // 品項點數(含稅) 先拿跟計算
            $itemPointUse       = (int) $_item->getData("row_total_point_discount");
            $itemDiscountAmount = (int) $_item->getData("base_discount_amount");
            if ($itemPointUse > 0) {
                $totalUsePoint += $itemPointUse;
            }

            if ($itemDiscountAmount > 0) {
                $totalDiscountAmount += $itemDiscountAmount;
            }

            $itemQtyOrdered  = (int) $_item->getData("qty_ordered");
//            $itemQtyRefunded = $orderItemIds[$_item->getId()] ?? (int)$_item->getData("qty_refunded");
            // 先註解上面的資訊 部分退貨會有問題 先採取抓不到就給0
            $itemQtyRefunded = $orderItemIds[$_item->getId()] ?? 0;

            /** 已退款 */
            if ($itemQtyOrdered - $itemQtyRefunded <= 0) {
                /** 如果有 $baseAdjustment 那就扣掉品項點數*/
                if ($itemPointUse > 0 && $baseAdjustment !== 0) {
                    $baseAdjustment -= $itemPointUse;
                    $totalUsePoint -= $itemPointUse;
                } elseif ($itemPointUse > 0){
                    /** 只有點數 */
                    $totalUsePoint -= $itemPointUse;
                }

                if ($itemDiscountAmount > 0 && $discountAmount !== 0) {
                    $discountAmount -= $itemDiscountAmount;
                    $totalDiscountAmount -= $itemDiscountAmount;
                }

                if ($filter) {
                    continue;
                }
            }

            if (!$filter && !empty($orderItemIds) && isset($orderItemIds[$_item->getId()])) {
                continue;
            }

            // 訂單資訊組合
            // ItemTaxType 1:應稅 2:零稅率 3:免稅
            // 當課稅類別[TaxType] = 9時，商品課稅類別只能
            // 1.應稅+免稅
            // 2.應稅+零稅率，
            // 免稅和零稅率發票不能同時開立。

            $qtyOrdered   = (int)$_item->getQtyOrdered();
            $formattedQty = (int) floor($qtyOrdered);

            $itemIncludeTax = (int) $_item->getPriceInclTax();
            $itemDiscountUse = (int) $_item->getData("base_discount_amount");

            if ($itemDiscountUse > 0) {
                /** 先乘拿總數 */
                $itemIncludeTax *= $qtyOrdered;
                /** 扣除discount */
                $itemIncludeTax -= $itemDiscountUse;
                /** 再除回來 */
                $itemIncludeTax /= $qtyOrdered;
            }

            /** base_price_incl_tax or price_incl_tax */
            $includeTax = $itemIncludeTax;
            $excludeTax = $itemIncludeTax / 1.05;
            $tax        = $includeTax - $excludeTax;
            $itemAmount = round($includeTax * $formattedQty);

            /** ECPAY 發票品項 */
            $ecPayItems = [
                'ItemName'    => $_item->getName(),
                'ItemCount'   => $formattedQty,
                'ItemWord'    => '項',
                'ItemPrice'   => number_format($includeTax, 7),
                'ItemTaxType' => '1',
                'ItemAmount'  => $itemAmount
            ];

            if ($_item->getTaxAmount() === 0.000) {
                $mixTax['free_tax']        = 1;
                $ecPayItems['ItemTaxType'] = '3';
                $taxType                   = 3;
            } else {
                $mixTax['need_tax'] = 1;
                $taxType            = 1;
            }

            $items[] = $ecPayItems;

            $itemLogArray = [
                'order_item_id'   => $_item->getId(),
                'order_item_name' => $_item->getName(),
                'qty'             => $formattedQty,
                'include_tax'     => number_format($includeTax, 7),
                'exclude_tax'     => number_format($excludeTax, 7),
                'tax'             => number_format($tax, 7),
                'type'            => HotaiOrderItemInvoiceLogs::TYPE_ITEM,
            ];

            /** 如果大於活動扣抵大於0 寫入after_discount欄位 */
            if ($itemDiscountUse > 0) {
                $discountIncludeTax = $this->transferToTwTax($itemDiscountUse);
                $discountExcludeTax = $this->transferToTwTax($discountIncludeTax, true);
                $afterDiscountTax   = $discountIncludeTax - $discountExcludeTax;
                $itemLogArray['discount_amount_include_tax'] = $discountIncludeTax;
                $itemLogArray['discount_amount_exclude_tax'] = $discountExcludeTax;
                $itemLogArray['discount_amount_tax'] = $afterDiscountTax;
            }

            $logs[] = $itemLogArray;

            if ($itemPointUse > 0) {
                // 品項點數(未稅) /1.05
                $itemPointExcludeTax = $this->transferToTwTax($itemPointUse, true);

                $items[] = [
                    'ItemName'    => '點數扣抵',
                    'ItemCount'   => 1,
                    'ItemWord'    => '項',
                    'ItemPrice'   => -$itemPointUse,
                    'ItemTaxType' => '3',
                    'ItemAmount'  => -$itemPointUse,
                ];

                $logs[] = [
                    'order_item_id'   => $_item->getId(),
                    'order_item_name' => '點數扣抵',
                    'qty'             => 1,
                    'include_tax'     => -$itemPointUse,
                    'exclude_tax'     => -$itemPointExcludeTax,
                    'tax'             => -($itemPointUse - $itemPointExcludeTax),
                    'type'            => HotaiOrderItemInvoiceLogs::TYPE_POINT,
                    'export_report'   => 0,
                ];
            }
        }

        $baseShippingAmount = (int) $this->_orderService->getBaseShippingInclTax($orderId);
        $this->writeLog('InvoiceService baseShippingAmount:' . print_r($baseShippingAmount, true));

        if ($baseShippingAmount > 0) {

            if ($shippingIncTax > 0) {
                $baseShippingAmount = $shippingIncTax;
            }

            $baseShippingAmount       = $this->transferToTwTax($baseShippingAmount);
            $shippingAmountExcludeTax = $this->transferToTwTax($baseShippingAmount, true);

            $items[] = [
                'ItemName'    => '運費',
                'ItemCount'   => 1,
                'ItemWord'    => '項',
                'ItemPrice'   => $baseShippingAmount,
                'ItemTaxType' => '3',
                'ItemAmount'  => $baseShippingAmount,
            ];
            $logs[]  = [
                'order_item_id'   => '',
                'order_item_name' => '運費',
                'qty'             => 1,
                'include_tax'     => $baseShippingAmount,
                'exclude_tax'     => $shippingAmountExcludeTax,
                'tax'             => -($baseShippingAmount - $shippingAmountExcludeTax),
                'type'            => HotaiOrderItemInvoiceLogs::TYPE_SHIPPING,
            ];
        }

        if ($baseAdjustment !== 0 || $discountAmount !== 0) {
            if ($baseAdjustment !== 0) {
                $baseAdjustment -= $totalUsePoint;
                $fee += $baseAdjustment;

            }

            if ($discountAmount !== 0) {
                $discountAmount -= $totalDiscountAmount;
                $fee += $discountAmount;
            }

            $fee           = $this->transferToTwTax($fee);
            $feeExcludeTax = $this->transferToTwTax($fee, true);

            $items[] = [
                'ItemName'    => '訂單處理費',
                'ItemCount'   => 1,
                'ItemWord'    => '項',
                'ItemPrice'   => -$fee,
                'ItemTaxType' => '3',
                'ItemAmount'  => -$fee,
            ];
            $logs[]  = [
                'order_item_id'   => '',
                'order_item_name' => '訂單處理費',
                'qty'             => 1,
                'include_tax'     => $fee,
                'exclude_tax'     => $feeExcludeTax,
                'tax'             => -($fee - $feeExcludeTax),
                'type'            => HotaiOrderItemInvoiceLogs::TYPE_FEE,
            ];
        }

        if (isset($mixTax['free_tax'], $mixTax['need_tax'])) {
            $taxType = 9;
        }

        return [
            'tax_type'  => $taxType ?? 1,
            'items'     => $items ?? [],
            'item_logs' => $logs ?? [],
            'item_total_point_use' => $totalUsePoint ?? 0,
        ];
    }

    /**
     * @param $price
     * @param bool $calculate
     * @return int
     */
    private function transferToTwTax($price, bool $calculate = false): int
    {
        $price = $price ?? 0;
        return ($calculate) ? (int) round($price / 1.05) : (int) round($price);
    }

    /**
     * @param int $orderId
     * @param string $invoiceNumber
     * @param string $hotaiCheckoutNumber
     * @param int $status
     * @param int $pointUsed
     * @param int $includeTax
     * @param int $isReverse
     * @param int $invoiceCount
     * @param int $isCrossMonth
     * @return HotaiOrderInvoiceLogsInterface|null
     * @throws LocalizedException
     */
    public function saveOrderInvoiceLogs(
        int $orderId,
        string $invoiceNumber,
        string $hotaiCheckoutNumber,
        int $status,
        int $pointUsed,
        int $includeTax,
        int $isReverse = 0,
        int $invoiceCount = 1,
        int $isCrossMonth = 0,
    ): ?HotaiOrderInvoiceLogsInterface {
        if ($this->checkEcpayInvoiceHotaiOrderInvoiceLogs($orderId, $status, $hotaiCheckoutNumber, $isReverse) !== 0) {
            return null;
        }

        $excludeTax = $this->transferToTwTax($includeTax, true);
        $tax        = $includeTax - $excludeTax;
        $data       = clone $this->hotaiOrderInvoiceLogsInterface;

        $createdAt = Carbon::now('Asia/Taipei');
//        if (empty($invoiceNumber)
//            && $isReverse === HotaiOrderInvoiceLogs::NO_REVERSE
//            && $status === HotaiOrderInvoiceLogs::NO_INVOICE
//            && $invoiceCount === 1
//        ) {
//            $createdAt = $this->getInvoiceTime('point', $orderId);
//        }

        return $this->hotaiOrderInvoiceLogsRepository->save(
            $data->setOrderId($orderId)
                ->setInvoiceNumber($invoiceNumber)
                ->setHotaiCheckoutNumber($hotaiCheckoutNumber)
                ->setStatus($status)
                ->setPointUsed($pointUsed)
                ->setIncludeTax($includeTax)
                ->setExcludeTax($excludeTax)
                ->setTax($tax)
                ->setIsReverse($isReverse)
                ->setInvoiceCount($invoiceCount)
                ->setIsCrossMonth($isCrossMonth)
                ->setCreatedAt($createdAt)
                ->setUpdatedAt(Carbon::now('Asia/Taipei'))
        );
    }

    /**
     * @param $hotaiOrderInvoiceLogsId
     * @param array $items
     * @return void
     * @throws LocalizedException
     */
    public function saveOrderItemInvoiceLogs($hotaiOrderInvoiceLogsId, array $items): void
    {
        foreach ($items as $item) {
            $data = clone $this->hotaiOrderItemInvoiceLogsInterface;

            if (isset($item['discount_amount_include_tax'])) {
                $data->setDiscountAmountIncludeTax($item['discount_amount_include_tax']);
            }

            if (isset($item['discount_amount_exclude_tax'])) {
                $data->setDiscountAmountExcludeTax($item['discount_amount_exclude_tax']);
            }

            if (isset($item['discount_amount_tax'])) {
                $data->setDiscountAmountTax($item['discount_amount_tax']);
            }

            if (isset($item['export_report'])) {
                $data->setExportReport($item['export_report']);
            }

            $this->hotaiOrderItemInvoiceLogsRepository->save(
                $data->setHotaiOrderInvoiceLogId($hotaiOrderInvoiceLogsId)
                    ->setOrderItemId($item['order_item_id'])
                    ->setOrderItemName($item['order_item_name'])
                    ->setQty($item['qty'])
                    ->setIncludeTax($item['include_tax'])
                    ->setExcludeTax($item['exclude_tax'])
                    ->setType($item['type'])
                    ->setTax($item['tax'])
                    ->setCreatedAt(Carbon::now('Asia/Taipei'))
                    ->setUpdatedAt(Carbon::now('Asia/Taipei'))
            );
        }
    }

    /**
     * @param Order $order
     * @param $values
     * @return void
     */
    private function setValuesForOrder(Order $order, $values = [])
    {
        foreach ($values as $name => $value) {
            $order->setData($name, $value);
        }
    }

    /**
     * @param $orderId
     * @param $values
     * @return void
     * @throws LocalizedException
     */
    private function directSaveDataForOrder($orderId, $values)
    {
        foreach ($values as $key => $value) {
            $this->_orderService->setOrderData($orderId, $key, $value);
        }
    }

    /**
     * @param $orderId
     * @param $saleAmount
     * @param $invoiceItems
     * @param $pointUsedTotal
     * @param int $invoiceCount
     * @return bool
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function freeOrderInvoice($orderId, $saleAmount, $invoiceItems, $pointUsedTotal, int $invoiceCount = 1): bool
    {
        try {
            $order = $this->_orderService->getOrder($orderId);
            $generateHotaiCheckoutNumberCond = $saleAmount == 0;
            $hotaiCheckoutNumber = $generateHotaiCheckoutNumberCond ? $this->generateHotaiCheckoutNumber($invoiceCount, $orderId) : '';
            $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                $orderId,
                '',
                $hotaiCheckoutNumber,
                HotaiOrderInvoiceLogs::NO_INVOICE,
                $pointUsedTotal,
                $saleAmount,
                0,
                $invoiceCount
            );

            if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog))
            {
                $this->saveOrderItemInvoiceLogs(
                    $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                    , $invoiceItems['item_logs']
                );
                $values = [
                    'hotai_order_invoice_log_id' => $orderInvoiceLog->getHotaiOrderInvoiceLogsId(),
                    'ecpay_invoice_updated_at' => Carbon::now('Asia/Taipei'),
                    'ecpay_invoice_status' => HotaiOrderInvoiceLogs::NO_INVOICE,
                    'hotai_checkout_number' => $hotaiCheckoutNumber,
                    'ecpay_invoice_auto_tag' => 0,
                    'ecpay_invoice_tag' => 1
                ];
                $this->setValuesForOrder($order, $values);
                $this->directSaveDataForOrder($orderId, $values);
                // 執行Magento自己的發票程序
                $this->_orderService->setOrderInvoice($orderId);
                $comment = '開立成功(純點)' . '，發票號碼：' . $hotaiCheckoutNumber;
            }

            if (is_null($orderInvoiceLog)){
                $rowData = $this->getEcpayInvoiceHotaiOrderInvoiceLog($orderId,
                    HotaiOrderInvoiceLogs::NO_INVOICE, $hotaiCheckoutNumber, 0
                );
                $values = [
                    'hotai_order_invoice_log_id' => $rowData['hotai_order_invoice_logs_id'],
                    'ecpay_invoice_updated_at' =>$rowData['created_at'],
                    'ecpay_invoice_status' =>HotaiOrderInvoiceLogs::NO_INVOICE,
                    'hotai_checkout_number' => $hotaiCheckoutNumber,
                    'ecpay_invoice_auto_tag' => 0,
                    'ecpay_invoice_tag' => 1
                ];
                $this->setValuesForOrder($order, $values);
                $this->directSaveDataForOrder($orderId, $values);

                $comment = '開立成功(純點-回寫)' . '，發票號碼：' . $hotaiCheckoutNumber;
            }

            $this->_orderService->setOrderCommentForBack($orderId, $comment ?? '開立成功(無狀態)' . '，發票號碼：' . $hotaiCheckoutNumber);

            return true;
        } catch (Throwable $t) {
            $comment = '開立失敗(1014)' . $t->getMessage();
            $this->writeLog('一般開立發票失敗(1014):' . $comment);
            $this->_orderService->setOrderCommentForBack($orderId, $comment);
            return false;
        }
    }

    /**
     * @param $orderId
     * @param $saleAmount
     * @param $ecpayInvoiceNumber
     * @param $invoiceItems
     * @param $invoiceStatus
     * @param int $invoiceCount
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function setInvalidOrder($orderId, $saleAmount, $ecpayInvoiceNumber, $invoiceItems, $invoiceStatus, int $invoiceCount = 1): void
    {
        $order = $this->_orderService->getOrder($orderId);
        if ($invoiceStatus === HotaiOrderInvoiceLogs::DISCOUNT) {
            $isCrossMonth = 1;
        }

        if ($saleAmount == 0) {
            $invoiceStatus = HotaiOrderInvoiceLogs::NO_INVOICE;
        }

        $hotaiCheckoutNumber = ($saleAmount == 0) ? $this->generateHotaiCheckoutNumber($invoiceCount, $orderId) : $ecpayInvoiceNumber;
        $values = [
            'ecpay_invoice_tag' => 2,
            'ecpay_invoice_issue_type' => 0,
            'ecpay_invoice_od_sob' => '',
            'ecpay_invoice_status' => $invoiceStatus,
            'ecpay_invoice_number' => '',
            'ecpay_invoice_random_number' => '',
            'ecpay_invoice_updated_at' => Carbon::now('Asia/Taipei'),
        ];
        $this->setValuesForOrder($order, $values);
        $this->directSaveDataForOrder($orderId, $values);
        // 清除原先發票欄位
        $ecpayInvoiceNumber = empty($ecpayInvoiceNumber) ? '' : $ecpayInvoiceNumber;
        $hotaiCheckoutNumber = empty($hotaiCheckoutNumber) ? '' : $hotaiCheckoutNumber;

        if ($this->checkEcpayInvoiceHotaiOrderInvoiceLogs($orderId, $invoiceStatus, $hotaiCheckoutNumber, HotaiOrderInvoiceLogs::REVERSE) === 0) {
            try {
                $rowData = $this->getEcpayInvoiceHotaiOrderInvoiceLog($orderId,
                    $invoiceStatus, $hotaiCheckoutNumber, 0, $invoiceCount
                );

                $orderInvoiceLog = $this->saveOrderInvoiceLogs(
                    $orderId,
                    $ecpayInvoiceNumber,
                    $hotaiCheckoutNumber,
                    $invoiceStatus,
                    $rowData['point_used'] ?? 0,
                    $saleAmount,
                    HotaiOrderInvoiceLogs::REVERSE,
                    $invoiceCount,
                    $isCrossMonth ?? 0
                );

                if (isset($invoiceItems['item_logs']) && !is_null($orderInvoiceLog)) {
                    $this->saveOrderItemInvoiceLogs(
                        $orderInvoiceLog->getHotaiOrderInvoiceLogsId()
                        , $invoiceItems['item_logs']
                    );
                }

                $this->_orderService->setOrderData($orderId, 'hotai_order_invoice_log_id', $orderInvoiceLog->getHotaiOrderInvoiceLogsId());
            } catch (Throwable $t) {
                $this->writeLog('saveOrderInvoiceLogs error:' . $t->getMessage());
            }
        }
    }

    /**
     * @param $count
     * @param $orderId
     * @return string
     */
    private function generateHotaiCheckoutNumber($count, $orderId): string
    {
        $prefix = "P";
        $orderIdToBase36 = $this->baseEncodeDecode->base36Encode($orderId);

        $formattedCount = str_pad($count, 2, '0', STR_PAD_LEFT);

        if (strlen($orderIdToBase36) > 7) {
            $orderIdToBase36 = substr($orderIdToBase36, 0, 7);
        } else {
            $orderIdToBase36 = str_pad($orderIdToBase36, 7, '0', STR_PAD_LEFT);
        }

        return $prefix . $formattedCount . $orderIdToBase36;
    }

    /**
     * @param $orderId
     * @return string
     * @throws LocalizedException
     */
    private function getBillingAddress($orderId): string
    {
        // 取得帳單地址資訊
        $billingPostcode = $this->_orderService->getBillingPostcode($orderId);
        $billingRegion   = $this->_orderService->getBillingRegion($orderId);
        $billingCity     = $this->_orderService->getBillingCity($orderId);
        $billingStreet   = $this->_orderService->getBillingStreet($orderId);

        return $billingPostcode . $billingRegion . $billingCity . $billingStreet;
    }

    /**
     * @param $orderId
     * @param $invoiceNo
     * @param $invoiceDate
     * @param $randomNumber
     * @param $merchantTradeNo
     * @param $print
     * @return void
     * @throws LocalizedException
     * @throws Zend_Log_Exception
     */
    private function saveInvoiceData($orderId, $invoiceNo, $invoiceDate, $randomNumber, $merchantTradeNo, $print): void
    {
        $order = $this->_orderService->getOrder($orderId);
        $this->_orderService->setOrderCommentForBack($orderId, '開立發票成功回寫資料...' . $this->_orderService->getOrderState($orderId) . ' ' . $this->_orderService->getOrderStatus($orderId));
        // 一般開立
        // 更新訂單發票欄位
        $values = [
            'ecpay_invoice_number' => $invoiceNo,
            'hotai_checkout_number' => $invoiceNo,
            'ecpay_invoice_date' => $invoiceDate,
            'ecpay_invoice_random_number' => $randomNumber,
            'ecpay_invoice_status' => HotaiOrderInvoiceLogs::CREATED,
            'ecpay_invoice_issue_type' => 1,
            'ecpay_invoice_od_sob' => $merchantTradeNo,
            'ecpay_invoice_print' => $print,
            'ecpay_invoice_updated_at' => Carbon::now('Asia/Taipei'),
            'ecpay_invoice_auto_tag' => 0,
            'ecpay_invoice_tag' => 1,
        ];
        $this->setValuesForOrder($order, $values);
        $this->directSaveDataForOrder($orderId,$values);
        // 執行Magento自己的發票程序
        $this->_orderService->setOrderInvoice($orderId);
    }

    /**
     * @param $orderId
     * @param $status
     * @param $hotaiCheckoutNumber
     * @param $isReverse
     * @return int
     */
    private function checkEcpayInvoiceHotaiOrderInvoiceLogs($orderId, $status, $hotaiCheckoutNumber, $isReverse): int
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from('ecpay_invoice_hotai_order_invoice_logs');
        $select->where(
            'hotai_checkout_number = "'.$hotaiCheckoutNumber.'" AND status = '.$status.' AND order_id = ' . $orderId. ' AND is_reverse = ' . $isReverse
        );
        $result = $connection->fetchRow($select);

        return ($result === false) ? 0 : 1;
    }

    /**
     * @param $orderId
     * @param $status
     * @param $hotaiCheckoutNumber
     * @param $isReverse
     * @param null $invoiceCount
     * @return array
     */
    private function getEcpayInvoiceHotaiOrderInvoiceLog($orderId, $status, $hotaiCheckoutNumber, $isReverse, $invoiceCount = null): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from('ecpay_invoice_hotai_order_invoice_logs');
        $whereCondition = 'hotai_checkout_number = "'.$hotaiCheckoutNumber.'" AND status = '.$status.' AND order_id = ' . $orderId. ' AND is_reverse = ' . $isReverse;

        if ($invoiceCount !== null) {
            $whereCondition .= ' AND invoice_count = ' . $invoiceCount;
        }

        $select->where($whereCondition);
        $result = $connection->fetchRow($select);

        return ($result === false) ? [] : $result;
    }

    /**
     * @param $type
     * @param $orderId
     * @return string
     */
    private function getInvoiceTime($type, $orderId): string
    {
        $nowDateString = Carbon::now('Asia/Taipei')->toDateTimeString();
        $connection = $this->resourceConnection->getConnection();
        try {
            switch ($type) {
                case 'cash':
                    $select = $connection->select()->from('sales_order_status_history');
                    $select->where(
                        'comment like %授權金額為% AND parent_id = ' . $orderId
                    );
                    $result = $connection->fetchRow($select);
                    return count($result) > 0 ? $result['created_at'] : $nowDateString;
                case 'point':
                    $select = $connection->select()
                        ->from(['soi' => 'sales_order_item'], ['order_id', 'hotai_point_deduction_point_trace_no'])
                        ->join(
                            ['hpar' => 'hotai_point_api_record'],
                            'soi.hotai_point_deduction_point_trace_no = hpar.trace_no',
                            ['commit_status', 'commit_datetime']
                        )
                        ->where('soi.order_id = ?', $orderId)
                    ;
                    $result = $connection->fetchRow($select);
                    return $result['commit_status'] == 1 ? $result['commit_datetime'] : $nowDateString;
                default:
                    return $nowDateString;
            }
        } catch (Exception $e) {
            return $nowDateString;
        }
    }
}
