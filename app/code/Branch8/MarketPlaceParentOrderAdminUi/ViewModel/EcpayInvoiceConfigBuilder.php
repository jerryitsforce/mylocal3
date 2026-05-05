<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\ViewModel;

use Ecpay\General\Model\EcpayInvoice;
use Magento\Sales\Model\Order;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class EcpayInvoiceConfigBuilder implements ArgumentInterface
{
    private $order;
    private \Ecpay\General\Helper\Services\Common\EncryptionsService $_encryptionsService;
    private \Ecpay\General\Helper\Services\Common\OrderService $_orderService;
    private \Ecpay\General\Helper\Services\Config\MainService $_mainService;
    private \Ecpay\General\Helper\Services\Config\InvoiceService $_invoiceService;

    /***
     * @param \Ecpay\General\Helper\Services\Common\EncryptionsService $encryptionsService
     * @param \Ecpay\General\Helper\Services\Common\OrderService $orderService
     * @param \Ecpay\General\Helper\Services\Config\MainService $mainService
     * @param \Ecpay\General\Helper\Services\Config\InvoiceService $invoiceService
     */
    public function __construct(
        \Ecpay\General\Helper\Services\Common\EncryptionsService $encryptionsService,
        \Ecpay\General\Helper\Services\Common\OrderService       $orderService,
        \Ecpay\General\Helper\Services\Config\MainService        $mainService,
        \Ecpay\General\Helper\Services\Config\InvoiceService     $invoiceService
    )
    {
        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
    }

    /**
     * @param Order $order
     * @return EcpayInvoiceConfigBuilder
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return false|string
     */
    public function getEncryptData()
    {
        return [
            'order_id' => $this->_encryptionsService->encrypt($this->getOrder()->getId()),
            'protect_code' => $this->_orderService->getProtectCode($this->getOrder()->getId()),
        ];
    }

    /**
     * @return false|string
     */
    public function getInvoiceData()
    {
        // 取得發票資料
        $invoiceTypeCode = $this->_orderService->getecpayInvoiceType($this->getOrder()->getId());
        $invoiceData = [
            'ecpay_invoice_tag' => $this->_orderService->getEcpayInvoiceTag($this->getOrder()->getId()),
            'ecpay_invoice_type' => $invoiceTypeCode ? $this->_invoiceService->getInvoiceTypeTable()[$invoiceTypeCode] : '',
            'ecpay_invoice_date' => $this->_orderService->getEcpayInvoiceDate($this->getOrder()->getId()),
            'ecpay_invoice_issue_type' => $this->_orderService->getEcpayInvoiceIssueType($this->getOrder()->getId()),
            'ecpay_invoice_number' => $this->_orderService->getEcpayInvoiceNumber($this->getOrder()->getId()),
            'ecpay_invoice_od_sob' => $this->_orderService->getEcpayInvoiceOdSob($this->getOrder()->getId()),
            'ecpay_invoice_random_number' => $this->_orderService->getEcpayInvoiceRandomNumber($this->getOrder()->getId()),
        ];

        // 依照 ecpayInvoiceType 加入資料
        $extension = [];
        switch ($invoiceTypeCode) {
            case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                $extension = [
                    'ecpay_invoice_carruer_num' => $this->_orderService->getEcpayInvoiceCarruerNum($this->getOrder()->getId()),
                ];
                break;
            case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                $extension = [
                    'ecpay_invoice_customer_company' => $this->_orderService->getEcpayInvoiceCustomerCompany($this->getOrder()->getId()),
                    'ecpay_invoice_customer_identifier' => $this->_orderService->getEcpayInvoiceCustomerIdentifier($this->getOrder()->getId()),
                ];
                break;
            case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                $extension = [
                    'ecpay_invoice_love_code' => $this->_orderService->getEcpayInvoiceLoveCode($this->getOrder()->getId()),
                ];
                break;
        }
        $invoiceData = array_merge($invoiceData, $extension);

        return ($invoiceData);
    }

    /**
     * 發票模組是否啟動
     *
     * @return bool
     */
    public function getIvoiceModuleEnable()
    {
        return $this->_mainService->getMainConfig('ecpay_enabled_invoice');
    }

    /**
     * @return array
     */

    public function getConfig()
    {
        return [
            "encryptData" => $this->getEncryptData(),
            "invoiceData" => $this->getInvoiceData(),
            "invoiceModuleEnable" => $this->getIvoiceModuleEnable()
        ];
    }
}
