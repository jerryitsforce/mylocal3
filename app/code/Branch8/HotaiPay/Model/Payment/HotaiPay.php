<?php

namespace Branch8\HotaiPay\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class HotaiPay extends AbstractMethod
{
    const CODE = "hotaipay";
    protected $_code = self::CODE;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canRefund = true;
    protected $_canCapture = false;

    protected $_isInitializeNeeded = true;
    /**
     * Index resultPageFactory
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function isAvailable(
        CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
